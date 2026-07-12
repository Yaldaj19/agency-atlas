<?php
/**
 * پست‌تایپ «نمایندگی» و تاکسونومی «استان».
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Post_Type {

	const POST_TYPE = 'atlas_agency';
	const TAXONOMY  = 'atlas_region';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'archive_query' ) );
	}

	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => 'نمایندگی‌ها',
					'singular_name'      => 'نمایندگی',
					'menu_name'          => 'اطلس نمایندگی‌ها',
					'add_new'            => 'افزودن نمایندگی',
					'add_new_item'       => 'افزودن نمایندگی جدید',
					'edit_item'          => 'ویرایش نمایندگی',
					'new_item'           => 'نمایندگی جدید',
					'view_item'          => 'مشاهده نمایندگی',
					'search_items'       => 'جستجوی نمایندگی',
					'not_found'          => 'نمایندگی‌ای یافت نشد',
					'not_found_in_trash' => 'نمایندگی‌ای در زباله‌دان نیست',
					'all_items'          => 'همه نمایندگی‌ها',
				),
				'public'       => true,
				'show_in_rest' => true, // ادیتور بلوکی (گوتنبرگ) برای توضیحات نمایندگی.
				'menu_icon'    => 'dashicons-location-alt',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				// آرشیو خودکار CPT غیرفعال است تا نامک /branches برای یک «برگه» با چیدمان سفارشی آزاد بماند.
				// نمایش نقشه/لیست از طریق شورت‌کد [agency_atlas_archive] داخل همان برگه انجام می‌شود.
				'has_archive'  => false,
				'rewrite'      => array( 'slug' => 'branch', 'with_front' => false ),
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => 'استان‌ها',
					'singular_name' => 'استان',
					'menu_name'     => 'استان‌ها',
					'all_items'     => 'همه استان‌ها',
					'edit_item'     => 'ویرایش استان',
					'search_items'  => 'جستجوی استان',
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'agency-region', 'with_front' => false ),
			)
		);
	}

	/**
	 * ترم تاکسونومی متصل به یک region_key از یک نقشه.
	 */
	public static function term_for_region( $map_id, $region_key ) {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'number'     => 1,
				'meta_query' => array(
					array(
						'key'   => 'atlas_map_id',
						'value' => $map_id,
					),
					array(
						'key'   => 'atlas_region_key',
						'value' => $region_key,
					),
				),
			)
		);

		return ( ! is_wp_error( $terms ) && $terms ) ? $terms[0] : null;
	}

	/**
	 * همه نمایندگی‌های منتشرشده، گروه‌بندی‌شده بر اساس region_key نقشه.
	 */
	public static function agencies_by_region( $map_id ) {
		$grouped = array();

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post ) {
			$terms = get_the_terms( $post, self::TAXONOMY );
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				if ( get_term_meta( $term->term_id, 'atlas_map_id', true ) !== $map_id ) {
					continue;
				}
				$key = get_term_meta( $term->term_id, 'atlas_region_key', true );
				if ( ! $key ) {
					continue;
				}
				$grouped[ $key ][] = $post;
			}
		}

		return $grouped;
	}

	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['atlas_city']  = 'شهر';
				$new['atlas_phone'] = 'تلفن';
			}
		}

		return $new;
	}

	public static function column_content( $column, $post_id ) {
		if ( 'atlas_city' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_atlas_city', true ) );
		} elseif ( 'atlas_phone' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_atlas_phone', true ) );
		}
	}

	/**
	 * آرشیو نمایندگی‌ها بدون صفحه‌بندی نمایش داده می‌شود (گروه‌بندی بر اساس استان).
	 */
	public static function archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_post_type_archive( self::POST_TYPE ) || $query->is_tax( self::TAXONOMY ) ) {
			$query->set( 'posts_per_page', -1 );
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
		}
	}
}
