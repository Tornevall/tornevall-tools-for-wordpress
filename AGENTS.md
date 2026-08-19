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
- `[tornevall_guestbook]` creates a local target container and uses `assets/guestbook.js`.
- Public guestbook reads go through `/wp-json/ttfw/v1/guestbook/entries`; WordPress PHP then calls Tools `/api/guestbook/owned/entries` with the server-side token.
- Tools owner-scopes public reads and remote moderation to the exact API key through `source_api_key_id`.
- The public guestbook entry list renders inside Shadow DOM and receives public fields only.
- Dedicated guestbook/Turnstile settings live in `includes/class-ttfw-guestbook-settings.php` and `ttfw_guestbook_options`.
- `includes/class-ttfw-guestbook-api.php` is the server-side client for the central Tools guestbook API.
- Public WordPress signing also goes through `/wp-json/ttfw/v1/guestbook/entries`, implemented by `includes/class-ttfw-guestbook-rest.php`.
- The visitor browser talks only to WordPress. PHP verifies Turnstile, optionally checks DNSBL through the addon bridge, and forwards the sanitized submission to Tools with the stored guestbook token.
- `includes/class-ttfw-guestbook-admin.php` owns `Tools -> Tools Guestbook`, owner-scoped moderation and the recommended-addons panel.
- Tools remains the authoritative guestbook database. Do not introduce a separate WordPress guestbook table.
- Tornevall Networks DNSBL Implementation is an optional addon. Consume its generic stable WordPress filters instead of calling its internal classes directly.

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
- Never send API tokens or Turnstile secrets to JavaScript through `wp_localize_script()`, inline scripts, data attributes, public REST responses, or remote widget URLs.
- Do not store full AI responses or request context unless a future setting explicitly enables logging.
- Keep code compatible with the declared PHP version.
- Public widget and API endpoint settings must use HTTPS.
- Shortcode attributes must be allow-listed or strictly bounded.

## Security requirements

- Provider, guestbook, and DNSBL tokens must stay server-side.
- Turnstile secret must stay server-side. The public site key may be rendered.
- Password/token/secret fields must render blank and preserve existing values when submitted blank.
- Settings and guestbook administration must require `manage_options`.
- Editor AI calls must require `edit_posts` at minimum.
- Do not add public REST endpoints for AI generation.
- The public guestbook REST endpoint may be unauthenticated because anybody is allowed to read/sign, but it must remain a local proxy with no token disclosure.
- Public guestbook POST must include sanitization, rate limiting, honeypot handling and server-side Turnstile verification.
- Turnstile verification must check successful Siteverify, exact WordPress hostname, and action `guestbook` before forwarding a post.
- Do not trust model names, provider names, URLs, prompts, persona text, or context from JavaScript.
- HTTPS is required for Tools endpoint settings.
- Do not log secrets.
- Do not include tokens in exception messages, REST responses, browser data, screenshots, tests, or documentation examples.
- Public guestbook reads must remain owner-scoped through the server-side token; never fall back to the global Tools feed for a configured WordPress tenant.
- DNSBL must remain optional. Disabling or uninstalling DNSBL must not break guestbook reading, Turnstile-protected signing, or owner-scoped moderation.
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

Default API base:

```text
https://tools.tornevall.net/api/guestbook
```

The `[tornevall_guestbook]` shortcode supports:

- `theme`: `tools`, `miazma`, or `terminal`.
- `limit`: 1-50.

The shortcode must:

- Create a unique target element for each shortcode instance.
- Read through the local WordPress REST proxy, never by exposing the Tools token.
- Render only the exact configured token's visible entries.
- Keep the public entry list in Shadow DOM.
- Remain build-step free.
- Render the local sign form only when server-side guestbook credentials and Turnstile credentials are configured.
- Keep reading available when Turnstile is not configured, but disable signing.

### Guestbook token scopes

- `guestbook.write`: owner-scoped public read plus server-side public-signing proxy.
- `guestbook.moderate`: server-side administrative listing and hide/restore operations for the exact same API key.

The browser must never call the authenticated Tools guestbook API directly. The expected public flows are:

```text
Browser GET -> WordPress REST -> WordPress PHP -> Tools owner-scoped guestbook API
Browser POST -> WordPress REST -> Turnstile Siteverify -> WordPress PHP -> Tools guestbook API
```

Forward the original visitor IP and WordPress site identity from PHP when submitting to Tools. Do not infer the source IP from browser-controlled form fields.

Use separate Tools guestbook tokens when separate sites should represent separate guestbooks.

### DNSBL addon contract

The optional DNSBL addon exposes these generic filters:

- `tornevall_dnsbl_capabilities`
- `tornevall_dnsbl_check_ip`
- `tornevall_dnsbl_report_ip`

Guestbook behavior when DNSBL is absent:

- owner-scoped public reading works
- Turnstile-protected public signing works
- owner-scoped Tools moderation works
- DNSBL-specific controls do not exist

When DNSBL is active and reports `can_check`, the local public guestbook proxy may reject an already listed source IP before forwarding it to Tools.

The `tornevall_dnsbl_report_ip` flow is admin-only and explicit. The default generic web-abuse bitmask is `64` (`IP_ABUSE_NO_SMTP`). Do not call the report filter automatically from public validation, spam rejection, hide actions, or DNSBL checks.

Consumers may provide non-sensitive `source_type`, `source_name`, and `source_note` context for Tools TXT audit publication. Never include visitor name, e-mail or message body in public DNS TXT metadata.

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

- Keep browser-side code free of tokens and secrets.
- Use local WordPress REST endpoints for authenticated upstream operations.
- Keep selected block context limited in length.
- Prefer WordPress data stores over DOM scraping for editor features.
- Do not add dependencies that require a build step unless the build tooling is added in the same pull request.
- If a build step is added later, document it in `README.md` and this file.
- Keep owner-scoped guestbook rendering in local `assets/guestbook.js`; do not switch a WordPress tenant back to the global Tools embed.

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
- Add `[tornevall_guestbook]` to a public page and confirm the owner-scoped widget loads.
- Test guestbook themes `tools`, `miazma`, and `terminal`.
- Test a guestbook `limit` below 1 and above 50 and confirm it is clamped.
- Confirm invalid guestbook themes fall back to `tools`.
- Configure a guestbook token, save the field blank, and confirm the stored token remains usable.
- Configure Turnstile, save its secret blank, and confirm the stored secret remains usable.
- Confirm another guestbook token's entries do not appear in the public list or WordPress admin.
- Submit a public guestbook entry and confirm the browser request goes only to local WordPress REST and contains no Tools token or Turnstile secret.
- Confirm the central entry appears in Tools and can be hidden/restored from WordPress admin.
- Test the guestbook with DNSBL absent and confirm normal guestbook functionality remains available without DNSBL controls.
- Activate DNSBL and confirm check controls appear when available.
- Confirm Report abuse only appears when DNSBL reports add capability and always requires explicit admin action.
- Confirm no provider, guestbook, DNSBL token, Turnstile secret or private guestbook e-mail address appears in page source or browser requests.

## Pull request standards

Every pull request should include:

- Summary of behavior changes.
- Security notes when touching settings, REST, remote requests, public embeds, or addon integrations.
- Manual test notes.
- Changelog update.
- Documentation update when behavior changes.

## Do not do this

- Do not put provider, guestbook, DNSBL tokens, or Turnstile secrets into JavaScript.
- Do not use the Tools internal endpoint as if it were OpenAI-compatible.
- Do not add a public unauthenticated AI endpoint.
- Do not silently swallow provider errors.
- Do not hardcode user-specific secrets.
- Do not skip `CHANGELOG.md`.
- Do not create a separate WordPress guestbook database while Tools is authoritative.
- Do not let a WordPress guestbook token enumerate or mutate another token owner's entries.
- Do not hard-depend on DNSBL or call DNSBL internal classes from the guestbook client.
- Do not automatically blacklist guestbook visitors.
