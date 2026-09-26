<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Typed chapter resource management.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook;

use context_module;
use moodle_url;
use stdClass;

/**
 * Stores structured chapter materials separately from legacy attachments and links.
 */
class resource_manager {
    /** @var string[] Supported resource types. */
    public const TYPES = ['pdf', 'slides', 'complementary', 'audio', 'link', 'other'];

    /**
     * Get resources for one chapter.
     *
     * @param int $chapterid Chapter id.
     * @param bool $includehidden Include hidden resources.
     * @return stdClass[]
     */
    public function get_resources(int $chapterid, bool $includehidden = false): array {
        global $DB;
        $conditions = ['chapterid' => $chapterid];
        if (!$includehidden) {
            $conditions['visible'] = 1;
        }
        return array_values($DB->get_records('videobook_resources', $conditions, 'sortorder ASC, id ASC'));
    }

    /**
     * Get one resource.
     *
     * @param int $resourceid Resource id.
     * @param int $chapterid Chapter id.
     * @return stdClass
     */
    public function get_resource(int $resourceid, int $chapterid): stdClass {
        global $DB;
        return $DB->get_record('videobook_resources', [
            'id' => $resourceid,
            'chapterid' => $chapterid,
        ], '*', MUST_EXIST);
    }

    /**
     * Prepare file draft data.
     *
     * @param stdClass $resource Resource.
     * @param context_module $context Context.
     * @return stdClass
     */
    public function prepare_form_data(stdClass $resource, context_module $context): stdClass {
        $draftid = file_get_submitted_draft_itemid('resourcefile');
        file_prepare_draft_area($draftid, $context->id, 'mod_videobook', 'resource', $resource->id, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['*'],
        ]);
        $resource->resourcefile = $draftid;
        return $resource;
    }

    /**
     * Create a resource.
     *
     * @param stdClass $data Submitted data.
     * @param context_module $context Context.
     * @return int
     */
    public function create(stdClass $data, context_module $context): int {
        global $DB;
        $maxsort = (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), 0) FROM {videobook_resources} WHERE chapterid = :chapterid',
            ['chapterid' => $data->chapterid]
        );
        $record = $this->normalise($data);
        $record->sortorder = $maxsort + 10;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->id = $DB->insert_record('videobook_resources', $record);
        $this->save_file($record, $data, $context);
        return (int)$record->id;
    }

    /**
     * Update a resource.
     *
     * @param stdClass $data Submitted data.
     * @param context_module $context Context.
     * @return void
     */
    public function update(stdClass $data, context_module $context): void {
        global $DB;
        $existing = $this->get_resource((int)$data->id, (int)$data->chapterid);
        $record = $this->normalise($data);
        $record->id = $existing->id;
        $record->sortorder = $existing->sortorder;
        $record->timecreated = $existing->timecreated;
        $record->timemodified = time();
        $DB->update_record('videobook_resources', $record);
        $this->save_file($record, $data, $context);
    }

    /**
     * Delete a resource and its file.
     *
     * @param stdClass $resource Resource.
     * @param context_module $context Context.
     * @return void
     */
    public function delete(stdClass $resource, context_module $context): void {
        global $DB;
        get_file_storage()->delete_area_files($context->id, 'mod_videobook', 'resource', $resource->id);
        $DB->delete_records('videobook_resources', ['id' => $resource->id]);
        $this->renumber((int)$resource->chapterid);
    }

    /**
     * Delete all resources in one chapter.
     *
     * @param int $chapterid Chapter id.
     * @param context_module $context Context.
     * @return void
     */
    public function delete_chapter_resources(int $chapterid, context_module $context): void {
        foreach ($this->get_resources($chapterid, true) as $resource) {
            $this->delete($resource, $context);
        }
    }

    /**
     * Move a resource.
     *
     * @param int $chapterid Chapter id.
     * @param int $resourceid Resource id.
     * @param string $direction Direction.
     * @return void
     */
    public function move(int $chapterid, int $resourceid, string $direction): void {
        global $DB;
        $resources = $this->get_resources($chapterid, true);
        $index = null;
        foreach ($resources as $i => $resource) {
            if ((int)$resource->id === $resourceid) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($resources[$target])) {
            return;
        }
        $a = $resources[$index];
        $b = $resources[$target];
        $sort = $a->sortorder;
        $a->sortorder = $b->sortorder;
        $b->sortorder = $sort;
        $DB->update_record('videobook_resources', $a);
        $DB->update_record('videobook_resources', $b);
        $this->renumber($chapterid);
    }

    /**
     * Duplicate chapter resources.
     *
     * @param int $oldchapterid Source chapter.
     * @param int $newchapterid Target chapter.
     * @param context_module $context Context.
     * @return void
     */
    public function duplicate_for_chapter(int $oldchapterid, int $newchapterid, context_module $context): void {
        global $DB;
        foreach ($this->get_resources($oldchapterid, true) as $resource) {
            $oldid = (int)$resource->id;
            unset($resource->id);
            $resource->chapterid = $newchapterid;
            $resource->timecreated = time();
            $resource->timemodified = $resource->timecreated;
            $newid = (int)$DB->insert_record('videobook_resources', $resource);
            $this->copy_file_area($context, $oldid, $newid);
        }
    }

    /**
     * Export one resource for Mustache.
     *
     * @param stdClass $resource Resource.
     * @param context_module $context Context.
     * @return array|null
     */
    public function export_for_template(stdClass $resource, context_module $context): ?array {
        $url = '';
        $download = false;
        if ($resource->source === 'url') {
            $url = clean_param((string)$resource->url, PARAM_URL);
        } else {
            $files = get_file_storage()->get_area_files(
                $context->id, 'mod_videobook', 'resource', $resource->id, 'filename', false
            );
            if ($files) {
                $file = reset($files);
                $url = moodle_url::make_pluginfile_url(
                    $context->id, 'mod_videobook', 'resource', $resource->id,
                    $file->get_filepath(), $file->get_filename(), true
                )->out(false);
                $download = true;
            }
        }
        if ($url === '') {
            return null;
        }
        return [
            'id' => (int)$resource->id,
            'title' => format_string($resource->title),
            'description' => (string)$resource->description,
            'hasdescription' => trim((string)$resource->description) !== '',
            'type' => (string)$resource->type,
            'typelabel' => get_string('resourcetype:' . $resource->type, 'videobook'),
            'url' => $url,
            'download' => $download,
            'external' => $resource->source === 'url',
        ];
    }

    /**
     * Renumber chapter resources.
     *
     * @param int $chapterid Chapter id.
     * @return void
     */
    private function renumber(int $chapterid): void {
        global $DB;
        $sort = 10;
        foreach ($this->get_resources($chapterid, true) as $resource) {
            if ((int)$resource->sortorder !== $sort) {
                $resource->sortorder = $sort;
                $DB->update_record('videobook_resources', $resource);
            }
            $sort += 10;
        }
    }

    /**
     * Normalise submitted resource data.
     *
     * @param stdClass $data Data.
     * @return stdClass
     */
    private function normalise(stdClass $data): stdClass {
        $type = clean_param((string)($data->type ?? 'complementary'), PARAM_ALPHAEXT);
        if (!in_array($type, self::TYPES, true)) {
            $type = 'other';
        }
        $source = (string)($data->source ?? 'file') === 'url' ? 'url' : 'file';
        return (object)[
            'chapterid' => (int)$data->chapterid,
            'title' => clean_param((string)$data->title, PARAM_TEXT),
            'type' => $type,
            'source' => $source,
            'description' => clean_param((string)($data->description ?? ''), PARAM_TEXT),
            'url' => $source === 'url' ? clean_param((string)($data->url ?? ''), PARAM_URL) : '',
            'visible' => empty($data->visible) ? 0 : 1,
        ];
    }

    /**
     * Save a resource file.
     *
     * @param stdClass $resource Resource.
     * @param stdClass $submitted Submitted data.
     * @param context_module $context Context.
     * @return void
     */
    private function save_file(stdClass $resource, stdClass $submitted, context_module $context): void {
        if ($resource->source !== 'file') {
            get_file_storage()->delete_area_files($context->id, 'mod_videobook', 'resource', $resource->id);
            return;
        }
        if (!isset($submitted->resourcefile)) {
            return;
        }
        file_save_draft_area_files($submitted->resourcefile, $context->id, 'mod_videobook', 'resource', $resource->id, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['*'],
        ]);
    }

    /**
     * Copy the File API area of a structured resource.
     *
     * @param context_module $context Context.
     * @param int $oldid Source item id.
     * @param int $newid Target item id.
     * @return void
     */
    private function copy_file_area(context_module $context, int $oldid, int $newid): void {
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, 'mod_videobook', 'resource', $oldid, 'id', false) as $file) {
            $fs->create_file_from_storedfile([
                'contextid' => $context->id,
                'component' => 'mod_videobook',
                'filearea' => 'resource',
                'itemid' => $newid,
                'filepath' => $file->get_filepath(),
                'filename' => $file->get_filename(),
            ], $file);
        }
    }
}
