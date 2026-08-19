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
* Server-side guestbook token flow so visitors can read and sign only the guestbook owned by the configured token without receiving the Tools API token.
* Per-site Cloudflare Turnstile protection for public guestbook signing.
* Guestbook administration under Tools -> Tools Guestbook, scoped to the configured token.
* Optional Tornevall Networks DNSBL addon for visitor-IP filtering and explicit administrator abuse reporting.

== Guestbook ==

Render the configured token owner's guestbook with:

`[tornevall_guestbook]`

Choose a theme and number of entries:

`[tornevall_guestbook theme="miazma" limit="10"]`

The browser reads through the local WordPress REST endpoint. WordPress authenticates to Tools from PHP and Tools returns only visible entries created through the same API key. The public entry list renders inside Shadow DOM so the active WordPress theme does not change the guestbook presentation.

The central guestbook stays in Tools. The WordPress plugin does not create a second guestbook database.

== Public signing and Turnstile ==

Public signing requires a guestbook token plus a Cloudflare Turnstile site key and secret configured under Tools -> Tools Guestbook.

The visitor completes Turnstile in the browser. WordPress verifies the returned token server-side with Cloudflare Siteverify and requires the returned hostname to match the current WordPress hostname and the action to equal `guestbook`. Only after verification does WordPress forward the guestbook entry to Tools.

If Turnstile is not configured, the token owner's existing guestbook remains readable but public signing is disabled.

== Guestbook administration ==

Open Tools -> Tools Guestbook to:

* Configure the HTTPS Tools guestbook API endpoint.
* Store a guestbook token with `guestbook.write` and `guestbook.moderate` scopes.
* Configure the WordPress site's Turnstile site key and secret.
* Search and filter entries owned by the configured guestbook token.
* Hide or restore only that token's entries.
* Review private visitor e-mail/source-IP data for those entries as an administrator.
* Install or activate the recommended Tornevall Networks DNSBL Implementation addon when permitted.
* Check source IPs and explicitly report abuse when the DNSBL addon and its own token permit it.

DNSBL is optional. Without the addon, guestbook reading, signing and moderation continue to work; DNSBL-specific controls do not exist in Tools for WordPress.

Blacklist publication is never automatic. The Report abuse action requires a WordPress administrator and DNSBL add permission. Safe source metadata can be forwarded for public DNS TXT audit context; visitor name, e-mail and message content are not intended for TXT publication.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate the plugin in WordPress.
3. Open Settings -> Tornevall Tools AI when configuring AI providers.
4. Open Tools -> Tools Guestbook and configure the guestbook token.
5. Configure a Cloudflare Turnstile widget that permits this WordPress hostname and save its site key and secret.
6. Add `[tornevall_guestbook theme="miazma" limit="10"]` to a page.
7. Optionally install the recommended DNSBL addon from the guestbook admin page.

== Frequently Asked Questions ==

= Are API tokens or Turnstile secrets exposed in the browser? =

No. AI, guestbook and DNSBL tokens and the Turnstile secret remain server-side. Only the public Turnstile site key is rendered in the page.

= Can one WordPress owner see another owner's guestbook entries? =

No. Public owner feeds and remote moderation are scoped by Tools to the exact configured API key. Foreign entry ids cannot be moderated through that token.

= Does the guestbook require the DNSBL plugin? =

No. DNSBL is an optional addon. Without it, the guestbook still works but has no DNSBL check/report integration.

= Can DNSBL automatically blacklist someone who signs the guestbook? =

No. The optional DNSBL addon can block a currently listed source IP before forwarding a new entry, but publishing a new classification requires an explicit administrator Report abuse action and a DNSBL token with add permission.

= Which guestbook token scopes are needed? =

Use `guestbook.write` for owner-scoped reading and server-to-server signing, and `guestbook.moderate` for the WordPress moderation page.

= Which guestbook themes are available? =

Use `tools`, `miazma`, or `terminal`. Invalid values fall back to `tools`.

= Which capability is required to use the editor assistant? =

The AI REST endpoint requires `edit_posts`.

= Which capability is required to configure the plugin and guestbook? =

Administrative settings require `manage_options`.

== Changelog ==

= 0.1.2 =
* Added server-side guestbook token/API settings.
* Added owner-scoped public guestbook reads and WordPress moderation.
* Added public WordPress signing proxy without exposing the Tools token.
* Added per-site Cloudflare Turnstile verification with hostname/action validation.
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