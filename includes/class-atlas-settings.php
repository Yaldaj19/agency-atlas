<?php
/**
 * صفحه تنظیمات + راهنمای استفاده.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Settings {

	const OPTION = 'agency_atlas_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Agency_Atlas_Post_Type::POST_TYPE,
			'تنظیمات اطلس نمایندگی‌ها',
			'تنظیمات و راهنما',
			'manage_options',
			'agency-atlas-settings',
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			'agency_atlas',
			self::OPTION,
			array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) )
		);
	}

	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'agency-atlas-settings' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".atlas-color").wpColorPicker();});' );

		wp_register_style( 'agency-atlas-admin', false, array(), AGENCY_ATLAS_VERSION );
		wp_enqueue_style( 'agency-atlas-admin' );
		wp_add_inline_style( 'agency-atlas-admin', self::admin_css() );
	}

	private static function admin_css() {
		return '
		.atlas-admin{max-width:1000px}
		.atlas-admin .nav-tab-wrapper{margin-bottom:24px;border-bottom:1px solid #e2e4e7}
		.atlas-admin .nav-tab{border-radius:8px 8px 0 0;font-weight:600}
		.atlas-admin .nav-tab-active{border-bottom-color:#fff;color:#b45309}
		.atlas-hero{display:flex;align-items:center;gap:16px;background:linear-gradient(120deg,#fff7e0,#eef4fb);border:1px solid #f2e6cf;border-radius:16px;padding:20px 24px;margin:16px 0 8px}
		.atlas-hero .dashicons{font-size:34px;width:34px;height:34px;color:#d99a1e}
		.atlas-hero h1{margin:0;font-size:22px}
		.atlas-hero p{margin:4px 0 0;color:#64748b}
		.atlas-card-box{background:#fff;border:1px solid #e2e4e7;border-radius:14px;padding:22px 26px;margin:0 0 20px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
		.atlas-card-box>h2.title,.atlas-card-box>h2{margin-top:0;padding-bottom:12px;border-bottom:1px solid #eef0f2}
		.atlas-admin .form-table th{font-weight:700}
		.atlas-guide{line-height:2}
		.atlas-guide h2{font-size:18px;margin:26px 0 10px;padding-inline-start:12px;border-inline-start:4px solid #ffcc00}
		.atlas-guide h3{font-size:15px;margin:18px 0 8px;color:#334155}
		.atlas-guide code{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:2px 7px;color:#b45309;font-size:13px}
		.atlas-guide table code{color:#0f766e}
		.atlas-guide ol,.atlas-guide ul{padding-inline-start:22px}
		.atlas-guide .widefat{border-radius:10px;overflow:hidden;margin:8px 0 16px}
		';
	}

	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = agency_atlas_get_settings();

		foreach ( array( 'map_fill', 'map_hover', 'map_sea', 'map_dot' ) as $color ) {
			if ( isset( $input[ $color ] ) ) {
				$val           = sanitize_hex_color( $input[ $color ] );
				$out[ $color ] = $val ? $val : $out[ $color ];
			}
		}

		$out['display']          = ( isset( $input['display'] ) && 'modal' === $input['display'] ) ? 'modal' : 'inline';
		$out['card_style']       = ( isset( $input['card_style'] ) && 'classic' === $input['card_style'] ) ? 'classic' : 'glassmorphism';

		// رنگ‌های سفارشی کارت‌ها؛ خالی = پیش‌فرض (بدون رنگ سفارشی).
		foreach ( array( 'card_bg', 'card_text', 'card_border' ) as $ck ) {
			if ( isset( $input[ $ck ] ) ) {
				$val        = ( '' === trim( (string) $input[ $ck ] ) ) ? '' : sanitize_hex_color( $input[ $ck ] );
				$out[ $ck ] = $val ? $val : '';
			}
		}
		$out['archive_page_id']  = isset( $input['archive_page_id'] ) ? absint( $input['archive_page_id'] ) : $out['archive_page_id'];
		$out['archive_title']    = isset( $input['archive_title'] ) ? sanitize_text_field( $input['archive_title'] ) : $out['archive_title'];
		$out['hide_archive_title'] = empty( $input['hide_archive_title'] ) ? '' : '1';
		$out['archive_content']  = isset( $input['archive_content'] ) ? wp_kses_post( $input['archive_content'] ) : $out['archive_content'];
		$out['archive_show_map'] = empty( $input['archive_show_map'] ) ? '' : '1';
		// حذف داده‌ها فقط با تأیید دوگانه فعال می‌شود (تیک اصلی + تأیید نهایی).
		$out['uninstall_data']   = ( ! empty( $input['uninstall_data'] ) && ! empty( $input['uninstall_confirm'] ) ) ? '1' : '';

		return $out;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = agency_atlas_get_settings();
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
		$base_url = admin_url( 'edit.php?post_type=' . Agency_Atlas_Post_Type::POST_TYPE . '&page=agency-atlas-settings' );
		?>
		<div class="wrap atlas-admin">
			<div class="atlas-hero">
				<span class="dashicons dashicons-location-alt"></span>
				<div>
					<h1>اطلس نمایندگی‌ها</h1>
					<p>نقشهٔ تعاملی و آرشیو نمایندگی‌ها — تنظیم ظاهر، صفحهٔ آرشیو و راهنمای استفاده.</p>
				</div>
			</div>
			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( $base_url ); ?>" class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>">تنظیمات</a>
				<a href="<?php echo esc_url( $base_url . '&tab=guide' ); ?>" class="nav-tab <?php echo 'guide' === $tab ? 'nav-tab-active' : ''; ?>">راهنمای استفاده</a>
			</h2>

			<?php if ( 'guide' === $tab ) : ?>
				<?php self::render_guide(); ?>
			<?php else : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'agency_atlas' ); ?>
					<div class="atlas-card-box">
					<h2 class="title">ظاهر نقشه</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label>رنگ استان‌ها</label></th>
							<td><input type="text" class="atlas-color" name="<?php echo esc_attr( self::OPTION ); ?>[map_fill]" value="<?php echo esc_attr( $settings['map_fill'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label>رنگ هاور / انتخاب</label></th>
							<td><input type="text" class="atlas-color" name="<?php echo esc_attr( self::OPTION ); ?>[map_hover]" value="<?php echo esc_attr( $settings['map_hover'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label>رنگ دریاها</label></th>
							<td><input type="text" class="atlas-color" name="<?php echo esc_attr( self::OPTION ); ?>[map_sea]" value="<?php echo esc_attr( $settings['map_sea'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label>رنگ نشانگر نمایندگی</label></th>
							<td><input type="text" class="atlas-color" name="<?php echo esc_attr( self::OPTION ); ?>[map_dot]" value="<?php echo esc_attr( $settings['map_dot'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row">حالت نمایش پیش‌فرض</th>
							<td>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[display]" value="inline" <?php checked( $settings['display'], 'inline' ); ?>> پنل کنار نقشه (اسکرول نرم)</label><br>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[display]" value="modal" <?php checked( $settings['display'], 'modal' ); ?>> مودال (پنجره بازشو)</label>
								<p class="description">در هر شورت‌کد هم قابل تغییر است: <code dir="ltr">[agency_atlas display="modal"]</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row">استایل کارت‌های نمایندگی</th>
							<td>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[card_style]" value="glassmorphism" <?php checked( $settings['card_style'], 'glassmorphism' ); ?>> شیشه‌ای (گلاسمورفیسم) — پیش‌فرض</label><br>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[card_style]" value="classic" <?php checked( $settings['card_style'], 'classic' ); ?>> کلاسیک (کارت سفید ساده)</label>
								<p class="description">ظاهر کارت‌های نمایندگی در آرشیو و کنار نقشه. حالت شیشه‌ای پس‌زمینهٔ محو و بلور دارد؛ حالت کلاسیک همان کارت سفید قبلی است.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">رنگ‌های کارت (اختیاری)</th>
							<td>
								<p style="margin:0 0 10px">
									<label style="display:inline-block;min-width:150px">رنگ پس‌زمینه کارت</label>
									<input type="text" class="atlas-color" name="<?php echo esc_attr( self::OPTION ); ?>[card_bg]" value="<?php echo esc_attr( $settings['card_bg'] ); ?>" data-default-color="">
								</p>
								<p style="margin:0 0 10px">
									<label style="display:inline-block;min-width:150px">رنگ متن کارت</label>
									<input type="text" class="atlas-color" name="<?php echo esc_attr( self::OPTION ); ?>[card_text]" value="<?php echo esc_attr( $settings['card_text'] ); ?>" data-default-color="">
								</p>
								<p style="margin:0 0 6px">
									<label style="display:inline-block;min-width:150px">رنگ بوردر کارت</label>
									<input type="text" class="atlas-color" name="<?php echo esc_attr( self::OPTION ); ?>[card_border]" value="<?php echo esc_attr( $settings['card_border'] ); ?>" data-default-color="">
								</p>
								<p class="description">هر کدام را خالی بگذارید، همان حالت پیش‌فرض (شیشه‌ای فعلی) استفاده می‌شود. در حالت شیشه‌ای، رنگ پس‌زمینه به‌صورت نیمه‌شفاف (با بلور) اعمال می‌شود.</p>
							</td>
						</tr>
					</table>

					</div>
					<div class="atlas-card-box">
					<h2 class="title">صفحه آرشیو نمایندگی‌ها</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="atlas-archive-page">برگه اختصاصی آرشیو</label></th>
							<td>
								<?php
								wp_dropdown_pages(
									array(
										'id'                => 'atlas-archive-page',
										'name'              => self::OPTION . '[archive_page_id]',
										'selected'          => (int) $settings['archive_page_id'],
										'show_option_none'  => '— آرشیو داخلی پلاگین (پیش‌فرض) —',
										'option_none_value' => '0',
									)
								);
								?>
								<p class="description">
									اگر برگه‌ای انتخاب کنید، آدرس آرشیو نمایندگی‌ها به آن برگه منتقل می‌شود و محتوای صفحه کاملاً در اختیار خودتان است (ادیتور بلوکی، بلوک HTML سفارشی و ...).
									داخل برگه، شورت‌کد <code dir="ltr">[agency_atlas_archive]</code> را هر جا خواستید بگذارید تا نقشه + لیست کامل نمایندگی‌ها همان‌جا رندر شود.
									در این حالت «عنوان» و «محتوای» زیر نادیده گرفته می‌شوند.
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="atlas-archive-title">عنوان آرشیو (H1 صفحه)</label></th>
							<td>
								<input type="text" class="regular-text" id="atlas-archive-title" name="<?php echo esc_attr( self::OPTION ); ?>[archive_title]" value="<?php echo esc_attr( $settings['archive_title'] ); ?>">
								<p class="description">همان تیتر اصلی (H1) صفحه است. اگر خالی بماند، عنوان برگه‌ی اختصاص‌داده‌شده به آرشیو نمایش داده می‌شود. توضیح زیر، درست زیر همین تیتر چاپ می‌شود.</p>
								<p style="margin-top:10px"><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[hide_archive_title]" value="1" <?php checked( $settings['hide_archive_title'], '1' ); ?>> عنوان (H1) بالای آرشیو نمایش داده نشود</label></p>
								<p class="description">اگر می‌خواهید عنوان صفحه را خودِ برگه (قالب) نمایش دهد، این گزینه را بزنید تا تیتر تکراری پلاگین حذف شود.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">نمایش نقشه در آرشیو</th>
							<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[archive_show_map]" value="1" <?php checked( $settings['archive_show_map'], '1' ); ?>> نقشه تعاملی بالای آرشیو نمایش داده شود</label></td>
						</tr>
						<tr>
							<th scope="row"><label>توضیح (زیر عنوان)</label></th>
							<td>
								<?php
								wp_editor(
									$settings['archive_content'],
									'atlas_archive_content',
									array(
										'textarea_name' => self::OPTION . '[archive_content]',
										'textarea_rows' => 8,
										'media_buttons' => true,
									)
								);
								?>
								<p class="description">این توضیح دقیقاً زیر تیتر (H1) آرشیو نمایش داده می‌شود.</p>
							</td>
						</tr>
					</table>

					</div>
					<div class="atlas-card-box">
					<h2 class="title">داده‌ها</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">حذف داده‌ها هنگام حذف پلاگین</th>
							<td>
								<label><input type="checkbox" id="atlas-uninstall-data" name="<?php echo esc_attr( self::OPTION ); ?>[uninstall_data]" value="1" <?php checked( $settings['uninstall_data'], '1' ); ?>> با حذف کامل پلاگین، همه نمایندگی‌ها، استان‌ها و تنظیمات هم پاک شوند</label>
								<div id="atlas-uninstall-confirm-box" style="display:none;margin-top:12px;padding:14px 16px;border:1px solid #d63638;background:#fcf0f1;border-radius:8px;max-width:640px">
									<p style="margin:0 0 10px;color:#8a1f1f;font-weight:700">⚠️ هشدار: این کار برگشت‌ناپذیر است. با حذف پلاگین، تمام نمایندگی‌ها و استان‌ها برای همیشه پاک می‌شوند.</p>
									<label style="color:#8a1f1f"><input type="checkbox" id="atlas-uninstall-confirm" name="<?php echo esc_attr( self::OPTION ); ?>[uninstall_confirm]" value="1"> بله، مطمئنم و تأیید نهایی می‌کنم که داده‌ها حذف شوند.</label>
								</div>
								<p class="description">برای فعال‌شدن این گزینه، باید هم این تیک و هم تأیید نهایی زده شود؛ در غیر این صورت داده‌ها هنگام حذف پلاگین حفظ می‌مانند.</p>
							</td>
						</tr>
					</table>

					<script>
					( function () {
						var main = document.getElementById( 'atlas-uninstall-data' );
						var box = document.getElementById( 'atlas-uninstall-confirm-box' );
						var confirmBox = document.getElementById( 'atlas-uninstall-confirm' );
						if ( ! main ) { return; }
						function sync() {
							box.style.display = main.checked ? 'block' : 'none';
							if ( ! main.checked ) { confirmBox.checked = false; }
						}
						main.addEventListener( 'change', sync );
						sync();
						// تأیید دوباره هنگام ذخیره
						main.form.addEventListener( 'submit', function ( e ) {
							if ( main.checked && ! confirmBox.checked ) {
								e.preventDefault();
								alert( 'برای حذف داده‌ها باید «تأیید نهایی» را هم بزنید، وگرنه داده‌ها حفظ می‌شوند.' );
								confirmBox.focus();
								return;
							}
							if ( main.checked && confirmBox.checked ) {
								if ( ! window.confirm( 'مطمئنید؟ با حذف پلاگین همه نمایندگی‌ها و استان‌ها برای همیشه پاک خواهند شد.' ) ) {
									e.preventDefault();
								}
							}
						} );
					} )();
					</script>
					</div>

					<?php submit_button( 'ذخیره تنظیمات' ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_guide() {
		$archive_url = get_post_type_archive_link( Agency_Atlas_Post_Type::POST_TYPE );
		?>
		<div class="atlas-card-box atlas-guide">
			<h2>شروع سریع</h2>
			<ol>
				<li>از منوی <strong>اطلس نمایندگی‌ها ← افزودن نمایندگی</strong> یک نمایندگی بسازید: عنوان (نام نمایندگی)، توضیحات دلخواه با ادیتور بلوکی، لوگو از «تصویر شاخص».</li>
				<li>در باکس <strong>استان‌ها</strong> استان نمایندگی را تیک بزنید (۳۱ استان ایران از قبل ساخته شده‌اند).</li>
				<li>در متاباکس <strong>اطلاعات تماس</strong>، شهر، مدیر، تلفن، موبایل، آدرس و در صورت نیاز لینک مسیریابی را وارد کنید.</li>
				<li>شورت‌کد را در هر برگه یا نوشته قرار دهید.</li>
			</ol>

			<h2>شورت‌کد</h2>
			<p><code dir="ltr">[agency_atlas]</code> — دقیقاً همان چیدمان صفحه آرشیو: لیست نمایندگی‌ها (راست) + نقشه‌ی چسبان (چپ) در دسکتاپ. <code dir="ltr">[agency_atlas_archive]</code> هم دقیقاً همین خروجی را دارد.</p>
			<table class="widefat striped" style="max-width:820px">
				<thead><tr><th>پارامتر</th><th>مقادیر</th><th>توضیح</th></tr></thead>
				<tbody>
					<tr><td><code dir="ltr">list</code></td><td><code dir="ltr">1</code> | <code dir="ltr">0</code></td><td>با <code dir="ltr">0</code> فقط نقشه‌ی تعاملی (بدون لیست کناری) نمایش داده می‌شود</td></tr>
					<tr><td><code dir="ltr">show_map</code></td><td><code dir="ltr">1</code> | <code dir="ltr">0</code></td><td>با <code dir="ltr">0</code> فقط لیست نمایندگی‌ها (بدون نقشه)</td></tr>
					<tr><td><code dir="ltr">display</code></td><td><code dir="ltr">inline</code> | <code dir="ltr">modal</code></td><td>در حالت «فقط نقشه» (<code dir="ltr">list="0"</code>): پنل زیر نقشه یا پنجره مودال</td></tr>
					<tr><td><code dir="ltr">chips</code></td><td><code dir="ltr">1</code> | <code dir="ltr">0</code></td><td>نمایش/مخفی‌کردن دکمه‌های استان‌ها زیر نقشه</td></tr>
					<tr><td><code dir="ltr">map</code></td><td><code dir="ltr">iran</code></td><td>شناسه نقشه (نقشه‌های بعدی که اضافه شوند با همین پارامتر انتخاب می‌شوند)</td></tr>
				</tbody>
			</table>
			<p>مثال فقط نقشه با مودال: <code dir="ltr">[agency_atlas list="0" display="modal"]</code></p>

			<h2>صفحه آرشیو — دو روش</h2>
			<p><strong>روش ۱ (پیش‌فرض):</strong> آرشیو خودکار در آدرس <a href="<?php echo esc_url( $archive_url ); ?>" target="_blank"><?php echo esc_html( $archive_url ); ?></a> — عنوان و محتوای بالای آن از تب «تنظیمات» قابل ویرایش است.</p>
			<p><strong>روش ۲ (برگه اختصاصی — کنترل کامل):</strong> یک برگه بسازید، هر محتوایی خواستید با ادیتور بلوکی بنویسید (متن، تصویر، بلوک «HTML سفارشی» برای کدنویسی و ...)، و هر جای برگه شورت‌کد <code dir="ltr">[agency_atlas_archive]</code> را بگذارید. سپس در تب «تنظیمات ← برگه اختصاصی آرشیو» همان برگه را انتخاب کنید. از این به بعد آدرس آرشیو به این برگه منتقل می‌شود و همه لینک‌های «همه نمایندگی‌ها» به آن اشاره می‌کنند.</p>
			<p>هر نمایندگی صفحه اختصاصی خودش را هم دارد.</p>
			<p class="description">اگر بعد از فعال‌سازی، صفحه آرشیو ۴۰۴ داد، یک‌بار «تنظیمات ← پیوندهای یکتا» را ذخیره کنید.</p>

			<h2>سایت چندزبانه (WPML)</h2>
			<p>نمایندگی‌ها و استان‌ها مثل هر محتوای دیگر در WPML قابل ترجمه‌اند؛ در هر زبان، نقشه و لیست به‌صورت خودکار فقط نمایندگی‌های همان زبان را نشان می‌دهند.</p>
			<p>دربارهٔ «برگهٔ اختصاصی آرشیو»: کافی است همان برگه را در WPML به زبان‌های دیگر ترجمه کنید (و شورت‌کد <code dir="ltr">[agency_atlas_archive]</code> در ترجمه هم باشد). پلاگین در هر زبان به‌صورت خودکار نسخهٔ ترجمه‌شدهٔ همان برگه را به‌عنوان آرشیو می‌شناسد و ریدایرکت/لینک‌ها را به آن می‌بندد. اگر برگه در زبانی ترجمه نشده باشد، همان نسخهٔ اصلی استفاده می‌شود.</p>

			<h2>افزودن نقشه جدید (توسعه‌دهنده)</h2>
			<p>نقشه‌ها از طریق فیلتر <code dir="ltr">agency_atlas_maps</code> ثبت می‌شوند. فایل SVG باید برای هر منطقه یک <code dir="ltr">&lt;path data-region="key"&gt;</code> داشته باشد و برای هر منطقه یک ترم استان با متای <code dir="ltr">atlas_map_id</code> و <code dir="ltr">atlas_region_key</code> بسازید. نمونه کامل در ابتدای فایل <code dir="ltr">includes/class-atlas-maps.php</code> آمده است.</p>
		</div>
		<?php
	}
}
