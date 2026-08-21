# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

## [Unreleased]

### Added

- Added an explicit Tools account pairing flow from wp-admin. The administrator is sent to `tools.tornevall.net`, signs in there and approves the WordPress installation before any managed credential is issued.
- Added DNSBL/FraudBL account pairing that can select an existing active non-admin token, rotate it in place and return its new secret directly to this WordPress installation through the one-time server-side exchange.
- Added an explicit alternative on the Tools approval page for creating a separate non-admin DNSBL site token instead of rotating the selected token.
- Added Guestbook managed credentials through the same Tools pairing API.
- Added the optional `tornevall_dnsbl_managed_api_token` server-side filter bridge for the separate DNSBL WordPress plugin.
- Added a Tools account status card that shows granted services, DNSBL credential mode and permission metadata without rendering raw credentials.

### Changed

- The pairing client now accepts the generic `tools_connection` callback state while retaining compatibility with the original `ttfw_connection` field.
- Guestbook uses a managed Tools credential automatically when no manual Guestbook token is configured. A manual token remains the explicit override.
- The shared Tools API client now has a separate unauthenticated request method used only for the public pairing endpoints; authenticated service requests still require a token.
- Updated the WordPress.org `Tested up to` value to 7.1 after exercising that version through the official Plugin Check runner.
- Added translator context for placeholder-based Guestbook admin strings and versioned the Turnstile script enqueue.
- Kept Cloudflare Turnstile as an explicitly documented external service while narrowing the Plugin Check exception to its exact offloaded-content diagnostic code.

### Security and privacy

- Existing DNSBL token secrets are never displayed or returned before rotation. When rotation is approved, Tools generates a new secret, invalidates the previous one and returns only the new value once.
- Admin DNSBL tokens are never installed directly in WordPress. Selecting one causes Tools to create a non-admin copy of its effective DNSBL permissions instead.
- Pairing state is kept in a short-lived per-admin transient and credential exchange happens server-to-server.
- Managed credentials remain server-side and are never displayed in wp-admin HTML or browser JavaScript.
- Connecting to Tools is an explicit administrator action; plugin activation does not start pairing or make authenticated account requests.
- Rotating an existing token may require other clients using its previous value to be updated; Tools warns about this before approval.

## [0.2.1] - 2026-08-21

### Added

- Added a dedicated **Tornevall Tools -> Guestbook connection** page that loads the configured Tools user's owned guestbooks on demand.
- Added explicit selection of the one Tools guestbook used by this WordPress installation.
- Added remote guestbook creation when the configured token has both `guestbook.write` and `guestbook.moderate`.
- New remotely created guestbooks can initialize Tools site context from the WordPress URL, locale and site description.

### Changed

- Public guestbook reads, submissions and WordPress moderation listings now add the stored guestbook selector server-side, so multiple guestbooks owned by one Tools account stay isolated.
- Replacing the Guestbook token clears the previous guestbook selection.
- The Tools module overview now distinguishes a configured token from a completed guestbook selection.

### Security and privacy

- The selected guestbook cannot be overridden from public browser input.
- Guestbook catalog/creation requests are made only from explicit admin setup actions; plugin activation does not make authenticated guestbook catalog requests.
- Tools and Turnstile credentials remain server-side, and each WordPress installation continues to use its own Turnstile configuration.

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
