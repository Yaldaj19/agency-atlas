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

			// شماره تماس نماینده (برجسته): متای اختصاصی، خالی = شماره‌ی اول لیست.
			$atlas_rep = trim( (string) get_post_meta( $atlas_id, '_atlas_rep_phone', true ) );
			if ( '' === $atlas_rep && $atlas_phones ) {
				$atlas_rep = $atlas_phones[0];
			}

			// توضیح کوتاه هیرو: متای اختصاصی، خالی = چند کلمه از ابتدای متن اصلی.
			$atlas_excerpt = trim( (string) get_post_meta( $atlas_id, '_atlas_excerpt', true ) );
			if ( '' === $atlas_excerpt && $atlas_has_desc ) {
				$atlas_excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 28, '…' );
			}
			?>

			<?php
			echo Agency_Atlas_Frontend::breadcrumb_html(
				get_the_title(),
				array( agency_atlas_i18n( 'نمایندگی‌ها' ) => $atlas_archive )
			); // phpcs:ignore -- خروجی تابع escape شده است.
			?>

			<article <?php post_class( 'atlas-single-article' ); ?>>
				<header class="atlas-single-hero<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>">

					<div class="atlas-single-hero-text">
						<h1 class="atlas-single-title"><?php the_title(); ?></h1>

						<?php if ( $atlas_region || $atlas_city ) : ?>
							<div class="atlas-single-badges">
								<?php foreach ( $atlas_region as $atlas_term ) : ?>
									<a class="atlas-chip" href="<?php echo esc_url( get_term_link( $atlas_term ) ); ?>"><?php echo esc_html( $atlas_term->name ); ?></a>
								<?php endforeach; ?>
								<?php if ( $atlas_city ) : ?>
									<span class="atlas-chip atlas-chip-plain"><?php echo esc_html( $atlas_city ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="atlas-hero-info">
							<?php if ( $atlas_manager ) : ?>
								<div class="atlas-hero-row">
									<span class="atlas-hero-label"><?php echo Agency_Atlas_Frontend::render_icon( 'user' ); // phpcs:ignore ?><?php echo esc_html( agency_atlas_i18n( 'مدیر' ) ); ?></span>
									<span class="atlas-hero-val"><?php echo esc_html( $atlas_manager ); ?></span>
								</div>
							<?php endif; ?>

							<?php if ( $atlas_phones ) : ?>
								<div class="atlas-hero-row">
									<span class="atlas-hero-label"><?php echo Agency_Atlas_Frontend::render_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( agency_atlas_i18n( 'شماره‌های تماس' ) ); ?></span>
									<span class="atlas-hero-val">
										<?php foreach ( $atlas_phones as $atlas_ph ) : ?>
											<a href="<?php echo esc_url( Agency_Atlas_Frontend::tel_href( $atlas_ph ) ); ?>" dir="ltr"><?php echo esc_html( $atlas_ph ); ?></a>
										<?php endforeach; ?>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( $atlas_address ) : ?>
								<div class="atlas-hero-row">
									<span class="atlas-hero-label"><?php echo Agency_Atlas_Frontend::render_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( count( $atlas_address ) > 1 ? agency_atlas_i18n( 'آدرس‌ها' ) : agency_atlas_i18n( 'آدرس' ) ); ?></span>
									<span class="atlas-hero-val atlas-hero-val-block">
										<?php foreach ( $atlas_address as $atlas_ad ) : ?>
											<span class="atlas-hero-addr">
												<span><?php echo esc_html( $atlas_ad['text'] ); ?></span>
												<?php if ( ! empty( $atlas_ad['map_url'] ) ) : ?>
													<a href="<?php echo esc_url( $atlas_ad['map_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( agency_atlas_i18n( 'نمایش روی نقشه' ) ); ?></a>
												<?php endif; ?>
											</span>
										<?php endforeach; ?>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( $atlas_socials ) : ?>
								<div class="atlas-hero-row">
									<span class="atlas-hero-label"><?php echo Agency_Atlas_Frontend::render_icon( 'globe' ); // phpcs:ignore ?><?php echo esc_html( agency_atlas_i18n( 'شبکه‌های اجتماعی' ) ); ?></span>
									<span class="atlas-hero-val"><?php echo Agency_Atlas_Frontend::render_socials( $atlas_socials ); // phpcs:ignore -- خروجی تابع escape شده است. ?></span>
								</div>
							<?php endif; ?>
						</div>

						<?php if ( $atlas_excerpt ) : ?>
							<p class="atlas-hero-excerpt"><?php echo esc_html( $atlas_excerpt ); ?></p>
						<?php endif; ?>
					</div>

					<div class="atlas-single-hero-media">
						<?php if ( $atlas_rep ) : ?>
							<a class="atlas-hero-repcall" href="<?php echo esc_url( Agency_Atlas_Frontend::tel_href( $atlas_rep ) ); ?>">
								<span class="atlas-hero-repcall-icon"><?php echo Agency_Atlas_Frontend::render_icon( 'phone' ); // phpcs:ignore ?></span>
								<span class="atlas-hero-repcall-text">
									<span class="atlas-hero-repcall-label"><?php echo esc_html( agency_atlas_i18n( 'شماره تماس نماینده' ) ); ?></span>
									<span class="atlas-hero-repcall-num" dir="ltr"><?php echo esc_html( $atlas_rep ); ?></span>
								</span>
							</a>
						<?php endif; ?>

						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'class' => 'atlas-single-logo' ) ); ?>
						<?php else : ?>
							<span class="atlas-single-logo atlas-single-logo-empty" aria-hidden="true"><?php echo Agency_Atlas_Frontend::render_icon( 'pin' ); // phpcs:ignore ?></span>
						<?php endif; ?>
					</div>
				</header>

				<?php if ( $atlas_has_desc ) : ?>
					<section class="atlas-single-content">
						<?php the_content(); ?>
					</section>
				<?php endif; ?>

				<a class="atlas-btn atlas-btn-ghost atlas-single-back" href="<?php echo esc_url( $atlas_archive ); ?>">← <?php echo esc_html( agency_atlas_i18n( 'بازگشت به همه نمایندگی‌ها' ) ); ?></a>
			</article>
		<?php endwhile; ?>
	</div>
</div>

<?php
get_footer( 'shop' );
