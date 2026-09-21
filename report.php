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
 * Per-student chapter progress report.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_videobook\chapter_manager;
use mod_videobook\progress_manager;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videobook', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:viewreport', $context);

$PAGE->set_url('/mod/videobook/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('reporttitle', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));

$chapters = (new chapter_manager())->get_chapters((int)$activity->id);
$progressmanager = new progress_manager();
$users = get_enrolled_users($context, 'mod/videobook:view', 0,
    "u.id,u.firstname,u.lastname,u.email,u.picture,u.imagealt,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename",
    "u.lastname,u.firstname");
$rows = [];
foreach ($users as $user) {
    if (isguestuser($user) || has_capability('mod/videobook:managechapters', $context, $user->id)) {
        continue;
    }
    $progress = $progressmanager->get_user_progress((int)$activity->id, (int)$user->id);
    $chapterstates = [];
    $lastaccess = 0;
    $completed = 0;
    foreach ($chapters as $chapter) {
        $record = $progress[(int)$chapter->id] ?? null;
        $status = $record ? (int)$record->status : progress_manager::STATUS_NOTSTARTED;
        if ($status === progress_manager::STATUS_COMPLETED) {
            $completed++;
        }
        $lastaccess = max($lastaccess, $record ? (int)$record->lastaccess : 0);
        $chapterstates[] = [
            'statusnotstarted' => $status === progress_manager::STATUS_NOTSTARTED,
            'statusinprogress' => $status === progress_manager::STATUS_INPROGRESS,
            'statuscompleted' => $status === progress_manager::STATUS_COMPLETED,
            'statustext' => get_string('status' . $status, 'videobook'),
            'percent' => $record ? round((float)$record->percent) : 0,
            'hasvideo' => $chapter->videosource !== 'none',
        ];
    }
    $rows[] = [
        'fullname' => fullname($user),
        'email' => $user->email,
        'chapters' => $chapterstates,
        'completed' => $completed,
        'chaptercount' => count($chapters),
        'overallpercent' => count($chapters) ? round(($completed / count($chapters)) * 100) : 0,
        'lastaccess' => $lastaccess ? userdate($lastaccess) : get_string('never', 'videobook'),
        'reseturl' => (new moodle_url('/mod/videobook/resetprogress.php', [
            'id' => $cm->id, 'userid' => $user->id,
        ]))->out(false),
    ];
}

$headers = [];
foreach ($chapters as $index => $chapter) {
    $headers[] = ['number' => $index + 1, 'title' => format_string($chapter->title)];
}

$data = [
    'name' => format_string($activity->name),
    'chapterheaders' => $headers,
    'haschapters' => !empty($headers),
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'viewurl' => (new moodle_url('/mod/videobook/view.php', ['id' => $cm->id]))->out(false),
    'canreset' => has_capability('mod/videobook:resetprogress', $context),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videobook/report', $data);
echo $OUTPUT->footer();
