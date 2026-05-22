// This file is part of Course Agent - AI Course Creator Plugin for Moodle

/**
 * @module local_courseagent/coursecreator
 * @copyright 2026 Course Agent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/config'],
    function($, Ajax, Notification, Str, CoreConfig) {

    'use strict';

    let config = {};
    let strings = {};
    let extractedFileText = '';
    let progressTimer = null;
    let generateXhr = null;

    // Accepted MIME types / extensions.
    const ACCEPTED_EXTS = ['txt','pdf','docx','pptx','odt','rtf','md','csv','epub'];
    const MAX_FILE_BYTES = 50 * 1024 * 1024; // 50 MB

    /**
     * Initialize the course creator.
     * @param {Object} userConfig Configuration object from PHP
     */
    const initH5pTypeSelector = function() {
        if ($('#include-h5p').length === 0) return;

        let pillsHtml = '';
        Object.keys(H5P_LABELS).forEach(function(key) {
            const label = H5P_LABELS[key].replace('H5P: ', '');
            pillsHtml += '<label class="h5p-type-pill badge badge-secondary mr-1 mb-1"'
                + ' style="cursor:pointer;font-size:0.75em;font-weight:normal;padding:5px 8px;">'
                + '<input type="checkbox" class="h5p-type-check" value="' + key + '" checked'
                + ' style="margin-right:4px;vertical-align:middle;"> ' + label + '</label>';
        });

        const html = '<div id="h5p-type-selector" class="mt-2 p-2 bg-white border rounded" style="display:none;">'
            + '<div class="d-flex justify-content-between align-items-center mb-1">'
            + '<small class="font-weight-bold text-muted">Content types the AI can choose:</small>'
            + '<div><a href="#" id="h5p-select-all" class="small">All</a>'
            + ' / <a href="#" id="h5p-deselect-all" class="small">None</a></div></div>'
            + '<div class="d-flex flex-wrap">' + pillsHtml + '</div>'
            + '<small id="h5p-type-error" class="text-danger" style="display:none;">'
            + 'Please select at least one content type.</small>'
            + '</div>';

        $('#include-h5p').closest('.d-flex.align-items-center.justify-content-between').after(html);

        $('#include-h5p').on('change', function() {
            $('#h5p-type-selector').toggle($(this).is(':checked'));
        });
        if ($('#include-h5p').is(':checked')) {
            $('#h5p-type-selector').show();
        }

        $('#h5p-select-all').on('click', function(e) {
            e.preventDefault();
            $('.h5p-type-check').prop('checked', true);
        });
        $('#h5p-deselect-all').on('click', function(e) {
            e.preventDefault();
            $('.h5p-type-check').prop('checked', false);
        });
    };

    const init = function(userConfig) {
        config = userConfig;
        setupEventListeners();
        setupDropzone();
        initH5pTypeSelector();

        Str.get_strings([
            {key: 'js:auto_select',               component: 'local_courseagent'},
            {key: 'js:auto_select_first',          component: 'local_courseagent'},
            {key: 'js:unsupported_filetype',       component: 'local_courseagent'},
            {key: 'js:file_too_large',             component: 'local_courseagent'},
            {key: 'js:could_not_extract',          component: 'local_courseagent'},
            {key: 'js:upload_failed',              component: 'local_courseagent'},
            {key: 'js:extracting_text',            component: 'local_courseagent'},
            {key: 'js:characters_extracted',       component: 'local_courseagent'},
            {key: 'js:please_enter_topic',         component: 'local_courseagent'},
            {key: 'js:sections_range_error',       component: 'local_courseagent', param: userConfig.maxSections},
            {key: 'js:failed_generate',            component: 'local_courseagent'},
            {key: 'js:error_generating',           component: 'local_courseagent'},
            {key: 'js:generation_cancelled',       component: 'local_courseagent'},
            {key: 'js:adding_quizzes_assignments', component: 'local_courseagent'},
            {key: 'js:adding_quizzes',             component: 'local_courseagent'},
            {key: 'js:adding_assignments',         component: 'local_courseagent'},
            {key: 'js:planning_course',            component: 'local_courseagent'},
            {key: 'js:plan_failed',                component: 'local_courseagent'},
            {key: 'js:plan_error',                 component: 'local_courseagent'},
            {key: 'js:generating_from_plan',      component: 'local_courseagent'},
            {key: 'js:generating_from_plan_desc', component: 'local_courseagent'},
        ]).then(function(s) {
            strings = {
                autoSelect:               s[0],
                autoSelectFirst:          s[1],
                unsupportedFiletype:      s[2],
                fileTooLarge:             s[3],
                couldNotExtract:          s[4],
                uploadFailed:             s[5],
                extractingText:           s[6],
                charactersExtracted:      s[7],
                pleaseEnterTopic:         s[8],
                sectionsRangeError:       s[9],
                failedGenerate:           s[10],
                errorGenerating:          s[11],
                generationCancelled:      s[12],
                addingQuizzesAssignments: s[13],
                addingQuizzes:            s[14],
                addingAssignments:        s[15],
                planningCourse:           s[16],
                planFailed:               s[17],
                planError:                s[18],
                generatingFromPlan:       s[19],
                generatingFromPlanDesc:   s[20],
            };
            updateModelSelector();
            return strings;
        }).catch(Notification.exception);
    };

    // -------------------------------------------------------------------------
    // Event setup
    // -------------------------------------------------------------------------

    const setupEventListeners = function() {
        $('#btn-generate').on('click', generateCourseOutline);
        $('#upload-remove').on('click', removeUploadedFile);
        $('#ai-provider').on('change', updateModelSelector);
        $('#btn-cancel-generate').on('click', cancelGeneration);

        // Character counter for Course Topic (max 500).
        const $topic = $('#course-topic');
        const $counter = $('#course-topic-counter');
        if ($topic.length && $counter.length) {
            const updateCounter = function() {
                const len = $topic.val().length;
                $counter.text(len + ' / 500');
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
    };

    // -------------------------------------------------------------------------
    // Dropzone setup
    // -------------------------------------------------------------------------

    const setupDropzone = function() {
        const $zone  = $('#upload-dropzone');
        const $input = $('#upload-file-input');

        // Click on zone -> open file picker.
        $zone.on('click', function(e) {
            if ($(e.target).closest('#upload-remove').length) return; // don't open picker when removing
            $input.trigger('click');
        });

        // File picker change.
        $input.on('change', function() {
            if (this.files && this.files[0]) {
                handleFileSelected(this.files[0]);
            }
        });

        // Drag events.
        $zone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $zone.addClass('drag-over');
        });

        $zone.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $zone.removeClass('drag-over');
        });

        $zone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $zone.removeClass('drag-over');
            const files = e.originalEvent.dataTransfer.files;
            if (files && files[0]) {
                handleFileSelected(files[0]);
            }
        });
    };

    // -------------------------------------------------------------------------
    // File handling
    // -------------------------------------------------------------------------

    const handleFileSelected = function(file) {
        // Validate extension.
        const ext = file.name.split('.').pop().toLowerCase();
        if (ACCEPTED_EXTS.indexOf(ext) === -1) {
            Notification.addNotification({
                message: strings.unsupportedFiletype.replace('{$a}', ext),
                type: 'error'
            });
            return;
        }

        // Validate size.
        if (file.size > MAX_FILE_BYTES) {
            Notification.addNotification({
                message: strings.fileTooLarge.replace('{$a}', formatBytes(file.size)),
                type: 'error'
            });
            return;
        }

        // Show extracting state.
        showDropzoneState('extracting', file.name);

        // Upload to server for text extraction.
        const formData = new FormData();
        formData.append('action', 'extract_content');
        formData.append('sesskey', CoreConfig.sesskey);
        formData.append('file', file);

        $.ajax({
            url: CoreConfig.wwwroot + '/local/courseagent/ajax.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    extractedFileText = response.text;
                    $('#upload-extracted-text').val(response.text);
                    showDropzoneState('done', file.name, response.charcount);
                } else {
                    showDropzoneState('idle');
                    Notification.addNotification({
                        message: response.error || strings.couldNotExtract,
                        type: 'error'
                    });
                }
            },
            error: function(xhr) {
                showDropzoneState('idle');
                let msg = strings.uploadFailed;
                try { msg = JSON.parse(xhr.responseText).error || msg; } catch (e) {}
                Notification.addNotification({ message: msg, type: 'error' });
            }
        });
    };

    const removeUploadedFile = function(e) {
        e.stopPropagation();
        extractedFileText = '';
        $('#upload-extracted-text').val('');
        $('#upload-file-input').val('');
        showDropzoneState('idle');
    };

    /**
     * Update the dropzone's visual state.
     * @param {string} state  'idle' | 'extracting' | 'done'
     * @param {string} [name] File name
     * @param {number} [chars] Character count
     */
    const showDropzoneState = function(state, name, chars) {
        const $inner = $('#upload-dropzone-inner');
        const $info  = $('#upload-file-info');

        if (state === 'idle') {
            $inner.show();
            $info.addClass('d-none');
            $('#upload-dropzone').removeClass('has-file');
        } else if (state === 'extracting') {
            $inner.hide();
            $info.removeClass('d-none');
            $('#upload-filename').text(name);
            $('#upload-charcount').html('<i class="fa fa-spinner fa-spin"></i> ' + strings.extractingText);
            $('#upload-dropzone').addClass('has-file');
        } else if (state === 'done') {
            $inner.hide();
            $info.removeClass('d-none');
            $('#upload-filename').text(name);
            $('#upload-charcount').html(
                '<i class="fa fa-check-circle text-success"></i> ' +
                strings.charactersExtracted.replace('{$a}', chars.toLocaleString())
            );
            $('#upload-dropzone').addClass('has-file');
        }
    };

    const formatBytes = function(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    // -------------------------------------------------------------------------
    // Course generation
    // -------------------------------------------------------------------------

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
        const useSvg      = $('#use-svg').is(':checked');
        const includeH5p  = config.hasSaasKey ? $('#include-h5p').is(':checked') : false;
        const h5pTypes    = includeH5p
            ? $('.h5p-type-check:checked').map(function() { return this.value; }).get().join(',')
            : '';

        // Require topic OR uploaded file.
        if (!topic && !extractedFileText) {
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
            usesvg:            useSvg ? 1 : 0,
            includeh5p:        includeH5p ? 1 : 0,
            h5p_types:         h5pTypes,
            provider:          $('#ai-provider').val() || 0,
            model:             $('#ai-model').val() || '',
            sesskey:           CoreConfig.sesskey,
            extracted_content: extractedFileText,
            custom_title:      customTitle,
        };

        $('#btn-generate').prop('disabled', true);

        if (config.hasSaasKey) {
            // Paid flow: plan step first.
            runPlanStep(formData, includeQuiz, includeAssignment);
        } else {
            // Free flow: generate directly.
            showProgress(includeQuiz, includeAssignment);
            runGenerateStep(formData, false, includeQuiz, includeAssignment);
        }
    };

    // ── Plan step (paid users only) ──────────────────────────────────────────

    const runPlanStep = function(formData, includeQuiz, includeAssignment) {
        // Show spinner reusing the loading modal with a different heading.
        $('#ca-loading-progress').css('width', '30%');
        $('#ca-loading-percent').text('');
        $('#ca-step-outline').find('.ca-step-label').text(strings.planningCourse || 'Planning your course...');
        $('#ca-step-lessons, #ca-step-extras').hide();
        $('#ca-loading-modal').show();

        const planData = Object.assign({}, formData, { action: 'plan' });

        $.ajax({
            url:      CoreConfig.wwwroot + '/local/courseagent/ajax.php',
            type:     'POST',
            data:     planData,
            dataType: 'json',
            success:  function(response) {
                $('#ca-loading-modal').hide();
                // Restore steps for later.
                $('#ca-step-lessons, #ca-step-extras').show();
                if (response.success && response.plan) {
                    showPlanModal(response.plan, formData, includeQuiz, includeAssignment);
                } else {
                    $('#btn-generate').prop('disabled', false);
                    Notification.addNotification({
                        message: response.error || strings.planFailed,
                        type: 'error'
                    });
                }
            },
            error: function(xhr) {
                $('#ca-loading-modal').hide();
                $('#ca-step-lessons, #ca-step-extras').show();
                $('#btn-generate').prop('disabled', false);
                let msg = strings.planError || strings.planFailed;
                try { msg = JSON.parse(xhr.responseText).error || msg; } catch (e) {}
                Notification.addNotification({ message: msg, type: 'error' });
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
            if (sec.quiz) {
                $badges.append('<span class="badge badge-info mr-1">Quiz</span>');
            }
            if (sec.assignment) {
                $badges.append('<span class="badge badge-secondary mr-1">Assignment</span>');
            }
            if (sec.h5p_type) {
                var label = H5P_LABELS[sec.h5p_type] || ('H5P: ' + sec.h5p_type);
                $badges.append('<span class="badge badge-success mr-1">' + label + '</span>');
            }

            $item.append($name).append($desc).append($badges);

            if (sec.h5p_reason && sec.h5p_type) {
                $item.append(
                    $('<div class="small text-muted font-italic mt-1"></div>').text(sec.h5p_reason)
                );
            }

            $sections.append($item);
        });

        // Wire buttons.
        $('#btn-plan-approve').off('click').on('click', function() {
            hidePlanModal();
            showProgress(includeQuiz, includeAssignment, true);
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
                console.log('[CA DEBUG] Generate success response:', response);
                if (response.success) {
                    var redirectUrl = CoreConfig.wwwroot + '/local/courseagent/preview.php';
                    console.log('[CA DEBUG] Redirecting to:', redirectUrl);
                    if (response.fallback_log && response.fallback_log.length > 0) {
                        console.warn('[CA FALLBACK LOG]', response.fallback_log.length, 'attempt(s):', response.fallback_log);
                    }
                    window.location.href = redirectUrl;
                    return;
                }
                console.warn('[CA DEBUG] generate failed:', response.error);
                hideProgress();
                $('#btn-generate').prop('disabled', false);
                Notification.addNotification({
                    message: response.error || strings.failedGenerate,
                    type: 'error'
                });
            },
            error: function(xhr) {
                generateXhr = null;
                if (xhr.status === 0 && xhr.statusText === 'abort') { return; }
                hideProgress();
                $('#btn-generate').prop('disabled', false);
                let msg = strings.errorGenerating;
                let parsed = null;
                try { parsed = JSON.parse(xhr.responseText); msg = parsed.error || msg; } catch (e) {}
                if (parsed && parsed.fallback_log && parsed.fallback_log.length > 0) {
                    console.error('[CA FALLBACK LOG]', parsed.fallback_log);
                }
                Notification.addNotification({ message: msg, type: 'error' });
            }
        });
    };

    const cancelGeneration = function() {
        if (generateXhr) {
            generateXhr.abort();
            generateXhr = null;
        }
        hideProgress();
        $('#btn-generate').prop('disabled', false);
        Notification.addNotification({
            message: strings.generationCancelled,
            type:    'info'
        });
    };

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    const showProgress = function(includeQuiz, includeAssignment, usePlan) {
        // Reset progress to 0.
        var $bar = $('#ca-loading-progress');
        var $pct = $('#ca-loading-percent');
        var $extras = $('#ca-step-extras');

        $bar.css('width', '0%');
        $pct.text('0%');

        // Configure extras step visibility and label.
        if (!includeQuiz && !includeAssignment) {
            $extras.hide();
        } else {
            $extras.show();
            var label = '';
            if (includeQuiz && includeAssignment) {
                label = strings.addingQuizzesAssignments;
            } else if (includeQuiz) {
                label = strings.addingQuizzes;
            } else {
                label = strings.addingAssignments;
            }
            $extras.find('.ca-step-label').text(label);
        }

        // Reset step states.
        var $steps = $('.ca-step:visible');
        $steps.removeClass('ca-step-done ca-step-active').addClass('ca-step-pending');
        $steps.find('.ca-step-bubble').html('');
        $steps.eq(0).addClass('ca-step-active').removeClass('ca-step-pending');
        $steps.eq(0).find('.ca-step-bubble').html('<i class="fa fa-hourglass-half" aria-hidden="true"></i>');

        if (usePlan) {
            $('#ca-loading-title').text(strings.generatingFromPlan);
            $('#ca-loading-desc').text(strings.generatingFromPlanDesc);
        }

        $('#ca-loading-modal').fadeIn(200);

        // Fake step progression with random timing (2-5 seconds per step).
        var currentStep = 0;
        var totalSteps = $steps.length;

        function moveToNextStep() {
            if (currentStep < totalSteps) {
                // Mark current step as done.
                var $currentStep = $steps.eq(currentStep);
                $currentStep.removeClass('ca-step-active ca-step-pending').addClass('ca-step-done');
                $currentStep.find('.ca-step-bubble').html('<i class="fa fa-check" aria-hidden="true"></i>');

                currentStep++;

                // Move to next step if exists.
                if (currentStep < totalSteps) {
                    var $nextStep = $steps.eq(currentStep);
                    $nextStep.removeClass('ca-step-pending ca-step-done').addClass('ca-step-active');
                    $nextStep.find('.ca-step-bubble').html('<i class="fa fa-hourglass-half" aria-hidden="true"></i>');

                    // Schedule next step with random delay (1-3 seconds).
                    var randomDelay = Math.floor(Math.random() * 2000) + 1000;
                    setTimeout(moveToNextStep, randomDelay);
                }
            }
        }

        // Track last known server progress for real progress bar.
        var lastServerPct = 0;
        var displayPct = 0;

        // Poll server for real progress updates (for progress bar only).
        progressTimer = setInterval(function() {
            $.ajax({
                url:  CoreConfig.wwwroot + '/local/courseagent/ajax.php',
                type: 'POST',
                data: { action: 'get_progress', sesskey: CoreConfig.sesskey },
                dataType: 'json',
                success: function(resp) {
                    if (resp.success && resp.progress) {
                        var p = resp.progress;
                        lastServerPct = p.percent || 0;

                        // Update message if provided.
                        if (p.message) {
                            $('#ca-loading-modal .text-muted.mb-4').text(p.message);
                        }
                    }
                }
            });

            // Smoothly animate display toward last server percentage.
            var target = Math.max(lastServerPct, displayPct);
            if (displayPct < target) {
                displayPct = Math.min(target, displayPct + Math.max(0.5, (target - displayPct) * 0.15));
            } else if (displayPct < 90) {
                // Slowly creep forward when waiting for next server update.
                displayPct = Math.min(90, displayPct + 0.2);
            }
            var rounded = Math.round(displayPct);
            $bar.css('width', rounded + '%');
            $pct.text(rounded + '%');
        }, 500);

        // Start fake step progression.
        var initialDelay = Math.floor(Math.random() * 2000) + 1000;
        setTimeout(moveToNextStep, initialDelay);
    };

    const hideProgress = function() {
        // Stop the timer.
        if (progressTimer) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
        // Animate to 100% before closing.
        var $bar = $('#ca-loading-progress');
        var $pct = $('#ca-loading-percent');

        $bar.css('width', '100%');
        $pct.text('100%');

        setTimeout(function() {
            $('#ca-loading-modal').fadeOut(200);
        }, 400);
    };

    return { init: init };
});
