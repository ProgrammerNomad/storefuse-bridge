# Current admin sidebar (as-shipped)

Registered in `StoreFuse_Bridge_Admin::register_menus()` (`includes/class-admin.php`).

| # | Menu label | Slug | Callback | View file | Saves settings? |
|---|------------|------|----------|-----------|-----------------|
| - | StoreFuse (top) | `storefuse-bridge` | `page_dashboard` | `admin/views/page-dashboard.php` | No (AJAX cache only) |
| 1 | Dashboard | `storefuse-bridge` | `page_dashboard` | same | No |
| 2 | General | `storefuse-bridge-general` | `page_general` | `page-general.php` | Yes |
| 3 | Homepage | `storefuse-bridge-homepage` | `page_homepage` | `page-homepage.php` | Yes |
| 4 | Mobile app | `storefuse-bridge-mobile-app` | `page_mobile_app` | `page-mobile-app.php` | Yes |
| 5 | Navigation | `storefuse-bridge-navigation` | `page_navigation` | `page-navigation.php` | No |
| 6 | Social & Trust | `storefuse-bridge-social` | `page_social` | `page-social.php` | Yes |
| 7 | Checkout | `storefuse-bridge-checkout` | `page_checkout` | `page-checkout.php` | Yes |
| 8 | Storefront & Clients | `storefuse-bridge-storefront` | `page_storefront` | `page-storefront.php` | Yes (+ webhook log read-only) |
| 9 | API & Tools | `storefuse-bridge-api` | `page_api` | `page-api.php` | Yes (CORS only) |
| 10 | Advanced | `storefuse-bridge-advanced` | `page_advanced` | `page-advanced.php` | Yes (modules only) |
| 11 | Extensions | `storefuse-bridge-extensions` | `page_extensions` | `page-extensions.php` | No |

## Access control

- Every page callback checks `current_user_can( StoreFuse_Bridge_Admin::settings_capability() )` before rendering.
- Settings forms use `settings_fields( 'storefuse_bridge_settings_group' )` and POST to `options.php`.
- AJAX actions use `check_ajax_referer( 'sfb_admin_nonce', 'nonce' )` and the same capability.

## Assets

- Enqueued on any hook containing `storefuse-bridge`: `assets/admin.css`, `assets/admin.js`, WP Color Picker, Media Library (homepage hero).

## Planned v2

The redirect and client-aware menu behavior described in [../refactor-v2/target-sidebar.md](../refactor-v2/target-sidebar.md) is planned and is not part of the current plugin yet.
