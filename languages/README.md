# Translations

Text domain: `storefuse-bridge` (see plugin header `Domain Path: /languages`).

Generate or refresh the template:

```bash
wp i18n make-pot storefuse-bridge languages/storefuse-bridge.pot --domain=storefuse-bridge
```

Requires [WP-CLI](https://wp-cli.org/) with the i18n command. Commit updated `.pot` when user-facing strings change.
