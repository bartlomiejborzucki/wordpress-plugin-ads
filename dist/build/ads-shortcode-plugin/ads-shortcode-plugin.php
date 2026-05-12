<?php
/**
 * Plugin Name: Ads Shortcode Plugin
 * Description: Manage reusable ads and render a random ad from a shortcode ID list.
 * Version: 1.1.0
 * Author: Codex
 * Text Domain: ads-shortcode-plugin
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADS_PLUGIN_FILE', __FILE__ );
define( 'ADS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADS_PLUGIN_VERSION', '1.1.0' );
define( 'ADS_PLUGIN_VERSION_OPTION', 'ads_shortcode_plugin_version' );
define( 'ADS_POST_TYPE', 'ads_item' );
define( 'ADS_GROUP_TAXONOMY', 'ads_group' );
define( 'ADS_FALLBACK_AD_OPTION', 'ads_shortcode_fallback_ad_id' );

require_once ADS_PLUGIN_PATH . 'includes/class-ads-plugin.php';

register_activation_hook( ADS_PLUGIN_FILE, array( 'Ads_Plugin', 'activate' ) );
register_deactivation_hook( ADS_PLUGIN_FILE, array( 'Ads_Plugin', 'deactivate' ) );

Ads_Plugin::boot();
