# Tornevall Tools for WordPress

Tornevall Tools for WordPress brings selected services from **Tornevall Networks Tools** into WordPress.

The plugin acts as the WordPress integration layer for Tools: configuration lives in wp-admin, credentials stay server-side, and each supported Tools service is exposed as an independent WordPress module where that integration is useful.

The goal is not to recreate Tools inside WordPress. The goal is to make existing Tools functionality available naturally from a WordPress site.

## Current feature set

Version `0.2.0` provides the first complete Tools integration: **Dynamic DNS**.

Included now:

- a dedicated `Tornevall Tools` section in wp-admin
- a module foundation for independent Tools integrations
- a shared server-side client for documented `https://tools.tornevall.net/api/*` endpoints
- Dynamic DNS configuration from WordPress
- manual Dynamic DNS updates from wp-admin
- scheduled Dynamic DNS updates through WP-Cron
- server-side Dynamic DNS token storage
- last-run status without exposing credentials

Additional Tools integrations can be added as separate modules without requiring every site to use every Tools service.

## Dynamic DNS

The first module connects WordPress to the Tornevall Networks Tools Dynamic DNS service.

It is intended for WordPress installations where the server's public IP address may change and a Tornevall Networks Dynamic DNS hostname should continue pointing to the server automatically.

The module uses:

```text
POST https://tools.tornevall.net/api/dyndns/update
```

with a server-side Dynamic DNS token and:

```json
{
  "hostname": "home.dyn.tornevall.net",
  "address": "auto"
}
```

`address=auto` tells Tools to use the public source address seen for the WordPress server request.

The module is disabled by default. No Dynamic DNS request is made until an administrator enables the module and supplies both a hostname and token.

Supported schedules use WordPress' built-in WP-Cron intervals:

- hourly
- twice daily
- daily

A manual `Update now` action is also available after the module is configured. It requires `manage_options` and a WordPress nonce.

## Tornevall Networks Tools service

Modules in this plugin may communicate with Tornevall Networks Tools when the corresponding feature has been enabled and configured by the site administrator.

For the current Dynamic DNS module:

- Service: `https://tools.tornevall.net`
- Documentation: `https://tools.tornevall.net/docs/en/dynamic-dns`
- Terms of service: `https://tools.tornevall.net/docs/en/terms-of-service`
- Privacy policy: `https://tools.tornevall.net/docs/en/privacy-policy`

The Dynamic DNS token stays in WordPress options and is only used by PHP for server-to-server requests. It is not exposed to browser JavaScript.

Future modules must document their own external-service data flow before release.

## Related Tornevall WordPress plugins

DNSBL/FraudBL protection is intentionally not duplicated here. It already has its own maintained WordPress plugin:

- `https://github.com/Tornevall/tornevall-wp-dnsbl`
- `https://wordpress.org/plugins/tornevall-networks-dnsbl-implementation/`

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- a Tornevall Networks Tools account/service credential for modules that require authentication

For Dynamic DNS specifically, a Dynamic DNS hostname and token are required.

## Installation

1. Copy the plugin directory to `wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate `Tornevall Tools for WordPress` in wp-admin.
3. Open `Tornevall Tools`.
4. Configure the Tools modules you want to use.

For Dynamic DNS:

1. Create or select a Dynamic DNS hostname in Tornevall Networks Tools.
2. Create or rotate the Dynamic DNS token for your Tools account.
3. Enter the hostname and token in WordPress.
4. Select an update interval.
5. Enable Dynamic DNS and save.
6. Use `Update now` to verify the configuration immediately.

## Architecture

```text
tornevall-tools-for-wordpress.php             Main plugin bootstrap
includes/class-ttfw-plugin.php                Plugin lifecycle and hooks
includes/class-ttfw-settings.php              Admin UI and option sanitization
includes/class-ttfw-api-client.php            Restricted Tools API HTTP client
includes/class-ttfw-module-registry.php       Module metadata/overview
includes/class-ttfw-dynamic-dns-module.php    Dynamic DNS logic and scheduling
readme.txt                                    WordPress.org plugin readme
README.md                                     Project/developer documentation
CHANGELOG.md                                  Release history
AGENTS.md                                     Development rules
uninstall.php                                 Option and schedule cleanup
```

Each module should solve a real WordPress use case, stay isolated from unrelated modules, and only load the hooks, cron jobs, REST routes, assets, or remote requests it actually needs.

## Planned integrations

The plugin can grow alongside stable Tools services. Current candidates include:

- guestbook integration once the Tools guestbook API is ready for consumers
- RSS and content workflows
- Whisper transcription and media workflows
- social publishing and integration
- site diagnostics and service health
- editor and content utilities
- AI-assisted workflows after the separate AI implementation is production-ready

This is a development roadmap, not a promise that every Tools service will become a WordPress module.

## WordPress.org release plan

This repository is the development source. Public releases are intended to be distributed through the WordPress Plugin Directory.

Before the first submission:

1. Smoke-test the package on the latest stable WordPress release.
2. Run the official WordPress Plugin Check checks.
3. Validate `readme.txt` with the WordPress.org readme validator.
4. Review every external-service disclosure and privacy statement.
5. Confirm the final WordPress.org plugin name and slug before review begins.
6. Submit a complete installable ZIP to the WordPress Plugin Directory.
7. After approval, publish release files through the WordPress.org SVN repository.

## License

GPL-2.0-or-later.
