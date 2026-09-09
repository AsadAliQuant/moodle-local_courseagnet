# AGENTS.md — Course Agent Plugin

**Course Agent** is a Moodle 5.x local plugin (`local_courseagent`) for AI-powered course generation.
All AI calls are server-side (PHP → external API). No browser AI libraries.

> **Product positioning — read before writing any copy, UI text, or comments:**
> This plugin is an AI course builder, not an H5P generator. H5P activity generation is one optional feature.
> Never write phrases like "AI-powered H5P generation", "H5P activity generator", or anything that frames
> H5P as the whole product. Future features include RAG-based full course generation from scratch,
> more Moodle activity types, and more. Use neutral framing: "CourseAgent", "AI course builder".

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
| [.Codex/docs/architecture.md](.Codex/docs/architecture.md) | Data flow, entry points, full file map |
| [.Codex/docs/ai-assist.md](.Codex/docs/ai-assist.md) | Chat edit system: intent detection, delta protocol, CRUD ops, AI call budget |
| [.Codex/docs/providers.md](.Codex/docs/providers.md) | AI provider system, preset buttons, model widget, adding new presets/formats |
| [.Codex/docs/database.md](.Codex/docs/database.md) | DB schema, security model, DB change checklist |
| [.Codex/docs/frontend.md](.Codex/docs/frontend.md) | AMD JS, CSS, navigation hooks, AJAX pattern, terser build |
| [.Codex/docs/dev-notes.md](.Codex/docs/dev-notes.md) | Gotchas, known limits, Moodle 5.x compat notes |
| [.Codex/docs/mdl_coding_style.md](.Codex/docs/mdl_coding_style.md) | Moodle PHP coding standards |
| [.Codex/docs/mdl_frankenstyle.md](.Codex/docs/mdl_frankenstyle.md) | Frankenstyle plugin structure |
| [.Codex/docs/mdl_plugin_codechecker_erros.md](.Codex/docs/mdl_plugin_codechecker_erros.md) | Codechecker error fixes |
| [.Codex/docs/mdl_sql.md](.Codex/docs/mdl_sql.md) | SQL coding standards |
| [.Codex/docs/moodle-native-layout.md](.Codex/docs/moodle-native-layout.md) | How to make a plugin page fully flush/edge-to-edge (remove iframe/card feel) |
