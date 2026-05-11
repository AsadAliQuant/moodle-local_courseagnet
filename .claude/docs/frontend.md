# Frontend

## AMD Modules

| File | Role |
|------|------|
| `amd/src/coursecreator.js` | Main UI — form submit, generate, publish. jQuery + `core/ajax`, `core/notification` |
| `amd/src/preview.js` | Preview panel rendering |
| `amd/build/` | Minified copies — update manually or via `grunt` |

**Build required after every src edit.** Minify with terser (installed globally):
```
terser amd/src/MODULE.js --compress --mangle --output amd/build/MODULE.min.js
```
Never copy src to build unminified — Moodle loads the `.min.js` file in production.

## CSS

`styles.css` — all rules scoped under `#courseagnet-app` to prevent theme conflicts. Uses Bootstrap 4 classes (Moodle 5.x standard).

## Page Layout

All pages use `$PAGE->set_pagelayout('standard')` except `providers.php` which uses `'admin'`.

## Navigation Hooks (`lib.php` + `classes/hook/navigation.php`)

| Hook | Where it appears |
|------|-----------------|
| `local_courseagent_extend_navigation()` | Main nav drawer (teachers+) |
| `local_courseagent_extend_navigation_user()` | User profile nav |
| `local_courseagent_extend_settings_navigation()` | Site Administration |
| `classes/hook/navigation.php` | Moodle 5.x primary navigation (modern hook) |

## AJAX Pattern

Direct POST to `ajax.php?action=X&sesskey=...` with JSON body:

```js
$.ajax({
    url: config.wwwroot + '/local/courseagent/ajax.php?action=X&sesskey=' + config.sesskey,
    type: 'POST',
    data: JSON.stringify(payload),
    contentType: 'application/json',
    dataType: 'json',
});
```

## AI Assist Chat Pattern (preview.js)

Chat sends a small hint, not the full course:

```js
var payload = {
    user_prompt: text,
    context_hint: { target_type, section_index, question_index }
    // No course_data — server reads from session
};
```

Response is a `delta` object. Apply with `applyDelta(config.courseData, response.delta)`.

See `.claude/docs/ai-assist.md` for full delta spec.

## Adding a New AJAX Action

1. Add `case 'myaction':` in `ajax.php` switch
2. Add `break;` at the end of the case — **missing break causes double JSON output**
3. Add corresponding JS call in the relevant `amd/src/*.js`
4. Add lang string if needed
