// This file is part of Course Agent - AI Course Creator Plugin for Moodle

/**
 * @module local_courseagent/coursecreator
 * @copyright 2026 Course Agent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/config', 'local_courseagent/customselect'],
    function($, Ajax, Notification, Str, CoreConfig, CustomSelect) {

    'use strict';

    let config = {};
    let strings = {};
    let progressTimer = null;
    let generateXhr = null;

    // -------------------------------------------------------------------------
    // Dual-range sliders + activity sub-option toggles
    // -------------------------------------------------------------------------

    /**
     * Build the HTML for one sub-option block (a labelled toggle + dual-range slider).
     * Used for the JS-injected H5P rule so it matches the static quiz/assignment markup.
     * @param {Object} o Block options
     * @return {string} HTML
     */
    const buildSubopt = function(o) {
        const offClass = o.checked ? '' : ' ca-subopt--off';
        return '<div class="ca-subopt' + offClass + '" id="' + o.id + '-subopt">'
            + '<div class="ca-subopt-head">'
            + '<div class="ca-subopt-info">'
            + '<div class="ca-subopt-title">' + o.title + '</div>'
            + '<div class="ca-subopt-desc">' + o.desc + '</div>'
            + '</div>'
            + '<div class="custom-control custom-switch ca-subopt-switch">'
            + '<input type="checkbox" class="custom-control-input ca-subopt-toggle" id="' + o.id
            + '" data-activity="' + o.activity + '"' + (o.checked ? ' checked' : '') + '>'
            + '<label class="custom-control-label" for="' + o.id + '"></label>'
            + '</div></div>'
            + '<div class="ca-subopt-body"><div class="ca-range">'
            + '<span class="ca-range-val ca-range-val--min">' + o.minVal + '</span>'
            + '<div class="ca-range-track"><div class="ca-range-fill"></div>'
            + '<input type="range" class="ca-range-input ca-range-input--min" id="' + o.minId
            + '" min="' + o.rangeMin + '" max="' + o.rangeMax + '" value="' + o.minVal + '">'
            + '<input type="range" class="ca-range-input ca-range-input--max" id="' + o.maxId
            + '" min="' + o.rangeMin + '" max="' + o.rangeMax + '" value="' + o.maxVal + '">'
            + '</div>'
            + '<span class="ca-range-val ca-range-val--max">' + o.maxVal + '</span>'
            + '</div></div></div>';
    };

    /**
     * Paint a single .ca-range: position the fill between the thumbs and sync the value labels.
     * @param {jQuery} $range The .ca-range element
     */
    const paintRange = function($range) {
        const $min = $range.find('.ca-range-input--min');
        const $max = $range.find('.ca-range-input--max');
        const lo = parseInt($min.attr('min'), 10);
        const hi = parseInt($min.attr('max'), 10);
        const a = parseInt($min.val(), 10);
        const b = parseInt($max.val(), 10);
        const span = (hi - lo) || 1;
        const leftPct = ((a - lo) / span) * 100;
        const rightPct = ((b - lo) / span) * 100;
        $range.find('.ca-range-fill').css({ left: leftPct + '%', width: (rightPct - leftPct) + '%' });
        $range.find('.ca-range-val--min').text(a);
        $range.find('.ca-range-val--max').text(b);
        // When the min thumb reaches the top end both thumbs overlap there; lift it above
        // the max thumb so it stays grabbable (the max thumb wins the overlap everywhere else).
        $min.css('z-index', a >= hi ? 5 : '');
    };

    /**
     * Wire every dual-range slider: keep min <= max and repaint on drag. Idempotent.
     */
    const initRangeSliders = function() {
        $('#courseagent-app .ca-range').each(function() {
            const $range = $(this);
            if ($range.data('caRangeInit')) { return; }
            $range.data('caRangeInit', true);
            const $min = $range.find('.ca-range-input--min');
            const $max = $range.find('.ca-range-input--max');
            $min.on('input change', function() {
                if (parseInt($min.val(), 10) > parseInt($max.val(), 10)) { $min.val($max.val()); }
                paintRange($range);
            });
            $max.on('input change', function() {
                if (parseInt($max.val(), 10) < parseInt($min.val(), 10)) { $max.val($min.val()); }
                paintRange($range);
            });
            paintRange($range);
        });
    };

    /**
     * Wire the per-activity sub-option toggles: each flips on/off freely and independently,
     * dimming its slider when off. Delegated so injected H5P toggles work too.
     */
    const initSubToggles = function() {
        const $app = $('#courseagent-app');
        if ($app.data('caSubtogglesInit')) { return; }
        $app.data('caSubtogglesInit', true);
        // Each sub-option flips on/off freely and independently; just dim its slider when off.
        $app.on('change', '.ca-subopt-toggle', function() {
            $(this).closest('.ca-subopt').toggleClass('ca-subopt--off', !this.checked);
        });
    };

    /**
     * Initialize the course creator.
     * @param {Object} userConfig Configuration object from PHP
     */
    const initH5pTypeSelector = function() {
        if ($('#include-h5p').length === 0 || $('#h5p-toggle-container').length === 0) return;

        // Only the activity types the activated plan includes are shown. Starter = 7 types,
        // Pro = all 17 (mirrors backend STARTER_ALLOWED_TYPES in plan_service.py).
        const allowedKeys = config.saasPlan === 'starter'
            ? Object.keys(H5P_LABELS).filter(function(key) { return STARTER_TYPES.indexOf(key) !== -1; })
            : Object.keys(H5P_LABELS);

        let pillsHtml = '';
        allowedKeys.forEach(function(key) {
            const label = H5P_LABELS[key].replace('H5P: ', '');
            const inputId = 'h5p-type-' + key;
            pillsHtml += '<input type="checkbox" class="btn-check h5p-type-check" id="' + inputId
                + '" value="' + key + '" autocomplete="off">'
                + '<label class="btn ca-h5p-pill" for="' + inputId + '">'
                + '<i class="fa fa-check ca-h5p-pill-check" aria-hidden="true"></i>'
                + '<span>' + label + '</span></label>';
        });

        const subheading = strings.h5pSubheading || 'The AI will select the most suitable activity type for each section.';
        const perSecLabel = strings.perSectionLabel || 'Per section / module';
        const perSecDesc  = strings.perSectionDesc || 'How many to create in every single section.';
        const totalLabel  = strings.totalCourseLabel || 'Total across course';
        const totalDesc   = strings.totalCourseDesc || 'Set a limit for the whole course combined.';
        const selectedLabel = strings.selectedActivityTypes || 'Selected Activity Types';
        const selectAllLabel = strings.selectAll || 'Select all';
        const deselectAllLabel = strings.deselectAll || 'Deselect all';

        const html = '<div id="h5p-type-selector" class="ca-h5p-selector" style="display:none;">'
            + '<p class="ca-h5p-subhead">' + subheading + '</p>'
            + '<div class="ca-h5p-pill-head">'
            + '<span class="ca-h5p-pill-head-label">' + selectedLabel + ' (<span id="h5p-selected-count">0</span>)</span>'
            + '<span class="ca-h5p-pill-actions">'
            + '<a href="#" id="h5p-select-all">' + selectAllLabel + '</a>'
            + '<span class="ca-h5p-sep">/</span>'
            + '<a href="#" id="h5p-deselect-all">' + deselectAllLabel + '</a></span></div>'
            + '<div class="ca-h5p-pills">' + pillsHtml + '</div>'
            + '<small id="h5p-type-error" class="text-danger" style="display:none;">'
            + 'Please select at least one content type.</small>'
            + '<div class="ca-h5p-counts"><div class="ca-subopts">'
            + buildSubopt({
                id: 'h5p-per-section-enabled', activity: 'h5p', checked: false,
                minId: 'h5p-min-per-section', maxId: 'h5p-max-per-section',
                rangeMin: 1, rangeMax: 10, minVal: 1, maxVal: 3,
                title: perSecLabel, desc: perSecDesc
            })
            + buildSubopt({
                id: 'h5p-total-enabled', activity: 'h5p', checked: false,
                minId: 'h5p-min-total', maxId: 'h5p-max-total',
                rangeMin: 1, rangeMax: 20, minVal: 1, maxVal: 6,
                title: totalLabel, desc: totalDesc
            })
            + '</div></div>'
            + '</div>';

        $('#h5p-toggle-container').append(html);

        // The filled/checked pill visual is driven purely by CSS (.h5p-type-check:checked + .ca-h5p-pill),
        // so JS only keeps the live "(N)" count in sync.
        const updateSelectedCount = function() {
            $('#h5p-selected-count').text($('.h5p-type-check:checked').length);
        };

        $('#include-h5p').on('change', function() {
            const $panel = $('#h5p-type-selector').stop(true, true);
            if ($(this).is(':checked')) {
                $panel.slideDown(200);
            } else {
                $panel.slideUp(200);
            }
        });
        if ($('#include-h5p').is(':checked')) {
            $('#h5p-type-selector').show();
        }

        $('#h5p-toggle-container').on('change', '.h5p-type-check', function() {
            updateSelectedCount();
            $('#h5p-type-error').hide();
        });

        $('#h5p-select-all').on('click', function(e) {
            e.preventDefault();
            $('.h5p-type-check').prop('checked', true);
            updateSelectedCount();
            $('#h5p-type-error').hide();
        });
        $('#h5p-deselect-all').on('click', function(e) {
            e.preventDefault();
            $('.h5p-type-check').prop('checked', false);
            updateSelectedCount();
        });

        updateSelectedCount();
    };

    const init = function(userConfig) {
        config = userConfig;
        setupEventListeners();
        Str.get_strings([
            {key: 'js:auto_select',               component: 'local_courseagent'},
            {key: 'js:auto_select_first',          component: 'local_courseagent'},
            {key: 'js:please_enter_topic',         component: 'local_courseagent'},
            {key: 'js:sections_range_error',       component: 'local_courseagent', param: userConfig.maxSections},
            {key: 'js:failed_generate',            component: 'local_courseagent'},
            {key: 'js:error_generating',           component: 'local_courseagent'},
            {key: 'js:generation_cancelled',       component: 'local_courseagent'},
            {key: 'js:planning_course',            component: 'local_courseagent'},
            {key: 'js:plan_failed',                component: 'local_courseagent'},
            {key: 'js:plan_error',                 component: 'local_courseagent'},
            {key: 'js:generating_from_plan',       component: 'local_courseagent'},
            {key: 'js:generating_from_plan_desc',  component: 'local_courseagent'},
            {key: 'js:h5p_subheading',             component: 'local_courseagent'},
            {key: 'js:h5p_per_section_label',      component: 'local_courseagent'},
            {key: 'js:h5p_total_label',            component: 'local_courseagent'},
            {key: 'js:license_fix_instruction',        component: 'local_courseagent'},
            {key: 'js:license_open_settings',          component: 'local_courseagent'},
            {key: 'js:license_transient_instruction',  component: 'local_courseagent'},
            {key: 'js:selected_activity_types',        component: 'local_courseagent'},
            {key: 'js:select_all',                     component: 'local_courseagent'},
            {key: 'js:deselect_all',                   component: 'local_courseagent'},
            {key: 'js:per_section_label',              component: 'local_courseagent'},
            {key: 'js:per_section_desc',               component: 'local_courseagent'},
            {key: 'js:total_course_label',             component: 'local_courseagent'},
            {key: 'js:total_course_desc',              component: 'local_courseagent'},
        ]).then(function(s) {
            strings = {
                autoSelect:               s[0],
                autoSelectFirst:          s[1],
                pleaseEnterTopic:         s[2],
                sectionsRangeError:       s[3],
                failedGenerate:           s[4],
                errorGenerating:          s[5],
                generationCancelled:      s[6],
                planningCourse:           s[7],
                planFailed:               s[8],
                planError:                s[9],
                generatingFromPlan:       s[10],
                generatingFromPlanDesc:   s[11],
                h5pSubheading:            s[12],
                h5pPerSectionLabel:       s[13],
                h5pTotalLabel:            s[14],
                licenseFixInstruction:         s[15],
                licenseOpenSettings:           s[16],
                licenseTransientInstruction:   s[17],
                selectedActivityTypes:         s[18],
                selectAll:                     s[19],
                deselectAll:                   s[20],
                perSectionLabel:               s[21],
                perSectionDesc:                s[22],
                totalCourseLabel:              s[23],
                totalCourseDesc:               s[24],
            };
            CustomSelect.enhance('#courseagent-app select.custom-select');
            updateModelSelector();
            initH5pTypeSelector();
            initRangeSliders();
            initSubToggles();
            return strings;
        }).catch(Notification.exception);
    };

    // -------------------------------------------------------------------------
    // Event setup
    // -------------------------------------------------------------------------

    const setupEventListeners = function() {
        $('#btn-generate').on('click', generateCourseOutline);
        $('#ai-provider').on('change', updateModelSelector);
        $('#btn-cancel-generate').on('click', cancelGeneration);
        $('#btn-loading-dismiss').on('click', dismissLoadingModal);

        // Quiz min/max: show when quiz toggle is on, hide when off.
        $('#include-quiz').on('change', function() {
            const $panel = $('#quiz-minmax').stop(true, true);
            if ($(this).is(':checked')) {
                $panel.slideDown(200);
            } else {
                $panel.slideUp(200);
            }
        });
        if ($('#include-quiz').is(':checked')) {
            $('#quiz-minmax').show();
        } else {
            $('#quiz-minmax').hide();
        }

        // Assignment min/max: show when assignment toggle is on, hide when off.
        $('#include-assignment').on('change', function() {
            const $panel = $('#assignment-minmax').stop(true, true);
            if ($(this).is(':checked')) {
                $panel.slideDown(200);
            } else {
                $panel.slideUp(200);
            }
        });
        if ($('#include-assignment').is(':checked')) {
            $('#assignment-minmax').show();
        } else {
            $('#assignment-minmax').hide();
        }

        // Character counter for Course Topic (max 500).
        const $topic = $('#course-topic');
        const $counter = $('#course-topic-counter');
        if ($topic.length && $counter.length) {
            const updateCounter = function() {
                const len = $topic.val().length;
                $counter.text(len + ' / 500 chars');
                $counter.toggleClass('text-danger', len >= 500);
            };
            $topic.on('input', updateCounter);
            updateCounter();
        }
    };

    /**
     * Populate the model selector based on the chosen provider.
     */
    const updateModelSelector = function() {
        const providerId = $('#ai-provider').val();
        const $modelSelect = $('#ai-model');
        $modelSelect.empty();
        $modelSelect.append('<option value="">' + (config.providers[providerId] ? strings.autoSelectFirst : strings.autoSelect) + '</option>');

        if (config.providers[providerId] && config.providers[providerId].models) {
            config.providers[providerId].models.forEach(function(model) {
                $modelSelect.append('<option value="' + model + '">' + model + '</option>');
            });
        }

        // Rebuild the custom listbox so it reflects the new model list.
        CustomSelect.refresh('#ai-model');
    };

    // -------------------------------------------------------------------------
    // Course generation
    // -------------------------------------------------------------------------

    // ── License error helpers ────────────────────────────────────────────────
    const isLicenseError = function(response) {
        return response && typeof response.error_code === 'string'
            && response.error_code.indexOf('license_') === 0;
    };

    // Message for when the SaaS server is unreachable (transient / network error).
    // Only shown when the user enabled a paid feature (H5P activity generation) for this run.
    const serverConnectivityErrorHtml = function() {
        return 'H5P activity generation needs the CourseAgent service, which can\'t be reached right now.'
            + '<br><br>Turn off H5P activity generation to create your course with the free features,'
            + ' or try again once connectivity is restored.';
    };

    // Show an error inside the loading modal rather than closing it.
    const showModalError = function(html) {
        if (progressTimer) { clearInterval(progressTimer); progressTimer = null; }
        $('#ca-loading-running').hide();
        $('#ca-loading-error-msg').html(html);
        $('#ca-loading-error').show();
        $('#ca-loading-modal').show();
        $('#ca-plan-modal').hide();
        $('#btn-generate').prop('disabled', false);
    };

    // Close the loading modal and reset it back to the in-progress state for next use.
    const dismissLoadingModal = function() {
        $('#ca-loading-modal').fadeOut(200, function() {
            $('#ca-loading-error').hide();
            $('#ca-loading-running').show();
        });
    };

    // Route a license validation failure into the in-modal error panel.
    const renderLicenseError = function(response) {
        let html;
        if (response.is_transient) {
            html = serverConnectivityErrorHtml();
        } else {
            html = '<strong>' + response.error + '</strong>';
            if (strings.licenseFixInstruction) {
                html += '<br>' + strings.licenseFixInstruction;
            }
            if (response.settings_url) {
                html += ' <a href="' + response.settings_url + '" target="_blank" rel="noopener">'
                      + (strings.licenseOpenSettings || 'Open plugin settings') + '</a>';
            }
        }
        showModalError(html);
    };

    // Activity types included in the Starter plan (mirrors backend STARTER_ALLOWED_TYPES).
    // Pro unlocks every type in H5P_LABELS below.
    const STARTER_TYPES = [
        'single_choice_set', 'multiple_choice', 'true_false', 'fill_in_blanks',
        'drag_the_words', 'summary', 'quiz_question_set'
    ];

    // H5P type display labels.
    const H5P_LABELS = {
        'single_choice_set':  'H5P: Single Choice',
        'summary':            'H5P: Summary',
        'drag_the_words':     'H5P: Drag Words',
        'multiple_choice':    'H5P: Multiple Choice',
        'true_false':         'H5P: True/False',
        'fill_in_blanks':     'H5P: Fill in Blanks',
        'quiz_question_set':  'H5P: Quiz (Question Set)',
        'dialog_cards':       'H5P: Dialog Cards',
        'essay':              'H5P: Essay',
        'mark_the_words':     'H5P: Mark the Words',
        'sort_the_paragraphs':'H5P: Sort Paragraphs',
        'crossword':          'H5P: Crossword',
        'find_the_words':     'H5P: Find the Words',
        'accordion':          'H5P: Accordion',
        'personality_quiz':   'H5P: Personality Quiz',
        'chart':              'H5P: Chart',
        'timeline':           'H5P: Timeline',
    };

    const generateCourseOutline = function() {
        const topic       = $('#course-topic').val().trim();
        const customTitle = $('#course-custom-title').val().trim();
        const level       = $('#course-level').val();
        const numSections = parseInt($('#num-sections').val());
        const includeQuiz = $('#include-quiz').is(':checked');
        const includeAssignment = $('#include-assignment').is(':checked');
        const useEmojis   = $('#use-emojis').is(':checked');
        const useDiagrams = $('#use-diagrams').is(':checked');
        const includeH5p  = config.hasSaasKey ? $('#include-h5p').is(':checked') : false;
        const h5pTypes    = includeH5p
            ? $('.h5p-type-check:checked').map(function() { return this.value; }).get().join(',')
            : '';

        // Require topic.
        if (!topic) {
            Notification.addNotification({ message: strings.pleaseEnterTopic, type: 'error' });
            return;
        }
        if (numSections < 2 || numSections > config.maxSections) {
            Notification.addNotification({ message: strings.sectionsRangeError, type: 'error' });
            return;
        }
        if (includeH5p && h5pTypes === '') {
            $('#h5p-type-error').show();
            return;
        }
        $('#h5p-type-error').hide();

        const formData = {
            topic:             topic,
            level:             level,
            numsections:       numSections,
            includequiz:       includeQuiz ? 1 : 0,
            includeassignment: includeAssignment ? 1 : 0,
            useemojis:         useEmojis ? 1 : 0,
            usediagrams:       useDiagrams ? 1 : 0,
            includeh5p:        includeH5p ? 1 : 0,
            h5p_types:         h5pTypes,
            provider:          $('#ai-provider').val() || 0,
            model:             $('#ai-model').val() || '',
            sesskey:           CoreConfig.sesskey,
            custom_title:      customTitle,
            // H5P min/max + which sub-options are active
            h5p_per_section_enabled: $('#h5p-per-section-enabled').is(':checked') ? 1 : 0,
            h5p_total_enabled:       $('#h5p-total-enabled').is(':checked') ? 1 : 0,
            h5p_min_per_section: parseInt($('#h5p-min-per-section').val()) || 1,
            h5p_max_per_section: parseInt($('#h5p-max-per-section').val()) || 3,
            h5p_min_total:       parseInt($('#h5p-min-total').val()) || 1,
            h5p_max_total:       parseInt($('#h5p-max-total').val()) || 6,
            // Quiz min/max + which sub-options are active
            quiz_per_section_enabled: $('#quiz-per-section-enabled').is(':checked') ? 1 : 0,
            quiz_total_enabled:       $('#quiz-total-enabled').is(':checked') ? 1 : 0,
            quiz_min_per_section: parseInt($('#quiz-min-per-section').val()) || 1,
            quiz_max_per_section: parseInt($('#quiz-max-per-section').val()) || 3,
            quiz_min_total:       parseInt($('#quiz-min-total').val()) || 1,
            quiz_max_total:       parseInt($('#quiz-max-total').val()) || 6,
            // Assignment min/max + which sub-options are active
            assignment_per_section_enabled: $('#assignment-per-section-enabled').is(':checked') ? 1 : 0,
            assignment_total_enabled:       $('#assignment-total-enabled').is(':checked') ? 1 : 0,
            assignment_min_per_section: parseInt($('#assignment-min-per-section').val()) || 1,
            assignment_max_per_section: parseInt($('#assignment-max-per-section').val()) || 2,
            assignment_min_total:       parseInt($('#assignment-min-total').val()) || 1,
            assignment_max_total:       parseInt($('#assignment-max-total').val()) || 4,
        };

        $('#btn-generate').prop('disabled', true);

        if (config.hasSaasKey) {
            // Paid flow: plan step first.
            runPlanStep(formData, includeQuiz, includeAssignment);
        } else {
            // Free flow: generate directly.
            showProgress(false);
            runGenerateStep(formData, false, includeQuiz, includeAssignment);
        }
    };

    // ── Plan step (paid users only) ──────────────────────────────────────────

    const runPlanStep = function(formData, includeQuiz, includeAssignment) {
        // Reuse the loading modal, showing the planning stage as the status line.
        $('#ca-loading-desc').text(strings.planningCourse || 'Planning your course...');
        $('#ca-loading-modal').show();

        const planData = Object.assign({}, formData, { action: 'plan' });

        $.ajax({
            url:      CoreConfig.wwwroot + '/local/courseagent/ajax.php',
            type:     'POST',
            data:     planData,
            dataType: 'json',
            success:  function(response) {
                if (isLicenseError(response)) { renderLicenseError(response); return; }
                if (response.success && response.plan) {
                    $('#ca-loading-modal').hide();
                    showPlanModal(response.plan, formData, includeQuiz, includeAssignment);
                } else {
                    showModalError(response.error || strings.planFailed);
                }
            },
            error: function(xhr) {
                let html = xhr.status === 0
                    ? serverConnectivityErrorHtml()
                    : (strings.planError || strings.planFailed);
                if (xhr.status !== 0) {
                    try { html = JSON.parse(xhr.responseText).error || html; } catch (e) {}
                }
                showModalError(html);
            }
        });
    };

    const showPlanModal = function(plan, formData, includeQuiz, includeAssignment) {
        // Populate title + summary.
        $('#ca-plan-modal-title').text(plan.title || '');
        $('#ca-plan-summary').text(plan.summary || '');

        // Build section rows.
        const $sections = $('#ca-plan-sections').empty();
        (plan.sections || []).forEach(function(sec, idx) {
            const $item = $('<div class="list-group-item px-3 py-2"></div>');

            const $name = $('<div class="font-weight-bold mb-1"></div>').text(
                'Section ' + (idx + 1) + ': ' + (sec.name || '')
            );
            const $desc = $('<div class="small text-muted mb-2"></div>').text(sec.description || '');

            const $badges = $('<div class="mb-1"></div>');
            $badges.append('<span class="badge badge-primary mr-1">Lesson</span>');

            // Support the new count shape (quiz_count / assignment_count / h5p_types[]) and the older one.
            const quizCount = (typeof sec.quiz_count === 'number') ? sec.quiz_count : (sec.quiz ? 1 : 0);
            const assignCount = (typeof sec.assignment_count === 'number') ? sec.assignment_count : (sec.assignment ? 1 : 0);
            const h5pTypes = Array.isArray(sec.h5p_types) ? sec.h5p_types : (sec.h5p_type ? [sec.h5p_type] : []);

            if (quizCount > 0) {
                $badges.append('<span class="badge badge-info mr-1">Quiz' + (quizCount > 1 ? ' × ' + quizCount : '') + '</span>');
            }
            if (assignCount > 0) {
                $badges.append('<span class="badge badge-secondary mr-1">Assignment' + (assignCount > 1 ? ' × ' + assignCount : '') + '</span>');
            }
            h5pTypes.forEach(function(t) {
                var label = H5P_LABELS[t] || ('H5P: ' + t);
                $badges.append('<span class="badge badge-success mr-1">' + label + '</span>');
            });

            if (formData.usediagrams) {
                $badges.append('<span class="badge badge-warning mr-1">Diagram</span>');
            }

            $item.append($name).append($desc).append($badges);

            if (sec.h5p_reason && h5pTypes.length > 0) {
                $item.append(
                    $('<div class="small text-muted font-italic mt-1"></div>').text(sec.h5p_reason)
                );
            }

            $sections.append($item);
        });

        // Wire buttons.
        $('#btn-plan-approve').off('click').on('click', function() {
            hidePlanModal();
            showProgress(true);
            runGenerateStep(formData, true, includeQuiz, includeAssignment);
        });
        $('#btn-plan-edit').off('click').on('click', function() {
            hidePlanModal();
            $('#btn-generate').prop('disabled', false);
        });

        $('#ca-plan-modal').show();
    };

    const hidePlanModal = function() {
        $('#ca-plan-modal').hide();
    };

    // ── Generate step (both flows) ───────────────────────────────────────────

    const runGenerateStep = function(formData, usePlan, includeQuiz, includeAssignment) {
        const requestData = Object.assign({}, formData, {
            action:   'generate',
            use_plan: usePlan ? 1 : 0,
        });

        console.log('[CA DEBUG] Sending generate request:', {
            topic: requestData.topic,
            provider: requestData.provider,
            numsections: requestData.numsections,
            use_plan: requestData.use_plan,
            url: CoreConfig.wwwroot + '/local/courseagent/ajax.php'
        });

        generateXhr = $.ajax({
            url:      CoreConfig.wwwroot + '/local/courseagent/ajax.php',
            type:     'POST',
            data:     requestData,
            dataType: 'json',
            success:  function(response) {
                generateXhr = null;
                if (progressTimer) { clearInterval(progressTimer); progressTimer = null; }
                console.log('[CA DEBUG] Generate success response:', response);
                if (isLicenseError(response)) {
                    renderLicenseError(response);
                    return;
                }
                if (response.success) {
                    if (response.license_warning) {
                        Notification.addNotification({ message: response.license_warning, type: 'warning' });
                    }
                    var redirectUrl = CoreConfig.wwwroot + '/local/courseagent/preview.php';
                    console.log('[CA DEBUG] Redirecting to:', redirectUrl);
                    if (response.fallback_log && response.fallback_log.length > 0) {
                        console.warn('[CA FALLBACK LOG]', response.fallback_log.length, 'attempt(s):', response.fallback_log);
                    }
                    window.location.href = redirectUrl;
                    return;
                }
                console.warn('[CA DEBUG] generate failed:', response.error);
                showModalError(response.error || strings.failedGenerate);
            },
            error: function(xhr) {
                generateXhr = null;
                if (xhr.status === 0 && xhr.statusText === 'abort') { return; }
                let html;
                if (xhr.status === 0) {
                    html = serverConnectivityErrorHtml();
                } else {
                    html = strings.errorGenerating;
                    let parsed = null;
                    try { parsed = JSON.parse(xhr.responseText); html = parsed.error || html; } catch (e) {}
                    if (parsed && parsed.fallback_log && parsed.fallback_log.length > 0) {
                        console.error('[CA FALLBACK LOG]', parsed.fallback_log);
                    }
                }
                showModalError(html);
            }
        });
    };

    const cancelGeneration = function() {
        if (generateXhr) {
            generateXhr.abort();
            generateXhr = null;
        }
        dismissLoadingModal();
        $('#btn-generate').prop('disabled', false);
        Notification.addNotification({
            message: strings.generationCancelled,
            type:    'info'
        });
    };

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    const showProgress = function(usePlan) {
        // The progress bar is indeterminate (continuous stripe animation set in the markup):
        // course generation is a single AI call with no real sub-progress to report. We only
        // surface the real server stage as the status line, never a fake percentage or step.
        var $desc = $('#ca-loading-desc');

        if (usePlan) {
            $('#ca-loading-title').text(strings.generatingFromPlan);
            $desc.text(strings.generatingFromPlanDesc);
        }

        $('#ca-loading-modal').fadeIn(200);

        // Poll the server for the real current stage and show it as the status line.
        progressTimer = setInterval(function() {
            $.ajax({
                url:  CoreConfig.wwwroot + '/local/courseagent/ajax.php',
                type: 'POST',
                data: { action: 'get_progress', sesskey: CoreConfig.sesskey },
                dataType: 'json',
                success: function(resp) {
                    if (resp.success && resp.progress && resp.progress.message) {
                        $desc.text(resp.progress.message);
                    }
                }
            });
        }, 1000);
    };

    const hideProgress = function() {
        if (progressTimer) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
        $('#ca-loading-modal').fadeOut(200);
    };

    return { init: init };
});
