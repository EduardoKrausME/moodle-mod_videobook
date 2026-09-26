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
 * Chapter management page.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_videobook\chapter_manager;
use mod_videobook\resource_manager;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videobook', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);

$PAGE->set_url('/mod/videobook/manage.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managechapters', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));

$manager = new chapter_manager();
$order = optional_param_array('chapterorder', [], PARAM_INT);
if (data_submitted() && !empty($order)) {
    require_sesskey();
    $manager->reorder((int)$activity->id, $order);
    redirect(new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]));
}

$chapters = $manager->get_chapters((int)$activity->id, true);
$resourcemanager = new resource_manager();
$rows = [];
foreach ($chapters as $index => $chapter) {
    $completiontype = (string)($chapter->completiontype ?? ($chapter->videosource === 'none' ? 'manual' : 'percent'));
    $completionlabel = match ($completiontype) {
        'view' => get_string('completionview', 'videobook'),
        'ended' => get_string('completionended', 'videobook'),
        'manual' => get_string('completionmanual', 'videobook'),
        'percent_or_manual' => get_string('completionpercentormanual', 'videobook'),
        default => get_string('completionpercent', 'videobook') . ' (' . (int)$chapter->minimumpercent . '%)',
    };
    $imageurl = $manager->get_chapter_image_url($chapter, $context);
    $resources = $resourcemanager->get_resources((int)$chapter->id, true);
    $resourceitems = [];
    foreach ($resources as $resource) {
        $resourceitems[] = [
            'title' => format_string($resource->title),
            'typelabel' => get_string('resourcetype:' . $resource->type, 'videobook'),
            'visible' => !empty($resource->visible),
            'hidden' => empty($resource->visible),
            'editurl' => (new moodle_url('/mod/videobook/resource.php', [
                'cmid' => $cm->id,
                'chapterid' => $chapter->id,
                'resourceid' => $resource->id,
            ]))->out(false),
        ];
    }

    $chapterediturl = (new moodle_url('/mod/videobook/chapter.php', [
        'cmid' => $cm->id,
        'chapterid' => $chapter->id,
    ]))->out(false);

    foreach (get_file_storage()->get_area_files(
        $context->id,
        'mod_videobook',
        'attachments',
        $chapter->id,
        'filename',
        false
    ) as $file) {
        $resourceitems[] = [
            'title' => $file->get_filename(),
            'typelabel' => get_string('legacyattachment', 'videobook'),
            'visible' => true,
            'hidden' => false,
            'editurl' => $chapterediturl,
        ];
    }

    $legacylinks = json_decode((string)$chapter->linksjson, true);
    if (is_array($legacylinks)) {
        foreach ($legacylinks as $link) {
            if (!is_array($link) || empty($link['url'])) {
                continue;
            }
            $label = trim((string)($link['label'] ?? ''));
            $resourceitems[] = [
                'title' => $label !== '' ? format_string($label) : clean_param((string)$link['url'], PARAM_URL),
                'typelabel' => get_string('legacylink', 'videobook'),
                'visible' => true,
                'hidden' => false,
                'editurl' => $chapterediturl,
            ];
        }
    }

    $resourcecount = count($resourceitems);

    $rows[] = [
        'id' => (int)$chapter->id,
        'number' => $index + 1,
        'title' => format_string($chapter->title),
        'section' => format_string((string)$chapter->sectiontitle),
        'hassection' => trim((string)$chapter->sectiontitle) !== '',
        'source' => get_string('source' . $chapter->videosource, 'videobook'),
        'completion' => $completionlabel,
        'visible' => !empty($chapter->visible),
        'hidden' => empty($chapter->visible),
        'imageurl' => $imageurl,
        'hasimage' => $imageurl !== '',
        'resourcecount' => $resourcecount,
        'resources' => $resourceitems,
        'hasresources' => !empty($resourceitems),
        'editurl' => (new moodle_url('/mod/videobook/chapter.php', [
            'cmid' => $cm->id, 'chapterid' => $chapter->id,
        ]))->out(false),
        'resourcesurl' => (new moodle_url('/mod/videobook/resources.php', [
            'cmid' => $cm->id, 'chapterid' => $chapter->id,
        ]))->out(false),
        'duplicateurl' => (new moodle_url('/mod/videobook/chapter_duplicate.php', [
            'cmid' => $cm->id, 'chapterid' => $chapter->id, 'sesskey' => sesskey(),
        ]))->out(false),
        'visibilityurl' => (new moodle_url('/mod/videobook/chapter_visibility.php', [
            'cmid' => $cm->id, 'chapterid' => $chapter->id, 'sesskey' => sesskey(),
        ]))->out(false),
        'deleteurl' => (new moodle_url('/mod/videobook/chapter_delete.php', [
            'cmid' => $cm->id, 'chapterid' => $chapter->id,
        ]))->out(false),
        'upurl' => (new moodle_url('/mod/videobook/chapter_move.php', [
            'cmid' => $cm->id, 'chapterid' => $chapter->id, 'direction' => 'up', 'sesskey' => sesskey(),
        ]))->out(false),
        'downurl' => (new moodle_url('/mod/videobook/chapter_move.php', [
            'cmid' => $cm->id, 'chapterid' => $chapter->id, 'direction' => 'down', 'sesskey' => sesskey(),
        ]))->out(false),
        'canup' => $index > 0,
        'candown' => $index < count($chapters) - 1,
    ];
}

$data = [
    'name' => format_string($activity->name),
    'chapters' => $rows,
    'haschapters' => !empty($rows),
    'addurl' => (new moodle_url('/mod/videobook/chapter.php', ['cmid' => $cm->id]))->out(false),
    'importurl' => (new moodle_url('/mod/videobook/import.php', ['id' => $cm->id]))->out(false),
    'viewurl' => (new moodle_url('/mod/videobook/view.php', ['id' => $cm->id]))->out(false),
    'formurl' => (new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]))->out(false),
    'sesskey' => sesskey(),
];

$PAGE->requires->js_call_amd('mod_videobook/manage', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videobook/manage', $data);
echo $OUTPUT->footer();
