# Ads Shortcode Plugin

WordPress plugin for managing reusable ads and embedding them with a shortcode.

## What it does

- creates an `Ads` admin section backed by a custom post type,
- lets you build ad content with the standard WordPress editor,
- supports optional wrapper classes and scoped wrapper CSS declarations,
- supports an optional promotional separator template with global labels and per-ad overrides,
- renders ads with `[ads ids="1"]` or `[ads ids="1,2,3"]`,
- renders ads by group with `[ads groups="sidebar"]` or `[ads groups="sidebar,footer"]`,
- randomly picks one published ad when multiple IDs are passed,
- supports ad and group start/end dates,
- supports weighted ad selection,
- counts impressions and clicks,
- adds configurable UTM tracking to every ad link and explicit form target,
- supports a global fallback ad,
- supports cache-friendly AJAX rendering with `cache="ajax"`,
- adds missing `loading="lazy"` and `decoding="async"` attributes to ad images,
- adds missing `loading="lazy"` attributes to ad iframes.

You can test the plugin without installing it on production by using WordPress Playground:

```text
https://playground.wordpress.net/
```

## Package for WordPress

Run:

```bash
./scripts/package-plugin.sh
```

The ZIP file will be created at:

```text
dist/ads-shortcode-plugin.zip
```

Upload that ZIP in `Plugins -> Add New -> Upload Plugin`.

## Shortcode examples

```text
[ads ids="12"]
[ads ids="12,24,36"]
[ads groups="sidebar"]
[ads groups="sidebar,footer"]
[ads ids="12,24,36" cache="ajax"]
```

Use `cache="ajax"` when full-page cache, including LiteSpeed Cache, freezes the randomly selected ad in cached HTML. The default mode does not purge caches, set cache headers, or interfere with cache plugins.

## Local quality checks

Basic syntax check:

```bash
php -l ads-shortcode-plugin.php
php -l includes/class-ads-plugin.php
php -l includes/class-ads-admin.php
php -l includes/class-ads-shortcode.php
php -l uninstall.php
```

If you want proper WordPress coding standard checks, install Composer dependencies:

```bash
composer install
vendor/bin/phpcs
```

## Development notes

UTM values can be configured per ad. Empty fields use the site hostname for
`utm_source`, `display` for `utm_medium`, and the ad slug for `utm_campaign`.
The generated `utm_content` identifies the ad and click-target position. URLs
that already contain individual UTM parameters keep their existing values.

- shortcode name: `ads`
- custom post type: `ads_item`
- ad group taxonomy: `ads_group`
- expected WordPress/PHP baseline: WP 6.0+, PHP 7.4+
