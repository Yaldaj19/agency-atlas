<?php
/**
 * ماژول «بازارهای صادراتی» — بخش کامل + کرهٔ سه‌بعدی واقعی (three.js).
 *
 * شورت‌کد: [bitak_export_globe]  → کل بخش را (عنوان/توضیح/آمار/گلوب) رندر می‌کند،
 * پس می‌توانید همان یک شورت‌کد را هرجای صفحه بگذارید.
 * داده‌ها از option «agency_atlas_markets» (زیرمنوی «بازارهای صادراتی») خوانده می‌شوند.
 * رشته‌های متنی از طریق agency_atlas_i18n() برای WPML قابل ترجمه‌اند (context: agency-atlas).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Markets {

	const OPTION    = 'agency_atlas_markets';
	const SHORTCODE = 'bitak_export_globe';

	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		// گلوب یک ES module است؛ تگ اسکریپت را به type="module" تبدیل می‌کنیم.
		add_filter( 'script_loader_tag', array( __CLASS__, 'module_tag' ), 10, 3 );
	}

	/* ───────────────────────────── تنظیمات/داده ───────────────────────────── */

	public static function defaults() {
		return array(
			'eyebrow'      => 'حضور جهانی',
			'title'        => 'بازارهای بیتک',
			'title_tag'    => 'h2',
			'desc'         => '<p>محصولات بیتک علاوه بر حضور در بازار ایران، به بیش از ۱۰ کشور صادر می‌شوند. توسعهٔ بازارهای بین‌المللی و ایجاد همکاری‌های پایدار با شرکای تجاری، بخشی از مسیر رشد بیتک است.</p>',
			'count_number' => '+۱۰',
			'count_label'  => 'کشور مقصد صادرات',
			'theme'        => 'dark',
			'bg_color'     => '#171935',
			'text_color'   => '#ffffff',
			'origin_code'  => 'IR',
			'country_codes' => array( 'IQ', 'AF', 'TM', 'AM', 'AZ', 'GE', 'RU', 'PK', 'AE', 'OM', 'SY', 'KZ' ),
		);
	}

	public static function get() {
		$saved = get_option( self::OPTION, array() );
		$out   = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		if ( empty( $out['country_codes'] ) || ! is_array( $out['country_codes'] ) ) {
			$out['country_codes'] = self::defaults()['country_codes'];
		}
		return $out;
	}

	/**
	 * تبدیل آرایه‌ای از کدهای کشور به نقاط { code, name, lat, lng }.
	 */
	public static function points_from_codes( $codes ) {
		$all = function_exists( 'agency_atlas_countries' ) ? agency_atlas_countries() : array();
		$out = array();
		foreach ( (array) $codes as $c ) {
			$c = strtoupper( trim( $c ) );
			if ( isset( $all[ $c ] ) ) {
				$out[] = array(
					'code' => $c,
					'name' => $all[ $c ][0],
					'lat'  => (float) $all[ $c ][2],
					'lng'  => (float) $all[ $c ][3],
				);
			}
		}
		return $out;
	}

	private static function allowed_tag( $tag ) {
		$tag = strtolower( (string) $tag );
		return in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'div' ), true ) ? $tag : 'h2';
	}

	/* ───────────────────────────── فرانت‌اند ───────────────────────────── */

	public static function register_assets() {
		$css = AGENCY_ATLAS_DIR . 'assets/css/markets.css';
		$js  = AGENCY_ATLAS_DIR . 'assets/js/markets-globe.mjs';
		wp_register_style( 'agency-atlas-markets', AGENCY_ATLAS_URL . 'assets/css/markets.css', array(), file_exists( $css ) ? filemtime( $css ) : AGENCY_ATLAS_VERSION );
		wp_register_script( 'agency-atlas-markets', AGENCY_ATLAS_URL . 'assets/js/markets-globe.mjs', array(), file_exists( $js ) ? filemtime( $js ) : AGENCY_ATLAS_VERSION, array( 'in_footer' => true ) );
	}

	public static function enqueue() {
		wp_enqueue_style( 'agency-atlas-markets' );
		wp_enqueue_script( 'agency-atlas-markets' );
	}

	public static function module_tag( $tag, $handle, $src ) {
		if ( 'agency-atlas-markets' !== $handle ) {
			return $tag;
		}
		return '<script type="module" src="' . esc_url( $src ) . '" id="agency-atlas-markets-js"></script>' . "\n";
	}

	public static function shortcode( $atts ) {
		self::enqueue();

		$s = self::get();

		$atts = shortcode_atts( array(
			'theme' => '',
			'tag'   => '',
			'bg'    => '',
			'title' => '',
		), $atts, self::SHORTCODE );

		$theme      = '' !== $atts['theme'] ? $atts['theme'] : $s['theme'];
		$theme      = ( 'light' === $theme ) ? 'light' : 'dark';
		$title_tag  = self::allowed_tag( '' !== $atts['tag'] ? $atts['tag'] : $s['title_tag'] );
		$bg_color   = '' !== $atts['bg'] ? $atts['bg'] : $s['bg_color'];
		$title_text = '' !== $atts['title'] ? $atts['title'] : $s['title'];

		$origin = self::points_from_codes( array( $s['origin_code'] ) );
		$origin = $origin ? $origin[0] : null;
		$points = self::points_from_codes( $s['country_codes'] );

		// مارکرها: مبدأ + مقصدها
		$markers = array();
		if ( $origin ) {
			$markers[] = array( 'location' => array( $origin['lat'], $origin['lng'] ), 'name' => agency_atlas_i18n( $origin['name'], 'markets.country.' . $origin['code'] ), 'origin' => true );
		}
		foreach ( $points as $p ) {
			$markers[] = array( 'location' => array( $p['lat'], $p['lng'] ), 'name' => agency_atlas_i18n( $p['name'], 'markets.country.' . $p['code'] ), 'origin' => false );
		}

		$config = array(
			'markers' => $markers,
			'focus'   => $origin ? array( $origin['lat'], $origin['lng'] ) : array( 32.4279, 53.6880 ),
			'theme'   => $theme,
			'texture' => AGENCY_ATLAS_URL . 'assets/img/earth.jpg',
		);

		$style_vars = '';
		if ( $bg_color ) {
			$style_vars .= '--bm-bg:' . esc_attr( $bg_color ) . ';';
		}
		if ( $s['text_color'] ) {
			$style_vars .= '--bm-text:' . esc_attr( $s['text_color'] ) . ';';
		}

		ob_start();
		?>
		<section class="bitak-markets bitak-markets--<?php echo esc_attr( $theme ); ?>" style="<?php echo esc_attr( $style_vars ); ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
			<div class="bitak-markets__inner">
				<div class="bitak-markets__copy">
					<?php if ( $s['eyebrow'] ) : ?>
						<span class="bitak-markets__eyebrow"><?php echo esc_html( agency_atlas_i18n( $s['eyebrow'], 'markets.eyebrow' ) ); ?></span>
					<?php endif; ?>
					<<?php echo $title_tag; ?> class="bitak-markets__title"><?php echo esc_html( agency_atlas_i18n( $title_text, 'markets.title' ) ); ?></<?php echo $title_tag; ?>>
					<div class="bitak-markets__desc"><?php echo wp_kses_post( agency_atlas_i18n( $s['desc'], 'markets.desc' ) ); ?></div>
					<?php if ( $s['count_number'] ) : ?>
						<div class="bitak-markets__count">
							<b><?php echo esc_html( agency_atlas_i18n( $s['count_number'], 'markets.count.number' ) ); ?></b>
							<span><?php echo esc_html( agency_atlas_i18n( $s['count_label'], 'markets.count.label' ) ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<div class="bitak-markets__globewrap">
					<div class="bitak-globe bitak-globe--<?php echo esc_attr( $theme ); ?>" data-theme="<?php echo esc_attr( $theme ); ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<div class="bitak-globe__stage">
							<div class="bitak-globe__poster" aria-hidden="true">
								<svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
									<defs><radialGradient id="bg-globe-fill" cx="38%" cy="32%" r="75%"><stop offset="0" stop-color="#3a3f7a"/><stop offset="1" stop-color="#171935"/></radialGradient></defs>
									<circle cx="100" cy="100" r="82" fill="url(#bg-globe-fill)" stroke="rgba(255,255,255,.08)"/>
									<g stroke="rgba(107,196,200,.35)" stroke-width="1" fill="none">
										<ellipse cx="100" cy="100" rx="82" ry="30"/><ellipse cx="100" cy="100" rx="82" ry="58"/><line x1="18" y1="100" x2="182" y2="100"/>
										<ellipse cx="100" cy="100" rx="30" ry="82"/><ellipse cx="100" cy="100" rx="58" ry="82"/><line x1="100" y1="18" x2="100" y2="182"/>
									</g>
								</svg>
							</div>
							<canvas class="bitak-globe__canvas" role="img" aria-label="<?php echo esc_attr( agency_atlas_i18n( 'کرهٔ زمین با نشان کشورهای مقصد صادرات بیتک', 'markets.globe.aria' ) ); ?>"></canvas>
							<div class="bitak-globe__pins" aria-hidden="true"></div>

							<div class="bitak-globe__focusbar" hidden>
								<button type="button" class="bitak-globe__back" data-bg-back aria-label="<?php echo esc_attr( agency_atlas_i18n( 'بازگشت به نمای کلی', 'markets.back.aria' ) ); ?>">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12h18"/><path d="M12 5l7 7-7 7"/></svg>
								</button>
								<span class="bitak-globe__focusname"></span>
							</div>

							<span class="bitak-globe__badge">
								<b><?php echo esc_html( agency_atlas_i18n( $s['count_number'], 'markets.count.number' ) ); ?></b>
								<?php echo esc_html( agency_atlas_i18n( $s['count_label'], 'markets.count.label' ) ); ?>
							</span>
						</div>

						<?php if ( $points ) : ?>
							<ul class="bitak-globe__list" aria-label="<?php echo esc_attr( agency_atlas_i18n( 'کشورهای مقصد صادرات', 'markets.list.aria' ) ); ?>">
								<?php foreach ( $points as $p ) :
									$pname = agency_atlas_i18n( $p['name'], 'markets.country.' . $p['code'] ); ?>
									<li>
										<button type="button" class="bitak-globe__country" data-lat="<?php echo esc_attr( $p['lat'] ); ?>" data-lng="<?php echo esc_attr( $p['lng'] ); ?>" data-name="<?php echo esc_attr( $pname ); ?>">
											<span class="bitak-globe__dot" aria-hidden="true"></span><?php echo esc_html( $pname ); ?>
										</button>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<script type="application/json" class="bitak-globe__config"><?php echo wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
					</div>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/* ───────────────────────────── ادمین ───────────────────────────── */

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Agency_Atlas_Post_Type::POST_TYPE,
			'بازارهای صادراتی (گلوب)',
			'بازارهای صادراتی',
			'manage_options',
			'agency-atlas-markets',
			array( __CLASS__, 'render' )
		);
	}

	public static function admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'agency-atlas-markets' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		$css = AGENCY_ATLAS_DIR . 'assets/css/markets-admin.css';
		$js  = AGENCY_ATLAS_DIR . 'assets/js/markets-admin.js';
		wp_enqueue_style( 'agency-atlas-markets-admin', AGENCY_ATLAS_URL . 'assets/css/markets-admin.css', array( 'wp-color-picker' ), file_exists( $css ) ? filemtime( $css ) : AGENCY_ATLAS_VERSION );
		wp_enqueue_script( 'agency-atlas-markets-admin', AGENCY_ATLAS_URL . 'assets/js/markets-admin.js', array( 'jquery', 'wp-color-picker' ), file_exists( $js ) ? filemtime( $js ) : AGENCY_ATLAS_VERSION, true );
	}

	public static function register() {
		register_setting( 'agency_atlas_markets_group', self::OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	public static function sanitize( $input ) {
		$out = self::get();
		if ( ! is_array( $input ) ) {
			return $out;
		}
		foreach ( array( 'eyebrow', 'title', 'count_number', 'count_label' ) as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$out[ $k ] = sanitize_text_field( $input[ $k ] );
			}
		}
		if ( isset( $input['title_tag'] ) ) {
			$out['title_tag'] = self::allowed_tag( $input['title_tag'] );
		}
		if ( isset( $input['desc'] ) ) {
			$out['desc'] = wp_kses_post( $input['desc'] );
		}
		if ( isset( $input['theme'] ) ) {
			$out['theme'] = ( 'light' === $input['theme'] ) ? 'light' : 'dark';
		}
		foreach ( array( 'bg_color', 'text_color' ) as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$hex          = sanitize_hex_color( $input[ $k ] );
				$out[ $k ]    = $hex ? $hex : '';
			}
		}
		$all = function_exists( 'agency_atlas_countries' ) ? agency_atlas_countries() : array();
		if ( isset( $input['origin_code'] ) ) {
			$oc                 = strtoupper( sanitize_text_field( $input['origin_code'] ) );
			$out['origin_code'] = isset( $all[ $oc ] ) ? $oc : 'IR';
		}
		if ( isset( $input['country_codes'] ) ) {
			$codes = array();
			foreach ( (array) $input['country_codes'] as $c ) {
				$c = strtoupper( sanitize_text_field( $c ) );
				if ( isset( $all[ $c ] ) && ! in_array( $c, $codes, true ) ) {
					$codes[] = $c;
				}
			}
			$out['country_codes'] = $codes;
		}
		if ( function_exists( 'agency_atlas_flush_related_caches' ) ) {
			agency_atlas_flush_related_caches();
		}
		return $out;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s     = self::get();
		$all   = function_exists( 'agency_atlas_countries' ) ? agency_atlas_countries() : array();
		$count = count( self::points_from_codes( $s['country_codes'] ) );
		$opt   = esc_attr( self::OPTION );
		?>
		<div class="wrap atlas-mk">
			<h1 class="atlas-mk__h1"><span class="dashicons dashicons-admin-site-alt3"></span> بازارهای صادراتی — کرهٔ سه‌بعدی</h1>

			<!-- راهنما (قبل از تنظیمات) -->
			<div class="atlas-mk__guide">
				<div class="atlas-mk__guide-row">
					<div class="atlas-mk__guide-ico">🌍</div>
					<div>
						<h2>چطور استفاده کنم؟</h2>
						<p>این شورت‌کد را هرجای صفحه (مثلاً صفحهٔ «دربارهٔ ما») بگذارید تا <strong>کل بخش</strong> — عنوان، توضیح، آمار و کرهٔ چرخان — نمایش داده شود:</p>
						<code class="atlas-mk__code">[<?php echo esc_html( self::SHORTCODE ); ?>]</code>
						<p class="atlas-mk__hint">هم‌اکنون <strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong> کشور مقصد انتخاب شده است. رنگ پس‌زمینه، متن‌ها، تگ عنوان و کشورها را از فرم پایین تنظیم کنید.</p>
					</div>
				</div>
				<ul class="atlas-mk__guide-tags">
					<li><b>مبدأ کجاست؟</b> کشور «مبدأ» (پیش‌فرض ایران) روی گلوب با <em>پین بزرگ‌تر و هالهٔ متمایز</em> و برچسب نامش نمایش داده می‌شود؛ گلوب هم از همان‌جا شروع می‌شود.</li>
					<li><b>چند‌زبانه (WPML):</b> متن‌ها و نام کشورها از مسیر <em>WPML → String Translation → context: agency-atlas</em> قابل ترجمه‌اند.</li>
					<li><b>کلیک روی کشور:</b> کره به آن کشور می‌چرخد و زوم می‌کند؛ دکمهٔ بازگشت برمی‌گرداند.</li>
				</ul>
			</div>

			<form method="post" action="options.php" class="atlas-mk__form">
				<?php settings_fields( 'agency_atlas_markets_group' ); ?>

				<div class="atlas-mk__card">
					<h2 class="atlas-mk__card-h">محتوای بخش</h2>
					<div class="atlas-mk__grid">
						<label class="atlas-mk__field">
							<span>برچسب بالا (eyebrow)</span>
							<input type="text" name="<?php echo $opt; ?>[eyebrow]" value="<?php echo esc_attr( $s['eyebrow'] ); ?>">
						</label>
						<label class="atlas-mk__field">
							<span>عنوان بخش</span>
							<input type="text" name="<?php echo $opt; ?>[title]" value="<?php echo esc_attr( $s['title'] ); ?>">
						</label>
						<label class="atlas-mk__field atlas-mk__field--sm">
							<span>تگ عنوان (SEO)</span>
							<select name="<?php echo $opt; ?>[title_tag]">
								<?php foreach ( array( 'h1', 'h2', 'h3', 'h4', 'div' ) as $t ) : ?>
									<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $s['title_tag'], $t ); ?>><?php echo esc_html( strtoupper( $t ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</div>

					<div class="atlas-mk__field">
						<span>توضیح بخش</span>
						<?php
						wp_editor(
							$s['desc'],
							'atlas_mk_desc',
							array(
								'textarea_name' => self::OPTION . '[desc]',
								'textarea_rows' => 6,
								'media_buttons' => false,
								'teeny'         => true,
							)
						);
						?>
					</div>

					<div class="atlas-mk__grid">
						<label class="atlas-mk__field atlas-mk__field--sm">
							<span>عدد آمار</span>
							<input type="text" dir="ltr" name="<?php echo $opt; ?>[count_number]" value="<?php echo esc_attr( $s['count_number'] ); ?>">
						</label>
						<label class="atlas-mk__field">
							<span>برچسب آمار</span>
							<input type="text" name="<?php echo $opt; ?>[count_label]" value="<?php echo esc_attr( $s['count_label'] ); ?>">
						</label>
					</div>
				</div>

				<div class="atlas-mk__card">
					<h2 class="atlas-mk__card-h">ظاهر</h2>
					<div class="atlas-mk__appearance">
						<label class="atlas-mk__field atlas-mk__field--sm">
							<span>حالت گلوب</span>
							<select name="<?php echo $opt; ?>[theme]">
								<option value="dark" <?php selected( $s['theme'], 'dark' ); ?>>تیره (Dark)</option>
								<option value="light" <?php selected( $s['theme'], 'light' ); ?>>روشن (Light)</option>
							</select>
						</label>
						<div class="atlas-mk__color-row">
							<span class="atlas-mk__color-name">🎨 رنگ پس‌زمینهٔ کل بخش</span>
							<input type="text" class="atlas-mk-color" name="<?php echo $opt; ?>[bg_color]" value="<?php echo esc_attr( $s['bg_color'] ); ?>" data-default-color="#171935">
						</div>
						<div class="atlas-mk__color-row">
							<span class="atlas-mk__color-name">✒️ رنگ عنوان و متن‌ها</span>
							<input type="text" class="atlas-mk-color" name="<?php echo $opt; ?>[text_color]" value="<?php echo esc_attr( $s['text_color'] ); ?>" data-default-color="#ffffff">
						</div>
					</div>
				</div>

				<div class="atlas-mk__card">
					<h2 class="atlas-mk__card-h">مبدأ و کشورهای مقصد</h2>

					<label class="atlas-mk__field atlas-mk__field--mid">
						<span>مبدأ (مرکز صادرات)</span>
						<select name="<?php echo $opt; ?>[origin_code]">
							<?php foreach ( $all as $code => $c ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $s['origin_code'], $code ); ?>><?php echo esc_html( $c[0] . ' — ' . $c[1] ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>

					<div class="atlas-mk__field">
						<span>کشورهای مقصد صادرات <em class="atlas-mk__counter">(<span data-mk-count><?php echo esc_html( number_format_i18n( $count ) ); ?></span> انتخاب‌شده)</em></span>
						<div class="atlas-mk-multi" data-mk-multi>
							<input type="search" class="atlas-mk-multi__search" placeholder="🔎 جستجوی کشور (فارسی یا انگلیسی)…" autocomplete="off">
							<div class="atlas-mk-multi__list">
								<?php foreach ( $all as $code => $c ) :
									if ( $code === $s['origin_code'] ) { continue; }
									$checked = in_array( $code, (array) $s['country_codes'], true ); ?>
									<label class="atlas-mk-multi__item<?php echo $checked ? ' is-on' : ''; ?>" data-search="<?php echo esc_attr( mb_strtolower( $c[0] . ' ' . $c[1] . ' ' . $code ) ); ?>">
										<input type="checkbox" name="<?php echo $opt; ?>[country_codes][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( $checked ); ?>>
										<span class="atlas-mk-multi__fa"><?php echo esc_html( $c[0] ); ?></span>
										<span class="atlas-mk-multi__en"><?php echo esc_html( $c[1] ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<p class="atlas-mk__hint">هر تعداد کشور که نیاز دارید تیک بزنید. جستجو برای پیدا کردن سریع.</p>
					</div>
				</div>

				<?php submit_button( 'ذخیرهٔ تغییرات', 'primary large' ); ?>
			</form>
		</div>
		<?php
	}
}
