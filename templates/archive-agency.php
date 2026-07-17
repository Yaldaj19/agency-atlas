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

<div class="atlas-page" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
	<div class="container atlas-container">
		<?php if ( $atlas_is_tax ) : ?>
			<?php // مسیر راهنما (breadcrumb) اینجا رندر نمی‌شود؛ نمایش آن بر عهدهٔ قالب/برگه است. ?>
			<header class="atlas-archive-header">
				<h1 class="atlas-archive-title"><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
				<?php if ( term_description() ) : ?>
					<div class="atlas-archive-content"><?php echo wp_kses_post( term_description() ); ?></div>
				<?php endif; ?>
			</header>
		<?php else : ?>
			<?php echo Agency_Atlas_Frontend::archive_header_html( post_type_archive_title( '', false ) ); // phpcs:ignore -- خروجی تابع escape شده است. ?>
		<?php endif; ?>

		<?php if ( $atlas_is_tax ) : ?>
			<?php if ( have_posts() ) : ?>
				<div class="atlas-cards atlas-cards-wide">
					<?php
					while ( have_posts() ) {
						the_post();
						echo Agency_Atlas_Frontend::render_card( get_post() ); // phpcs:ignore -- خروجی تابع escape شده است.
					}
					?>
				</div>
			<?php else : ?>
				<p class="atlas-hint"><?php echo esc_html( agency_atlas_i18n( 'هنوز نمایندگی‌ای در این استان ثبت نشده است.' ) ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<?php
			if ( '1' === $atlas_settings['archive_show_map'] ) {
				echo Agency_Atlas_Frontend::render_locator( 'iran', $atlas_settings['display'] ); // phpcs:ignore -- خروجی تابع escape شده است.
			} else {
				echo Agency_Atlas_Frontend::render_directory( 'iran' ); // phpcs:ignore -- خروجی تابع escape شده است.
			}
			?>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer( 'shop' );
