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
 * Accessible lightweight lesson tabs.
 *
 * @module     mod_videobook/tabs
 * @package    mod_videobook
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    const activate = (root, id) => {
        root.querySelectorAll('[data-tab-target]').forEach((button) => {
            const active = button.dataset.tabTarget === id;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.tabIndex = active ? 0 : -1;
        });
        root.querySelectorAll('[data-tab-panel]').forEach((panel) => {
            panel.classList.toggle('d-none', panel.dataset.tabPanel !== id);
        });
    };

    const init = () => {
        $('[data-region="lesson-tabs"]').each(function() {
            const root = this;
            const buttons = [...root.querySelectorAll('[data-tab-target]')];

            buttons.forEach((button, index) => {
                button.addEventListener('click', () => activate(root, button.dataset.tabTarget));
                button.addEventListener('keydown', (event) => {
                    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                        return;
                    }
                    event.preventDefault();
                    let target = index;
                    if (event.key === 'ArrowLeft') {
                        target = (index - 1 + buttons.length) % buttons.length;
                    } else if (event.key === 'ArrowRight') {
                        target = (index + 1) % buttons.length;
                    } else if (event.key === 'Home') {
                        target = 0;
                    } else if (event.key === 'End') {
                        target = buttons.length - 1;
                    }
                    activate(root, buttons[target].dataset.tabTarget);
                    buttons[target].focus();
                });
            });
        });
    };

    return {init: init};
});
