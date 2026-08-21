# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

## [Unreleased]

### Changed

- Updated the WordPress.org `Tested up to` value to 7.1 after exercising that version through the official Plugin Check runner.
- Added translator context for placeholder-based Guestbook admin strings and versioned the Turnstile script enqueue.
- Kept Cloudflare Turnstile as an explicitly documented external service while narrowing the Plugin Check exception to its exact offloaded-content diagnostic code.

## [0.2.0] - 2026-08-19

### Added

- General Tornevall Tools integration foundation and top-level Tools overview.
- Shared server-side Tools API client restricted to the official Tools origin.
- Dynamic DNS configuration, manual updates and WP-Cron scheduling.
- Dynamic DNS status handling with server-side token storage.
- Owner-scoped Tools Guestbook integration from the 0.1.1/0.1.2 line, including local WordPress REST proxying, moderation and local frontend assets.
- Cloudflare Turnstile support for public guestbook signing.
- Optional Guestbook-to-DNSBL bridge that only appears when the separate Tornevall DNSBL plugin is installed and exposes the required capabilities.

### Changed

- Established Tornevall Tools for WordPress as the WordPress client/integration layer for selected Tornevall Networks Tools services.
- Guestbook and Dynamic DNS are the first release features.
- Updated WordPress.org documentation around actual Tools integrations and external-service data flows.

### Removed

- Removed the direct OpenAI runtime client, Tools AI runtime client, AI REST endpoint and Gutenberg AI editor assets from the public release line.

### Security and privacy

- Guestbook and Dynamic DNS credentials remain server-side.
- Dynamic DNS is disabled by default.
- Guestbook owner-scoping is enforced through the configured Tools token.
- Public guestbook writes validate Turnstile server-side when signing is enabled.
- DNSBL functionality is not duplicated; optional controls call the separate DNSBL plugin bridge only when available.

## [0.1.2] - 2026-08-19

- Added owner-scoped Guestbook API proxying and moderation.
- Added Cloudflare Turnstile protection for public signing.
- Added optional integration with the standalone Tornevall DNSBL plugin.

## [0.1.1] - 2026-08-19

- Added the first public Tools Guestbook shortcode integration.

## [0.1.0] - 2026-06-18

- Initial development prototype focused on AI editor connectors.
