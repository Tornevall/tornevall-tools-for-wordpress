# AGENTS.md

This file defines how future agents and developers should continue work on Tornevall Tools for WordPress.

## Project goal

Build WordPress-native integrations for stable Tornevall Networks Tools services. The plugin is the WordPress client/integration layer for Tools; it is not an AI-first product and is not positioned as a Jetpack replacement.

Current public integrations:

1. Guestbook.
2. Dynamic DNS.
3. Statuspage.
4. Optional Tools account pairing for managed service credentials.

AI work remains separate until it is production-ready. DNSBL/FraudBL must not be reimplemented here because Tornevall Networks DNSBL has its own WordPress plugin. The Guestbook may use the standalone DNSBL plugin through its public WordPress bridge when that plugin is installed and active. Tools account pairing may supply a site-specific DNSBL credential to that plugin, but this repository must still not implement DNSBL lookup/write behavior itself.

## Current architecture

- `tornevall-tools-for-wordpress.php` loads the plugin runtime.
- `TTFW_Settings` owns the Tornevall Tools overview and Dynamic DNS settings.
- `TTFW_API_Client` is the fixed-origin server-side client for documented `tools.tornevall.net/api/*` endpoints. Authenticated `request()` still requires a token. Public pairing and Statuspage calls use the separate `public_request()` method.
- `TTFW_Tools_Connection` owns the explicit Tools account device flow, one-time exchange and locally stored managed service credentials.
- `TTFW_Tools_Connection_Admin` renders the connect/disconnect controls and public-safe service status on the Tools overview page.
- `TTFW_Dynamic_DNS_Module` owns Dynamic DNS updates and WP-Cron scheduling.
- `TTFW_Guestbook_API`, `TTFW_Guestbook_REST`, `TTFW_Guestbook_Settings`, `TTFW_Guestbook`, `TTFW_Guestbook_Admin` and `TTFW_Guestbook_Connection_Admin` own the central Tools Guestbook integration.
- `TTFW_Statuspage_Settings` owns the configured public status-page slug and bounded live-cache TTL.
- `TTFW_Statuspage_API` consumes and validates the versioned public Status Platform contract at `/api/status/v1/pages/{slug}`.
- `TTFW_Statuspage` owns last-good caching, health semantics, the canonical Statuspage renderer, `[tornevall_statuspage]`, and the dynamic Gutenberg block registration.
- `blocks/statuspage/block.json` and `blocks/statuspage/index.js` define the Statuspage block metadata and editor-only UI.
- `TTFW_Statuspage_Admin` renders the Statuspage setup and diagnostics surface.
- `TTFW_Module_Registry` exposes integration status for the Tools overview.
- Guestbook frontend JavaScript is packaged locally as `assets/guestbook.js`.

## Integration rules

Each integration should solve one independently useful WordPress problem and only load the hooks, assets, REST routes, cron jobs or remote requests it actually needs.

Remote service use must be documented in `readme.txt`. Credentials stay server-side. State-changing admin actions require capability checks and nonces. Public REST routes need explicit permission behavior and strict validation.

Do not make authenticated external requests merely because the plugin was activated.

## Gutenberg blocks

Blocks for Tools integrations should be thin WordPress editor surfaces over the integration's canonical PHP/service layer whenever server-rendering is sufficient.

- Use `block.json` metadata and normal WordPress block registration.
- Keep executable editor JavaScript packaged locally with the plugin.
- Do not duplicate remote clients, caches, authorization, normalization or business/status semantics in block JavaScript.
- Prefer dynamic/server-rendered blocks when the equivalent shortcode or PHP renderer already exists.
- A block and its shortcode compatibility surface must share the same canonical renderer rather than maintaining parallel HTML implementations.
- Editor placeholders and inspector controls should not cause remote Tools requests merely because an editor opens a post.
- Browser-side ToolsAPI requests require an explicit contract/security reason and must never expose server-side Tools credentials.

## Statuspage

Tools remains authoritative for status pages, components, incidents and incident updates. WordPress is a public read/render layer and must not introduce a second Status Platform database or local incident editor.

The public v1 read contract is:

```text
GET https://tools.tornevall.net/api/status/v1/pages/{slug}
```

The client must require schema version `1.0` and normalize all externally supplied status, page, component and incident fields before rendering.

Status health semantics are part of the contract:

- `major_outage` is the only confirmed critical/major-outage state.
- `degraded`, `partial_outage`, `maintenance`, and `stale` are warning/non-critical states.
- missing configuration is neutral.
- unknown/unrecognized remote state stays `unknown`; never promote it to outage.
- an HTTP/API/JSON/transport failure is a communication failure, not evidence of a service outage.
- on communication failure, use the last successful snapshot as `stale` when available; otherwise report unavailable/unknown.

The configured status slug must remain a constrained identifier and must never become an arbitrary remote URL. The Tools origin stays fixed in `TTFW_API_Client`.

Public Statuspage rendering must not expose Tools bearer credentials. The current public endpoint needs no bearer token. Any future owner-scoped configuration/catalog endpoint must remain server-side and use explicit administrator capability checks.

Do not fetch Statuspage data solely because the plugin activates or because an editor loads the Statuspage block placeholder. The first request may occur when an administrator opens the Statuspage setup/status surface, explicitly refreshes it, or when a public page actually renders the shortcode/block.

## Votech integration contract

Votech is a client integration of the canonical ToolsAPI Votech service. WordPress must not implement a second voting engine or a parallel API contract.

- Use the canonical ToolsAPI Votech API namespace directly under `/api/votech/...`.
- Do not introduce URL-based Votech API versions such as `/api/votech/v1`, `/api/votech/v2`, `/v1`, `/v2`, versioned SDK directories, or equivalent route namespaces.
- Do not add a versioned WordPress-side proxy path merely to wrap the unversioned ToolsAPI Votech API.
- This is an explicit architecture rule required to match the current ToolsAPI API structure. It overrides any generic preference to version new APIs in the URL.
- Consume the same canonical Votech SDK/embed contract used by ToolsAPI issue Tornevall/toolsApi#1001 and the WordPress integration tickets #22/#23.
- If a future concrete compatibility problem requires a migration mechanism, handle that specific case explicitly instead of pre-creating version namespaces.

## Tools account pairing

The account connection is opt-in. It must only start after a WordPress administrator explicitly chooses to connect.

Current contract:

1. WordPress sends site name, site URL, same-host callback URL and requested service names to `POST /api/integrations/wordpress/device`.
2. The browser is redirected only to the fixed `https://tools.tornevall.net` origin.
3. The administrator signs in to Tools and approves there.
4. Tools creates dedicated site credentials instead of exposing an existing raw service token.
5. WordPress exchanges the short-lived device code server-to-server exactly once through `POST /api/integrations/wordpress/token`.
6. WordPress stores only the newly delegated service credentials and public-safe permission/status metadata.

The raw device code is temporary and must stay in a short-lived user-scoped transient until the callback. It must not be printed in markup, notices or logs.

The initial managed services are DNSBL/FraudBL and Guestbook. Dynamic DNS remains manually configured because the current Tools Dynamic DNS token service maintains one primary token per user and rotation can invalidate another client.

Manual service credentials are explicit overrides. In particular:

- `TTFW_Guestbook_Settings::token()` must return the manually configured Guestbook token first and use the managed Guestbook token only when the manual value is empty.
- The standalone DNSBL plugin may consume the managed DNSBL token through `tornevall_dnsbl_managed_api_token` only when its own explicit token is absent.

Never display managed credentials in wp-admin. Status UI may show service availability, scopes, guardrails or counts, but not token strings.

Disconnecting currently removes the local connection and local managed credentials. If server-side revocation is added later, it must be an explicit authenticated Tools contract rather than a client-side guess based on token names.

## Guestbook

Tools remains the authoritative guestbook database. WordPress proxies owner-scoped reads, writes and moderation through local REST endpoints so the Tools token stays server-side.

A configured token identifies one Tools user, but a Tools user may own multiple guestbooks. WordPress therefore stores an explicit selected guestbook id/slug after the administrator chooses a book on the Guestbook connection page. That stored selector must be added server-side to public read/write and admin-list requests. Browser input must never be allowed to override the selected Tools guestbook.

The Guestbook connection page may request `GET /api/guestbook/owned/books` only when an administrator actually opens that setup surface or performs an explicit connection action. Do not fetch the catalog merely because the plugin was activated.

Remote creation through `POST /api/guestbook/owned/books` is permitted only when Tools reports that the configured token can create. The current Tools contract requires the same token to have both `guestbook.write` and `guestbook.moderate`. New books are always owned by the Tools user behind the token; WordPress never sends or chooses an owner id.

When the manually configured Guestbook token is replaced, clear the stored guestbook selection so a selection from the previous Tools user cannot be reused accidentally.

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
php tests/statuspage-contract-test.php
php tests/statuspage-block-test.php
```

When WordPress Coding Standards are available:

```bash
phpcs --standard=WordPress .
```
