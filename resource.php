<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Create or edit a structured chapter resource.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once("{$CFG->libdir}/formslib.php");

use mod_videobook\chapter_manager;
use mod_videobook\form\resource_form;
use mod_videobook\resource_manager;

$cmid = required_param('cmid', PARAM_INT);
$chapterid = required_param('chapterid', PARAM_INT);
$resourceid = optional_param('resourceid', 0, PARAM_INT);
if (!$resourceid) {
    $resourceid = optional_param('id', 0, PARAM_INT);
}

$cm = get_coursemodule_from_id('videobook', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);

$chapter = (new chapter_manager())->get_chapter($chapterid, (int)$activity->id);
$manager = new resource_manager();

if ($resourceid) {
    $resource = $manager->prepare_form_data($manager->get_resource($resourceid, $chapterid), $context);
} else {
    $resource = (object)[
        'id' => 0, 'chapterid' => $chapterid, 'cmid' => $cm->id, 'title' => '',
        'type' => 'complementary', 'source' => 'file', 'description' => '', 'url' => '',
        'visible' => 1, 'resourcefile' => file_get_submitted_draft_itemid('resourcefile'),
    ];
}
$resource->cmid = $cm->id;
$resource->chapterid = $chapterid;

$url = new moodle_url('/mod/videobook/resource.php', [
    'cmid' => $cm->id, 'chapterid' => $chapterid,
] + ($resourceid ? ['resourceid' => $resourceid] : []));
$PAGE->set_url($url);
$PAGE->set_title($resourceid ? get_string('editresource', 'videobook') : get_string('addresource', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));

$form = new resource_form($url);
$form->set_data($resource);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videobook/resources.php', ['cmid' => $cm->id, 'chapterid' => $chapterid]));
}
if ($data = $form->get_data()) {
    if ($resourceid) {
        $data->id = $resourceid;
        $manager->update($data, $context);
    } else {
        $manager->create($data, $context);
    }
    redirect(new moodle_url('/mod/videobook/resources.php', ['cmid' => $cm->id, 'chapterid' => $chapterid]),
        get_string('resourcesaved', 'videobook'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($chapter->title));
$form->display();
echo $OUTPUT->footer();
