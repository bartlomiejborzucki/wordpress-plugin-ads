<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ads_Shortcode {

	private static $render_stack             = array();
	private static $tracking_script_rendered = false;

	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'ids'    => '',
				'group'  => '',
				'groups' => '',
				'cache'  => '',
			),
			$atts,
			'ads'
		);

		if ( 'ajax' === strtolower( (string) $atts['cache'] ) ) {
			return self::render_ajax_placeholder( $atts );
		}

		return self::render_shortcode_content( $atts );
	}

	public static function ajax_render() {
		$atts = array(
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public endpoint renders only published ads from sanitized shortcode attributes.
			'ids'    => isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '',
			'group'  => '',
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public endpoint renders only published ads from sanitized shortcode attributes.
			'groups' => isset( $_POST['groups'] ) ? sanitize_text_field( wp_unslash( $_POST['groups'] ) ) : '',
			'cache'  => '',
		);

		wp_send_json_success(
			array(
				'html' => self::render_shortcode_content( $atts ),
			)
		);
	}

	private static function render_shortcode_content( array $atts ) {
		$ids    = self::parse_ids( $atts['ids'] );
		$groups = self::parse_groups( $atts['groups'] . ',' . $atts['group'] );

		$ad = self::pick_random_ad( $ids, $groups );

		if ( ! $ad instanceof WP_Post ) {
			$ad = self::get_fallback_ad();
		}

		if ( ! $ad instanceof WP_Post ) {
			return '';
		}

		if ( isset( self::$render_stack[ $ad->ID ] ) ) {
			return '';
		}

		self::$render_stack[ $ad->ID ] = true;

		try {
			return self::render_ad_markup( $ad );
		} finally {
			unset( self::$render_stack[ $ad->ID ] );
		}
	}

	private static function parse_ids( $ids ) {
		$raw_ids    = array_map( 'trim', explode( ',', (string) $ids ) );
		$parsed_ids = array_map( 'absint', $raw_ids );

		return array_values( array_unique( array_filter( $parsed_ids ) ) );
	}

	private static function parse_groups( $groups ) {
		$raw_groups = array_map( 'trim', explode( ',', (string) $groups ) );
		$groups     = array();

		foreach ( $raw_groups as $group ) {
			if ( '' === $group ) {
				continue;
			}

			$groups[] = sanitize_title( $group );
		}

		return array_values( array_unique( array_filter( $groups ) ) );
	}

	private static function pick_random_ad( array $ids, array $groups ) {
		$posts = self::get_candidate_ads( $ids, $groups );
		$posts = array_values( array_filter( $posts, array( __CLASS__, 'is_ad_active' ) ) );

		if ( empty( $posts ) ) {
			return null;
		}

		return self::pick_weighted_ad( $posts );
	}

	private static function get_candidate_ads( array $ids, array $groups ) {
		$posts = array();

		if ( ! empty( $ids ) ) {
			$posts = array_merge( $posts, self::get_ads_by_ids( $ids ) );
		}

		$active_groups = self::get_active_group_slugs( $groups );

		if ( ! empty( $active_groups ) ) {
			$posts = array_merge( $posts, self::get_ads_by_groups( $active_groups ) );
		}

		if ( empty( $posts ) ) {
			return array();
		}

		$unique_posts = array();

		foreach ( $posts as $post ) {
			$unique_posts[ $post->ID ] = $post;
		}

		return array_values( $unique_posts );
	}

	private static function get_ads_by_ids( array $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}

		return get_posts(
			array(
				'post_type'        => ADS_POST_TYPE,
				'post_status'      => 'publish',
				'post__in'         => $ids,
				'orderby'          => 'post__in',
				'numberposts'      => count( $ids ),
				'suppress_filters' => false,
			)
		);
	}

	private static function get_ads_by_groups( array $groups ) {
		if ( empty( $groups ) ) {
			return array();
		}

		return get_posts(
			array(
				'post_type'        => ADS_POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'suppress_filters' => false,
				'tax_query'        => array(
					array(
						'taxonomy' => ADS_GROUP_TAXONOMY,
						'field'    => 'slug',
						'terms'    => $groups,
					),
				),
			)
		);
	}

	private static function get_active_group_slugs( array $groups ) {
		if ( empty( $groups ) ) {
			return array();
		}

		$active_groups = array();

		foreach ( $groups as $group ) {
			$term = get_term_by( 'slug', $group, ADS_GROUP_TAXONOMY );

			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$start_date = get_term_meta( $term->term_id, '_ads_group_start_date', true );
			$end_date   = get_term_meta( $term->term_id, '_ads_group_end_date', true );

			if ( Ads_Plugin::is_date_range_active( $start_date, $end_date ) ) {
				$active_groups[] = $group;
			}
		}

		return $active_groups;
	}

	private static function is_ad_active( WP_Post $ad ) {
		$start_date = get_post_meta( $ad->ID, '_ads_start_date', true );
		$end_date   = get_post_meta( $ad->ID, '_ads_end_date', true );

		return Ads_Plugin::is_date_range_active( $start_date, $end_date );
	}

	private static function pick_weighted_ad( array $posts ) {
		$total_weight = 0;

		foreach ( $posts as $post ) {
			$total_weight += self::get_ad_weight( $post->ID );
		}

		if ( 1 > $total_weight ) {
			return $posts[ array_rand( $posts ) ];
		}

		$target = wp_rand( 1, $total_weight );

		foreach ( $posts as $post ) {
			$target -= self::get_ad_weight( $post->ID );

			if ( 1 > $target ) {
				return $post;
			}
		}

		return end( $posts );
	}

	private static function get_ad_weight( $post_id ) {
		$weight = absint( get_post_meta( $post_id, '_ads_weight', true ) );

		return 1 > $weight ? 1 : min( $weight, 100 );
	}

	private static function get_fallback_ad() {
		$fallback_id = absint( get_option( ADS_FALLBACK_AD_OPTION, 0 ) );

		if ( ! $fallback_id ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'        => ADS_POST_TYPE,
				'post_status'      => 'publish',
				'post__in'         => array( $fallback_id ),
				'orderby'          => 'post__in',
				'numberposts'      => 1,
				'suppress_filters' => false,
			)
		);

		if ( empty( $posts ) ) {
			return null;
		}

		return self::is_ad_active( $posts[0] ) ? $posts[0] : null;
	}

	private static function render_ajax_placeholder( array $atts ) {
		$instance_id = sprintf( 'ads-placeholder-%s', wp_generate_password( 8, false, false ) );
		$groups      = self::parse_groups( $atts['groups'] . ',' . $atts['group'] );

		$html  = sprintf(
			'<div id="%1$s" class="ads-shortcode-placeholder" data-ads-shortcode-placeholder="1" data-ads-ids="%2$s" data-ads-groups="%3$s"></div>',
			esc_attr( $instance_id ),
			esc_attr( implode( ',', self::parse_ids( $atts['ids'] ) ) ),
			esc_attr( implode( ',', $groups ) )
		);
		$html .= self::get_ajax_render_script();
		$html .= self::get_click_tracking_script();

		return $html;
	}

	private static function get_ajax_render_script() {
		$ajax_url = admin_url( 'admin-ajax.php' );

		return sprintf(
			'<script>(function(){var render=function(node){if(node.dataset.adsLoaded){return;}node.dataset.adsLoaded="1";var data=new FormData();data.append("action","ads_shortcode_render");data.append("ids",node.dataset.adsIds||"");data.append("groups",node.dataset.adsGroups||"");fetch("%s",{method:"POST",credentials:"same-origin",body:data}).then(function(response){return response.json();}).then(function(payload){if(payload&&payload.success&&payload.data&&payload.data.html){node.innerHTML=payload.data.html;}}).catch(function(){});};document.querySelectorAll("[data-ads-shortcode-placeholder]").forEach(render);}());</script>',
			esc_url( $ajax_url )
		);
	}

	private static function render_ad_markup( WP_Post $ad ) {
		$instance_id     = sprintf( 'ads-item-%d-%s', $ad->ID, wp_generate_password( 6, false, false ) );
		$wrapper_classes = get_post_meta( $ad->ID, '_ads_wrapper_classes', true );
		$inline_css      = get_post_meta( $ad->ID, '_ads_inline_css', true );

		$classes = trim( 'ads-shortcode-item ' . $wrapper_classes );
		$content = apply_filters( 'the_content', $ad->post_content );
		$content = self::optimize_media_markup( $content );
		Ads_Plugin::increment_counter( $ad->ID, '_ads_impressions' );

		$html = '';

		if ( '' !== $inline_css ) {
			$html .= sprintf(
				'<style>#%1$s{%2$s}</style>',
				esc_attr( $instance_id ),
				esc_html( $inline_css )
			);
		}

		$html .= sprintf(
			'<div id="%1$s" class="%2$s" data-ad-id="%3$d">%4$s</div>',
			esc_attr( $instance_id ),
			esc_attr( $classes ),
			absint( $ad->ID ),
			$content
		);
		$html .= self::get_click_tracking_script();

		return $html;
	}

	private static function get_click_tracking_script() {
		if ( self::$tracking_script_rendered ) {
			return '';
		}

		self::$tracking_script_rendered = true;
		$ajax_url                       = admin_url( 'admin-ajax.php' );

		return sprintf(
			'<script>(function(){document.addEventListener("click",function(event){var target=event.target.closest(".ads-shortcode-item a,.ads-shortcode-item button,.ads-shortcode-item input[type=submit]");if(!target){return;}var wrapper=target.closest(".ads-shortcode-item[data-ad-id]");if(!wrapper||wrapper.dataset.adsClickTracked){return;}wrapper.dataset.adsClickTracked="1";var data=new FormData();data.append("action","ads_shortcode_track_click");data.append("ad_id",wrapper.dataset.adId);if(navigator.sendBeacon){navigator.sendBeacon("%s",data);return;}fetch("%s",{method:"POST",credentials:"same-origin",body:data}).catch(function(){});});}());</script>',
			esc_url( $ajax_url ),
			esc_url( $ajax_url )
		);
	}

	private static function optimize_media_markup( $content ) {
		if ( false === stripos( $content, '<img' ) && false === stripos( $content, '<iframe' ) ) {
			return $content;
		}

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return self::optimize_media_markup_with_tag_processor( $content );
		}

		return self::optimize_media_markup_with_regex( $content );
	}

	private static function optimize_media_markup_with_tag_processor( $content ) {
		$processor = new WP_HTML_Tag_Processor( $content );

		while ( $processor->next_tag() ) {
			$tag_name = strtoupper( $processor->get_tag() );

			if ( 'IMG' === $tag_name ) {
				self::set_missing_attribute( $processor, 'loading', 'lazy' );
				self::set_missing_attribute( $processor, 'decoding', 'async' );
			}

			if ( 'IFRAME' === $tag_name ) {
				self::set_missing_attribute( $processor, 'loading', 'lazy' );
			}
		}

		return $processor->get_updated_html();
	}

	private static function set_missing_attribute( WP_HTML_Tag_Processor $processor, $name, $value ) {
		if ( null === $processor->get_attribute( $name ) ) {
			$processor->set_attribute( $name, $value );
		}
	}

	private static function optimize_media_markup_with_regex( $content ) {
		return preg_replace_callback(
			'/<(img|iframe)\b[^>]*>/i',
			function ( $matches ) {
				$tag      = $matches[0];
				$tag_name = strtolower( $matches[1] );

				if ( 'img' === $tag_name ) {
					$tag = self::add_missing_attribute_to_tag( $tag, 'loading', 'lazy' );
					$tag = self::add_missing_attribute_to_tag( $tag, 'decoding', 'async' );
				}

				if ( 'iframe' === $tag_name ) {
					$tag = self::add_missing_attribute_to_tag( $tag, 'loading', 'lazy' );
				}

				return $tag;
			},
			$content
		);
	}

	private static function add_missing_attribute_to_tag( $tag, $name, $value ) {
		if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=/i', $tag ) ) {
			return $tag;
		}

		return preg_replace(
			'/\s*(\/?)>$/',
			sprintf( ' %s="%s"$1>', esc_attr( $name ), esc_attr( $value ) ),
			$tag
		);
	}
}
