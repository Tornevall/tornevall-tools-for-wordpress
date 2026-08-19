# AGENTS.md

This file defines how future agents and developers should continue work on Tornevall Tools for WordPress.

## Project goal

Build a modular WordPress-native toolbox around stable Tornevall Networks Tools services.

The public plugin must not be defined by one feature category. Individual integrations should be isolated modules that can be enabled only when useful.

Current public module:

1. Dynamic DNS.

AI is under separate development and is not part of the current `main` runtime. DNSBL/FraudBL must not be duplicated here because it has its own maintained WordPress plugin.

## Current architecture

- `tornevall-tools-for-wordpress.php` loads the public plugin runtime.
- `TTFW_Settings` owns the top-level wp-admin page and sanitized settings.
- `TTFW_API_Client` makes server-side requests to the fixed Tornevall Networks Tools service origin.
- `TTFW_Module_Registry` exposes module metadata for the admin overview.
- `TTFW_Dynamic_DNS_Module` owns Dynamic DNS updates and WP-Cron scheduling.
- Credentials stay server-side in WordPress options.

## Module rules

Each new module should:

- solve one independently useful WordPress problem
- remain disabled or inactive until the administrator intentionally configures it when remote service use is involved
- own its own hooks, cron jobs, REST routes, admin actions, and status data
- avoid loading frontend/editor assets unless the module needs them
- use the shared Tools API client when talking to `tools.tornevall.net`
- document the exact external-service data flow in `readme.txt`
- clean up scheduled events when the plugin is deactivated or uninstalled

Do not add a module merely because a Tools feature exists. The WordPress integration must have a clear use case.

## Scope boundaries

### AI

AI work currently lives outside the public main release line. When it returns, it should return as an optional module and be reviewed against the WordPress version current at that time.

### DNSBL/FraudBL

Do not implement DNSBL/FraudBL protection in this repository. It belongs in:

```text
https://github.com/Tornevall/tornevall-wp-dnsbl
```

### External executable code

Do not load executable JavaScript, CSS, PHP, plugin updates, or add-ons from Tornevall Networks Tools. WordPress.org permits documented SaaS/API communication but does not permit using an external service as a remote code delivery mechanism.

## Required documentation practice

Always update these files in the same pull request when behavior changes:

- `CHANGELOG.md`
- `README.md`
- `readme.txt` when user-facing or WordPress.org information changes
- `AGENTS.md` when architecture, scope, or development rules change

`CHANGELOG.md` must always be updated.

## WordPress development rules

Follow WordPress plugin practices:

- Check `ABSPATH` before executing plugin PHP files.
- Use WordPress hooks and APIs instead of direct database access where possible.
- Use `register_setting()` with a `sanitize_callback` for settings.
- Sanitize all input.
- Escape all output.
- Protect state-changing admin actions with capability checks and nonces.
- Use `wp_remote_request()`, `wp_remote_get()`, or `wp_remote_post()` for outbound HTTP.
- Use `wp_json_encode()` for JSON request bodies.
- Use `WP_Error` for recoverable failures.
- Keep credentials server-side.
- Keep code compatible with the declared PHP version.
- Do not add arbitrary remote endpoint settings when a module only needs the official Tools service origin.

## Security and privacy requirements

- Token/password fields must render blank and preserve existing values when submitted blank.
- Settings and manual service actions require `manage_options` unless a module has a documented reason for a narrower capability.
- Do not log credentials.
- Do not include credentials in errors, notices, browser data, screenshots, tests, or documentation examples.
- Store only the minimum status information needed for diagnostics.
- Do not make external service calls merely because the plugin was activated.
- Every remote data flow must be documented in `readme.txt` before public release.

## Dynamic DNS contract

Documentation:

```text
https://tools.tornevall.net/docs/en/dynamic-dns
```

Current update endpoint:

```text
POST https://tools.tornevall.net/api/dyndns/update
```

Current request body:

```json
{
  "hostname": "home.dyn.tornevall.net",
  "address": "auto"
}
```

Authentication:

```text
Authorization: Bearer <dynamic-dns-token>
```

`address=auto` deliberately uses the source address seen by Tools. Do not replace it with browser/client IP data.

## WordPress.org release requirements

The GitHub repository is the development source. Public distribution should use the WordPress Plugin Directory after approval.

Before submission or release:

1. Verify the plugin is complete and installable as a normal WordPress plugin ZIP.
2. Smoke-test against the latest stable WordPress release.
3. Run the official Plugin Check checks.
4. Validate `readme.txt` with the official readme validator.
5. Confirm all external service disclosures are current.
6. Confirm the final plugin name/slug before review begins.
7. Keep the WordPress.org SVN release synchronized with the released plugin version.

Do not add a custom self-updater for the WordPress.org build.

## PHP quality checklist

Before merging:

```bash
find . -name "*.php" -print -exec php -l {} \;
```

When WordPress Coding Standards are installed:

```bash
phpcs --standard=WordPress .
```

## Manual test checklist

- Activate the plugin without fatal errors.
- Confirm no remote request is made while Dynamic DNS is disabled.
- Open `Tornevall Tools` as an administrator.
- Confirm users without `manage_options` cannot access settings or manual update actions.
- Save a Dynamic DNS hostname and token.
- Save again with the token field blank and confirm the stored token is preserved.
- Enable Dynamic DNS and confirm one WP-Cron event is scheduled.
- Change the interval and confirm the old event is replaced.
- Disable Dynamic DNS and confirm the scheduled event is removed.
- Run `Update now` with a valid token/hostname and confirm Tools updates the host.
- Confirm success/failure status contains no token data.
- Deactivate the plugin and confirm the cron hook is removed.

## Pull request standards

Every pull request should include:

- a linked issue/ticket
- summary of behavior changes
- security/privacy notes for settings, remote requests, uploads, REST, or cron work
- manual test notes
- changelog update
- documentation update when behavior changes

## Do not do this

- Do not turn the public plugin back into an AI-only product.
- Do not duplicate the standalone DNSBL/FraudBL plugin.
- Do not expose service credentials to JavaScript.
- Do not allow arbitrary remote API origins without a documented requirement and security review.
- Do not add unauthenticated state-changing endpoints.
- Do not silently swallow remote service errors.
- Do not hardcode user-specific secrets.
- Do not skip `CHANGELOG.md`.
