# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

## [0.2.0] - 2026-08-19

### Added

- Added a top-level `Tornevall Tools` wp-admin page and module overview.
- Added a shared server-side API client restricted to documented `https://tools.tornevall.net/api/*` requests.
- Added the Dynamic DNS integration with manual updates, WP-Cron scheduling, server-side token storage, and last-run status.
- Kept the public `[tornevall_guestbook]` Tools guestbook integration introduced on `main` in 0.1.1.
- Added Guestbook to the Tools module overview alongside Dynamic DNS.
- Added explicit external-service documentation for both current Tools integrations.

### Changed

- Established Tornevall Tools for WordPress as the WordPress integration/client for selected Tornevall Networks Tools services.
- Reworked README and WordPress.org readme wording around actual Tools service integrations rather than a single feature category.
- Guestbook and Dynamic DNS are now presented together as the first public Tools feature set.

### Removed

- Removed the AI service, AI REST controller, editor sidebar/block JavaScript, and AI editor CSS from the public release line.
- DNSBL/FraudBL remains outside this plugin because it has its own maintained WordPress plugin.

### Security and privacy

- Dynamic DNS is disabled by default and does not make remote requests until explicitly enabled and configured.
- Dynamic DNS credentials remain server-side.
- Authenticated API requests are restricted to the fixed Tornevall Networks Tools service origin.
- Manual Dynamic DNS updates require administrator capability and a WordPress nonce.
- The guestbook integration is public and token-free, accepts only allow-listed presentation parameters, and requires an HTTPS service endpoint.

## [0.1.1] - 2026-08-19

### Added

- Public `[tornevall_guestbook]` shortcode for embedding the Tools guestbook.
- Guestbook theme selection through `theme="tools"`, `theme="miazma"`, or `theme="terminal"`.
- Guestbook entry limit through the `limit` shortcode attribute.
- `ttfw_guestbook_embed_url` developer filter for HTTPS staging/testing endpoints.

### Security

- The guestbook integration exposes no provider or API tokens.
- The embed endpoint is restricted to HTTPS and falls back to the production Tools URL when an invalid override is supplied.

## [0.1.0] - 2026-06-18

### Added

- Initial plugin bootstrap.
- Initial wp-admin settings page.
- Initial server-side Tornevall Tools AI connector.
- Initial server-side OpenAI connector.
- Initial block editor sidebar and AI Assistant block prototype.

## Development note

The AI editor work continues separately in PR #3 and is not part of the current public release line. It can return later as an optional Tools integration after production hardening and current WordPress integration work are complete.
