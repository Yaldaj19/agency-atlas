<?php
/**
 * اسکیما (JSON-LD): LocalBusiness برای صفحه هر نمایندگی و ItemList برای آرشیو.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Schema {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output' ) );
	}

	public static function output() {
		if ( is_singular( Agency_Atlas_Post_Type::POST_TYPE ) ) {
			self::single_schema();
		} elseif ( is_post_type_archive( Agency_Atlas_Post_Type::POST_TYPE ) ) {
			self::archive_schema();
		}
	}

	private static function single_schema() {
		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'LocalBusiness',
			'name'     => get_the_title( $post ),
			'url'      => get_permalink( $post ),
		);

		$phones = Agency_Atlas_Frontend::get_phones( $post->ID );
		if ( $phones ) {
			$schema['telephone'] = $phones[0];
		}

		$addresses = Agency_Atlas_Frontend::get_addresses( $post->ID );
		$address   = $addresses ? $addresses[0]['text'] : '';
		$city      = get_post_meta( $post->ID, '_atlas_city', true );
		$terms     = get_the_terms( $post, Agency_Atlas_Post_Type::TAXONOMY );

		$socials = Agency_Atlas_Frontend::get_socials( $post->ID );
		if ( $socials ) {
			$same = array();
			foreach ( $socials as $s ) {
				if ( 'email' !== $s['network'] ) {
					$same[] = $s['url'];
				}
			}
			if ( $same ) {
				$schema['sameAs'] = $same;
			}
		}

		if ( $address || $city || ( $terms && ! is_wp_error( $terms ) ) ) {
			$postal = array(
				'@type'          => 'PostalAddress',
				'addressCountry' => 'IR',
			);
			if ( $address ) {
				$postal['streetAddress'] = $address;
			}
			if ( $city ) {
				$postal['addressLocality'] = $city;
			}
			if ( $terms && ! is_wp_error( $terms ) ) {
				$postal['addressRegion'] = $terms[0]->name;
			}
			$schema['address'] = $postal;
		}

		if ( has_post_thumbnail( $post ) ) {
			$schema['image'] = get_the_post_thumbnail_url( $post, 'medium' );
		}

		self::print_json( $schema );

		// مسیر راهنمای ساختاری.
		self::breadcrumb_schema(
			array(
				'خانه'        => home_url( '/' ),
				'نمایندگی‌ها' => Agency_Atlas_Frontend::directory_url(),
				get_the_title( $post ) => get_permalink( $post ),
			)
		);
	}

	/**
	 * BreadcrumbList برای موتورهای جستجو.
	 *
	 * @param array $items label => url (به ترتیب).
	 */
	private static function breadcrumb_schema( $items ) {
		$list = array();
		$i    = 1;
		foreach ( $items as $label => $url ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i++,
				'name'     => $label,
				'item'     => $url,
			);
		}

		self::print_json(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $list,
			)
		);
	}

	private static function print_json( $schema ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	private static function archive_schema() {
		$posts = get_posts(
			array(
				'post_type'      => Agency_Atlas_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( ! $posts ) {
			return;
		}

		$items = array();
		foreach ( $posts as $i => $post ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => get_the_title( $post ),
				'url'      => get_permalink( $post ),
			);
		}

		$title = agency_atlas_get_settings()['archive_title'];
		if ( '' === trim( (string) $title ) ) {
			$title = post_type_archive_title( '', false );
		}

		self::print_json(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'ItemList',
				'name'            => $title,
				'itemListElement' => $items,
			)
		);
	}
}
