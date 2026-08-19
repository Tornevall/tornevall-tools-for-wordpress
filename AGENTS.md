# AGENTS.md

This file defines how future agents and developers should continue work on Tornevall Tools for WordPress.

## Project goal

Build WordPress-native integrations with Tornevall Networks Tools, including:

1. Tornevall Networks Tools AI endpoints.
2. Direct OpenAI API access.
3. Public and administrative Tools integrations such as the central guestbook.

The plugin must be exact, conservative, and secure. Do not expose provider or integration tokens to the browser.

## Current architecture

- WordPress editor UI lives in `assets/editor.js`.
- Editor UI calls `/wp-json/ttfw/v1/ai/respond`.
- PHP REST controller sanitizes editor input and checks `edit_posts`.
- PHP service calls the configured remote provider with server-side credentials.
- wp-admin AI settings store provider configuration in `ttfw_options`.
- Public Tools guestbook integration lives in `includes/class-ttfw-guestbook.php`.
- `[tornevall_guestbook]` creates a target container and enqueues the public Tools `/guestbook/embed.js` endpoint.
- The remote guestbook entry list renders inside Shadow DOM and receives public presentation data only.
- Dedicated guestbook connection settings live in `includes/class-ttfw-guestbook-settings.php` and `ttfw_guestbook_options`.
- `includes/class-ttfw-guestbook-api.php` is the server-side client for the central Tools guestbook API.
- Public WordPress signing goes through `/wp-json/ttfw/v1/guestbook/entries`, implemented by `includes/class-ttfw-guestbook-rest.php`.
- The visitor browser talks only to WordPress. PHP forwards the sanitized submission to Tools with the stored guestbook token.
- `includes/class-ttfw-guestbook-admin.php` owns `Tools -> Tools Guestbook`, central moderation and the recommended-addons panel.
- Tools remains the authoritative guestbook database. Do not introduce a separate WordPress guestbook table.
- Tornevall Networks DNSBL Implementation is an optional addon. Consume its stable WordPress filters instead of calling its internal classes directly.

## Required documentation practice

Always update these files in the same pull request when behavior changes:

- `CHANGELOG.md`
- `README.md`
- `readme.txt` when user-facing WordPress.org-style information changes
- `AGENTS.md` when development rules, architecture, or process changes

`CHANGELOG.md` must always be updated. No feature, fix, or refactor should be merged without a changelog entry.

## WordPress development rules

Follow WordPress plugin practices:

- Check `ABSPATH` before executing plugin PHP files.
- Use WordPress hooks and APIs instead of direct superglobal or database access where possible.
- Use `register_setting()` with a `sanitize_callback` for settings.
- Use `register_rest_route()` with a real `permission_callback`.
- Sanitize all input.
- Escape all output.
- Use `wp_remote_post()`, `wp_remote_request()`, and related WordPress HTTP helpers for outbound HTTP.
- Use `wp_json_encode()` for JSON request bodies.
- Use `WP_Error` with status data for REST errors.
- Never send API tokens to JavaScript through `wp_localize_script()`, inline scripts, data attributes, public REST responses, or remote widget URLs.
- Do not store full AI responses or request context unless a future setting explicitly enables logging.
- Keep code compatible with the declared PHP version.
- Public widget and API endpoint settings must use HTTPS.
- Shortcode attributes must be allow-listed or strictly bounded before being forwarded to remote widgets.

## Security requirements

- Provider, guestbook, and DNSBL tokens must stay server-side.
- Password/token fields must render blank and preserve existing values when submitted blank.
- Settings and central guestbook administration must require `manage_options`.
- Editor AI calls must require `edit_posts` at minimum.
- Do not add public REST endpoints for AI generation.
- The public guestbook REST endpoint may be unauthenticated because anybody is allowed to sign, but it must remain a local proxy with sanitization, rate limiting, honeypot handling, and no token disclosure.
- Do not trust model names, provider names, URLs, prompts, persona text, or context from JavaScript.
- HTTPS is required for Tools endpoint settings.
- Do not log secrets.
- Do not include tokens in exception messages, REST responses, browser data, screenshots, tests, or documentation examples.
- The public guestbook embed is token-free. Never add AI/provider tokens or private guestbook fields to its URL or browser payload.
- DNSBL must remain optional. Disabling or uninstalling DNSBL must not break guestbook reading, signing, or central moderation.
- Never auto-publish a visitor IP to DNSBL merely because a guestbook entry was rejected, hidden, or looks suspicious.

## Tornevall Tools AI contract

Default endpoint:

```text
https://tools.tornevall.net/api/ai/internal/respond
```

The internal endpoint expects Tools-specific JSON. Do not send OpenAI-native `messages[]` directly to it.

Minimum fields:

```json
{
  "client_slug": "tornevall_tools_wordpress",
  "user_prompt": "Reply with ok",
  "context": "Connection test"
}
```

Important points:

- `client_slug` is required.
- At least one of `context` or `user_prompt` must be non-empty.
- Token-authenticated callers need the correct Tools AI scope for the endpoint.
- The plugin should keep using a stable client slug unless the admin changes it.

## Tools guestbook contract

Default embed endpoint:

```text
https://tools.tornevall.net/guestbook/embed.js
```

Default API base:

```text
https://tools.tornevall.net/api/guestbook
```

The `[tornevall_guestbook]` shortcode supports:

- `theme`: `tools`, `miazma`, or `terminal`.
- `limit`: 1-50.

The shortcode must:

- Create a unique target element for each shortcode instance.
- Pass only public presentation parameters to the Tools embed endpoint.
- Enqueue the remote script only when the shortcode is rendered.
- Keep the endpoint HTTPS-only.
- Fall back to the production endpoint if a developer filter returns an invalid URL.
- Remain build-step free.
- Render the local sign form only when server-side guestbook credentials are configured.

The `ttfw_guestbook_embed_url` filter may be used for staging/testing. It must not become a vehicle for browser-side secrets.

### Guestbook token scopes

Use Tools API scopes, not a public shared secret embedded into the widget:

- `guestbook.write`: server-side public-signing proxy.
- `guestbook.moderate`: server-side administrative listing and hide/restore operations.

The browser must never call the authenticated Tools guestbook API directly. The expected public signing flow is:

```text
Browser -> WordPress REST -> WordPress PHP -> Tools guestbook API
```

Forward the original visitor IP and WordPress site identity from PHP when submitting to Tools. Do not infer the source IP from browser-controlled form fields.

### DNSBL addon contract

The optional DNSBL addon exposes these filters:

- `tornevall_dnsbl_capabilities`
- `tornevall_dnsbl_check_ip`
- `tornevall_dnsbl_report_ip`

Guestbook behavior when DNSBL is absent:

- public embed works
- public signing works
- central Tools moderation works
- DNSBL-specific controls are hidden/unavailable

When DNSBL is active and reports `can_check`, the local public guestbook proxy may reject an already listed source IP before forwarding it to Tools.

The `tornevall_dnsbl_report_ip` flow is admin-only and explicit. The default guestbook/web-abuse bitmask is `64` (`IP_ABUSE_NO_SMTP`). Do not call the report filter automatically from public validation, spam rejection, hide actions, or DNSBL checks.

## OpenAI direct contract

Current implementation uses:

```text
https://api.openai.com/v1/responses
```

The request should contain:

- `model`
- `input[]`
- a `developer` message for the configured persona
- a `user` message for context plus task

Review the default OpenAI model before a release. Model availability changes over time.

## JavaScript rules

The current editor and guestbook JavaScript intentionally have no build step and use browser/WordPress globals.

When editing `assets/editor.js` or `assets/guestbook.js`:

- Keep browser-side code free of tokens.
- Use local WordPress REST endpoints for authenticated upstream operations.
- Keep selected block context limited in length.
- Prefer WordPress data stores over DOM scraping for editor features.
- Do not add dependencies that require a build step unless the build tooling is added in the same pull request.
- If a build step is added later, document it in `README.md` and this file.

The public guestbook entry-list JavaScript is served by Tools. Do not copy the remote Shadow DOM widget implementation into this plugin unless the architecture is intentionally changed and documented.

## PHP quality checklist

Before merging:

```bash
find . -name "*.php" -print -exec php -l {} \;
```

When WordPress Coding Standards are installed:

```bash
phpcs --standard=WordPress .
```

## Manual test checklist

- Activate plugin.
- Save settings with a new OpenAI token.
- Save settings again with OpenAI token blank and confirm the old token still works.
- Save settings with a new Tools token.
- Save settings again with Tools token blank and confirm the old token still works.
- Try invalid Tools endpoint URL and confirm it falls back to the default HTTPS endpoint.
- Open the block editor as an editor/admin.
- Confirm `Tornevall AI` sidebar loads.
- Confirm `Tornevall AI Assistant` appears in the `Text` category.
- Generate with Tools AI.
- Generate with OpenAI direct.
- Insert generated content after selected block.
- Replace selected block with generated content.
- Confirm a user without `edit_posts` cannot call the AI REST endpoint.
- Confirm a user without `manage_options` cannot open settings or guestbook admin.
- Add `[tornevall_guestbook]` to a public page and confirm the widget loads.
- Test guestbook themes `tools`, `miazma`, and `terminal`.
- Test a guestbook `limit` below 1 and above 50 and confirm it is clamped.
- Confirm invalid guestbook themes fall back to `tools`.
- Configure a guestbook token, save the field blank, and confirm the stored token remains usable.
- Submit a public guestbook entry and confirm the browser request goes only to local WordPress REST and contains no Tools token.
- Confirm the central entry appears in Tools and can be hidden/restored from WordPress admin.
- Test the guestbook with DNSBL absent and confirm normal guestbook functionality remains available.
- Activate DNSBL and confirm check controls appear when available.
- Confirm Report abuse only appears when DNSBL reports add capability and always requires explicit admin action.
- Confirm no provider, guestbook, or DNSBL token or private public guestbook e-mail address appears in page source or browser requests.

## Pull request standards

Every pull request should include:

- Summary of behavior changes.
- Security notes when touching settings, REST, remote requests, public embeds, or addon integrations.
- Manual test notes.
- Changelog update.
- Documentation update when behavior changes.

## Do not do this

- Do not put provider, guestbook, or DNSBL tokens into JavaScript.
- Do not use the Tools internal endpoint as if it were OpenAI-compatible.
- Do not add a public unauthenticated AI endpoint.
- Do not silently swallow provider errors.
- Do not hardcode user-specific secrets.
- Do not skip `CHANGELOG.md`.
- Do not forward arbitrary shortcode values or non-HTTPS guestbook URLs.
- Do not create a separate WordPress guestbook database while Tools is authoritative.
- Do not hard-depend on DNSBL or call DNSBL internal classes from the guestbook client.
- Do not automatically blacklist guestbook visitors.
