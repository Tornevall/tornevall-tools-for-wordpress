# AGENTS.md

This file defines how future agents and developers should continue work on Tornevall Tools for WordPress.

## Project goal

Build a WordPress-native connector between the block editor and:

1. Tornevall Networks Tools AI endpoints.
2. Direct OpenAI API access.

The plugin must be exact, conservative, and secure. Do not expose provider tokens to the browser.

## Current architecture

- WordPress editor UI lives in `assets/editor.js`.
- Editor UI calls `/wp-json/ttfw/v1/ai/respond`.
- PHP REST controller sanitizes editor input and checks `edit_posts`.
- PHP service calls the configured remote provider with server-side credentials.
- wp-admin settings store provider configuration in `ttfw_options`.

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
- Use `wp_remote_post()` and related WordPress HTTP helpers for outbound HTTP.
- Use `wp_json_encode()` for JSON request bodies.
- Use `WP_Error` with status data for REST errors.
- Never send API tokens to JavaScript through `wp_localize_script()` or inline scripts.
- Do not store full AI responses or request context unless a future setting explicitly enables logging.
- Keep code compatible with the declared PHP version.

## Security requirements

- Provider tokens must stay server-side.
- Password/token fields must render blank and preserve existing values when submitted blank.
- Settings must require `manage_options`.
- Editor AI calls must require `edit_posts` at minimum.
- Do not add public REST endpoints for AI generation.
- Do not trust model names, provider names, URLs, prompts, persona text, or context from JavaScript.
- HTTPS is required for the Tools endpoint setting.
- Do not log secrets.
- Do not include tokens in exception messages, REST responses, browser data, screenshots, tests, or documentation examples.

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

The current editor JavaScript intentionally has no build step. It uses WordPress globals.

When editing `assets/editor.js`:

- Keep browser-side code free of tokens.
- Use `wp.apiFetch` for REST calls.
- Keep selected block context limited in length.
- Prefer WordPress data stores over DOM scraping.
- Do not add dependencies that require a build step unless the build tooling is added in the same pull request.
- If a build step is added later, document it in `README.md` and this file.

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
- Confirm a user without `edit_posts` cannot call the REST endpoint.
- Confirm a user without `manage_options` cannot open settings.

## Pull request standards

Every pull request should include:

- Summary of behavior changes.
- Security notes when touching settings, REST, or remote requests.
- Manual test notes.
- Changelog update.
- Documentation update when behavior changes.

## Do not do this

- Do not put provider tokens into JavaScript.
- Do not use the Tools internal endpoint as if it were OpenAI-compatible.
- Do not add a public unauthenticated AI endpoint.
- Do not silently swallow provider errors.
- Do not hardcode user-specific secrets.
- Do not skip `CHANGELOG.md`.
