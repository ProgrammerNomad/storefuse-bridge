# Storefront & Clients (current)

## Purpose

Storefront URL, password reset path, primary client guidance, ISR webhook config and delivery log. **v2 splits** into [Setup](../../refactor-v2/pages/setup.md) + [Webhooks (ISR)](../../refactor-v2/pages/webhooks-isr.md).

## Menu

| | |
|--|--|
| **Slug** | `storefuse-bridge-storefront` |
| **View** | `admin/views/page-storefront.php` |

## Page layout

1. **Headless storefront URL** - URL, reset path, primary client select  
2. **Cache revalidation (ISR webhooks)** - path, secret, test button  
3. **Webhook delivery log** (when webhooks module enabled) - read-only table + clear log  

## Fields

| Label | Key | ID |
|-------|-----|-----|
| Storefront URL | `storefront_url` | `sfb_storefront_url` |
| Password reset path | `storefront_reset_path` | `sfb_storefront_reset_path` |
| Primary client | `primary_client` | `sfb_primary_client` | Admin only; `nextjs` \| `flutter` \| `other` |
| Revalidate path | `storefront_revalidate_path` | `sfb_revalidate_path` |
| Revalidation secret | `revalidation_secret` | `sfb_revalidation_secret` |

## Non-settings UI

- Warning when `module_webhooks_enabled` is off  
- Log table from option `storefuse_bridge_webhook_log`  
- `#sfb-test-webhook`, `#sfb-clear-webhook-log`  

## v2

[setup.md](../../refactor-v2/pages/setup.md) - client cards UX, health check. [webhooks-isr.md](../../refactor-v2/pages/webhooks-isr.md) - ISR panel + log.
