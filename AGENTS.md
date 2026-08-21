# AGENTS.md

This file defines how future agents and developers should continue work on Tornevall Tools for WordPress.

## Project goal

Build WordPress-native integrations for stable Tornevall Networks Tools services. The plugin is the WordPress client/integration layer for Tools; it is not an AI-first product and is not positioned as a Jetpack replacement.

Current public integrations:

1. Guestbook.
2. Dynamic DNS.

AI work remains separate until it is production-ready. DNSBL/FraudBL must not be reimplemented here because Tornevall Networks DNSBL has its own WordPress plugin. The Guestbook may use the standalone DNSBL plugin through its public WordPress bridge when that plugin is installed and active.

## Current architecture

- `tornevall-tools-for-wordpress.php` loads the plugin runtime.
- `TTFW_Settings` owns the Tornevall Tools overview and Dynamic DNS settings.
- `TTFW_API_Client` is the fixed-origin server-side client for documented `tools.tornevall.net/api/*` endpoints.
- `TTFW_Dynamic_DNS_Module` owns Dynamic DNS updates and WP-Cron scheduling.
- `TTFW_Guestbook_API`, `TTFW_Guestbook_REST`, `TTFW_Guestbook_Settings`, `TTFW_Guestbook`, `TTFW_Guestbook_Admin` and `TTFW_Guestbook_Connection_Admin` own the central Tools Guestbook integration.
- `TTFW_Guestbook_Connection_Admin` is the explicit admin surface for listing the configured Tools user's guestbooks, selecting one for this WordPress installation and creating a new owned book when the token permits it.
- `TTFW_Module_Registry` exposes integration status for the Tools overview.
- Guestbook frontend JavaScript is packaged locally as `assets/guestbook.js`.

## Integration rules

Each integration should solve one independently useful WordPress problem and only load the hooks, assets, REST routes, cron jobs or remote requests it actually needs.

Remote service use must be documented in `readme.txt`. Credentials stay server-side. State-changing admin actions require capability checks and nonces. Public REST routes need explicit permission behavior and strict validation.

Do not make authenticated external requests merely because the plugin was activated.

## Guestbook

Tools remains the authoritative guestbook database. WordPress proxies owner-scoped reads, writes and moderation through local REST endpoints so the Tools token stays server-side.

A configured token identifies one Tools user, but a Tools user may own multiple guestbooks. WordPress therefore stores an explicit selected guestbook id/slug after the administrator chooses a book on the Guestbook connection page. That stored selector must be added server-side to public read/write and admin-list requests. Browser input must never be allowed to override the selected Tools guestbook.

The Guestbook connection page may request `GET /api/guestbook/owned/books` only when an administrator actually opens that setup surface or performs an explicit connection action. Do not fetch the catalog merely because the plugin was activated.

Remote creation through `POST /api/guestbook/owned/books` is permitted only when Tools reports that the configured token can create. The current Tools contract requires the same token to have both `guestbook.write` and `guestbook.moderate`. New books are always owned by the Tools user behind the token; WordPress never sends or chooses an owner id.

When the Guestbook token is replaced, clear the stored guestbook selection so a selection from the previous Tools user cannot be reused accidentally.

Public signing is protected by Cloudflare Turnstile when configured. The Turnstile secret stays server-side. The browser receives only the public site key and single-use challenge token. Each external WordPress installation supplies its own Turnstile configuration; the plugin must not ship a shared Tools Turnstile secret.

The optional DNSBL controls are integration points into the separate Tornevall DNSBL WordPress plugin. Do not copy DNSBL lookup/reporting implementation into this repository.

## Dynamic DNS

Dynamic DNS is opt-in and disabled by default.

Update endpoint:

```text
POST https://tools.tornevall.net/api/dyndns/update
Authorization: Bearer <dynamic-dns-token>
```

Request body:

```json
{
  "hostname": "home.dyn.tornevall.net",
  "address": "auto"
}
```

`address=auto` deliberately uses the source address seen by Tools.

## AI boundary

Do not add the direct OpenAI client, Tools AI runtime, AI REST endpoint or editor AI assets back to the public release line until the AI work is intentionally reintroduced as an optional integration and reviewed for the then-current WordPress APIs.

## WordPress.org release requirements

Before submission or release:

1. Verify the plugin is complete and installable as a normal ZIP.
2. Smoke-test against the latest stable WordPress release.
3. Run official Plugin Check.
4. Validate `readme.txt`.
5. Review every external-service disclosure, including Tools and Cloudflare Turnstile.
6. Keep executable frontend code packaged with the plugin unless WordPress.org explicitly permits the external-service requirement.
7. Confirm final plugin name/slug before submission.

## Required documentation

Behavior changes should update, as applicable:

- `CHANGELOG.md`
- `README.md`
- `readme.txt`
- `AGENTS.md`

Every development request should have a linked issue/ticket and a PR.

## Security checklist

- Sanitize input and escape output.
- Keep tokens/secrets server-side.
- Never log credentials.
- Never include credentials in errors, browser payloads, screenshots, tests or documentation examples.
- Use WordPress HTTP APIs for outbound requests.
- Use `manage_options` for sensitive settings/admin actions unless a narrower capability is explicitly designed.
- Protect state changes with nonces.
- Do not allow arbitrary remote origins without a documented requirement and review.

## PHP checks

```bash
find . -name "*.php" -print -exec php -l {} \;
```

When WordPress Coding Standards are available:

```bash
phpcs --standard=WordPress .
```
