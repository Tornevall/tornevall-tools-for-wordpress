# Tornevall Tools for WordPress

Tornevall Tools for WordPress brings selected services from **Tornevall Networks Tools** into WordPress.

The plugin is the WordPress integration layer for Tools. It does not try to recreate Tools inside WordPress; it exposes useful Tools services in a WordPress-native way while keeping service credentials server-side.

## Current integrations

Version `0.3.0` contains:

- **Guestbook** - central Tools-backed guestbook with explicit per-site guestbook selection, shortcode rendering, owner-scoped reads/writes, moderation and Cloudflare Turnstile support.
- **Dynamic DNS** - keep a Tornevall Networks Dynamic DNS hostname synchronized from WordPress manually or through WP-Cron.
- **Statuspage** - render a public Tools Status Platform page in WordPress with overall state, component state, active incidents and incident timelines.
- **Tools account connection** - explicitly authorize this WordPress site from a logged-in Tools account and let Tools create dedicated site credentials for supported services.

AI is not part of the current public runtime. DNSBL/FraudBL is not duplicated in this plugin; the standalone DNSBL plugin remains authoritative for DNSBL behavior.

## Statuspage

Tools is authoritative for Status Platform data. WordPress reads the public versioned endpoint:

```text
GET https://tools.tornevall.net/api/status/v1/pages/{slug}
```

Configure the public status-page slug under **Tornevall Tools -> Statuspage** and render it with:

```text
[tornevall_statuspage]
```

Include recent resolved incident history with:

```text
[tornevall_statuspage history="1"]
```

The response contract is validated as Status Platform schema `1.0`. Public rendering includes the page title/description, overall status, components, active incidents and update timelines.

### Status and cache semantics

The integration deliberately separates a confirmed outage from a failed status request:

- `operational` is healthy.
- `degraded`, `partial_outage` and `maintenance` are warning states.
- `major_outage` is the only confirmed critical/major-outage state.
- missing configuration is neutral.
- an unknown remote state remains unknown.
- a failed Tools request uses the last successful snapshot as **stale** when one exists.
- a failed Tools request without a previous snapshot is **temporarily unavailable**, not a major outage.

Successful responses use a bounded live cache (60-3600 seconds, default 300). The most recent successful snapshot is retained separately so a short API/network interruption does not falsely turn the WordPress status display into an outage.

The public Status Platform endpoint does not require a bearer token. No Tools credential is emitted in public HTML or browser JavaScript.

## Tools account connection

Open **Tornevall Tools** in wp-admin and choose **Connect to Tornevall Tools**. Pairing is always an explicit administrator action.

The flow is:

1. WordPress asks the public Tools pairing endpoint for a short-lived device code.
2. The administrator is redirected to `https://tools.tornevall.net` and signs in there.
3. Tools shows the WordPress site and the services it wants to use.
4. The administrator approves or denies the request.
5. Tools creates dedicated credentials for this WordPress site. Existing raw service tokens are not sent to WordPress and are not reused.
6. WordPress exchanges the device code server-to-server once and stores the granted site credentials locally.

The wp-admin status card shows which managed services were granted and their permission metadata, but never displays the credentials themselves.

Initial managed services are DNSBL/FraudBL and Guestbook. Manual credentials remain explicit overrides. Dynamic DNS stays manually configured in this pairing version because its current Tools token model maintains one primary user token and should not be silently rotated.

Disconnecting removes the locally stored managed connection and credentials from WordPress.

## Guestbook

Tools remains the authoritative Guestbook database.

Public pages use local WordPress Guestbook JavaScript and local REST endpoints. WordPress forwards requests to Tools from PHP so the Tools Guestbook token never needs to enter browser JavaScript or markup.

The default Tools Guestbook API base is:

```text
https://tools.tornevall.net/api/guestbook
```

A Tools account may own more than one guestbook. After configuring a server-side Guestbook token, or connecting a Tools account that grants Guestbook access, open **Tornevall Tools -> Guestbook connection** and select the guestbook used by this WordPress installation.

Basic shortcode:

```text
[tornevall_guestbook]
```

Optional presentation attributes:

```text
[tornevall_guestbook theme="miazma" limit="10"]
```

Supported themes are `tools`, `miazma` and `terminal`. The entry limit is bounded to 1-50.

Public signing is disabled until Cloudflare Turnstile is configured for the WordPress hostname. Each WordPress installation supplies its own Turnstile site key and secret. Turnstile is validated server-side before a visitor entry is forwarded to Tools.

## Dynamic DNS

Dynamic DNS is disabled by default.

When enabled, the plugin calls:

```text
POST https://tools.tornevall.net/api/dyndns/update
```

with a server-side bearer token and:

```json
{
  "hostname": "home.dyn.tornevall.net",
  "address": "auto"
}
```

Supported WP-Cron schedules are hourly, twice daily and daily. Administrators can also run an immediate update from wp-admin.

## External services

### Tornevall Networks Tools

Tools is used for Statuspage public reads, account pairing, Guestbook and Dynamic DNS integrations.

- Service: https://tools.tornevall.net/
- Status Platform public API: `https://tools.tornevall.net/api/status/v1/pages/{slug}`
- WordPress pairing API: https://tools.tornevall.net/api/integrations/wordpress/device
- Guestbook API: https://tools.tornevall.net/api/guestbook
- Dynamic DNS documentation: https://tools.tornevall.net/docs/en/dynamic-dns
- Terms: https://tools.tornevall.net/docs/en/terms-of-service
- Privacy: https://tools.tornevall.net/docs/en/privacy-policy

Statuspage sends only the configured public slug and normal HTTP metadata. Successful public status responses may be cached locally to provide stale fallback during a temporary API failure.

Authenticated Guestbook, Dynamic DNS and account-pairing credentials remain server-side.

### Cloudflare Turnstile

The Guestbook can use Cloudflare Turnstile to protect public signing. Each WordPress installation supplies its own site key and secret; the secret remains server-side.

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- A public Tools status page for Statuspage rendering
- A Tornevall Networks Tools account or manual credentials for integrations that require authentication
- Cloudflare Turnstile credentials if public Guestbook signing is enabled

## Architecture

```text
tornevall-tools-for-wordpress.php                         Main bootstrap
includes/class-ttfw-settings.php                          Tools overview / Dynamic DNS settings
includes/class-ttfw-api-client.php                        Shared fixed-origin Tools API client
includes/class-ttfw-tools-connection.php                  Tools account pairing and managed credentials
includes/class-ttfw-tools-connection-admin.php            Tools account status and connect/disconnect controls
includes/class-ttfw-dynamic-dns-module.php                Dynamic DNS logic and WP-Cron
includes/class-ttfw-module-registry.php                   Integration overview metadata
includes/class-ttfw-statuspage-settings.php               Selected public status page and cache settings
includes/class-ttfw-statuspage-api.php                    Status Platform v1 public response client/normalizer
includes/class-ttfw-statuspage.php                        Statuspage cache, health semantics and shortcode rendering
includes/class-ttfw-statuspage-admin.php                  Statuspage setup and diagnostics
includes/class-ttfw-guestbook-api.php                     Tools Guestbook server-side client
includes/class-ttfw-guestbook-settings.php                Guestbook credentials / selected book / Turnstile settings
includes/class-ttfw-guestbook-connection-admin.php        Guestbook catalog, selection and remote creation
includes/class-ttfw-guestbook-rest.php                    Local Guestbook REST proxy
includes/class-ttfw-guestbook.php                         Guestbook shortcode/frontend integration
includes/class-ttfw-guestbook-admin.php                   Owner-scoped Guestbook administration
assets/guestbook.js                                       Local Guestbook frontend client
tests/statuspage-contract-test.php                         Deterministic Statuspage contract regression checks
```

## Verification

The GitHub workflow runs PHP syntax checks on PHP 7.4 and 8.4, focused Statuspage contract tests on both versions, and the official WordPress Plugin Check action.

## License

GPL-2.0-or-later.
