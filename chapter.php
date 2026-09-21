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
 * Create or edit a chapter.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once("{$CFG->libdir}/formslib.php");

use mod_videobook\chapter_manager;
use mod_videobook\form\chapter_form;

$cmid = required_param('cmid', PARAM_INT);
$chapterid = optional_param('chapterid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videobook', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);

$manager = new chapter_manager();
if ($chapterid) {
    $chapter = $manager->get_chapter($chapterid, (int)$activity->id);
    $chapter = $manager->prepare_form_data($chapter, $context);
} else {
    $chapter = (object)[
        'id' => 0,
        'videobookid' => $activity->id,
        'cmid' => $cm->id,
        'title' => '',
        'content' => '',
        'contentformat' => FORMAT_HTML,
        'videosource' => 'none',
        'videourl' => '',
        'links' => '',
        'minimumpercent' => 80,
    ];
    $draftid = file_get_submitted_draft_itemid('content_editor');
    $chapter->content_editor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => $draftid];
    foreach (['videofile', 'chapterimage', 'attachments', 'captions'] as $field) {
        $chapter->{$field} = file_get_submitted_draft_itemid($field);
    }
}
$chapter->cmid = $cm->id;
$chapter->videobookid = $activity->id;

$PAGE->set_url('/mod/videobook/chapter.php', ['cmid' => $cm->id] + ($chapterid ? ['chapterid' => $chapterid] : []));
$PAGE->set_title($chapterid ? get_string('editchapter', 'videobook') : get_string('addchapter', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));

$form = new chapter_form(null, ['context' => $context]);
$form->set_data($chapter);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]));
}
if ($data = $form->get_data()) {
    if ($chapterid) {
        $data->id = $chapterid;
        $manager->update($data, $context);
    } else {
        $manager->create($data, $context);
    }
    redirect(new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]), get_string('chaptersaved', 'videobook'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($chapterid ? get_string('editchapter', 'videobook') : get_string('addchapter', 'videobook'));
$form->display();
echo $OUTPUT->footer();
