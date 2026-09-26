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
 * Structured resource form.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook\form;

use moodleform;

/**
 * Resource editing form.
 */
class resource_form extends moodleform {
    /**
     * Define form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'chapterid');
        $mform->setType('chapterid', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('resourcetitle', 'videobook'), ['size' => 64]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('select', 'type', get_string('resourcetype', 'videobook'), [
            'pdf' => get_string('resourcetype:pdf', 'videobook'),
            'slides' => get_string('resourcetype:slides', 'videobook'),
            'complementary' => get_string('resourcetype:complementary', 'videobook'),
            'audio' => get_string('resourcetype:audio', 'videobook'),
            'link' => get_string('resourcetype:link', 'videobook'),
            'other' => get_string('resourcetype:other', 'videobook'),
        ]);

        $mform->addElement('textarea', 'description', get_string('resourcedescription', 'videobook'), [
            'rows' => 3, 'cols' => 70,
        ]);
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('select', 'source', get_string('resourcesource', 'videobook'), [
            'file' => get_string('resourcesourcefile', 'videobook'),
            'url' => get_string('resourcesourceurl', 'videobook'),
        ]);
        $mform->setDefault('source', 'file');

        $mform->addElement('filemanager', 'resourcefile', get_string('resourcefile', 'videobook'), null, [
            'subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['*'],
        ]);
        $mform->hideIf('resourcefile', 'source', 'neq', 'file');

        $mform->addElement('url', 'url', get_string('resourceurl', 'videobook'), ['size' => 80], [
            'usefilepicker' => false,
        ]);
        $mform->setType('url', PARAM_URL);
        $mform->hideIf('url', 'source', 'neq', 'url');

        $mform->addElement('selectyesno', 'visible', get_string('resourcevisible', 'videobook'));
        $mform->setDefault('visible', 1);

        $this->add_action_buttons(true, get_string('saveresource', 'videobook'));
    }

    /**
     * Validate resource source.
     *
     * @param array $data Data.
     * @param array $files Files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (($data['source'] ?? 'file') === 'url') {
            if (empty($data['url'])) {
                $errors['url'] = get_string('required');
            }
        } else {
            $draftid = (int)($data['resourcefile'] ?? 0);
            $info = $draftid ? file_get_draft_area_info($draftid) : ['filecount' => 0];
            if (empty($info['filecount'])) {
                $errors['resourcefile'] = get_string('required');
            } else if ((int)$info['filecount'] > 1) {
                $errors['resourcefile'] = get_string('errormaxfiles', 'videobook');
            }
        }
        return $errors;
    }
}
