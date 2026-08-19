# Tornevall Tools for WordPress

Tornevall Tools for WordPress adds server-side AI connectors and Tornevall Networks Tools integrations to WordPress.

The AI implementation supports two providers:

1. Tornevall Networks Tools AI through `https://tools.tornevall.net/api/ai/internal/respond`.
2. Direct OpenAI access through the OpenAI Responses API.

The plugin is intentionally WordPress-native. API tokens are configured in wp-admin and used only from PHP. The block editor calls a local WordPress REST endpoint, and that endpoint forwards sanitized requests to the selected provider.

## Current status

Version `0.1.1` adds the first public Tools integration outside the editor: an embeddable Tools guestbook shortcode.

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
- Public `[tornevall_guestbook]` shortcode backed by the Tools guestbook JavaScript embed.
- Guestbook themes: `tools`, `miazma`, and `terminal`.
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
- Native Gutenberg guestbook block. The shortcode is the first integration and test surface.

## Requirements

- WordPress 6.5 or newer.
- PHP 7.4 or newer.
- A user with `manage_options` to configure the AI connectors.
- Editor users need `edit_posts` to use the AI REST endpoint.
- For OpenAI direct mode: an OpenAI API key and model name.
- For Tornevall Tools AI mode: a Tools bearer token with the correct AI scope for the configured endpoint.
- For the guestbook shortcode: public HTTPS access to `tools.tornevall.net`.

## Guestbook embed

The plugin can embed the public Tools guestbook in any post, page, or widget area that processes WordPress shortcodes.

Basic usage:

```text
[tornevall_guestbook]
```

Choose a theme and number of entries:

```text
[tornevall_guestbook theme="miazma" limit="10"]
```

Available themes:

- `tools` - modern default styling.
- `miazma` - modernized black and blue Miazmabook styling.
- `terminal` - compact dark monospace styling.

`limit` is clamped to 1-50 entries.

The shortcode creates a unique target element and enqueues the Tools JavaScript entry at:

```text
https://tools.tornevall.net/guestbook/embed.js
```

The remote widget renders inside Shadow DOM, so the active WordPress theme should not alter the guestbook presentation. The embed contains public guestbook data only. E-mail addresses and AI/provider tokens are never included.

For staging or local integration testing, developers can override the embed URL without adding an admin-facing setting:

```php
add_filter(
    'ttfw_guestbook_embed_url',
    static function () {
        return 'https://staging.example.test/guestbook/embed.js';
    }
);
```

Only valid HTTPS URLs are accepted. Invalid overrides fall back to the production Tools endpoint.

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
  "client_version": "0.1.1",
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
- The guestbook embed is public and token-free.
- The guestbook embed URL must use HTTPS.

## Development structure

```text
tornevall-tools-for-wordpress.php  Main plugin bootstrap
includes/class-ttfw-plugin.php      Hooks and editor asset loading
includes/class-ttfw-settings.php    Settings API and sanitization
includes/class-ttfw-rest-controller.php REST endpoint
includes/class-ttfw-ai-service.php  Provider adapters
includes/class-ttfw-guestbook.php   Public Tools guestbook shortcode integration
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
3. Open `Settings -> Tornevall Tools AI` to configure AI providers when needed.
4. Open the block editor and use `Tornevall AI` from the editor sidebar or insert the `Tornevall AI Assistant` block.
5. To test the Tools guestbook, add `[tornevall_guestbook theme="miazma" limit="10"]` to a page and view the page on the frontend.

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
- Add `[tornevall_guestbook]` to a public page and confirm the widget renders.
- Test `tools`, `miazma`, and `terminal` themes.
- Confirm an invalid shortcode theme falls back to `tools`.
- Confirm `limit` stays within 1-50.
- Confirm no provider token appears in the guestbook page source or network request.
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
8. Promote the guestbook shortcode into a native Gutenberg block if the embed contract proves stable.

## License

GPL-2.0-or-later.
