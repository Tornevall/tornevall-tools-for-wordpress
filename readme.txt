=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: tools, dynamic-dns, dns, automation, utilities
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to selected Tornevall Networks Tools services. The first module provides Dynamic DNS updates.

== Description ==

Tornevall Tools for WordPress brings selected services from Tornevall Networks Tools into WordPress.

The plugin acts as a WordPress integration layer for Tools. Supported services are exposed as independent modules so site administrators can configure and use only the functionality they need.

The initial release includes a complete Dynamic DNS integration:

* Configure a Tornevall Networks Dynamic DNS hostname from wp-admin.
* Keep the hostname updated automatically through WordPress WP-Cron.
* Trigger an immediate update manually from wp-admin.
* Store the Dynamic DNS token server-side.
* View the result of the latest update without exposing credentials.

The Dynamic DNS module is disabled by default. It does not contact Tornevall Networks Tools until an administrator enables it and configures both a hostname and token.

Additional Tornevall Networks Tools services may be added as independent WordPress modules in future releases.

= Dynamic DNS =

The Dynamic DNS module is useful for WordPress installations where the server's public IP address may change and a Tornevall Networks Dynamic DNS hostname should continue pointing to the server.

The module sends an authenticated server-to-server request to:

`POST https://tools.tornevall.net/api/dyndns/update`

with the configured hostname and `address=auto`.

`address=auto` means Tornevall Networks Tools uses the public source IP address seen for the WordPress server request as the Dynamic DNS address.

The initial module supports the WordPress built-in cron intervals hourly, twice daily, and daily, plus a manual `Update now` action.

= External service =

This plugin integrates with Tornevall Networks Tools at `https://tools.tornevall.net`.

For the current Dynamic DNS module, WordPress sends:

* the configured Dynamic DNS hostname
* the configured Dynamic DNS bearer token
* `address=auto`, allowing the service to use the public source IP address of the WordPress server request

This data is sent only after the administrator enables and configures the Dynamic DNS module.

The Dynamic DNS token is stored in WordPress options and is used only by PHP for server-to-server requests. It is not sent to browser JavaScript.

Service documentation: https://tools.tornevall.net/docs/en/dynamic-dns

Terms of service: https://tools.tornevall.net/docs/en/terms-of-service

Privacy policy: https://tools.tornevall.net/docs/en/privacy-policy

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress` or install it through WordPress when available in the Plugin Directory.
2. Activate `Tornevall Tools for WordPress`.
3. Open `Tornevall Tools` in wp-admin.
4. Configure the Tools modules you want to use.

For Dynamic DNS:

1. Create or select a Dynamic DNS hostname in Tornevall Networks Tools.
2. Create or rotate your Dynamic DNS token.
3. Enter the hostname and token in WordPress.
4. Select an update interval.
5. Enable Dynamic DNS and save.
6. Use `Update now` to verify the configuration.

== Frequently Asked Questions ==

= What is Tornevall Tools for WordPress? =

It is the WordPress integration for selected services provided by Tornevall Networks Tools. Each supported service can be exposed as an independent WordPress module.

= Does the plugin contact Tornevall Networks Tools immediately after activation? =

No. The Dynamic DNS module is disabled by default. Remote requests begin only after an administrator enables and configures it.

= What does the Dynamic DNS module send? =

It sends the configured hostname, the Dynamic DNS bearer token, and `address=auto`. Tornevall Networks Tools can therefore see and use the public source IP address of the WordPress server request.

= Is the token exposed in the browser? =

No. The token is stored in WordPress options and used by PHP for server-to-server requests.

= How often can Dynamic DNS update? =

The initial module supports WordPress built-in cron intervals: hourly, twice daily, or daily. Administrators can also run an immediate manual update.

= Does this replace the Tornevall Networks DNSBL plugin? =

No. DNSBL/FraudBL protection remains a separate WordPress plugin and is not duplicated here.

== Changelog ==

= 0.2.0 =
* Established Tornevall Tools for WordPress as the WordPress integration for selected Tornevall Networks Tools services.
* Added the Tornevall Tools admin dashboard and module foundation.
* Added a shared server-side Tools API client.
* Added the first complete module: Dynamic DNS.
* Added manual Dynamic DNS updates protected by capability checks and nonces.
* Added scheduled Dynamic DNS updates using WP-Cron.
* Kept service credentials server-side and documented the external service data flow.

= 0.1.0 =
* Initial development prototype.
