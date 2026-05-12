<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$ads_posts = get_posts(
	array(
		'post_type'   => 'ads_item',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

foreach ( $ads_posts as $ads_post_id ) {
	wp_delete_post( $ads_post_id, true );
}
