# Tornevall Tools for WordPress

Tornevall Tools for WordPress adds server-side AI connectors and Tornevall Networks Tools integrations to WordPress.

The AI implementation supports two providers:

1. Tornevall Networks Tools AI through `https://tools.tornevall.net/api/ai/internal/respond`.
2. Direct OpenAI access through the OpenAI Responses API.

The plugin is intentionally WordPress-native. API tokens are configured in wp-admin and used only from PHP. Browser-side features call local WordPress endpoints, and WordPress performs authenticated remote requests server-side.

## Current status

Version `0.1.2` turns the guestbook integration into an owner-scoped central guestbook client while keeping Tools as the authoritative database.

Implemented now:

- wp-admin settings page under `Settings -> Tornevall Tools AI`
- configurable OpenAI and Tools AI providers
- block editor sidebar and assistant block
- server-side AI REST endpoint at `/wp-json/ttfw/v1/ai/respond`
- public `[tornevall_guestbook]` shortcode with `tools`, `miazma`, and `terminal` themes
- dedicated guestbook API URL/token stored server-side
- owner-scoped public guestbook reading through local WordPress REST
- public WordPress guestbook signing through `/wp-json/ttfw/v1/guestbook/entries`
- per-site Cloudflare Turnstile protection for public signing
- WordPress admin guestbook under `Tools -> Tools Guestbook`, limited to the configured token's own entries
- recommended add-ons panel for Tornevall Networks DNSBL Implementation
- optional DNSBL visitor-IP blocking, checking and explicit administrator abuse reporting
- tokens and Turnstile secrets are never localized to public JavaScript

## Guestbook

```text
[tornevall_guestbook]
```

Choose a theme and number of entries:

```text
[tornevall_guestbook theme="miazma" limit="10"]
```

`limit` is clamped to 1-50 entries. The public list renders in Shadow DOM.

The shortcode no longer loads the global Tools feed for WordPress tenants. The browser calls the local WordPress REST endpoint, WordPress authenticates to Tools from PHP, and Tools returns only visible entries whose `source_api_key_id` matches the configured API key.

A different guestbook token therefore cannot enumerate or mutate this token's entries. Use a separate Tools guestbook token when separate sites should have separate guestbooks.

### Public signing

The flow is:

```text
Visitor browser -> WordPress REST -> Turnstile Siteverify -> Tools guestbook API -> central guestbook database
```

The browser never receives the Tools guestbook token or Turnstile secret.

Public signing requires a configured guestbook token plus a Cloudflare Turnstile site key and secret for the WordPress hostname. WordPress validates the Turnstile response server-side, checks the returned hostname and requires action `guestbook`, then forwards sanitized form fields, the original visitor IP and site identity to Tools.

If Turnstile is missing, the existing guestbook remains readable but signing is disabled.

### Guestbook token and Turnstile

Open:

```text
/wp-admin/tools.php?page=tornevall-tools-guestbook
```

Configure:

- HTTPS Tools guestbook API endpoint
- Tools API key with `guestbook.write` and `guestbook.moderate`
- Cloudflare Turnstile site key for this WordPress hostname
- Cloudflare Turnstile secret key

Secret fields render blank and preserve existing stored values when saved blank.

### WordPress guestbook admin

`Tools -> Tools Guestbook` loads only entries owned by the configured token.

The page supports:

- search
- visible/hidden filtering
- DNSBL status filtering when the addon exists
- private e-mail and source-IP review for the token's entries
- hide/restore actions through `guestbook.moderate`
- optional DNSBL IP checks
- explicit DNSBL abuse reporting when the DNSBL addon is active and its own token has add permission

## Recommended DNSBL addon

The guestbook does **not** require the DNSBL plugin.

When the DNSBL plugin is absent:

- the token-owned guestbook still renders
- visitors can still sign when Turnstile is configured
- owner-scoped moderation still works
- no DNSBL check/report controls exist in Tools for WordPress

When DNSBL is active, Tools for WordPress uses the generic plugin-to-plugin bridge exposed by Tornevall Networks DNSBL Implementation rather than reaching into plugin internals.

Blacklist publication is never automatic. `Report abuse` requires a WordPress administrator and a DNSBL token with add permission. The consumer sends safe source type/name/note metadata so Tools can publish useful DNS TXT audit context. Visitor name, e-mail and message contents must not be placed in public TXT data.

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

- WordPress 6.5 or newer
- PHP 7.4 or newer
- `manage_options` for plugin and guestbook administration
- `edit_posts` for the AI editor endpoint
- a suitable provider key for AI features
- a Tools API key with guestbook scopes for WordPress guestbook reading/signing/moderation
- Cloudflare Turnstile keys for public guestbook signing
- Tornevall Networks DNSBL Implementation only when optional DNSBL protection/reporting is wanted

## Security notes

- AI, guestbook and DNSBL tokens stay server-side
- Turnstile secret stays server-side; only the public site key is rendered
- public guestbook JavaScript talks only to local WordPress REST
- Tools owner-scopes public reads and remote moderation to the exact configured API key
- e-mail addresses and visitor IPs are not part of public guestbook payloads
- WordPress validates Turnstile hostname and action before forwarding a post
- guestbook moderation requires `manage_options` plus remote `guestbook.moderate`
- DNSBL controls do not exist without the optional addon
- DNSBL report controls additionally require the add permission reported by that addon's token
- DNSBL publication is always an explicit administrator action
- remote requests use WordPress HTTP APIs

## Development structure

```text
tornevall-tools-for-wordpress.php       Main plugin bootstrap
includes/class-ttfw-plugin.php          Hooks and asset loading
includes/class-ttfw-settings.php        AI settings and sanitization
includes/class-ttfw-rest-controller.php AI REST endpoint
includes/class-ttfw-ai-service.php      AI provider adapters
includes/class-ttfw-guestbook.php       Guestbook shortcode and public form
includes/class-ttfw-guestbook-api.php   Server-side Tools guestbook API client
includes/class-ttfw-guestbook-settings.php Guestbook and Turnstile credentials
includes/class-ttfw-guestbook-rest.php  Owner-scoped read/sign WordPress proxy
includes/class-ttfw-guestbook-admin.php Owner-scoped moderation and addon UI
assets/guestbook.js                     Owner-scoped public list/sign client
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
3. Configure a guestbook token and site-local Turnstile keys.
4. Add `[tornevall_guestbook theme="miazma" limit="10"]` to a public page.
5. Confirm the public list contains only entries created through this token.
6. Submit a public entry and confirm the browser sends no Tools token or Turnstile secret.
7. Confirm the new entry appears after successful Turnstile verification.
8. Confirm another guestbook token's entries are neither listed nor mutable.
9. Hide and restore an owned entry from WordPress admin.
10. Test with the DNSBL addon absent; no DNSBL controls should exist.
11. Activate the DNSBL addon and verify capability-aware check/report controls.
12. Confirm `Report abuse` appears only when the DNSBL token can add records.
13. Run PHP lint across all PHP files and WordPress Coding Standards when installed.

## License

GPL-2.0-or-later.
