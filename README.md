# Video Book (`mod_videobook`)

Video Book is a Moodle activity module for building a multimedia book organised into chapters, with video as the primary
learning medium.

Teachers create ordered chapters containing a title, video, explanatory text, image, files/documents, captions and
complementary links. Students navigate with a chapter sidebar plus Previous/Next controls. Navigation can be free or
sequential.

## Main features

- Chapter-based multimedia activity.
- Video sources: Moodle upload, direct video/HLS URL, YouTube and Vimeo.
- Real watched-segment tracking instead of treating the furthest playback position as watched content.
- Per-chapter minimum watched percentage.
- Per-chapter states: Not started, In progress and Completed.
- Overall Video Book progress based on completed chapters.
- Resume from the last playback position, automatically or after asking the learner.
- Optional restriction preventing students from seeking into video sections not yet watched.
- WebVTT captions, with automatic SRT-to-WebVTT conversion on save.
- Chapter images, rich text, PDFs/files and complementary links.
- Student-by-chapter progress report.
- Moodle activity completion rule requiring all chapters.
- Privacy API support.
- Moodle backup and restore support, including user progress when user data is included.

## Requirements

- Moodle 4.5 or newer.
- PHP version supported by the target Moodle release.

## Installation

Copy the plugin to:

`mod/videobook`

Then complete the Moodle upgrade from Site administration.

## Video tracking

The browser sends short contiguous playback intervals to Moodle. The server merges these intervals, calculates the
amount of unique content watched and derives the chapter percentage from that value. Seeking alone therefore does not
increase progress.

When free seeking is disabled, previously watched portions remain available, but the player is corrected if a learner
attempts to jump beyond server-confirmed watched content.

## Credits

Architecture and video tracking concepts were adapted from `mod_videoprogress`:

https://github.com/EduardoKrausME/moodle-mod_videoprogress

Copyright 2026 Eduardo Kraus.

Licensed under GNU GPL v3 or later.
