<?php
/**
 * Plugin Name:       Agency Atlas — اطلس نمایندگی‌ها
 * Plugin URI:        https://yaldajahanshahi.ir
 * Description:       نمایش نمایندگی‌ها روی نقشه SVG تعاملی ایران — با حالت پنل و مودال، آرشیو اختصاصی با محتوای قابل تنظیم، ادیتور پیشرفته و ساختار قابل توسعه برای افزودن نقشه‌های دیگر.
 * Version:           1.0.0
 * Author:            Yalda Jahanshahi
 * Author URI:        https://yaldajahanshahi.ir
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       agency-atlas
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGENCY_ATLAS_VERSION', '1.0.0' );
define( 'AGENCY_ATLAS_FILE', __FILE__ );
define( 'AGENCY_ATLAS_DIR', plugin_dir_path( __FILE__ ) );
define( 'AGENCY_ATLAS_URL', plugin_dir_url( __FILE__ ) );

require_once AGENCY_ATLAS_DIR . 'includes/class-atlas-maps.php';
require_once AGENCY_ATLAS_DIR . 'includes/class-atlas-post-type.php';
require_once AGENCY_ATLAS_DIR . 'includes/class-atlas-meta.php';
require_once AGENCY_ATLAS_DIR . 'includes/class-atlas-settings.php';
require_once AGENCY_ATLAS_DIR . 'includes/class-atlas-frontend.php';
require_once AGENCY_ATLAS_DIR . 'includes/class-atlas-schema.php';
require_once AGENCY_ATLAS_DIR . 'includes/class-atlas-activator.php';

Agency_Atlas_Post_Type::init();
Agency_Atlas_Meta::init();
Agency_Atlas_Settings::init();
Agency_Atlas_Frontend::init();
Agency_Atlas_Schema::init();

register_activation_hook( __FILE__, array( 'Agency_Atlas_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/**
 * ترجمهٔ رشته‌های ثابتِ فرانت‌اند از طریق WPML String Translation (context: agency-atlas).
 * روی سایت‌های بدون WPML همان متن اصلی برمی‌گردد (بدون وابستگی سخت).
 * name پیش‌فرض = خود متن، تا با ثبت رشته در WPML هماهنگ بماند.
 */
function agency_atlas_i18n( $text, $name = '' ) {
	if ( '' === $name ) {
		$name = $text;
	}
	if ( has_filter( 'wpml_translate_single_string' ) ) {
		do_action( 'wpml_register_single_string', 'agency-atlas', $name, $text );
		return (string) apply_filters( 'wpml_translate_single_string', $text, 'agency-atlas', $name );
	}
	return $text;
}

/**
 * دسترسی سریع به تنظیمات پلاگین با مقادیر پیش‌فرض.
 */
function agency_atlas_get_settings() {
	$defaults = array(
		'map_fill'         => '#dfe6ec',
		'map_hover'        => '#ffcc00',
		'map_sea'          => '#bfdbef',
		'map_dot'          => '#d32f2f',
		'display'          => 'inline',
		'card_style'       => 'glassmorphism',
		'card_bg'          => '',
		'card_text'        => '',
		'card_border'      => '',
		'archive_page_id'  => 0,
		'archive_title'    => 'نمایندگی‌های ما',
		'hide_archive_title' => '',
		'archive_content'  => '',
		'archive_show_map' => '1',
		'archive_layout'   => 'filter',
		'uninstall_data'   => '',
	);
	$saved = get_option( 'agency_atlas_settings', array() );

	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/**
 * وقتی تنظیمات اطلس ذخیره می‌شود، کش خروجی ویجت‌های قالب (info_description که شورت‌کد اطلس
 * را embed کرده) و کش صفحه‌ی LiteSpeed را پاک می‌کنیم؛ وگرنه تغییر «حالت نمایش» (پنل/مودال)
 * یا رنگ‌ها به‌خاطر HTML کش‌شده روی صفحه اعمال نمی‌شود.
 */
function agency_atlas_flush_related_caches() {
	if ( function_exists( 'clear_widget_cache' ) ) {
		clear_widget_cache( 'info_description' );
	}
	// LiteSpeed: پاک‌کردن کش کامل صفحه‌ها تا شورت‌کد دوباره رندر شود.
	do_action( 'litespeed_purge_all' );
}
add_action( 'update_option_agency_atlas_settings', 'agency_atlas_flush_related_caches' );
add_action( 'add_option_agency_atlas_settings', 'agency_atlas_flush_related_caches' );
