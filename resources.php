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
 * Manage structured chapter resources.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_videobook\chapter_manager;
use mod_videobook\resource_manager;

$cmid = required_param('cmid', PARAM_INT);
$chapterid = required_param('chapterid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$resourceid = optional_param('resourceid', 0, PARAM_INT);
$direction = optional_param('direction', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('videobook', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);

$chapter = (new chapter_manager())->get_chapter($chapterid, (int)$activity->id);
$manager = new resource_manager();

if ($action === 'delete' && $resourceid) {
    require_sesskey();
    $manager->delete($manager->get_resource($resourceid, $chapterid), $context);
    redirect(new moodle_url('/mod/videobook/resources.php', ['cmid' => $cm->id, 'chapterid' => $chapterid]),
        get_string('resourcedeleted', 'videobook'));
}
if ($action === 'move' && $resourceid && in_array($direction, ['up', 'down'], true)) {
    require_sesskey();
    $manager->move($chapterid, $resourceid, $direction);
    redirect(new moodle_url('/mod/videobook/resources.php', ['cmid' => $cm->id, 'chapterid' => $chapterid]));
}

$PAGE->set_url('/mod/videobook/resources.php', ['cmid' => $cm->id, 'chapterid' => $chapterid]);
$PAGE->set_title(get_string('manageresources', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));

$resources = $manager->get_resources($chapterid, true);
$rows = [];
foreach ($resources as $index => $resource) {
    $rows[] = [
        'title' => format_string($resource->title),
        'typelabel' => get_string('resourcetype:' . $resource->type, 'videobook'),
        'source' => get_string('resourcesource' . $resource->source, 'videobook'),
        'visible' => !empty($resource->visible),
        'editurl' => (new moodle_url('/mod/videobook/resource.php', [
            'cmid' => $cm->id, 'chapterid' => $chapterid, 'resourceid' => $resource->id,
        ]))->out(false),
        'deleteurl' => (new moodle_url('/mod/videobook/resources.php', [
            'cmid' => $cm->id, 'chapterid' => $chapterid, 'action' => 'delete',
            'resourceid' => $resource->id, 'sesskey' => sesskey(),
        ]))->out(false),
        'upurl' => (new moodle_url('/mod/videobook/resources.php', [
            'cmid' => $cm->id, 'chapterid' => $chapterid, 'action' => 'move',
            'resourceid' => $resource->id, 'direction' => 'up', 'sesskey' => sesskey(),
        ]))->out(false),
        'downurl' => (new moodle_url('/mod/videobook/resources.php', [
            'cmid' => $cm->id, 'chapterid' => $chapterid, 'action' => 'move',
            'resourceid' => $resource->id, 'direction' => 'down', 'sesskey' => sesskey(),
        ]))->out(false),
        'canup' => $index > 0,
        'candown' => $index < count($resources) - 1,
    ];
}

$data = [
    'chaptertitle' => format_string($chapter->title),
    'resources' => $rows,
    'hasresources' => !empty($rows),
    'addurl' => (new moodle_url('/mod/videobook/resource.php', [
        'cmid' => $cm->id, 'chapterid' => $chapterid,
    ]))->out(false),
    'backurl' => (new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videobook/resources', $data);
echo $OUTPUT->footer();
