<?php

// This file is part of Course Agent - AI Course Creator Plugin for Moodle
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings for local_courseagent.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course Agent - AI Course Creator';
$string['nav_createcourse'] = 'Create Course with AI';
$string['courseagent:createcourse'] = 'Create AI generated courses';
$string['courseagent:viewmycourses'] = 'View my AI generated courses';

// My courses page button.
$string['createwithai'] = 'Create Course with AI';

// Page strings.
$string['create_course'] = 'Create AI Course';
$string['my_courses'] = 'My AI Courses';
$string['course_history'] = 'Course Generation History';
$string['coursetopic'] = 'Course Topic';
$string['coursetopic_placeholder'] = 'Describe the main topics, learning objectives, or paste an existing syllabus outline...';
$string['coursetopic_help'] = 'Describe the topic the AI should build the course around.';

// Index page strings.
$string['configure_settings'] = 'Configure your AI-generated curriculum settings.';
$string['course_title'] = 'Course Title';
$string['optional_override'] = '(Optional override)';
$string['course_title_placeholder'] = 'Leave blank to let the AI choose a title';
$string['upload_content'] = 'Upload Your Content';
$string['pro_badge'] = 'PRO';
$string['upload_content_desc'] = 'Upload a document and let the AI build the course directly from your material.';
$string['click_to_upload'] = 'Click to upload';
$string['or_drag_drop'] = 'or drag & drop';
$string['accepted_file_types'] = 'TXT, PDF, DOCX, PPTX, ODT, RTF, MD, CSV, EPUB &mdash; max&nbsp;50&nbsp;MB';
$string['pro_feature'] = 'Pro Feature';
$string['pro_feature_desc'] = 'Document upload is available in the <strong>Pro version</strong>.<br>Upgrade to unlock this and other advanced features.';
$string['difficulty_level'] = 'Difficulty Level';
$string['level_beginner'] = 'Beginner';
$string['level_intermediate'] = 'Intermediate';
$string['level_advanced'] = 'Advanced';
$string['num_sections'] = 'Number of Sections';
$string['sections_range'] = 'Between 2 and {$a} sections.';
$string['included_components'] = 'Included Components';
$string['include_quizzes'] = 'Include Quizzes';
$string['include_quizzes_desc'] = 'Generate MCQs at the end of each section';
$string['include_assignments_label'] = 'Include Assignments';
$string['include_assignments_desc_ui'] = 'Create practical tasks for learners';
$string['use_emojis_label'] = 'Use Emojis';
$string['use_emojis_desc_ui'] = 'Add relevant emojis to make content more engaging';
$string['include_svg_diagrams'] = 'Include SVG Diagrams';
$string['include_svg_desc_ui'] = 'Generate simple SVG illustrations where helpful';
$string['ai_provider'] = 'AI Provider';
$string['model_selection'] = 'Model Selection';
$string['generate_course_btn'] = 'Generate Course';
$string['how_it_works'] = 'How it works';
$string['how_it_works_desc'] = 'CourseAgent uses advanced AI to instantly draft a comprehensive Moodle course structure based on your topic and parameters.';
$string['structuring'] = 'Structuring';
$string['structuring_desc'] = 'We analyze your topic and break it down into logical modules and lessons.';
$string['content_generation'] = 'Content Generation';
$string['content_generation_desc'] = 'Detailed lesson content, readings, and summaries are drafted for each section.';
$string['review_refine'] = 'Review &amp; Refine';
$string['review_refine_desc'] = 'You can edit everything before finalizing and publishing to Moodle.';
$string['pro_tip'] = 'Pro Tip:';
$string['pro_tip_desc'] = 'Be as specific as possible in the Topic field. Pasting a syllabus outline yields the best results.';
$string['generating_course'] = 'Generating Your Course...';
$string['generating_course_desc'] = "This may take a few seconds. We're crafting high-quality content for you.";
$string['step_outline'] = 'Creating course outline';
$string['step_lessons'] = 'Generating lessons';
$string['step_extras'] = 'Adding quizzes and assignments';
$string['cancel'] = 'Cancel';

// My courses page strings.
$string['no_courses_yet'] = 'You have not generated any courses yet.';
$string['date_created'] = 'Date Created';
$string['course_title_col'] = 'Course Title';
$string['status_col'] = 'Status';
$string['untitled_course'] = 'Untitled Course';
$string['view_course'] = 'View Course';

// Providers page strings.
$string['error_creating_provider'] = 'Error creating provider: {$a}';
$string['more_models'] = 'more';
$string['disable_provider'] = 'Disable provider';
$string['enable_provider'] = 'Enable provider';

// AJAX endpoint strings.
$string['error_no_topic'] = 'Please enter a course topic or upload a document.';
$string['progress_preparing'] = 'Preparing course outline...';
$string['progress_finalizing'] = 'Finalizing course...';
$string['progress_complete'] = 'Course generated successfully!';
$string['provider_not_found'] = 'Provider not found';
$string['upload_err_ini_size'] = 'File exceeds server upload_max_filesize.';
$string['upload_err_form_size'] = 'File exceeds form MAX_FILE_SIZE.';
$string['upload_err_partial'] = 'File was only partially uploaded.';
$string['upload_err_no_file'] = 'No file was uploaded.';
$string['upload_err_no_tmp_dir'] = 'Missing temporary folder.';
$string['upload_err_cant_write'] = 'Failed to write file to disk.';
$string['upload_err_extension'] = 'A PHP extension stopped the upload.';
$string['upload_err_generic'] = 'File upload failed (code {$a})';

// Provider form strings.
$string['baseurl_placeholder'] = 'https://api.openai.com/v1';
$string['endpoint_placeholder'] = 'chat/completions';
$string['no_models_yet'] = 'No models added yet. Add at least one model.';
$string['default_model'] = 'Default model';
$string['model_label'] = 'Model {$a}';
$string['edit_title'] = 'Edit';
$string['move_up'] = 'Move up';
$string['move_down'] = 'Move down';
$string['save'] = 'Save';
$string['connection_successful'] = 'Connection successful';
$string['connection_failed'] = 'Connection failed';
$string['check_console_debug'] = 'Check browser console for detailed debug info';
$string['request_error'] = 'Request error';

// Preview page extra strings.
$string['options'] = 'Options';
$string['send'] = 'Send';

// JS-accessible strings (passed via config from PHP).
$string['js:auto_select'] = 'Auto-select';
$string['js:auto_select_first'] = 'Auto-select (first available)';
$string['js:unsupported_filetype'] = 'Unsupported file type ".{$a}". Please upload: TXT, PDF, DOCX, PPTX, ODT, RTF, MD, CSV, or EPUB.';
$string['js:file_too_large'] = 'File is too large ({$a}). Maximum allowed size is 50 MB.';
$string['js:could_not_extract'] = 'Could not extract text from the file.';
$string['js:upload_failed'] = 'Failed to upload file for extraction.';
$string['js:extracting_text'] = 'Extracting text...';
$string['js:characters_extracted'] = '{$a} characters extracted';
$string['js:please_enter_topic'] = 'Please enter a course topic.';
$string['js:sections_range_error'] = 'Number of sections must be between 2 and {$a}';
$string['js:failed_generate'] = 'Failed to generate course';
$string['js:error_generating'] = 'An error occurred while generating the course';
$string['js:generation_cancelled'] = 'Course generation cancelled.';
$string['js:adding_quizzes_assignments'] = 'Adding quizzes and assignments';
$string['js:adding_quizzes'] = 'Adding quizzes';
$string['js:adding_assignments'] = 'Adding assignments';
$string['js:ai_initial_msg'] = "I've drafted the initial content for the course. Review the sections in the sidebar and let me know if you'd like any changes.";
$string['js:quickaction_1'] = 'Add 10 more questions to section 2 quiz';
$string['js:quickaction_2'] = 'Remove assignment from section 3';
$string['js:quickaction_3'] = 'Make section 1 more advanced';
$string['js:section_label'] = 'Section {$a}';
$string['js:lesson_label'] = 'Lesson';
$string['js:quiz_count'] = 'Quiz ({$a})';
$string['js:assignment_label'] = 'Assignment';
$string['js:content_tab'] = 'Content';
$string['js:quiz_questions_tab'] = 'Quiz Questions';
$string['js:assignment_details_tab'] = 'Assignment Details';
$string['js:ai_generated'] = 'AI Generated';
$string['js:no_lesson_content'] = 'No lesson content for this section.';
$string['js:no_content_available'] = 'No content available.';
$string['js:no_quiz'] = 'No quiz for this section.';
$string['js:no_assignment'] = 'No assignment for this section.';
$string['js:correct'] = 'Correct';
$string['js:instructions'] = 'Instructions';
$string['js:expected_length'] = 'Expected length:';
$string['js:words'] = 'words';
$string['js:ai_chat_placeholder'] = 'I\'ve noted your request: "{$a}". I\'ll update the course content accordingly. (This is a placeholder — connect to the AI agent backend for real modifications.)';
$string['js:no_course_data'] = 'No course data found. Please generate a course first.';
$string['js:publishing'] = 'Publishing...';
$string['js:course_published'] = 'Course published successfully!';
$string['js:failed_publish'] = 'Failed to publish course';
$string['js:error_publishing'] = 'An error occurred while publishing the course';

// Plan approval flow strings.
$string['course_plan_title'] = 'Your Course Plan';
$string['course_plan_subtitle'] = 'Review the AI-designed structure before full generation begins.';
$string['approve_generate_btn'] = 'Approve &amp; Generate Course';
$string['edit_topic_btn'] = 'Edit Topic';
$string['js:planning_course'] = 'Planning your course structure...';
$string['js:plan_failed'] = 'Failed to plan course structure';
$string['js:plan_error'] = 'An error occurred while planning the course';
$string['js:generating_from_plan'] = 'Building Your Course from Plan...';
$string['js:generating_from_plan_desc'] = 'Your approved plan is now being built into a full course.';
$string['js:approve_generate'] = 'Approve &amp; Generate Course';
$string['js:h5p_single_choice'] = 'H5P: Single Choice';
$string['js:h5p_summary'] = 'H5P: Summary';
$string['js:h5p_drag_words'] = 'H5P: Drag Words';
$string['js:h5p_multiple_choice'] = 'H5P: Multiple Choice';
$string['js:h5p_true_false'] = 'H5P: True/False';
$string['js:h5p_fill_in_blanks'] = 'H5P: Fill in Blanks';
$string['js:h5p_quiz_set'] = 'H5P: Quiz (Question Set)';
$string['js:h5p_dialog_cards'] = 'H5P: Dialog Cards';
$string['js:h5p_essay'] = 'H5P: Essay';
$string['js:h5p_mark_the_words'] = 'H5P: Mark the Words';
$string['js:h5p_sort_paragraphs'] = 'H5P: Sort Paragraphs';
$string['js:h5p_crossword'] = 'H5P: Crossword';
$string['js:h5p_find_the_words'] = 'H5P: Find the Words';
$string['js:h5p_accordion'] = 'H5P: Accordion';
$string['js:h5p_personality_quiz'] = 'H5P: Personality Quiz';
$string['js:h5p_chart'] = 'H5P: Chart';
$string['js:h5p_timeline'] = 'H5P: Timeline';

// License settings strings.
$string['saas_heading'] = 'CourseAgent License';
$string['saas_heading_desc'] = 'Enter your license key to unlock paid advanced features. Leave blank to use local AI providers only.';
$string['saas_api_key'] = 'License Key';
$string['saas_api_key_desc'] = 'Your CourseAgent license key, received when activating your plan.';

// H5P activity toggle on course creation form.
$string['include_h5p'] = 'Generate H5P Activities';
$string['include_h5p_desc'] = 'Generate interactive H5P activities for each section (requires a license key)';

// SaaS error messages (user-visible).
$string['h5p_quota_exceeded'] = 'Monthly H5P generation limit reached. Upgrade your plan to continue.';
$string['h5p_paid_feature'] = 'H5P activity generation requires a paid CourseAgent plan. Contact your administrator to upgrade.';
$string['h5p_rate_limited'] = 'Too many requests to the H5P service. Please try again shortly.';
$string['h5p_service_unavailable'] = 'H5P generation service is temporarily unavailable. The course was published without H5P activities.';

// Settings strings.
$string['settings'] = 'Course Agent Settings';
$string['generation_settings'] = 'Course Generation Settings';
$string['max_sections'] = 'Maximum Sections';
$string['max_sections_desc'] = 'Maximum number of course sections to generate.';
$string['max_quiz_questions'] = 'Maximum Quiz Questions';
$string['max_quiz_questions_desc'] = 'Maximum number of quiz questions per section.';
$string['enable_assignments'] = 'Enable Assignment Generation';
$string['enable_assignments_desc'] = 'When enabled, AI will generate assignments for each section.';
$string['use_emojis'] = 'Use Emojis';
$string['use_emojis_desc'] = 'Add relevant emojis throughout the content to make it more engaging.';
$string['use_svg'] = 'Include SVG Diagrams';
$string['use_svg_desc'] = 'Generate simple SVG illustrations and diagrams where helpful for explanations.';

// Provider management strings.
$string['provider_management'] = 'AI Provider Management';
$string['provider_manage_link'] = 'Manage AI Providers';
$string['provider_add'] = 'Add New Provider';
$string['provider_add_heading'] = 'Add Provider';
$string['preset_quicksetup'] = 'Quick Setup — choose a preset';
$string['preset_quicksetup_desc'] = 'Click a preset to auto-fill all connection fields. Then paste your API key and click Test Connection.';
$string['provider_edit'] = 'Edit Provider';
$string['provider_name'] = 'Provider Name';
$string['provider_name_desc'] = 'A short, recognisable label for this connection — for example <strong>OpenAI GPT-4o</strong> or <strong>Gemini 2.5 Flash</strong>. Shown in the provider list and model selector.';
$string['provider_name_help'] = 'A friendly name for this AI provider (e.g., "OpenAI GPT-4", "Google Gemini").';
$string['provider_apikey'] = 'API Key';
$string['provider_apikey_desc'] = 'The secret key issued by your AI provider. It is encrypted with AES-256 before being stored in the database — it is never saved in plain text. You can find your key in your provider\'s developer console.';
$string['provider_apikey_help'] = 'Your secret API key for authentication. This is encrypted with AES-256 before being saved and is never stored in plain text.';
$string['provider_apikey_note'] = 'Leave this field as-is to keep the existing API key. Only type a new value if you want to replace it.';
$string['provider_baseurl'] = 'Base URL';
$string['provider_baseurl_desc'] = 'The root URL of the API — without a trailing slash. Everything else is appended to this.<br>Examples:<br>&nbsp;• <code>https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-04-17:generateContent</code> (Gemini)<br>&nbsp;• <code>https://api.openai.com/v1</code> (OpenAI)';
$string['provider_baseurl_help'] = 'The root URL of the AI API. Do not include a trailing slash.';
$string['provider_baseurl_invalid'] = 'Please enter a valid URL starting with http:// or https://.';
$string['provider_endpoint'] = 'Chat Endpoint Path';
$string['provider_endpoint_desc'] = 'The path that is appended to the Base URL to reach the chat/completion endpoint.<br>Examples:<br>&nbsp;• Leave <strong>empty</strong> if the Base URL already points directly to the endpoint (Gemini)<br>&nbsp;• <code>chat/completions</code> for OpenAI-compatible APIs';
$string['provider_endpoint_help'] = 'The endpoint path appended to the base URL for chat/completion requests. Leave blank if the base URL is the full endpoint.';
$string['provider_api_format'] = 'API Format';
$string['provider_api_format_help'] = 'Select the request format this provider expects. Use <strong>OpenAI-compatible</strong> for OpenAI and any other OpenAI-style endpoint. Use <strong>Google Gemini</strong> for Google AI Studio or Vertex AI endpoints — these use a different authentication and request body format.';
$string['provider_api_format_openai'] = 'OpenAI-compatible';
$string['provider_api_format_gemini'] = 'Google Gemini (AI Studio / Vertex AI)';
$string['provider_models'] = 'Available Models';
$string['provider_models_desc'] = 'The model identifiers that this provider supports. The <strong>first model</strong> in the list is used as the default when generating courses. Add at least one model. Use the exact model ID from your provider\'s documentation.';
$string['provider_models_help'] = 'Add one or more model identifiers. The first model in the list is the default. Use exact IDs from the provider documentation, e.g. <code>gpt-4o</code>, <code>gemini-2.5-flash-preview-04-17</code>.';
$string['provider_model_add'] = 'Add Model';
$string['provider_model_placeholder'] = 'e.g. gpt-4o or gemini-2.5-flash-preview-04-17';
$string['provider_model_remove'] = 'Remove';
$string['provider_model_empty_error'] = 'Please enter a model ID before adding.';
$string['provider_model_duplicate_error'] = 'This model ID has already been added.';
$string['provider_isdefault'] = 'Set as Default Provider';
$string['provider_isdefault_desc'] = 'When checked, this provider is automatically used for all course generation tasks unless overridden per-request. Only one provider can be the default at a time — enabling this will unset any existing default.';
$string['provider_isdefault_help'] = 'Make this the default provider for course generation. Only one provider can be the default — setting this will remove the default flag from any other provider.';
$string['provider_enabled'] = 'Enabled';
$string['provider_enabled_desc'] = 'Enable or disable this provider. Disabled providers remain saved but cannot be used for course generation. Useful for temporarily switching between providers without deleting configuration.';
$string['provider_enabled_help'] = 'Only enabled providers can be used for course generation. Disable a provider to pause it without deleting its configuration.';
$string['provider_status'] = 'Status';
$string['provider_default'] = 'Default';
$string['provider_disabled'] = 'Disabled';
$string['provider_test'] = 'Test Connection';
$string['provider_test_connection'] = 'Test Connection';
$string['provider_test_loading'] = 'Testing connection...';
$string['provider_test_success'] = 'Connection successful!';
$string['provider_test_failed'] = 'Connection failed';
$string['provider_test_noapikey'] = 'Please enter an API key to test.';
$string['provider_test_nobaseurl'] = 'Please enter a Base URL to test.';
$string['provider_test_validating'] = 'Validating credentials...';
$string['provider_test_show_response'] = 'Show response body';
$string['provider_test_hide_response'] = 'Hide response body';
$string['provider_test_response_body'] = 'Response Body';
$string['provider_toggle'] = 'Toggle Enable/Disable';
$string['provider_set_default'] = 'Set as default provider';
$string['provider_autoselect'] = 'Auto-select (first available)';
$string['default_provider'] = 'Default Provider';
$string['default_provider_desc'] = 'Select the default AI provider for course generation.';

// Provider CRUD messages.
$string['provider_created'] = 'Provider created successfully.';
$string['provider_updated'] = 'Provider updated successfully.';
$string['provider_deleted'] = 'Provider deleted successfully.';
$string['provider_name_exists'] = 'A provider with this name already exists. Please choose a different name.';
$string['provider_delete_confirm'] = 'Are you sure you want to delete the provider "{$a}"? This action cannot be undone.';
$string['provider_no_providers'] = 'No AI providers configured yet.';
$string['provider_no_providers_help'] = 'Add an AI provider to enable course generation. You can add multiple providers (e.g. OpenAI and Gemini) and switch between them.';

// Error strings.
$string['error_nopermission'] = 'You do not have permission to use Course Agent.';
$string['error_invalid_action'] = 'Invalid action requested.';
$string['error_invalid_json'] = 'Invalid JSON data received.';
$string['error_course_creation_failed'] = 'Failed to create course.';
$string['error_no_provider'] = 'No AI provider configured. Please contact your administrator.';
$string['error_edit_params'] = 'Missing edit parameters. Please select an item to edit first.';
$string['error_no_preview_data'] = 'No course data found. Please generate a course first.';

// Privacy strings.
$string['privacy:metadata:courseagent_sessions'] = 'Stores information about AI-generated course sessions.';
$string['privacy:metadata:courseagent_sessions:userid'] = 'The ID of the user who generated the course.';
$string['privacy:metadata:courseagent_sessions:courseid'] = 'The ID of the created Moodle course.';
$string['privacy:metadata:courseagent_sessions:status'] = 'The status of the generation session.';
$string['privacy:metadata:courseagent_sessions:course_json'] = 'The JSON data containing the course structure.';
$string['privacy:metadata:courseagent_sessions:timecreated'] = 'The time when the session was created.';
$string['privacy:metadata:courseagent_sessions:timemodified'] = 'The time when the session was last modified.';
$string['privacy:metadata:courseagent_providers'] = 'Stores AI provider configurations.';
$string['privacy:metadata:courseagent_providers:apikey'] = 'Encrypted API key for the AI provider.';

// Preview page.
$string['preview_course'] = 'Preview Course';
$string['preview_description'] = 'Review your AI-generated course before publishing.';
$string['back_to_create'] = 'Back to Create';
$string['no_preview_data'] = 'No course data found. Please generate a course first.';
$string['publish_to_moodle'] = 'Publish to Moodle';

// Actions.
$string['actions'] = 'Actions';
$string['provider_models_list_label'] = 'Model ID';

// Preview page UI strings.
$string['draft_mode'] = 'Draft Mode';
$string['course_builder'] = 'Course Builder';
$string['ai_assistant_active'] = 'AI Assistant Active';
$string['agent_assistant'] = 'Agent Assistant';
$string['chat_placeholder'] = 'Ask the agent to modify content...';
$string['chat_disclaimer'] = 'Agent can make mistakes. Consider verifying changes.';
