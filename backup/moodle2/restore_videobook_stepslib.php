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
 * Video Book restore structure.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores activity records, user progress and files.
 */
class restore_videobook_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define restore paths.
     *
     * @return array
     */
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('videobook', '/activity/videobook'),
            new restore_path_element('videobook_chapter', '/activity/videobook/chapters/chapter'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videobook_progress', '/activity/videobook/progresses/progress');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore main record.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videobook(array $data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->id = $DB->insert_record('videobook', $data);
        $this->apply_activity_instance($data->id);
        $this->set_mapping('videobook', $oldid, $data->id, true);
    }

    /**
     * Restore chapter.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videobook_chapter(array $data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videobookid = $this->get_new_parentid('videobook');
        $data->id = $DB->insert_record('videobook_chapters', $data);
        $this->set_mapping('videobook_chapter', $oldid, $data->id, true);
    }

    /**
     * Restore one user progress row.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videobook_progress(array $data): void {
        global $DB;
        $data = (object)$data;
        $data->videobookid = $this->get_new_parentid('videobook');
        $data->chapterid = $this->get_mappingid('videobook_chapter', $data->chapterid, 0);
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if ($data->chapterid && $data->userid) {
            $DB->insert_record('videobook_progress', $data);
        }
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        foreach (['video', 'image', 'attachments', 'captions', 'content'] as $filearea) {
            $this->add_related_files('mod_videobook', $filearea, 'videobook_chapter');
        }
    }
}
