# Tornevall Tools for WordPress

Tornevall Tools for WordPress brings selected services from **Tornevall Networks Tools** into WordPress.

The plugin is the WordPress integration layer for Tools. It does not try to recreate Tools inside WordPress; it exposes useful Tools services in a WordPress-native way while keeping service credentials server-side.

## Current integrations

Version `0.2.0` contains two complete Tools integrations:

- **Guestbook** - central Tools-backed guestbook with shortcode rendering, owner-scoped reads/writes, moderation and Cloudflare Turnstile support.
- **Dynamic DNS** - keep a Tornevall Networks Dynamic DNS hostname synchronized from WordPress manually or through WP-Cron.

AI is not part of the current public runtime. The earlier AI implementation remains separate until it is ready to return as an optional integration.

DNSBL/FraudBL is not duplicated in this plugin. Guestbook moderation can optionally use the bridge exposed by the separate Tornevall Networks DNSBL WordPress plugin when that plugin is installed and active.

## Guestbook

Tools remains the authoritative Guestbook database.

Public pages use the local WordPress Guestbook JavaScript and local REST endpoints. WordPress forwards requests to Tools from PHP so the Tools Guestbook token never needs to enter browser JavaScript or markup.

Basic shortcode:

```text
[tornevall_guestbook]
```

Optional presentation attributes:

```text
[tornevall_guestbook theme="miazma" limit="10"]
```

Supported themes are `tools`, `miazma` and `terminal`. The entry limit is bounded to 1-50.

The Guestbook administration supports owner-scoped entry search, filtering, hide/restore and optional DNSBL check/report controls when the standalone DNSBL plugin provides those capabilities.

Public signing is disabled until Cloudflare Turnstile is configured for the WordPress hostname. Turnstile is validated server-side before a visitor entry is forwarded to Tools.

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

`address=auto` tells Tools to use the public source address seen for the WordPress server request.

Supported WP-Cron schedules:

- hourly
- twice daily
- daily

Administrators can also run an immediate `Update now` request from wp-admin.

## External services

### Tornevall Networks Tools

Tools is used for the Guestbook and Dynamic DNS integrations.

- Service: https://tools.tornevall.net/
- Dynamic DNS documentation: https://tools.tornevall.net/docs/en/dynamic-dns
- Terms: https://tools.tornevall.net/docs/en/terms-of-service
- Privacy: https://tools.tornevall.net/docs/en/privacy-policy

Guestbook and Dynamic DNS credentials are stored in WordPress and used by PHP for server-to-server requests.

### Cloudflare Turnstile

The Guestbook can use Cloudflare Turnstile to protect public signing. The browser receives the public site key and challenge widget; the Turnstile secret remains server-side and is used by WordPress for Siteverify validation.

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- Tornevall Networks Tools credentials for integrations that require authentication
- Cloudflare Turnstile credentials if public Guestbook signing is enabled

## Architecture

```text
tornevall-tools-for-wordpress.php             Main bootstrap
includes/class-ttfw-settings.php              Tools overview / Dynamic DNS settings
includes/class-ttfw-api-client.php            Shared fixed-origin Tools API client
includes/class-ttfw-dynamic-dns-module.php    Dynamic DNS logic and WP-Cron
includes/class-ttfw-module-registry.php       Integration overview metadata
includes/class-ttfw-guestbook-api.php         Tools Guestbook server-side client
includes/class-ttfw-guestbook-settings.php    Guestbook credentials / Turnstile settings
includes/class-ttfw-guestbook-rest.php        Local Guestbook REST proxy
includes/class-ttfw-guestbook.php             Guestbook shortcode/frontend integration
includes/class-ttfw-guestbook-admin.php       Owner-scoped Guestbook administration
assets/guestbook.js                           Local Guestbook frontend client
```

## WordPress.org release plan

This repository is the development source. Public releases are intended to be distributed through the WordPress Plugin Directory.

Before the first submission:

1. Smoke-test on the latest stable WordPress release.
2. Run official WordPress Plugin Check.
3. Validate `readme.txt`.
4. Review Tools and Cloudflare external-service disclosures.
5. Confirm the final WordPress.org name and slug.
6. Submit a complete installable ZIP.

## Planned integrations

Future Tools integrations may include RSS/content workflows, Whisper/media workflows, social publishing, site diagnostics and editor utilities. AI can return later as an optional integration when the separate AI work is production-ready.

## License

GPL-2.0-or-later.
