# Tornevall Tools for WordPress

Tornevall Tools for WordPress is a modular WordPress integration for services provided by Tornevall Networks Tools.

The public plugin is intentionally not tied to one service category. Features are added as independent modules and should only contact external Tornevall Networks services when the site administrator enables and configures them.

## Current release line

Version `0.2.0` refocuses the plugin away from the earlier AI-only prototype and introduces the general Tools foundation.

Included now:

- a dedicated `Tornevall Tools` wp-admin page
- a small module registry used to present independent Tools features
- a shared server-side client restricted to documented `https://tools.tornevall.net/api/*` endpoints
- a Dynamic DNS module
- manual Dynamic DNS updates from wp-admin
- scheduled Dynamic DNS updates through WP-Cron
- server-side Dynamic DNS token storage
- last-run status without storing or displaying credentials

Not included in this release line:

- AI editor integration
- DNSBL/FraudBL protection

The AI editor work remains under development and can return later as an optional module. DNSBL/FraudBL already has a separate maintained WordPress plugin and is deliberately not duplicated here:

- https://github.com/Tornevall/tornevall-wp-dnsbl
- https://wordpress.org/plugins/tornevall-networks-dnsbl-implementation/

## Dynamic DNS

ToolsAPI provides a Dynamic DNS service under `/api/dyndns`.

The WordPress module uses:

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

## External service

The Dynamic DNS module communicates with Tornevall Networks Tools only after explicit configuration.

Service documentation:

- https://tools.tornevall.net/docs/en/dynamic-dns

Terms of service:

- https://tools.tornevall.net/docs/en/terms-of-service

Privacy policy:

- https://tools.tornevall.net/docs/en/privacy-policy

The Dynamic DNS token stays in WordPress options and is only used by PHP for server-to-server requests. It is not exposed to browser JavaScript.

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- a Tornevall Networks Tools Dynamic DNS hostname and token to use the Dynamic DNS module

## Installation

1. Copy the plugin directory to `wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate `Tornevall Tools for WordPress` in wp-admin.
3. Open `Tornevall Tools`.
4. Enable and configure the modules you want to use.

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

The module architecture is intentionally small. New modules should remain isolated and should not load editor, frontend, cron, REST, or remote-request behavior unless that module needs it.

## WordPress.org release plan

This repository is the development source. A public release should be submitted to and distributed through the WordPress Plugin Directory rather than using a custom plugin updater.

Before the first submission:

1. Smoke-test the package on the latest stable WordPress release.
2. Run the official WordPress Plugin Check checks.
3. Validate `readme.txt` with the WordPress.org readme validator.
4. Review every external service disclosure and privacy statement.
5. Confirm the final WordPress.org plugin name and slug before review begins.
6. Submit a complete installable ZIP to the WordPress.org Plugin Directory.
7. After approval, publish release files through the WordPress.org SVN repository.

## Roadmap

Potential future Tornevall Tools modules include:

- RSS/content workflows
- Whisper transcription and media workflows
- social publishing/integration
- site diagnostics and service health
- editor/content utilities
- AI assistance after the separate AI work is ready for production

This list is directional. A module should only be added when the corresponding Tools service has a stable contract and the WordPress integration is independently useful.

## License

GPL-2.0-or-later.
