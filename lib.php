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
 * Core callbacks for Video Book.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videobook\progress_manager;

/**
 * Declare supported Moodle features.
 *
 * @param string $feature Feature constant.
 * @return bool|null
 */
function videobook_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Create an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videobook_mod_form|null $mform Form instance.
 * @return int
 */
function videobook_add_instance(stdClass $data, ?mod_videobook_mod_form $mform = null): int {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    return $DB->insert_record('videobook', $data);
}

/**
 * Update an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videobook_mod_form|null $mform Form instance.
 * @return bool
 */
function videobook_update_instance(stdClass $data, ?mod_videobook_mod_form $mform = null): bool {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    return $DB->update_record('videobook', $data);
}

/**
 * Delete an activity instance and dependent data.
 *
 * @param int $id Activity id.
 * @return bool
 */
function videobook_delete_instance(int $id): bool {
    global $DB;
    $activity = $DB->get_record('videobook', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videobook', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        get_file_storage()->delete_area_files($context->id, 'mod_videobook');
    }

    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('videobook_progress', ['videobookid' => $id]);
    $DB->delete_records('videobook_chapters', ['videobookid' => $id]);
    $DB->delete_records('videobook', ['id' => $id]);
    $transaction->allow_commit();
    return true;
}

/**
 * Serve protected chapter files.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param context $context Context.
 * @param string $filearea File area.
 * @param array $args Path args.
 * @param bool $forcedownload Forced download.
 * @param array $options Options.
 * @return bool
 */
function mod_videobook_pluginfile($course, $cm, $context, string $filearea, array $args,
                                  bool $forcedownload, array $options = []): bool {
    global $DB;

    $allowedareas = ['video', 'image', 'attachments', 'captions', 'content'];
    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, $allowedareas, true)) {
        return false;
    }

    require_login($course, true, $cm);
    require_capability('mod/videobook:view', $context);

    $itemid = (int)array_shift($args);
    $chapter = $DB->get_record('videobook_chapters', ['id' => $itemid], 'id,videobookid', MUST_EXIST);
    if ((int)$chapter->videobookid !== (int)$cm->instance) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_videobook', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    if ($filearea === 'attachments') {
        $forcedownload = true;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Describe File API areas.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param context $context Context.
 * @return array
 */
function videobook_get_file_areas($course, $cm, $context): array {
    return [
        'video' => get_string('videofile', 'videobook'),
        'image' => get_string('chapterimage', 'videobook'),
        'attachments' => get_string('attachments', 'videobook'),
        'captions' => get_string('captions', 'videobook'),
        'content' => get_string('chaptertext', 'videobook'),
    ];
}

/**
 * Prepare cached course-module information.
 *
 * @param stdClass $cm Course module record.
 * @return cached_cm_info|null
 */
function videobook_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;
    $activity = $DB->get_record('videobook', ['id' => $cm->instance],
        'id,name,intro,introformat,completionallchapters');
    if (!$activity) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videobook', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionallchapters' => (bool)$activity->completionallchapters,
        ];
    }
    return $info;
}

/**
 * Return active completion rule descriptions.
 *
 * @param cached_cm_info $cm Cached module info.
 * @return array
 */
function videobook_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC ||
        empty($cm->customdata['customcompletionrules']['completionallchapters'])) {
        return [];
    }
    return [get_string('completiondetail:allchapters', 'videobook')];
}

/**
 * Legacy completion callback.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param int $userid User id.
 * @param bool $type Expected state.
 * @return bool
 */
function videobook_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;
    $activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
    if (empty($activity->completionallchapters)) {
        return $type;
    }
    return (new progress_manager())->is_book_complete((int)$activity->id, $userid);
}

/**
 * Extend settings navigation with chapter management and report links.
 *
 * @param settings_navigation $settingsnav Settings navigation.
 * @param navigation_node $modulenode Module node.
 * @return void
 */
function videobook_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $modulenode): void {
    global $PAGE;
    if (!$PAGE->cm) {
        return;
    }
    $context = context_module::instance($PAGE->cm->id);
    if (has_capability('mod/videobook:managechapters', $context)) {
        $modulenode->add(
            get_string('managechapters', 'videobook'),
            new moodle_url('/mod/videobook/manage.php', ['id' => $PAGE->cm->id]),
            navigation_node::TYPE_SETTING
        );
    }
    if (has_capability('mod/videobook:viewreport', $context)) {
        $modulenode->add(
            get_string('viewreport', 'videobook'),
            new moodle_url('/mod/videobook/report.php', ['id' => $PAGE->cm->id]),
            navigation_node::TYPE_SETTING
        );
    }
}
