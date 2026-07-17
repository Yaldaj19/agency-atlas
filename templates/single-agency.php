<?php
/**
 * قالب صفحه اختصاصی نمایندگی.
 * برای override، فایل single-atlas_agency.php را در قالب سایت بسازید.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );
?>

<div class="atlas-page atlas-single" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
	<div class="container atlas-container">
		<?php
		while ( have_posts() ) :
			the_post();

			$atlas_id       = get_the_ID();
			$atlas_terms    = get_the_terms( $atlas_id, Agency_Atlas_Post_Type::TAXONOMY );
			$atlas_region   = ( $atlas_terms && ! is_wp_error( $atlas_terms ) ) ? $atlas_terms : array();
			$atlas_city     = get_post_meta( $atlas_id, '_atlas_city', true );
			$atlas_manager  = get_post_meta( $atlas_id, '_atlas_manager', true );
			$atlas_phones   = Agency_Atlas_Frontend::get_phones( $atlas_id );
			$atlas_address  = Agency_Atlas_Frontend::get_addresses( $atlas_id );
			$atlas_socials  = Agency_Atlas_Frontend::get_socials( $atlas_id );
			$atlas_archive  = Agency_Atlas_Frontend::directory_url();
			$atlas_has_desc = '' !== trim( wp_strip_all_tags( get_the_content() ) );
			?>

			<?php
			echo Agency_Atlas_Frontend::breadcrumb_html(
				get_the_title(),
				array( agency_atlas_i18n( 'نمایندگی‌ها' ) => $atlas_archive )
			); // phpcs:ignore -- خروجی تابع escape شده است.
			?>

			<article <?php post_class( 'atlas-single-article' ); ?>>
				<header class="atlas-single-hero<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>">
					<div class="atlas-single-hero-media">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'class' => 'atlas-single-logo' ) ); ?>
						<?php else : ?>
							<span class="atlas-single-logo atlas-single-logo-empty" aria-hidden="true"><?php echo Agency_Atlas_Frontend::render_icon( 'pin' ); // phpcs:ignore ?></span>
						<?php endif; ?>
					</div>
					<div class="atlas-single-hero-text">
						<h1 class="atlas-single-title"><?php the_title(); ?></h1>
						<div class="atlas-single-badges">
							<?php foreach ( $atlas_region as $atlas_term ) : ?>
								<a class="atlas-chip" href="<?php echo esc_url( get_term_link( $atlas_term ) ); ?>"><?php echo esc_html( $atlas_term->name ); ?></a>
							<?php endforeach; ?>
							<?php if ( $atlas_city ) : ?>
								<span class="atlas-chip atlas-chip-plain"><?php echo esc_html( $atlas_city ); ?></span>
							<?php endif; ?>
						</div>
						<?php if ( $atlas_phones ) : ?>
							<div class="atlas-single-cta">
								<a class="atlas-btn atlas-btn-lg" href="<?php echo esc_url( Agency_Atlas_Frontend::tel_href( $atlas_phones[0] ) ); ?>"><?php echo esc_html( agency_atlas_i18n( 'تماس با نمایندگی' ) ); ?></a>
							</div>
						<?php endif; ?>
						<?php echo Agency_Atlas_Frontend::render_socials( $atlas_socials ); // phpcs:ignore -- خروجی تابع escape شده است. ?>
					</div>
				</header>

				<div class="atlas-single-grid">
					<div class="atlas-single-main">
						<?php if ( $atlas_has_desc ) : ?>
							<div class="atlas-single-body">
								<?php the_content(); ?>
							</div>
						<?php endif; ?>

						<?php if ( $atlas_address ) : ?>
							<div class="atlas-single-address">
								<h2 class="atlas-single-h2"><span class="atlas-dir-pin"><?php echo Agency_Atlas_Frontend::render_icon( 'pin' ); // phpcs:ignore ?></span> <?php echo esc_html( count( $atlas_address ) > 1 ? agency_atlas_i18n( 'آدرس‌ها' ) : agency_atlas_i18n( 'آدرس' ) ); ?></h2>
								<ul class="atlas-address-list">
									<?php foreach ( $atlas_address as $atlas_ad ) : ?>
										<li>
											<p><?php echo esc_html( $atlas_ad['text'] ); ?></p>
											<?php if ( ! empty( $atlas_ad['map_url'] ) ) : ?>
												<a class="atlas-btn atlas-btn-ghost" href="<?php echo esc_url( $atlas_ad['map_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( agency_atlas_i18n( 'نمایش روی نقشه' ) ); ?></a>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>

					<aside class="atlas-single-aside">
						<div class="atlas-info-card">
							<h2 class="atlas-info-card-title"><?php echo esc_html( agency_atlas_i18n( 'اطلاعات تماس' ) ); ?></h2>
							<ul class="atlas-card-info">
								<?php if ( $atlas_manager ) : ?>
									<li><?php echo Agency_Atlas_Frontend::render_icon( 'user' ); // phpcs:ignore ?><span class="atlas-info-label"><?php echo esc_html( agency_atlas_i18n( 'مدیر:' ) ); ?></span> <?php echo esc_html( $atlas_manager ); ?></li>
								<?php endif; ?>
								<?php foreach ( $atlas_phones as $atlas_ph ) : ?>
									<li><?php echo Agency_Atlas_Frontend::render_icon( 'phone' ); // phpcs:ignore ?><a href="<?php echo esc_url( Agency_Atlas_Frontend::tel_href( $atlas_ph ) ); ?>" dir="ltr"><?php echo esc_html( $atlas_ph ); ?></a></li>
								<?php endforeach; ?>
							</ul>
							<?php if ( $atlas_socials ) : ?>
								<div class="atlas-info-card-socials">
									<?php echo Agency_Atlas_Frontend::render_socials( $atlas_socials ); // phpcs:ignore -- خروجی تابع escape شده است. ?>
								</div>
							<?php endif; ?>
							<a class="atlas-btn atlas-btn-ghost atlas-back-link" href="<?php echo esc_url( $atlas_archive ); ?>">← <?php echo esc_html( agency_atlas_i18n( 'بازگشت به همه نمایندگی‌ها' ) ); ?></a>
						</div>
					</aside>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</div>

<?php
get_footer( 'shop' );
