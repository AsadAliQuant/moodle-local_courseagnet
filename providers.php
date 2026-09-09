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
 * AI Provider management page.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Commenting.InlineComment.IncorrectCommentSeparator
// phpcs:disable moodle.Commenting.InlineComment.InvalidEndChar
// phpcs:disable moodle.Commenting.InlineComment.NotCapital
// phpcs:disable Squiz.PHP.CommentedOutCode.Found
// phpcs:disable moodle.Files.LineLength.TooLong
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/form/provider_form.php');

use local_courseagent\provider;
use local_courseagent\form\provider_form;

// Require admin login.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Page setup.
$PAGE->set_url('/local/courseagent/providers.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('provider_management', 'local_courseagent'));
$PAGE->set_heading(get_string('pluginname', 'local_courseagent'));
$PAGE->set_pagelayout('admin');

// Parameters.
$action  = optional_param('action', '', PARAM_ALPHA);
$id      = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$editid  = optional_param('edit', 0, PARAM_INT);

// Action: Delete.
if ($action === 'delete' && $id && confirm_sesskey()) {
    $rec = provider::get($id);
    if ($rec) {
        if ($confirm) {
            provider::delete($id);
            redirect(
                new moodle_url('/local/courseagent/providers.php'),
                get_string('provider_deleted', 'local_courseagent'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(
                get_string('provider_delete_confirm', 'local_courseagent', $rec->name),
                new moodle_url('/local/courseagent/providers.php', [
                    'action'  => 'delete',
                    'id'      => $id,
                    'confirm' => 1,
                    'sesskey' => sesskey(),
                ]),
                new moodle_url('/local/courseagent/providers.php')
            );
            echo $OUTPUT->footer();
            exit;
        }
    }
}

// Action: Set default.
if ($action === 'setdefault' && $id && confirm_sesskey()) {
    provider::setDefault($id);
    redirect(
        new moodle_url('/local/courseagent/providers.php'),
        get_string('provider_set_default', 'local_courseagent'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Action: Toggle enabled.
if ($action === 'toggle' && $id && confirm_sesskey()) {
    $rec = provider::get($id);
    if ($rec) {
        provider::setEnabled($id, !$rec->enabled);
        redirect(new moodle_url('/local/courseagent/providers.php'));
    }
}

// Action: Test connection (AJAX).
if ($action === 'test' && $id) {
    header('Content-Type: application/json');
    try {
        require_sesskey();
        $result = provider::testConnection($id);
        echo json_encode([
            'success'     => $result->success,
            'message'     => $result->message,
            'httpcode'    => $result->httpcode,
            'ai_response' => $result->ai_response ?? null,
            'response'    => $result->response,
            'debug'       => $result->debug ?? null,
        ]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'debug' => ['exception' => $e->getMessage()]]);
    }
    exit;
}

// Add or edit form.
$isediting   = ($editid > 0);
$isadding    = ($action === 'add');
$form        = null;  // Initialize to avoid undefined variable warning.

// Check if form was submitted - Moodle forms send a _qf__<formclass> marker in POST.
// This must be detected BEFORE we decide whether to create the form object,
// because get_data() requires the form object to exist.
$qfmarker = '_qf__local_courseagent_form_provider_form';
$formsubmitted = optional_param($qfmarker, null, PARAM_RAW) !== null;

if ($isediting) {
    $rec = provider::get($editid);
    if ($rec) {
        $form = new provider_form(new moodle_url('/local/courseagent/providers.php', ['edit' => $editid]), ['provider' => $rec]);
        $form->set_data([
            'id'          => $rec->id,
            'name'        => $rec->name,
            'baseurl'     => $rec->baseurl,
            'endpoint'    => $rec->endpoint,
            'api_format'  => $rec->api_format ?? 'openai',
            'isdefault'   => $rec->isdefault,
            'enabled'     => $rec->enabled,
            'models_json' => $rec->models,
        ]);
    }
} elseif ($isadding || $formsubmitted) {
    // Create form for both "add" display AND form submission processing.
    $form = new provider_form(new moodle_url('/local/courseagent/providers.php'), ['provider' => null]);
}

if ($form) {
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/local/courseagent/providers.php'));
    }

    if ($data = $form->get_data()) {
        // Debug: Uncomment to see what data is received.
        // echo $OUTPUT->header(); echo '<pre>'; print_r($data); echo '</pre>'; echo $OUTPUT->footer(); exit;

        // Parse models from the hidden JSON field (populated by JS widget).
        $modelsraw = $data->models_json ?? '[]';
        $models    = json_decode($modelsraw, true);
        if (!is_array($models)) {
            $models = [];
        }
        // Sanitise: trim and remove blanks.
        $models = array_values(array_filter(array_map('trim', $models)));
        $data->models = $models;

        // If editing and API key is blank, keep the existing key.
        if (!empty($data->id) && empty(trim($data->apikey ?? ''))) {
            $existing         = provider::get($data->id);
            $data->apikey     = provider::decryptApikey($existing->apikey);
        }

        if (!empty($data->id)) {
            provider::update($data->id, $data);
            redirect(
                new moodle_url('/local/courseagent/providers.php'),
                get_string('provider_updated', 'local_courseagent'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            try {
                $newid = provider::create($data);
                // Debug: Uncomment the next line to see whether provider was created.
                // redirect(new moodle_url('/local/courseagent/providers.php'), "Created provider ID: " . $newid, null, \core\output\notification::NOTIFY_SUCCESS);
                redirect(
                    new moodle_url('/local/courseagent/providers.php'),
                    get_string('provider_created', 'local_courseagent'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } catch (\Exception $e) {
                // Show error if create failed.
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('error_creating_provider', 'local_courseagent', $e->getMessage()), 'notifyproblem');
                echo html_writer::div(
                    html_writer::link(
                        new moodle_url('/local/courseagent/providers.php'),
                        get_string('back', 'core')
                    ),
                    'mt-3'
                );
                echo $OUTPUT->footer();
                exit;
            }
        }
    }

    // Render form page.
    // Heading: "Add Provider" when adding, "Edit Provider: <name>" when editing.
    if ($isediting && !empty($rec)) {
        $pageheading = get_string('provider_edit', 'local_courseagent') . ': ' . format_string($rec->name);
    } else {
        $pageheading = get_string('provider_add_heading', 'local_courseagent');
    }

    echo $OUTPUT->header();

    // Breadcrumb-style back link.
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/courseagent/providers.php'),
            html_writer::tag('i', '', ['class' => 'fa fa-arrow-left mr-1']) .
            get_string('provider_management', 'local_courseagent')
        ),
        'mb-3'
    );

    echo $OUTPUT->heading($pageheading);

    // Preset buttons â€” only on the Add (not Edit) page.
    if ($isadding) {
        $presets = [
            'openrouter' => [
                'label'      => 'OpenRouter',
                'icon'       => 'https://openrouter.ai/favicon.ico',
                'name'       => 'OpenRouter',
                'baseurl'    => 'https://openrouter.ai',
                'endpoint'   => 'api/v1/chat/completions',
                'api_format' => 'openai',
                'models'     => [
                    'meta-llama/llama-3.3-70b-instruct:free',
                    'google/gemma-3-27b-it:free',
                    'deepseek/deepseek-r1-0528:free',
                    'microsoft/phi-4:free',
                ],
            ],
            'gemini' => [
                'label'      => 'Google Gemini',
                'icon'       => 'https://www.gstatic.com/lamda/images/gemini_sparkle_v002_d4735304ff6292a690345.svg',
                'name'       => 'Google Gemini',
                'baseurl'    => 'https://generativelanguage.googleapis.com',
                'endpoint'   => 'v1beta/models/{model}:generateContent',
                'api_format' => 'gemini',
                'models'     => [
                    'gemini-2.5-flash',
                    'gemini-2.5-flash-lite',
                    'gemini-3-flash-preview',
                ],
            ],
            'nvidia_nim' => [
                'label'      => 'NVIDIA NIM',
                'icon'       => 'https://www.nvidia.com/favicon.ico',
                'name'       => 'NVIDIA NIM',
                'baseurl'    => 'https://integrate.api.nvidia.com',
                'endpoint'   => 'v1/chat/completions',
                'api_format' => 'openai',
                'models'     => [
                    'meta/llama-3.1-8b-instruct',
                    'meta/llama-3.1-70b-instruct',
                    'z-ai/glm4.7',
                    'deepseek-ai/deepseek-v4-pro',
                ],
            ],
        ];

        $presetsjson = json_encode($presets);

        echo html_writer::start_div('courseagent-presets card mb-4 border-0 bg-light');
        echo html_writer::start_div('card-body py-3');
        echo html_writer::tag(
            'p',
            html_writer::tag('i', '', ['class' => 'fa fa-bolt mr-1']) .
            get_string('preset_quicksetup', 'local_courseagent'),
            ['class' => 'font-weight-semibold mb-2']
        );
        echo html_writer::tag(
            'p',
            get_string('preset_quicksetup_desc', 'local_courseagent'),
            ['class' => 'text-muted small mb-3']
        );

        $btnhtml = '';
        foreach ($presets as $key => $p) {
            $imghtml = html_writer::empty_tag('img', [
                'src'    => $p['icon'],
                'width'  => '18',
                'height' => '18',
                'class'  => 'mr-2',
                'alt'    => $p['label'],
            ]);
            $btnhtml .= html_writer::tag(
                'button',
                $imghtml . $p['label'],
                [
                    'type'         => 'button',
                    'class'        => 'btn btn-outline-primary btn-sm mr-2 mb-2 preset-btn',
                    'data-preset'  => $key,
                ]
            );
        }
        echo html_writer::div($btnhtml, 'd-flex flex-wrap');
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Inline JS: read preset data and fill form fields on button click.
        echo html_writer::tag('script', "
(function() {
    var presets = " . $presetsjson . ";

    document.querySelectorAll('.preset-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var key = this.getAttribute('data-preset');
            var p = presets[key];
            if (!p) { return; }

            // Fill text fields. Overwrite the name on every preset click, but
            // leave a name the admin typed themselves untouched (only replace it
            // if it's empty or still equals a previously-applied preset name).
            var nameEl = document.getElementById('id_name');
            if (nameEl) {
                var lastPreset = nameEl.getAttribute('data-preset-name') || '';
                if (!nameEl.value || nameEl.value === lastPreset) {
                    nameEl.value = p.name;
                    nameEl.setAttribute('data-preset-name', p.name);
                }
            }

            var baseurlEl = document.getElementById('id_baseurl');
            if (baseurlEl) { baseurlEl.value = p.baseurl; }

            var endpointEl = document.getElementById('id_endpoint');
            if (endpointEl) { endpointEl.value = p.endpoint; }

            // Fill API format select.
            var fmtEl = document.getElementById('id_api_format');
            if (fmtEl) { fmtEl.value = p.api_format; }

            // Populate the models widget via exposed global.
            if (window.caModelsWidget) {
                window.caModelsWidget.setModels(p.models);
            }

            // Highlight the active preset button.
            document.querySelectorAll('.preset-btn').forEach(function(b) {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
        });
    });
})();
        ");
    }

    $form->display();
    echo $OUTPUT->footer();
    exit;
}

// ------------------------------------------------------------------ //
// Provider list page                                                   //
// ------------------------------------------------------------------ //
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('provider_management', 'local_courseagent'));

// "Add New Provider" button.
$addurl = new moodle_url('/local/courseagent/providers.php', ['action' => 'add']);
echo html_writer::div(
    html_writer::link(
        $addurl->out(false),
        get_string('provider_add', 'local_courseagent'),
        ['class' => 'btn btn-primary']
    ),
    'mb-3'
);

$providers = provider::getAll();

if (empty($providers)) {
    echo $OUTPUT->notification(get_string('provider_no_providers', 'local_courseagent'), 'notifymessage');
    echo html_writer::tag('p', get_string('provider_no_providers_help', 'local_courseagent'));
} else {
    $table             = new html_table();
    $table->head       = [
        get_string('provider_name', 'local_courseagent'),
        get_string('provider_baseurl', 'local_courseagent'),
        get_string('provider_models', 'local_courseagent'),
        get_string('provider_status', 'local_courseagent'),
        get_string('actions', 'local_courseagent'),
    ];
    $table->attributes['class'] = 'generaltable table-striped';
    $table->id = 'provider-table';

    foreach ($providers as $p) {
        $models     = json_decode($p->models, true) ?: [];
        $modelcount = count($models);
        $modelshtml = '';
        if ($modelcount > 0) {
            // Show first model + count badge.
            $first     = htmlspecialchars($models[0], ENT_QUOTES);
            $modelshtml = html_writer::tag('code', $first, ['class' => 'small']);
            if ($modelcount > 1) {
                $modelshtml .= ' ' . html_writer::tag('span', '+' . ($modelcount - 1) . ' ' . get_string('more_models', 'local_courseagent'), ['class' => 'badge badge-light border text-muted']);
            }
        } else {
            $modelshtml = html_writer::tag('span', 'â€”', ['class' => 'text-muted']);
        }

        // Status badges.
        $status = '';
        if ($p->isdefault) {
            $status .= html_writer::tag(
                'span',
                html_writer::tag('i', '', ['class' => 'fa fa-star mr-1']) . get_string('provider_default', 'local_courseagent'),
                ['class' => 'badge badge-primary mr-1']
            );
        }
        $status .= $p->enabled
            ? html_writer::tag('span', get_string('provider_enabled', 'local_courseagent'), ['class' => 'badge badge-success'])
            : html_writer::tag('span', get_string('provider_disabled', 'local_courseagent'), ['class' => 'badge badge-secondary']);

        // Actions.
        $actions = [];

        // Edit.
        $actions[] = html_writer::link(
            new moodle_url('/local/courseagent/providers.php', ['edit' => $p->id]),
            $OUTPUT->pix_icon('i/edit', get_string('edit')),
            ['class' => 'action-icon', 'title' => get_string('edit')]
        );

        // Set as default (only shown if not already default and is enabled).
        if (!$p->isdefault && $p->enabled) {
            $actions[] = html_writer::link(
                new moodle_url('/local/courseagent/providers.php', ['action' => 'setdefault', 'id' => $p->id, 'sesskey' => sesskey()]),
                $OUTPUT->pix_icon('i/star', get_string('provider_set_default', 'local_courseagent')),
                ['class' => 'action-icon', 'title' => get_string('provider_set_default', 'local_courseagent')]
            );
        }

        // Toggle enable/disable.
        $toggleicon  = $p->enabled ? 'i/hide' : 'i/show';
        $toggletitle = $p->enabled ? get_string('disable_provider', 'local_courseagent') : get_string('enable_provider', 'local_courseagent');
        $actions[] = html_writer::link(
            new moodle_url('/local/courseagent/providers.php', ['action' => 'toggle', 'id' => $p->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon($toggleicon, $toggletitle),
            ['class' => 'action-icon', 'title' => $toggletitle]
        );

        // Delete.
        $actions[] = html_writer::link(
            new moodle_url('/local/courseagent/providers.php', ['action' => 'delete', 'id' => $p->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon('i/delete', get_string('delete')),
            ['class' => 'action-icon', 'title' => get_string('delete')]
        );

        $table->data[] = [
            format_string($p->name),
            html_writer::link(
                $p->baseurl,
                html_writer::tag('code', strlen($p->baseurl) > 45 ? substr($p->baseurl, 0, 45) . 'â€¦' : $p->baseurl, ['class' => 'small']),
                ['target' => '_blank', 'rel' => 'noopener', 'title' => $p->baseurl]
            ),
            $modelshtml,
            $status,
            html_writer::div(implode(' ', $actions), 'nowrap'),
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
