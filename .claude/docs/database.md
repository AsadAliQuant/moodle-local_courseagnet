# Database & Security

## Tables

### `courseagent_sessions`
Tracks each course generation attempt.

| Column | Type | Notes |
|--------|------|-------|
| `userid` | int | Moodle user |
| `courseid` | int | Created course (0 if draft) |
| `status` | varchar | `draft` / `published` / `failed` |
| `course_json` | longtext | Full AI-generated course structure |
| `timecreated`, `timemodified` | int | Timestamps |

### `courseagent_providers`
AI provider configurations.

| Column | Type | Notes |
|--------|------|-------|
| `name` | varchar | Display label |
| `apikey` | text | AES-256-CBC encrypted + HMAC |
| `baseurl` | varchar | e.g. `https://generativelanguage.googleapis.com` |
| `endpoint` | varchar | e.g. `v1beta/models/{model}:generateContent` |
| `api_format` | varchar | `gemini` or `openai` |
| `models` | text | JSON array of model IDs |
| `isdefault` | tinyint | 1 = this provider is used by default |
| `enabled` | tinyint | 1 = active |
| `sortorder` | int | Display order |

## Security Model

- **Capabilities**: `local/courseagent:createcourse`, `local/courseagent:viewmycourses`
- **API Key Encryption**: AES-256-CBC + HMAC via `classes/provider.php`
- **Encryption Key**: derived from `get_site_identifier()` — site-specific, never hardcoded
- **Context**: all operations use `CONTEXT_SYSTEM`
- **AJAX protection**: all endpoints call `require_sesskey()`; JS passes `config.sesskey`

## DB Changes Checklist

1. Edit `db/install.xml` (new installs)
2. Add upgrade step in `db/upgrade.php`
3. Bump `$plugin->version` in `version.php`
