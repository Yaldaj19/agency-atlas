<?php
/**
 * فعال‌سازی: ساخت ترم‌های استان برای نقشه‌های ثبت‌شده + flush rewrite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Activator {

	public static function activate() {
		Agency_Atlas_Post_Type::register();
		self::seed_regions();
		flush_rewrite_rules();
	}

	/**
	 * برای هر منطقهٔ هر نقشه، اگر ترم استان وجود ندارد ساخته می‌شود.
	 */
	public static function seed_regions() {
		foreach ( Agency_Atlas_Maps::all() as $map_id => $map ) {
			foreach ( $map['regions'] as $region_key => $region_name ) {
				if ( Agency_Atlas_Post_Type::term_for_region( $map_id, $region_key ) ) {
					continue;
				}

				$existing = get_term_by( 'name', $region_name, Agency_Atlas_Post_Type::TAXONOMY );
				if ( $existing ) {
					$term_id = $existing->term_id;
				} else {
					$result = wp_insert_term(
						$region_name,
						Agency_Atlas_Post_Type::TAXONOMY,
						array( 'slug' => $region_key )
					);
					if ( is_wp_error( $result ) ) {
						continue;
					}
					$term_id = $result['term_id'];
				}

				update_term_meta( $term_id, 'atlas_map_id', $map_id );
				update_term_meta( $term_id, 'atlas_region_key', $region_key );
			}
		}
	}
}
