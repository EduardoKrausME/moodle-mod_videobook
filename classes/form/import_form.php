<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Bulk chapter import form.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videobook\form;

use moodleform;

/**
 * CSV import form.
 */
class import_form extends moodleform {
    /**
     * Define form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'videobook'), null, [
            'accepted_types' => ['.csv'],
        ]);
        $mform->addRule('csvfile', null, 'required', null, 'client');
        $mform->addElement('static', 'csvhelp', '', get_string('csvformathelp', 'videobook'));
        $this->add_action_buttons(true, get_string('importchapters', 'videobook'));
    }
}
