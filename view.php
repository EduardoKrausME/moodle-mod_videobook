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
 * Student view for Video Book.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_videobook\chapter_manager;
use mod_videobook\progress_manager;

$id = required_param('id', PARAM_INT);
$chapterid = optional_param('chapter', 0, PARAM_INT);

$cm = get_coursemodule_from_id('videobook', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:view', $context);

$PAGE->set_url('/mod/videobook/view.php', ['id' => $cm->id] + ($chapterid ? ['chapter' => $chapterid] : []));
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$chaptermanager = new chapter_manager();
$progressmanager = new progress_manager();
$chapters = $chaptermanager->get_chapters((int)$activity->id);
$canmanage = has_capability('mod/videobook:managechapters', $context);
$istracked = !isguestuser();
$progress = $istracked ? $progressmanager->get_user_progress((int)$activity->id, (int)$USER->id) : [];

if (!$chapters) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($activity->name));
    if ($canmanage) {
        echo $OUTPUT->notification(get_string('nochaptersteacher', 'videobook'), 'info');
        echo $OUTPUT->single_button(new moodle_url('/mod/videobook/chapter.php',
            ['cmid' => $cm->id]), get_string('addchapter', 'videobook'));
    } else {
        echo $OUTPUT->notification(get_string('nochaptersstudent', 'videobook'), 'info');
    }
    echo $OUTPUT->footer();
    exit;
}

$current = null;
if ($chapterid) {
    foreach ($chapters as $chapter) {
        if ((int)$chapter->id === $chapterid) {
            $current = $chapter;
            break;
        }
    }
    if (!$current) {
        throw new moodle_exception('invalidchapter', 'videobook');
    }
} else {
    $current = $chaptermanager->choose_default_chapter($chapters, $progress) ?? reset($chapters);
}

if (!$chaptermanager->can_access($activity, $chapters, (int)$current->id, $progress, $canmanage)) {
    redirect(new moodle_url('/mod/videobook/view.php', ['id' => $cm->id]), get_string('chapterlocked', 'videobook'), null,
        \core\output\notification::NOTIFY_WARNING);
}

if ($istracked) {
    $currentprogress = $progressmanager->touch((int)$activity->id, (int)$current->id, (int)$USER->id);
    $progress[(int)$current->id] = $currentprogress;
} else {
    $currentprogress = null;
}

$event = \mod_videobook\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videobook', $activity);
$event->trigger();
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$nav = [];
$currentindex = 0;
foreach ($chapters as $index => $chapter) {
    if ((int)$chapter->id === (int)$current->id) {
        $currentindex = $index;
    }
    $record = $progress[(int)$chapter->id] ?? null;
    $status = $record ? (int)$record->status : progress_manager::STATUS_NOTSTARTED;
    $accessible = $chaptermanager->can_access($activity, $chapters, (int)$chapter->id, $progress, $canmanage);
    $nav[] = [
        'number' => $index + 1,
        'title' => format_string($chapter->title),
        'current' => (int)$chapter->id === (int)$current->id,
        'locked' => !$accessible,
        'url' => $accessible ? (new moodle_url('/mod/videobook/view.php', [
            'id' => $cm->id,
            'chapter' => $chapter->id,
        ]))->out(false) : '',
        'statusnotstarted' => $status === progress_manager::STATUS_NOTSTARTED,
        'statusinprogress' => $status === progress_manager::STATUS_INPROGRESS,
        'statuscompleted' => $status === progress_manager::STATUS_COMPLETED,
        'statustext' => get_string('status' . $status, 'videobook'),
        'percent' => $record ? round((float)$record->percent) : 0,
    ];
}

$previousurl = '';
if ($currentindex > 0) {
    $previous = $chapters[$currentindex - 1];
    $previousurl = (new moodle_url('/mod/videobook/view.php', ['id' => $cm->id, 'chapter' => $previous->id]))->out(false);
}
$nexturl = '';
$nextunlockurl = '';
$nextlocked = false;
if (isset($chapters[$currentindex + 1])) {
    $next = $chapters[$currentindex + 1];
    $nextunlockurl = (new moodle_url('/mod/videobook/view.php', ['id' => $cm->id, 'chapter' => $next->id]))->out(false);
    if ($chaptermanager->can_access($activity, $chapters, (int)$next->id, $progress, $canmanage)) {
        $nexturl = (new moodle_url('/mod/videobook/view.php', ['id' => $cm->id, 'chapter' => $next->id]))->out(false);
    } else {
        $nextlocked = true;
    }
}

$resources = $chaptermanager->get_resources($current, $context);
$hasvideo = $current->videosource !== 'none';
$player = ['type' => 'none'];
if ($hasvideo) {
    $player = $chaptermanager->get_player_config($current, $context);
}
$playerconfig = $player + [
        'cmid' => (int)$cm->id,
        'chapterid' => (int)$current->id,
        'resumeplayback' => (int)$activity->resumeplayback,
        'allowseek' => (bool)$activity->allowseek,
        'lastposition' => $currentprogress ? (float)$currentprogress->lastposition : 0,
        'segments' => $currentprogress ? json_decode((string)$currentprogress->segments, true) : [],
        'tracked' => $istracked,
    ];

$bookpercent = $istracked ? $progressmanager->get_book_percent((int)$activity->id, (int)$USER->id) : 0;
$currentstatus = $currentprogress ? (int)$currentprogress->status : progress_manager::STATUS_NOTSTARTED;
$currentpercent = $currentprogress ? round((float)$currentprogress->percent) : 0;

$data = [
    'name' => format_string($activity->name),
    'hasintro' => trim((string)$activity->intro) !== '',
    'intro' => format_module_intro('videobook', $activity, $cm->id, false),
    'chapters' => $nav,
    'chaptertitle' => format_string($current->title),
    'chapternumber' => $currentindex + 1,
    'chaptercount' => count($chapters),
    'chapterpositiontext' => get_string('chapterxofy', 'videobook', (object)[
        'current' => $currentindex + 1,
        'total' => count($chapters),
    ]),
    'chaptertext' => $chaptermanager->format_content($current, $context),
    'haschaptertext' => trim((string)$current->content) !== '',
    'hasvideo' => $hasvideo,
    'html5' => $player['type'] === 'html5',
    'youtube' => $player['type'] === 'youtube',
    'vimeo' => $player['type'] === 'vimeo',
    'videourl' => $player['url'] ?? '',
    'captions' => $resources['captions'],
    'configjson' => json_encode($playerconfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    'imageurl' => $resources['imageurl'],
    'hasimage' => $resources['imageurl'] !== '',
    'attachments' => $resources['attachments'],
    'hasattachments' => !empty($resources['attachments']),
    'links' => array_map(static function (array $link): array {
        $link['displaylabel'] = $link['label'] !== '' ? $link['label'] : $link['url'];
        return $link;
    }, $resources['links']),
    'haslinks' => !empty($resources['links']),
    'previousurl' => $previousurl,
    'hasprevious' => $previousurl !== '',
    'nexturl' => $nexturl,
    'hasnext' => $nexturl !== '',
    'nextlocked' => $nextlocked,
    'nextunlockurl' => $nextunlockurl,
    'currentpercent' => $currentpercent,
    'currentcompleted' => $currentstatus === progress_manager::STATUS_COMPLETED,
    'bookpercent' => round($bookpercent),
    'tracked' => $istracked,
    'textchapter' => !$hasvideo,
    'cancomplete' => $istracked && !$hasvideo && $currentstatus !== progress_manager::STATUS_COMPLETED,
    'manageurl' => (new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]))->out(false),
    'reporturl' => (new moodle_url('/mod/videobook/report.php', ['id' => $cm->id]))->out(false),
    'canmanage' => $canmanage,
    'canreport' => has_capability('mod/videobook:viewreport', $context),
];

$PAGE->requires->js_call_amd('mod_videobook/book', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videobook/view', $data);
echo $OUTPUT->footer();
