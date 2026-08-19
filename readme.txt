=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: tools, dynamic-dns, dns, automation, utilities
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modular bridge between WordPress and Tornevall Networks Tools, starting with Dynamic DNS.

== Description ==

Tornevall Tools for WordPress connects WordPress to optional services provided by Tornevall Networks Tools.

The plugin is designed as a modular toolbox. Individual features are enabled and configured separately so a site does not need to load functionality it does not use.

Version 0.2.0 includes the first public module:

* Dynamic DNS updates through Tornevall Networks Tools.
* Manual updates from wp-admin.
* Scheduled updates using WordPress WP-Cron.
* Server-side token storage.
* Last-run status in the WordPress admin area.
* A shared Tools API client restricted to the Tornevall Networks Tools service origin.

The Dynamic DNS module is disabled by default. It does not contact Tornevall Networks Tools until an administrator enables it and configures a Dynamic DNS hostname and token.

AI functionality is not part of this release. DNSBL/FraudBL protection is also not included because it is maintained separately in the existing Tornevall Networks DNSBL Implementation plugin.

= External service =

The Dynamic DNS module uses Tornevall Networks Tools at `https://tools.tornevall.net`.

When the module is enabled, WordPress sends the configured Dynamic DNS hostname and a server-side bearer token to:

`POST https://tools.tornevall.net/api/dyndns/update`

The request uses `address=auto`, which means Tornevall Networks Tools uses the public source address seen for the WordPress server request as the Dynamic DNS address.

The Dynamic DNS token is stored in WordPress options and is used only by PHP for server-to-server requests. The token is not sent to browser JavaScript.

Service documentation: https://tools.tornevall.net/docs/en/dynamic-dns

Terms of service: https://tools.tornevall.net/docs/en/terms-of-service

Privacy policy: https://tools.tornevall.net/docs/en/privacy-policy

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress` or install the plugin through WordPress when it is available in the Plugin Directory.
2. Activate `Tornevall Tools for WordPress`.
3. Open `Tornevall Tools` in wp-admin.
4. Enable the modules you want to use.

For Dynamic DNS:

1. Create or select a Dynamic DNS hostname in Tornevall Networks Tools.
2. Create or rotate your Dynamic DNS token.
3. Enter the hostname and token in the WordPress settings.
4. Select an update interval.
5. Enable Dynamic DNS and save.
6. Use `Update now` to verify the configuration.

== Frequently Asked Questions ==

= Does the plugin contact Tornevall Networks Tools immediately after activation? =

No. The Dynamic DNS module is disabled by default. Remote requests begin only after an administrator enables and configures it.

= What does the Dynamic DNS module send? =

It sends the configured hostname, the Dynamic DNS bearer token, and `address=auto`. Tools can therefore see and use the public source IP address of the WordPress server request.

= Is the token exposed in the browser? =

No. The token is stored in WordPress options and used by PHP for server-to-server requests.

= How often can Dynamic DNS update? =

The initial module supports WordPress built-in cron intervals: hourly, twice daily, or daily. Administrators can also run an immediate manual update.

= Does this plugin include DNSBL or FraudBL? =

No. DNSBL/FraudBL protection has its own WordPress plugin: Tornevall Networks DNSBL Implementation.

= Does this plugin include AI? =

Not in this release. AI editor functionality is being developed separately and may return later as an optional module when it is production-ready.

== Changelog ==

= 0.2.0 =
* Refocused the plugin from the early AI prototype to a general Tornevall Tools module platform.
* Added the Tornevall Tools admin dashboard and module overview.
* Added a shared server-side Tools API client.
* Added the first Dynamic DNS module.
* Added manual Dynamic DNS updates protected by capability checks and nonces.
* Added scheduled Dynamic DNS updates using WP-Cron.
* Removed AI editor runtime code from the public main release line.

= 0.1.0 =
* Initial development prototype focused on AI editor connectors.
