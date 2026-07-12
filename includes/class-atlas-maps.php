<?php
/**
 * رجیستری نقشه‌ها — نقشه‌های جدید از طریق فیلتر agency_atlas_maps اضافه می‌شوند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Maps {

	/**
	 * همه نقشه‌های ثبت‌شده.
	 *
	 * هر نقشه: label (نام نمایش)، svg (مسیر فایل SVG داخل سرور)،
	 * regions (آرایه region_key => نام فارسی).
	 *
	 * برای افزودن نقشه جدید:
	 * add_filter( 'agency_atlas_maps', function ( $maps ) {
	 *     $maps['turkey'] = array(
	 *         'label'   => 'ترکیه',
	 *         'svg'     => '/path/to/turkey.svg',
	 *         'regions' => array( 'istanbul' => 'استانبول' ),
	 *     );
	 *     return $maps;
	 * } );
	 */
	public static function all() {
		$maps = array(
			'iran' => array(
				'label'   => 'ایران',
				'svg'     => AGENCY_ATLAS_DIR . 'maps/iran/iran.svg',
				'regions' => include AGENCY_ATLAS_DIR . 'maps/iran/regions.php',
			),
		);

		return apply_filters( 'agency_atlas_maps', $maps );
	}

	public static function get( $map_id ) {
		$maps = self::all();

		return isset( $maps[ $map_id ] ) ? $maps[ $map_id ] : null;
	}

	/**
	 * محتوای SVG نقشه (فقط فایل‌های محلی ثبت‌شده در رجیستری خوانده می‌شوند).
	 */
	public static function svg( $map_id ) {
		$map = self::get( $map_id );
		if ( ! $map || empty( $map['svg'] ) || ! is_readable( $map['svg'] ) ) {
			return '';
		}

		return (string) file_get_contents( $map['svg'] );
	}
}
