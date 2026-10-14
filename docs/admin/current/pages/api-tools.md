# API & Tools (current) → API & CORS (v2)

## Purpose

CORS for browser clients, API URL reference, cache group flush, session checklist.

## Menu

| | |
|--|--|
| **Slug** | `storefuse-bridge-api` |
| **View** | `admin/views/page-api.php` |

## Page layout

1. **CORS** form (save)  
2. **API base URL** (read-only)  
3. **Cache control** (AJAX buttons)  
4. **Session & auth checklist** (static)  

## Fields

| Label | Key | ID |
|-------|-----|-----|
| Enable CORS | `cors_enabled` | checkbox |
| Allowed origins | `cors_allowed_origins` | `sfb_cors_origins` textarea, one origin per line |

Submit button: Save CORS Settings.

## Non-settings UI

Copy API URL, links to `/status`, `/settings`. Cache groups: all, products, settings, navigation, homepage, search - `.sfb-flush-group`, result `#sfb-flush-group-result`.

## v2 deltas

Remove cache section from this page (move to Dashboard). Rename menu **API & CORS**. Link to Dashboard for cache.
