=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: ai, openai, block-editor, gutenberg, writing, guestbook
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds Tornevall Networks Tools AI, direct OpenAI connectors, and Tools integrations to WordPress.

== Description ==

Tornevall Tools for WordPress adds a server-side AI assistant to the WordPress block editor and public integrations backed by Tornevall Networks Tools.

The plugin supports two AI provider modes:

* Tornevall Networks Tools AI through the Tools internal AI endpoint.
* Direct OpenAI access through the OpenAI Responses API.

API tokens are configured in wp-admin and stay server-side. The editor uses a local WordPress REST endpoint, which forwards sanitized requests to the configured provider.

Primary features:

* Configurable default persona.
* Configurable default provider.
* Settings page under Settings -> Tornevall Tools AI.
* Block editor sidebar named Tornevall AI.
* Tornevall AI Assistant block in the Text category.
* Insert generated text after selected blocks.
* Replace selected blocks with generated text.
* Public `[tornevall_guestbook]` shortcode for the Tools guestbook.
* Guestbook themes: `tools`, `miazma`, and `terminal`.

== Guestbook ==

Embed the public Tools guestbook with:

`[tornevall_guestbook]`

Choose a theme and number of entries:

`[tornevall_guestbook theme="miazma" limit="10"]`

The guestbook widget is loaded from the public Tools JavaScript embed endpoint over HTTPS. It renders inside Shadow DOM so the active WordPress theme does not change the guestbook styling. Guestbook e-mail addresses and AI/provider tokens are never exposed in the widget.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate the plugin in WordPress.
3. Open Settings -> Tornevall Tools AI when configuring AI providers.
4. Open the block editor and use the Tornevall AI sidebar or Tornevall AI Assistant block.
5. Add `[tornevall_guestbook theme="miazma" limit="10"]` to a page to test the guestbook integration.

== Frequently Asked Questions ==

= Are API tokens exposed in the browser? =

No. Tokens are stored in WordPress options and used by PHP when the local REST endpoint forwards the request. The guestbook embed does not require a provider token.

= Which capability is required to use the editor assistant? =

The REST endpoint requires `edit_posts`.

= Which capability is required to configure the plugin? =

The settings page requires `manage_options`.

= Is the Tools endpoint OpenAI-compatible? =

No. The default Tools endpoint uses a Tools-specific JSON contract with `client_slug` and `user_prompt`.

= Can I use only OpenAI direct access? =

Yes. Set the default provider to OpenAI direct and configure an OpenAI API token and model.

= Which guestbook themes are available? =

Use `tools`, `miazma`, or `terminal`. Invalid values fall back to `tools`.

== Changelog ==

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
