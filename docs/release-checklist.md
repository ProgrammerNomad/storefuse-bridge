# Release checklist (StoreFuse Bridge)

## Pre-release

- [ ] Bump `STOREFUSE_BRIDGE_VERSION` and plugin header `Version`
- [ ] Update [CHANGELOG.md](../CHANGELOG.md) and [verified-routes.md](verified-routes.md) audit version
- [ ] Run CI (PHPCS + PHPUnit + `php -l` on changed PHP)
- [ ] Run [staging-smoke.md](staging-smoke.md) and [acceptance-gates.md](acceptance-gates.md) on staging

## Upgrade test (0.1 → current)

- [ ] Install previous zip, activate, save settings
- [ ] Upgrade plugin; confirm `storefuse_bridge_version` option updated
- [ ] Confirm REST `/status` reports new version
- [ ] Flush cache once from API & Tools if catalog looks stale

## Rollback

- [ ] Deactivate plugin (cache flush runs on deactivation)
- [ ] Restore previous plugin files; reactivate
- [ ] Verify WooCommerce checkout still works on native WC pages

## Production gates

- [ ] CORS origins configured for each browser client
- [ ] Webhook storefront URL is HTTPS and not a private IP
- [ ] No PII in WC logs from Bridge source
