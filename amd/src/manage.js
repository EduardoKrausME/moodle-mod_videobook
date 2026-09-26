// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Drag-and-drop ordering for the Video Book chapter management screen.
 *
 * @module     mod_videobook/manage
 * @package    mod_videobook
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    const SELECTOR = '[data-region="chapter-sortable"]';
    let dragged = null;

    const findAfter = (container, y) => {
        const items = [...container.querySelectorAll('[data-chapter-id]:not(.is-dragging)')];
        return items.reduce((closest, item) => {
            const box = item.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return {offset: offset, element: item};
            }
            return closest;
        }, {offset: Number.NEGATIVE_INFINITY, element: null}).element;
    };

    const init = () => {
        $(SELECTOR).each(function() {
            const container = this;

            container.addEventListener('dragstart', (event) => {
                const item = event.target.closest('[data-chapter-id]');
                if (!item) {
                    return;
                }
                dragged = item;
                item.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', item.dataset.chapterId || '');
            });

            container.addEventListener('dragend', () => {
                if (dragged) {
                    dragged.classList.remove('is-dragging');
                }
                dragged = null;
            });

            container.addEventListener('dragover', (event) => {
                if (!dragged) {
                    return;
                }
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                const after = findAfter(container, event.clientY);
                if (after) {
                    container.insertBefore(dragged, after);
                } else {
                    container.appendChild(dragged);
                }
            });
        });
    };

    return {init: init};
});
