<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ads_Admin {

	public static function boot() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . ADS_POST_TYPE, array( __CLASS__, 'save_meta_boxes' ) );
		add_filter( 'manage_' . ADS_POST_TYPE . '_posts_columns', array( __CLASS__, 'register_columns' ) );
		add_action( 'manage_' . ADS_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
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
		$weight          = '' === $weight ? 1 : absint( $weight );
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

		update_post_meta( $post_id, '_ads_wrapper_classes', Ads_Plugin::sanitize_wrapper_classes( $wrapper_classes ) );
		update_post_meta( $post_id, '_ads_inline_css', Ads_Plugin::sanitize_css_declarations( $inline_css ) );
		update_post_meta( $post_id, '_ads_start_date', Ads_Plugin::sanitize_date( $start_date ) );
		update_post_meta( $post_id, '_ads_end_date', Ads_Plugin::sanitize_date( $end_date ) );
		update_post_meta( $post_id, '_ads_weight', Ads_Plugin::sanitize_weight( $weight ) );
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
			$fallback_ad_id = isset( $_POST['ads_fallback_ad_id'] ) ? absint( wp_unslash( $_POST['ads_fallback_ad_id'] ) ) : 0;
			update_option( ADS_FALLBACK_AD_OPTION, $fallback_ad_id, false );
			?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'ads-shortcode-plugin' ); ?></p></div>
			<?php
		}

		$fallback_ad_id = absint( get_option( ADS_FALLBACK_AD_OPTION, 0 ) );
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
