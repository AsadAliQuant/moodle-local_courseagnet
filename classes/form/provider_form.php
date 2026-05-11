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

namespace local_courseagent\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Provider add/edit form - Standard Moodle settings style.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_form extends \moodleform {
    /**
     * Define the form fields for provider add/edit.
     */
    public function definition() {
        global $CFG;

// phpcs:disable moodle.Files.LineLength.TooLong
        $mform = $this->_form;
        $provider = $this->_customdata['provider'] ?? null;
        $isediting = !empty($provider);

        // Provider Name.
        $mform->addElement('text', 'name', get_string('provider_name', 'local_courseagent'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'provider_name', 'local_courseagent');

        // API Key.
        $mform->addElement('password', 'apikey', get_string('provider_apikey', 'local_courseagent'), ['autocomplete' => 'new-password']);
        $mform->setType('apikey', PARAM_RAW_TRIMMED);
        if (!$isediting) {
            $mform->addRule('apikey', get_string('required'), 'required', null, 'client');
        }
        $mform->addHelpButton('apikey', 'provider_apikey', 'local_courseagent');

        // When editing, show note about leaving blank to keep existing key.
        if ($isediting && !empty($provider->apikey)) {
            $mform->addElement(
                'static',
                'apikey_note',
                '',
                get_string('provider_apikey_note', 'local_courseagent')
            );
        }

        // Base URL.
        $mform->addElement('text', 'baseurl', get_string('provider_baseurl', 'local_courseagent'), ['placeholder' => get_string('baseurl_placeholder', 'local_courseagent')]);
        $mform->setType('baseurl', PARAM_RAW_TRIMMED);
        $mform->addRule('baseurl', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('baseurl', 'provider_baseurl', 'local_courseagent');

        // Endpoint.
        $mform->addElement('text', 'endpoint', get_string('provider_endpoint', 'local_courseagent'), ['placeholder' => get_string('endpoint_placeholder', 'local_courseagent')]);
        $mform->setType('endpoint', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('endpoint', 'provider_endpoint', 'local_courseagent');

        // API Format.
        $mform->addElement('select', 'api_format', get_string('provider_api_format', 'local_courseagent'), [
            'openai' => get_string('provider_api_format_openai', 'local_courseagent'),
            'gemini' => get_string('provider_api_format_gemini', 'local_courseagent'),
        ]);
        $mform->setDefault('api_format', 'openai');
        $mform->setType('api_format', PARAM_ALPHA);
        $mform->addHelpButton('api_format', 'provider_api_format', 'local_courseagent');
        if ($isediting && !empty($provider->api_format)) {
            $mform->setDefault('api_format', $provider->api_format);
        }

        // Available Models.
        $mform->addElement('hidden', 'models_json', '[]');
        $mform->setType('models_json', PARAM_RAW);

        $mform->addElement(
            'static',
            'models_widget',
            get_string('provider_models', 'local_courseagent'),
            $this->render_models_widget($provider)
        );
        $mform->addHelpButton('models_widget', 'provider_models', 'local_courseagent');

        // Set as default.
        $mform->addElement('advcheckbox', 'isdefault', get_string('provider_isdefault', 'local_courseagent'));
        $mform->addHelpButton('isdefault', 'provider_isdefault', 'local_courseagent');
        if ($isediting) {
            $mform->setDefault('isdefault', (int)$provider->isdefault);
        }

        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string('provider_enabled', 'local_courseagent'));
        $mform->setDefault('enabled', 1);
        $mform->addHelpButton('enabled', 'provider_enabled', 'local_courseagent');
        if ($isediting) {
            $mform->setDefault('enabled', (int)$provider->enabled);
        }

        // Hidden: record ID (0 = new).
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Test connection button - placed before the action buttons.
        $mform->addElement(
            'static',
            'test_widget',
            '',
            $this->render_test_button()
        );

        // Action buttons.
        $this->add_action_buttons(true, $isediting ? get_string('savechanges', 'admin') : get_string('provider_add', 'local_courseagent'));
    }

    /**
     * Render the JS-powered model list widget HTML.
     *
     * @param \stdClass|null $provider Existing provider (for pre-population)
     * @return string HTML
     */
    private function render_models_widget(?\stdClass $provider): string {
        $existing = [];
        if ($provider && !empty($provider->models)) {
            $decoded = json_decode($provider->models, true);
            if (is_array($decoded)) {
                $existing = array_values(array_filter(array_map('trim', $decoded)));
            }
        }

        $existingjson = htmlspecialchars(json_encode($existing), ENT_QUOTES, 'UTF-8');
        $placeholder = get_string('provider_model_placeholder', 'local_courseagent');
        $addlabel = get_string('provider_model_add', 'local_courseagent');
        $removelabel = get_string('provider_model_remove', 'local_courseagent');
        $nomodelsmsg = get_string('no_models_yet', 'local_courseagent');
        $defaultmodel = get_string('default_model', 'local_courseagent');
        $modellabel = get_string('model_label', 'local_courseagent');
        $editlabel = get_string('edit_title', 'local_courseagent');
        $moveuplabel = get_string('move_up', 'local_courseagent');
        $movedownlabel = get_string('move_down', 'local_courseagent');
        $savelabel = get_string('save', 'local_courseagent');
        $emptymodelerr = get_string('provider_model_empty_error', 'local_courseagent');
        $dupmodelerr = get_string('provider_model_duplicate_error', 'local_courseagent');

        return <<<HTML
<div id="courseagent-models-widget" class="courseagent-models-widget" data-existing="{$existingjson}">

    <!-- Input row -->
    <div class="input-group mb-2" style="max-width:520px;">
        <input type="text"
               id="ca-model-input"
               class="form-control"
               placeholder="{$placeholder}"
               aria-label="Model ID input" />
        <div class="input-group-append">
            <button type="button" id="ca-model-add" class="btn btn-secondary">
                <i class="fa fa-plus mr-1"></i>{$addlabel}
            </button>
        </div>
    </div>

    <!-- Validation message -->
    <div id="ca-model-error" class="text-danger small mb-2" style="display:none;"></div>

    <!-- Model list -->
    <ul id="ca-model-list" class="list-group" style="max-width:520px;"></ul>

    <p id="ca-model-empty" class="text-muted small mt-2" style="display:none;">
        <i class="fa fa-exclamation-circle mr-1"></i>{$nomodelsmsg}
    </p>
</div>

<script>
(function() {
    var models = [];
    var existingRaw = document.getElementById('courseagent-models-widget').getAttribute('data-existing');
    try { models = JSON.parse(existingRaw) || []; } catch(e) { models = []; }

    var input    = document.getElementById('ca-model-input');
    var addBtn   = document.getElementById('ca-model-add');
    var list     = document.getElementById('ca-model-list');
    var errorDiv = document.getElementById('ca-model-error');
    var emptyMsg = document.getElementById('ca-model-empty');
    var jsonField;

    // Find the hidden models_json field inside the Moodle form.
    function getJsonField() {
        if (!jsonField) {
            jsonField = document.querySelector('input[name="models_json"]');
        }
        return jsonField;
    }

    function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.style.display = 'block';
    }

    function clearError() {
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
    }

    function sync() {
        var f = getJsonField();
        if (f) { f.value = JSON.stringify(models); }
        emptyMsg.style.display = models.length === 0 ? 'block' : 'none';
    }

    function renderList() {
        list.innerHTML = '';
        models.forEach(function(model, idx) {
            var li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3';

            // Order badge.
            var badge = document.createElement('span');
            badge.className = 'badge badge-primary badge-pill mr-2';
            badge.title = idx === 0 ? '{$defaultmodel}' : '{$modellabel}'.replace('{\$a}', (idx + 1));
            badge.textContent = idx === 0 ? '★' : (idx + 1);

            // Editable model name.
            var nameEl = document.createElement('span');
            nameEl.className = 'flex-grow-1 ca-model-name';
            nameEl.textContent = model;
            nameEl.style.fontFamily = 'monospace';
            nameEl.style.fontSize   = '0.9rem';

            // Action buttons.
            var actions = document.createElement('span');
            actions.className = 'ml-2 d-flex align-items-center';

            // Edit button.
            var editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'btn btn-sm btn-outline-secondary mr-1';
            editBtn.title = '{$editlabel}';
            editBtn.innerHTML = '<i class="fa fa-pencil"></i>';
            editBtn.addEventListener('click', function() { startEdit(li, nameEl, idx); });

            // Move up button (not shown for first item).
            var upBtn = document.createElement('button');
            upBtn.type = 'button';
            upBtn.className = 'btn btn-sm btn-outline-secondary mr-1';
            upBtn.title = '{$moveuplabel}';
            upBtn.innerHTML = '<i class="fa fa-arrow-up"></i>';
            upBtn.disabled = (idx === 0);
            upBtn.addEventListener('click', function() {
                var tmp = models[idx - 1];
                models[idx - 1] = models[idx];
                models[idx] = tmp;
                renderList(); sync();
            });

            // Move down button (not shown for last item).
            var downBtn = document.createElement('button');
            downBtn.type = 'button';
            downBtn.className = 'btn btn-sm btn-outline-secondary mr-1';
            downBtn.title = '{$movedownlabel}';
            downBtn.innerHTML = '<i class="fa fa-arrow-down"></i>';
            downBtn.disabled = (idx === models.length - 1);
            downBtn.addEventListener('click', function() {
                var tmp = models[idx + 1];
                models[idx + 1] = models[idx];
                models[idx] = tmp;
                renderList(); sync();
            });

            // Remove button.
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger';
            removeBtn.title = '{$removelabel}';
            removeBtn.innerHTML = '<i class="fa fa-trash"></i>';
            removeBtn.addEventListener('click', function() {
                models.splice(idx, 1);
                renderList(); sync();
            });

            actions.appendChild(editBtn);
            actions.appendChild(upBtn);
            actions.appendChild(downBtn);
            actions.appendChild(removeBtn);

            li.appendChild(badge);
            li.appendChild(nameEl);
            li.appendChild(actions);
            list.appendChild(li);
        });
        sync();
    }

    function startEdit(li, nameEl, idx) {
        // Replace the text span with an inline input.
        var editInput = document.createElement('input');
        editInput.type = 'text';
        editInput.className = 'form-control form-control-sm flex-grow-1 mr-2';
        editInput.value = models[idx];
        editInput.style.fontFamily = 'monospace';

        var saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'btn btn-sm btn-primary mr-1';
        saveBtn.innerHTML = '<i class="fa fa-check"></i> {$savelabel}';
        saveBtn.addEventListener('click', function() {
            var val = editInput.value.trim();
            if (!val) { editInput.classList.add('is-invalid'); return; }
            if (models.indexOf(val) !== -1 && models.indexOf(val) !== idx) {
                editInput.classList.add('is-invalid'); return;
            }
            models[idx] = val;
            renderList(); sync();
        });

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn btn-sm btn-secondary';
        cancelBtn.innerHTML = '<i class="fa fa-times"></i>';
        cancelBtn.addEventListener('click', function() { renderList(); });

        // Swap out nameEl and actions.
        li.innerHTML = '';
        li.className = 'list-group-item d-flex align-items-center py-2 px-3';
        li.appendChild(editInput);
        li.appendChild(saveBtn);
        li.appendChild(cancelBtn);
        editInput.focus();

        editInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { saveBtn.click(); }
            if (e.key === 'Escape') { cancelBtn.click(); }
        });
    }

    addBtn.addEventListener('click', function() {
        clearError();
        var val = input.value.trim();
        if (!val) { showError('{$emptymodelerr}'); input.focus(); return; }
        if (models.indexOf(val) !== -1) { showError('{$dupmodelerr}'); input.focus(); return; }
        models.push(val);
        input.value = '';
        renderList(); sync();
        input.focus();
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); addBtn.click(); }
    });

    // Initial render.
    renderList();
    sync();

    // Expose setter so external preset buttons can populate the list.
    window.caModelsWidget = {
        setModels: function(arr) {
            models = arr.slice();
            renderList();
            sync();
        }
    };
})();
</script>
HTML;
    }

    /**
     * Render the test connection button HTML.
     *
     * @return string HTML
     */
    private function render_test_button(): string {
        global $PAGE;

        $provider = $this->_customdata['provider'] ?? null;
        $isediting = !empty($provider);
        $providerid = $isediting ? (int)$provider->id : 0;

        $testlabel = get_string('provider_test_connection', 'local_courseagent');
        $loadinglabel = get_string('provider_test_loading', 'local_courseagent');
        $noapikeylabel = get_string('provider_test_noapikey', 'local_courseagent');
        $nobaseurllabel = get_string('provider_test_nobaseurl', 'local_courseagent');
        $showresponselabel = get_string('provider_test_show_response', 'local_courseagent');
        $hideresponselabel = get_string('provider_test_hide_response', 'local_courseagent');
        $responsebodylabel = get_string('provider_test_response_body', 'local_courseagent');
        $connectionsuccess = get_string('connection_successful', 'local_courseagent');
        $connectionfailed = get_string('connection_failed', 'local_courseagent');
        $checkconsole = get_string('check_console_debug', 'local_courseagent');
        $requesterror = get_string('request_error', 'local_courseagent');
        global $OUTPUT;
        $iconvalidhtml   = $OUTPUT->pix_icon('i/valid',   '', 'moodle', ['class' => 'mr-1']);
        $iconinvalidhtml = $OUTPUT->pix_icon('i/invalid', '', 'moodle', ['class' => 'mr-1']);

        // Inject JavaScript using Moodle's js_init_code() for plain JS execution.
        // NOTE: js_init_code() executes after DOM is ready, so no DOMContentLoaded needed.
        $jscode = <<<JS
console.log('[Course Agent] Test button script loaded (provider_form)');
var btn = document.getElementById('ca-test-connection-btn');
console.log('[Course Agent] Button element:', btn ? 'FOUND' : 'NOT FOUND');
if (!btn) {
    console.error('[Course Agent] CRITICAL: Button element not found!');
    return;
}

var status = document.getElementById('ca-test-status');
var result = document.getElementById('ca-test-result');
var providerId = document.getElementById('ca-provider-id').value;
console.log('[Course Agent] Provider ID:', providerId);

btn.addEventListener('click', function(e) {
    e.preventDefault();
    console.log('[Course Agent] ========================================');
    console.log('[Course Agent] ===== TEST BUTTON CLICKED =====');
    console.log('[Course Agent] ========================================');

    // Get form values.
    var baseurl = document.querySelector('input[name="baseurl"]');
    var endpoint = document.querySelector('input[name="endpoint"]');
    var apikey = document.querySelector('input[name="apikey"]');
    var modelsJson = document.querySelector('input[name="models_json"]');
    var apiformatEl = document.querySelector('select[name="api_format"]');

    var baseurlVal = baseurl ? baseurl.value.trim() : '';
    var endpointVal = endpoint ? endpoint.value.trim() : '';
    var apikeyVal = apikey ? apikey.value.trim() : '';
    var modelsRaw = modelsJson ? modelsJson.value : '[]';
    var apiformatVal = apiformatEl ? apiformatEl.value : 'openai';

    console.log('[Course Agent] --- FORM VALUES ---');
    console.log('[Course Agent] Base URL:', baseurlVal || '(empty)');
    console.log('[Course Agent] Endpoint:', endpointVal || '(empty)');
    console.log('[Course Agent] API Key:', apikeyVal ? '(provided, ' + apikeyVal.length + ' chars)' : '(empty)');
    console.log('[Course Agent] Models JSON:', modelsRaw);

    // Parse models and get first one.
    var models = [];
    try { models = JSON.parse(modelsRaw) || []; } catch(e) {
        console.error('[Course Agent] Failed to parse models JSON:', e);
        models = [];
    }
    var model = models.length > 0 ? models[0] : '';
    console.log('[Course Agent] Models array:', models);
    console.log('[Course Agent] Model to use:', model || '(none)');

    // Validate required fields.
    if (!baseurlVal) {
        console.warn('[Course Agent] Validation failed: no baseurl');
        status.textContent = '{$nobaseurllabel}';
        status.className = 'ml-2 text-danger';
        return;
    }

    // If editing existing provider and API key field is empty, use stored key.
    // Otherwise, require API key to be entered.
    var useStoredKey = (providerId > 0 && !apikeyVal);
    console.log('[Course Agent] Use stored key:', useStoredKey);

    if (!useStoredKey && !apikeyVal) {
        console.warn('[Course Agent] Validation failed: no apikey');
        status.textContent = '{$noapikeylabel}';
        status.className = 'ml-2 text-danger';
        return;
    }

    // Show loading state.
    btn.disabled = true;
    status.textContent = '{$loadinglabel}';
    status.className = 'ml-2 text-muted';
    result.style.display = 'none';

    // Build form data.
    var formData = new FormData();
    formData.append('sesskey', M.cfg.sesskey);

    if (useStoredKey) {
        console.log('[Course Agent] --- REQUEST MODE: Using stored provider credentials ---');
        console.log('[Course Agent] Provider ID:', providerId);
        formData.append('action', 'test_provider');
        formData.append('providerid', providerId);
    } else {
        console.log('[Course Agent] --- REQUEST MODE: Using form values (test_provider_raw) ---');
        formData.append('action', 'test_provider_raw');
        formData.append('baseurl', baseurlVal);
        formData.append('endpoint', endpointVal);
        formData.append('apikey', apikeyVal);
        formData.append('model', model);
        formData.append('api_format', apiformatVal);
    }

    // Make AJAX request.
    var ajaxUrl = 'ajax.php';
    console.log('[Course Agent] --- AJAX REQUEST ---');
    console.log('[Course Agent] URL:', ajaxUrl);
    console.log('[Course Agent] Method: POST');
    console.log('[Course Agent] FormData entries:');
    for (var pair of formData.entries()) {
        // Mask API key in logs
        if (pair[0] === 'apikey') {
            console.log('[Course Agent]   ' + pair[0] + ': ' + pair[1].substring(0, 8) + '...');
        } else {
            console.log('[Course Agent]   ' + pair[0] + ': ' + pair[1]);
        }
    }

    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(function(r) {
        console.log('[Course Agent] --- RAW HTTP RESPONSE ---');
        console.log('[Course Agent] HTTP Status:', r.status);
        console.log('[Course Agent] OK:', r.ok);
        console.log('[Course Agent] Status Text:', r.statusText);
        console.log('[Course Agent] Content-Type:', r.headers.get('content-type'));
        return r.text();
    })
    .then(function(rawText) {
        console.log('[Course Agent] --- RAW RESPONSE BODY ---');
        console.log('[Course Agent] Length:', rawText.length);
        console.log('[Course Agent] First 500 chars:');
        console.log(rawText.substring(0, 500));
        if (rawText.length > 500) {
            console.log('[Course Agent] Last 500 chars:');
            console.log(rawText.substring(rawText.length - 500));
        }
        var data;
        try {
            data = JSON.parse(rawText);
        } catch (parseErr) {
            console.error('[Course Agent] JSON.parse FAILED:', parseErr.message);
            btn.disabled = false;
            status.textContent = '';
            result.className = 'mt-2 alert alert-danger';
            result.innerHTML = '<strong>Parse error:</strong> Server response is not valid JSON. See console.';
            result.style.display = 'block';
            throw parseErr;
        }
        console.log('[Course Agent] ========================================');
        console.log('[Course Agent] --- PARSED JSON RESPONSE ---');
        console.log('[Course Agent] ========================================');
        console.log('[Course Agent] Object keys:', Object.keys(data));
        console.log('[Course Agent] Success:', data.success);
        console.log('[Course Agent] Message:', data.message);
        console.log('[Course Agent] HTTP Code:', data.httpcode);
        console.log('[Course Agent] AI Response:', data.ai_response || '(none)');

        if (data.debug) {
            console.log('[Course Agent] ========================================');
            console.log('[Course Agent] --- DEBUG INFO FROM SERVER ---');
            console.log('[Course Agent] ========================================');
            console.log('[Course Agent] API Type:', data.debug.api_type);
            console.log('[Course Agent] Provider Name:', data.debug.provider_name || '(form values)');
            console.log('[Course Agent] Provider ID:', data.debug.provider_id || '(new provider)');
            console.log('[Course Agent] Base URL:', data.debug.base_url);
            console.log('[Course Agent] Endpoint:', data.debug.endpoint);
            console.log('[Course Agent] Model Used:', data.debug.model);
            console.log('[Course Agent] Models Available:', data.debug.models_available || '(from form)');
            console.log('[Course Agent] Full URL (masked):', data.debug.full_url);
            console.log('[Course Agent] Request Method:', data.debug.request_method);
            console.log('[Course Agent] Request Headers:', data.debug.request_headers);
            console.log('[Course Agent] Request Payload:');
            console.log(JSON.stringify(data.debug.request_payload, null, 2));
            console.log('[Course Agent] Response Raw:', data.debug.response_raw ? data.debug.response_raw.substring(0, 500) + '...' : '(none)');
            if (data.debug.curl_error) {
                console.error('[Course Agent] cURL Error:', data.debug.curl_error);
            }
            if (data.debug.exception) {
                console.error('[Course Agent] Exception:', data.debug.exception);
            }
        }

        console.log('[Course Agent] --- FULL RESPONSE OBJECT ---');
        console.log(JSON.stringify(data.response, null, 2));

        btn.disabled = false;
        status.textContent = '';

        // Build response body toggle section.
        var responseBodyHtml = '';
        if (data.response) {
            var responseJson = JSON.stringify(data.response, null, 2);
            responseBodyHtml = '<div style="margin-top:10px;">' +
                '<span class="ca-response-toggle text-primary" data-expanded="false">' +
                '<i class="fa fa-chevron-right mr-1"></i>{$showresponselabel}</span>' +
                '<div class="ca-response-body" style="display:none;">' +
                '<strong>{$responsebodylabel}:</strong><br>' + escapeHtml(responseJson) + '</div></div>';
        }

        if (data.success) {
            console.log('[Course Agent] ✓ SUCCESS: Connection test passed!');
            result.className = 'mt-2 alert alert-success';
            result.innerHTML = '<strong>{$iconvalidhtml}{$testlabel}:</strong> ' +
                (data.message || 'Connection successful!') +
                (data.httpcode ? ' (HTTP ' + data.httpcode + ')' : '') +
                (data.ai_response ? '<br><em>AI replied: ' + escapeHtml(data.ai_response) + '</em>' : '') +
                responseBodyHtml;
        } else {
            console.error('[Course Agent] ✗ FAILED: Connection test failed!');
            result.className = 'mt-2 alert alert-danger';
            result.innerHTML = '<strong>{$iconinvalidhtml}{$testlabel}:</strong> ' +
                (data.message || 'Connection failed') +
                (data.httpcode ? ' (HTTP ' + data.httpcode + ')' : '') +
                responseBodyHtml;
        }
        result.style.display = 'block';

        // Attach toggle handlers for response body.
        var toggles = result.querySelectorAll('.ca-response-toggle');
        toggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                var body = this.nextElementSibling;
                var icon = this.querySelector('i');
                if (body.style.display === 'none') {
                    body.style.display = 'block';
                    icon.className = 'fa fa-chevron-down mr-1';
                    this.innerHTML = '<i class="fa fa-chevron-down mr-1"></i>{$hideresponselabel}';
                } else {
                    body.style.display = 'none';
                    icon.className = 'fa fa-chevron-right mr-1';
                    this.innerHTML = '<i class="fa fa-chevron-right mr-1"></i>{$showresponselabel}';
                }
            });
        });
    })
    .catch(function(err) {
        console.error('[Course Agent] ========================================');
        console.error('[Course Agent] ✗ AJAX/FETCH ERROR');
        console.error('[Course Agent] ========================================');
        console.error('[Course Agent] Error:', err);
        console.error('[Course Agent] Error Message:', err.message);
        console.error('[Course Agent] Error Stack:', err.stack);
        btn.disabled = false;
        status.textContent = '';
        result.className = 'mt-2 alert alert-danger';
        result.textContent = 'Request error: ' + err.message;
        result.style.display = 'block';
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
JS;
        // Use js_init_code() for plain JS - this handles DOM-ready timing internally.
        $PAGE->requires->js_init_code($jscode);

        // Return only the HTML (no script).
        return <<<HTML
<div class="courseagent-test-connection mb-3">
    <button type="button" id="ca-test-connection-btn" class="btn btn-outline-primary">
        <i class="fa fa-plug mr-1"></i>{$testlabel}
    </button>
    <span id="ca-test-status" class="ml-2 text-muted"></span>
    <div id="ca-test-result" class="mt-2" style="display:none;"></div>
    <input type="hidden" id="ca-provider-id" value="{$providerid}" />
</div>

<style>
.ca-response-toggle {
    cursor: pointer;
    user-select: none;
}
.ca-response-toggle:hover {
    text-decoration: underline;
}
.ca-response-body {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    margin-top: 8px;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 300px;
    overflow-y: auto;
}
</style>
HTML;
    }

    /**
     * Server-side validation.
     *
     * @param array $data
     * @param array $files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Name uniqueness check.
        if (!empty($data['name'])) {
            global $DB;
            if (!empty($data['id'])) {
                $exists = $DB->record_exists_select(
                    'courseagent_providers',
                    'name = ? AND id <> ?',
                    [trim($data['name']), (int)$data['id']]
                );
            } else {
                $exists = $DB->record_exists('courseagent_providers', ['name' => trim($data['name'])]);
            }
            if ($exists) {
                $errors['name'] = get_string('provider_name_exists', 'local_courseagent');
            }
        }

        // Base URL format.
        if (!empty($data['baseurl'])) {
            $url = trim($data['baseurl']);
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                $errors['baseurl'] = get_string('provider_baseurl_invalid', 'local_courseagent');
            }
        }

        // Models are optional — allow submission with zero models.
        // (The AI call will fall back to a provider-level default if no model is specified.)

        return $errors;
    }
}
