=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: tools, guestbook, dynamic-dns, dns, automation
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to selected Tornevall Networks Tools services, including Guestbook and Dynamic DNS.

== Description ==

Tornevall Tools for WordPress is the WordPress integration for selected services provided by Tornevall Networks Tools.

Version 0.2.1 includes two primary integrations:

* Guestbook: select one guestbook owned by the configured Tools user, then display, submit and moderate it from WordPress.
* Dynamic DNS: keep a Tornevall Networks Dynamic DNS hostname updated manually or through WP-Cron.

Service credentials are kept server-side. Integrations only contact external services when their functionality is configured or explicitly used.

= Guestbook =

Add the Guestbook to a post or page with:

`[tornevall_guestbook]`

Optional presentation attributes:

`[tornevall_guestbook theme="miazma" limit="10"]`

Supported themes are `tools`, `miazma`, and `terminal`. The entry limit is restricted to 1-50.

The Guestbook uses local WordPress JavaScript and local WordPress REST endpoints. WordPress communicates with Tornevall Networks Tools from PHP, which keeps the configured Tools Guestbook token out of browser JavaScript and markup.

After configuring a Guestbook token, open `Tornevall Tools -> Guestbook connection`. The page loads guestbooks owned by the Tools user behind that token and lets an administrator select the one used by this WordPress site. The selected guestbook id/slug is stored locally and added server-side to guestbook read, write and moderation-list requests.

If the configured token has both `guestbook.write` and `guestbook.moderate`, WordPress can also create a new guestbook in Tools and select it immediately. The create form can send the WordPress site URL, locale and a short site description as initial guestbook context. Tools always owns the authoritative guestbook record, and the Tools user behind the token is always the owner.

Replacing the Guestbook token clears the previous guestbook selection.

Guestbook administration is owner-scoped by the configured Tools user and selected guestbook. Public signing can be protected with Cloudflare Turnstile and remains disabled until Turnstile is configured for this WordPress hostname.

The Guestbook can optionally show DNSBL check/report controls when the separate Tornevall Networks DNSBL WordPress plugin is installed, active, and exposes the required bridge capabilities. DNSBL itself is not implemented by this plugin.

= Dynamic DNS =

The Dynamic DNS integration can keep a configured Tornevall Networks Dynamic DNS hostname synchronized with the public source address seen by Tools.

It supports manual updates and WordPress built-in WP-Cron intervals: hourly, twice daily, or daily.

Dynamic DNS is disabled by default and does not send authenticated requests until an administrator enables it and supplies a hostname and token.

= External services =

This plugin integrates with Tornevall Networks Tools at `https://tools.tornevall.net`.

For Guestbook functionality, WordPress communicates server-to-server with the Tools Guestbook API at:

`https://tools.tornevall.net/api/guestbook`

WordPress may send the configured Guestbook bearer token and Guestbook read/write/moderation data to that service. When an administrator explicitly opens or uses the Guestbook connection page, WordPress may also request the token user's guestbook catalog and may send a new guestbook name, slug, site URL, locale and site description when creating a guestbook. The token is stored server-side and is not sent to public browser JavaScript.

For Dynamic DNS, WordPress sends the configured Dynamic DNS hostname, bearer token, and `address=auto` to:

`POST https://tools.tornevall.net/api/dyndns/update`

`address=auto` allows Tools to use the public source IP address of the WordPress server request as the Dynamic DNS address.

Tornevall Networks Tools:
https://tools.tornevall.net/

Dynamic DNS documentation:
https://tools.tornevall.net/docs/en/dynamic-dns

Terms of service:
https://tools.tornevall.net/docs/en/terms-of-service

Privacy policy:
https://tools.tornevall.net/docs/en/privacy-policy

= Cloudflare Turnstile =

When public Guestbook signing is enabled, the plugin uses Cloudflare Turnstile. Each WordPress installation supplies its own Turnstile site key and secret.

The browser loads the Turnstile widget script from:

`https://challenges.cloudflare.com/turnstile/v0/api.js`

The browser receives the public Turnstile site key and challenge. WordPress then sends the returned challenge token and the server-side Turnstile secret to Cloudflare Siteverify at:

`POST https://challenges.cloudflare.com/turnstile/v0/siteverify`

The Turnstile secret is not exposed to browser JavaScript or markup.

Cloudflare Turnstile documentation:
https://developers.cloudflare.com/turnstile/

Cloudflare Terms of Use:
https://www.cloudflare.com/policies/terms/

Cloudflare Privacy Policy:
https://www.cloudflare.com/policies/privacy/

Cloudflare Turnstile Privacy Addendum:
https://www.cloudflare.com/turnstile-privacy-policy/

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress` or install it through WordPress when available in the Plugin Directory.
2. Activate `Tornevall Tools for WordPress`.
3. Open `Tornevall Tools` in wp-admin for the integration overview and Dynamic DNS settings.
4. Open `Tools -> Tools Guestbook` and configure a server-side Tools token with `guestbook.write` and, for moderation/remote creation, `guestbook.moderate`.
5. Open `Tornevall Tools -> Guestbook connection` and select an existing guestbook or create a new one.
6. Configure this WordPress site's own Cloudflare Turnstile site key and secret if public Guestbook signing should be enabled.

== Frequently Asked Questions ==

= What is Tornevall Tools for WordPress? =

It is the WordPress client/integration layer for selected Tornevall Networks Tools services.

= Why do I need to select a guestbook after entering a token? =

One Tools user can own multiple guestbooks. The explicit selection makes this WordPress installation consistently use one of them for public reads, submissions and moderation listings.

= Can WordPress create the Tools guestbook for me? =

Yes, when the configured server-side token has both `guestbook.write` and `guestbook.moderate`. Otherwise you can still select an existing guestbook owned by that Tools user.

= Does the plugin provide a shared Turnstile key? =

No. Each external WordPress installation configures its own Cloudflare Turnstile site key and secret for its hostname.

= Does it include AI? =

AI is not part of the current public release runtime. Earlier AI work is being developed separately and may return later as an optional integration.

= Does it replace the Tornevall DNSBL plugin? =

No. DNSBL/FraudBL remains in the separate Tornevall Networks DNSBL WordPress plugin. Guestbook moderation can optionally use its public bridge when that plugin is installed and active.

= Are Tools tokens exposed in the browser? =

No. Guestbook and Dynamic DNS credentials are kept server-side and used by PHP for authenticated Tools requests.

= Does Dynamic DNS contact Tools immediately after activation? =

No. Dynamic DNS is disabled by default.

== Changelog ==

= 0.2.1 =
* Added an explicit Guestbook connection page for selecting one guestbook owned by the configured Tools user.
* Added remote Tools guestbook creation when the token has both guestbook scopes.
* Added selected-book scoping to public reads, submissions and moderation listings.
* Clears the selected guestbook when the configured Guestbook token changes.
* Keeps Tools and Turnstile credentials server-side and does not fetch the guestbook catalog merely on plugin activation.

= 0.2.0 =
* Established the plugin as the WordPress integration for selected Tornevall Networks Tools services.
* Added the general Tornevall Tools integration overview.
* Added Dynamic DNS with manual and scheduled WP-Cron updates.
* Preserved and integrated the owner-scoped Tools Guestbook, local REST proxy and moderation workflow.
* Added Cloudflare Turnstile support for public Guestbook signing.
* Preserved optional integration with the separate Tornevall DNSBL plugin without duplicating DNSBL functionality.
* Removed the AI runtime, AI REST endpoint and AI editor assets from the public release line.

= 0.1.2 =
* Added owner-scoped Guestbook administration, local API proxying and Turnstile support.

= 0.1.1 =
* Added the first Tools Guestbook shortcode.

= 0.1.0 =
* Initial development prototype.
