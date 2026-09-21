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
 * Reset one learner's Video Book progress.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$cm = get_coursemodule_from_id('videobook', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:resetprogress', $context);
$returnurl = new moodle_url('/mod/videobook/report.php', ['id' => $cm->id]);

if ($confirm && confirm_sesskey()) {
    $DB->delete_records('videobook_progress', ['videobookid' => $activity->id, 'userid' => $userid]);
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm)) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
    }
    redirect($returnurl, get_string('progressreset', 'videobook', fullname($user)), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_url('/mod/videobook/resetprogress.php', ['id' => $id, 'userid' => $userid]);
$PAGE->set_title(get_string('resetprogress', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('resetprogressconfirm', 'videobook', fullname($user)),
    new moodle_url('/mod/videobook/resetprogress.php', [
        'id' => $id, 'userid' => $userid, 'confirm' => 1, 'sesskey' => sesskey(),
    ]),
    $returnurl
);
echo $OUTPUT->footer();
