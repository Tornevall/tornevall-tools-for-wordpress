# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

## [0.2.0] - 2026-08-19

### Added

- Added a top-level `Tornevall Tools` wp-admin page.
- Added a small module registry for independently enabled Tools features.
- Added a shared server-side API client restricted to documented `https://tools.tornevall.net/api/*` requests.
- Added the first Dynamic DNS module.
- Added manual Dynamic DNS updates with `manage_options` and nonce protection.
- Added scheduled Dynamic DNS updates through WordPress WP-Cron.
- Added configurable hourly, twice-daily, and daily update intervals.
- Added server-side Dynamic DNS token storage with blank-field preservation.
- Added last-run Dynamic DNS status in wp-admin.
- Added explicit external-service documentation for Tornevall Networks Tools.

### Changed

- Refocused the public plugin from an AI-first editor integration to the general Tornevall Tools for WordPress product.
- Updated plugin metadata, README, and WordPress.org readme wording to describe a modular Tools plugin.

### Removed

- Removed the AI service, AI REST controller, editor sidebar/block JavaScript, and AI editor CSS from the `main` release line.
- DNSBL/FraudBL remains outside this plugin because it has its own maintained WordPress plugin.

### Security and privacy

- Dynamic DNS is disabled by default and does not make remote requests until explicitly enabled and configured.
- Dynamic DNS credentials remain server-side.
- Remote API requests are restricted to the fixed Tornevall Networks Tools service origin.
- Manual update actions require administrator capability and a WordPress nonce.

## [0.1.0] - 2026-06-18

### Added

- Initial plugin bootstrap.
- Initial wp-admin settings page.
- Initial server-side Tornevall Tools AI connector.
- Initial server-side OpenAI connector.
- Initial block editor sidebar and AI Assistant block prototype.

## Development note

The AI editor work continues separately in PR #3 and is not part of the current public `main` release line. It can be reintroduced later as an optional module after production hardening and current WordPress integration work are complete.
