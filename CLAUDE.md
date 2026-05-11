# CLAUDE.md — Course Agent Plugin

**Course Agent** is a Moodle 5.x local plugin (`local_courseagent`) for AI-powered course generation.
All AI calls are server-side (PHP → external API). No browser AI libraries.

## Quick Context

- Plugin dir: `local/courseagent/` on XAMPP local dev
- Main entry: `index.php` → `ajax.php` → `classes/api.php` → AI provider
- Two DB tables: `courseagent_sessions`, `courseagent_providers`
- Capabilities: `local/courseagent:createcourse`, `local/courseagent:viewmycourses`

## AMD JS Build Rule

After editing any `amd/src/*.js` file, always minify with terser:
```
terser amd/src/MODULE.js --compress --mangle --output amd/build/MODULE.min.js
```
Terser is installed globally (`terser --version` to confirm). Never manually copy src to build.

## Detail Docs — read only what the task needs

| Doc | When to read |
|-----|-------------|
| [.claude/docs/architecture.md](.claude/docs/architecture.md) | Data flow, entry points, full file map |
| [.claude/docs/ai-assist.md](.claude/docs/ai-assist.md) | Chat edit system: intent detection, delta protocol, CRUD ops, AI call budget |
| [.claude/docs/providers.md](.claude/docs/providers.md) | AI provider system, preset buttons, model widget, adding new presets/formats |
| [.claude/docs/database.md](.claude/docs/database.md) | DB schema, security model, DB change checklist |
| [.claude/docs/frontend.md](.claude/docs/frontend.md) | AMD JS, CSS, navigation hooks, AJAX pattern, terser build |
| [.claude/docs/dev-notes.md](.claude/docs/dev-notes.md) | Gotchas, known limits, Moodle 5.x compat notes |
| [.claude/docs/mdl_coding_style.md](.claude/docs/mdl_coding_style.md) | Moodle PHP coding standards |
| [.claude/docs/mdl_frankenstyle.md](.claude/docs/mdl_frankenstyle.md) | Frankenstyle plugin structure |
| [.claude/docs/mdl_plugin_codechecker_erros.md](.claude/docs/mdl_plugin_codechecker_erros.md) | Codechecker error fixes |
| [.claude/docs/mdl_sql.md](.claude/docs/mdl_sql.md) | SQL coding standards |
