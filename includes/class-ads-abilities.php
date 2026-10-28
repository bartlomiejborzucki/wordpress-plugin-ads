<?php
/**
 * WordPress Abilities API integration for MCP clients.
 *
 * @package Ads_Shortcode_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ads_Abilities {

	private static $meta_fields = array(
		'wrapper_classes'       => '_ads_wrapper_classes',
		'inline_css'            => '_ads_inline_css',
		'start_date'            => '_ads_start_date',
		'end_date'              => '_ads_end_date',
		'weight'                => '_ads_weight',
		'template_mode'         => '_ads_template_mode',
		'template_top_label'    => '_ads_template_top_label',
		'template_bottom_label' => '_ads_template_bottom_label',
		'utm_source'            => '_ads_utm_source',
		'utm_medium'            => '_ads_utm_medium',
		'utm_campaign'          => '_ads_utm_campaign',
	);

	public static function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'ads-shortcode-plugin',
			array(
				'label'       => __( 'Ads', 'ads-shortcode-plugin' ),
				'description' => __( 'Manage reusable ads and ad groups.', 'ads-shortcode-plugin' ),
			)
		);
	}

	public static function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$read_meta  = array(
			'public'       => true,
			'show_in_rest' => false,
			'annotations'  => array( 'readonly' => true ),
		);
		$write_meta = array(
			'public'       => true,
			'show_in_rest' => false,
		);

		wp_register_ability(
			'ads-shortcode-plugin/list-ads',
			array(
				'label'               => __( 'List ads', 'ads-shortcode-plugin' ),
				'description'         => __( 'List ads the current user can edit, including drafts and their group slugs.', 'ads-shortcode-plugin' ),
				'category'            => 'ads-shortcode-plugin',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'page'     => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 100,
							'default' => 20,
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, 'list_ads' ),
				'permission_callback' => array( __CLASS__, 'can_edit_ads' ),
				'meta'                => $read_meta,
			)
		);

		wp_register_ability(
			'ads-shortcode-plugin/get-ad',
			array(
				'label'               => __( 'Get ad', 'ads-shortcode-plugin' ),
				'description'         => __( 'Get one editable ad with content, settings, groups, and counters.', 'ads-shortcode-plugin' ),
				'category'            => 'ads-shortcode-plugin',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
					),
					'required'             => array( 'id' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, 'get_ad' ),
				'permission_callback' => array( __CLASS__, 'can_edit_ad' ),
				'meta'                => $read_meta,
			)
		);

		wp_register_ability(
			'ads-shortcode-plugin/list-groups',
			array(
				'label'               => __( 'List ad groups', 'ads-shortcode-plugin' ),
				'description'         => __( 'List existing ad groups and their emission dates.', 'ads-shortcode-plugin' ),
				'category'            => 'ads-shortcode-plugin',
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, 'list_groups' ),
				'permission_callback' => array( __CLASS__, 'can_edit_ads' ),
				'meta'                => $read_meta,
			)
		);

		$meta_properties = array();
		foreach ( self::$meta_fields as $field => $key ) {
			$meta_properties[ $field ] = array( 'type' => 'string' );
		}
		$meta_properties['weight'] = array(
			'type'    => 'integer',
			'minimum' => 1,
		);

		wp_register_ability(
			'ads-shortcode-plugin/save-ad',
			array(
				'label'               => __( 'Create or update ad', 'ads-shortcode-plugin' ),
				'description'         => __( 'Create a draft ad or update an editable ad. Supply an ID to update; omitted settings stay unchanged. Publishing requires publish_posts.', 'ads-shortcode-plugin' ),
				'category'            => 'ads-shortcode-plugin',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'id'          => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'title'       => array( 'type' => 'string' ),
						'content'     => array( 'type' => 'string' ),
						'status'      => array(
							'type' => 'string',
							'enum' => array( 'draft', 'publish' ),
						),
						'group_slugs' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'settings'    => array(
							'type'                 => 'object',
							'properties'           => $meta_properties,
							'additionalProperties' => false,
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, 'save_ad' ),
				'permission_callback' => array( __CLASS__, 'can_save_ad' ),
				'meta'                => $write_meta,
			)
		);
	}

	public static function can_edit_ads() {
		return current_user_can( 'edit_posts' );
	}

	public static function can_edit_ad( $input ) {
		$post = get_post( absint( $input['id'] ) );
		return $post && ADS_POST_TYPE === $post->post_type && current_user_can( 'edit_post', $post->ID );
	}

	public static function can_save_ad( $input ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		if ( isset( $input['id'] ) && ! self::can_edit_ad( $input ) ) {
			return false;
		}
		return ! isset( $input['status'] ) || 'publish' !== $input['status'] || current_user_can( 'publish_posts' );
	}

	public static function list_ads( $input ) {
		$query = new WP_Query(
			array(
				'post_type'      => ADS_POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => isset( $input['per_page'] ) ? min( 100, max( 1, absint( $input['per_page'] ) ) ) : 20,
				'paged'          => isset( $input['page'] ) ? max( 1, absint( $input['page'] ) ) : 1,
			)
		);
		$ads   = array();
		foreach ( $query->posts as $post ) {
			if ( current_user_can( 'edit_post', $post->ID ) ) {
				$ads[] = array(
					'id'          => $post->ID,
					'title'       => $post->post_title,
					'status'      => $post->post_status,
					'group_slugs' => wp_get_object_terms( $post->ID, ADS_GROUP_TAXONOMY, array( 'fields' => 'slugs' ) ),
				);
			}
		}
		return array(
			'ads'   => $ads,
			'page'  => isset( $input['page'] ) ? max( 1, absint( $input['page'] ) ) : 1,
			'pages' => (int) $query->max_num_pages,
		);
	}

	public static function get_ad( $input ) {
		$post = get_post( absint( $input['id'] ) );
		if ( ! $post || ADS_POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error( 'ads_ad_not_found', __( 'Ad not found or access denied.', 'ads-shortcode-plugin' ) );
		}
		$settings = array();
		foreach ( self::$meta_fields as $field => $key ) {
			$settings[ $field ] = 'weight' === $field ? absint( get_post_meta( $post->ID, $key, true ) ) : (string) get_post_meta( $post->ID, $key, true );
		}
		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'content'     => $post->post_content,
			'status'      => $post->post_status,
			'group_slugs' => wp_get_object_terms( $post->ID, ADS_GROUP_TAXONOMY, array( 'fields' => 'slugs' ) ),
			'settings'    => $settings,
			'impressions' => absint( get_post_meta( $post->ID, '_ads_impressions', true ) ),
			'clicks'      => absint( get_post_meta( $post->ID, '_ads_clicks', true ) ),
		);
	}

	public static function list_groups() {
		$terms = get_terms(
			array(
				'taxonomy'   => ADS_GROUP_TAXONOMY,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		$groups = array();
		foreach ( $terms as $term ) {
			$groups[] = array(
				'id'         => $term->term_id,
				'name'       => $term->name,
				'slug'       => $term->slug,
				'start_date' => (string) get_term_meta( $term->term_id, '_ads_group_start_date', true ),
				'end_date'   => (string) get_term_meta( $term->term_id, '_ads_group_end_date', true ),
			);
		}
		return array( 'groups' => $groups );
	}

	public static function save_ad( $input ) {
		if ( ! self::can_save_ad( $input ) ) {
			return new WP_Error( 'ads_forbidden', __( 'You cannot save this ad.', 'ads-shortcode-plugin' ) );
		}
		if ( ! isset( $input['id'] ) && ( ! isset( $input['title'] ) || '' === trim( $input['title'] ) ) ) {
			return new WP_Error( 'ads_title_required', __( 'A title is required when creating an ad.', 'ads-shortcode-plugin' ) );
		}
		$term_ids = null;
		if ( isset( $input['group_slugs'] ) ) {
			$term_ids = array();
			foreach ( $input['group_slugs'] as $slug ) {
				$term = get_term_by( 'slug', sanitize_title( $slug ), ADS_GROUP_TAXONOMY );
				if ( ! $term ) {
					return new WP_Error( 'ads_group_not_found', __( 'One or more ad groups do not exist.', 'ads-shortcode-plugin' ) );
				}
				$term_ids[] = $term->term_id;
			}
		}
		$postarr = array( 'post_type' => ADS_POST_TYPE );
		if ( isset( $input['id'] ) ) {
			$postarr['ID'] = absint( $input['id'] );
		} else {
			$postarr['post_status'] = 'draft';
		}
		if ( isset( $input['title'] ) ) {
			$postarr['post_title'] = wp_slash( sanitize_text_field( $input['title'] ) );
		}
		if ( isset( $input['content'] ) ) {
			$content                 = current_user_can( 'unfiltered_html' ) ? $input['content'] : wp_kses_post( $input['content'] );
			$postarr['post_content'] = wp_slash( $content );
		}
		if ( isset( $input['status'] ) ) {
			$postarr['post_status'] = $input['status'];
		}
		$post_id = isset( $input['id'] ) ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		if ( null !== $term_ids ) {
			$terms_result = wp_set_object_terms( $post_id, $term_ids, ADS_GROUP_TAXONOMY );
			if ( is_wp_error( $terms_result ) ) {
				return $terms_result;
			}
		}
		if ( isset( $input['settings'] ) ) {
			foreach ( $input['settings'] as $field => $value ) {
				if ( ! isset( self::$meta_fields[ $field ] ) ) {
					continue;
				}
				$key = self::$meta_fields[ $field ];
				switch ( $field ) {
					case 'wrapper_classes':
						$value = Ads_Plugin::sanitize_wrapper_classes( $value );
						break;
					case 'inline_css':
						$value = Ads_Plugin::sanitize_css_declarations( $value );
						break;
					case 'start_date':
					case 'end_date':
						$value = Ads_Plugin::sanitize_date( $value );
						break;
					case 'weight':
						$value = Ads_Plugin::sanitize_weight( $value );
						break;
					case 'template_mode':
						$value = Ads_Plugin::sanitize_template_mode( $value );
						break;
					case 'template_top_label':
					case 'template_bottom_label':
						$value = Ads_Plugin::sanitize_template_label( $value );
						break;
					default:
						$value = Ads_Plugin::sanitize_utm_value( $value );
				}
				update_post_meta( $post_id, $key, $value );
			}
		}
		return self::get_ad( array( 'id' => $post_id ) );
	}
}
