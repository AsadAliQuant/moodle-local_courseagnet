// This file is part of Course Agent - AI Course Creator Plugin for Moodle

/**
 * @module local_courseagent/preview
 * @copyright 2026 Course Agent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    'use strict';

    let config = {};
    let strings = {};  // Language strings from PHP.
    let state = {
        currentSectionIndex: 0,
        currentTab: 'content',
        expandedSections: new Set([0]),
        chatMessages: [],
        quickActions: [],
        chatHistory: []  // Mirrors session history — used for optimistic display tracking.
    };

    var chatXhr = null;
    var chatLoading = false;

    const init = function() {
        // Adjust app top offset to match whatever the fixed navbar height is (theme-agnostic).
        var nb = document.querySelector('nav.navbar.fixed-top');
        var app = document.getElementById('courseagent-preview-app');
        if (nb && app) {
            app.style.setProperty('--ca-navbar-height', nb.offsetHeight + 'px');
        }

        // Read config from inline script tag (bypasses js_call_amd 1024 char limit).
        var configEl = document.getElementById('ca-config-data');
        if (configEl) {
            try {
                var userConfig = JSON.parse(configEl.textContent);
                config = userConfig;
                strings = config.strings || {};
            } catch (e) {
                console.error('Failed to parse ca-config-data:', e);
                strings = {};
            }
        } else {
            // Fallback: empty config (shouldn't happen in normal flow).
            strings = {};
        }

        // Populate dynamic state with language strings.
        state.chatMessages = [
            { type: 'ai', text: strings.aiInitialMsg }
        ];
        state.quickActions = [
            strings.quickaction1,
            strings.quickaction2,
            strings.quickaction3
        ];

        var dataEl = document.getElementById('ca-preview-data');
        if (dataEl) {
            try {
                config.courseData = JSON.parse(dataEl.textContent);
            } catch (e) {
                config.courseData = null;
            }
        }
        if (config.courseData) {
            $('#ca-course-title').text(config.courseData.title || strings.untitledCourse);
            renderAll();
        }
        setupEventListeners();
    };

    // Track which item is currently selected for editing.
    var currentEditContext = {
        targetType: null,   // 'lesson', 'quiz', 'question', 'assignment'
        targetIndex: 0,   // section index
        questionIndex: null // null unless targetType === 'question'
    };

    const updateEditContext = function(targetType, targetIndex, questionIndex) {
        currentEditContext.targetType = targetType;
        currentEditContext.targetIndex = targetIndex;
        currentEditContext.questionIndex = questionIndex;
    };

    const renderAll = function() {
        renderSidebar();
        renderMain();
        renderChat();
    };

    /* ── Sidebar Tree ── */
    const renderSidebar = function() {
        var sections = config.courseData.sections || [];
        var html = '';

        sections.forEach(function(section, index) {
            var isExpanded = state.expandedSections.has(index);
            var sectionNum = index + 1;

            html += '<div class="ca-tree-section">';
            html += '<button class="ca-tree-section-header' + (isExpanded ? ' is-expanded' : '') + '" data-index="' + index + '">';
            html += '<i class="fa fa-chevron-' + (isExpanded ? 'down' : 'right') + ' ca-tree-chevron"></i>';
            html += '<span class="ca-tree-section-title">' + strings.sectionLabel.replace('{$a}', sectionNum) + ': ' + escapeHtml(section.name) + '</span>';
            html += '</button>';

            if (isExpanded) {
                html += '<div class="ca-tree-children">';

                // Lesson item.
                if (section.lesson) {
                    var isActive = state.currentSectionIndex === index && state.currentTab === 'content';
                    html += '<a href="#" class="ca-tree-item' + (isActive ? ' is-active' : '') + '" ' +
                            'data-section="' + index + '" data-tab="content">';
                    html += '<i class="fa fa-file-alt ca-tree-item-icon"></i>';
                    html += '<span class="ca-tree-item-label">' + strings.lessonLabel + ': ' + escapeHtml(section.name) + '</span>';
                    html += '</a>';
                }

                // Quiz item.
                if (section.quiz && section.quiz.questions && section.quiz.questions.length > 0) {
                    var isQuizActive = state.currentSectionIndex === index && state.currentTab === 'quiz';
                    html += '<a href="#" class="ca-tree-item' + (isQuizActive ? ' is-active' : '') + '" ' +
                            'data-section="' + index + '" data-tab="quiz">';
                    html += '<i class="fa fa-question-circle ca-tree-item-icon"></i>';
                    html += '<span class="ca-tree-item-label">' + strings.quizCount.replace('{$a}', section.quiz.questions.length) + '</span>';
                    html += '</a>';
                }

                // Assignment item.
                if (section.assignment) {
                    var isAssignActive = state.currentSectionIndex === index && state.currentTab === 'assignment';
                    html += '<a href="#" class="ca-tree-item' + (isAssignActive ? ' is-active' : '') + '" ' +
                            'data-section="' + index + '" data-tab="assignment">';
                    html += '<i class="fa fa-tasks ca-tree-item-icon"></i>';
                    html += '<span class="ca-tree-item-label">' + strings.assignmentLabel + '</span>';
                    html += '</a>';
                }

                html += '</div>';
            }

            html += '</div>';
        });

        $('#ca-sidebar-tree').html(html);
    };

    /* ── Main Content ── */
    const renderMain = function() {
        var sections = config.courseData.sections || [];
        var section = sections[state.currentSectionIndex];
        if (!section) { return; }

        var hasLesson = !!section.lesson;
        var hasQuiz = !!(section.quiz && section.quiz.questions && section.quiz.questions.length > 0);
        var hasAssignment = !!section.assignment;

        
        var html = '';

        html += '<div class="ca-main-content">';
        html += '<div class="ca-main-card">';
        html += '<div class="ca-ai-badge"><i class="fa fa-magic"></i> ' + strings.aiGenerated + '</div>';

        if (state.currentTab === 'content') {
            html += renderLessonContent(section);
        } else if (state.currentTab === 'quiz') {
            html += renderQuizContent(section);
        } else if (state.currentTab === 'assignment') {
            html += renderAssignmentContent(section);
        }

        html += '</div>';
        html += '</div>';

        $('#ca-main').html(html);
    };

    const renderLessonContent = function(section) {
        var html = '';
        if (!section.lesson) {
            html += '<p class="text-muted">' + strings.noLessonContent + '</p>';
            return html;
        }

        var lesson = section.lesson;
        html += '<h1 class="ca-content-title">' + escapeHtml(lesson.title || section.name) + '</h1>';

        if (lesson.summary) {
            html += '<p class="ca-content-lead">' + escapeHtml(lesson.summary) + '</p>';
        }

        if (lesson.content_html) {
            html += '<div class="ca-content-body">' + lesson.content_html + '</div>';
        } else {
            html += '<p class="text-muted">' + strings.noContentAvailable + '</p>';
        }

        return html;
    };

    const renderQuizContent = function(section) {
        var html = '';
        if (!section.quiz || !section.quiz.questions || section.quiz.questions.length === 0) {
            html += '<p class="text-muted">' + strings.noQuiz + '</p>';
            return html;
        }

            html += '<h1 class="ca-content-title">' + strings.quizCount.replace('{$a}', escapeHtml(section.name)) + '</h1>';

        section.quiz.questions.forEach(function(q, qi) {
            var correctIdx = (typeof q.correct_answer === 'number') ? q.correct_answer : -1;
            html += '<div class="ca-quiz-question" data-question-index="' + qi + '">';
            html += '<div class="ca-quiz-question-header">';
            html += '<span class="ca-quiz-number">Q' + (qi + 1) + '</span>';
            html += '<span class="ca-quiz-qtext">' + escapeHtml(q.question) + '</span>';
            html += '</div>';
            html += '<ul class="ca-quiz-options">';
            if (q.options && q.options.length > 0) {
                q.options.forEach(function(opt, oi) {
                    var isCorrect = (oi === correctIdx);
                    html += '<li class="ca-quiz-option' + (isCorrect ? ' is-correct' : '') + '">';
                    html += '<span class="ca-quiz-opt-marker">' + String.fromCharCode(65 + oi) + '.</span>';
                    html += '<span class="ca-quiz-opt-text">' + escapeHtml(opt) + '</span>';
                    if (isCorrect) {
                        html += '<span class="ca-quiz-correct-badge"><i class="fa fa-check"></i> ' + strings.correct + '</span>';
                    }
                    html += '</li>';
                });
            }
            html += '</ul>';
            if (q.explanation) {
                html += '<div class="ca-quiz-explanation"><i class="fa fa-info-circle"></i> ' + escapeHtml(q.explanation) + '</div>';
            }
            html += '</div>';
        });

        return html;
    };

    const renderAssignmentContent = function(section) {
        var html = '';
        if (!section.assignment) {
            html += '<p class="text-muted">' + strings.noAssignment + '</p>';
            return html;
        }

        var a = section.assignment;
        html += '<h1 class="ca-content-title">' + strings.assignmentLabel + ': ' + escapeHtml(a.title || section.name) + '</h1>';

        if (a.description) {
            html += '<p class="ca-content-lead">' + escapeHtml(a.description) + '</p>';
        }

        if (a.instructions && a.instructions.length > 0) {
            html += '<div class="ca-info-box">';
            html += '<h3 class="ca-info-box-title"><i class="fa fa-lightbulb"></i> ' + strings.instructions + '</h3>';
            html += '<ol class="ca-assignment-instructions">';
            a.instructions.forEach(function(inst) {
                html += '<li>' + escapeHtml(inst) + '</li>';
            });
            html += '</ol>';
            html += '</div>';
        }

        if (a.word_count) {
            html += '<p class="ca-meta"><i class="fa fa-file-text-o"></i> ' + strings.expectedLength + ' <strong>' + a.word_count + ' ' + strings.words + '</strong></p>';
        }

        return html;
    };

    /* ── Chat Panel ── */
    const renderChat = function() {
        var messagesHtml = '';
        state.chatMessages.forEach(function(msg) {
            if (msg.type === 'ai') {
                messagesHtml += '<div class="ca-chat-message ca-chat-message--ai">';
                messagesHtml += '<div class="ca-chat-avatar ca-chat-avatar--ai"><i class="fa fa-robot"></i></div>';
                if (msg.isTyping) {
                    messagesHtml += '<div class="ca-chat-bubble ca-chat-bubble--ai"><div class="ca-typing-indicator"><span></span><span></span><span></span></div></div>';
                } else if (msg.isPlan) {
                    // Plan message — show plan text + Confirm/Cancel buttons.
                    messagesHtml += '<div class="ca-chat-bubble ca-chat-bubble--ai">';
                    messagesHtml += escapeHtml(msg.text);
                    messagesHtml += '<div class="ca-plan-actions">';
                    messagesHtml += '<button class="ca-plan-confirm btn btn-sm btn-success">Yes, do it</button>';
                    messagesHtml += '<button class="ca-plan-cancel btn btn-sm btn-secondary">Cancel</button>';
                    messagesHtml += '</div>';
                    messagesHtml += '</div>';
                } else {
                    messagesHtml += '<div class="ca-chat-bubble ca-chat-bubble--ai">' + escapeHtml(msg.text) + '</div>';
                }
                messagesHtml += '</div>';
            } else {
                messagesHtml += '<div class="ca-chat-message ca-chat-message--user">';
                messagesHtml += '<div class="ca-chat-avatar ca-chat-avatar--user"><i class="fa fa-user"></i></div>';
                messagesHtml += '<div class="ca-chat-bubble ca-chat-bubble--user">' + escapeHtml(msg.text) + '</div>';
                messagesHtml += '</div>';
            }
        });
        $('#ca-chat-messages').html(messagesHtml);
        scrollChatToBottom();

        var quickHtml = '';
        state.quickActions.forEach(function(action) {
            quickHtml += '<button class="ca-quick-chip">' + escapeHtml(action) + '</button>';
        });
        $('#ca-chat-quickactions').html(quickHtml);
    };

    const scrollChatToBottom = function() {
        var container = document.getElementById('ca-chat-messages');
        if (container) { container.scrollTop = container.scrollHeight; }
    };

    const applyDelta = function(courseData, delta) {
        var sections = courseData.sections || [];
        var op = delta.op;
        var sectionIdx = delta.section_index;
        var targetType = delta.target_type;

        if (targetType === 'section') {
            if (op === 'add') {
                sections.push(delta.data);
                state.expandedSections.add(sections.length - 1);
                state.currentSectionIndex = sections.length - 1;
            } else if (op === 'delete') {
                sections.splice(sectionIdx, 1);
                state.currentSectionIndex = Math.max(0, sectionIdx - 1);
            } else {
                sections[sectionIdx] = delta.data;
            }
        } else {
            var section = sections[sectionIdx];
            if (!section) { return courseData; }

            if (targetType === 'lesson') {
                section.lesson = delta.data;
                state.currentSectionIndex = sectionIdx;
                state.currentTab = 'content';
            } else if (targetType === 'quiz') {
                section.quiz = delta.data;
                state.currentSectionIndex = sectionIdx;
                state.currentTab = 'quiz';
            } else if (targetType === 'question') {
                if (!section.quiz) { section.quiz = { name: 'Quiz', questions: [] }; }
                if (op === 'add') {
                    section.quiz.questions.push(delta.data);
                } else if (op === 'delete') {
                    section.quiz.questions.splice(delta.question_index, 1);
                } else {
                    section.quiz.questions[delta.question_index] = delta.data;
                }
                state.currentSectionIndex = sectionIdx;
                state.currentTab = 'quiz';
            } else if (targetType === 'assignment') {
                section.assignment = delta.data;
                state.currentSectionIndex = sectionIdx;
                state.currentTab = 'assignment';
            }
        }

        courseData.sections = sections;
        return courseData;
    };

    const setChatButtonMode = function(mode) {
        var $btn = $('#ca-chat-send');
        if (mode === 'stop') {
            $btn.find('.ca-icon-send').hide();
            $btn.find('.ca-icon-stop').show();
            $btn.addClass('is-loading').attr('title', 'Cancel');
        } else {
            $btn.find('.ca-icon-stop').hide();
            $btn.find('.ca-icon-send').show();
            $btn.removeClass('is-loading').attr('title', 'Send');
        }
    };

    const sendChatMessage = function(text) {
        if (!text.trim()) { return; }
        if (chatLoading) { return; }
        chatLoading = true;
        setChatButtonMode('stop');

        state.chatMessages.push({ type: 'user', text: text.trim() });
        renderChat();

        state.chatMessages.push({ type: 'ai', text: '...', isTyping: true });
        renderChat();

        var payload = {
            user_prompt: text.trim()
            // No context_hint — AI determines target from NLP.
            // No course_data — server reads from session.
        };

        chatXhr = $.ajax({
            url: config.wwwroot + '/local/courseagent/ajax.php?action=ai_assist&sesskey=' + config.sesskey,
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                chatLoading = false;
                chatXhr = null;
                setChatButtonMode('send');
                state.chatMessages = state.chatMessages.filter(function(m) { return !m.isTyping; });

                if (response.used_provider) {
                    console.log('[CA CHAT] used provider: ' + response.used_provider
                        + ', model: ' + (response.used_model || '(default)'));
                }
                if (response.fallback_log && response.fallback_log.length > 0) {
                    console.warn('[CA FALLBACK LOG] ' + response.fallback_log.length
                        + ' attempt(s) before success:');
                    console.table(response.fallback_log);
                } else if (response.success) {
                    console.log('[CA FALLBACK LOG] First attempt succeeded — no fallbacks needed.');
                }

                if (response.success) {
                    var rtype = response.response_type || 'delta';

                    if (rtype === 'question') {
                        // AI asking clarifying question — no course changes.
                        state.chatMessages.push({ type: 'ai', text: response.message || '' });

                    } else if (rtype === 'plan') {
                        // AI showing plan — display with Confirm/Cancel buttons.
                        state.chatMessages.push({
                            type: 'ai',
                            text: response.message || '',
                            isPlan: true,
                            planSummary: response.plan_summary || ''
                        });

                    } else {
                        // Delta — apply changes.
                        if (response.delta) {
                            config.courseData = applyDelta(config.courseData, response.delta);
                        } else if (response.data) {
                            config.courseData = response.data;
                        }
                        var dataEl = document.getElementById('ca-preview-data');
                        if (dataEl) { dataEl.textContent = JSON.stringify(config.courseData); }
                        renderAll();
                        state.chatMessages.push({ type: 'ai', text: response.message || '' });
                    }

                    // Track history locally.
                    state.chatHistory.push({ role: 'user',      content: text.trim() });
                    state.chatHistory.push({ role: 'assistant', content: response.message || '' });
                } else {
                    state.chatMessages.push({
                        type: 'ai',
                        text: response.error || 'Failed to update. Please try again.'
                    });
                }
                renderChat();
            },
            error: function(xhr) {
                chatLoading = false;
                chatXhr = null;
                setChatButtonMode('send');
                if (xhr.statusText === 'abort') { return; }
                state.chatMessages = state.chatMessages.filter(function(m) { return !m.isTyping; });
                var errMsg = 'Error updating. Please try again.';
                var parsed = null;
                try { parsed = JSON.parse(xhr.responseText); } catch (e) {
                    console.error('[CA CHAT] Failed to parse error response — likely PHP fatal (OOM/timeout). Check PHP error_log.');
                }
                if (parsed) {
                    errMsg = parsed.error || errMsg;
                    if (parsed.fallback_log && parsed.fallback_log.length > 0) {
                        console.error('[CA FALLBACK LOG] ' + parsed.fallback_log.length
                            + ' failed attempt(s) before giving up:');
                        console.table(parsed.fallback_log);
                    } else {
                        console.error('[CA FALLBACK LOG] No fallback_log in error response (PHP crashed before fallback loop completed).');
                    }
                }
                state.chatMessages.push({ type: 'ai', text: errMsg });
                renderChat();
            }
        });
    };

    const clearChat = function() {
        $.ajax({
            url: config.wwwroot + '/local/courseagent/ajax.php?action=clear_chat&sesskey=' + config.sesskey,
            type: 'POST',
            data: JSON.stringify({}),
            contentType: 'application/json',
            dataType: 'json',
            success: function() {
                state.chatMessages = [{ type: 'ai', text: strings.aiInitialMsg }];
                state.chatHistory  = [];
                renderChat();
            }
        });
    };

    // Current question index when viewing quiz.
    var currentQuestionIndex = 0;

    /* ── Event Listeners ── */
    const setupEventListeners = function() {
        $('#btn-publish').on('click', publishCourse);

        // Sidebar toggles & item clicks (delegated).
        $('#ca-sidebar-tree')
            .off('click.courseagent')
            .on('click.courseagent', '.ca-tree-section-header', function(e) {
                e.preventDefault();
                var index = parseInt($(this).data('index'), 10);
                if (state.expandedSections.has(index)) {
                    state.expandedSections.delete(index);
                } else {
                    state.expandedSections.add(index);
                }
                renderSidebar();
            })
            .on('click.courseagent', '.ca-tree-item', function(e) {
                e.preventDefault();
                var sectionIdx = parseInt($(this).data('section'), 10);
                var tab = $(this).data('tab');
                state.currentSectionIndex = sectionIdx;
                state.currentTab = tab;
                renderSidebar();
                renderMain();
            });

        // Main tab clicks (delegated).
        $('#ca-main')
            .off('click.courseagent')
            .on('click.courseagent', '.ca-main-tab', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                state.currentTab = tab;
                state.currentQuestionIndex = 0; // Reset question selection.
                renderMain();
            })
            .on('click.courseagent', '.ca-quiz-question', function(e) {
                e.preventDefault();
                var qidx = parseInt($(this).data('questionIndex'), 10);
                state.currentQuestionIndex = qidx;
                // Update visual selection.
                $('.ca-quiz-question').removeClass('is-selected');
                $(this).addClass('is-selected');
            });

        // Chat send / stop.
        $('#ca-chat-send').on('click', function() {
            if (chatLoading) {
                if (chatXhr) { chatXhr.abort(); }
                chatLoading = false;
                chatXhr = null;
                setChatButtonMode('send');
                state.chatMessages = state.chatMessages.filter(function(m) { return !m.isTyping; });
                state.chatMessages.push({ type: 'ai', text: 'Request cancelled.' });
                renderChat();
                return;
            }
            var $input = $('#ca-chat-input');
            var val = $input.val();
            $input.val('');
            $input[0].style.height = 'auto';
            sendChatMessage(val);
        });

        $('#ca-chat-input').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey && !chatLoading) {
                e.preventDefault();
                var $input = $(this);
                var val = $input.val();
                $input.val('');
                $input[0].style.height = 'auto';
                sendChatMessage(val);
            }
        });

        $('#ca-chat-input').on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });

        // Clear chat button.
        $('#ca-chat-clear').on('click', clearChat);

        // Plan confirm/cancel (delegated — buttons rendered dynamically).
        $('#ca-chat-messages')
            .off('click.courseagent-plan')
            .on('click.courseagent-plan', '.ca-plan-confirm', function() {
                // Mark plan message as resolved so buttons disappear.
                var lastPlan = state.chatMessages.filter(function(m) { return m.isPlan; });
                if (lastPlan.length) { lastPlan[lastPlan.length - 1].isPlan = false; }
                renderChat();
                sendChatMessage('yes, proceed with the plan');
            })
            .on('click.courseagent-plan', '.ca-plan-cancel', function() {
                var lastPlan = state.chatMessages.filter(function(m) { return m.isPlan; });
                if (lastPlan.length) { lastPlan[lastPlan.length - 1].isPlan = false; }
                state.chatMessages.push({ type: 'ai', text: 'Cancelled. Let me know if you want to make a different change.' });
                renderChat();
            });

        // Quick action chips.
        $('#ca-chat-quickactions')
            .off('click.courseagent')
            .on('click.courseagent', '.ca-quick-chip', function() {
                sendChatMessage($(this).text());
            });
    };

    /* ── Publish ── */
    const publishCourse = function() {
        if (!config.courseData) {
            Notification.addNotification({
                message: strings.noCourseData,
                type:    'error'
            });
            return;
        }

        $('#btn-publish').prop('disabled', true).html('<i class="fa fa-spinner fa-spin fa-fw"></i> ' + strings.publishing);

        $.ajax({
            url:         config.wwwroot + '/local/courseagent/ajax.php?action=publish&sesskey=' + config.sesskey,
            type:        'POST',
            data:        JSON.stringify(config.courseData),
            contentType: 'application/json',
            dataType:    'json',
            success:     function(response) {
                $('#btn-publish').prop('disabled', false).html('<i class="fa fa-upload fa-fw"></i> ' + strings.publishToMoodle);
                if (response.success) {
                    Notification.addNotification({ message: strings.coursePublished, type: 'success' });
                    setTimeout(function() { window.location.href = response.course_url; }, 1500);
                } else {
                    Notification.addNotification({
                        message: response.error || strings.failedPublish,
                        type:    'error'
                    });
                }
            },
            error: function(xhr) {
                $('#btn-publish').prop('disabled', false).html('<i class="fa fa-upload fa-fw"></i> ' + strings.publishToMoodle);
                let msg = strings.errorPublishing;
                try { msg = JSON.parse(xhr.responseText).error || msg; } catch (e) {}
                Notification.addNotification({ message: msg, type: 'error' });
            }
        });
    };

    const escapeHtml = function(text) {
        if (typeof text !== 'string') { return String(text || ''); }
        const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    };

    return { init: init };
});
