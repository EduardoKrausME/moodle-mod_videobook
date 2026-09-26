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
 * Progress calculation and persistence.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook;

use stdClass;

/**
 * Maintains server-authoritative watched intervals per chapter.
 */
class progress_manager {
    /** @var int */
    public const STATUS_NOTSTARTED = 0;

    /** @var int */
    public const STATUS_INPROGRESS = 1;

    /** @var int */
    public const STATUS_COMPLETED = 2;

    /**
     * Return all progress rows for a user indexed by chapter id.
     *
     * @param int $videobookid Activity id.
     * @param int $userid User id.
     * @return array
     */
    public function get_user_progress(int $videobookid, int $userid): array {
        global $DB;
        $records = $DB->get_records('videobook_progress', [
            'videobookid' => $videobookid,
            'userid' => $userid,
        ]);
        $result = [];
        foreach ($records as $record) {
            $result[(int)$record->chapterid] = $record;
        }
        return $result;
    }

    /**
     * Touch a chapter when viewed.
     *
     * @param int $videobookid Activity id.
     * @param int $chapterid Chapter id.
     * @param int $userid User id.
     * @return stdClass
     */
    public function touch(int $videobookid, int $chapterid, int $userid): stdClass {
        global $DB;
        $record = $DB->get_record('videobook_progress', ['chapterid' => $chapterid, 'userid' => $userid]);
        $now = time();
        if (!$record) {
            $record = (object)[
                'videobookid' => $videobookid,
                'chapterid' => $chapterid,
                'userid' => $userid,
                'duration' => 0,
                'lastposition' => 0,
                'segments' => '[]',
                'uniquewatched' => 0,
                'percent' => 0,
                'status' => self::STATUS_INPROGRESS,
                'timecreated' => $now,
                'timemodified' => $now,
                'lastaccess' => $now,
                'lastheartbeat' => 0,
                'lastclienttime' => 0,
            ];
            $record->id = $DB->insert_record('videobook_progress', $record);
        } else {
            if ((int)$record->status === self::STATUS_NOTSTARTED) {
                $record->status = self::STATUS_INPROGRESS;
            }
            $record->lastaccess = $now;
            $record->timemodified = $now;
            $DB->update_record('videobook_progress', $record);
        }
        return $record;
    }

    /**
     * Merge a watched interval and recalculate chapter completion.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $chapter Chapter.
     * @param int $userid User id.
     * @param float $duration Media duration.
     * @param float $currentposition Current position.
     * @param float $segmentstart Segment start.
     * @param float $segmentend Segment end.
     * @param float $playbackrate Playback rate reported by the player.
     * @param int $clienttime Monotonic client event time.
     * @return stdClass
     * @throws \dml_exception
     */
    public function update_video_progress(stdClass $activity, stdClass $chapter, int $userid, float $duration,
                                          float $currentposition, float $segmentstart, float $segmentend,
                                          float $playbackrate = 1.0, int $clienttime = 0): stdClass {
        global $DB;

        $record = $this->touch((int)$activity->id, (int)$chapter->id, $userid);
        $now = time();
        $clienttime = $clienttime > 0 ? $clienttime : $now;

        // Ignore duplicated or out-of-order payloads. Their watched interval has either
        // already been applied or belongs to an older player state and must not move
        // the resume position backwards.
        if (!empty($record->lastclienttime) && $clienttime <= (int)$record->lastclienttime) {
            $record->trackingreason = 'stale';
            return $record;
        }

        $reported = min(86400.0, max(0.0, $duration));
        $floor = max(0.0, $currentposition, $segmentstart, $segmentend, $this->max_watched_position($record));
        $duration = $this->normalise_duration((float)$record->duration, $reported, $floor);
        if ($duration <= 0) {
            $record->lastheartbeat = $now;
            $record->lastclienttime = $clienttime;
            $record->timemodified = $now;
            $record->lastaccess = $now;
            $DB->update_record('videobook_progress', $record);
            $record->trackingreason = 'accepted';
            return $record;
        }

        $start = max(0.0, min($duration, min($segmentstart, $segmentend)));
        $end = max(0.0, min($duration, max($segmentstart, $segmentend)));
        $requestedlength = max(0.0, $end - $start);

        // Progress is bounded by server wall-clock time. A modified browser may report
        // arbitrary media positions, but it cannot manufacture minutes of watched
        // content in a few seconds by repeatedly calling the web service.
        $rate = min(4.0, max(0.25, $playbackrate));
        $reference = !empty($record->lastheartbeat) ? (int)$record->lastheartbeat : (int)$record->timecreated;
        $elapsed = max(0, min(90, $now - $reference));
        $allowedlength = min(60.0, ($elapsed * $rate) + 3.0);
        $trackingreason = 'accepted';
        if ($requestedlength > $allowedlength) {
            $end = min($duration, $start + $allowedlength);
            $trackingreason = 'ratecapped';
        }

        $segments = $this->decode_segments((string)$record->segments);
        if ($end > $start + 0.1) {
            $segments[] = [$start, $end];
        }
        $segments = $this->merge_segments($segments, $duration);

        $unique = 0.0;
        foreach ($segments as [$a, $b]) {
            $unique += max(0, $b - $a);
        }
        $percent = min(100, $duration > 0 ? ($unique / $duration) * 100 : 0);

        $record->duration = $duration;
        $record->lastposition = max(0, min($duration, $currentposition));
        $record->segments = json_encode($segments, JSON_PRESERVE_ZERO_FRACTION);
        $record->uniquewatched = $unique;
        $record->percent = $percent;
        $record->status = $percent + 0.0001 >= (int)$chapter->minimumpercent
            ? self::STATUS_COMPLETED : self::STATUS_INPROGRESS;
        $record->lastheartbeat = $now;
        $record->lastclienttime = $clienttime;
        $record->timemodified = $now;
        $record->lastaccess = $now;
        $DB->update_record('videobook_progress', $record);
        $this->update_completion($activity, $userid);
        $record->trackingreason = $trackingreason;
        return $record;
    }

    /**
     * Stabilise duration without permanently locking an early inaccurate reading.
     *
     * Small corrections in either direction are accepted. Large decreases are
     * rejected because they could turn a short forged duration into instant
     * completion. The duration can never be below observed positions.
     *
     * @param float $stored Stored duration.
     * @param float $reported Newly reported duration.
     * @param float $floor Lowest possible duration from observed positions.
     * @return float
     */
    private function normalise_duration(float $stored, float $reported, float $floor): float {
        if ($stored <= 0) {
            return max($reported, $floor);
        }
        if ($reported <= 0) {
            return max($stored, $floor);
        }
        $tolerance = max(5.0, $stored * 0.05);
        if (abs($reported - $stored) <= $tolerance) {
            return max($reported, $floor);
        }
        return max($stored, $reported, $floor);
    }

    /**
     * Mark a non-video chapter complete.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $chapter Chapter.
     * @param int $userid User id.
     * @return stdClass
     */
    public function complete_text_chapter(stdClass $activity, stdClass $chapter, int $userid): stdClass {
        global $DB;
        $record = $this->touch((int)$activity->id, (int)$chapter->id, $userid);
        $record->percent = 100;
        $record->status = self::STATUS_COMPLETED;
        $record->timemodified = time();
        $record->lastaccess = $record->timemodified;
        $DB->update_record('videobook_progress', $record);
        $this->update_completion($activity, $userid);
        return $record;
    }

    /**
     * Calculate overall completed-chapter percentage.
     *
     * @param int $videobookid Activity id.
     * @param int $userid User id.
     * @return float
     */
    public function get_book_percent(int $videobookid, int $userid): float {
        global $DB;
        $total = $DB->count_records('videobook_chapters', ['videobookid' => $videobookid]);
        if (!$total) {
            return 0.0;
        }
        $completed = $DB->count_records('videobook_progress', [
            'videobookid' => $videobookid,
            'userid' => $userid,
            'status' => self::STATUS_COMPLETED,
        ]);
        return min(100, ($completed / $total) * 100);
    }

    /**
     * Check whether all chapters are complete.
     *
     * @param int $videobookid Activity id.
     * @param int $userid User id.
     * @return bool
     */
    public function is_book_complete(int $videobookid, int $userid): bool {
        global $DB;
        $total = $DB->count_records('videobook_chapters', ['videobookid' => $videobookid]);
        if ($total === 0) {
            return false;
        }
        $completed = $DB->count_records('videobook_progress', [
            'videobookid' => $videobookid,
            'userid' => $userid,
            'status' => self::STATUS_COMPLETED,
        ]);
        return $completed >= $total;
    }

    /**
     * Maximum watched endpoint for seek restriction.
     *
     * @param stdClass $progress Progress row.
     * @return float
     */
    public function max_watched_position(stdClass $progress): float {
        $max = 0.0;
        foreach ($this->decode_segments((string)$progress->segments) as $segment) {
            $max = max($max, (float)$segment[1]);
        }
        return $max;
    }

    /**
     * Synchronise Moodle completion from chapter states.
     *
     * @param stdClass $activity Activity.
     * @param int $userid User id.
     * @return void
     */
    public function update_completion(stdClass $activity, int $userid): void {
        if (empty($activity->completionallchapters)) {
            return;
        }
        $cm = get_coursemodule_from_instance('videobook', $activity->id, $activity->course, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $course = get_course($activity->course);
        $completion = new \completion_info($course);
        if (!$completion->is_enabled($cm)) {
            return;
        }
        $completion->update_state($cm, $this->is_book_complete((int)$activity->id, $userid)
            ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE, $userid);
    }

    /**
     * Decode stored segments.
     *
     * @param string $json JSON segments.
     * @return array
     */
    private function decode_segments(string $json): array {
        $segments = json_decode($json, true);
        if (!is_array($segments)) {
            return [];
        }
        return array_values(array_filter($segments, static function ($segment): bool {
            return is_array($segment) && count($segment) === 2 && is_numeric($segment[0]) && is_numeric($segment[1]);
        }));
    }

    /**
     * Merge overlapping or adjacent segments.
     *
     * @param array $segments Segments.
     * @param float $duration Duration.
     * @return array
     */
    private function merge_segments(array $segments, float $duration): array {
        $normalised = [];
        foreach ($segments as $segment) {
            $start = max(0, min($duration, (float)$segment[0]));
            $end = max($start, min($duration, (float)$segment[1]));
            if ($end > $start) {
                $normalised[] = [$start, $end];
            }
        }
        usort($normalised, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($normalised as $segment) {
            if (!$merged || $segment[0] > $merged[count($merged) - 1][1] + 0.75) {
                $merged[] = $segment;
                continue;
            }
            $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $segment[1]);
        }
        return $merged;
    }
}
