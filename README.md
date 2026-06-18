# Tornevall Tools for WordPress

Tornevall Tools for WordPress adds a server-side AI connector layer to the WordPress block editor.

The plugin supports two providers:

1. Tornevall Networks Tools AI through `https://tools.tornevall.net/api/ai/internal/respond`.
2. Direct OpenAI access through the OpenAI Responses API.

The plugin is WordPress-native. API tokens are configured in wp-admin and used only from PHP. The block editor calls local WordPress REST endpoints, and PHP forwards sanitized requests to the selected provider.

## Current status

Version `0.2.0` extends the initial editor assistant with custom source text, document upload extraction, Markdown-to-block insertion, and wp-admin token tests.

Implemented now:

- wp-admin settings page under `Settings -> Tornevall Tools AI`.
- Configurable default provider.
- Configurable default persona.
- Configurable OpenAI API token and model.
- Configurable Tornevall Tools AI bearer token, endpoint, client slug, and optional model override.
- Token test buttons for both configured providers in wp-admin.
- Block editor sidebar named `Tornevall AI`.
- Block inserter block named `Tornevall AI Assistant` in the `Text` category.
- Separate `Instructions` field and `Custom text` field.
- Optional document upload into the custom text field.
- Server-side text extraction for `.txt`, `.md`, `.html`, `.htm`, `.docx`, `.doc`, and `.pdf`.
- Markdown-friendly AI output converted into WordPress-compatible blocks when inserted.
- Local REST endpoint at `/wp-json/ttfw/v1/ai/respond`.
- Local REST endpoint at `/wp-json/ttfw/v1/document/extract`.
- Insert and replace controls for generated content.
- Tokens are not localized to JavaScript.
- Settings sanitization and output escaping.
- REST permission callbacks based on `edit_posts`, `upload_files`, and `manage_options` where relevant.

Not implemented yet:

- Streaming responses.
- Per-user token storage.
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
- Editor users need `edit_posts` to use the AI endpoint.
- Editor users need `upload_files` to upload documents for extraction.
- For OpenAI direct mode: an OpenAI API key and model name.
- For Tornevall Tools AI mode: a Tools bearer token with the correct AI scope for the configured endpoint.

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
  "client_version": "0.2.0",
  "client_platform": "wordpress"
}
```

The `client_slug` should be stable. Tools can use it for server-side defaults, audit trails, and usage statistics. This endpoint is not treated as a generic OpenAI-compatible endpoint because it expects Tools-specific fields.

### OpenAI direct

The OpenAI connector calls:

```text
https://api.openai.com/v1/responses
```

The plugin sends a Responses API request with `model`, `input[]`, a `developer` message containing the configured persona, and a `user` message containing selected block context, custom text, editor instructions, and output format guidance.

The default model is currently `gpt-4o-mini`, but this must be reviewed before release and can be changed in wp-admin.

## Editor UI

The plugin exposes two editor entry points:

1. `Tornevall AI` sidebar in the editor plugin sidebar area.
2. `Tornevall AI Assistant` block in the block inserter `Text` category.

The editor UI has separate fields:

- `Instructions`: what AI should do.
- `Custom text`: source text to rewrite, clean up, summarize, or convert.
- `Persona override`: optional per-request override.

Selected blocks are still sent as context. Custom text is sent separately so instructions like "rewrite this completely" can work without confusing the instruction text with source text.

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

The editor can upload a document and extract text into the custom text field. The uploaded file is not sent to OpenAI or Tools directly. PHP extracts text first, and the editor then sends text through the normal AI request route.

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
| OpenAI API token | Direct OpenAI API key. Blank field preserves existing value. |
| OpenAI model | Model name for direct OpenAI requests. |
| Tools AI token | Bearer token for Tools AI. Blank field preserves existing value. |
| Tools AI endpoint | Tools endpoint URL. HTTPS only. |
| Tools client slug | Stable client identifier sent to Tools. |
| Tools model override | Optional model override. Blank uses Tools-side defaults. |
| Response language | `auto`, `sv`, `en`, `da`, `no`, `de`, `fr`, or `es`. |
| HTTP timeout | 5-120 seconds. |

The same page includes token test buttons for Tools AI and OpenAI. The tests use saved settings only, so save changed tokens before testing.

## Security notes

- Tokens are stored in the WordPress options table.
- Tokens are never passed to JavaScript.
- Token input fields are blank on render. A blank submitted token preserves the stored token.
- The block editor calls only local WordPress REST endpoints.
- The AI endpoint requires an authenticated user with `edit_posts`.
- Document extraction requires `edit_posts` and `upload_files`.
- Settings and token tests require `manage_options`.
- Admin output is escaped.
- Settings and REST input are sanitized before use.
- Remote requests use WordPress HTTP APIs.
- Uploaded documents are read for extraction and are not persisted by the plugin.

## Development structure

```text
tornevall-tools-for-wordpress.php       Main plugin bootstrap
includes/class-ttfw-plugin.php          Hooks and editor asset loading
includes/class-ttfw-settings.php        Settings API, token tests, sanitization
includes/class-ttfw-rest-controller.php REST endpoints
includes/class-ttfw-ai-service.php      Provider adapters
includes/class-ttfw-document-extractor.php Document text extraction
assets/editor.js                        Block editor UI, no build step yet
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
4. Configure at least one provider token.
5. Save settings.
6. Test the configured provider token from the same settings page.
7. Open the block editor and use `Tornevall AI` from the editor sidebar or insert the `Tornevall AI Assistant` block.

## Testing checklist

Before merging a change:

- Activate the plugin without PHP fatal errors.
- Save settings with empty token fields and confirm existing token values are preserved.
- Save settings with invalid Tools URL and confirm fallback to the default URL.
- Test the saved Tools AI token from wp-admin.
- Test the saved OpenAI token from wp-admin.
- Open the block editor with no JavaScript console errors.
- Generate through Tools AI using pasted custom text.
- Generate through OpenAI direct using pasted custom text.
- Upload `.txt` or `.md` and confirm text appears in the custom text field.
- Upload `.docx` and confirm text extraction works when PHP Zip is available.
- Upload `.pdf` and confirm best-effort extraction or a clear error/warning.
- Confirm generated Markdown can be inserted as WordPress blocks.
- Confirm generated Markdown can replace selected blocks.
- Confirm users without `edit_posts` cannot call the AI endpoint.
- Confirm users without `upload_files` cannot call the document extraction endpoint.
- Confirm settings are available only to `manage_options` users.
- Run PHP lint for all PHP files.
- Run WordPress Coding Standards when available.

## Suggested next development steps

1. Add automated PHPUnit tests for settings sanitization, token tests, extraction permissions, and REST permission handling.
2. Add JavaScript unit or E2E tests for the editor flows.
3. Add richer Markdown parsing or a build-step-supported parser if needed.
4. Add server-side provider health checks with more diagnostics.
5. Add model discovery where the provider supports it.
6. Add support for per-role or per-user provider restrictions.
7. Add opt-in request logging without storing secrets or full private post content.
8. Add streaming once the provider and editor UX are ready.
9. Add OCR or external extractor integrations as explicit optional dependencies.

## License

GPL-2.0-or-later.
