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
 * Move a chapter up or down.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_videobook\chapter_manager;

$cmid = required_param('cmid', PARAM_INT);
$chapterid = required_param('chapterid', PARAM_INT);
$direction = required_param('direction', PARAM_ALPHA);
require_sesskey();
$cm = get_coursemodule_from_id('videobook', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);
if (!in_array($direction, ['up', 'down'], true)) {
    throw new moodle_exception('invaliddirection', 'videobook');
}
$manager = new chapter_manager();
$manager->get_chapter($chapterid, (int)$activity->id);
$manager->move((int)$activity->id, $chapterid, $direction);
redirect(new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]));
