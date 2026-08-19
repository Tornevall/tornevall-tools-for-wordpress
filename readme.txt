=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: tools, guestbook, dynamic-dns, dns, utilities
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to Tornevall Networks Tools with Guestbook and Dynamic DNS integrations.

== Description ==

Tornevall Tools for WordPress brings selected services from Tornevall Networks Tools into WordPress.

The plugin acts as the WordPress integration layer for Tools. Each supported service is exposed through a WordPress feature suited to that service.

The current release includes:

* Guestbook: embed the public Tornevall Networks Tools guestbook with a shortcode.
* Dynamic DNS: keep a Tornevall Networks Dynamic DNS hostname updated from your WordPress server.
* A Tornevall Tools wp-admin overview for the available integrations.

= Guestbook =

Embed the public Tools guestbook with:

`[tornevall_guestbook]`

Choose a theme and number of entries:

`[tornevall_guestbook theme="miazma" limit="10"]`

Available themes are `tools`, `miazma`, and `terminal`. The `limit` value is restricted to 1-50 entries.

The Guestbook service is contacted only on pages where the shortcode is rendered. No Tools API token or other private service credential is required for the public guestbook.

= Dynamic DNS =

Dynamic DNS is useful for WordPress installations where the server's public IP address may change and a Tornevall Networks Dynamic DNS hostname should continue pointing to the server.

The module can:

* Configure a Tornevall Networks Dynamic DNS hostname from wp-admin.
* Keep the hostname updated automatically through WordPress WP-Cron.
* Trigger an immediate update manually from wp-admin.
* Store the Dynamic DNS token server-side.
* Show the result of the latest update without exposing credentials.

Dynamic DNS is disabled by default. It does not contact Tornevall Networks Tools until an administrator enables it and configures both a hostname and token.

The initial module supports WordPress built-in cron intervals hourly, twice daily, and daily, plus a manual `Update now` action.

= External service =

This plugin integrates with Tornevall Networks Tools at `https://tools.tornevall.net`.

Guestbook service:

When a visitor opens a page containing `[tornevall_guestbook]`, the visitor's browser loads the public guestbook service from:

`https://tools.tornevall.net/guestbook/embed.js`

The plugin supplies only public presentation parameters such as the selected theme, entry limit, and generated target identifier. The Guestbook integration does not send a Tools API token, Dynamic DNS token, or other private plugin credential. As with a normal web request, Tornevall Networks Tools receives request metadata such as the visitor's IP address and user agent.

Dynamic DNS service:

When Dynamic DNS is enabled, the WordPress server sends the configured Dynamic DNS hostname and server-side bearer token to:

`POST https://tools.tornevall.net/api/dyndns/update`

The request uses `address=auto`, which allows Tornevall Networks Tools to use the public source IP address seen for the WordPress server request as the Dynamic DNS address.

The Dynamic DNS token is stored in WordPress options and is used only by PHP for server-to-server requests. It is not sent to browser JavaScript.

Service: https://tools.tornevall.net/

Dynamic DNS documentation: https://tools.tornevall.net/docs/en/dynamic-dns

Terms of service: https://tools.tornevall.net/docs/en/terms-of-service

Privacy policy: https://tools.tornevall.net/docs/en/privacy-policy

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress` or install it through WordPress when available in the Plugin Directory.
2. Activate `Tornevall Tools for WordPress`.
3. Open `Tornevall Tools` in wp-admin to see the available integrations.

For Guestbook:

1. Add `[tornevall_guestbook]` to a page or post.
2. Optionally set `theme` and `limit` shortcode attributes.

For Dynamic DNS:

1. Create or select a Dynamic DNS hostname in Tornevall Networks Tools.
2. Create or rotate your Dynamic DNS token.
3. Enter the hostname and token in the WordPress settings.
4. Select an update interval.
5. Enable Dynamic DNS and save.
6. Use `Update now` to verify the configuration.

== Frequently Asked Questions ==

= What is Tornevall Tools for WordPress? =

It is the WordPress integration for selected services provided by Tornevall Networks Tools.

= Which Tools integrations are included now? =

The current release includes the public Tools Guestbook shortcode and Dynamic DNS management.

= Does the plugin contact Tornevall Networks Tools immediately after activation? =

No. Activation by itself does not make a service request. Guestbook is contacted when a visitor opens a page containing the Guestbook shortcode. Dynamic DNS starts making requests only after an administrator enables and configures it.

= Does the Guestbook require a token? =

No. The current Guestbook integration uses the public Tools guestbook service and passes only public presentation parameters.

= What does the Dynamic DNS module send? =

It sends the configured hostname, the Dynamic DNS bearer token, and `address=auto`. Tornevall Networks Tools can therefore see and use the public source IP address of the WordPress server request.

= Is the Dynamic DNS token exposed in the browser? =

No. The token is stored in WordPress options and used by PHP for server-to-server requests.

= How often can Dynamic DNS update? =

The module supports WordPress built-in cron intervals: hourly, twice daily, or daily. Administrators can also run an immediate manual update.

= Does this replace the Tornevall Networks DNSBL plugin? =

No. DNSBL/FraudBL protection remains a separate WordPress plugin and is not duplicated here.

== Changelog ==

= 0.2.0 =
* Established Tornevall Tools for WordPress as the WordPress integration for selected Tornevall Networks Tools services.
* Included the public Tools Guestbook shortcode as a current integration.
* Added the Tornevall Tools admin dashboard and module foundation.
* Added a shared server-side Tools API client.
* Added Dynamic DNS configuration, manual updates, and scheduled WP-Cron updates.
* Kept Dynamic DNS credentials server-side and documented both current external-service data flows.
* Removed the earlier AI runtime from the public release line while it remains under separate development.

= 0.1.1 =
* Added public Tornevall Networks Tools Guestbook shortcode.
* Added Tools, Miazma, and Terminal guestbook themes.
* Added configurable entry limit for the Guestbook shortcode.

= 0.1.0 =
* Initial development prototype.
