<?php
/**
 * متاباکس اطلاعات نمایندگی — شماره‌ها، آدرس‌ها و شبکه‌های اجتماعی به‌صورت تکرارشونده.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Atlas_Meta {

	const NONCE = 'agency_atlas_meta_nonce';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_box' ) );
		add_action( 'save_post_' . Agency_Atlas_Post_Type::POST_TYPE, array( __CLASS__, 'save' ) );
	}

	public static function add_box() {
		add_meta_box(
			'agency_atlas_contact',
			'اطلاعات نمایندگی',
			array( __CLASS__, 'render' ),
			Agency_Atlas_Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );

		$city      = get_post_meta( $post->ID, '_atlas_city', true );
		$manager   = get_post_meta( $post->ID, '_atlas_manager', true );
		$phones    = Agency_Atlas_Frontend::get_phones( $post->ID );
		$addresses = Agency_Atlas_Frontend::get_addresses( $post->ID );
		$socials   = Agency_Atlas_Frontend::get_socials( $post->ID );
		$networks  = Agency_Atlas_Frontend::social_networks();

		if ( ! $phones ) {
			$phones = array( '' );
		}
		if ( ! $addresses ) {
			$addresses = array( array( 'text' => '', 'map_url' => '' ) );
		}
		?>
		<div class="atlas-meta">
			<div class="atlas-meta-row">
				<p class="atlas-meta-field">
					<label for="atlas_city"><strong>شهر</strong></label>
					<input class="widefat" type="text" id="atlas_city" name="_atlas_city" value="<?php echo esc_attr( $city ); ?>">
				</p>
				<p class="atlas-meta-field">
					<label for="atlas_manager"><strong>نام مدیر</strong></label>
					<input class="widefat" type="text" id="atlas_manager" name="_atlas_manager" value="<?php echo esc_attr( $manager ); ?>">
				</p>
			</div>

			<!-- شماره‌های تماس -->
			<div class="atlas-sec">
				<h4>شماره‌های تماس</h4>
				<div class="atlas-rep" data-rep="phones">
					<?php foreach ( $phones as $ph ) : ?>
						<div class="atlas-rep-item">
							<input class="widefat" type="text" dir="ltr" name="_atlas_phones[]" value="<?php echo esc_attr( $ph ); ?>" placeholder="مثلاً 02177889900 یا 09121234567">
							<button type="button" class="button atlas-rep-del" aria-label="حذف">×</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button atlas-rep-add" data-rep="phones">+ افزودن شماره</button>
				<template class="atlas-tpl-phones">
					<div class="atlas-rep-item">
						<input class="widefat" type="text" dir="ltr" name="_atlas_phones[]" value="" placeholder="مثلاً 02177889900 یا 09121234567">
						<button type="button" class="button atlas-rep-del" aria-label="حذف">×</button>
					</div>
				</template>
			</div>

			<!-- آدرس‌ها -->
			<div class="atlas-sec">
				<h4>آدرس‌ها</h4>
				<div class="atlas-rep" data-rep="addresses">
					<?php foreach ( $addresses as $i => $ad ) : ?>
						<div class="atlas-rep-item atlas-rep-block">
							<textarea class="widefat" rows="2" name="_atlas_addresses[<?php echo (int) $i; ?>][text]" placeholder="آدرس کامل"><?php echo esc_textarea( $ad['text'] ); ?></textarea>
							<input class="widefat" type="url" dir="ltr" name="_atlas_addresses[<?php echo (int) $i; ?>][map_url]" value="<?php echo esc_attr( $ad['map_url'] ); ?>" placeholder="لینک مسیریابی (نشان/گوگل‌مپ) — اختیاری">
							<button type="button" class="button atlas-rep-del" aria-label="حذف">× حذف این آدرس</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button atlas-rep-add" data-rep="addresses">+ افزودن آدرس</button>
				<template class="atlas-tpl-addresses">
					<div class="atlas-rep-item atlas-rep-block">
						<textarea class="widefat" rows="2" name="_atlas_addresses[__i__][text]" placeholder="آدرس کامل"></textarea>
						<input class="widefat" type="url" dir="ltr" name="_atlas_addresses[__i__][map_url]" value="" placeholder="لینک مسیریابی (نشان/گوگل‌مپ) — اختیاری">
						<button type="button" class="button atlas-rep-del" aria-label="حذف">× حذف این آدرس</button>
					</div>
				</template>
			</div>

			<!-- شبکه‌های اجتماعی -->
			<div class="atlas-sec">
				<h4>شبکه‌های اجتماعی و پیام‌رسان‌ها</h4>
				<div class="atlas-rep" data-rep="socials">
					<?php foreach ( $socials as $i => $s ) : ?>
						<div class="atlas-rep-item">
							<select name="_atlas_socials[<?php echo (int) $i; ?>][network]">
								<?php foreach ( $networks as $key => $n ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['network'], $key ); ?>><?php echo esc_html( $n['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<input class="widefat" type="text" dir="ltr" name="_atlas_socials[<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr( $s['url'] ); ?>" placeholder="آدرس لینک یا آیدی (برای ایمیل: نشانی ایمیل)">
							<button type="button" class="button atlas-rep-del" aria-label="حذف">×</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button atlas-rep-add" data-rep="socials">+ افزودن شبکه اجتماعی</button>
				<template class="atlas-tpl-socials">
					<div class="atlas-rep-item">
						<select name="_atlas_socials[__i__][network]">
							<?php foreach ( $networks as $key => $n ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $n['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<input class="widefat" type="text" dir="ltr" name="_atlas_socials[__i__][url]" value="" placeholder="آدرس لینک یا آیدی (برای ایمیل: نشانی ایمیل)">
						<button type="button" class="button atlas-rep-del" aria-label="حذف">×</button>
					</div>
				</template>
			</div>

			<p class="description">لوگوی نمایندگی را از «تصویر شاخص» و استان را از باکس «استان‌ها» تنظیم کنید.</p>
		</div>

		<style>
			.atlas-meta-row{display:grid;grid-template-columns:1fr 1fr;gap:0 20px}
			.atlas-meta-field label{display:block;margin-bottom:4px}
			.atlas-sec{margin-top:20px;padding-top:16px;border-top:1px solid #e2e4e7}
			.atlas-sec h4{margin:0 0 10px;font-size:14px}
			.atlas-rep-item{display:flex;align-items:flex-start;gap:8px;margin-bottom:8px}
			.atlas-rep-item.atlas-rep-block{flex-direction:column;background:#f6f7f9;border:1px solid #e2e4e7;border-radius:8px;padding:10px}
			.atlas-rep-item.atlas-rep-block .button{align-self:flex-start}
			.atlas-rep-item select{max-width:160px}
			.atlas-rep-del{color:#b32d2e}
		</style>
		<script>
		( function () {
			var box = document.getElementById( 'agency_atlas_contact' );
			if ( ! box ) { return; }
			var counters = { addresses: 1000, socials: 1000 };
			box.addEventListener( 'click', function ( e ) {
				var add = e.target.closest( '.atlas-rep-add' );
				if ( add ) {
					var key = add.dataset.rep;
					var tpl = box.querySelector( '.atlas-tpl-' + key );
					var wrap = box.querySelector( '.atlas-rep[data-rep="' + key + '"]' );
					if ( tpl && wrap ) {
						var html = tpl.innerHTML.replace( /__i__/g, counters[ key ] !== undefined ? counters[ key ]++ : '' );
						var div = document.createElement( 'div' );
						div.innerHTML = html.trim();
						wrap.appendChild( div.firstChild );
					}
					return;
				}
				var del = e.target.closest( '.atlas-rep-del' );
				if ( del ) {
					var item = del.closest( '.atlas-rep-item' );
					var container = del.closest( '.atlas-rep' );
					if ( item && container && container.querySelectorAll( '.atlas-rep-item' ).length > 1 ) {
						item.remove();
					} else if ( item ) {
						item.querySelectorAll( 'input, textarea' ).forEach( function ( f ) { f.value = ''; } );
					}
				}
			} );
		} )();
		</script>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// شهر و مدیر.
		update_post_meta( $post_id, '_atlas_city', sanitize_text_field( wp_unslash( $_POST['_atlas_city'] ?? '' ) ) );
		update_post_meta( $post_id, '_atlas_manager', sanitize_text_field( wp_unslash( $_POST['_atlas_manager'] ?? '' ) ) );

		// شماره‌ها.
		$phones = array();
		if ( isset( $_POST['_atlas_phones'] ) && is_array( $_POST['_atlas_phones'] ) ) {
			foreach ( wp_unslash( $_POST['_atlas_phones'] ) as $p ) {
				$p = sanitize_text_field( $p );
				if ( '' !== $p ) {
					$phones[] = $p;
				}
			}
		}
		self::save_array( $post_id, '_atlas_phones', $phones );

		// آدرس‌ها.
		$addresses = array();
		if ( isset( $_POST['_atlas_addresses'] ) && is_array( $_POST['_atlas_addresses'] ) ) {
			foreach ( wp_unslash( $_POST['_atlas_addresses'] ) as $ad ) {
				$text = sanitize_textarea_field( $ad['text'] ?? '' );
				$url  = esc_url_raw( $ad['map_url'] ?? '' );
				if ( '' !== $text || '' !== $url ) {
					$addresses[] = array( 'text' => $text, 'map_url' => $url );
				}
			}
		}
		self::save_array( $post_id, '_atlas_addresses', $addresses );

		// شبکه‌های اجتماعی.
		$networks = Agency_Atlas_Frontend::social_networks();
		$socials  = array();
		if ( isset( $_POST['_atlas_socials'] ) && is_array( $_POST['_atlas_socials'] ) ) {
			foreach ( wp_unslash( $_POST['_atlas_socials'] ) as $s ) {
				$net = sanitize_key( $s['network'] ?? '' );
				$raw = trim( $s['url'] ?? '' );
				if ( '' === $raw || ! isset( $networks[ $net ] ) ) {
					continue;
				}
				$url = ( 'email' === $net ) ? sanitize_email( $raw ) : esc_url_raw( $raw );
				if ( '' !== $url ) {
					$socials[] = array( 'network' => $net, 'url' => $url );
				}
			}
		}
		self::save_array( $post_id, '_atlas_socials', $socials );

		// پاک‌سازی کلیدهای تک‌مقداری قدیمی (پس از انتقال به آرایه‌ها).
		foreach ( array( '_atlas_phone', '_atlas_mobile', '_atlas_address', '_atlas_map_url', '_atlas_website' ) as $legacy ) {
			delete_post_meta( $post_id, $legacy );
		}
	}

	private static function save_array( $post_id, $key, $value ) {
		if ( $value ) {
			update_post_meta( $post_id, $key, $value );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}
}
