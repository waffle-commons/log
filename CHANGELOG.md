# Changelog — waffle-commons/log

All notable changes to this component are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Released in lockstep with the Waffle Commons umbrella tag.

## [0.1.0-beta6] — 2026-09

**Theme: worker-safety audit coverage.**

### Changed
- **Worker-safety audit coverage.** This component was never audited by `wfl igor`: it had no `igor-php/igor-php` in `require-dev`, so the ecosystem runner silently skipped it for four releases. It now ships `igor.json`, the `composer igor` script, and the dev dependency, and is part of the 0-KO gate.

### Documentation
- The README now links into the central Diátaxis documentation tree (DOC-02).

## [0.1.0-beta5] — 2026-07-08

**Theme: static-analysis hardening.**

### Changed
- Enabled the Mago `cyclomatic-complexity` lint rule with a `threshold = 50` ratchet in [`mago.toml`](./mago.toml). Config-only change — no source or behavioural changes in this component since `0.1.0-beta4`.

## [0.1.0-beta4] — 2026-06-13

### Changed
- Lockstep version bump with the Beta-4 wave (security hardening, worker-mode diagnostics, and DX tooling landed in sibling components). No behavioural changes in this component since `0.1.0-beta3`.

## [0.1.0-beta3] — 2026-06-07

**Theme: identity federation & stateless persistence (ecosystem wave).**

### Changed
- Lockstep version bump; `composer.lock` refreshed with the beta-3 dependency wave.

## [0.1.0-beta2.1] — 2026-05-30

### Changed
- Lockstep re-tag of `0.1.0-beta2` (umbrella housekeeping patch) — no source changes in this component.

## [0.1.0-beta2] — 2026-05-29

### Changed
- Lockstep version bump only. No behavioural changes since `0.1.0-beta1`.
- `composer.lock` refreshed to align with the ecosystem-wide dependency wave.

## [0.1.0-beta1]

See the umbrella [CHANGELOG](../CHANGELOG.md#010-beta1) for the full Beta-1 narrative — PSR-3 `StreamLogger` (JSON) and `LogChannel` enum-style constants.
