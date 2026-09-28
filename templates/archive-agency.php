<?php
/**
 * قالب آرشیو نمایندگی‌ها + آرشیو استان.
 * برای override، فایل archive-atlas_agency.php را در قالب سایت بسازید.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );

$atlas_settings = agency_atlas_get_settings();
$atlas_is_tax   = is_tax( Agency_Atlas_Post_Type::TAXONOMY );
?>

<div class="atlas-page<?php echo $atlas_is_tax ? ' atlas-single' : ''; ?><?php echo esc_attr( agency_atlas_skin_class() ); ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
	<div class="container atlas-container">
		<?php if ( $atlas_is_tax ) : ?>
			<?php
			$atlas_term_obj  = get_queried_object();
			$atlas_term_name = $atlas_term_obj ? $atlas_term_obj->name : single_term_title( '', false );
			$atlas_tax_title = agency_atlas_i18n( 'استان' ) . ' ' . $atlas_term_name;
			$atlas_tax_count = ( $atlas_term_obj && isset( $atlas_term_obj->count ) ) ? (int) $atlas_term_obj->count : 0;
			// مسیرراهنمای هیرو: دقیقاً مثلِ آرشیو نمایندگی از بردکرامبِ قالب (متنِ ساده، بدون کپسول).
			ob_start();
			if ( function_exists( 'the_breadcrumb' ) ) { the_breadcrumb(); }
			$atlas_tax_crumb_theme = trim( (string) ob_get_clean() );
			// بخشِ عنوان مثلِ آرشیوها (هیروی قالب) — مگر پوستهٔ default (بیتک) که همان هدرِ سادهٔ قبلی می‌ماند.
			$atlas_tax_hero  = Agency_Atlas_Frontend::hero_html( $atlas_tax_title, $atlas_tax_crumb_theme, $atlas_tax_count, agency_atlas_i18n( 'نمایندگی در این استان' ) );
			// بردکرامبِ پلاگین برای حالتِ پیش‌فرض (بیتک) — دست‌نخورده.
			$atlas_tax_crumb = Agency_Atlas_Frontend::breadcrumb_html(
				$atlas_term_name,
				array( agency_atlas_i18n( 'نمایندگی‌ها' ) => Agency_Atlas_Frontend::directory_url() )
			);
			?>
			<?php if ( '' !== $atlas_tax_hero ) : ?>
				<?php echo $atlas_tax_hero; // phpcs:ignore -- خروجی escape شده است. ?>
				<?php if ( term_description() ) : ?>
					<div class="atlas-archive-content"><?php echo wp_kses_post( term_description() ); ?></div>
				<?php endif; ?>
			<?php else : ?>
				<?php echo $atlas_tax_crumb; // phpcs:ignore -- خروجی escape شده است. ?>
				<header class="atlas-archive-header">
					<h1 class="atlas-archive-title"><?php echo esc_html( $atlas_tax_title ); ?></h1>
					<?php if ( term_description() ) : ?>
						<div class="atlas-archive-content"><?php echo wp_kses_post( term_description() ); ?></div>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<div class="atlas-tax-cards <?php echo esc_attr( Agency_Atlas_Frontend::card_style_class() ); ?>" style="--tax-cols:<?php echo esc_attr( $atlas_settings['tax_cols'] ); ?>">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php echo Agency_Atlas_Frontend::render_tax_card( get_post() ); // phpcs:ignore -- خروجی تابع escape شده است. ?>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<p class="atlas-hint"><?php echo esc_html( agency_atlas_i18n( 'هنوز نمایندگی‌ای در این استان ثبت نشده است.' ) ); ?></p>
			<?php endif; ?>

		<?php else : ?>
			<?php echo Agency_Atlas_Frontend::archive_header_html( post_type_archive_title( '', false ) ); // phpcs:ignore -- خروجی تابع escape شده است. ?>
			<?php
			if ( '1' === $atlas_settings['archive_show_map'] ) {
				if ( 'filter' === $atlas_settings['archive_layout'] ) {
					echo Agency_Atlas_Frontend::render_locator_filter( 'iran', $atlas_settings['display'] ); // phpcs:ignore -- خروجی تابع escape شده است.
				} else {
					echo Agency_Atlas_Frontend::render_locator( 'iran', $atlas_settings['display'] ); // phpcs:ignore -- خروجی تابع escape شده است.
				}
			} else {
				echo Agency_Atlas_Frontend::render_directory( 'iran' ); // phpcs:ignore -- خروجی تابع escape شده است.
			}
			?>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer( 'shop' );
