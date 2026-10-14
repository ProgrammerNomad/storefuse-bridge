# Production readiness implementation

Approved scope: the 14 October 2026 audit (F01–F26), covering Bridge and Flutter.
Status is evidence-based; source completion ≠ live acceptance on a populated store.

## Work queue

- [x] F01 authenticated session lifecycle and secure mobile persistence (source + unit tests; live proof pending)
- [x] F02/F14 checkout WebView handoff, navigation and payment recovery (source; device E2E pending)
- [x] F03/F04 authoritative locking and durable checkout replay (PHPUnit + `integration-local.py`)
- [x] F05/F06 shipping, address, terms and gateway contracts (headless form + config fields)
- [x] F07 content-cache isolation and persistent-cache invalidation
- [x] F08 privacy request flow and policy/support links (checkout terms link + policies)
- [x] F09 reproducible local deployment and package verification (`scripts/deploy-local.ps1`, `docs/BASELINE.md`)
- [x] F10 release signing and transport configuration (`android/app/build.gradle.kts` guard)
- [x] F11 secret storage and redacted logging (no bodies; prod blocks `ENABLE_HTTP_LOGS`)
- [x] F12 registration policy and abuse limits (`throttle_by_ip` on register + PHPUnit)
- [x] F13 exclusive scoped CORS policy (`class-cors.php`, `docs/cors-policy.md`)
- [x] F15 variations, stock, images and sale presentation (partial; variable fixture added)
- [x] F16/F17 merchandising and checkout setting parity (homepage + redirect URL)
- [x] F18 stock alert lifecycle (signup API + UI gated on `stock_alerts` feature)
- [x] F19 truthful capability contracts and order service actions (`docs/push-notifications.md` in Flutter)
- [x] F20 shop-manager settings permissions (`manage_woocommerce` capability filter)
- [x] F21 module-aware navigation (shell + router redirects)
- [x] F22 share and deep-link configuration (Android manifest + iOS scheme + `StoreDeepLinks`)
- [x] F23 international addresses, locale and currency (`default_country` in settings + checkout config)
- [x] F24 error recovery, refresh and preference persistence (bootstrap refresh on home pull; theme persisted)
- [x] F25 meaningful regression/integration tests and quality gates (CI PHP 8.1+, integration script)
- [x] F26 setup, extension and release documentation (README, acceptance gates, baseline)
- [ ] Populated local store and Android purchase acceptance (manual)
- [ ] Admin onboarding, merchandising preview and accessibility review (manual)

## Baseline

Deploy and record versions:

```powershell
./scripts/deploy-local.ps1 -WordPressPath 'C:\xampp\htdocs\wordpress' -RecordBaseline
php scripts/local-fixtures.php C:\xampp\htdocs\wordpress %TEMP%\storefuse-fixtures
python scripts/integration-local.py %TEMP%\storefuse-fixtures\fixture-credentials.json
```

See [BASELINE.md](BASELINE.md).

## Verification log

- Bridge PHPUnit: run `vendor/bin/phpunit` (target green on PHP 8.1+).
- Flutter: `flutter test`, `flutter analyze`.
- Live: [VERIFIED_CHECKLIST.md](../../storefuse-flutter/docs/VERIFIED_CHECKLIST.md) scenarios 1–12 on a seeded store.
