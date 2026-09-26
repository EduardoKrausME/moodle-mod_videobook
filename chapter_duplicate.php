<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Duplicate a Video Book chapter.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_videobook\chapter_manager;

$cmid = required_param('cmid', PARAM_INT);
$chapterid = required_param('chapterid', PARAM_INT);
require_sesskey();

$cm = get_coursemodule_from_id('videobook', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);

$manager = new chapter_manager();
$chapter = $manager->get_chapter($chapterid, (int)$activity->id);
$manager->duplicate($chapter, $context);

redirect(new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]),
    get_string('chapterduplicated', 'videobook'), null, \core\output\notification::NOTIFY_SUCCESS);
