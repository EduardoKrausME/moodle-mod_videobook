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
 * English language strings for Video Book.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['addchapter'] = 'Add chapter';
$string['allowseek'] = 'Allow free seeking in video';
$string['allowseek_help'] = 'When disabled, students can return to previously watched portions but cannot skip forward into content they have not actually watched.';
$string['attachments'] = 'Files and documents';
$string['backtobook'] = 'Back to Video Book';
$string['bookprogress'] = 'Video Book progress';
$string['captions'] = 'Captions';
$string['captions_help'] = 'Upload WebVTT or SRT subtitle files. SRT files are converted to WebVTT when the chapter is saved.';
$string['chaptercompleted'] = 'Chapter completed.';
$string['chapterdeleted'] = 'Chapter deleted.';
$string['chapterhasnovideo'] = 'This chapter does not contain a video.';
$string['chapterhasvideo'] = 'A video chapter is completed automatically from its watched percentage.';
$string['chapterimage'] = 'Chapter image';
$string['chapterlocked'] = 'This chapter is locked until the previous chapters are completed.';
$string['chapternavigation'] = 'Chapter navigation';
$string['chapterprogress'] = 'Chapter progress';
$string['chapters'] = 'Chapters';
$string['chaptersaved'] = 'Chapter saved.';
$string['chaptertext'] = 'Explanatory text';
$string['chaptertitle'] = 'Chapter title';
$string['chapterxofy'] = 'Chapter {$a->current} of {$a->total}';
$string['complementarylinks'] = 'Complementary links';
$string['complementarylinks_help'] = 'Enter one link per line. Use URL only, or Label|URL. Example: Moodle documentation|https://moodle.org/';
$string['completionallchapters'] = 'Require all chapters to be completed';
$string['completiondetail:allchapters'] = 'Complete all Video Book chapters';
$string['completionrules'] = '';
$string['delete'] = 'Delete';
$string['deletechapter'] = 'Delete chapter';
$string['deletechapterconfirm'] = 'Delete the chapter "{$a}" and all student progress stored for it?';
$string['edit'] = 'Edit';
$string['editchapter'] = 'Edit chapter';
$string['errormaxfiles'] = 'Only one file can be uploaded.';
$string['invalidchapter'] = 'Invalid Video Book chapter.';
$string['invaliddirection'] = 'Invalid chapter movement direction.';
$string['invalidpercentage'] = 'Enter a percentage from 1 to 100.';
$string['invalidvideosource'] = 'Invalid video source.';
$string['invalidvimeourl'] = 'The Vimeo URL is invalid.';
$string['invalidyoutubeurl'] = 'The YouTube URL or video ID is invalid.';
$string['lastaccess'] = 'Last access';
$string['managechapters'] = 'Manage chapters';
$string['markchaptercomplete'] = 'Mark chapter as completed';
$string['minimumpercent'] = 'Minimum video percentage';
$string['minimumpercent_help'] = 'Percentage of unique video content that the student must actually watch for this chapter to be considered completed.';
$string['modulename'] = 'Video Book';
$string['modulename_help'] = 'Builds a multimedia book organised into chapters, primarily based on videos, with real viewing progress, resume, resources and chapter-by-chapter reporting.';
$string['modulenameplural'] = 'Video Books';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['navigationfree'] = 'Free navigation';
$string['navigationheader'] = 'Navigation and playback';
$string['navigationmode'] = 'Chapter navigation';
$string['navigationsequential'] = 'Sequential navigation';
$string['never'] = 'Never';
$string['next'] = 'Next';
$string['nextlocked'] = 'Next chapter locked';
$string['noactivities'] = 'There are no Video Book activities in this course.';
$string['nochaptersstudent'] = 'This Video Book does not have any chapters available yet.';
$string['nochaptersteacher'] = 'No chapters have been created yet. Add the first chapter to start the Video Book.';
$string['nostudents'] = 'No students were found for this activity.';
$string['overall'] = 'Overall progress';
$string['pluginadministration'] = 'Video Book administration';
$string['pluginname'] = 'Video Book';
$string['previous'] = 'Previous';
$string['privacy:metadata:progress'] = 'Stores each user\'s progress in each Video Book chapter.';
$string['privacy:metadata:progress:duration'] = 'The known video duration.';
$string['privacy:metadata:progress:lastaccess'] = 'The last time the chapter was accessed.';
$string['privacy:metadata:progress:lastposition'] = 'The last saved playback position.';
$string['privacy:metadata:progress:percent'] = 'The watched percentage calculated by the server.';
$string['privacy:metadata:progress:segments'] = 'The video intervals that were actually watched.';
$string['privacy:metadata:progress:status'] = 'The chapter status: not started, in progress or completed.';
$string['privacy:metadata:progress:uniquewatched'] = 'The amount of unique video content watched.';
$string['privacy:metadata:progress:userid'] = 'The user identifier.';
$string['privacy:progresspath'] = 'Chapter progress';
$string['progressreset'] = 'Progress reset.';
$string['reporttitle'] = 'Video Book progress report';
$string['resetprogress'] = 'Reset progress';
$string['resetprogressconfirm'] = 'Reset all Video Book progress for {$a}?';
$string['resourcesheader'] = 'Chapter resources';
$string['resumeask'] = 'Ask the student';
$string['resumeautomatic'] = 'Resume automatically';
$string['resumefromstart'] = 'Always start from the beginning';
$string['resumeno'] = 'Start from the beginning';
$string['resumeplayback'] = 'Resume playback';
$string['resumequestion'] = 'You stopped at {$a}. Do you want to continue from there?';
$string['resumeyes'] = 'Continue';
$string['savechapter'] = 'Save chapter';
$string['seekblocked'] = 'You cannot skip forward into a section you have not watched yet.';
$string['sourcenone'] = 'No video';
$string['sourceupload'] = 'Video uploaded to Moodle';
$string['sourceurl'] = 'Direct video or HLS URL';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['status0'] = 'Not started';
$string['status1'] = 'In progress';
$string['status2'] = 'Completed';
$string['student'] = 'Student';
$string['trackingerror'] = 'The video progress could not be synchronised.';
$string['videobook:addinstance'] = 'Add a new Video Book';
$string['videobook:managechapters'] = 'Manage Video Book chapters';
$string['videobook:resetprogress'] = 'Reset Video Book progress';
$string['videobook:view'] = 'View Video Book';
$string['videobook:viewreport'] = 'View Video Book reports';
$string['videobookname'] = 'Video Book name';
$string['videofile'] = 'Video file';
$string['videofilemissing'] = 'The uploaded video file could not be found.';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Video URL';
$string['viewreport'] = 'View report';
$string['ws:accepted'] = 'Whether the progress update was accepted.';
$string['ws:bookpercent'] = 'Overall Video Book completion percentage.';
$string['ws:chapterid'] = 'Chapter identifier.';
$string['ws:cmid'] = 'Course module identifier.';
$string['ws:completed'] = 'Whether the chapter is completed.';
$string['ws:correctposition'] = 'Playback position approved by the server.';
$string['ws:currentposition'] = 'Current player position.';
$string['ws:duration'] = 'Video duration reported by the player.';
$string['ws:lastposition'] = 'Last stored playback position.';
$string['ws:percent'] = 'Server-calculated watched percentage.';
$string['ws:playbackrate'] = 'Current playback rate.';
$string['ws:playerstate'] = 'Current player state.';
$string['ws:reason'] = 'Result of the server validation.';
$string['ws:segmentend'] = 'End of the continuously watched interval.';
$string['ws:segments'] = 'Consolidated watched intervals encoded as JSON.';
$string['ws:segmentstart'] = 'Start of the continuously watched interval.';
$string['ws:status'] = 'Chapter progress status.';
