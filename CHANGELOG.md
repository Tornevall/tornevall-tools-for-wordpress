# Changelog

All notable changes to Tornevall Tools for WordPress are documented here.

## [0.3.0] - 2026-06-18

### Added

- Added an inline Tornevall AI rewrite menu to selected block toolbars.
- Added one-click block actions for rephrase, simplify, summarize, expand, shorten, clarify, tone changes, and Swedish or English translation.
- Added an `Ask AI Assistant` modal for custom per-block rewrite instructions.
- Added shared editor AI request helper so the sidebar, assistant block, and inline toolbar use the same server-side endpoint.

### Changed

- Bumped plugin version to `0.3.0`.
- Added the WordPress `wp-hooks` editor script dependency so the plugin can extend `editor.BlockEdit` safely.

## [0.2.0] - 2026-06-18

### Added

- Added separate editor `Instructions` and `Custom text` fields.
- Added custom text support in server-side AI payloads.
- Added Markdown-oriented output instructions for AI responses.
- Added Markdown-to-WordPress-block conversion for editor insert and replace actions.
- Added document upload extraction endpoint at `/wp-json/ttfw/v1/document/extract`.
- Added best-effort text extraction for `.txt`, `.md`, `.html`, `.htm`, `.docx`, `.doc`, and `.pdf` uploads.
- Added wp-admin provider test actions for Tools AI and OpenAI.
- Added editor upload controls that place extracted document text into the custom text field.

### Changed

- Updated default persona to prefer clean Markdown that can be converted to WordPress blocks.
- Bumped plugin version to `0.2.0`.
- Expanded editor help text to explain selected-block context and custom text context separately.

### Security

- Document extraction requires `edit_posts` and `upload_files`.
- Provider tests require `manage_options` and a provider-specific nonce.
- Uploaded documents are read for text extraction and are not persisted by the plugin.
- Document uploads are validated by extension, WordPress file type checks, and a 10 MB plugin limit.

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

- Kept provider credentials server-side.
- Added settings sanitization.
- Added admin output escaping.
- Added REST permission callback requiring `edit_posts`.
- Added settings capability requirement through `manage_options`.
