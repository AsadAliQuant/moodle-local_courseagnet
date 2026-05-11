# Moodle Native Layout — Remove iframe/Card Feel

How to make a `local_` plugin page fully flush and edge-to-edge, looking like a native Moodle page instead of a card-inside-a-page.

## The Problem

Moodle Boost wraps all plugin content in a deep stack of containers, each adding padding/margin:

```
#page-wrapper
  └─ #page.drawers
       └─ #topofscroll.main-inner        ← padding: 1.5rem 0.5rem; margin-bottom: 3rem
            └─ #page-content.pb-3        ← padding-bottom: 1rem (Bootstrap)
                 └─ #region-main-box
                      └─ #region-main
                           └─ your content
```

Plus a responsive media rule:
```css
@media (min-width: 768px) {
    #page.drawers div[role="main"] {
        padding-left: 15px;
        padding-right: 15px;
    }
}
```

Result: any custom full-width layout (e.g. a 3-column app) ends up looking like a card-in-a-page.

## The Fix — Two Files, Zero Theme Changes

### 1. PHP page file

Add a scoped body class right after `set_pagelayout()`:

```php
$PAGE->set_pagelayout('base');
$PAGE->add_body_class('local-PLUGINNAME-flush');
```

Use `base` layout — it gives Moodle's top navbar only, no block regions, no course index drawer.

### 2. plugin styles.css

```css
/* ── Native Moodle layout overrides (scoped — won't affect other pages) ── */
body.local-PLUGINNAME-flush #page-wrapper,
body.local-PLUGINNAME-flush #page {
    padding: 0 !important;
    margin: 0 !important;
    max-width: none !important;
}

body.local-PLUGINNAME-flush .main-inner,
body.local-PLUGINNAME-flush #page-content,
body.local-PLUGINNAME-flush #region-main,
body.local-PLUGINNAME-flush #region-main-box {
    padding: 0 !important;
    margin: 0 !important;
    max-width: none !important;
    border: 0 !important;
    background: transparent !important;
}

/* Boost responsive gutter — must be overridden separately */
body.local-PLUGINNAME-flush #page.drawers div[role="main"] {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

/* Hide Moodle page title bar and secondary nav (plugin has its own header) */
body.local-PLUGINNAME-flush #page-header,
body.local-PLUGINNAME-flush .secondary-navigation {
    display: none;
}
```

### Viewport-height app

For an app that fills the remaining screen below the navbar:

```css
#my-app-container {
    height: calc(100vh - 60px); /* 60px = Boost primary navbar height */
    overflow: hidden;
}
```

Adjust `60px` if the site uses a taller/shorter theme navbar.

## Why `!important` is Required

Boost compiles SCSS to specific rules that a plain `body.class element` selector can't beat:
- `.main-inner { padding: 1.5rem 0.5rem }` — wins on compiled order
- `#page.drawers div[role="main"]` — wins inside a media query

`!important` is the only reliable override short of editing the theme.

## Why `base` Layout

| Layout | Navbar | Left drawer | Right drawer |
|--------|--------|-------------|--------------|
| `base` | ✓ | ✗ | ✗ |
| `standard` | ✓ | ✗ | ✓ (blocks) |
| `embedded` | ✗ | ✗ | ✗ |
| `incourse` | ✓ | ✓ (course index) | ✓ (blocks) |

`base` is the sweet spot: you keep Moodle's top navbar (so it feels native) but get no drawers to fight.

## Applied In This Plugin

- [preview.php:38](../../../preview.php#L38) — `add_body_class('local-courseagent-preview-flush')`
- [styles.css](../../../styles.css) — look for `/* ── Native Moodle layout overrides ── */`
