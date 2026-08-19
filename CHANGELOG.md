# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

## [0.1.2] - 2026-08-19

### Added

- Dedicated server-side Tools guestbook API settings and token storage.
- Public WordPress guestbook signing through `/wp-json/ttfw/v1/guestbook/entries`, which forwards submissions to the central Tools guestbook without exposing the Tools token.
- WordPress admin page under `Tools -> Tools Guestbook` for searching, filtering, hiding and restoring central guestbook entries.
- Recommended add-ons panel for the optional Tornevall Networks DNSBL Implementation plugin, including install/activate actions when permitted.
- Optional DNSBL guestbook filtering: when the DNSBL addon is active, listed visitor IPs can be rejected before forwarding to Tools.
- Optional administrator DNSBL check and explicit abuse-report controls in the WordPress guestbook admin.

### Changed

- `[tornevall_guestbook]` now keeps the existing themed Tools embed and adds a local signing form when a guestbook API token is configured.
- The guestbook remains fully usable without the DNSBL addon; only DNSBL-specific controls disappear.

### Security

- Guestbook API and DNSBL tokens remain server-side and are never localized to public JavaScript or rendered in public markup.
- Blank guestbook-token settings preserve the stored token.
- Public guestbook submissions have a local rate limit and honeypot before server-to-server forwarding.
- DNSBL blacklist publication is never automatic; reporting requires a WordPress administrator and DNSBL add permission.

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
- wp-admin settings page under `Settings -> Tornevall Tools AI`.
- Configurable default provider.
- Configurable default persona.
- Configurable direct OpenAI token and model.
- Configurable Tornevall Tools AI token, endpoint, client slug, and optional model override.
- Local WordPress REST endpoint at `/wp-json/ttfw/v1/ai/respond`.
- Server-side adapter for Tornevall Tools AI internal endpoint.
- Server-side adapter for OpenAI Responses API.
- Block editor sidebar named `Tornevall AI`.
- Block inserter block named `Tornevall AI Assistant` in the `Text` category.
- Insert-after-selection and replace-selection controls.
- `README.md`, `readme.txt`, `CHANGELOG.md`, and `AGENTS.md` project documentation.

### Security

- Kept provider tokens server-side.
- Added settings sanitization.
- Added admin output escaping.
- Added REST permission callback requiring `edit_posts`.
- Added settings capability requirement through `manage_options`.
