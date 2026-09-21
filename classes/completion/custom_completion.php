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
 * Custom completion for Video Book.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook\completion;

use core_completion\activity_custom_completion;
use mod_videobook\progress_manager;

/**
 * Completion rule requiring all chapters.
 */
class custom_completion extends activity_custom_completion {
    /**
     * Evaluate a custom rule.
     *
     * @param string $rule Rule name.
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $activity = $DB->get_record('videobook', ['id' => $this->cm->instance], '*', MUST_EXIST);
        if ($rule === 'completionallchapters' && !empty($activity->completionallchapters)) {
            return (new progress_manager())->is_book_complete((int)$activity->id, (int)$this->userid)
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return COMPLETION_COMPLETE;
    }

    /**
     * Custom rules supported by the activity.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionallchapters'];
    }

    /**
     * Rule descriptions.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return ['completionallchapters' => get_string('completiondetail:allchapters', 'videobook')];
    }

    /**
     * Completion display order.
     *
     * @return string[]
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionallchapters', 'completionusegrade', 'completionpassgrade'];
    }
}
