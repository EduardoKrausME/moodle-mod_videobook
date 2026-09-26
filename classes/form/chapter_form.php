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
 * Chapter editing form.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook\form;

use moodleform;

/**
 * Chapter form.
 */
class chapter_form extends moodleform {
    /**
     * Define form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $context = $this->_customdata['context'] ?? null;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'videobookid');
        $mform->setType('videobookid', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('chaptertitle', 'videobook'), ['size' => 64]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('text', 'sectiontitle', get_string('sectiontitle', 'videobook'), ['size' => 64]);
        $mform->setType('sectiontitle', PARAM_TEXT);
        $mform->addHelpButton('sectiontitle', 'sectiontitle', 'videobook');

        $mform->addElement('selectyesno', 'visible', get_string('chaptervisible', 'videobook'));
        $mform->setDefault('visible', 1);

        $mform->addElement('editor', 'content_editor', get_string('chaptertext', 'videobook'), null, [
            'noclean' => false,
            'context' => $context,
        ]);

        $mform->addElement('editor', 'transcript_editor', get_string('transcript', 'videobook'), null, [
            'noclean' => false,
            'context' => $context,
        ]);
        $mform->addHelpButton('transcript_editor', 'transcript', 'videobook');

        $mform->addElement('select', 'videosource', get_string('videosource', 'videobook'), [
            'none' => get_string('sourcenone', 'videobook'),
            'upload' => get_string('sourceupload', 'videobook'),
            'url' => get_string('sourceurl', 'videobook'),
            'youtube' => get_string('sourceyoutube', 'videobook'),
            'vimeo' => get_string('sourcevimeo', 'videobook'),
        ]);
        $mform->setDefault('videosource', 'none');

        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videobook'), null, [
            'subdirs' => 0,
            'accepted_types' => ['.mp4', '.webm', '.ogv', '.m4v', '.mov', '.m3u8'],
        ]);
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');

        $mform->addElement('url', 'videourl', get_string('videourl', 'videobook'), ['size' => 80], [
            'usefilepicker' => false,
        ]);
        $mform->setType('videourl', PARAM_URL);
        $mform->hideIf('videourl', 'videosource', 'in', ['none', 'upload']);

        $mform->addElement('select', 'completiontype', get_string('completiontype', 'videobook'), [
            'view' => get_string('completionview', 'videobook'),
            'percent' => get_string('completionpercent', 'videobook'),
            'ended' => get_string('completionended', 'videobook'),
            'manual' => get_string('completionmanual', 'videobook'),
            'percent_or_manual' => get_string('completionpercentormanual', 'videobook'),
        ]);
        $mform->setDefault('completiontype', 'percent');
        $mform->addHelpButton('completiontype', 'completiontype', 'videobook');

        $mform->addElement('text', 'minimumpercent', get_string('minimumpercent', 'videobook'), ['size' => 5]);
        $mform->setType('minimumpercent', PARAM_INT);
        $mform->setDefault('minimumpercent', 80);
        $mform->addHelpButton('minimumpercent', 'minimumpercent', 'videobook');
        $mform->hideIf('minimumpercent', 'completiontype', 'notin', ['percent', 'percent_or_manual']);

        $mform->addElement('select', 'contentorder', get_string('contentorder', 'videobook'), [
            'video_content_transcript_resources' => get_string('ordervideocontenttranscriptresources', 'videobook'),
            'video_resources_content_transcript' => get_string('ordervideoresourcescontenttranscript', 'videobook'),
            'video_transcript_content_resources' => get_string('ordervideotranscriptcontentresources', 'videobook'),
            'content_video_transcript_resources' => get_string('ordercontentvideotranscriptresources', 'videobook'),
        ]);
        $mform->setDefault('contentorder', 'video_content_transcript_resources');
        $mform->addHelpButton('contentorder', 'contentorder', 'videobook');

        $mform->addElement('filemanager', 'captions', get_string('captions', 'videobook'), null, [
            'subdirs' => 0,
            'accepted_types' => ['.vtt', '.srt'],
        ]);
        $mform->hideIf('captions', 'videosource', 'in', ['none', 'youtube', 'vimeo']);

        $mform->addElement('html', '<h3>' . get_string('resourcesheader', 'videobook') . '</h3>');
        $mform->addElement('filemanager', 'chapterimage', get_string('chapterimage', 'videobook'), null, [
            'subdirs' => 0,
            'accepted_types' => ['image'],
        ]);
        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'videobook'), null, [
            'subdirs' => 0,
            'accepted_types' => ['*'],
        ]);
        $mform->addElement('textarea', 'links', get_string('complementarylinks', 'videobook'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('links', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('links', 'complementarylinks', 'videobook');

        $this->add_action_buttons(true, get_string('savechapter', 'videobook'));
    }

    /**
     * Validate chapter fields.
     *
     * @param array $data Submitted data.
     * @param array $files Files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $source = $data['videosource'] ?? 'none';
        if ($source === 'upload') {
            $info = !empty($data['videofile']) ? file_get_draft_area_info($data['videofile']) : ['filecount' => 0];
            if (empty($info['filecount'])) {
                $errors['videofile'] = get_string('required');
            }
        }
        if (in_array($source, ['url', 'youtube', 'vimeo'], true) && empty($data['videourl'])) {
            $errors['videourl'] = get_string('required');
        }
        $completiontype = (string)($data['completiontype'] ?? 'percent');
        if (in_array($completiontype, ['percent', 'percent_or_manual'], true)) {
            $minimumpercent = (int)($data['minimumpercent'] ?? 0);
            if ($minimumpercent < 1 || $minimumpercent > 100) {
                $errors['minimumpercent'] = get_string('invalidpercentage', 'videobook');
            }
        }
        if ($source === 'none' && in_array($completiontype, ['percent', 'ended', 'percent_or_manual'], true)) {
            $errors['completiontype'] = get_string('completionrequiresvideo', 'videobook');
        }
        foreach (['videofile', 'chapterimage'] as $field) {
            $draftid = (int)($data[$field] ?? 0);
            if ($draftid > 0) {
                $draftinfo = file_get_draft_area_info($draftid);
                if ((int)$draftinfo['filecount'] > 1) {
                    $errors[$field] = get_string('errormaxfiles', 'videobook');
                }
            }
        }

        $captionsdraftid = (int)($data['captions'] ?? 0);
        if ($captionsdraftid > 0) {
            $draftinfo = file_get_draft_area_info($captionsdraftid);
            if ((int)$draftinfo['filecount'] > 10) {
                $errors['captions'] = get_string('errormaxfiles', 'videobook');
            }
        }

        return $errors;
    }
}
