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
 * Database upgrade steps.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Executes module database upgrades.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_videobook_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026092601) {
        $table = new xmldb_table('videobook_progress');

        $field = new xmldb_field('lastheartbeat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'lastaccess');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('lastclienttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'lastheartbeat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026092601, 'videobook');
    }

    if ($oldversion < 2026092602) {
        $table = new xmldb_table('videobook');
        $field = new xmldb_field('layoutmode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'tabs', 'allowseek');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('videobook_chapters');
        $fields = [
            new xmldb_field('transcript', XMLDB_TYPE_TEXT, null, null, null, null, null, 'contentformat'),
            new xmldb_field('transcriptformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'transcript'),
            new xmldb_field('sectiontitle', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'transcriptformat'),
            new xmldb_field('completiontype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'percent', 'linksjson'),
            new xmldb_field('contentorder', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null,
                'video_content_transcript_resources', 'completiontype'),
            new xmldb_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'contentorder'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // A temporary empty-string default is required while adding a NOT NULL field to populated tables.
        // Remove it afterwards so upgraded installations match install.xml.
        $sectiontitlefield = new xmldb_field(
            'sectiontitle',
            XMLDB_TYPE_CHAR,
            '255',
            null,
            XMLDB_NOTNULL,
            null,
            null,
            'transcriptformat'
        );
        $dbman->change_field_default($table, $sectiontitlefield);

        $DB->execute("UPDATE {videobook_chapters} SET completiontype = 'manual' WHERE videosource = 'none'");

        $table = new xmldb_table('videobook_resources');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('chapterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'complementary');
            $table->add_field('source', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'file');
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('url', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('chapterid', XMLDB_KEY_FOREIGN, ['chapterid'], 'videobook_chapters', ['id']);
            $table->add_index('chapter-sort', XMLDB_INDEX_NOTUNIQUE, ['chapterid', 'sortorder']);
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026092602, 'videobook');
    }

    if ($oldversion < 2026092603) {
        upgrade_mod_savepoint(true, 2026092603, 'videobook');
    }

    return true;
}
