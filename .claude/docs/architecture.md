# Architecture

## Plugin Type
- **Type**: `local` plugin (site-wide, not course-specific)
- **Location**: `local/courseagent/`
- **Moodle Version**: 5.0+ (tested 5.1)
- **Component Name**: `local_courseagent`

## Data Flow

```
User Request → index.php → AMD JS → ajax.php → classes/api.php → AI Provider API
                                        ↓
                                  classes/provider.php (encryption, config)
                                        ↓
                                  Moodle Course APIs (create_course, add_moduleinfo)
```

## Entry Points

| File | Purpose |
|------|---------|
| `index.php` | Main course creation UI — two-column layout (form + preview) |
| `ajax.php` | AJAX endpoint: `generate`, `publish`, `ai_assist`, `edit_item`, `test_provider`, `get_models`, `extract_content`, `get_progress` |
| `providers.php` | Provider management (CRUD, test connections, set default, presets) |
| `mycourses.php` | Course history — user's generated courses |
| `settings.php` | Admin settings: max sections, quiz questions, assignments toggle |

## Course Generation Flow

1. **Generate** (`ajax.php?action=generate`):
   - User submits topic, level, sections, quiz/assignment prefs
   - `api.php::generate_course_outline()` builds prompt
   - `provider::call_api()` sends to configured provider
   - AI returns JSON → stored in `$SESSION->courseagent_preview` → displayed in preview

2. **AI Assist / Edit** (`ajax.php?action=ai_assist`):
   - Chat panel sends `{user_prompt, context_hint}` — **no full course in body**
   - Server reads course from `$SESSION->courseagent_preview`
   - `api.php::ai_assist()` detects intent (from hint or AI), generates only the changed item
   - Returns surgical `delta` — client patches its local copy; session stores full merged course
   - See `.claude/docs/ai-assist.md` for full detail

3. **Publish** (`ajax.php?action=publish`):
   - `api.php::publish_course()` validates
   - `create_course()` → `course_create_sections_if_missing()`
   - Adds modules: `mod_page` (lessons), `mod_quiz`, `mod_assign`
   - Saves session to DB

## File Map

```
local/courseagent/
├── amd/src/coursecreator.js       # Frontend AMD module
├── amd/src/preview.js             # Preview panel JS
├── classes/
│   ├── api.php                    # Core: generate, publish, create modules
│   ├── provider.php               # AI providers: CRUD, encryption, API calls
│   ├── form/provider_form.php     # Provider add/edit form + model widget
│   ├── hook/navigation.php        # Moodle 5.x primary nav hook
│   └── privacy/provider.php       # GDPR compliance
├── db/
│   ├── access.php                 # Capability definitions
│   ├── install.xml                # DB schema
│   ├── hooks.php                  # Hook callbacks
│   └── upgrade.php                # Version upgrades
├── lang/en/local_courseagent.php  # All language strings
├── ajax.php                       # AJAX endpoint
├── index.php                      # Main creation page
├── lib.php                        # Navigation callbacks (minimal)
├── mycourses.php                  # Course history
├── providers.php                  # Provider management + preset buttons
├── settings.php                   # Admin settings
├── styles.css                     # Styles scoped to #courseagnet-app
└── version.php                    # Plugin metadata
```
