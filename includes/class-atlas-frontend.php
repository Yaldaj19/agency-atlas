<?php
/**
 * فرانت‌اند: شورت‌کد نقشه، کارت نمایندگی، قالب آرشیو و لود شرطی assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Frontend {

	private static $instance = 0;

	public static function init() {
		add_shortcode( 'agency_atlas', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'agency_atlas_archive', array( __CLASS__, 'shortcode_archive' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_filter( 'template_include', array( __CLASS__, 'templates' ) );
		add_filter( 'post_type_archive_link', array( __CLASS__, 'archive_link' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_archive_to_page' ) );
	}

	/**
	 * برگه انتخاب‌شده در تنظیمات به‌عنوان صفحه آرشیو (الگوی «صفحه فروشگاه» ووکامرس).
	 */
	public static function archive_page_id() {
		$page_id = (int) agency_atlas_get_settings()['archive_page_id'];

		// چندزبانه (WPML/Polylang): نسخهٔ برگهٔ آرشیو در زبان جاری. بدون افزونهٔ ترجمه، همان مقدار برمی‌گردد.
		if ( $page_id ) {
			$page_id = (int) apply_filters( 'wpml_object_id', $page_id, 'page', true );
		}

		return ( $page_id && 'publish' === get_post_status( $page_id ) ) ? $page_id : 0;
	}

	/**
	 * آدرس واقعی آرشیو CPT (/branches/) بدون اعمال ریدایرکت به برگهٔ انتخابی.
	 */
	public static function raw_archive_url() {
		remove_filter( 'post_type_archive_link', array( __CLASS__, 'archive_link' ), 10 );
		$url = get_post_type_archive_link( Agency_Atlas_Post_Type::POST_TYPE );
		add_filter( 'post_type_archive_link', array( __CLASS__, 'archive_link' ), 10, 2 );

		return $url;
	}

	/**
	 * آدرس «فهرست نمایندگی‌ها» برای پیوندهای بازگشت/بردکرامب.
	 * چون has_archive غیرفعال است، به برگهٔ /branches اشاره می‌کند.
	 */
	public static function directory_url() {
		$page_id = self::archive_page_id();

		// اگر در تنظیمات برگه‌ای ست نشده، برگه‌ای با نامک branches را پیدا کن.
		if ( ! $page_id ) {
			$page = get_page_by_path( 'branches' );
			if ( $page ) {
				$page_id = (int) apply_filters( 'wpml_object_id', $page->ID, 'page', true );
			}
		}

		return $page_id ? get_permalink( $page_id ) : home_url( '/' );
	}

	/**
	 * لینک آرشیو CPT به برگه انتخابی اشاره می‌کند.
	 */
	public static function archive_link( $link, $post_type ) {
		if ( Agency_Atlas_Post_Type::POST_TYPE === $post_type ) {
			$page_id = self::archive_page_id();
			if ( $page_id ) {
				return get_permalink( $page_id );
			}
		}

		return $link;
	}

	/**
	 * آدرس آرشیو داخلی به برگه انتخابی ریدایرکت می‌شود.
	 */
	public static function redirect_archive_to_page() {
		if ( ! is_post_type_archive( Agency_Atlas_Post_Type::POST_TYPE ) ) {
			return;
		}
		$page_id = self::archive_page_id();
		if ( ! $page_id || is_page( $page_id ) ) {
			return;
		}

		$target = get_permalink( $page_id );

		// جلوگیری از حلقهٔ ریدایرکت: اگر نامک برگهٔ آرشیو با نامک آرشیو CPT («branches») یکی باشد،
		// مقصد ریدایرکت همان آدرس فعلی می‌شود و مرورگر در حلقه می‌افتد (ERR_TOO_MANY_REDIRECTS).
		// در این حالت ریدایرکت را رها می‌کنیم تا خودِ قالب آرشیو رندر شود.
		if ( ! $target || untrailingslashit( $target ) === untrailingslashit( self::raw_archive_url() ) ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	public static function register_assets() {
		$css = AGENCY_ATLAS_DIR . 'assets/css/atlas.css';
		$js  = AGENCY_ATLAS_DIR . 'assets/js/atlas.js';

		wp_register_style( 'agency-atlas', AGENCY_ATLAS_URL . 'assets/css/atlas.css', array(), file_exists( $css ) ? filemtime( $css ) : AGENCY_ATLAS_VERSION );
		wp_register_script( 'agency-atlas', AGENCY_ATLAS_URL . 'assets/js/atlas.js', array(), file_exists( $js ) ? filemtime( $js ) : AGENCY_ATLAS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		// لود فقط در صفحاتی که لازم است.
		$need = is_post_type_archive( Agency_Atlas_Post_Type::POST_TYPE )
			|| is_tax( Agency_Atlas_Post_Type::TAXONOMY )
			|| is_singular( Agency_Atlas_Post_Type::POST_TYPE );

		if ( ! $need && is_singular() ) {
			$post = get_post();
			$need = $post && ( has_shortcode( $post->post_content, 'agency_atlas' ) || has_shortcode( $post->post_content, 'agency_atlas_archive' ) );
		}

		if ( $need ) {
			self::enqueue();
		}
	}

	public static function enqueue() {
		wp_enqueue_style( 'agency-atlas' );
		wp_enqueue_script( 'agency-atlas' );
	}

	public static function shortcode( $atts ) {
		$settings = agency_atlas_get_settings();
		$atts     = shortcode_atts(
			array(
				'map'      => 'iran',
				'display'  => $settings['display'],
				'chips'    => '1',
				'show_map' => '1',
				'list'     => '1',
				'header'   => '1',
			),
			$atts,
			'agency_atlas'
		);

		return self::archive_output( $atts, '0' !== $atts['header'], get_the_title( get_post() ) );
	}

	/**
	 * خروجی مشترک آرشیو: هدر (H1 + توضیح) + چیدمان لیست/نقشه.
	 *
	 * @param array  $atts           پارامترهای شورت‌کد.
	 * @param bool   $with_header    نمایش هدر عنوان/توضیح.
	 * @param string $fallback_title عنوانی که اگر «عنوان آرشیو» در تنظیمات خالی باشد استفاده می‌شود.
	 */
	private static function archive_output( $atts, $with_header, $fallback_title ) {
		$chips = '0' !== $atts['chips'];
		$out   = $with_header ? self::archive_header_html( $fallback_title ) : '';

		// پیش‌فرض: همان چیدمان آرشیو (لیست + نقشه). با list="0" فقط نقشه‌ی تعاملی (پنل/مودال).
		if ( '0' !== $atts['show_map'] && '0' !== $atts['list'] ) {
			$out .= self::render_locator( $atts['map'], $atts['display'], $chips );
		} elseif ( '0' !== $atts['show_map'] ) {
			$out .= self::render_map( $atts['map'], $atts['display'], $chips );
		} else {
			$out .= self::render_directory( $atts['map'] );
		}

		// wrapper مشابه قالب آرشیو تا شورت‌کد در هر برگه‌ای دقیقاً مثل آرشیو دیده شود.
		return '<div class="atlas-page atlas-shortcode" dir="rtl"><div class="atlas-container">' . $out . '</div></div>';
	}

	/**
	 * هدر آرشیو: عنوان (H1) از تنظیمات یا در صورت خالی‌بودن از $fallback_title، به‌همراه توضیح زیر آن.
	 */
	public static function archive_header_html( $fallback_title = '' ) {
		$settings   = agency_atlas_get_settings();
		$title      = '' !== trim( (string) $settings['archive_title'] ) ? $settings['archive_title'] : $fallback_title;
		$content    = $settings['archive_content'];
		$show_title = '1' !== (string) $settings['hide_archive_title'] && '' !== trim( (string) $title );

		// مسیر راهنما (breadcrumb) عمداً اینجا رندر نمی‌شود؛ نمایش آن بر عهدهٔ قالب/برگه است
		// تا بعد از عنوان و بدون تکرار نشان داده شود.
		if ( ! $show_title && '' === trim( (string) $content ) ) {
			return '';
		}

		ob_start();
		?>
		<header class="atlas-archive-header">
			<?php if ( $show_title ) : ?>
				<h1 class="atlas-archive-title"><?php echo esc_html( $title ); ?></h1>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $content ) ) : ?>
				<div class="atlas-archive-content"><?php echo apply_filters( 'the_content', wp_kses_post( $content ) ); // phpcs:ignore ?></div>
			<?php endif; ?>
		</header>
		<?php
		return ob_get_clean();
	}

	/**
	 * مسیر راهنما (breadcrumb): خانه / [کرامب‌های میانی] / عنوان جاری.
	 *
	 * @param string $current    عنوان صفحه جاری (کرامب آخر، بدون لینک).
	 * @param array  $middle     کرامب‌های میانی به‌صورت array( label => url ).
	 */
	public static function breadcrumb_html( $current, $middle = array() ) {
		ob_start();
		?>
		<nav class="atlas-breadcrumb" aria-label="مسیر راهنما">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
			<?php foreach ( $middle as $label => $url ) : ?>
				<span class="atlas-breadcrumb-sep" aria-hidden="true">/</span>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<?php if ( '' !== trim( (string) $current ) ) : ?>
				<span class="atlas-breadcrumb-sep" aria-hidden="true">/</span>
				<span class="atlas-breadcrumb-current"><?php echo esc_html( $current ); ?></span>
			<?php endif; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * شورت‌کد آرشیو کامل: نقشه + لیست همه نمایندگی‌ها گروه‌بندی‌شده بر اساس استان.
	 * برای استفاده داخل برگه‌ای که به‌عنوان صفحه آرشیو انتخاب می‌شود.
	 */
	public static function shortcode_archive( $atts ) {
		$settings = agency_atlas_get_settings();
		$atts     = shortcode_atts(
			array(
				'map'      => 'iran',
				'display'  => $settings['display'],
				'chips'    => '1',
				'show_map' => '1',
				'list'     => '1',
				'header'   => '1',
			),
			$atts,
			'agency_atlas_archive'
		);

		return self::archive_output( $atts, '0' !== $atts['header'], get_the_title( get_post() ) );
	}

	/**
	 * گروه‌بندی نمایندگی‌ها بر اساس استان، به ترتیب نقشه (فقط استان‌های دارای نمایندگی).
	 *
	 * @return array key => array( name, posts )
	 */
	public static function directory_groups( $map_id ) {
		$map = Agency_Atlas_Maps::get( $map_id );
		if ( ! $map ) {
			return array();
		}

		$grouped = Agency_Atlas_Post_Type::agencies_by_region( $map_id );
		$out     = array();

		foreach ( $map['regions'] as $key => $name ) {
			if ( ! empty( $grouped[ $key ] ) ) {
				$out[ $key ] = array(
					'name'  => $name,
					'posts' => $grouped[ $key ],
				);
			}
		}

		return $out;
	}

	/**
	 * چیدمان اصلی آرشیو: لیست (راست) + نقشه چسبان (چپ) در دسکتاپ.
	 */
	public static function render_locator( $map_id = 'iran', $display = 'inline', $chips = true ) {
		$map = Agency_Atlas_Maps::get( $map_id );
		if ( ! $map ) {
			return '';
		}

		self::enqueue();
		$groups   = self::directory_groups( $map_id );
		$map_html = self::render_map( $map_id, $display, $chips, 'locator' );
		$list     = $groups ? self::directory_markup( $groups ) : '<p class="atlas-hint">هنوز نمایندگی‌ای ثبت نشده است.</p>';

		ob_start();
		?>
		<div class="atlas-locator" dir="rtl" style="<?php echo esc_attr( self::color_vars() ); ?>">
			<div class="atlas-locator-list">
				<?php echo $list; // phpcs:ignore -- خروجی تابع escape شده است. ?>
			</div>
			<div class="atlas-locator-map">
				<?php echo $map_html; // phpcs:ignore -- خروجی تابع escape شده است. ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * لیست کامل نمایندگی‌ها، گروه‌بندی‌شده بر اساس استان (بدون نقشه).
	 */
	public static function render_directory( $map_id = 'iran' ) {
		self::enqueue();
		$groups = self::directory_groups( $map_id );

		if ( ! $groups ) {
			return '<p class="atlas-hint">هنوز نمایندگی‌ای ثبت نشده است.</p>';
		}

		return '<div class="atlas-directory-wrap" dir="rtl">' . self::directory_markup( $groups ) . '</div>';
	}

	/**
	 * مارک‌آپ گروه‌های استان — هر گروه شناسه دارد تا نقشه به آن اسکرول کند.
	 */
	private static function directory_markup( $groups ) {
		ob_start();
		?>
		<div class="atlas-directory">
			<?php foreach ( $groups as $key => $group ) : ?>
				<section class="atlas-dir-group" id="atlas-grp-<?php echo esc_attr( $key ); ?>" data-region="<?php echo esc_attr( $key ); ?>">
					<h2 class="atlas-dir-title">
						<span class="atlas-dir-pin"><?php echo self::icon( 'pin' ); // phpcs:ignore ?></span>
						<?php echo esc_html( $group['name'] ); ?>
						<span class="atlas-chip-count"><?php echo esc_html( number_format_i18n( count( $group['posts'] ) ) ); ?></span>
					</h2>
					<div class="atlas-cards">
						<?php foreach ( $group['posts'] as $group_post ) : ?>
							<?php echo self::render_card( $group_post ); // phpcs:ignore -- خروجی تابع escape شده است. ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * متغیرهای رنگ نقشه از تنظیمات (برای style inline).
	 */
	public static function color_vars() {
		$s = agency_atlas_get_settings();

		return sprintf(
			'--atlas-fill:%s;--atlas-hover:%s;--atlas-sea:%s;--atlas-dot:%s;',
			$s['map_fill'],
			$s['map_hover'],
			$s['map_sea'],
			$s['map_dot']
		);
	}

	/**
	 * رندر نقشه تعاملی.
	 *
	 * @param string $mode standalone (با پنل/مودال) یا locator (فقط نقشه، کلیک = اسکرول به لیست).
	 */
	public static function render_map( $map_id, $display = 'inline', $chips = true, $mode = 'standalone' ) {
		$map = Agency_Atlas_Maps::get( $map_id );
		if ( ! $map ) {
			return '';
		}

		$svg = Agency_Atlas_Maps::svg( $map_id );
		if ( ! $svg ) {
			return '';
		}

		self::enqueue();
		self::$instance++;
		$uid = 'atlas-' . self::$instance;

		// شناسه‌های داخل SVG برای چند نقشه در یک صفحه یکتا می‌شوند.
		$svg = str_replace( 'atlas-svg-title', $uid . '-svg-title', $svg );

		$display   = ( 'modal' === $display ) ? 'modal' : 'inline';
		$is_locator = ( 'locator' === $mode );
		$grouped   = Agency_Atlas_Post_Type::agencies_by_region( $map_id );
		$regions   = array();

		foreach ( $map['regions'] as $key => $name ) {
			$regions[ $key ] = array(
				'name'  => $name,
				'count' => isset( $grouped[ $key ] ) ? count( $grouped[ $key ] ) : 0,
			);
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $uid ); ?>" class="atlas-wrap<?php echo $is_locator ? ' atlas-wrap-locator' : ''; ?>" dir="rtl"
			data-display="<?php echo esc_attr( $display ); ?>"
			data-mode="<?php echo esc_attr( $mode ); ?>"
			data-regions="<?php echo esc_attr( wp_json_encode( $regions ) ); ?>"
			style="<?php echo esc_attr( self::color_vars() ); ?>">

			<div class="atlas-map-area">
				<?php echo $svg; // phpcs:ignore -- SVG ایستا و باندل‌شده خود پلاگین. ?>
				<div class="atlas-tooltip" aria-hidden="true"></div>
			</div>

			<?php if ( $chips ) : ?>
				<div class="atlas-chips" role="group" aria-label="استان‌های دارای نمایندگی">
					<?php foreach ( $regions as $key => $region ) : ?>
						<?php if ( $region['count'] > 0 ) : ?>
							<button type="button" class="atlas-chip" data-region="<?php echo esc_attr( $key ); ?>">
								<?php echo esc_html( $region['name'] ); ?>
								<span class="atlas-chip-count"><?php echo esc_html( number_format_i18n( $region['count'] ) ); ?></span>
							</button>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! $is_locator ) : ?>
				<?php if ( 'inline' === $display ) : ?>
					<div class="atlas-panel" aria-live="polite">
						<p class="atlas-hint">برای مشاهده نمایندگی‌های هر استان، روی نقشه یا نام استان کلیک کنید.</p>
					</div>
				<?php else : ?>
					<div class="atlas-modal" role="dialog" aria-modal="true" aria-label="نمایندگی‌ها" hidden>
						<div class="atlas-modal-backdrop" data-atlas-close></div>
						<div class="atlas-modal-box">
							<div class="atlas-modal-head">
								<h2 class="atlas-modal-title"></h2>
								<button type="button" class="atlas-modal-close" data-atlas-close aria-label="بستن پنجره">&times;</button>
							</div>
							<div class="atlas-modal-body"></div>
						</div>
					</div>
				<?php endif; ?>

				<?php foreach ( $grouped as $key => $posts ) : ?>
					<template data-region="<?php echo esc_attr( $key ); ?>">
						<div class="atlas-cards">
							<?php foreach ( $posts as $post ) : ?>
								<?php echo self::render_card( $post ); // phpcs:ignore -- خروجی داخل تابع escape می‌شود. ?>
							<?php endforeach; ?>
						</div>
					</template>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * کارت یک نمایندگی.
	 *
	 * @param WP_Post $post نمایندگی.
	 * @param bool    $link_title لینک عنوان به صفحه اختصاصی.
	 */
	public static function render_card( $post, $link_title = true ) {
		$city      = get_post_meta( $post->ID, '_atlas_city', true );
		$manager   = get_post_meta( $post->ID, '_atlas_manager', true );
		$phones    = self::get_phones( $post->ID );
		$addresses = self::get_addresses( $post->ID );
		$socials   = self::get_socials( $post->ID );
		$first_map = '';
		foreach ( $addresses as $ad ) {
			if ( ! empty( $ad['map_url'] ) ) {
				$first_map = $ad['map_url'];
				break;
			}
		}

		ob_start();
		?>
		<article class="atlas-card">
			<header class="atlas-card-head">
				<?php if ( has_post_thumbnail( $post ) ) : ?>
					<?php echo get_the_post_thumbnail( $post, 'thumbnail', array( 'class' => 'atlas-card-logo', 'loading' => 'lazy' ) ); ?>
				<?php else : ?>
					<span class="atlas-card-logo atlas-card-logo-empty" aria-hidden="true"><?php echo self::icon( 'pin' ); // phpcs:ignore ?></span>
				<?php endif; ?>
				<div>
					<h3 class="atlas-card-title">
						<?php if ( $link_title ) : ?>
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( get_the_title( $post ) ); ?>
						<?php endif; ?>
					</h3>
					<?php if ( $city ) : ?>
						<span class="atlas-card-city"><?php echo esc_html( $city ); ?></span>
					<?php endif; ?>
				</div>
			</header>

			<ul class="atlas-card-info">
				<?php if ( $manager ) : ?>
					<li><?php echo self::icon( 'user' ); // phpcs:ignore ?><span class="atlas-info-label">مدیر:</span> <?php echo esc_html( $manager ); ?></li>
				<?php endif; ?>
				<?php foreach ( $phones as $ph ) : ?>
					<li><?php echo self::icon( 'phone' ); // phpcs:ignore ?><a href="<?php echo esc_url( self::tel_href( $ph ) ); ?>" dir="ltr"><?php echo esc_html( $ph ); ?></a></li>
				<?php endforeach; ?>
				<?php foreach ( $addresses as $ad ) : ?>
					<?php if ( empty( $ad['text'] ) ) { continue; } ?>
					<li><?php echo self::icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( $ad['text'] ); ?></span></li>
				<?php endforeach; ?>
			</ul>

			<?php echo self::render_socials( $socials ); // phpcs:ignore -- خروجی تابع escape شده است. ?>

			<?php if ( $first_map || $link_title ) : ?>
				<footer class="atlas-card-actions">
					<?php if ( $first_map ) : ?>
						<a class="atlas-btn" href="<?php echo esc_url( $first_map ); ?>" target="_blank" rel="noopener">مسیریابی</a>
					<?php endif; ?>
					<?php if ( $link_title ) : ?>
						<a class="atlas-btn atlas-btn-ghost" href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank" rel="noopener">جزئیات بیشتر</a>
					<?php endif; ?>
				</footer>
			<?php endif; ?>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * شماره‌های تماس (آرایه) با سازگاری با نسخهٔ تک‌مقداری قدیمی.
	 */
	public static function get_phones( $post_id ) {
		$phones = get_post_meta( $post_id, '_atlas_phones', true );
		if ( ! is_array( $phones ) || ! $phones ) {
			$phones = array();
			foreach ( array( '_atlas_phone', '_atlas_mobile' ) as $k ) {
				$v = get_post_meta( $post_id, $k, true );
				if ( $v ) {
					$phones[] = $v;
				}
			}
		}

		return array_values( array_filter( array_map( 'trim', (array) $phones ) ) );
	}

	/**
	 * آدرس‌ها: آرایه‌ای از array( text, map_url ) با سازگاری با نسخهٔ قدیمی.
	 */
	public static function get_addresses( $post_id ) {
		$addresses = get_post_meta( $post_id, '_atlas_addresses', true );
		if ( ! is_array( $addresses ) || ! $addresses ) {
			$old = get_post_meta( $post_id, '_atlas_address', true );
			$addresses = $old
				? array( array( 'text' => $old, 'map_url' => get_post_meta( $post_id, '_atlas_map_url', true ) ) )
				: array();
		}

		$out = array();
		foreach ( $addresses as $ad ) {
			$text = isset( $ad['text'] ) ? trim( $ad['text'] ) : '';
			$url  = isset( $ad['map_url'] ) ? trim( $ad['map_url'] ) : '';
			if ( '' !== $text || '' !== $url ) {
				$out[] = array( 'text' => $text, 'map_url' => $url );
			}
		}

		return $out;
	}

	/**
	 * شبکه‌های اجتماعی: آرایه‌ای از array( network, url )؛ وب‌سایت قدیمی هم افزوده می‌شود.
	 */
	public static function get_socials( $post_id ) {
		$socials = get_post_meta( $post_id, '_atlas_socials', true );
		$socials = is_array( $socials ) ? $socials : array();

		$out      = array();
		$networks = self::social_networks();
		$has_site = false;
		foreach ( $socials as $s ) {
			$net = isset( $s['network'] ) ? sanitize_key( $s['network'] ) : '';
			$url = isset( $s['url'] ) ? trim( $s['url'] ) : '';
			if ( '' === $url || ! isset( $networks[ $net ] ) ) {
				continue;
			}
			if ( 'website' === $net ) {
				$has_site = true;
			}
			$out[] = array( 'network' => $net, 'url' => $url );
		}

		$legacy = get_post_meta( $post_id, '_atlas_website', true );
		if ( $legacy && ! $has_site ) {
			$out[] = array( 'network' => 'website', 'url' => $legacy );
		}

		return $out;
	}

	/**
	 * فهرست شبکه‌های اجتماعی پشتیبانی‌شده (برای متاباکس و نمایش).
	 */
	public static function social_networks() {
		return array(
			'telegram'  => array( 'label' => 'تلگرام', 'color' => '#229ED9' ),
			'whatsapp'  => array( 'label' => 'واتساپ', 'color' => '#25D366' ),
			'bale'      => array( 'label' => 'بله', 'color' => '#20A6A0' ),
			'rubika'    => array( 'label' => 'روبیکا', 'color' => '#6C4BF4' ),
			'eitaa'     => array( 'label' => 'ایتا', 'color' => '#EA7A17' ),
			'instagram' => array( 'label' => 'اینستاگرام', 'color' => '#E1306C' ),
			'aparat'    => array( 'label' => 'آپارات', 'color' => '#ED145B' ),
			'youtube'   => array( 'label' => 'یوتیوب', 'color' => '#FF0000' ),
			'twitter'   => array( 'label' => 'توییتر (X)', 'color' => '#111111' ),
			'facebook'  => array( 'label' => 'فیسبوک', 'color' => '#1877F2' ),
			'linkedin'  => array( 'label' => 'لینکدین', 'color' => '#0A66C2' ),
			'website'   => array( 'label' => 'وب‌سایت', 'color' => '#475569' ),
			'email'     => array( 'label' => 'ایمیل', 'color' => '#64748B' ),
		);
	}

	/**
	 * ردیف آیکن‌های شبکه‌های اجتماعی.
	 */
	public static function render_socials( $socials ) {
		if ( ! $socials ) {
			return '';
		}
		$networks = self::social_networks();

		ob_start();
		echo '<div class="atlas-socials">';
		foreach ( $socials as $s ) {
			$net  = $s['network'];
			$meta = isset( $networks[ $net ] ) ? $networks[ $net ] : array( 'label' => $net, 'color' => '#475569' );
			$href = $s['url'];
			if ( 'email' === $net && false !== strpos( $href, '@' ) && 0 !== strpos( $href, 'mailto:' ) ) {
				$href = 'mailto:' . $href;
			}
			printf(
				'<a class="atlas-social atlas-social-%1$s" style="--sc:%2$s" href="%3$s" target="_blank" rel="noopener nofollow" aria-label="%4$s" title="%4$s">%5$s</a>',
				esc_attr( $net ),
				esc_attr( $meta['color'] ),
				esc_url( $href ),
				esc_attr( $meta['label'] ),
				self::social_icon( $net ) // phpcs:ignore -- SVG داخلی.
			);
		}
		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * آیکن هر شبکهٔ اجتماعی. اگر قالب آیکن رنگی متناظر داشته باشد از آن استفاده می‌شود،
	 * وگرنه آیکن داخلی پلاگین.
	 */
	public static function social_icon( $network ) {
		// نگاشت به آیکن‌های قالب (پوشهٔ social-media).
		$themed = array(
			'telegram'  => 'social-media/telegram',
			'whatsapp'  => 'social-media/whatsapp',
			'eitaa'     => 'social-media/eitaa',
			'instagram' => 'social-media/instagram',
			'aparat'    => 'social-media/footer/aparat',
			'youtube'   => 'social-media/footer/youtube',
			'twitter'   => 'social-media/x',
			'facebook'  => 'social-media/facebook',
			'linkedin'  => 'social-media/linkedin',
			'email'     => 'social-media/email',
		);
		if ( isset( $themed[ $network ] ) && function_exists( 'get_svg_icon' ) && defined( 'YJ19_ICONS' ) && file_exists( YJ19_ICONS . '/' . $themed[ $network ] . '.svg' ) ) {
			return get_svg_icon( $themed[ $network ], 'atlas-social-svg' );
		}

		$v = 'viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"';
		switch ( $network ) {
			case 'telegram':
				return '<svg ' . $v . '><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.7.8l-4.6-3.4-2.2 2.1c-.3.3-.5.5-1 .5l.3-4.7 8.6-7.8c.4-.3-.1-.5-.6-.2L6.7 13 2.2 11.6c-1-.3-1-1 .2-1.4L20.6 3c.8-.3 1.5.2 1.3 1.3z"/></svg>';
			case 'whatsapp':
				return '<svg ' . $v . '><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.5A10 10 0 1 0 12 2zm5.4 14.2c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 .9-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.4 0 .5l-.4.6c-.2.2-.3.4-.1.7.2.3.9 1.4 1.9 2.3 1.3 1.1 2.3 1.5 2.6 1.6.3.1.5.1.7-.1l.7-.9c.2-.2.4-.2.6-.1l1.8.9c.3.1.4.2.5.3.1.3.1.7-.1 1.3z"/></svg>';
			case 'instagram':
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>';
			case 'website':
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/></svg>';
			case 'email':
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>';
			case 'youtube':
			case 'aparat':
				return '<svg ' . $v . '><path d="M3 8a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V8zm7 1.5v5l4.5-2.5L10 9.5z"/></svg>';
			case 'twitter':
				return '<svg ' . $v . '><path d="M17.5 3h3l-6.6 7.5L22 21h-6l-4.3-5.6L6.7 21H3.6l7-8L2.5 3h6.2l3.9 5.1L17.5 3zm-1 16h1.7L7.6 4.8H5.8L16.5 19z"/></svg>';
			case 'linkedin':
				return '<svg ' . $v . '><path d="M6.9 8.4H3.9V21h3V8.4zM5.4 3a1.8 1.8 0 1 0 0 3.6 1.8 1.8 0 0 0 0-3.6zM21 21h-3v-6.6c0-1.6-.6-2.6-2-2.6-1 0-1.6.7-1.9 1.4-.1.2-.1.6-.1.9V21h-3V8.4h3v1.4c.4-.6 1.2-1.5 2.9-1.5 2.1 0 3.7 1.4 3.7 4.3V21z"/></svg>';
			case 'facebook':
				return '<svg ' . $v . '><path d="M15 3h-2.5C10 3 9 4.6 9 6.5V9H7v3h2v9h3v-9h2.3l.7-3H12V7c0-.6.4-1 1-1h2V3z"/></svg>';
			default:
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16v11H8l-4 3V5z"/></svg>';
		}
	}

	/**
	 * لینک tel: استاندارد (۰۲۱... به +98 تبدیل می‌شود).
	 */
	public static function tel_href( $number ) {
		$clean = preg_replace( '/[^0-9+]/', '', $number );
		if ( 0 === strpos( $clean, '0' ) ) {
			$clean = '+98' . substr( $clean, 1 );
		}

		return 'tel:' . $clean;
	}

	public static function render_icon( $name ) {
		return self::icon( $name );
	}

	private static function icon( $name ) {
		$icons = array(
			'user'   => '<svg class="atlas-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>',
			'phone'  => '<svg class="atlas-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>',
			'mobile' => '<svg class="atlas-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>',
			'pin'    => '<svg class="atlas-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
			'globe'  => '<svg class="atlas-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20Z"/></svg>',
		);

		return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	}

	/**
	 * قالب‌های آرشیو، تاکسونومی و صفحه تکی از پلاگین لود می‌شوند مگر قالب سایت override کند.
	 */
	public static function templates( $template ) {
		if ( is_post_type_archive( Agency_Atlas_Post_Type::POST_TYPE ) || is_tax( Agency_Atlas_Post_Type::TAXONOMY ) ) {
			$theme_template = locate_template( array( 'archive-' . Agency_Atlas_Post_Type::POST_TYPE . '.php' ) );

			return $theme_template ? $theme_template : AGENCY_ATLAS_DIR . 'templates/archive-agency.php';
		}

		if ( is_singular( Agency_Atlas_Post_Type::POST_TYPE ) ) {
			$theme_template = locate_template( array( 'single-' . Agency_Atlas_Post_Type::POST_TYPE . '.php' ) );

			return $theme_template ? $theme_template : AGENCY_ATLAS_DIR . 'templates/single-agency.php';
		}

		return $template;
	}
}
