# Tornevall Tools for WordPress

Tornevall Tools for WordPress brings selected services from **Tornevall Networks Tools** into WordPress.

The plugin acts as the WordPress integration layer for Tools: WordPress provides the site-facing configuration and integration points, while Tornevall Networks Tools provides the corresponding services.

The goal is not to recreate Tools inside WordPress. The goal is to make useful Tools functionality available naturally from a WordPress site.

## Current feature set

Version `0.2.0` contains two public Tools integrations:

- **Guestbook** - embed the public Tornevall Networks Tools guestbook with a WordPress shortcode.
- **Dynamic DNS** - keep a Tornevall Networks Dynamic DNS hostname synchronized with the public address of the WordPress server.

The plugin also includes:

- a dedicated `Tornevall Tools` section in wp-admin
- a small module registry for independent Tools integrations
- a shared server-side client for authenticated `https://tools.tornevall.net/api/*` requests
- server-side credential handling for modules that require authentication

Additional Tools integrations can be added independently without requiring every site to use every Tools service.

## Guestbook

The guestbook integration can be used in any post, page, or widget area that processes WordPress shortcodes.

Basic usage:

```text
[tornevall_guestbook]
```

Choose a theme and number of entries:

```text
[tornevall_guestbook theme="miazma" limit="10"]
```

Available themes:

- `tools` - default Tools styling
- `miazma` - modernized black and blue Miazmabook styling
- `terminal` - compact dark monospace styling

`limit` is clamped to 1-50 entries.

The shortcode only loads the public guestbook service when the shortcode is rendered. The service-owned frontend is loaded over HTTPS from:

```text
https://tools.tornevall.net/guestbook/embed.js
```

It receives only public presentation parameters (`theme`, `limit`, and a generated target identifier). No Tools API token, Dynamic DNS token, AI/provider token, or private guestbook e-mail address is supplied by the WordPress plugin.

For HTTPS staging/testing, developers can override the guestbook embed URL with the `ttfw_guestbook_embed_url` filter. Invalid or non-HTTPS values fall back to the production endpoint.

## Dynamic DNS

The Dynamic DNS integration is intended for WordPress installations where the server's public IP address may change and a Tornevall Networks Dynamic DNS hostname should continue pointing to the server automatically.

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

Dynamic DNS is disabled by default. No Dynamic DNS request is made until an administrator enables the module and supplies both a hostname and token.

Supported schedules use WordPress' built-in WP-Cron intervals:

- hourly
- twice daily
- daily

A manual `Update now` action is also available after configuration. It requires `manage_options` and a WordPress nonce.

## Tornevall Networks Tools service

This plugin integrates with `https://tools.tornevall.net`.

Current service use:

### Guestbook

When a visitor opens a page containing `[tornevall_guestbook]`, the visitor's browser requests the Tools guestbook service. The request includes the selected public presentation parameters. As with a normal web request, the Tools service also receives request metadata such as the visitor's IP address and user agent.

### Dynamic DNS

When enabled, the WordPress server sends the configured Dynamic DNS hostname, the server-side bearer token, and `address=auto` to the Tools Dynamic DNS API. The token remains server-side in WordPress.

Service links:

- Tools: `https://tools.tornevall.net/`
- Dynamic DNS documentation: `https://tools.tornevall.net/docs/en/dynamic-dns`
- Terms of service: `https://tools.tornevall.net/docs/en/terms-of-service`
- Privacy policy: `https://tools.tornevall.net/docs/en/privacy-policy`

Future integrations must document their own external-service data flow before release.

## Related Tornevall WordPress plugins

DNSBL/FraudBL protection is intentionally not duplicated here. It already has its own maintained WordPress plugin:

- `https://github.com/Tornevall/tornevall-wp-dnsbl`
- `https://wordpress.org/plugins/tornevall-networks-dnsbl-implementation/`

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- public HTTPS access to `tools.tornevall.net` for the Guestbook integration
- a Tornevall Networks Dynamic DNS hostname and token to use Dynamic DNS

## Installation

1. Copy the plugin directory to `wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate `Tornevall Tools for WordPress` in wp-admin.
3. Open `Tornevall Tools` to see the available integrations.

For Guestbook, add `[tornevall_guestbook]` to a page or post.

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
includes/class-ttfw-api-client.php            Restricted authenticated Tools API client
includes/class-ttfw-module-registry.php       Module metadata/overview
includes/class-ttfw-guestbook.php             Public Tools guestbook shortcode
includes/class-ttfw-dynamic-dns-module.php    Dynamic DNS logic and scheduling
readme.txt                                    WordPress.org plugin readme
README.md                                     Project/developer documentation
CHANGELOG.md                                  Release history
AGENTS.md                                     Development rules
uninstall.php                                 Option and schedule cleanup
```

Each integration should solve a real WordPress use case, stay isolated from unrelated integrations, and only load the hooks, cron jobs, REST routes, assets, or remote requests it actually needs.

## Planned integrations

The plugin can grow alongside stable Tools services. Candidates include:

- RSS and content workflows
- Whisper transcription and media workflows
- social publishing and integration
- site diagnostics and service health
- editor and content utilities
- AI-assisted workflows after the separate AI implementation is production-ready

This is a development roadmap, not a promise that every Tools service will become a WordPress integration.

## WordPress.org release plan

This repository is the development source. Public releases are intended to be distributed through the WordPress Plugin Directory.

Before the first submission:

1. Smoke-test the package on the latest stable WordPress release.
2. Run the official WordPress Plugin Check checks.
3. Validate `readme.txt` with the WordPress.org readme validator.
4. Review every external-service disclosure and privacy statement.
5. Review the service-owned Guestbook embed against the current Plugin Directory external-code/serviceware rules.
6. Confirm the final WordPress.org plugin name and slug before review begins.
7. Submit a complete installable ZIP to the WordPress Plugin Directory.
8. After approval, publish release files through the WordPress.org SVN repository.

## License

GPL-2.0-or-later.
