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
$string['addresource'] = 'Add material';
$string['allowseek'] = 'Allow free seeking in video';
$string['allowseek_help'] = 'When disabled, students can return to previously watched portions but cannot skip forward into content they have not actually watched.';
$string['attachments'] = 'Files and documents';
$string['available'] = 'Available';
$string['backtobook'] = 'Back to Video Book';
$string['bookprogress'] = 'Video Book progress';
$string['captions'] = 'Captions';
$string['captions_help'] = 'Upload WebVTT or SRT subtitle files. SRT files are converted to WebVTT when the chapter is saved.';
$string['chapteranalytics'] = 'Progress by chapter';
$string['chaptercompleted'] = 'Chapter completed.';
$string['chapterdeleted'] = 'Chapter deleted.';
$string['chapterduplicated'] = 'Chapter duplicated.';
$string['chapterhasnovideo'] = 'This chapter does not contain a video.';
$string['chapterhasvideo'] = 'A video chapter is completed automatically from its watched percentage.';
$string['chapterimage'] = 'Chapter image';
$string['chapterlocked'] = 'This chapter is locked until the previous chapters are completed.';
$string['chapternavigation'] = 'Chapter navigation';
$string['chapterprogress'] = 'Chapter progress';
$string['chapters'] = 'Chapters';
$string['chaptersaved'] = 'Chapter saved.';
$string['chaptersimported'] = '{$a} chapters imported.';
$string['chaptertext'] = 'Explanatory text';
$string['chaptertitle'] = 'Chapter title';
$string['chaptervisible'] = 'Visible to students';
$string['chapterxofy'] = 'Chapter {$a->current} of {$a->total}';
$string['complementarylinks'] = 'Complementary links';
$string['complementarylinks_help'] = 'Enter one link per line. Use URL only, or Label|URL. Example: Moodle documentation|https://moodle.org/';
$string['completedcount'] = 'Completed';
$string['completionallchapters'] = 'Require all chapters to be completed';
$string['completiondetail:allchapters'] = 'Complete all Video Book chapters';
$string['completionended'] = 'Watch the entire video';
$string['completionmanual'] = 'Mark manually as completed';
$string['completionpercent'] = 'Watch a percentage of the video';
$string['completionpercentormanual'] = 'Watch the percentage or mark manually';
$string['completionrequiresvideo'] = 'This completion rule requires a video.';
$string['completionrules'] = '';
$string['completiontype'] = 'Chapter completion';
$string['completiontype_help'] = 'Defines when this chapter is considered completed. The rule also controls sequential unlocking when sequential navigation is enabled.';
$string['completionview'] = 'Open the chapter';
$string['contentorder'] = 'Content order';
$string['contentorder_help'] = 'Choose the simple order used to present the video, lesson text, transcript and materials without turning the chapter into a free-form page builder.';
$string['continuestudying'] = 'Continue studying';
$string['copytitle'] = '{$a} (copy)';
$string['csvfile'] = 'CSV file';
$string['csvformathelp'] = 'Use the columns title, video_url, content, transcript, section, order, visible, completion, minimum_percent and links. Comma and semicolon separators are supported.';
$string['csvmissing'] = 'The uploaded CSV file could not be found.';
$string['csvtitlemissing'] = 'The CSV file must contain a title column.';
$string['delete'] = 'Delete';
$string['deletechapter'] = 'Delete chapter';
$string['deletechapterconfirm'] = 'Delete the chapter "{$a}" and all student progress stored for it?';
$string['dragtoorder'] = 'Drag to reorder';
$string['duplicate'] = 'Duplicate';
$string['edit'] = 'Edit';
$string['editchapter'] = 'Edit chapter';
$string['editresource'] = 'Edit material';
$string['errormaxfiles'] = 'Only one file can be uploaded.';
$string['hide'] = 'Hide';
$string['importchapters'] = 'Import chapters from CSV';
$string['inprogresscount'] = 'In progress';
$string['invalidchapter'] = 'Invalid Video Book chapter.';
$string['invalidchapterorder'] = 'Invalid chapter order.';
$string['invaliddirection'] = 'Invalid chapter movement direction.';
$string['invalidpercentage'] = 'Enter a percentage from 1 to 100.';
$string['invalidvideosource'] = 'Invalid video source.';
$string['invalidvimeourl'] = 'The Vimeo URL is invalid.';
$string['invalidyoutubeurl'] = 'The YouTube URL or video ID is invalid.';
$string['lastaccess'] = 'Last access';
$string['layoutcontinuous'] = 'Continuous content';
$string['layoutmode'] = 'Lesson layout';
$string['layoutmode_help'] = 'Tabs keep content, transcript and materials on the same video page in separate panels. Continuous content displays the configured blocks one after another.';
$string['layouttabs'] = 'Tabs below the video';
$string['legacyattachment'] = 'Legacy file';
$string['legacylink'] = 'Legacy link';
$string['locked'] = 'Locked';
$string['managechapters'] = 'Manage chapters';
$string['manageresources'] = 'Manage materials';
$string['manualcompletiondisabled'] = 'Manual completion is not enabled for this chapter.';
$string['markchaptercomplete'] = 'Mark chapter as completed';
$string['materials'] = 'Materials';
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
$string['noresults'] = 'No chapters matched your search.';
$string['nostudents'] = 'No students were found for this activity.';
$string['notstartedcount'] = 'Not started';
$string['ordercontentvideotranscriptresources'] = 'Content → Video → Transcript → Materials';
$string['ordervideocontenttranscriptresources'] = 'Video → Content → Transcript → Materials';
$string['ordervideoresourcescontenttranscript'] = 'Video → Materials → Content → Transcript';
$string['ordervideotranscriptcontentresources'] = 'Video → Transcript → Content → Materials';
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
$string['resourcedeleted'] = 'Material deleted.';
$string['resourcedescription'] = 'Short description';
$string['resourcefile'] = 'Material file';
$string['resourcesaved'] = 'Material saved.';
$string['resourcesheader'] = 'Chapter resources';
$string['resourcesource'] = 'Material source';
$string['resourcesourcefile'] = 'Uploaded file';
$string['resourcesourceurl'] = 'External URL';
$string['resourcetitle'] = 'Material title';
$string['resourcetype'] = 'Material type';
$string['resourcetype:audio'] = 'Audio / podcast';
$string['resourcetype:complementary'] = 'Complementary file';
$string['resourcetype:link'] = 'External link';
$string['resourcetype:other'] = 'Other';
$string['resourcetype:pdf'] = 'PDF / lesson material';
$string['resourcetype:slides'] = 'Slides';
$string['resourceurl'] = 'Material URL';
$string['resourcevisible'] = 'Visible to students';
$string['resumeask'] = 'Ask the student';
$string['resumeautomatic'] = 'Resume automatically';
$string['resumefromstart'] = 'Always start from the beginning';
$string['resumeno'] = 'Start from the beginning';
$string['resumeplayback'] = 'Resume playback';
$string['resumequestion'] = 'You stopped at {$a}. Do you want to continue from there?';
$string['resumeyes'] = 'Continue';
$string['savechapter'] = 'Save chapter';
$string['saveorder'] = 'Save order';
$string['saveresource'] = 'Save material';
$string['searchchapters'] = 'Search chapters';
$string['searchresults'] = 'Search results';
$string['sectiontitle'] = 'Section / module';
$string['sectiontitle_help'] = 'Optional grouping label shown in the chapter menu, for example “Module 1 — Introduction”. Chapters remain a simple ordered list.';
$string['seekblocked'] = 'You cannot skip forward into a section you have not watched yet.';
$string['show'] = 'Show';
$string['sourcenone'] = 'No video';
$string['sourceupload'] = 'Video uploaded to Moodle';
$string['sourceurl'] = 'Direct video or HLS URL';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['status0'] = 'Not started';
$string['status1'] = 'In progress';
$string['status2'] = 'Completed';
$string['stoppedat'] = 'Stopped at {$a}';
$string['structuredresources'] = 'Structured chapter materials';
$string['student'] = 'Student';
$string['trackingerror'] = 'The video progress could not be synchronised.';
$string['transcript'] = 'Transcript';
$string['transcript_help'] = 'Native lesson transcript. It can contain formatted text, links and embedded files and is searchable inside the Video Book.';
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
