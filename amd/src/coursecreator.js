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
    const init = function(userConfig) {
        config = userConfig;
        setupEventListeners();
        setupDropzone();

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

    const generateCourseOutline = function() {
        const topic       = $('#course-topic').val().trim();
        const customTitle = $('#course-custom-title').val().trim();
        const level       = $('#course-level').val();
        const numSections = parseInt($('#num-sections').val());
        const includeQuiz = $('#include-quiz').is(':checked');
        const includeAssignment = $('#include-assignment').is(':checked');
        const useEmojis   = $('#use-emojis').is(':checked');
        const useSvg      = $('#use-svg').is(':checked');

        // Require topic OR uploaded file.
        if (!topic && !extractedFileText) {
            Notification.addNotification({
                message: strings.pleaseEnterTopic,
                type: 'error'
            });
            return;
        }

        if (numSections < 2 || numSections > config.maxSections) {
            Notification.addNotification({
                message: strings.sectionsRangeError,
                type: 'error'
            });
            return;
        }

        showProgress(includeQuiz, includeAssignment);
        $('#btn-generate').prop('disabled', true);

        const requestData = {
            action:            'generate',
            topic:             topic,
            level:             level,
            numsections:       numSections,
            includequiz:       includeQuiz ? 1 : 0,
            includeassignment: includeAssignment ? 1 : 0,
            useemojis:         useEmojis ? 1 : 0,
            usesvg:            useSvg ? 1 : 0,
            provider:          $('#ai-provider').val() || 0,
            model:             $('#ai-model').val() || '',
            sesskey:           CoreConfig.sesskey,
            extracted_content: extractedFileText,
            custom_title:      customTitle
        };

        console.log('[CA DEBUG] Sending generate request:', {
            topic: requestData.topic,
            provider: requestData.provider,
            model: requestData.model,
            numsections: requestData.numsections,
            includequiz: requestData.includequiz,
            includeassignment: requestData.includeassignment,
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
                    console.log('[CA DEBUG] used_provider:', response.used_provider, '| used_model:', response.used_model);
                    if (response.fallback_log && response.fallback_log.length > 0) {
                        console.warn('[CA FALLBACK LOG] ' + response.fallback_log.length + ' attempt(s) before success:');
                        console.table(response.fallback_log);
                    } else {
                        console.log('[CA FALLBACK LOG] First attempt succeeded — no fallbacks needed.');
                    }
                    window.location.href = redirectUrl;
                    return;
                }

                console.warn('[CA DEBUG] response.success=false. Error:', response.error);
                hideProgress();
                $('#btn-generate').prop('disabled', false);
                Notification.addNotification({
                    message: response.error || strings.failedGenerate,
                    type:    'error'
                });
            },
            error: function(xhr) {
                generateXhr = null;
                console.error('[CA DEBUG] XHR error. status:', xhr.status, 'statusText:', xhr.statusText);
                console.error('[CA DEBUG] Raw responseText:', xhr.responseText);
                // Aborted requests have status 0 — don't show error for user-initiated cancel.
                if (xhr.status === 0 && xhr.statusText === 'abort') {
                    console.log('[CA DEBUG] Request was user-cancelled (abort).');
                    return;
                }
                hideProgress();
                $('#btn-generate').prop('disabled', false);
                let msg = strings.errorGenerating;
                let parsed = null;
                try {
                    parsed = JSON.parse(xhr.responseText);
                    msg = parsed.error || msg;
                } catch (e) {
                    console.error('[CA DEBUG] Server did not return valid JSON. Raw response text:', xhr.responseText);
                    console.error('[CA DEBUG] This is likely a PHP fatal error (OOM/timeout) — check PHP error_log.');
                }
                if (parsed) {
                    console.error('[CA DEBUG] Server returned error object:', parsed);
                    if (parsed.debug) {
                        console.error('[CA DEBUG] Debug info from server:', parsed.debug);
                    }
                    if (parsed.fallback_log && parsed.fallback_log.length > 0) {
                        console.error('[CA FALLBACK LOG] ' + parsed.fallback_log.length + ' failed attempt(s) before giving up:');
                        console.table(parsed.fallback_log);
                    } else {
                        console.error('[CA FALLBACK LOG] No fallback_log in error response (PHP crashed before fallback loop completed).');
                    }
                }
                console.error('[CA DEBUG] Error message shown to user:', msg);
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

    const showProgress = function(includeQuiz, includeAssignment) {
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
