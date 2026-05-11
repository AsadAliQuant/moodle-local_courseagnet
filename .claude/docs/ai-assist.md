# AI Assist — Surgical Edit System

## Overview

The chat panel in `preview.php` lets users make CRUD edits to a generated course via natural language. Every edit is surgical — only the changed item is sent/returned, not the full course JSON.

## Request Format (JS → Server)

```json
{
  "user_prompt": "add a harder question",
  "context_hint": {
    "target_type": "quiz|lesson|question|assignment|section",
    "section_index": 0,
    "question_index": null
  }
}
```

- **No `course_data` in body** — server reads from `$SESSION->courseagent_preview`
- `context_hint` is built from the current UI state (active section/tab/question)
- `question_index` only set when on quiz tab

## Response Format (Server → JS)

```json
{
  "success": true,
  "delta": {
    "op": "add|update|delete|replace",
    "target_type": "quiz|lesson|question|assignment|section",
    "section_index": 0,
    "question_index": null,
    "data": { ... only the updated item ... }
  },
  "message": "Added question to quiz in section 1."
}
```

Session always holds the full merged course. Client holds its own copy and applies deltas locally.

## Server-Side Flow (`classes/api.php::ai_assist`)

```
hint provided?
  YES → intent_from_hint()          ← 0 AI calls, keyword regex
  NO  → ai_detect_intent()          ← 1 AI call (intent detection)
         ↓
action === 'delete'?
  YES → merge_delta() + return       ← 0 AI calls (no generation needed)
  NO  → build_targeted_context()
         ↓
       build_generation_prompt_from_intent()   ← only the target item in prompt
         ↓
       provider::call_api()          ← 1 AI call (item generation only)
         ↓
       merge_delta()                 ← update full course in session
         ↓
       return delta + coursedata
```

## AI Call Budget Per Edit

| Scenario | AI calls |
|---|---|
| Edit lesson/quiz/assignment (context hint provided) | 1 |
| Add question (on quiz tab → hint provided) | 1 |
| Add section | 1 |
| Delete question/section | 0 |
| Cross-section edit ("in section 2") without hint | 2 (intent + gen) |

## Intent Resolution (`intent_from_hint`)

When a hint is present, action is derived from prompt keywords:

| Keyword pattern | Action |
|---|---|
| `add|create|generate|insert|new` | `add` |
| `delete|remove|drop` | `delete` |
| `replace|rewrite|redo|regenerate` | `replace` |
| anything else | `update` |

Special rules:
- `add new section` in prompt → overrides hint target to `section`
- `add` + target=`quiz` + prompt contains `question` → target becomes `question`
- `delete` + target=`quiz` + prompt contains `question N` → target becomes `question`, parses index

## Client-Side Delta Apply (`amd/src/preview.js::applyDelta`)

Patches `config.courseData` in place without full replace:

```
delta.target_type === 'section'   → push/splice/replace sections[]
delta.target_type === 'lesson'    → section.lesson = delta.data
delta.target_type === 'quiz'      → section.quiz = delta.data
delta.target_type === 'question'  → push/splice/replace quiz.questions[]
delta.target_type === 'assignment'→ section.assignment = delta.data
```

After apply, navigates to the updated section/tab automatically.

## CRUD Coverage

| Operation | Triggered by |
|---|---|
| Edit lesson content | Chat on content tab |
| Edit quiz | Chat on quiz tab, no question selected |
| Edit specific question | Chat on quiz tab, question clicked |
| Add question | "add a question" on quiz tab |
| Add section | "add a new section" (any tab) |
| Add quiz to section | "add quiz" on content tab |
| Add assignment | "add assignment" on content tab |
| Delete question | "delete question N" on quiz tab |
| Delete section | "delete this section" |

---

## Conversational AI Protocol (`ai_assist()`)

The `ai_assist()` method runs a multi-turn chat with full history. AI returns a JSON object with `type=question|plan|delta`.

### AI Response Schema

```json
{
  "type": "question|plan|delta",
  "message": "User-facing text",
  "plan_summary": null,
  "target_type": "lesson|quiz|question|assignment|section",
  "section_index": 0,
  "question_index": null,
  "op": "add|update|delete|replace",
  "data": { ... }
}
```

`data` is `null` for `type=question` and `type=plan`. For `op=delete`, `data` is always `null`.

### Type Rules

| type | When | Buttons shown |
|------|------|---------------|
| `question` | AI needs more info. Asks ONE focused question. | None |
| `plan` | AI has all info, change is large/risky. Describes what it will do. | Confirm / Cancel |
| `delta` | Change clear + specific, OR user confirmed a plan. Applies immediately. | None |

**Critical constraint**: `type=plan` must NEVER contain a question in `message`. If AI needs more info, it must use `type=question` first. Mixing question text with `type=plan` causes "question + Yes button" UI — confusing and wrong.

### Delete Enforcement (server-side)

`ai_assist()` has a guard after `$rtype = $result->type ?? 'delta'` that demotes any `type=delta` + `op=delete` to `type=plan` unless the user's current message contains a confirmation keyword (`yes`, `confirm`, `proceed`, `go ahead`, `do it`, `sure`, `ok`, `yep`, `yeah`). Safety net regardless of AI behavior.

### Rate Limit Handling

`provider::call_api_with_history()` is wrapped in `try/catch \Throwable`. On 429/quota errors, returns a `type=question` object with a friendly message ("Rate limit reached. Please wait ~N seconds.") instead of letting the raw exception propagate to Moodle's AJAX handler.

### Session History

`$SESSION->courseagent_chat_history` — last 20 turns (`role` + `content` + `ts`). Sends last 10 to AI per call. Course state stays in `$SESSION->courseagent_preview`.

---

## Critical Gotcha — Missing `break` Causes Double JSON

In `ajax.php`, the `ai_assist` case MUST have a `break;` before `default:`. Without it, PHP falls through to `throw new Exception(...)` which appends a second JSON object to the response, causing jQuery's `dataType: 'json'` to fail silently and show "Error updating."

Pattern to maintain:
```php
case 'ai_assist':
    // ...
    echo json_encode([...]);
    break;          // ← required

default:
    throw new Exception(...);
```
