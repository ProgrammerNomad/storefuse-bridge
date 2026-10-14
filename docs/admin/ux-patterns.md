# Admin UX patterns

Shared conventions across `admin/views/*.php` and `assets/admin.css` / `admin.js`.

## Page chrome

- Wrapper: `<div class="wrap sfb-admin">`  
- Title: `StoreFuse - {Page Name}` in `<h1>` (v2: consistent **StoreFuse - {Name}**)  
- Capability: render only if `current_user_can( settings_capability() )`  

## Section cards

- Container: `.sfb-card`  
- Heading: `<h2>` + optional `<p class="description">`  
- Fields: `<table class="form-table">` with `<th scope="row">` and labels `for`/`id` where applicable  

## Settings forms

```php
<form method="post" action="options.php">
  <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
  ...
  <?php submit_button(); ?>
</form>
```

- After save: `settings_errors( 'storefuse_bridge_settings' )` - success **Settings saved.**  
- Any save merges patch into `storefuse_bridge_settings` and **flushes all Bridge cache** (existing behaviour).  

## Special save batches

| Hidden field | Page | Reason |
|--------------|------|--------|
| `_sfb_save_app_ui=1` | Mobile app | Unchecked checkboxes must persist |
| `_sfb_save_modules=1` | Advanced | Module toggles only update when saving this form |

## Status badges

- `.sfb-badge--on` / `.sfb-badge--off` for OK/Todo, Enabled/Disabled  

## JavaScript (shipped)

- **Unsaved changes:** `beforeunload` when form dirty (v2 requirement: keep/enhance)  
- **Color:** WP Color Picker on `.sfb-color-picker`  
- **Homepage:** collapsible section cards (keyboard: Enter/Space on `<h2>`)  

## v2 production requirements (from refactor plan)

| Area | Requirement |
|------|-------------|
| Save feedback | `settings_errors()` on every form page |
| Unsaved changes | `beforeunload` on modified forms |
| Validation | Hex on colors; URL fields use `type="url"` or `esc_url_raw`; numbers use min/max HTML attrs |
| Focus | Visible focus styles; logical tab order |
| Labels | Every input/select/textarea has associated label |
| Contrast | WCAG AA on admin text |
| Spacing | Consistent margin between `.sfb-card` blocks |

## Non-form pages

Dashboard, Navigation, Extensions: no `submit_button`; AJAX buttons use `type="button"`.
