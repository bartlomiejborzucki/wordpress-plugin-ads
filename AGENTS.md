# AGENTS.md

## Project

This repository contains a standalone WordPress plugin:

- plugin slug: `ads-shortcode-plugin`
- main file: `ads-shortcode-plugin.php`
- shortcode: `[ads ids="1,2,3"]`
- custom post type: `ads_item`
- ad group taxonomy: `ads_group`

The plugin manages reusable ads in wp-admin and renders one random published ad from the provided ID or group list.

## Structure

- `ads-shortcode-plugin.php` boots the plugin and registers activation/deactivation hooks.
- `includes/class-ads-plugin.php` registers the CPT, ad group taxonomy, shortcode, meta fields, AJAX endpoints, versioned migrations, and shared sanitization.
- `includes/class-ads-admin.php` contains wp-admin UI, meta boxes, list table column, duplicate action, and help page.
- `includes/class-ads-shortcode.php` parses shortcode attributes, selects an ad by IDs/groups, guards against recursion, and renders output.
- `uninstall.php` fully removes plugin data by deleting `ads_item` posts on uninstall.
- `scripts/package-plugin.sh` builds the installable ZIP archive.

## Dev Commands

Install dev tooling:

```bash
composer install
```

Run WordPress coding standards:

```bash
vendor/bin/phpcs
```

Auto-fix style issues:

```bash
vendor/bin/phpcbf
```

Build the plugin ZIP:

```bash
./scripts/package-plugin.sh
```

Output package:

```text
dist/ads-shortcode-plugin.zip
```

Release workflow:

```bash
vendor/bin/phpcs
./scripts/package-plugin.sh
```

Before releasing, bump both `Version` and `ADS_PLUGIN_VERSION` in `ads-shortcode-plugin.php`, update `Stable tag` and `Changelog` in `readme.txt`, build `dist/ads-shortcode-plugin.zip`, then commit and push the changes.

## Working Rules

- Follow WordPress Coding Standards configured in `phpcs.xml.dist`.
- Keep the plugin compatible with WordPress-style PHP, not generic PSR-only style.
- Prefer core WordPress APIs over custom abstractions.
- Treat the per-ad CSS field as declarations-only; do not allow arbitrary selectors or rule blocks.
- Preserve the recursion guard in shortcode rendering unless replacing it with an equivalent safeguard.
- Preserve existing `ads_item` posts and their meta during plugin updates; use the version option `ads_shortcode_plugin_version` and additive migrations for schema/meta changes.
- Keep `cache="ajax"` compatible with full-page cache plugins such as LiteSpeed Cache; do not add global no-cache headers or cache purges for normal shortcode rendering.
- If uninstall behavior changes, document it explicitly because it currently deletes plugin-created ad posts.

## Testing

Minimum validation before finishing work:

```bash
php -l ads-shortcode-plugin.php
php -l includes/class-ads-plugin.php
php -l includes/class-ads-admin.php
php -l includes/class-ads-shortcode.php
php -l uninstall.php
vendor/bin/phpcs
./scripts/package-plugin.sh
```

Functional checks should be done in a temporary WordPress instance by testing:

- ad creation in admin,
- shortcode with one ID,
- shortcode with multiple IDs,
- wrapper classes,
- scoped CSS declarations,
- ad and group emission dates,
- weighted selection,
- fallback ad behavior,
- duplicate ad action in wp-admin,
- click/impression counters,
- `cache="ajax"` rendering with a page cache plugin or cached page simulation,
- builder usage in elements that execute WordPress shortcodes.
