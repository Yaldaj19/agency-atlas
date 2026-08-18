/* پنل «بازارهای صادراتی» — کالر پیکر + سلکت‌باکس چندگانه با جستجو و شمارنده */
(function ($) {
	'use strict';

	$(function () {
		// رنگ‌پیکرها
		if ($.fn.wpColorPicker) {
			$('.atlas-mk-color').wpColorPicker();
		}

		var $multi = $('[data-mk-multi]');
		if (!$multi.length) return;

		var $search = $multi.find('.atlas-mk-multi__search');
		var $items = $multi.find('.atlas-mk-multi__item');
		var $count = $('[data-mk-count]');

		function refreshCount() {
			var n = $multi.find('input[type=checkbox]:checked').length;
			$count.text(n.toLocaleString('fa-IR'));
		}

		// جستجو
		$search.on('input', function () {
			var q = ($(this).val() || '').toString().trim().toLowerCase();
			$items.each(function () {
				var hay = ($(this).attr('data-search') || '');
				$(this).toggle(q === '' || hay.indexOf(q) !== -1);
			});
		});

		// تیک زدن → استایل و شمارش
		$multi.on('change', 'input[type=checkbox]', function () {
			$(this).closest('.atlas-mk-multi__item').toggleClass('is-on', this.checked);
			refreshCount();
		});

		refreshCount();
	});
})(jQuery);
