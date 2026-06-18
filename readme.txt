=== Tornevall Tools for WordPress ===
Contributors: tornevall
Tags: ai, openai, block-editor, gutenberg, writing, markdown
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds Tornevall Networks Tools AI and direct OpenAI connectors to the WordPress block editor.

== Description ==

Tornevall Tools for WordPress adds a server-side AI assistant to the WordPress block editor.

The plugin supports two provider modes:

* Tornevall Networks Tools AI through the Tools internal AI endpoint.
* Direct OpenAI access through the OpenAI Responses API.

API tokens are configured in wp-admin and stay server-side. The editor uses local WordPress REST endpoints, which forward sanitized requests to the configured provider.

Primary features:

* Configurable default persona.
* Configurable default provider.
* Settings page under Settings -> Tornevall Tools AI.
* Provider test buttons in wp-admin.
* Block editor sidebar named Tornevall AI.
* Tornevall AI Assistant block in the Text category.
* Separate Instructions and Custom text fields.
* Document upload into the Custom text field.
* Markdown conversion to WordPress-compatible blocks.
* Insert or replace selected blocks with generated content.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/tornevall-tools-for-wordpress`.
2. Activate the plugin in WordPress.
3. Open Settings -> Tornevall Tools AI.
4. Configure at least one provider.
5. Save settings and run the provider test.
6. Open the block editor and use the Tornevall AI sidebar or Tornevall AI Assistant block.

== Frequently Asked Questions ==

= Are API tokens exposed in the browser? =

No. Tokens are stored in WordPress options and used by PHP when the local REST endpoint forwards the request.

= Which capability is required to use the editor assistant? =

The AI REST endpoint requires `edit_posts`.

= Which capability is required to upload documents for extraction? =

Document extraction requires `edit_posts` and `upload_files`.

= Which file types can be uploaded? =

The plugin accepts `.txt`, `.md`, `.html`, `.htm`, `.docx`, `.doc`, and `.pdf`. DOCX requires PHP Zip. PDF and legacy DOC extraction are best-effort.

= Which capability is required to configure the plugin? =

The settings page requires `manage_options`.

= Is the Tools endpoint OpenAI-compatible? =

No. The default Tools endpoint uses a Tools-specific JSON contract with `client_slug` and `user_prompt`.

= Can I use only OpenAI direct access? =

Yes. Set the default provider to OpenAI direct and configure an OpenAI API token and model.

== Changelog ==

= 0.2.0 =
* Added separate Instructions and Custom text fields.
* Added document upload extraction.
* Added Markdown-to-WordPress-block conversion.
* Added wp-admin provider tests.

= 0.1.0 =
* Initial implementation.
* Added wp-admin settings page.
* Added server-side Tools AI connector.
* Added server-side OpenAI connector.
* Added block editor sidebar.
* Added Tornevall AI Assistant block in the Text category.
