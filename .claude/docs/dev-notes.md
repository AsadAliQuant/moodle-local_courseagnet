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

- **Quiz questions not populated**: Plugin creates the quiz activity shell only. AI generates question JSON but `questionlib.php` integration is not implemented.
- **No grunt pipeline**: Use terser directly (`terser amd/src/X.js --compress --mangle --output amd/build/X.min.js`).

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

## Moodle 5.x Compatibility Notes

- Use `CONTEXT_SYSTEM` (not deprecated `get_system_context()`)
- Navigation: use hook class in `classes/hook/navigation.php` (not old `_extend_navigation` alone)
- Bootstrap 4 classes (not Bootstrap 5)
- Check `Sodium` PHP extension if crypto features added (XAMPP: copy DLLs to Apache/bin)
