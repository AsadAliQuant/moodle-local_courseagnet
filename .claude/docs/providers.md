# AI Provider System

Managed by `classes/provider.php`.

## Supported API Formats

| Format | Detection | Request style |
|--------|-----------|---------------|
| `gemini` | `generativelanguage.googleapis.com` | `POST {baseurl}/{endpoint}?key={apikey}` with `contents` array |
| `openai` | `openai.com` or custom | `POST {baseurl}/{endpoint}` with Bearer token, `model` + `messages` |

## Provider Config Fields

Each DB row in `courseagent_providers`:
- `name`, `apikey` (encrypted AES-256-CBC + HMAC), `baseurl`, `endpoint`
- `models` (JSON array — first item is default)
- `isdefault`, `enabled`, `sortorder`

## Preset Buttons (providers.php — Add page only)

Preset buttons auto-fill the form. Currently supported:

**Google Gemini**
- Base URL: `https://generativelanguage.googleapis.com`
- Endpoint: `v1beta/models/{model}:generateContent`
- Format: `gemini`
- Models (free, high rate limit):
  - `gemini-2.5-flash` ★ default
  - `gemini-2.5-flash-lite`
  - `gemini-2.5-pro`
  - `gemini-2.0-flash`
  - `gemini-3-flash-preview`

**NVIDIA NIM**
- Base URL: `https://integrate.api.nvidia.com`
- Endpoint: `v1/chat/completions`
- Format: `openai`
- Models:
  - `meta/llama-3.1-8b-instruct` ★ default
  - `meta/llama-3.1-70b-instruct`
  - `z-ai/glm4.7`
  - `deepseek-ai/deepseek-v4-pro`

To add more presets: edit the `$presets` array in `providers.php` (~line 240).

## Model Widget (provider_form.php)

- Hidden `input[name="models_json"]` stores JSON array
- `renderList()` / `sync()` manage the UI list ↔ hidden field
- **External setter**: `window.caModelsWidget.setModels(arr)` — call this to programmatically set models (used by preset buttons)
- First model in list = default (shown with ★ badge)

## Adding a New Provider Preset

```php
'openai' => [
    'label'      => 'OpenAI',
    'icon'       => 'https://...svg',
    'name'       => 'OpenAI',
    'baseurl'    => 'https://api.openai.com',
    'endpoint'   => 'v1/chat/completions',
    'api_format' => 'openai',
    'models'     => ['gpt-4o', 'gpt-4o-mini'],
],
```

## Adding a New API Format

Edit `classes/provider.php`:
- `call_api()` — add format detection + request logic
- `test_connection()` — add test logic for new format
