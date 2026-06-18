# Tornevall Tools for WordPress

Tornevall Tools for WordPress adds a server-side AI connector layer to the WordPress block editor.

The plugin supports two providers:

1. Tornevall Networks Tools AI through `https://tools.tornevall.net/api/ai/internal/respond`.
2. Direct OpenAI access through the OpenAI Responses API.

The plugin is WordPress-native. Provider credentials are configured in wp-admin and used only from PHP. The block editor calls local WordPress REST endpoints, and PHP forwards sanitized requests to the selected provider.

## Current status

Version `0.3.0` adds inline per-block rewrite controls on top of the sidebar, custom text, document upload, Markdown conversion, and wp-admin provider tests.

Implemented now:

- wp-admin settings page under `Settings -> Tornevall Tools AI`.
- Configurable default provider and persona.
- Configurable OpenAI and Tornevall Tools AI provider settings.
- Provider test buttons in wp-admin.
- Block editor sidebar named `Tornevall AI`.
- Block inserter block named `Tornevall AI Assistant` in the `Text` category.
- Inline Tornevall AI rewrite menu in selected block toolbars.
- Per-block actions: ask, rephrase, simplify, summarize, expand, shorten, clarify, tone changes, and translation.
- Separate `Instructions` field and `Custom text` field.
- Optional document upload into the custom text field.
- Server-side text extraction for `.txt`, `.md`, `.html`, `.htm`, `.docx`, `.doc`, and `.pdf`.
- Markdown-friendly AI output converted into WordPress-compatible blocks when inserted.
- Local REST endpoint at `/wp-json/ttfw/v1/ai/respond`.
- Local REST endpoint at `/wp-json/ttfw/v1/document/extract`.
- Insert and replace controls for generated content.

Not implemented yet:

- Streaming responses.
- Per-user provider configuration.
- Full conversation history.
- Image generation.
- Provider model discovery.
- Full OCR for scanned PDFs.
- Dependency-backed legacy `.doc` parsing.
- Automated test suite.

## Requirements

- WordPress 6.5 or newer.
- PHP 7.4 or newer.
- PHP Zip extension for `.docx` extraction.
- A user with `manage_options` to configure and test providers.
- Editor users need `edit_posts` to use the AI endpoint and inline toolbar actions.
- Editor users need `upload_files` to upload documents for extraction.

## Provider behavior

### Tornevall Tools AI

Default endpoint:

```text
https://tools.tornevall.net/api/ai/internal/respond
```

The plugin sends the Tools-specific request shape:

```json
{
  "client_slug": "tornevall_tools_wordpress",
  "context": "Persona, selected block context, custom text, and output instructions",
  "user_prompt": "Editor instructions",
  "client_name": "Tornevall Tools for WordPress",
  "client_version": "0.3.0",
  "client_platform": "wordpress"
}
```

The `client_slug` should be stable. This endpoint is not treated as a generic OpenAI-compatible endpoint because it expects Tools-specific fields.

### OpenAI direct

The OpenAI connector calls:

```text
https://api.openai.com/v1/responses
```

The plugin sends a Responses API request with `model`, `input[]`, a `developer` message containing the configured persona, and a `user` message containing selected block context, custom text, editor instructions, and output format guidance.

## Editor UI

The plugin exposes three editor entry points:

1. `Tornevall AI` sidebar in the editor plugin sidebar area.
2. `Tornevall AI Assistant` block in the block inserter `Text` category.
3. Inline Tornevall AI rewrite menu in selected block toolbars.

The inline menu is intended for quick block rewrites, similar to AI rewrite menus in other editor integrations. It works on the selected block text and replaces the current block with generated WordPress-compatible blocks.

Inline actions currently include:

- Ask AI Assistant.
- Rephrase.
- Simplify.
- Summarize.
- Expand.
- Make shorter.
- Make clearer.
- More formal.
- More casual.
- Translate to Swedish.
- Translate to English.

The sidebar and assistant block still provide the broader workflow with separate fields:

- `Instructions`: what AI should do.
- `Custom text`: source text to rewrite, clean up, summarize, or convert.
- `Persona override`: optional per-request override.

## Markdown and WordPress output

AI is instructed to return clean Markdown by default. The editor converts supported Markdown into WordPress-compatible blocks when inserted:

- Paragraphs.
- Headings.
- Ordered and unordered lists.
- Blockquotes.
- Code blocks.
- Separators.
- Basic inline links, emphasis, strong text, and inline code.

This keeps the result editable in Gutenberg instead of inserting one large raw HTML block.

## Document uploads

The editor can upload a document and extract text into the custom text field. The uploaded file is not sent to AI providers directly. PHP extracts text first, and the editor then sends text through the normal AI request route.

Supported extensions:

- `.txt`
- `.md`
- `.html`
- `.htm`
- `.docx`
- `.doc`
- `.pdf`

Extraction notes:

- `.docx` requires PHP Zip.
- `.pdf` extraction is best-effort and does not perform OCR.
- Legacy `.doc` extraction is best-effort and should be reviewed manually.
- Maximum upload size is 10 MB.

## Settings

The settings page is located at:

```text
/wp-admin/options-general.php?page=tornevall-tools-ai
```

Available settings:

| Setting | Purpose |
| --- | --- |
| Default provider | `tools` or `openai`. |
| Default persona | Server-side default instruction used for both providers. |
| OpenAI API key | Direct OpenAI access. Blank field preserves existing value. |
| OpenAI model | Model name for direct OpenAI requests. |
| Tools AI key | Bearer value for Tools AI. Blank field preserves existing value. |
| Tools AI endpoint | Tools endpoint URL. HTTPS only. |
| Tools client slug | Stable client identifier sent to Tools. |
| Tools model override | Optional model override. Blank uses Tools-side defaults. |
| Response language | `auto`, `sv`, `en`, `da`, `no`, `de`, `fr`, or `es`. |
| HTTP timeout | 5-120 seconds. |

## Security notes

- Provider credentials are stored in the WordPress options table.
- Provider credentials are never passed to JavaScript.
- The block editor calls only local WordPress REST endpoints.
- The AI endpoint requires an authenticated user with `edit_posts`.
- Inline toolbar rewrite actions use the same authenticated AI endpoint.
- Document extraction requires `edit_posts` and `upload_files`.
- Settings and provider tests require `manage_options`.
- Admin output is escaped.
- Settings and REST input are sanitized before use.
- Remote requests use WordPress HTTP APIs.
- Uploaded documents are read for extraction and are not persisted by the plugin.

## Development structure

```text
tornevall-tools-for-wordpress.php       Main plugin bootstrap
includes/class-ttfw-plugin.php          Hooks and editor asset loading
includes/class-ttfw-settings.php        Settings API, provider tests, sanitization
includes/class-ttfw-rest-controller.php REST endpoints
includes/class-ttfw-ai-service.php      Provider adapters
includes/class-ttfw-document-extractor.php Document text extraction
assets/editor.js                        Sidebar, block, inline toolbar UI
assets/editor.css                       Editor styles
readme.txt                              WordPress.org-style plugin readme
README.md                               Project and developer documentation
CHANGELOG.md                            Release history, always update
AGENTS.md                               Development rules for future agents
uninstall.php                           Option cleanup on uninstall
```

## Local installation

1. Copy the plugin directory to `wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate `Tornevall Tools for WordPress` in wp-admin.
3. Open `Settings -> Tornevall Tools AI`.
4. Configure at least one provider.
5. Save settings.
6. Test the configured provider from the same settings page.
7. Open the block editor and use the sidebar, assistant block, or selected-block toolbar menu.

## Testing checklist

Before merging a change:

- Activate the plugin without PHP fatal errors.
- Save settings with empty credential fields and confirm existing values are preserved.
- Save settings with invalid Tools URL and confirm fallback to the default URL.
- Test the saved Tools AI provider from wp-admin.
- Test the saved OpenAI provider from wp-admin.
- Open the block editor with no JavaScript console errors.
- Confirm the inline Tornevall AI menu appears in selected block toolbars.
- Run rephrase, shorten, expand, summarize, and custom ask actions from a selected block.
- Confirm selected blocks are replaced with editable WordPress blocks.
- Generate through Tools AI using pasted custom text.
- Generate through OpenAI direct using pasted custom text.
- Upload `.txt` or `.md` and confirm text appears in the custom text field.
- Upload `.docx` and confirm text extraction works when PHP Zip is available.
- Upload `.pdf` and confirm best-effort extraction or a clear error/warning.
- Confirm users without `edit_posts` cannot call the AI endpoint.
- Confirm users without `upload_files` cannot call the document extraction endpoint.
- Confirm settings are available only to `manage_options` users.
- Run PHP lint for all PHP files.
- Run WordPress Coding Standards when available.
- Run `node --check assets/editor.js`.

## Suggested next development steps

1. Add automated PHPUnit tests for settings sanitization, provider tests, extraction permissions, and REST permission handling.
2. Add JavaScript unit or E2E tests for sidebar, block, and inline toolbar flows.
3. Add richer Markdown parsing or a build-step-supported parser if needed.
4. Add per-site configurable inline toolbar actions.
5. Add provider health checks with more diagnostics.
6. Add model discovery where the provider supports it.
7. Add support for per-role or per-user provider restrictions.
8. Add opt-in request logging without storing secrets or full private post content.
9. Add streaming once the provider and editor UX are ready.
10. Add OCR or external extractor integrations as explicit optional dependencies.

## License

GPL-2.0-or-later.
