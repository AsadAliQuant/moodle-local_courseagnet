// This file is part of Course Agent - AI Course Creator Plugin for Moodle

/**
 * Progressive-enhancement custom select (polished listbox).
 *
 * Turns a native `<select>` into a modern, accessible dropdown while keeping the
 * native element in the DOM as the hidden source of truth. Selecting an option sets
 * the native value and dispatches a `change` event, so existing listeners (e.g. the
 * provider -> model cascade in coursecreator) keep working untouched.
 *
 * @module local_courseagent/customselect
 * @copyright 2026 Course Agent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    'use strict';

    var OPEN_CLASS = 'is-open';
    var instances = [];
    var globalBound = false;
    var uid = 0;

    /**
     * Escape a string for safe insertion into HTML / attribute context.
     * @param {*} str Raw value.
     * @return {string} Escaped string.
     */
    var esc = function(str) {
        return String(str === null || str === undefined ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    /**
     * Build the `<li>` option markup for a widget from its native `<select>`.
     * @param {HTMLSelectElement} select Native select.
     * @param {string} panelId Id of the owning panel (for option ids).
     * @return {string} HTML string.
     */
    var buildOptions = function(select, panelId) {
        var html = '';
        for (var i = 0; i < select.options.length; i++) {
            var opt = select.options[i];
            var selected = opt.selected;
            html += '<li class="ca-select__option' + (selected ? ' is-selected' : '') + '"'
                + ' id="' + panelId + '-opt-' + i + '"'
                + ' role="option"'
                + ' data-index="' + i + '"'
                + ' data-value="' + esc(opt.value) + '"'
                + ' aria-selected="' + (selected ? 'true' : 'false') + '">'
                + '<i class="fa fa-check ca-select__check" aria-hidden="true"></i>'
                + '<span class="ca-select__option-label">' + esc(opt.text) + '</span>'
                + '</li>';
        }
        return html;
    };

    /**
     * Update the trigger label to reflect the currently selected option.
     * @param {Object} state Widget state.
     */
    var updateTriggerLabel = function(state) {
        var sel = state.$select[0];
        var text = '';
        if (sel.selectedIndex >= 0) {
            text = sel.options[sel.selectedIndex].text;
        } else if (sel.options.length) {
            text = sel.options[0].text;
        }
        state.$trigger.find('.ca-select__label').text(text);
    };

    /**
     * Rebuild the panel options from the native select and refresh the label.
     * @param {Object} state Widget state.
     */
    var renderOptions = function(state) {
        state.$panel.html(buildOptions(state.$select[0], state.panelId));
        updateTriggerLabel(state);
    };

    /**
     * Highlight the option at the given index (keyboard / hover active row).
     * @param {Object} state Widget state.
     * @param {number} index Option index.
     */
    var setActive = function(state, index) {
        var $opts = state.$panel.find('.ca-select__option');
        if (index < 0 || index >= $opts.length) {
            return;
        }
        state.activeIndex = index;
        $opts.removeClass('is-active');
        var $active = $opts.eq(index).addClass('is-active');
        state.$trigger.attr('aria-activedescendant', $active.attr('id'));
    };

    /**
     * Scroll the active option into view inside the panel.
     * @param {Object} state Widget state.
     */
    var scrollActiveIntoView = function(state) {
        var $active = state.$panel.find('.ca-select__option.is-active');
        if (!$active.length) {
            return;
        }
        var panel = state.$panel[0];
        var opt = $active[0];
        var top = opt.offsetTop;
        var bottom = top + opt.offsetHeight;
        if (top < panel.scrollTop) {
            panel.scrollTop = top;
        } else if (bottom > panel.scrollTop + panel.clientHeight) {
            panel.scrollTop = bottom - panel.clientHeight;
        }
    };

    /**
     * Close a single widget.
     * @param {Object} state Widget state.
     */
    var close = function(state) {
        if (!state.$wrap.hasClass(OPEN_CLASS)) {
            return;
        }
        state.$wrap.removeClass(OPEN_CLASS);
        state.$trigger.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
        state.activeIndex = -1;
    };

    /**
     * Close every open widget on the page.
     */
    var closeAll = function() {
        for (var i = 0; i < instances.length; i++) {
            close(instances[i]);
        }
    };

    /**
     * Open a widget (closing any others first).
     * @param {Object} state Widget state.
     */
    var open = function(state) {
        if (state.$wrap.hasClass(OPEN_CLASS)) {
            return;
        }
        closeAll();
        state.$wrap.addClass(OPEN_CLASS);
        state.$trigger.attr('aria-expanded', 'true');
        var idx = state.$select[0].selectedIndex;
        setActive(state, idx >= 0 ? idx : 0);
        scrollActiveIntoView(state);
    };

    /**
     * Toggle a widget open/closed.
     * @param {Object} state Widget state.
     */
    var toggle = function(state) {
        if (state.$wrap.hasClass(OPEN_CLASS)) {
            close(state);
        } else {
            open(state);
        }
    };

    /**
     * Commit a selection: update the native select, fire `change`, sync the UI.
     * @param {Object} state Widget state.
     * @param {number} index Option index to select.
     */
    var commit = function(state, index) {
        var sel = state.$select[0];
        if (index < 0 || index >= sel.options.length) {
            return;
        }
        if (sel.selectedIndex !== index) {
            sel.selectedIndex = index;
            // Fire the native change event so existing listeners (provider -> model) run.
            state.$select.trigger('change');
        }
        var $opts = state.$panel.find('.ca-select__option');
        $opts.removeClass('is-selected').attr('aria-selected', 'false');
        $opts.eq(index).addClass('is-selected').attr('aria-selected', 'true');
        updateTriggerLabel(state);
    };

    /**
     * Type-ahead: jump to the first option whose text starts with the typed buffer.
     * @param {Object} state Widget state.
     * @param {string} ch Single printable character.
     */
    var typeahead = function(state, ch) {
        if (state.typeaheadTimer) {
            clearTimeout(state.typeaheadTimer);
        }
        state.typeahead += ch.toLowerCase();
        var buf = state.typeahead;
        var sel = state.$select[0];
        var found = -1;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].text.toLowerCase().indexOf(buf) === 0) {
                found = i;
                break;
            }
        }
        if (found >= 0) {
            if (state.$wrap.hasClass(OPEN_CLASS)) {
                setActive(state, found);
                scrollActiveIntoView(state);
            } else {
                commit(state, found);
            }
        }
        state.typeaheadTimer = setTimeout(function() {
            state.typeahead = '';
        }, 600);
    };

    /**
     * Keyboard handling for the trigger / open panel.
     * @param {Object} state Widget state.
     * @param {Event} e Keydown event.
     */
    var handleKeydown = function(state, e) {
        var key = e.key;
        var isOpen = state.$wrap.hasClass(OPEN_CLASS);
        var last = state.$select[0].options.length - 1;

        switch (key) {
            case 'ArrowDown':
            case 'Down':
                e.preventDefault();
                if (!isOpen) {
                    open(state);
                } else {
                    setActive(state, Math.min(state.activeIndex + 1, last));
                    scrollActiveIntoView(state);
                }
                break;
            case 'ArrowUp':
            case 'Up':
                e.preventDefault();
                if (!isOpen) {
                    open(state);
                } else {
                    setActive(state, Math.max(state.activeIndex - 1, 0));
                    scrollActiveIntoView(state);
                }
                break;
            case 'Home':
                if (isOpen) {
                    e.preventDefault();
                    setActive(state, 0);
                    scrollActiveIntoView(state);
                }
                break;
            case 'End':
                if (isOpen) {
                    e.preventDefault();
                    setActive(state, last);
                    scrollActiveIntoView(state);
                }
                break;
            case 'Enter':
            case ' ':
            case 'Spacebar':
                e.preventDefault();
                if (isOpen) {
                    commit(state, state.activeIndex);
                    close(state);
                } else {
                    open(state);
                }
                break;
            case 'Escape':
            case 'Esc':
                if (isOpen) {
                    e.preventDefault();
                    close(state);
                }
                break;
            case 'Tab':
                if (isOpen) {
                    close(state);
                }
                break;
            default:
                if (key && key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    typeahead(state, key);
                }
                break;
        }
    };

    /**
     * Bind interaction handlers for a single widget.
     * @param {Object} state Widget state.
     */
    var bindEvents = function(state) {
        state.$trigger.on('click', function(e) {
            e.preventDefault();
            toggle(state);
        });
        state.$trigger.on('keydown', function(e) {
            handleKeydown(state, e);
        });
        // Delegated handlers survive panel re-render (refresh).
        state.$panel.on('click', '.ca-select__option', function(e) {
            e.preventDefault();
            commit(state, parseInt($(this).attr('data-index'), 10));
            close(state);
            state.$trigger.focus();
        });
        state.$panel.on('mousemove', '.ca-select__option', function() {
            setActive(state, parseInt($(this).attr('data-index'), 10));
        });
    };

    /**
     * Bind the single document-level outside-click handler.
     */
    var bindGlobal = function() {
        if (globalBound) {
            return;
        }
        globalBound = true;
        $(document).on('click.ca-select', function(e) {
            if (!$(e.target).closest('.ca-select').length) {
                closeAll();
            }
        });
    };

    /**
     * Enhance a single native `<select>` into a custom listbox.
     * @param {HTMLSelectElement} select Native select element.
     */
    var enhanceOne = function(select) {
        var $select = $(select);
        if ($select.data('caSelect')) {
            return;
        }

        uid += 1;
        var baseId = 'ca-select-' + uid;
        var panelId = baseId + '-panel';
        var triggerId = baseId + '-trigger';

        $select.addClass('ca-select__native').attr({'aria-hidden': 'true', 'tabindex': '-1'});

        var $wrap = $('<div class="ca-select"></div>');
        $select.before($wrap);
        $wrap.append($select);

        var $trigger = $('<button type="button" class="ca-select__trigger" id="' + triggerId + '"'
            + ' role="combobox" aria-haspopup="listbox" aria-expanded="false"'
            + ' aria-controls="' + panelId + '">'
            + '<span class="ca-select__label"></span>'
            + '<i class="fa fa-chevron-down ca-select__chevron" aria-hidden="true"></i>'
            + '</button>');

        var $panel = $('<ul class="ca-select__panel" id="' + panelId + '" role="listbox" tabindex="-1"></ul>');

        var $label = $('label[for="' + $select.attr('id') + '"]');
        if ($label.length) {
            $trigger.attr('aria-label', $label.text().trim());
        }

        $wrap.append($trigger).append($panel);

        var state = {
            $select: $select,
            $wrap: $wrap,
            $trigger: $trigger,
            $panel: $panel,
            panelId: panelId,
            activeIndex: -1,
            typeahead: '',
            typeaheadTimer: null
        };
        $select.data('caSelect', state);
        instances.push(state);

        renderOptions(state);
        bindEvents(state);
    };

    return {
        /**
         * Enhance all native selects matching the selector.
         * @param {string} selector jQuery selector.
         */
        enhance: function(selector) {
            bindGlobal();
            $(selector).each(function() {
                enhanceOne(this);
            });
        },

        /**
         * Rebuild the listbox(es) after their native `<option>`s changed.
         * @param {string} selector jQuery selector.
         */
        refresh: function(selector) {
            $(selector).each(function() {
                var state = $(this).data('caSelect');
                if (!state) {
                    return;
                }
                close(state);
                renderOptions(state);
            });
        }
    };
});
