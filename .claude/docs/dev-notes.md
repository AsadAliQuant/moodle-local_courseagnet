# Dev Notes & Gotchas

## Key Patterns

### Error Handling (AJAX endpoints)
```php
try {
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

### Capability Check
Always check before course generation:
```php
require_capability('local/courseagent:createcourse', context_system::instance());
```

### API Key Edit — Blank = Keep Existing
When editing a provider and API key field is left blank, the existing encrypted key is preserved. Logic in `providers.php` ~line 175.

## Known Limitations

- **Quiz questions ARE populated**: `create_multichoice_question()` in `classes/api.php` handles full Moodle 5.x question bank insertion (`question_bank_entries`, `question_versions`, `qtype_multichoice_options`, `question_answers`). Dev notes were stale.
- **No grunt pipeline**: Use terser directly (`terser amd/src/X.js --compress --mangle --output amd/build/X.min.js`).

## publish_course() Return Type

`publish_course()` returns `array{courseid: int, h5p_warnings: string[]}`, not a plain int.  
`ajax.php` reads `$result['courseid']` and `$result['h5p_warnings']`. Don't assume it returns int.

## H5P Module Creation — No file_manager Draft

`create_h5p_module()` inserts the `h5pactivity` record directly and attaches the .h5p file via  
`get_file_storage()->create_file_from_pathname()` on the `package` filearea. This bypasses  
`h5pactivity_add_instance()` which expects a filemanager draft context. Moodle processes the  
H5P package lazily on first view — no pre-processing step needed at creation time.

## H5P SaaS — Non-Fatal by Design

`create_h5p_activities()` failures append to `$this->h5pwarnings[]` and return early. The  
course publishes successfully regardless. Check `h5p_warnings` in the publish response if  
debugging missing H5P modules.

## Standalone vs SaaS-connected Branching

`config.hasSaasKey` (bool, from `$jsconfig` in `index.php`) controls JS branching in  
`generateCourseOutline()`.

- **SaaS-connected path** (`hasSaasKey = true`): calls `runPlanStep()` first (AI generates course outline for teacher approval), then `runGenerateStep()`. Enables H5P activities, RAG, and the outline-approval modal.
- **Standalone path** (`hasSaasKey = false`): skips directly to `runGenerateStep()` using the plugin's own AI provider. Generates quizzes, assignments, and lessons only.

When adding new form options, verify both paths still work.

## Critical Gotcha — Missing `break` in ajax.php Switch

Every `case` in `ajax.php` must end with `break;` before `default:`. Without it, PHP falls through — `echo json_encode(success)` runs, then `throw new Exception` also runs, appending a second `{"success":false}` blob. jQuery's `dataType:'json'` fails to parse two concatenated JSON objects and fires the error callback instead of success. This produces the "Error updating. Please try again." message in the chat panel.

## External Dependencies

| Dependency | Used for |
|-----------|---------|
| `openssl` PHP ext | API key encryption |
| `curl` PHP ext | AI provider HTTP calls |
| Google Gemini API | Default AI provider |
| OpenAI API (or compatible) | Alternative provider |
| Moodle `lib/modulerlib.php` | Creating course modules |

## Windows Dev Setup — Plugin Junction + Config Shim

On Windows, PHP resolves `__DIR__` to the **real target path** through both symlinks and NTFS junctions. So when the plugin is junctioned from `C:\xampp\htdocs\moodle\public\local\courseagent` → `D:\Asad\...\vite-monorepo\moodle-plugin`, every plugin file sees `__DIR__` = the D: path. Then `__DIR__ . '/../../config.php'` looks for `D:\Asad\Projects\Moodle\Plugins\aiAgent\config.php` instead of the real Moodle config.

**Fix already in place:** A shim file at `D:\Asad\Projects\Moodle\Plugins\aiAgent\config.php` forwards to the real config:
```php
<?php
require_once('C:/xampp/htdocs/moodle/public/config.php');
```

**If you reinstall XAMPP or clone on a new machine**, recreate two things:
1. The junction (run as Admin in CMD):
   ```cmd
   mklink /J "C:\xampp\htdocs\moodle\public\local\courseagent" "D:\Asad\Projects\Moodle\Plugins\aiAgent\vite-monorepo\moodle-plugin"
   ```
2. The shim file at `D:\Asad\Projects\Moodle\Plugins\aiAgent\config.php` (content above).

The shim is outside the monorepo and not git-tracked — it must be created manually on each machine.

## Moodle 5.x Compatibility Notes

- Use `CONTEXT_SYSTEM` (not deprecated `get_system_context()`)
- Navigation: use hook class in `classes/hook/navigation.php` (not old `_extend_navigation` alone)
- Bootstrap 4 classes (not Bootstrap 5)
- Check `Sodium` PHP extension if crypto features added (XAMPP: copy DLLs to Apache/bin)
