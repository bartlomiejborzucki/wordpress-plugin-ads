<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ADS_PLUGIN_PATH . 'includes/class-ads-admin.php';
require_once ADS_PLUGIN_PATH . 'includes/class-ads-shortcode.php';

class Ads_Plugin {

	public static function activate() {
		self::register_post_type();
		self::register_group_taxonomy();
		self::maybe_run_migrations();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_group_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'init', array( __CLASS__, 'maybe_run_migrations' ), 20 );
		add_action( 'wp_ajax_ads_shortcode_render', array( 'Ads_Shortcode', 'ajax_render' ) );
		add_action( 'wp_ajax_nopriv_ads_shortcode_render', array( 'Ads_Shortcode', 'ajax_render' ) );
		add_action( 'wp_ajax_ads_shortcode_track_click', array( __CLASS__, 'ajax_track_click' ) );
		add_action( 'wp_ajax_nopriv_ads_shortcode_track_click', array( __CLASS__, 'ajax_track_click' ) );

		if ( is_admin() ) {
			Ads_Admin::boot();
		}
	}

	public static function load_textdomain() {
		load_plugin_textdomain(
			'ads-shortcode-plugin',
			false,
			dirname( plugin_basename( ADS_PLUGIN_FILE ) ) . '/languages'
		);
	}

	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Ads', 'ads-shortcode-plugin' ),
			'singular_name'      => __( 'Ad', 'ads-shortcode-plugin' ),
			'menu_name'          => __( 'Ads', 'ads-shortcode-plugin' ),
			'name_admin_bar'     => __( 'Ad', 'ads-shortcode-plugin' ),
			'add_new'            => __( 'Add New', 'ads-shortcode-plugin' ),
			'add_new_item'       => __( 'Add New Ad', 'ads-shortcode-plugin' ),
			'new_item'           => __( 'New Ad', 'ads-shortcode-plugin' ),
			'edit_item'          => __( 'Edit Ad', 'ads-shortcode-plugin' ),
			'view_item'          => __( 'View Ad', 'ads-shortcode-plugin' ),
			'all_items'          => __( 'All Ads', 'ads-shortcode-plugin' ),
			'search_items'       => __( 'Search Ads', 'ads-shortcode-plugin' ),
			'not_found'          => __( 'No ads found.', 'ads-shortcode-plugin' ),
			'not_found_in_trash' => __( 'No ads found in Trash.', 'ads-shortcode-plugin' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'menu_position'       => 26,
			'menu_icon'           => 'dashicons-megaphone',
			'supports'            => array( 'title', 'editor', 'revisions' ),
			'show_in_rest'        => true,
		);

		register_post_type( ADS_POST_TYPE, $args );
	}

	public static function register_group_taxonomy() {
		$labels = array(
			'name'          => __( 'Ad Groups', 'ads-shortcode-plugin' ),
			'singular_name' => __( 'Ad Group', 'ads-shortcode-plugin' ),
			'menu_name'     => __( 'Ad Groups', 'ads-shortcode-plugin' ),
			'all_items'     => __( 'All Ad Groups', 'ads-shortcode-plugin' ),
			'edit_item'     => __( 'Edit Ad Group', 'ads-shortcode-plugin' ),
			'add_new_item'  => __( 'Add New Ad Group', 'ads-shortcode-plugin' ),
			'search_items'  => __( 'Search Ad Groups', 'ads-shortcode-plugin' ),
		);

		register_taxonomy(
			ADS_GROUP_TAXONOMY,
			ADS_POST_TYPE,
			array(
				'labels'            => $labels,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
			)
		);
	}

	public static function register_meta() {
		register_post_meta(
			ADS_POST_TYPE,
			'_ads_wrapper_classes',
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_wrapper_classes' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			ADS_POST_TYPE,
			'_ads_inline_css',
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_css_declarations' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			ADS_POST_TYPE,
			'_ads_start_date',
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_date' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			ADS_POST_TYPE,
			'_ads_end_date',
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_date' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			ADS_POST_TYPE,
			'_ads_weight',
			array(
				'single'            => true,
				'type'              => 'integer',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_weight' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			ADS_POST_TYPE,
			'_ads_template_mode',
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_mode' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			ADS_POST_TYPE,
			'_ads_template_top_label',
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_label' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			ADS_POST_TYPE,
			'_ads_template_bottom_label',
			array(
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_label' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	public static function register_shortcode() {
		add_shortcode( 'ads', array( 'Ads_Shortcode', 'render' ) );
	}

	public static function maybe_run_migrations() {
		$stored_version = get_option( ADS_PLUGIN_VERSION_OPTION, '' );

		if ( ADS_PLUGIN_VERSION === $stored_version ) {
			return;
		}

		self::run_migrations( $stored_version );

		update_option( ADS_PLUGIN_VERSION_OPTION, ADS_PLUGIN_VERSION, false );
	}

	private static function run_migrations( $stored_version ) {
		if ( '' === $stored_version ) {
			return;
		}

		if ( version_compare( $stored_version, '1.1.0', '<' ) ) {
			self::migrate_default_weights();
		}
	}

	private static function migrate_default_weights() {
		$ads = get_posts(
			array(
				'post_type'        => ADS_POST_TYPE,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		foreach ( $ads as $ad_id ) {
			if ( '' === get_post_meta( $ad_id, '_ads_weight', true ) ) {
				update_post_meta( $ad_id, '_ads_weight', 1 );
			}
		}
	}

	public static function ajax_track_click() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public endpoint increments a counter for published ads only.
		$ad_id = isset( $_POST['ad_id'] ) ? absint( wp_unslash( $_POST['ad_id'] ) ) : 0;

		if ( ! $ad_id || ADS_POST_TYPE !== get_post_type( $ad_id ) || 'publish' !== get_post_status( $ad_id ) ) {
			wp_send_json_error();
		}

		self::increment_counter( $ad_id, '_ads_clicks' );
		wp_send_json_success();
	}

	public static function increment_counter( $post_id, $meta_key ) {
		$current = absint( get_post_meta( $post_id, $meta_key, true ) );
		update_post_meta( $post_id, $meta_key, $current + 1 );
	}

	public static function sanitize_wrapper_classes( $value ) {
		$classes = preg_split( '/\s+/', (string) $value );
		$classes = array_filter( array_map( 'sanitize_html_class', $classes ) );

		return implode( ' ', array_unique( $classes ) );
	}

	public static function sanitize_css_declarations( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		$value = trim( preg_replace( '/\s+/', ' ', $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/[{}<>]/', $value ) ) {
			return '';
		}

		$declarations = array_filter( array_map( 'trim', explode( ';', $value ) ) );
		$sanitized    = array();

		foreach ( $declarations as $declaration ) {
			if ( false === strpos( $declaration, ':' ) ) {
				continue;
			}

			list( $property, $css_value ) = array_map( 'trim', explode( ':', $declaration, 2 ) );

			if ( '' === $property || '' === $css_value ) {
				continue;
			}

			if ( ! preg_match( '/^--?[a-zA-Z0-9_-]+$/', $property ) ) {
				continue;
			}

			if ( preg_match( '/[{}<>]/', $css_value ) ) {
				continue;
			}

			$sanitized[] = $property . ': ' . $css_value;
		}

		if ( empty( $sanitized ) ) {
			return '';
		}

		return implode( '; ', $sanitized ) . ';';
	}

	public static function sanitize_date( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	public static function sanitize_weight( $value ) {
		$weight = absint( $value );

		if ( 1 > $weight ) {
			return 1;
		}

		return min( $weight, 100 );
	}

	public static function sanitize_template_mode( $value ) {
		$value = sanitize_key( (string) $value );
		$modes = array( 'inherit', 'enabled', 'disabled' );

		return in_array( $value, $modes, true ) ? $value : 'inherit';
	}

	public static function sanitize_template_label( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = trim( preg_replace( '/\s+/', ' ', $value ) );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, 80 );
		}

		return substr( $value, 0, 80 );
	}

	public static function get_default_template_top_label() {
		return __( 'Promocja materiałów własnych', 'ads-shortcode-plugin' );
	}

	public static function get_default_template_bottom_label() {
		return __( 'Koniec promocji', 'ads-shortcode-plugin' );
	}

	public static function is_date_range_active( $start_date, $end_date ) {
		$today = current_time( 'Y-m-d' );

		if ( '' !== $start_date && $today < $start_date ) {
			return false;
		}

		if ( '' !== $end_date && $today > $end_date ) {
			return false;
		}

		return true;
	}
}
