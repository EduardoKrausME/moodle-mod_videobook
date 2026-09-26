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
 * Tests for Video Book progress tracking.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook;

/**
 * Progress manager tests.
 */
final class progress_manager_test extends \advanced_testcase {
    /**
     * Create the minimal records needed by progress_manager.
     *
     * @return array
     */
    private function create_records(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $bookid = $DB->insert_record('videobook', (object)[
            'course' => $course->id,
            'name' => 'Tracking test',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'navigationmode' => 0,
            'resumeplayback' => 1,
            'allowseek' => 1,
            'completionallchapters' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $chapterid = $DB->insert_record('videobook_chapters', (object)[
            'videobookid' => $bookid,
            'title' => 'Chapter',
            'content' => '',
            'contentformat' => FORMAT_HTML,
            'videosource' => 'url',
            'videourl' => 'https://example.test/video.mp4',
            'linksjson' => '[]',
            'minimumpercent' => 80,
            'sortorder' => 10,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return [
            $DB->get_record('videobook', ['id' => $bookid], '*', MUST_EXIST),
            $DB->get_record('videobook_chapters', ['id' => $chapterid], '*', MUST_EXIST),
            $user,
        ];
    }

    /**
     * A client cannot manufacture one minute of progress in a few seconds.
     */
    public function test_progress_is_limited_by_server_elapsed_time(): void {
        global $DB;

        $this->resetAfterTest();
        [$activity, $chapter, $user] = $this->create_records();

        $manager = new progress_manager();
        $record = $manager->touch($activity->id, $chapter->id, $user->id);
        $record->lastheartbeat = time() - 5;
        $record->lastclienttime = 100;
        $DB->update_record('videobook_progress', $record);

        $progress = $manager->update_video_progress(
            $activity,
            $chapter,
            $user->id,
            100.0,
            60.0,
            0.0,
            60.0,
            1.0,
            101
        );

        $this->assertLessThanOrEqual(8.5, (float)$progress->uniquewatched);
        $this->assertSame('ratecapped', $progress->trackingreason);
        $this->assertNotEquals(progress_manager::STATUS_COMPLETED, (int)$progress->status);
    }

    /**
     * Replayed or out-of-order payloads must not regress resume position.
     */
    public function test_stale_update_does_not_regress_position(): void {
        global $DB;

        $this->resetAfterTest();
        [$activity, $chapter, $user] = $this->create_records();

        $manager = new progress_manager();
        $record = $manager->touch($activity->id, $chapter->id, $user->id);
        $record->duration = 100;
        $record->lastposition = 40;
        $record->lastclienttime = 200;
        $record->lastheartbeat = time() - 5;
        $DB->update_record('videobook_progress', $record);

        $progress = $manager->update_video_progress(
            $activity,
            $chapter,
            $user->id,
            100.0,
            10.0,
            0.0,
            10.0,
            1.0,
            199
        );

        $this->assertSame('stale', $progress->trackingreason);
        $this->assertEqualsWithDelta(40.0, (float)$progress->lastposition, 0.001);
    }

    /**
     * Small media-duration corrections may move in either direction.
     */
    public function test_small_duration_correction_can_decrease(): void {
        global $DB;

        $this->resetAfterTest();
        [$activity, $chapter, $user] = $this->create_records();

        $manager = new progress_manager();
        $record = $manager->touch($activity->id, $chapter->id, $user->id);
        $record->duration = 100;
        $record->lastheartbeat = time() - 5;
        $record->lastclienttime = 300;
        $DB->update_record('videobook_progress', $record);

        $progress = $manager->update_video_progress(
            $activity,
            $chapter,
            $user->id,
            98.0,
            4.0,
            0.0,
            4.0,
            1.0,
            301
        );

        $this->assertEqualsWithDelta(98.0, (float)$progress->duration, 0.001);
    }
}
