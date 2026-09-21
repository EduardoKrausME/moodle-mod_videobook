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
 * Delete a chapter after confirmation.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_videobook\chapter_manager;

$cmid = required_param('cmid', PARAM_INT);
$chapterid = required_param('chapterid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$cm = get_coursemodule_from_id('videobook', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);
$manager = new chapter_manager();
$chapter = $manager->get_chapter($chapterid, (int)$activity->id);
$returnurl = new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]);

if ($confirm && confirm_sesskey()) {
    $manager->delete($chapter, $context);
    redirect($returnurl, get_string('chapterdeleted', 'videobook'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_url('/mod/videobook/chapter_delete.php', ['cmid' => $cmid, 'chapterid' => $chapterid]);
$PAGE->set_title(get_string('deletechapter', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('deletechapterconfirm', 'videobook', format_string($chapter->title)),
    new moodle_url('/mod/videobook/chapter_delete.php', [
        'cmid' => $cmid, 'chapterid' => $chapterid, 'confirm' => 1, 'sesskey' => sesskey(),
    ]),
    $returnurl
);
echo $OUTPUT->footer();
