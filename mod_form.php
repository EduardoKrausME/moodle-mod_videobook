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
 * Activity configuration form.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Main Video Book activity form.
 */
class mod_videobook_mod_form extends moodleform_mod {
    /**
     * Define activity fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videobookname', 'videobook'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'navigationheader', get_string('navigationheader', 'videobook'));
        $mform->addElement('select', 'navigationmode', get_string('navigationmode', 'videobook'), [
            0 => get_string('navigationfree', 'videobook'),
            1 => get_string('navigationsequential', 'videobook'),
        ]);
        $mform->setDefault('navigationmode', 0);

        $mform->addElement('select', 'resumeplayback', get_string('resumeplayback', 'videobook'), [
            1 => get_string('resumeautomatic', 'videobook'),
            2 => get_string('resumeask', 'videobook'),
            0 => get_string('resumefromstart', 'videobook'),
        ]);
        $mform->setDefault('resumeplayback', 1);

        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'videobook'));
        $mform->setDefault('allowseek', 1);
        $mform->addHelpButton('allowseek', 'allowseek', 'videobook');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Add custom completion rules.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $mform->addElement('checkbox', 'completionallchapters', '', get_string('completionallchapters', 'videobook'));
        return ['completionallchapters'];
    }

    /**
     * Completion rule validation.
     *
     * @param array $data Submitted data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionallchapters']);
    }
}
