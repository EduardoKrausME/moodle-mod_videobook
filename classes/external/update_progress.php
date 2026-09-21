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
 * AJAX endpoint for video progress updates.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videobook\chapter_manager;
use mod_videobook\progress_manager;

/**
 * Store server-authoritative watched intervals.
 */
class update_progress extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, get_string('ws:cmid', 'videobook')),
            'chapterid' => new external_value(PARAM_INT, get_string('ws:chapterid', 'videobook')),
            'currentposition' => new external_value(PARAM_FLOAT, get_string('ws:currentposition', 'videobook')),
            'duration' => new external_value(PARAM_FLOAT, get_string('ws:duration', 'videobook')),
            'playbackrate' => new external_value(PARAM_FLOAT, get_string('ws:playbackrate', 'videobook')),
            'segmentstart' => new external_value(PARAM_FLOAT, get_string('ws:segmentstart', 'videobook')),
            'segmentend' => new external_value(PARAM_FLOAT, get_string('ws:segmentend', 'videobook')),
            'playerstate' => new external_value(PARAM_ALPHANUMEXT, get_string('ws:playerstate', 'videobook')),
        ]);
    }

    /**
     * Execute update.
     *
     * @param int $cmid Course module id.
     * @param int $chapterid Chapter id.
     * @param float $currentposition Current position.
     * @param float $duration Duration.
     * @param float $playbackrate Playback rate.
     * @param float $segmentstart Segment start.
     * @param float $segmentend Segment end.
     * @param string $playerstate Player state.
     * @return array
     */
    public static function execute(int $cmid, int $chapterid, float $currentposition, float $duration,
                                   float $playbackrate, float $segmentstart, float $segmentend, string $playerstate): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'chapterid' => $chapterid,
            'currentposition' => $currentposition,
            'duration' => $duration,
            'playbackrate' => $playbackrate,
            'segmentstart' => $segmentstart,
            'segmentend' => $segmentend,
            'playerstate' => $playerstate,
        ]);

        $cm = get_coursemodule_from_id('videobook', $params['cmid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        require_login($course, false, $cm);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videobook:view', $context);

        $activity = $DB->get_record('videobook', ['id' => $cm->instance], '*', MUST_EXIST);
        $chaptermanager = new chapter_manager();
        $chapter = $chaptermanager->get_chapter($params['chapterid'], (int)$activity->id);
        if (!empty($activity->navigationmode)) {
            $progressmanager = new progress_manager();
            $chapters = $chaptermanager->get_chapters((int)$activity->id);
            $userprogress = $progressmanager->get_user_progress((int)$activity->id, (int)$USER->id);
            $canmanage = has_capability('mod/videobook:managechapters', $context);
            if (!$chaptermanager->can_access($activity, $chapters, (int)$chapter->id, $userprogress, $canmanage)) {
                throw new \moodle_exception('chapterlocked', 'videobook');
            }
        }
        if ($chapter->videosource === 'none') {
            throw new \moodle_exception('chapterhasnovideo', 'videobook');
        }

        $manager = new progress_manager();
        $previous = $manager->touch((int)$activity->id, (int)$chapter->id, (int)$USER->id);
        $correctposition = (float)$params['currentposition'];
        $reason = 'accepted';
        if (empty($activity->allowseek)) {
            $maxwatched = $manager->max_watched_position($previous);
            if ($correctposition > $maxwatched + 5 && $maxwatched > 1) {
                $correctposition = $maxwatched;
                $reason = 'seekblocked';
                $params['segmentstart'] = min((float)$params['segmentstart'], $maxwatched);
                $params['segmentend'] = min((float)$params['segmentend'], $maxwatched);
            }
        }

        $progress = $manager->update_video_progress(
            $activity,
            $chapter,
            (int)$USER->id,
            (float)$params['duration'],
            $correctposition,
            (float)$params['segmentstart'],
            (float)$params['segmentend']
        );

        return [
            'accepted' => true,
            'reason' => $reason,
            'correctposition' => $correctposition,
            'percent' => (float)$progress->percent,
            'status' => (int)$progress->status,
            'completed' => (int)$progress->status === progress_manager::STATUS_COMPLETED,
            'bookpercent' => $manager->get_book_percent((int)$activity->id, (int)$USER->id),
            'segments' => (string)$progress->segments,
            'lastposition' => (float)$progress->lastposition,
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'accepted' => new external_value(PARAM_BOOL, get_string('ws:accepted', 'videobook')),
            'reason' => new external_value(PARAM_ALPHANUMEXT, get_string('ws:reason', 'videobook')),
            'correctposition' => new external_value(PARAM_FLOAT, get_string('ws:correctposition', 'videobook')),
            'percent' => new external_value(PARAM_FLOAT, get_string('ws:percent', 'videobook')),
            'status' => new external_value(PARAM_INT, get_string('ws:status', 'videobook')),
            'completed' => new external_value(PARAM_BOOL, get_string('ws:completed', 'videobook')),
            'bookpercent' => new external_value(PARAM_FLOAT, get_string('ws:bookpercent', 'videobook')),
            'segments' => new external_value(PARAM_RAW, get_string('ws:segments', 'videobook')),
            'lastposition' => new external_value(PARAM_FLOAT, get_string('ws:lastposition', 'videobook')),
        ]);
    }
}
