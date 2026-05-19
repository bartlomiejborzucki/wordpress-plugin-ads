<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ads_Admin {

	public static function boot() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . ADS_POST_TYPE, array( __CLASS__, 'save_meta_boxes' ) );
		add_action( 'admin_post_ads_duplicate_ad', array( __CLASS__, 'duplicate_ad' ) );
		add_filter( 'manage_' . ADS_POST_TYPE . '_posts_columns', array( __CLASS__, 'register_columns' ) );
		add_action( 'manage_' . ADS_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'register_row_actions' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'register_help_page' ) );
		add_action( ADS_GROUP_TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_group_add_fields' ) );
		add_action( ADS_GROUP_TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_group_edit_fields' ) );
		add_action( 'created_' . ADS_GROUP_TAXONOMY, array( __CLASS__, 'save_group_fields' ) );
		add_action( 'edited_' . ADS_GROUP_TAXONOMY, array( __CLASS__, 'save_group_fields' ) );
	}

	public static function register_meta_boxes() {
		add_meta_box(
			'ads-display-settings',
			__( 'Ad Display Settings', 'ads-shortcode-plugin' ),
			array( __CLASS__, 'render_settings_meta_box' ),
			ADS_POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'ads-shortcode-help',
			__( 'How to Use This Ad', 'ads-shortcode-plugin' ),
			array( __CLASS__, 'render_help_meta_box' ),
			ADS_POST_TYPE,
			'side',
			'default'
		);
	}

	public static function render_settings_meta_box( $post ) {
		wp_nonce_field( 'ads_save_meta_boxes', 'ads_meta_nonce' );

		$wrapper_classes = get_post_meta( $post->ID, '_ads_wrapper_classes', true );
		$inline_css      = get_post_meta( $post->ID, '_ads_inline_css', true );
		$start_date      = get_post_meta( $post->ID, '_ads_start_date', true );
		$end_date        = get_post_meta( $post->ID, '_ads_end_date', true );
		$weight          = get_post_meta( $post->ID, '_ads_weight', true );
		$template_mode   = get_post_meta( $post->ID, '_ads_template_mode', true );
		$top_label       = get_post_meta( $post->ID, '_ads_template_top_label', true );
		$bottom_label    = get_post_meta( $post->ID, '_ads_template_bottom_label', true );
		$weight          = '' === $weight ? 1 : absint( $weight );
		$template_mode   = '' === $template_mode ? 'inherit' : Ads_Plugin::sanitize_template_mode( $template_mode );
		?>
		<p>
			<label for="ads-start-date"><strong><?php esc_html_e( 'Start date', 'ads-shortcode-plugin' ); ?></strong></label>
			<input
				type="date"
				id="ads-start-date"
				name="ads_start_date"
				class="widefat"
				value="<?php echo esc_attr( $start_date ); ?>"
			/>
		</p>
		<p>
			<label for="ads-end-date"><strong><?php esc_html_e( 'End date', 'ads-shortcode-plugin' ); ?></strong></label>
			<input
				type="date"
				id="ads-end-date"
				name="ads_end_date"
				class="widefat"
				value="<?php echo esc_attr( $end_date ); ?>"
			/>
		</p>
		<p class="description">
			<?php esc_html_e( 'Leave dates empty to keep this ad always eligible.', 'ads-shortcode-plugin' ); ?>
		</p>
		<p>
			<label for="ads-weight"><strong><?php esc_html_e( 'Weight', 'ads-shortcode-plugin' ); ?></strong></label>
			<input
				type="number"
				id="ads-weight"
				name="ads_weight"
				class="widefat"
				min="1"
				max="100"
				step="1"
				value="<?php echo esc_attr( $weight ); ?>"
			/>
		</p>
		<p class="description">
			<?php esc_html_e( 'Higher weight makes this ad more likely to be selected.', 'ads-shortcode-plugin' ); ?>
		</p>
		<p>
			<label for="ads-wrapper-classes"><strong><?php esc_html_e( 'Wrapper CSS classes', 'ads-shortcode-plugin' ); ?></strong></label>
			<input
				type="text"
				id="ads-wrapper-classes"
				name="ads_wrapper_classes"
				class="widefat"
				value="<?php echo esc_attr( $wrapper_classes ); ?>"
				placeholder="banner banner--sidebar"
			/>
		</p>
		<p class="description">
			<?php esc_html_e( 'Use existing global theme classes whenever possible.', 'ads-shortcode-plugin' ); ?>
		</p>
		<p>
			<label for="ads-inline-css"><strong><?php esc_html_e( 'Wrapper CSS declarations', 'ads-shortcode-plugin' ); ?></strong></label>
			<textarea
				id="ads-inline-css"
				name="ads_inline_css"
				class="widefat"
				rows="5"
				placeholder="padding: 16px; background: #f5f5f5;"
			><?php echo esc_textarea( $inline_css ); ?></textarea>
		</p>
		<p class="description">
			<?php esc_html_e( 'Enter CSS declarations only. They will be scoped to this ad wrapper.', 'ads-shortcode-plugin' ); ?>
		</p>
		<hr />
		<p>
			<label for="ads-template-mode"><strong><?php esc_html_e( 'Promotional template', 'ads-shortcode-plugin' ); ?></strong></label>
			<select id="ads-template-mode" name="ads_template_mode" class="widefat">
				<option value="inherit" <?php selected( $template_mode, 'inherit' ); ?>><?php esc_html_e( 'Inherit global setting', 'ads-shortcode-plugin' ); ?></option>
				<option value="enabled" <?php selected( $template_mode, 'enabled' ); ?>><?php esc_html_e( 'Use custom labels', 'ads-shortcode-plugin' ); ?></option>
				<option value="disabled" <?php selected( $template_mode, 'disabled' ); ?>><?php esc_html_e( 'Disable for this ad', 'ads-shortcode-plugin' ); ?></option>
			</select>
		</p>
		<p>
			<label for="ads-template-top-label"><strong><?php esc_html_e( 'Top label', 'ads-shortcode-plugin' ); ?></strong></label>
			<input
				type="text"
				id="ads-template-top-label"
				name="ads_template_top_label"
				class="widefat"
				value="<?php echo esc_attr( $top_label ); ?>"
				placeholder="<?php echo esc_attr( Ads_Plugin::get_default_template_top_label() ); ?>"
			/>
		</p>
		<p>
			<label for="ads-template-bottom-label"><strong><?php esc_html_e( 'Bottom label', 'ads-shortcode-plugin' ); ?></strong></label>
			<input
				type="text"
				id="ads-template-bottom-label"
				name="ads_template_bottom_label"
				class="widefat"
				value="<?php echo esc_attr( $bottom_label ); ?>"
				placeholder="<?php echo esc_attr( Ads_Plugin::get_default_template_bottom_label() ); ?>"
			/>
		</p>
		<p class="description">
			<?php esc_html_e( 'Labels are used only when the promotional template is active for this ad.', 'ads-shortcode-plugin' ); ?>
		</p>
		<?php
	}

	public static function render_help_meta_box( $post ) {
		$shortcode = sprintf( '[ads ids="%d"]', absint( $post->ID ) );
		?>
		<p><?php esc_html_e( 'Insert this shortcode in post content or a text element that executes shortcodes.', 'ads-shortcode-plugin' ); ?></p>
		<code><?php echo esc_html( $shortcode ); ?></code>
		<p><?php esc_html_e( 'Example with multiple IDs: [ads ids="12,24,36"]', 'ads-shortcode-plugin' ); ?></p>
		<p><?php esc_html_e( 'When multiple IDs are provided, the plugin randomly renders one published ad.', 'ads-shortcode-plugin' ); ?></p>
		<?php
	}

	public static function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['ads_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ads_meta_nonce'] ) ), 'ads_save_meta_boxes' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$wrapper_classes = isset( $_POST['ads_wrapper_classes'] ) ? wp_unslash( $_POST['ads_wrapper_classes'] ) : '';
		$inline_css      = isset( $_POST['ads_inline_css'] ) ? wp_unslash( $_POST['ads_inline_css'] ) : '';
		$start_date      = isset( $_POST['ads_start_date'] ) ? wp_unslash( $_POST['ads_start_date'] ) : '';
		$end_date        = isset( $_POST['ads_end_date'] ) ? wp_unslash( $_POST['ads_end_date'] ) : '';
		$weight          = isset( $_POST['ads_weight'] ) ? wp_unslash( $_POST['ads_weight'] ) : 1;
		$template_mode   = isset( $_POST['ads_template_mode'] ) ? wp_unslash( $_POST['ads_template_mode'] ) : 'inherit';
		$top_label       = isset( $_POST['ads_template_top_label'] ) ? wp_unslash( $_POST['ads_template_top_label'] ) : '';
		$bottom_label    = isset( $_POST['ads_template_bottom_label'] ) ? wp_unslash( $_POST['ads_template_bottom_label'] ) : '';

		update_post_meta( $post_id, '_ads_wrapper_classes', Ads_Plugin::sanitize_wrapper_classes( $wrapper_classes ) );
		update_post_meta( $post_id, '_ads_inline_css', Ads_Plugin::sanitize_css_declarations( $inline_css ) );
		update_post_meta( $post_id, '_ads_start_date', Ads_Plugin::sanitize_date( $start_date ) );
		update_post_meta( $post_id, '_ads_end_date', Ads_Plugin::sanitize_date( $end_date ) );
		update_post_meta( $post_id, '_ads_weight', Ads_Plugin::sanitize_weight( $weight ) );
		update_post_meta( $post_id, '_ads_template_mode', Ads_Plugin::sanitize_template_mode( $template_mode ) );
		update_post_meta( $post_id, '_ads_template_top_label', Ads_Plugin::sanitize_template_label( $top_label ) );
		update_post_meta( $post_id, '_ads_template_bottom_label', Ads_Plugin::sanitize_template_label( $bottom_label ) );
	}

	public static function register_row_actions( $actions, $post ) {
		if ( ! $post instanceof WP_Post || ADS_POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$duplicate_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'ads_duplicate_ad',
					'ad_id'  => absint( $post->ID ),
				),
				admin_url( 'admin-post.php' )
			),
			'ads_duplicate_ad_' . $post->ID
		);

		$actions['ads_duplicate'] = sprintf(
			'<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( $duplicate_url ),
			/* translators: %s: ad title. */
			esc_attr( sprintf( __( 'Duplicate "%s"', 'ads-shortcode-plugin' ), get_the_title( $post ) ) ),
			esc_html__( 'Duplicate', 'ads-shortcode-plugin' )
		);

		return $actions;
	}

	public static function duplicate_ad() {
		$source_id = isset( $_GET['ad_id'] ) ? absint( wp_unslash( $_GET['ad_id'] ) ) : 0;

		if ( ! $source_id || ADS_POST_TYPE !== get_post_type( $source_id ) ) {
			wp_die( esc_html__( 'Invalid ad.', 'ads-shortcode-plugin' ) );
		}

		check_admin_referer( 'ads_duplicate_ad_' . $source_id );

		if ( ! current_user_can( 'edit_post', $source_id ) || ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate this ad.', 'ads-shortcode-plugin' ) );
		}

		$source = get_post( $source_id );

		if ( ! $source instanceof WP_Post ) {
			wp_die( esc_html__( 'Invalid ad.', 'ads-shortcode-plugin' ) );
		}

		$new_ad_id = wp_insert_post(
			array(
				'post_author'           => get_current_user_id(),
				'post_content'          => $source->post_content,
				'post_content_filtered' => $source->post_content_filtered,
				'post_excerpt'          => $source->post_excerpt,
				'post_name'             => '',
				'post_parent'           => 0,
				'post_password'         => $source->post_password,
				'post_status'           => 'draft',
				/* translators: %s: source ad title. */
				'post_title'            => sprintf( __( '%s (Copy)', 'ads-shortcode-plugin' ), $source->post_title ),
				'post_type'             => ADS_POST_TYPE,
			),
			true
		);

		if ( is_wp_error( $new_ad_id ) ) {
			wp_die( esc_html( $new_ad_id->get_error_message() ) );
		}

		self::copy_duplicate_meta( $source_id, $new_ad_id );
		self::copy_duplicate_terms( $source_id, $new_ad_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post'   => absint( $new_ad_id ),
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		);
		exit;
	}

	private static function copy_duplicate_meta( $source_id, $new_ad_id ) {
		$meta_keys = array(
			'_ads_wrapper_classes',
			'_ads_inline_css',
			'_ads_start_date',
			'_ads_end_date',
			'_ads_weight',
			'_ads_template_mode',
			'_ads_template_top_label',
			'_ads_template_bottom_label',
		);

		foreach ( $meta_keys as $meta_key ) {
			$meta_value = get_post_meta( $source_id, $meta_key, true );

			if ( '' !== $meta_value ) {
				update_post_meta( $new_ad_id, $meta_key, $meta_value );
			}
		}
	}

	private static function copy_duplicate_terms( $source_id, $new_ad_id ) {
		$term_ids = wp_get_object_terms(
			$source_id,
			ADS_GROUP_TAXONOMY,
			array(
				'fields' => 'ids',
			)
		);

		if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
			return;
		}

		wp_set_object_terms( $new_ad_id, array_map( 'absint', $term_ids ), ADS_GROUP_TAXONOMY );
	}

	public static function register_columns( $columns ) {
		$columns['ads_schedule']    = __( 'Schedule', 'ads-shortcode-plugin' );
		$columns['ads_weight']      = __( 'Weight', 'ads-shortcode-plugin' );
		$columns['ads_impressions'] = __( 'Impressions', 'ads-shortcode-plugin' );
		$columns['ads_clicks']      = __( 'Clicks', 'ads-shortcode-plugin' );
		$columns['ads_shortcode']   = __( 'Shortcode', 'ads-shortcode-plugin' );

		return $columns;
	}

	public static function render_columns( $column, $post_id ) {
		if ( 'ads_schedule' === $column ) {
			$start_date = get_post_meta( $post_id, '_ads_start_date', true );
			$end_date   = get_post_meta( $post_id, '_ads_end_date', true );

			if ( '' === $start_date && '' === $end_date ) {
				esc_html_e( 'Always', 'ads-shortcode-plugin' );
				return;
			}

			printf(
				'%s - %s',
				esc_html( '' === $start_date ? __( 'Now', 'ads-shortcode-plugin' ) : $start_date ),
				esc_html( '' === $end_date ? __( 'No end', 'ads-shortcode-plugin' ) : $end_date )
			);
			return;
		}

		if ( 'ads_weight' === $column ) {
			$weight = absint( get_post_meta( $post_id, '_ads_weight', true ) );
			echo esc_html( 1 > $weight ? 1 : $weight );
			return;
		}

		if ( 'ads_impressions' === $column ) {
			echo esc_html( absint( get_post_meta( $post_id, '_ads_impressions', true ) ) );
			return;
		}

		if ( 'ads_clicks' === $column ) {
			echo esc_html( absint( get_post_meta( $post_id, '_ads_clicks', true ) ) );
			return;
		}

		if ( 'ads_shortcode' === $column ) {
			printf(
				'<code>%s</code>',
				esc_html( sprintf( '[ads ids="%d"]', absint( $post_id ) ) )
			);
		}
	}

	public static function register_help_page() {
		add_submenu_page(
			'edit.php?post_type=' . ADS_POST_TYPE,
			__( 'Ads Shortcode Help', 'ads-shortcode-plugin' ),
			__( 'Usage Help', 'ads-shortcode-plugin' ),
			'edit_posts',
			'ads-shortcode-help',
			array( __CLASS__, 'render_help_page' )
		);

		add_submenu_page(
			'edit.php?post_type=' . ADS_POST_TYPE,
			__( 'Ads Settings', 'ads-shortcode-plugin' ),
			__( 'Settings', 'ads-shortcode-plugin' ),
			'manage_options',
			'ads-shortcode-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function render_help_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ads Shortcode Usage', 'ads-shortcode-plugin' ); ?></h1>
			<p><?php esc_html_e( 'Create ads in the Ads section, then insert them into post content with a shortcode.', 'ads-shortcode-plugin' ); ?></p>
			<p><code>[ads ids="1"]</code></p>
			<p><code>[ads ids="1,2,3"]</code></p>
			<p><code>[ads groups="sidebar"]</code></p>
			<p><code>[ads groups="sidebar,footer"]</code></p>
			<p><code>[ads ids="1,2,3" cache="ajax"]</code></p>
			<p><?php esc_html_e( 'With multiple IDs, one published ad is randomly selected on each render.', 'ads-shortcode-plugin' ); ?></p>
			<p><?php esc_html_e( 'Use cache="ajax" when full-page cache, including LiteSpeed Cache, freezes random ad output.', 'ads-shortcode-plugin' ); ?></p>
			<p><?php esc_html_e( 'This shortcode can also be used in builders like Oxygen when the target element executes WordPress shortcodes.', 'ads-shortcode-plugin' ); ?></p>
		</div>
		<?php
	}

	public static function render_settings_page() {
		if ( isset( $_POST['ads_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ads_settings_nonce'] ) ), 'ads_save_settings' ) && current_user_can( 'manage_options' ) ) {
			$fallback_ad_id        = isset( $_POST['ads_fallback_ad_id'] ) ? absint( wp_unslash( $_POST['ads_fallback_ad_id'] ) ) : 0;
			$template_enabled      = isset( $_POST['ads_template_enabled'] ) ? 1 : 0;
			$template_top_label    = isset( $_POST['ads_template_top_label'] ) ? wp_unslash( $_POST['ads_template_top_label'] ) : '';
			$template_bottom_label = isset( $_POST['ads_template_bottom_label'] ) ? wp_unslash( $_POST['ads_template_bottom_label'] ) : '';
			update_option( ADS_FALLBACK_AD_OPTION, $fallback_ad_id, false );
			update_option( ADS_TEMPLATE_ENABLED_OPTION, $template_enabled, false );
			update_option( ADS_TEMPLATE_TOP_LABEL_OPTION, Ads_Plugin::sanitize_template_label( $template_top_label ), false );
			update_option( ADS_TEMPLATE_BOTTOM_LABEL_OPTION, Ads_Plugin::sanitize_template_label( $template_bottom_label ), false );
			?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'ads-shortcode-plugin' ); ?></p></div>
			<?php
		}

		$fallback_ad_id        = absint( get_option( ADS_FALLBACK_AD_OPTION, 0 ) );
		$template_enabled      = (bool) get_option( ADS_TEMPLATE_ENABLED_OPTION, 1 );
		$template_top_label    = get_option( ADS_TEMPLATE_TOP_LABEL_OPTION, Ads_Plugin::get_default_template_top_label() );
		$template_bottom_label = get_option( ADS_TEMPLATE_BOTTOM_LABEL_OPTION, Ads_Plugin::get_default_template_bottom_label() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ads Settings', 'ads-shortcode-plugin' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'ads_save_settings', 'ads_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ads-fallback-ad-id"><?php esc_html_e( 'Fallback ad ID', 'ads-shortcode-plugin' ); ?></label></th>
						<td>
							<input
								type="number"
								id="ads-fallback-ad-id"
								name="ads_fallback_ad_id"
								min="0"
								step="1"
								value="<?php echo esc_attr( $fallback_ad_id ); ?>"
							/>
							<p class="description"><?php esc_html_e( 'Rendered when no eligible ad is found. Use 0 to disable fallback.', 'ads-shortcode-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Promotional template', 'ads-shortcode-plugin' ); ?></th>
						<td>
							<label for="ads-template-enabled">
								<input
									type="checkbox"
									id="ads-template-enabled"
									name="ads_template_enabled"
									value="1"
									<?php checked( $template_enabled ); ?>
								/>
								<?php esc_html_e( 'Wrap ads with promotional separators by default.', 'ads-shortcode-plugin' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Each ad can inherit this setting, override labels, or disable the template.', 'ads-shortcode-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ads-template-top-label"><?php esc_html_e( 'Default top label', 'ads-shortcode-plugin' ); ?></label></th>
						<td>
							<input
								type="text"
								id="ads-template-top-label"
								name="ads_template_top_label"
								class="regular-text"
								value="<?php echo esc_attr( $template_top_label ); ?>"
								placeholder="<?php echo esc_attr( Ads_Plugin::get_default_template_top_label() ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ads-template-bottom-label"><?php esc_html_e( 'Default bottom label', 'ads-shortcode-plugin' ); ?></label></th>
						<td>
							<input
								type="text"
								id="ads-template-bottom-label"
								name="ads_template_bottom_label"
								class="regular-text"
								value="<?php echo esc_attr( $template_bottom_label ); ?>"
								placeholder="<?php echo esc_attr( Ads_Plugin::get_default_template_bottom_label() ); ?>"
							/>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function render_group_add_fields() {
		?>
		<?php wp_nonce_field( 'ads_save_group_fields', 'ads_group_nonce' ); ?>
		<div class="form-field">
			<label for="ads-group-start-date"><?php esc_html_e( 'Start date', 'ads-shortcode-plugin' ); ?></label>
			<input type="date" id="ads-group-start-date" name="ads_group_start_date" value="" />
			<p><?php esc_html_e( 'Leave empty to make the group active immediately.', 'ads-shortcode-plugin' ); ?></p>
		</div>
		<div class="form-field">
			<label for="ads-group-end-date"><?php esc_html_e( 'End date', 'ads-shortcode-plugin' ); ?></label>
			<input type="date" id="ads-group-end-date" name="ads_group_end_date" value="" />
			<p><?php esc_html_e( 'Leave empty to keep the group active without an end date.', 'ads-shortcode-plugin' ); ?></p>
		</div>
		<?php
	}

	public static function render_group_edit_fields( $term ) {
		$start_date = get_term_meta( $term->term_id, '_ads_group_start_date', true );
		$end_date   = get_term_meta( $term->term_id, '_ads_group_end_date', true );
		?>
		<tr>
			<td colspan="2"><?php wp_nonce_field( 'ads_save_group_fields', 'ads_group_nonce' ); ?></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="ads-group-start-date"><?php esc_html_e( 'Start date', 'ads-shortcode-plugin' ); ?></label></th>
			<td><input type="date" id="ads-group-start-date" name="ads_group_start_date" value="<?php echo esc_attr( $start_date ); ?>" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="ads-group-end-date"><?php esc_html_e( 'End date', 'ads-shortcode-plugin' ); ?></label></th>
			<td><input type="date" id="ads-group-end-date" name="ads_group_end_date" value="<?php echo esc_attr( $end_date ); ?>" /></td>
		</tr>
		<?php
	}

	public static function save_group_fields( $term_id ) {
		if ( ! isset( $_POST['ads_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ads_group_nonce'] ) ), 'ads_save_group_fields' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$start_date = isset( $_POST['ads_group_start_date'] ) ? wp_unslash( $_POST['ads_group_start_date'] ) : '';
		$end_date   = isset( $_POST['ads_group_end_date'] ) ? wp_unslash( $_POST['ads_group_end_date'] ) : '';

		update_term_meta( $term_id, '_ads_group_start_date', Ads_Plugin::sanitize_date( $start_date ) );
		update_term_meta( $term_id, '_ads_group_end_date', Ads_Plugin::sanitize_date( $end_date ) );
	}
}
