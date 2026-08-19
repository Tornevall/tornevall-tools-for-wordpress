=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: ai, openai, block-editor, gutenberg, writing, guestbook
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds Tornevall Networks Tools AI, direct OpenAI connectors, and Tools integrations to WordPress.

== Description ==

Tornevall Tools for WordPress adds server-side AI tools and public integrations backed by Tornevall Networks Tools.

Primary features:

* Configurable Tools AI and direct OpenAI providers.
* Tornevall AI block-editor sidebar and assistant block.
* Public `[tornevall_guestbook]` shortcode.
* Guestbook themes: `tools`, `miazma`, and `terminal`.
* Server-side guestbook token flow so visitors can sign without receiving the Tools API token.
* Central guestbook administration under Tools -> Tools Guestbook.
* Optional Tornevall Networks DNSBL addon for visitor-IP filtering and explicit administrator abuse reporting.

== Guestbook ==

Embed the central Tools guestbook with:

`[tornevall_guestbook]`

Choose a theme and number of entries:

`[tornevall_guestbook theme="miazma" limit="10"]`

The entry list is loaded from the public Tools JavaScript embed endpoint over HTTPS and renders inside Shadow DOM so the active WordPress theme does not change the guestbook styling.

When a guestbook token is configured in Tools -> Tools Guestbook, the shortcode also shows a local sign form. The visitor posts to WordPress, and WordPress forwards the entry to Tools with the server-side token. The browser never receives that token.

The central guestbook stays in Tools. The WordPress plugin does not create a second guestbook database.

== Guestbook administration ==

Open Tools -> Tools Guestbook to:

* Configure the HTTPS Tools guestbook API endpoint.
* Store a guestbook token with `guestbook.write` and `guestbook.moderate` scopes.
* Search and filter central entries.
* Hide or restore entries.
* Review private visitor e-mail/source-IP data as an administrator.
* See DNSBL status returned by Tools.
* Install or activate the recommended Tornevall Networks DNSBL Implementation addon when permitted.
* Check source IPs and explicitly report guestbook/web abuse when the DNSBL addon and its own token permit it.

DNSBL is optional. Without the addon, guestbook reading, signing and central moderation continue to work; DNSBL-specific controls are simply unavailable.

Blacklist publication is never automatic. The Report abuse action requires a WordPress administrator and DNSBL add permission.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate the plugin in WordPress.
3. Open Settings -> Tornevall Tools AI when configuring AI providers.
4. Open Tools -> Tools Guestbook to configure central guestbook access.
5. Add `[tornevall_guestbook theme="miazma" limit="10"]` to a page.
6. Optionally install the recommended DNSBL addon from the guestbook admin page.

== Frequently Asked Questions ==

= Are API tokens exposed in the browser? =

No. AI, guestbook and DNSBL tokens remain server-side. The public guestbook form sends its request to a local WordPress REST endpoint, and PHP performs the authenticated request to Tools.

= Does the guestbook require the DNSBL plugin? =

No. DNSBL is an optional addon. Without it, the guestbook still works but cannot perform local visitor-IP checks or explicit blacklist reports from WordPress.

= Can DNSBL automatically blacklist someone who signs the guestbook? =

No. The optional DNSBL addon can block a currently listed source IP before forwarding a new entry, but publishing a new blacklist classification requires an explicit administrator Report abuse action.

= Which guestbook token scopes are needed? =

Use `guestbook.write` for server-to-server signing and `guestbook.moderate` for the WordPress moderation page.

= Which guestbook themes are available? =

Use `tools`, `miazma`, or `terminal`. Invalid values fall back to `tools`.

= Which capability is required to use the editor assistant? =

The AI REST endpoint requires `edit_posts`.

= Which capability is required to configure the plugin and guestbook? =

Administrative settings require `manage_options`.

== Changelog ==

= 0.1.2 =
* Added server-side central guestbook token/API settings.
* Added public WordPress signing proxy without exposing the Tools token.
* Added Tools -> Tools Guestbook moderation UI.
* Added recommended DNSBL addon install/activate handling.
* Added optional DNSBL pre-check, check and explicit abuse-report controls.
* Guestbook continues to work when DNSBL is not installed.

= 0.1.1 =
* Added public Tornevall Tools guestbook shortcode.
* Added Tools, Miazma, and Terminal guestbook themes.
* Added configurable entry limit for the guestbook shortcode.
* Added an HTTPS-only developer filter for staging guestbook embed endpoints.

= 0.1.0 =
* Initial implementation.
* Added wp-admin settings page.
* Added server-side Tools AI connector.
* Added server-side OpenAI connector.
* Added block editor sidebar.
* Added Tornevall AI Assistant block in the Text category.
