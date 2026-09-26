<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Chapter management helpers.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook;

use context_module;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Centralises chapter ordering, access, files and player configuration.
 */
class chapter_manager {
    /** @var array Supported video source identifiers. */
    public const SOURCES = ['none', 'upload', 'url', 'youtube', 'vimeo'];

    /**
     * Return chapters ordered for display.
     *
     * @param int $videobookid Activity id.
     * @return stdClass[]
     */
    public function get_chapters(int $videobookid): array {
        global $DB;
        return array_values($DB->get_records('videobook_chapters', ['videobookid' => $videobookid], 'sortorder ASC, id ASC'));
    }

    /**
     * Return one chapter and verify activity ownership.
     *
     * @param int $chapterid Chapter id.
     * @param int $videobookid Activity id.
     * @return stdClass
     */
    public function get_chapter(int $chapterid, int $videobookid): stdClass {
        global $DB;
        return $DB->get_record('videobook_chapters', [
            'id' => $chapterid,
            'videobookid' => $videobookid,
        ], '*', MUST_EXIST);
    }

    /**
     * Return the first chapter the user should see.
     *
     * @param stdClass[] $chapters Ordered chapters.
     * @param array $progress Progress indexed by chapter id.
     * @return stdClass|null
     */
    public function choose_default_chapter(array $chapters, array $progress): ?stdClass {
        foreach ($chapters as $chapter) {
            $state = $progress[$chapter->id]->status ?? progress_manager::STATUS_NOTSTARTED;
            if ((int)$state !== progress_manager::STATUS_COMPLETED) {
                return $chapter;
            }
        }
        return $chapters[0] ?? null;
    }

    /**
     * Check sequential navigation access.
     *
     * @param stdClass $activity Activity.
     * @param stdClass[] $chapters Ordered chapters.
     * @param int $chapterid Target chapter id.
     * @param array $progress Progress indexed by chapter id.
     * @param bool $canmanage Whether editor bypass applies.
     * @return bool
     */
    public function can_access(stdClass $activity, array $chapters, int $chapterid, array $progress, bool $canmanage): bool {
        if ($canmanage || empty($activity->navigationmode)) {
            return true;
        }
        foreach ($chapters as $chapter) {
            if ((int)$chapter->id === $chapterid) {
                return true;
            }
            $state = $progress[$chapter->id]->status ?? progress_manager::STATUS_NOTSTARTED;
            if ((int)$state !== progress_manager::STATUS_COMPLETED) {
                return false;
            }
        }
        return false;
    }

    /**
     * Create a chapter.
     *
     * @param stdClass $data Submitted data.
     * @param context_module $context Context.
     * @return int
     */
    public function create(stdClass $data, context_module $context): int {
        global $DB;
        $maxsort = (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), 0) FROM {videobook_chapters} WHERE videobookid = :bookid',
            ['bookid' => $data->videobookid]
        );
        $record = $this->normalise_record($data);
        $record->sortorder = $maxsort + 10;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $chapterid = $DB->insert_record('videobook_chapters', $record);
        $record->id = $chapterid;
        $this->save_files($record, $context, $data);
        return $chapterid;
    }

    /**
     * Update a chapter.
     *
     * @param stdClass $data Submitted data.
     * @param context_module $context Context.
     * @return void
     */
    public function update(stdClass $data, context_module $context): void {
        global $DB;
        $existing = $this->get_chapter((int)$data->id, (int)$data->videobookid);
        $record = $this->normalise_record($data);
        $record->id = $existing->id;
        $record->sortorder = $existing->sortorder;
        $record->timecreated = $existing->timecreated;
        $record->timemodified = time();
        $DB->update_record('videobook_chapters', $record);
        $this->save_files($record, $context, $data);
    }

    /**
     * Delete a chapter, its progress and files.
     *
     * @param stdClass $chapter Chapter.
     * @param context_module $context Context.
     * @return void
     */
    public function delete(stdClass $chapter, context_module $context): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('videobook_progress', ['chapterid' => $chapter->id]);
        $DB->delete_records('videobook_chapters', ['id' => $chapter->id]);
        foreach (['video', 'image', 'attachments', 'captions', 'content'] as $area) {
            get_file_storage()->delete_area_files($context->id, 'mod_videobook', $area, $chapter->id);
        }
        $transaction->allow_commit();
        $this->renumber((int)$chapter->videobookid);
    }

    /**
     * Move a chapter one position.
     *
     * @param int $videobookid Activity id.
     * @param int $chapterid Chapter id.
     * @param string $direction up or down.
     * @return void
     */
    public function move(int $videobookid, int $chapterid, string $direction): void {
        global $DB;
        $chapters = $this->get_chapters($videobookid);
        $index = null;
        foreach ($chapters as $i => $chapter) {
            if ((int)$chapter->id === $chapterid) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($chapters[$target])) {
            return;
        }
        $first = $chapters[$index];
        $second = $chapters[$target];
        $firstsort = $first->sortorder;
        $first->sortorder = $second->sortorder;
        $second->sortorder = $firstsort;
        $DB->update_record('videobook_chapters', $first);
        $DB->update_record('videobook_chapters', $second);
        $this->renumber($videobookid);
    }

    /**
     * Renumber sort order with gaps.
     *
     * @param int $videobookid Activity id.
     * @return void
     */
    public function renumber(int $videobookid): void {
        global $DB;
        $position = 10;
        foreach ($this->get_chapters($videobookid) as $chapter) {
            if ((int)$chapter->sortorder !== $position) {
                $chapter->sortorder = $position;
                $DB->update_record('videobook_chapters', $chapter);
            }
            $position += 10;
        }
    }

    /**
     * Prepare persisted fields for editing.
     *
     * @param stdClass $chapter Chapter.
     * @param context_module $context Context.
     * @return stdClass
     */
    public function prepare_form_data(stdClass $chapter, context_module $context): stdClass {
        $chapter = file_prepare_standard_editor($chapter, 'content', [
            'maxfiles' => -1,
            'noclean' => false,
            'context' => $context,
        ], $context, 'mod_videobook', 'content', $chapter->id);

        foreach ([
                     'videofile' => ['video', 1, ['.mp4', '.webm', '.ogv', '.m4v', '.mov', '.m3u8']],
                     'chapterimage' => ['image', 1, ['image']],
                     'attachments' => ['attachments', -1, ['*']],
                     'captions' => ['captions', 10, ['.vtt', '.srt']],
                 ] as $field => [$area, $maxfiles, $types]) {
            $draftid = file_get_submitted_draft_itemid($field);
            file_prepare_draft_area($draftid, $context->id, 'mod_videobook', $area, $chapter->id, [
                'subdirs' => 0,
                'maxfiles' => $maxfiles,
                'accepted_types' => $types,
            ]);
            $chapter->{$field} = $draftid;
        }

        $links = $this->decode_links((string)$chapter->linksjson);
        $lines = [];
        foreach ($links as $link) {
            $lines[] = ($link['label'] !== '' ? $link['label'] . '|' : '') . $link['url'];
        }
        $chapter->links = implode("\n", $lines);
        return $chapter;
    }

    /**
     * Build browser-safe player configuration for a chapter.
     *
     * @param stdClass $chapter Chapter.
     * @param context_module $context Context.
     * @return array
     */
    public function get_player_config(stdClass $chapter, context_module $context): array {
        $source = (string)$chapter->videosource;
        if ($source === 'none') {
            return ['type' => 'none'];
        }
        if ($source === 'upload') {
            $url = $this->first_file_url($context, 'video', (int)$chapter->id);
            if ($url === '') {
                throw new moodle_exception('videofilemissing', 'videobook');
            }
            return [
                'type' => 'html5',
                'url' => $url,
                'hls' => (bool)preg_match('/\.m3u8(?:$|\?)/i', $url),
            ];
        }
        if ($source === 'url') {
            return [
                'type' => 'html5',
                'url' => clean_param((string)$chapter->videourl, PARAM_URL),
                'hls' => (bool)preg_match('/\.m3u8(?:$|\?)/i', (string)$chapter->videourl),
            ];
        }
        if ($source === 'youtube') {
            return [
                'type' => 'youtube',
                'youtubeid' => $this->youtube_id((string)$chapter->videourl),
                'youtubehost' => 'https://www.youtube-nocookie.com',
            ];
        }
        if ($source === 'vimeo') {
            $config = $this->vimeo_config((string)$chapter->videourl);
            return [
                'type' => 'vimeo',
                'vimeoid' => $config['id'],
                'vimeohash' => $config['hash'],
                'vimeoplayerurl' => 'https://player.vimeo.com/api/player.js',
            ];
        }
        throw new moodle_exception('invalidvideosource', 'videobook');
    }

    /**
     * Return chapter resource data for templates.
     *
     * @param stdClass $chapter Chapter.
     * @param context_module $context Context.
     * @return array
     */
    public function get_resources(stdClass $chapter, context_module $context): array {
        $resources = [
            'imageurl' => $this->first_file_url($context, 'image', (int)$chapter->id),
            'attachments' => [],
            'captions' => [],
            'links' => $this->decode_links((string)$chapter->linksjson),
        ];
        foreach (get_file_storage()->get_area_files(
            $context->id, 'mod_videobook', 'attachments', $chapter->id, 'filename', false
        ) as $file) {
            $resources['attachments'][] = [
                'name' => $file->get_filename(),
                'url' => moodle_url::make_pluginfile_url(
                    $context->id, 'mod_videobook', 'attachments', $chapter->id,
                    $file->get_filepath(), $file->get_filename(), true
                )->out(false),
            ];
        }
        foreach (get_file_storage()->get_area_files(
            $context->id, 'mod_videobook', 'captions', $chapter->id, 'filename', false
        ) as $file) {
            if (strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION)) !== 'vtt') {
                continue;
            }
            $label = pathinfo($file->get_filename(), PATHINFO_FILENAME);
            $resources['captions'][] = [
                'label' => $label,
                'srclang' => $this->infer_language($label),
                'url' => moodle_url::make_pluginfile_url(
                    $context->id, 'mod_videobook', 'captions', $chapter->id,
                    $file->get_filepath(), $file->get_filename()
                )->out(false),
            ];
        }
        return $resources;
    }

    /**
     * Format chapter body through Moodle File API and filters.
     *
     * @param stdClass $chapter Chapter.
     * @param context_module $context Context.
     * @return string
     */
    public function format_content(stdClass $chapter, context_module $context): string {
        $content = file_rewrite_pluginfile_urls(
            (string)$chapter->content,
            'pluginfile.php',
            $context->id,
            'mod_videobook',
            'content',
            $chapter->id
        );
        return format_text($content, (int)$chapter->contentformat, ['context' => $context]);
    }

    /**
     * Return first protected file URL for chapter area.
     *
     * @param context_module $context Context.
     * @param string $area File area.
     * @param int $chapterid Chapter id.
     * @return string
     */
    private function first_file_url(context_module $context, string $area, int $chapterid): string {
        $files = get_file_storage()->get_area_files(
            $context->id, 'mod_videobook', $area, $chapterid, 'filename', false
        );
        if (!$files) {
            return '';
        }
        $file = reset($files);
        return moodle_url::make_pluginfile_url(
            $context->id, 'mod_videobook', $area, $chapterid,
            $file->get_filepath(), $file->get_filename()
        )->out(false);
    }

    /**
     * Save chapter editor and File API draft areas.
     *
     * @param stdClass $chapter Persisted chapter.
     * @param context_module $context Context.
     * @param stdClass $submitted Submitted form data.
     * @return void
     */
    private function save_files(stdClass $chapter, context_module $context, stdClass $submitted): void {
        global $DB;
        if (isset($submitted->content_editor)) {
            // file_postupdate_standard_editor() expects the *_editor data on the
            // same object it receives. The persisted chapter does not contain
            // content_editor, so copy the submitted editor data before processing it.
            $chapter->content_editor = $submitted->content_editor;
            $chapter = file_postupdate_standard_editor($chapter, 'content', [
                'maxfiles' => -1,
                'noclean' => false,
                'context' => $context,
            ], $context, 'mod_videobook', 'content', $chapter->id);
            $DB->update_record('videobook_chapters', $chapter);
        }

        $areas = [
            'videofile' => ['video', 1, ['.mp4', '.webm', '.ogv', '.m4v', '.mov', '.m3u8']],
            'chapterimage' => ['image', 1, ['image']],
            'attachments' => ['attachments', -1, ['*']],
            'captions' => ['captions', 10, ['.vtt', '.srt']],
        ];
        foreach ($areas as $field => [$area, $maxfiles, $types]) {
            if (!isset($submitted->{$field})) {
                continue;
            }
            file_save_draft_area_files($submitted->{$field}, $context->id, 'mod_videobook', $area, $chapter->id, [
                'subdirs' => 0,
                'maxfiles' => $maxfiles,
                'accepted_types' => $types,
            ]);
        }
        if ($chapter->videosource !== 'upload') {
            get_file_storage()->delete_area_files($context->id, 'mod_videobook', 'video', $chapter->id);
        }
        $this->normalise_captions($context, $chapter->id);
    }

    /**
     * Convert submitted data to a database record.
     *
     * @param stdClass $data Submitted data.
     * @return stdClass
     */
    private function normalise_record(stdClass $data): stdClass {
        $source = clean_param((string)($data->videosource ?? 'none'), PARAM_ALPHA);
        if (!in_array($source, self::SOURCES, true)) {
            $source = 'none';
        }
        return (object)[
            'videobookid' => (int)$data->videobookid,
            'title' => clean_param((string)$data->title, PARAM_TEXT),
            'content' => (string)($data->content_editor['text'] ?? $data->content ?? ''),
            'contentformat' => (int)($data->content_editor['format'] ?? $data->contentformat ?? FORMAT_HTML),
            'videosource' => $source,
            'videourl' => in_array($source, ['url', 'youtube', 'vimeo'], true)
                ? clean_param((string)($data->videourl ?? ''), PARAM_URL) : '',
            'linksjson' => json_encode($this->parse_links((string)($data->links ?? '')),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'minimumpercent' => max(1, min(100, (int)($data->minimumpercent ?? 80))),
        ];
    }

    /**
     * Parse one complementary link per line.
     *
     * @param string $raw Raw links.
     * @return array
     */
    private function parse_links(string $raw): array {
        $links = [];
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $label = count($parts) === 2 ? $parts[0] : '';
            $url = count($parts) === 2 ? $parts[1] : $parts[0];
            $url = clean_param($url, PARAM_URL);
            if ($url !== '') {
                $links[] = ['label' => clean_param($label, PARAM_TEXT), 'url' => $url];
            }
        }
        return $links;
    }

    /**
     * Decode link JSON safely.
     *
     * @param string $json JSON.
     * @return array
     */
    private function decode_links(string $json): array {
        $links = json_decode($json, true);
        return is_array($links) ? array_values(array_filter($links, static function ($link): bool {
            return is_array($link) && !empty($link['url']);
        })) : [];
    }

    /**
     * Extract a YouTube id from supported URLs or ids.
     *
     * @param string $value URL or id.
     * @return string
     */
    private function youtube_id(string $value): string {
        $value = trim($value);
        if (preg_match('/^[A-Za-z0-9_-]{6,20}$/', $value)) {
            return $value;
        }
        $host = strtolower((string)parse_url($value, PHP_URL_HOST));
        $path = trim((string)parse_url($value, PHP_URL_PATH), '/');
        $id = '';
        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = explode('/', $path)[0] ?? '';
        } else if (in_array($host, [
            'youtube.com', 'www.youtube.com', 'm.youtube.com',
            'youtube-nocookie.com', 'www.youtube-nocookie.com',
        ], true)) {
            parse_str((string)parse_url($value, PHP_URL_QUERY), $query);
            $id = (string)($query['v'] ?? '');
            if ($id === '' && preg_match('~(?:embed|shorts)/([A-Za-z0-9_-]{6,20})~', $path, $matches)) {
                $id = $matches[1];
            }
        }
        if (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
            throw new moodle_exception('invalidyoutubeurl', 'videobook');
        }
        return $id;
    }

    /**
     * Extract Vimeo id and optional unlisted hash.
     *
     * @param string $url Vimeo URL.
     * @return array
     */
    private function vimeo_config(string $url): array {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if (!in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            throw new moodle_exception('invalidvimeourl', 'videobook');
        }
        $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
        if (!preg_match('~^(?:video/)?(\d+)(?:/([A-Za-z0-9]+))?$~', $path, $matches)) {
            throw new moodle_exception('invalidvimeourl', 'videobook');
        }
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $hash = $matches[2] ?? '';
        if ($hash === '' && !empty($query['h']) && preg_match('/^[A-Za-z0-9]+$/', $query['h'])) {
            $hash = $query['h'];
        }
        return ['id' => $matches[1], 'hash' => $hash];
    }

    /**
     * Convert SRT captions to WebVTT inside the chapter file area.
     *
     * @param context_module $context Context.
     * @param int $chapterid Chapter id.
     * @return void
     */
    private function normalise_captions(context_module $context, int $chapterid): void {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_videobook', 'captions', $chapterid, 'filename', false);
        foreach ($files as $file) {
            if (strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION)) !== 'srt') {
                continue;
            }
            $content = str_replace(["\r\n", "\r"], "\n", $file->get_content());
            $content = preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $content);
            $content = "WEBVTT\n\n" . preg_replace('/^\d+\s*\n/m', '', (string)$content);
            $record = [
                'contextid' => $context->id,
                'component' => 'mod_videobook',
                'filearea' => 'captions',
                'itemid' => $chapterid,
                'filepath' => $file->get_filepath(),
                'filename' => pathinfo($file->get_filename(), PATHINFO_FILENAME) . '.vtt',
            ];
            if (!$fs->file_exists($record['contextid'], $record['component'], $record['filearea'],
                $record['itemid'], $record['filepath'], $record['filename'])) {
                $fs->create_file_from_string($record, $content);
            }
            $file->delete();
        }
    }

    /**
     * Infer a language code from a caption label.
     *
     * @param string $label Caption label.
     * @return string
     */
    private function infer_language(string $label): string {
        if (preg_match('/(?:^|[._-])([a-z]{2}(?:[-_][a-z]{2})?)$/i', $label, $matches)) {
            return strtolower(str_replace('_', '-', $matches[1]));
        }
        return 'en';
    }
}
