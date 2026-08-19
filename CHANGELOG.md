# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

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
