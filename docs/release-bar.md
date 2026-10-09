# Release bar decision

**Date:** 2026-03-20  
**Context:** [Production readiness audit](https://github.com/ProgrammerNomad/storefuse-bridge/blob/main/docs/acceptance-gates.md)

## Decision

**Pursue full v1.0 success criteria** from the merged production readiness plan—not “tag 0.2.0 and stop.”

- Plugin semver moves to **1.0.0** when acceptance scaffolding, tests, and documentation gaps are closed.
- **0.2.0** remains the changelog entry for the initial critical-fix pass; **1.0.0** is the production-gate release.
- Manual staging smoke is documented in [staging-smoke.md](staging-smoke.md); operators run it on real WP+WC before production cutover.

## Out of scope (unchanged)

- JWT auth module (spec only in [auth-strategy.md](auth-strategy.md))
- GDPR export/erasure endpoint
- WordPress.org submission as a hard deadline
