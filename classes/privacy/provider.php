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
 * Privacy API provider for Video Book.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for stored learner progress.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videobook_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'duration' => 'privacy:metadata:progress:duration',
            'lastposition' => 'privacy:metadata:progress:lastposition',
            'segments' => 'privacy:metadata:progress:segments',
            'uniquewatched' => 'privacy:metadata:progress:uniquewatched',
            'percent' => 'privacy:metadata:progress:percent',
            'status' => 'privacy:metadata:progress:status',
            'lastaccess' => 'privacy:metadata:progress:lastaccess',
        ], 'privacy:metadata:progress');
        return $collection;
    }

    /**
     * Find contexts containing data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videobook_progress} p ON p.videobookid = cm.instance
                 WHERE p.userid = :userid";
        return (new contextlist())->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videobook',
            'userid' => $userid,
        ]);
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videobook', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $records = $DB->get_records('videobook_progress', [
                'videobookid' => $cm->instance,
                'userid' => $userid,
            ]);
            $export = [];
            foreach ($records as $record) {
                $export[] = (object)[
                    'chapterid' => $record->chapterid,
                    'percent' => $record->percent,
                    'status' => $record->status,
                    'lastposition' => $record->lastposition,
                    'uniquewatched' => $record->uniquewatched,
                    'lastaccess' => transform::datetime($record->lastaccess),
                ];
            }
            writer::with_context($context)->export_data([get_string('privacy:progresspath', 'videobook')], (object)[
                'progress' => $export,
            ]);
        }
    }

    /**
     * Delete all personal data in a context.
     *
     * @param context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if (!$context instanceof context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videobook', $context->instanceid, 0, false, IGNORE_MISSING);
        if ($cm) {
            $DB->delete_records('videobook_progress', ['videobookid' => $cm->instance]);
        }
    }

    /**
     * Delete personal data for a user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videobook', $context->instanceid, 0, false, IGNORE_MISSING);
            if ($cm) {
                $DB->delete_records('videobook_progress', [
                    'videobookid' => $cm->instance,
                    'userid' => $userid,
                ]);
            }
        }
    }

    /**
     * Add users whose data exists in a context.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }
        $sql = "SELECT DISTINCT p.userid
                  FROM {videobook_progress} p
                  JOIN {course_modules} cm ON cm.instance = p.videobookid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['modname' => 'videobook', 'cmid' => $context->instanceid]);
    }

    /**
     * Delete data for users in a context.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function delete_data_for_users(userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videobook', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');
        $params['bookid'] = $cm->instance;
        $DB->delete_records_select('videobook_progress', "videobookid = :bookid AND userid {$insql}", $params);
    }
}
