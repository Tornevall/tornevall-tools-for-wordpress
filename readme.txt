=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: tools, guestbook, dynamic-dns, statuspage, monitoring, automation
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to selected Tornevall Networks Tools services, including Guestbook, Dynamic DNS, Statuspage and optional account-managed credentials.

== Description ==

Tornevall Tools for WordPress is the WordPress integration for selected services provided by Tornevall Networks Tools.

Version 0.3.0 includes:

* Guestbook: select one guestbook owned by the configured Tools user, then display, submit and moderate it from WordPress.
* Dynamic DNS: keep a Tornevall Networks Dynamic DNS hostname updated manually or through WP-Cron.
* Statuspage: render a public Tools status page with overall service state, components, active incidents and incident updates through a shortcode or native Gutenberg block.
* Tools account connection: explicitly authorize this WordPress installation from a logged-in Tools account and let Tools create dedicated site credentials for supported services.

Service credentials are kept server-side. Integrations only contact external services when their functionality is configured or explicitly used.

= Statuspage =

Open `Tornevall Tools -> Statuspage` and configure the public Tools status page slug. The plugin reads the documented public Status Platform API and stores a bounded live cache plus the last successful snapshot.

For the block editor, insert the `Tornevall Statuspage` block from the Tornevall Tools category. The block JavaScript only provides editor controls and a placeholder; frontend rendering is dynamic/server-side and uses the same PHP renderer, cache and outage semantics as the shortcode. Opening the editor does not trigger a Status Platform request merely to preview the block.

The shortcode remains available:

`[tornevall_statuspage]`

To include recent resolved incidents, enable the block inspector option or use:

`[tornevall_statuspage history="1"]`

The WordPress rendering includes the overall status, components, active incidents and incident update timelines. Tools remains authoritative for all status data.

Status semantics deliberately distinguish a confirmed outage from a communication problem. A confirmed `major_outage` may be rendered as critical/red by a theme. Missing configuration is neutral. Unknown status remains unknown. If the Tools API cannot be reached, WordPress uses the last successful snapshot when available and marks it stale instead of reporting an outage. If no successful snapshot exists, the integration reports the status as temporarily unavailable, not as a major outage.

The public status API does not require a bearer token. No Tools credential is inserted into public HTML or browser JavaScript.

= Tools account connection =

Open `Tornevall Tools` in wp-admin and choose `Connect to Tornevall Tools`.

WordPress requests a short-lived pairing code from Tools and redirects the administrator to `https://tools.tornevall.net`, where the administrator signs in and approves or denies the WordPress site. On approval, Tools creates dedicated credentials for this site instead of exposing or reusing existing raw service tokens. WordPress retrieves the newly created credentials through a one-time server-to-server exchange.

The initial managed services are DNSBL/FraudBL and Guestbook:

* DNSBL/FraudBL: when the logged-in Tools user has active DNSBL access, Tools can create a dedicated non-admin site credential based on an approved permission set. This plugin exposes that credential only through a server-side WordPress filter for the separate Tornevall Networks DNSBL plugin. DNSBL functionality is not implemented here.
* Guestbook: when the logged-in Tools user owns an active guestbook, Tools can create a dedicated owner-scoped Guestbook credential. It is used automatically only when the manual Guestbook token field is empty.

Manual service credentials remain explicit overrides. Dynamic DNS is not automatically managed by this pairing version because the current Tools Dynamic DNS token model maintains one primary user token and should not be silently rotated.

Managed credentials are never displayed in wp-admin or browser JavaScript. Disconnecting removes the locally stored connection and managed credentials from WordPress.

= Guestbook =

Add the Guestbook to a post or page with:

`[tornevall_guestbook]`

Optional presentation attributes:

`[tornevall_guestbook theme="miazma" limit="10"]`

Supported themes are `tools`, `miazma`, and `terminal`. The entry limit is restricted to 1-50.

The Guestbook uses local WordPress JavaScript and local WordPress REST endpoints. WordPress communicates with Tornevall Networks Tools from PHP, which keeps the effective Tools Guestbook token out of browser JavaScript and markup.

After configuring a manual Guestbook token or connecting a Tools account that grants Guestbook access, open `Tornevall Tools -> Guestbook connection`. The page loads guestbooks owned by the Tools user behind that token and lets an administrator select the one used by this WordPress site. The selected guestbook id/slug is stored locally and added server-side to guestbook read, write and moderation-list requests.

Existing owned guestbooks can be edited from the same page instead of creating a duplicate. Choose `Edit` next to a guestbook to change its name, slug, theme, site URL, language, description and active/hosted state. Editing requires both `guestbook.write` and `guestbook.moderate`. If the edited book is currently selected, WordPress refreshes its locally stored slug after Tools confirms the change.

If the effective token has both `guestbook.write` and `guestbook.moderate`, WordPress can also create a new guestbook in Tools and select it immediately. The create form can send the WordPress site URL, locale and a short site description as initial guestbook context. Tools always owns the authoritative guestbook record, and the Tools user behind the token is always the owner.

Guestbook token/Turnstile settings and entry moderation are grouped under `Tornevall Tools -> Tools Guestbook`. The page no longer appears as a separate item under WordPress core `Tools`.

Replacing the manually configured Guestbook token clears the previous guestbook selection.

Guestbook administration is owner-scoped by the configured Tools user and selected guestbook. Public signing can be protected with Cloudflare Turnstile and remains disabled until Turnstile is configured for this WordPress hostname.

The Guestbook can optionally show DNSBL check/report controls when the separate Tornevall Networks DNSBL WordPress plugin is installed, active, and exposes the required bridge capabilities. DNSBL itself is not implemented by this plugin.

= Dynamic DNS =

The Dynamic DNS integration can keep a configured Tornevall Networks Dynamic DNS hostname synchronized with the public source address seen by Tools.

It supports manual updates and WordPress built-in WP-Cron intervals: hourly, twice daily, or daily.

Dynamic DNS is disabled by default and does not send authenticated requests until an administrator enables it and supplies a hostname and token.

= External services =

This plugin integrates with Tornevall Networks Tools at `https://tools.tornevall.net`.

Statuspage uses the public read-only endpoint:

`GET https://tools.tornevall.net/api/status/v1/pages/{slug}`

The request contains only the configured public status page slug and normal HTTP metadata. No bearer credential is required. The returned public status data may be cached locally so the WordPress page can show the last known result if the live service is temporarily unavailable. The Gutenberg editor script does not call this endpoint directly.

For the optional Tools account connection, WordPress sends the site name, site URL, same-host callback URL and requested service names to:

`POST https://tools.tornevall.net/api/integrations/wordpress/device`

This request is sent only after a WordPress administrator chooses to connect. The administrator is then redirected to Tools to sign in and approve or deny the site. After approval, WordPress sends the short-lived device code server-to-server to:

`POST https://tools.tornevall.net/api/integrations/wordpress/token`

Tools returns only newly created site-specific credentials through this one-time exchange. Existing raw service token values are not requested by WordPress through this flow.

For Guestbook functionality, WordPress communicates server-to-server with the Tools Guestbook API at:

`https://tools.tornevall.net/api/guestbook`

WordPress may send the effective Guestbook bearer token and Guestbook read/write/moderation data to that service. When an administrator explicitly opens or uses the Guestbook connection page, WordPress may request the token user's guestbook catalog, create a guestbook, or update an existing owned guestbook. Create/update data can include the guestbook name, slug, theme, site URL, locale/language, description and active/hosted state. The token is stored server-side and is not sent to public browser JavaScript.

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
3. Open `Tornevall Tools` in wp-admin. Optionally connect a logged-in Tools account to let Tools create managed DNSBL/Guestbook credentials for this site.
4. For Statuspage, open `Tornevall Tools -> Statuspage`, enter the public Tools status page slug and insert the `Tornevall Statuspage` block, or use `[tornevall_statuspage]` for shortcode compatibility.
5. For Guestbook, open `Tornevall Tools -> Guestbook connection` and select an existing guestbook, edit an existing guestbook, or create a new one. A manual Guestbook token can still be configured and overrides a managed one.
6. Configure this WordPress site's own Cloudflare Turnstile site key and secret if public Guestbook signing should be enabled.
7. Configure Dynamic DNS manually if required.

== Frequently Asked Questions ==

= Does Statuspage support the block editor? =

Yes. Insert the native `Tornevall Statuspage` block. It is a dynamic block: JavaScript handles editor controls, while public output is rendered by the same PHP path used by the shortcode.

= What happens if the Statuspage API is unreachable? =

A network/API failure is not treated as a confirmed outage. WordPress shows the last successful status as stale when available. Without a previous successful response it shows a temporary unavailable/unknown state. Only an explicit `major_outage` from Tools is treated as a major outage.

= Does Statuspage expose a Tools token in the browser? =

No. The Statuspage endpoint is public and read-only, and the plugin does not insert Tools credentials into Statuspage markup or browser JavaScript.

= What is Tornevall Tools for WordPress? =

It is the WordPress client/integration layer for selected Tornevall Networks Tools services.

= Does connecting my Tools account copy my existing tokens into WordPress? =

No. Tools creates dedicated credentials for this WordPress site after you approve the connection. Existing raw service token values are not returned through the pairing flow.

= What if I already configured a Guestbook token manually? =

The manual token remains the explicit override. A managed Guestbook token is used only when the manual token field is empty.

= Why do I need to select a guestbook after entering or receiving a token? =

One Tools user can own multiple guestbooks. The explicit selection makes this WordPress installation consistently use one of them for public reads, submissions and moderation listings.

= Can I change the slug of an existing Tools guestbook from WordPress? =

Yes. Open `Tornevall Tools -> Guestbook connection` and choose `Edit` for the owned guestbook. The change updates the existing Tools record instead of creating a new guestbook. The effective token must have both Guestbook scopes.

= Can WordPress create the Tools guestbook for me? =

Yes, when the effective server-side token has both `guestbook.write` and `guestbook.moderate`. Otherwise you can still select an existing guestbook owned by that Tools user.

= Does the plugin provide a shared Turnstile key? =

No. Each external WordPress installation configures its own Cloudflare Turnstile site key and secret for its hostname.

= Does it include AI? =

AI is not part of the current public release runtime. Earlier AI work is being developed separately and may return later as an optional integration.

= Does it replace the Tornevall DNSBL plugin? =

No. DNSBL/FraudBL remains in the separate Tornevall Networks DNSBL WordPress plugin. This plugin can supply a managed DNSBL credential to that plugin through a server-side filter, but all DNSBL behavior stays in the DNSBL plugin.

= Are Tools tokens exposed in the browser? =

No. Guestbook, Dynamic DNS and managed service credentials are kept server-side and used by PHP for authenticated Tools requests.

= Does Dynamic DNS contact Tools immediately after activation? =

No. Dynamic DNS is disabled by default.

== Changelog ==

= Unreleased =
* Existing owned guestbooks can now be edited from Guestbook connection, including slug and site metadata, instead of requiring a duplicate book.
* Tools Guestbook settings/moderation now appears under the Tornevall Tools menu instead of WordPress core Tools.

= 0.3.0 =
* Added Statuspage configuration and public rendering through the native Gutenberg block and `[tornevall_statuspage]`.
* Added Tools Status Platform v1 public response validation, component status and incident timelines.
* Added bounded live caching and last-successful fallback. Communication failures are stale/unavailable, not major outages.
* Added focused Statuspage contract and Gutenberg block tests to the PHP compatibility CI matrix.

= 0.2.2 =
* Added explicit Tools account pairing from wp-admin with login and approval on tools.tornevall.net.
* Added dedicated site-managed DNSBL/FraudBL and Guestbook credentials from the Tools pairing API.
* Added a server-side managed DNSBL credential bridge for the separate Tornevall DNSBL plugin.
* Guestbook now falls back to the managed Guestbook credential only when no manual token is configured.
* Added a public-safe account/service status card without rendering raw credentials.

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
