# Tornevall Tools for WordPress

Tornevall Tools for WordPress adds server-side AI connectors and Tornevall Networks Tools integrations to WordPress.

The AI implementation supports two providers:

1. Tornevall Networks Tools AI through `https://tools.tornevall.net/api/ai/internal/respond`.
2. Direct OpenAI access through the OpenAI Responses API.

The plugin is intentionally WordPress-native. API tokens are configured in wp-admin and used only from PHP. Browser-side features call local WordPress endpoints, and WordPress performs authenticated remote requests server-side.

## Current status

Version `0.1.2` turns the guestbook embed into a complete central guestbook client while keeping Tools as the authoritative database.

Implemented now:

- wp-admin settings page under `Settings -> Tornevall Tools AI`.
- Configurable OpenAI and Tools AI providers.
- Block editor sidebar and assistant block.
- Server-side AI REST endpoint at `/wp-json/ttfw/v1/ai/respond`.
- Public `[tornevall_guestbook]` shortcode backed by the Tools guestbook JavaScript embed.
- Guestbook themes: `tools`, `miazma`, and `terminal`.
- Dedicated guestbook API URL/token stored server-side.
- Public WordPress guestbook signing through `/wp-json/ttfw/v1/guestbook/entries`.
- WordPress admin guestbook under `Tools -> Tools Guestbook`.
- Central entry search, visibility filtering, DNSBL filtering and hide/restore controls.
- Recommended add-ons panel for Tornevall Networks DNSBL Implementation.
- Optional DNSBL visitor-IP blocking, checking and explicit administrator abuse reporting.
- Tokens are never localized to public JavaScript.

## Guestbook

The shortcode embeds entries from the central Tools guestbook:

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

The remote entry list renders inside Shadow DOM so the active WordPress theme does not take over the guestbook presentation.

### Public signing

When a guestbook API token is configured, the shortcode also renders a local sign form. Visitors do not receive the Tools token. The flow is:

```text
Visitor browser -> WordPress REST -> Tools guestbook API -> central guestbook database
```

The browser posts only to:

```text
/wp-json/ttfw/v1/guestbook/entries
```

WordPress then adds the stored token and forwards sanitized form fields, visitor IP and site identity to Tools.

The local submission path includes a honeypot and a short per-IP rate limit.

### Guestbook token

Open:

```text
/wp-admin/tools.php?page=tornevall-tools-guestbook
```

Configure an HTTPS Tools guestbook API endpoint and a Tools API key containing:

- `guestbook.write` for public server-to-server submissions.
- `guestbook.moderate` for the WordPress admin moderation client.

The token field renders blank. Submitting it blank preserves the existing stored value.

### WordPress guestbook admin

`Tools -> Tools Guestbook` loads the authoritative central entries from Tools instead of creating a second WordPress guestbook table.

The page supports:

- search
- visible/hidden filtering
- DNSBL status filtering
- private e-mail and source-IP review for administrators
- hide/restore actions through the `guestbook.moderate` scope
- optional DNSBL IP checks
- explicit DNSBL abuse reporting when the DNSBL addon is active and its own token has add permission

## Recommended DNSBL addon

The guestbook does **not** require the DNSBL plugin.

The admin page recommends **Tornevall Networks DNSBL Implementation** and can offer install/activate actions to administrators with the corresponding WordPress capability.

When the DNSBL plugin is absent:

- the guestbook still renders
- visitors can still sign it
- central moderation still works
- no local DNSBL check/report controls are available

When DNSBL is active, Tools for WordPress uses its stable plugin-to-plugin filters rather than calling DNSBL internal classes. A listed visitor IP can be rejected before WordPress forwards the guestbook entry to Tools.

Blacklist publication is never automatic. The **Report abuse** action requires a WordPress administrator and a DNSBL token with add permission. Guestbook/web abuse uses bitmask 64 (`IP_ABUSE_NO_SMTP`) by default.

## Guestbook embed endpoint

The public embed defaults to:

```text
https://tools.tornevall.net/guestbook/embed.js
```

For staging or integration testing, developers can override the embed URL:

```php
add_filter(
    'ttfw_guestbook_embed_url',
    static function () {
        return 'https://staging.example.test/guestbook/embed.js';
    }
);
```

Only valid HTTPS URLs are accepted. Invalid overrides fall back to the production Tools endpoint.

## AI provider behavior

### Tornevall Tools AI

Default endpoint:

```text
https://tools.tornevall.net/api/ai/internal/respond
```

The plugin sends the Tools-specific request contract from PHP. The `client_slug` should stay stable so Tools can apply server-side defaults, auditing and usage statistics.

### OpenAI direct

The OpenAI connector calls the OpenAI Responses API from PHP with the configured model, developer persona and user/editor context.

## Requirements

- WordPress 6.5 or newer.
- PHP 7.4 or newer.
- `manage_options` for plugin and guestbook administration.
- `edit_posts` for the AI editor endpoint.
- A suitable provider key for AI features.
- A Tools API key with guestbook scopes for WordPress guestbook signing/moderation.
- Tornevall Networks DNSBL Implementation only when optional DNSBL guestbook protection/reporting is wanted.

## Security notes

- AI, guestbook and DNSBL tokens stay server-side.
- Token fields render blank and preserve the existing value when saved blank.
- Public guestbook JavaScript receives no Tools API token.
- E-mail addresses and visitor IPs are not part of the public Tools guestbook payload.
- Public WordPress signing is proxied through WordPress PHP.
- Guestbook moderation requires `manage_options` in WordPress plus the remote `guestbook.moderate` scope.
- DNSBL report controls are unavailable unless the optional addon is active and its configured token can add records.
- DNSBL publication from the guestbook is always an explicit administrator action.
- Remote requests use WordPress HTTP APIs.

## Development structure

```text
tornevall-tools-for-wordpress.php      Main plugin bootstrap
includes/class-ttfw-plugin.php          Hooks and asset loading
includes/class-ttfw-settings.php        AI settings and sanitization
includes/class-ttfw-rest-controller.php AI REST endpoint
includes/class-ttfw-ai-service.php      AI provider adapters
includes/class-ttfw-guestbook.php       Guestbook shortcode and public form
includes/class-ttfw-guestbook-api.php   Server-side Tools guestbook API client
includes/class-ttfw-guestbook-settings.php Dedicated guestbook credentials
includes/class-ttfw-guestbook-rest.php  Public WordPress signing proxy
includes/class-ttfw-guestbook-admin.php Central moderation and addon UI
assets/guestbook.js                     Public local signing form client
assets/editor.js                        Block editor UI, no build step
assets/editor.css                       Editor styles
readme.txt                              WordPress.org-style plugin readme
README.md                               Project and developer documentation
CHANGELOG.md                            Release history, always update
AGENTS.md                               Development rules for future agents
```

## Manual testing

Before merging a guestbook change:

1. Activate the plugin without PHP fatal errors.
2. Open `Tools -> Tools Guestbook` with no token and confirm the configuration state is safe.
3. Save a valid guestbook token and then save the token field blank; confirm the stored token still works.
4. Add `[tornevall_guestbook theme="miazma" limit="10"]` to a public page.
5. Submit a public entry and confirm it appears in the central Tools guestbook.
6. Confirm the browser network request goes to the local WordPress REST endpoint and contains no Tools token.
7. Hide and restore an entry from the WordPress admin.
8. Test with the DNSBL addon absent; guestbook features must remain usable.
9. Activate the DNSBL addon and verify capability status plus IP check controls.
10. Confirm **Report abuse** appears only when the DNSBL token can add records and always requires an explicit admin submit.
11. Confirm an already listed visitor is rejected locally when DNSBL checking is available.
12. Run PHP lint across all PHP files and WordPress Coding Standards when installed.

## License

GPL-2.0-or-later.
