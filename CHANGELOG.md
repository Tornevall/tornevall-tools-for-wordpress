# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

## [0.1.2] - 2026-08-19

### Added

- Dedicated server-side Tools guestbook API settings and token storage.
- Owner-scoped public guestbook reads through local WordPress REST; the browser never receives the Tools token.
- Public WordPress guestbook signing through `/wp-json/ttfw/v1/guestbook/entries`.
- Per-site Cloudflare Turnstile site-key/secret settings and server-side Siteverify validation.
- Exact Turnstile hostname and `guestbook` action validation before forwarding a visitor post.
- WordPress admin page under `Tools -> Tools Guestbook` for searching, filtering, hiding and restoring entries owned by the configured guestbook token.
- Recommended add-ons panel for the optional Tornevall Networks DNSBL Implementation plugin, including install/activate actions when permitted.
- Optional DNSBL guestbook filtering and explicit administrator check/report controls.

### Changed

- `[tornevall_guestbook]` now renders only visible entries owned by the configured Tools API key instead of the global Tools guestbook feed.
- The public list continues to use Shadow DOM and supports `tools`, `miazma`, and `terminal` themes.
- Remote moderation is limited to entries owned by the exact configured guestbook token.
- The guestbook remains usable without the DNSBL addon; DNSBL-specific controls do not exist when the addon is absent.

### Security

- Guestbook API and DNSBL tokens remain server-side and are never localized to public JavaScript or rendered in public markup.
- Turnstile secret remains server-side; only the public site key is rendered.
- Public signing fails closed when Turnstile is not configured or Siteverify returns another hostname/action.
- Blank secret/token settings preserve stored values.
- Public guestbook submissions have a local rate limit and honeypot before server-to-server forwarding.
- Foreign guestbook entries cannot be enumerated or moderated by another token.
- DNSBL blacklist publication is never automatic; reporting requires a WordPress administrator, the optional DNSBL addon, and DNSBL add permission.

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
