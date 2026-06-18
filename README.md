# Tornevall Tools for WordPress

Tornevall Tools for WordPress adds a server-side AI connector layer to the WordPress block editor.

The first implementation supports two providers:

1. Tornevall Networks Tools AI through `https://tools.tornevall.net/api/ai/internal/respond`.
2. Direct OpenAI access through the OpenAI Responses API.

The plugin is intentionally WordPress-native. API tokens are configured in wp-admin and used only from PHP. The block editor calls a local WordPress REST endpoint, and that endpoint forwards sanitized requests to the selected provider.

## Current status

Version `0.1.0` is the initial implementation.

Implemented now:

- wp-admin settings page under `Settings -> Tornevall Tools AI`.
- Configurable default provider.
- Configurable default persona.
- Configurable OpenAI API token and model.
- Configurable Tornevall Tools AI bearer token, endpoint, client slug, and optional model override.
- Block editor sidebar named `Tornevall AI`.
- Block inserter block named `Tornevall AI Assistant` in the `Text` category.
- Server-side REST endpoint at `/wp-json/ttfw/v1/ai/respond`.
- Insert and replace controls for generated content.
- Tokens are not localized to JavaScript.
- Settings sanitization and output escaping.
- REST permission callback based on `edit_posts`.

Not implemented yet:

- Streaming responses.
- Per-user token storage.
- Full conversation history.
- Image generation.
- Provider model discovery.
- Automated test suite.

## Requirements

- WordPress 6.5 or newer.
- PHP 7.4 or newer.
- A user with `manage_options` to configure the plugin.
- Editor users need `edit_posts` to use the REST endpoint.
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
  "context": "Selected block context and optional persona",
  "user_prompt": "Editor instruction",
  "client_name": "Tornevall Tools for WordPress",
  "client_version": "0.1.0",
  "client_platform": "wordpress"
}
```

The `client_slug` should be stable. Tools can use it for server-side defaults, audit trails, and usage statistics. This endpoint is not treated as a generic OpenAI-compatible endpoint because it expects Tools-specific fields.

### OpenAI direct

The OpenAI connector calls:

```text
https://api.openai.com/v1/responses
```

The plugin sends a Responses API request with `model`, `input[]`, a `developer` message containing the configured persona, and a `user` message containing selected block context plus the editor prompt.

The default model is currently `gpt-4o-mini`, but this must be reviewed before release and can be changed in wp-admin.

## Editor UI

The plugin exposes two editor entry points:

1. `Tornevall AI` sidebar in the editor plugin sidebar area.
2. `Tornevall AI Assistant` block in the block inserter `Text` category.

The sidebar is the primary workflow. It reads the currently selected block or blocks as context, sends that context to the WordPress REST endpoint, and can insert the result after the selection or replace the selection.

The block exists so the assistant can also be discovered where writing-related blocks are normally found. It can store generated HTML into the block content.

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

## Security notes

- Tokens are stored in the WordPress options table.
- Tokens are never passed to JavaScript.
- Token input fields are blank on render. A blank submitted token preserves the stored token.
- The block editor calls only the local WordPress REST endpoint.
- The REST endpoint requires an authenticated user with `edit_posts`.
- Settings require `manage_options`.
- Admin output is escaped.
- Settings and REST input are sanitized before use.
- Remote requests use WordPress HTTP APIs.

## Development structure

```text
tornevall-tools-for-wordpress.php  Main plugin bootstrap
includes/class-ttfw-plugin.php      Hooks and editor asset loading
includes/class-ttfw-settings.php    Settings API and sanitization
includes/class-ttfw-rest-controller.php REST endpoint
includes/class-ttfw-ai-service.php  Provider adapters
assets/editor.js                    Block editor UI, no build step yet
assets/editor.css                   Editor styles
readme.txt                          WordPress.org-style plugin readme
README.md                           Project and developer documentation
CHANGELOG.md                        Release history, always update
AGENTS.md                           Development rules for future agents
uninstall.php                       Option cleanup on uninstall
```

## Local installation

1. Copy the plugin directory to `wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate `Tornevall Tools for WordPress` in wp-admin.
3. Open `Settings -> Tornevall Tools AI`.
4. Configure at least one provider token.
5. Open the block editor and use `Tornevall AI` from the editor sidebar or insert the `Tornevall AI Assistant` block.

## Testing checklist

Before merging a change:

- Activate the plugin without PHP fatal errors.
- Save settings with empty token fields and confirm existing token values are preserved.
- Save settings with invalid Tools URL and confirm fallback to the default URL.
- Open the block editor with no JavaScript console errors.
- Generate through Tools AI using a valid token.
- Generate through OpenAI direct using a valid token.
- Confirm generated text can be inserted after selected blocks.
- Confirm generated text can replace selected blocks.
- Confirm users without `edit_posts` cannot call the REST endpoint.
- Confirm settings are available only to `manage_options` users.
- Run PHP lint for all PHP files.
- Run WordPress Coding Standards when available.

## Suggested next development steps

1. Add automated PHPUnit tests for settings sanitization and REST permission handling.
2. Add JavaScript unit or E2E tests for the editor flows.
3. Add provider health checks from wp-admin.
4. Add model discovery where the provider supports it.
5. Add support for per-role or per-user provider restrictions.
6. Add opt-in request logging without storing secrets or full private post content.
7. Add streaming once the provider and editor UX are ready.

## License

GPL-2.0-or-later.
