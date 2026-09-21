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
 * Video Book backup structure.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Builds the complete activity structure.
 */
class backup_videobook_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videobook', ['id'], [
            'name', 'intro', 'introformat', 'navigationmode', 'resumeplayback', 'allowseek',
            'completionallchapters', 'timecreated', 'timemodified',
        ]);
        $chapters = new backup_nested_element('chapters');
        $chapter = new backup_nested_element('chapter', ['id'], [
            'title', 'content', 'contentformat', 'videosource', 'videourl', 'linksjson',
            'minimumpercent', 'sortorder', 'timecreated', 'timemodified',
        ]);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'chapterid', 'userid', 'duration', 'lastposition', 'segments', 'uniquewatched',
            'percent', 'status', 'timecreated', 'timemodified', 'lastaccess',
        ]);

        $activity->add_child($chapters);
        $chapters->add_child($chapter);
        $activity->add_child($progresses);
        $progresses->add_child($progress);

        $activity->set_source_table('videobook', ['id' => backup::VAR_ACTIVITYID]);
        $chapter->set_source_table('videobook_chapters', ['videobookid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $progress->set_source_table('videobook_progress', ['videobookid' => backup::VAR_PARENTID]);
        }

        $progress->annotate_ids('user', 'userid');
        foreach (['video', 'image', 'attachments', 'captions', 'content'] as $filearea) {
            $chapter->annotate_files('mod_videobook', $filearea, 'id');
        }

        return $this->prepare_activity_structure($activity);
    }
}
