# Navigation

## Purpose

Assign WordPress menus to StoreFuse theme locations and preview header/footer trees. Used by web clients (`GET /navigation`, `settings.navigation`). Flutter uses `app_ui_nav_tabs`, not WP menus.

## Menu

| | |
|--|--|
| **Slug** | `storefuse-bridge-navigation` |
| **View** | `admin/views/page-navigation.php` |
| **Saves settings** | No |

## Current sidebar behavior

The current plugin displays this submenu for all `primary_client` values and does not show a client-context notice. In the v2 proposal, it is hidden from the sidebar for Flutter; authorized direct URLs remain available with an explanatory notice.

## Page layout

1. **Navigation menus** - Header (`storefuse-header`), Footer (`storefuse-footer`) assignment status + Edit links  
2. **Live preview** - Nested lists for header/footer  
3. **Navigation cache** - Flush navigation group  

## Fields

None in `storefuse_bridge_settings`. Menu content edited under **Appearance → Menus**.

## JavaScript

`#sfb-flush-nav-cache` - AJAX `storefuse_bridge_flush_cache_group`, `group=navigation`, result `#sfb-flush-nav-result`.

## API

`GET /navigation` or `GET /settings` → `navigation.main` / footer per module settings builder.
