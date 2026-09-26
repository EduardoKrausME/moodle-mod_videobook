<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Bulk chapter CSV import.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once("{$CFG->libdir}/formslib.php");

use mod_videobook\chapter_manager;
use mod_videobook\form\import_form;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videobook', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videobook:managechapters', $context);

$url = new moodle_url('/mod/videobook/import.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('importchapters', 'videobook'));
$PAGE->set_heading(format_string($course->fullname));

$form = new import_form($url);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]));
}
if ($data = $form->get_data()) {
    $usercontext = context_user::instance($USER->id);
    $files = get_file_storage()->get_area_files(
        $usercontext->id, 'user', 'draft', (int)$data->csvfile, 'id', false
    );
    $file = $files ? reset($files) : null;
    if (!$file) {
        throw new moodle_exception('csvmissing', 'videobook');
    }

    $content = preg_replace('/^\xEF\xBB\xBF/', '', $file->get_content());
    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $content);
    rewind($handle);
    $firstline = fgets($handle);
    rewind($handle);
    $delimiter = substr_count((string)$firstline, ';') > substr_count((string)$firstline, ',') ? ';' : ',';
    $headers = fgetcsv($handle, 0, $delimiter);
    $headers = array_map(static fn($value) => strtolower(trim((string)$value)), $headers ?: []);
    if (!in_array('title', $headers, true)) {
        throw new moodle_exception('csvtitlemissing', 'videobook');
    }

    $rows = [];
    while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (!array_filter($values, static fn($value) => trim((string)$value) !== '')) {
            continue;
        }
        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = trim((string)($values[$index] ?? ''));
        }
        if (($row['title'] ?? '') !== '') {
            $row['_rowindex'] = count($rows);
            $rows[] = $row;
        }
    }
    fclose($handle);

    $hasexplicitorder = (bool)array_filter($rows, static fn(array $row): bool =>
        isset($row['order']) && $row['order'] !== ''
    );
    if ($hasexplicitorder) {
        usort($rows, static function(array $a, array $b): int {
            $aorder = isset($a['order']) && $a['order'] !== '' ? (int)$a['order'] : PHP_INT_MAX;
            $border = isset($b['order']) && $b['order'] !== '' ? (int)$b['order'] : PHP_INT_MAX;
            return $aorder === $border
                ? ((int)$a['_rowindex'] <=> (int)$b['_rowindex'])
                : ($aorder <=> $border);
        });
    }

    $manager = new chapter_manager();
    $count = 0;
    foreach ($rows as $row) {
        $videourl = $row['video_url'] ?? ($row['videourl'] ?? '');
        $source = 'none';
        if ($videourl !== '') {
            $host = strtolower((string)parse_url($videourl, PHP_URL_HOST));
            if (str_contains($host, 'youtu')) {
                $source = 'youtube';
            } else if (str_contains($host, 'vimeo')) {
                $source = 'vimeo';
            } else {
                $source = 'url';
            }
        }
        $completion = $row['completion'] ?? ($source === 'none' ? 'manual' : 'percent');
        if (!in_array($completion, ['view', 'percent', 'ended', 'manual', 'percent_or_manual'], true)) {
            $completion = $source === 'none' ? 'manual' : 'percent';
        }
        if ($source === 'none' && in_array($completion, ['percent', 'ended', 'percent_or_manual'], true)) {
            $completion = 'manual';
        }

        $record = (object)[
            'videobookid' => $activity->id,
            'title' => $row['title'],
            'sectiontitle' => $row['section'] ?? '',
            'visible' => !isset($row['visible']) ||
                !in_array(strtolower($row['visible']), ['0', 'no', 'false', 'não'], true),
            'content_editor' => ['text' => $row['content'] ?? '', 'format' => FORMAT_HTML, 'itemid' => 0],
            'transcript_editor' => ['text' => $row['transcript'] ?? '', 'format' => FORMAT_HTML, 'itemid' => 0],
            'videosource' => $source,
            'videourl' => $videourl,
            'completiontype' => $completion,
            'minimumpercent' => (int)($row['minimum_percent'] ?? 80),
            'contentorder' => 'video_content_transcript_resources',
            'links' => $row['links'] ?? '',
        ];
        $manager->create($record, $context);
        $count++;
    }

    redirect(new moodle_url('/mod/videobook/manage.php', ['id' => $cm->id]),
        get_string('chaptersimported', 'videobook', $count), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importchapters', 'videobook'));
$form->display();
echo $OUTPUT->footer();
