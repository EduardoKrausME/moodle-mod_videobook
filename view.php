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
$searchquery = optional_param('q', '', PARAM_TEXT);

$cm = get_coursemodule_from_id('videobook', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:view', $context);

$urlparams = ['id' => $cm->id];
if ($chapterid) {
    $urlparams['chapter'] = $chapterid;
}
if ($searchquery !== '') {
    $urlparams['q'] = $searchquery;
}
$PAGE->set_url('/mod/videobook/view.php', $urlparams);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$chaptermanager = new chapter_manager();
$progressmanager = new progress_manager();
$canmanage = has_capability('mod/videobook:managechapters', $context);
$chapters = $chaptermanager->get_chapters((int)$activity->id, $canmanage);
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
    $currentprogress = $progressmanager->visit_chapter($activity, $current, (int)$USER->id);
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

$formatseconds = static function(float $seconds): string {
    $value = max(0, (int)round($seconds));
    $hours = intdiv($value, 3600);
    $minutes = intdiv($value % 3600, 60);
    $remaining = $value % 60;
    return $hours > 0
        ? sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining)
        : sprintf('%02d:%02d', $minutes, $remaining);
};

$navitems = [];
$currentindex = 0;
foreach ($chapters as $index => $chapter) {
    if ((int)$chapter->id === (int)$current->id) {
        $currentindex = $index;
    }
    $record = $progress[(int)$chapter->id] ?? null;
    $status = $record ? (int)$record->status : progress_manager::STATUS_NOTSTARTED;
    $accessible = $chaptermanager->can_access($activity, $chapters, (int)$chapter->id, $progress, $canmanage);
    $imageurl = $chaptermanager->get_chapter_image_url($chapter, $context);
    $statusicon = $accessible ? match ($status) {
        progress_manager::STATUS_COMPLETED => '✓',
        progress_manager::STATUS_INPROGRESS => '▶',
        default => '○',
    } : '🔒';
    $statustext = $accessible
        ? get_string('status' . $status, 'videobook')
        : get_string('locked', 'videobook');

    $navitems[] = [
        'id' => (int)$chapter->id,
        'number' => $index + 1,
        'title' => format_string($chapter->title),
        'section' => format_string((string)$chapter->sectiontitle),
        'current' => (int)$chapter->id === (int)$current->id,
        'locked' => !$accessible,
        'url' => $accessible ? (new moodle_url('/mod/videobook/view.php', [
            'id' => $cm->id,
            'chapter' => $chapter->id,
        ]))->out(false) : '',
        'statusnotstarted' => $status === progress_manager::STATUS_NOTSTARTED,
        'statusinprogress' => $status === progress_manager::STATUS_INPROGRESS,
        'statuscompleted' => $status === progress_manager::STATUS_COMPLETED,
        'statustext' => $statustext,
        'statusicon' => $statusicon,
        'percent' => $record ? round((float)$record->percent) : 0,
        'imageurl' => $imageurl,
        'hasimage' => $imageurl !== '',
    ];
}

$navsections = [];
$currentsection = null;
foreach ($navitems as $item) {
    $sectionkey = $item['section'];
    if ($currentsection === null || $currentsection['key'] !== $sectionkey) {
        if ($currentsection !== null) {
            $navsections[] = $currentsection;
        }
        $currentsection = [
            'key' => $sectionkey,
            'title' => $sectionkey,
            'hastitle' => $sectionkey !== '',
            'items' => [],
        ];
    }
    $currentsection['items'][] = $item;
}
if ($currentsection !== null) {
    $navsections[] = $currentsection;
}

$previousurl = '';
if ($currentindex > 0) {
    $previous = $chapters[$currentindex - 1];
    if ($chaptermanager->can_access($activity, $chapters, (int)$previous->id, $progress, $canmanage)) {
        $previousurl = (new moodle_url('/mod/videobook/view.php', [
            'id' => $cm->id, 'chapter' => $previous->id,
        ]))->out(false);
    }
}

$nexturl = '';
$nextunlockurl = '';
$nextlocked = false;
if (isset($chapters[$currentindex + 1])) {
    $next = $chapters[$currentindex + 1];
    $nextunlockurl = (new moodle_url('/mod/videobook/view.php', [
        'id' => $cm->id, 'chapter' => $next->id,
    ]))->out(false);
    if ($chaptermanager->can_access($activity, $chapters, (int)$next->id, $progress, $canmanage)) {
        $nexturl = $nextunlockurl;
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
    'lastclienttime' => $currentprogress ? (int)($currentprogress->lastclienttime ?? 0) : 0,
    'tracked' => $istracked,
];

$bookpercent = $istracked ? $progressmanager->get_book_percent((int)$activity->id, (int)$USER->id) : 0;
$currentstatus = $currentprogress ? (int)$currentprogress->status : progress_manager::STATUS_NOTSTARTED;
$currentpercent = $currentprogress ? round((float)$currentprogress->percent) : 0;
$haschaptertext = trim((string)$current->content) !== '';
$hastranscript = trim((string)$current->transcript) !== '';
$hasmaterials = !empty($resources['materials']);
$hasimage = $resources['imageurl'] !== '';
$hascontentpanel = $haschaptertext || $hasimage;
$chaptertext = $haschaptertext ? $chaptermanager->format_content($current, $context) : '';
$transcript = $hastranscript ? $chaptermanager->format_transcript($current, $context) : '';

$videoblock = [
    'isvideo' => true,
    'hasvideo' => $hasvideo,
    'html5' => $player['type'] === 'html5',
    'youtube' => $player['type'] === 'youtube',
    'vimeo' => $player['type'] === 'vimeo',
    'videourl' => $player['url'] ?? '',
    'captions' => $resources['captions'],
];
$contentblock = [
    'iscontent' => true,
    'hascontent' => $hascontentpanel,
    'haschaptertext' => $haschaptertext,
    'chaptertext' => $chaptertext,
    'hasimage' => $hasimage,
    'imageurl' => $resources['imageurl'],
];
$transcriptblock = [
    'istranscript' => true,
    'hastranscript' => $hastranscript,
    'transcript' => $transcript,
];
$materialsblock = [
    'ismaterials' => true,
    'hasmaterials' => $hasmaterials,
    'materials' => $resources['materials'],
];

$blockmap = [
    'video' => $videoblock,
    'content' => $contentblock,
    'transcript' => $transcriptblock,
    'resources' => $materialsblock,
];
$blocks = [];
foreach (explode('_', (string)($current->contentorder ?? 'video_content_transcript_resources')) as $token) {
    if (isset($blockmap[$token])) {
        $blocks[] = $blockmap[$token];
    }
}

$tabs = [];
if ($hascontentpanel) {
    $tabs[] = [
        'id' => 'content',
        'label' => get_string('chaptertext', 'videobook'),
        'active' => empty($tabs),
        'iscontent' => true,
        'haschaptertext' => $haschaptertext,
        'chaptertext' => $chaptertext,
        'hasimage' => $hasimage,
        'imageurl' => $resources['imageurl'],
    ];
}
if ($hastranscript) {
    $tabs[] = [
        'id' => 'transcript',
        'label' => get_string('transcript', 'videobook'),
        'active' => empty($tabs),
        'istranscript' => true,
        'transcript' => $transcript,
    ];
}
if ($hasmaterials) {
    $tabs[] = [
        'id' => 'materials',
        'label' => get_string('materials', 'videobook'),
        'active' => empty($tabs),
        'ismaterials' => true,
        'materials' => $resources['materials'],
    ];
}

$searchresults = [];
if ($searchquery !== '') {
    foreach ($chaptermanager->search_chapters((int)$activity->id, $searchquery, $canmanage) as $chapter) {
        $accessible = $chaptermanager->can_access($activity, $chapters, (int)$chapter->id, $progress, $canmanage);
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags(
            (string)$chapter->content . ' ' . (string)$chapter->transcript
        )));
        if (core_text::strlen($plain) > 180) {
            $plain = core_text::substr($plain, 0, 177) . '...';
        }
        $searchresults[] = [
            'title' => format_string($chapter->title),
            'section' => format_string((string)$chapter->sectiontitle),
            'hassection' => trim((string)$chapter->sectiontitle) !== '',
            'excerpt' => $plain,
            'hasexcerpt' => $plain !== '',
            'locked' => !$accessible,
            'url' => $accessible ? (new moodle_url('/mod/videobook/view.php', [
                'id' => $cm->id, 'chapter' => $chapter->id,
            ]))->out(false) : '',
        ];
    }
}

$showcontinue = !$chapterid && $currentprogress &&
    (int)$currentprogress->status === progress_manager::STATUS_INPROGRESS;
$completiontype = (string)($current->completiontype ?? ($hasvideo ? 'percent' : 'manual'));
$cancomplete = $istracked &&
    in_array($completiontype, ['manual', 'percent_or_manual'], true) &&
    $currentstatus !== progress_manager::STATUS_COMPLETED;

$data = [
    'name' => format_string($activity->name),
    'hasintro' => trim((string)$activity->intro) !== '',
    'intro' => format_module_intro('videobook', $activity, $cm->id, false),
    'navsections' => $navsections,
    'chaptertitle' => format_string($current->title),
    'chapterpositiontext' => get_string('chapterxofy', 'videobook', (object)[
        'current' => $currentindex + 1,
        'total' => count($chapters),
    ]),
    'hasvideo' => $hasvideo,
    'html5' => $player['type'] === 'html5',
    'youtube' => $player['type'] === 'youtube',
    'vimeo' => $player['type'] === 'vimeo',
    'videourl' => $player['url'] ?? '',
    'captions' => $resources['captions'],
    'configjson' => json_encode($playerconfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    'layouttabs' => (string)($activity->layoutmode ?? 'tabs') === 'tabs',
    'layoutcontinuous' => (string)($activity->layoutmode ?? 'tabs') !== 'tabs',
    'blocks' => $blocks,
    'tabs' => $tabs,
    'hastabs' => !empty($tabs),
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
    'cancomplete' => $cancomplete,
    'manageurl' => (new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]))->out(false),
    'reporturl' => (new moodle_url('/mod/videobook/report.php', ['id' => $cm->id]))->out(false),
    'canmanage' => $canmanage,
    'canreport' => has_capability('mod/videobook:viewreport', $context),
    'searchquery' => $searchquery,
    'hassearch' => $searchquery !== '',
    'searchresults' => $searchresults,
    'hassearchresults' => !empty($searchresults),
    'searchurl' => (new moodle_url('/mod/videobook/view.php'))->out(false),
    'cmid' => (int)$cm->id,
    'showcontinue' => $showcontinue,
    'continuepercent' => $currentpercent,
    'continueposition' => $currentprogress && (float)$currentprogress->lastposition > 0
        ? $formatseconds((float)$currentprogress->lastposition) : '',
    'continuestoppedtext' => $currentprogress && (float)$currentprogress->lastposition > 0
        ? get_string('stoppedat', 'videobook', $formatseconds((float)$currentprogress->lastposition)) : '',
    'hascontinueposition' => $currentprogress && (float)$currentprogress->lastposition > 0,
];

$PAGE->requires->js_call_amd('mod_videobook/book', 'init');
if ($data['layouttabs']) {
    $PAGE->requires->js_call_amd('mod_videobook/tabs', 'init');
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videobook/view', $data);
echo $OUTPUT->footer();
