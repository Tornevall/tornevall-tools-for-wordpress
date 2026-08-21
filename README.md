# Tornevall Tools for WordPress

Tornevall Tools for WordPress brings selected services from **Tornevall Networks Tools** into WordPress.

The plugin is the WordPress integration layer for Tools. It does not try to recreate Tools inside WordPress; it exposes useful Tools services in a WordPress-native way while keeping service credentials server-side.

## Current integrations

Version `0.2.1` contains two complete Tools integrations:

- **Guestbook** - central Tools-backed guestbook with explicit per-site guestbook selection, shortcode rendering, owner-scoped reads/writes, moderation and Cloudflare Turnstile support.
- **Dynamic DNS** - keep a Tornevall Networks Dynamic DNS hostname synchronized from WordPress manually or through WP-Cron.

AI is not part of the current public runtime. The earlier AI implementation remains separate until it is ready to return as an optional integration.

DNSBL/FraudBL is not duplicated in this plugin. Guestbook moderation can optionally use the bridge exposed by the separate Tornevall Networks DNSBL WordPress plugin when that plugin is installed and active.

## Guestbook

Tools remains the authoritative Guestbook database.

Public pages use the local WordPress Guestbook JavaScript and local REST endpoints. WordPress forwards requests to Tools from PHP so the Tools Guestbook token never needs to enter browser JavaScript or markup.

The default Tools Guestbook API base is:

```text
https://tools.tornevall.net/api/guestbook
```

A Tools account may own more than one guestbook. After configuring the server-side Guestbook token, open **Tornevall Tools -> Guestbook connection**. WordPress requests the token owner's guestbook catalog from Tools only while that setup page is being used. Select the guestbook that belongs to this WordPress site.

The selected guestbook id/slug is stored locally and is added server-side to public list, public signing and admin moderation-list requests. Browser input cannot switch the configured guestbook.

If the token has both `guestbook.write` and `guestbook.moderate`, the same connection page can create a new Tools guestbook and select it immediately. The initial Tools site context can include the WordPress URL, locale and a short description of the site and expected comments. The Tools user behind the token is always the owner; WordPress never supplies an owner id.

Replacing the configured Guestbook token clears the previous guestbook selection, preventing a selection from another Tools user from being reused accidentally.

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
- Guestbook API: https://tools.tornevall.net/api/guestbook
- Dynamic DNS documentation: https://tools.tornevall.net/docs/en/dynamic-dns
- Terms: https://tools.tornevall.net/docs/en/terms-of-service
- Privacy: https://tools.tornevall.net/docs/en/privacy-policy

Guestbook and Dynamic DNS credentials are stored in WordPress and used by PHP for server-to-server requests. Guestbook setup may send selected/created guestbook metadata and the WordPress site's URL, locale and description to Tools when an administrator explicitly uses the Guestbook connection page.

### Cloudflare Turnstile

The Guestbook can use Cloudflare Turnstile to protect public signing.

When configured, the visitor's browser loads the Turnstile widget script from:

```text
https://challenges.cloudflare.com/turnstile/v0/api.js
```

The public site key and challenge run in the browser. WordPress validates the returned challenge token server-side at:

```text
POST https://challenges.cloudflare.com/turnstile/v0/siteverify
```

The Turnstile secret remains server-side.

- Documentation: https://developers.cloudflare.com/turnstile/
- Terms: https://www.cloudflare.com/policies/terms/
- Privacy: https://www.cloudflare.com/policies/privacy/
- Turnstile Privacy Addendum: https://www.cloudflare.com/turnstile-privacy-policy/

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- Tornevall Networks Tools credentials for integrations that require authentication
- Cloudflare Turnstile credentials if public Guestbook signing is enabled

## Architecture

```text
tornevall-tools-for-wordpress.php                         Main bootstrap
includes/class-ttfw-settings.php                          Tools overview / Dynamic DNS settings
includes/class-ttfw-api-client.php                        Shared fixed-origin Tools API client
includes/class-ttfw-dynamic-dns-module.php                Dynamic DNS logic and WP-Cron
includes/class-ttfw-module-registry.php                   Integration overview metadata
includes/class-ttfw-guestbook-api.php                     Tools Guestbook server-side client
includes/class-ttfw-guestbook-settings.php                Guestbook credentials / selected book / Turnstile settings
includes/class-ttfw-guestbook-connection-admin.php        Guestbook catalog, selection and remote creation
includes/class-ttfw-guestbook-rest.php                    Local Guestbook REST proxy
includes/class-ttfw-guestbook.php                         Guestbook shortcode/frontend integration
includes/class-ttfw-guestbook-admin.php                   Owner-scoped Guestbook administration
assets/guestbook.js                                       Local Guestbook frontend client
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
