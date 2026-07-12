<?php
/**
 * حذف کامل پلاگین — داده‌ها فقط وقتی پاک می‌شوند که در تنظیمات تیک «حذف داده‌ها» زده شده باشد.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$atlas_settings = get_option( 'agency_atlas_settings', array() );

if ( is_array( $atlas_settings ) && ! empty( $atlas_settings['uninstall_data'] ) ) {
	// حذف همه نمایندگی‌ها.
	$atlas_posts = get_posts(
		array(
			'post_type'      => 'atlas_agency',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $atlas_posts as $atlas_post_id ) {
		wp_delete_post( $atlas_post_id, true );
	}

	// حذف ترم‌های استان.
	$atlas_terms = get_terms(
		array(
			'taxonomy'   => 'atlas_region',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( ! is_wp_error( $atlas_terms ) ) {
		foreach ( $atlas_terms as $atlas_term_id ) {
			wp_delete_term( $atlas_term_id, 'atlas_region' );
		}
	}
}

delete_option( 'agency_atlas_settings' );
