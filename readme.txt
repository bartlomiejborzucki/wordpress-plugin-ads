=== Ads Shortcode Plugin ===
Contributors: codex
Tags: ads, shortcode, advertising, oxygen, wysiwyg
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage reusable ads in WordPress and render one random published ad from a shortcode ID list.

== Description ==

Ads Shortcode Plugin adds an admin area for reusable ads and a shortcode for inserting them into post content or builders that execute WordPress shortcodes.

Features:

* Custom post type for ads
* Standard WordPress editor for ad content
* Optional wrapper CSS classes per ad
* Optional wrapper CSS declarations per ad
* Optional promotional separator template with global defaults and per-ad overrides
* Shortcode support with one or multiple ad IDs
* Ad groups with one or multiple group slugs
* Start and end dates for ads and ad groups
* Weighted random ad selection
* Impression and click counters
* Automatic UTM tracking for every ad link and explicit form target
* Duplicate action for quickly creating draft copies of existing ads
* Global fallback ad setting
* Cache-friendly AJAX rendering for full-page cache setups
* Random selection of one published ad when multiple IDs are provided
* Missing lazy loading and async decoding attributes are added to ad images
* Missing lazy loading attributes are added to ad iframes
* Compatible with classic content areas and builders such as Oxygen when shortcodes are executed

Shortcode examples:

* `[ads ids="12"]`
* `[ads ids="12,24,36"]`
* `[ads groups="sidebar"]`
* `[ads groups="sidebar,footer"]`
* `[ads ids="12,24,36" groups="sidebar,footer"]`
* `[ads ids="12,24,36" cache="ajax"]`

When multiple IDs are passed, the plugin randomly selects one published ad and renders it.

You can combine `ids` and `groups` in the same shortcode. The plugin builds one candidate pool from the provided ad IDs and ad groups, then renders one random published ad from that combined pool.

Use `cache="ajax"` when full-page cache, including LiteSpeed Cache, freezes the randomly selected ad in cached HTML. The default mode does not purge caches, set cache headers, or interfere with cache plugins.

You can test the plugin before installing it on a production site with WordPress Playground:

https://playground.wordpress.net/

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the ZIP package from the WordPress admin Plugins screen.
2. Activate the plugin through the WordPress Plugins screen.
3. Open the `Ads` menu in the admin panel and create one or more ads.
4. Insert `[ads ids="1"]` or `[ads ids="1,2,3"]` into a post, page, or builder text element that executes shortcodes.

== Frequently Asked Questions ==

= Does it work with Oxygen Builder? =

Yes, as long as you place the shortcode inside an element that executes native WordPress shortcodes.

= Can I style the ads with my theme CSS? =

Yes. The preferred approach is to use global theme classes. The plugin also allows optional wrapper classes and scoped wrapper CSS declarations per ad.

= Can I add the same separator around every ad? =

Yes. Version 1.3.0 adds a promotional separator template with editable global labels and per-ad overrides.

= What happens if I pass multiple IDs? =

The plugin renders one random published ad from the provided list.

= Can I combine IDs and groups in one shortcode? =

Yes. For example, `[ads ids="12,24" groups="sidebar,footer"]` uses both the provided ad IDs and ads assigned to the provided groups as the candidate pool, then renders one random published ad.

== Changelog ==

= 1.4.0 =

* Added automatic UTM parameters to all ad links and explicit form targets.
* Added per-ad UTM source, medium, and campaign settings.
* Existing UTM parameters in ad URLs are preserved.

= 1.3.0 =

* Added a promotional separator template for ads.
* Added global settings for default top and bottom promotional labels.
* Added per-ad controls to inherit, customize, or disable the promotional template.

= 1.2.0 =

* Added a Duplicate row action for ads in wp-admin.
* Duplicated ads are created as drafts and copy content, groups, schedule, weight, wrapper classes, and wrapper CSS declarations.
* Duplicated ads start impression and click counters from zero.

= 1.1.0 =

* Added ad and group emission dates.
* Added weighted ad selection.
* Added impression and click counters.
* Added ad groups.
* Added global fallback ad setting.
* Added cache-friendly AJAX rendering mode.
* Added media loading optimization for images and iframes.

= 1.0.0 =

* Initial release
