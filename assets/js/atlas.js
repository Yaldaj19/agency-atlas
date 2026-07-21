/**
 * Agency Atlas — نقشه تعاملی نمایندگی‌ها (vanilla JS)
 */
(function () {
	'use strict';

	var SVG_NS = 'http://www.w3.org/2000/svg';
	var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* شکل پین لوکیشن — نوکِ پین پایین‌مرکز (12,24) */
	var PIN_PATH = 'M12 0C6.5 0 2 4.5 2 10c0 7 10 14 10 14s10-7 10-14C22 4.5 17.5 0 12 0z';

	function initAtlas(wrap) {
		var regions = {};
		try {
			regions = JSON.parse(wrap.dataset.regions || '{}');
		} catch (e) { /* داده نامعتبر */ }

		var display = wrap.dataset.display || 'inline';
		var mode = wrap.dataset.mode || 'standalone';
		var svg = wrap.querySelector('svg.atlas-svg');
		var mapArea = wrap.querySelector('.atlas-map-area');
		var tooltip = wrap.querySelector('.atlas-tooltip');
		var panel = wrap.querySelector('.atlas-panel');
		var modal = wrap.querySelector('.atlas-modal');
		var locator = wrap.closest('.atlas-locator');
		var filterWrap = wrap.closest('.atlas-locator-filter');
		var lastFocus = null;

		if (!svg) {
			return;
		}

		/* نشانه‌گذاری استان‌های دارای نمایندگی + پین لوکیشن */
		Object.keys(regions).forEach(function (key) {
			var path = svg.querySelector('.atlas-region[data-region="' + key + '"]');
			if (!path) {
				return;
			}
			var info = regions[key];
			path.setAttribute('aria-label', info.name + (info.count > 0 ? '، ' + info.count + ' نمایندگی' : '، بدون نمایندگی'));

			if (info.count > 0) {
				path.classList.add('atlas-has');
				addPin(path, key);
			} else {
				path.setAttribute('tabindex', '-1');
			}
		});

		function addPin(path, key) {
			var box = path.getBBox();
			var cx = box.x + box.width / 2;
			var cy = box.y + box.height / 2;
			var s = 1.5; // مقیاس پین در مختصات نقشه

			var g = document.createElementNS(SVG_NS, 'g');
			g.setAttribute('class', 'atlas-pin');
			g.setAttribute('data-region', key);
			g.setAttribute('transform', 'translate(' + (cx - 12 * s) + ',' + (cy - 24 * s) + ') scale(' + s + ')');

			// هاله‌های تپشی برای استان فعال (دو حلقه با تأخیر تا پیوسته دیده شود)
			var halo1 = document.createElementNS(SVG_NS, 'circle');
			halo1.setAttribute('cx', '12');
			halo1.setAttribute('cy', '10');
			halo1.setAttribute('r', '6');
			halo1.setAttribute('class', 'atlas-pin-halo');
			var halo2 = document.createElementNS(SVG_NS, 'circle');
			halo2.setAttribute('cx', '12');
			halo2.setAttribute('cy', '10');
			halo2.setAttribute('r', '6');
			halo2.setAttribute('class', 'atlas-pin-halo atlas-pin-halo-2');
			g.appendChild(halo1);
			g.appendChild(halo2);

			var inner = document.createElementNS(SVG_NS, 'g');
			inner.setAttribute('class', 'atlas-pin-bob');

			var pin = document.createElementNS(SVG_NS, 'path');
			pin.setAttribute('d', PIN_PATH);
			pin.setAttribute('class', 'atlas-pin-body');

			var hole = document.createElementNS(SVG_NS, 'circle');
			hole.setAttribute('cx', '12');
			hole.setAttribute('cy', '10');
			hole.setAttribute('r', '3.6');
			hole.setAttribute('class', 'atlas-pin-hole');

			inner.appendChild(pin);
			inner.appendChild(hole);
			g.appendChild(inner);
			svg.appendChild(g);
		}

		/* تولتیپ */
		function showTooltip(evt, key) {
			var info = regions[key];
			if (!info || !tooltip) {
				return;
			}
			tooltip.textContent = info.name + (info.count > 0 ? ' — ' + info.count + ' نمایندگی' : '');
			tooltip.classList.add('atlas-tooltip-on');
			moveTooltip(evt);
		}

		function moveTooltip(evt) {
			var rect = mapArea.getBoundingClientRect();
			var x = evt.clientX - rect.left;
			var y = evt.clientY - rect.top;
			// tooltip وسط‌چین بالای نشانگر؛ داخل محدوده نقشه محدود می‌شود تا متن بیرون نزند.
			var half = tooltip.offsetWidth / 2;
			x = Math.max(half + 4, Math.min(rect.width - half - 4, x));
			tooltip.style.left = x + 'px';
			tooltip.style.top = (y - 14) + 'px';
		}

		function hideTooltip() {
			if (tooltip) {
				tooltip.classList.remove('atlas-tooltip-on');
			}
		}

		svg.addEventListener('mousemove', function (evt) {
			var path = evt.target.closest('.atlas-region');
			if (path && path.classList.contains('atlas-has')) {
				showTooltip(evt, path.dataset.region);
			} else {
				hideTooltip();
			}
		});
		svg.addEventListener('mouseleave', hideTooltip);

		/* حالت انتخاب */
		function setActive(key) {
			svg.querySelectorAll('.atlas-region.atlas-active').forEach(function (p) {
				p.classList.remove('atlas-active');
			});
			wrap.querySelectorAll('.atlas-chip.atlas-active').forEach(function (c) {
				c.classList.remove('atlas-active');
				c.removeAttribute('aria-pressed');
			});
			svg.querySelectorAll('.atlas-pin.atlas-pin-active').forEach(function (p) {
				p.classList.remove('atlas-pin-active');
			});
			var path = svg.querySelector('.atlas-region[data-region="' + key + '"]');
			if (path) {
				path.classList.add('atlas-active');
			}
			var chip = wrap.querySelector('.atlas-chip[data-region="' + key + '"]');
			if (chip) {
				chip.classList.add('atlas-active');
				chip.setAttribute('aria-pressed', 'true');
			}
			var activePin = svg.querySelector('.atlas-pin[data-region="' + key + '"]');
			if (activePin) {
				activePin.classList.add('atlas-pin-active');
			}
		}

		function cardsFor(key) {
			var tpl = wrap.querySelector('template[data-region="' + key + '"]');
			return tpl ? tpl.content.cloneNode(true) : null;
		}

		function openRegion(key) {
			var info = regions[key];
			if (!info || info.count < 1) {
				return;
			}
			setActive(key);

			/* حالت فیلتر: نمایش فقط کارت‌های استانِ انتخاب‌شده در بخش پایین */
			if (mode === 'filter' && filterWrap) {
				var results = filterWrap.querySelector('.atlas-filter-results');
				if (!results) {
					return;
				}
				results.textContent = '';
				var rh = document.createElement('h2');
				rh.className = 'atlas-filter-title';
				rh.textContent = 'نمایندگی‌های ' + info.name;
				results.appendChild(rh);
				var fcards = cardsFor(key);
				if (fcards) {
					results.appendChild(fcards);
				}
				results.classList.add('atlas-filter-results-on');
				results.focus({ preventScroll: true });
				results.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'nearest' });
				return;
			}

			/* حالت آرشیو: اسکرول به گروه استان در لیست کناری */
			if (mode === 'locator' && locator) {
				var target = locator.querySelector('[id="atlas-grp-' + key + '"]');
				if (target) {
					target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
					target.classList.remove('atlas-dir-flash');
					void target.offsetWidth;
					target.classList.add('atlas-dir-flash');
				}
				return;
			}

			var title = 'نمایندگی‌های ' + info.name;
			var cards = cardsFor(key);

			if (display === 'modal' && modal) {
				modal.querySelector('.atlas-modal-title').textContent = title;
				var body = modal.querySelector('.atlas-modal-body');
				body.textContent = '';
				if (cards) {
					body.appendChild(cards);
				}
				openModal();
			} else if (panel) {
				panel.textContent = '';
				var h = document.createElement('h2');
				h.className = 'atlas-panel-title';
				h.textContent = title;
				panel.appendChild(h);
				if (cards) {
					panel.appendChild(cards);
				}
				panel.classList.add('atlas-panel-on');
				panel.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'nearest' });
			}
		}

		/* مودال */
		function openModal() {
			lastFocus = document.activeElement;
			modal.hidden = false;
			document.body.classList.add('atlas-no-scroll');
			requestAnimationFrame(function () {
				modal.classList.add('atlas-modal-on');
				modal.querySelector('.atlas-modal-close').focus();
			});
			document.addEventListener('keydown', onModalKeydown);
		}

		function closeModal() {
			modal.classList.remove('atlas-modal-on');
			document.body.classList.remove('atlas-no-scroll');
			document.removeEventListener('keydown', onModalKeydown);
			var done = function () {
				modal.hidden = true;
				if (lastFocus) {
					lastFocus.focus();
				}
			};
			if (reducedMotion) {
				done();
			} else {
				setTimeout(done, 200);
			}
		}

		function onModalKeydown(evt) {
			if (evt.key === 'Escape') {
				closeModal();
				return;
			}
			if (evt.key !== 'Tab') {
				return;
			}
			var focusables = modal.querySelectorAll('a[href], button:not([disabled])');
			if (!focusables.length) {
				return;
			}
			var first = focusables[0];
			var last = focusables[focusables.length - 1];
			if (evt.shiftKey && document.activeElement === first) {
				evt.preventDefault();
				last.focus();
			} else if (!evt.shiftKey && document.activeElement === last) {
				evt.preventDefault();
				first.focus();
			}
		}

		if (modal) {
			modal.addEventListener('click', function (evt) {
				if (evt.target.closest('[data-atlas-close]')) {
					closeModal();
				}
			});
		}

		/* کلیک و کیبورد روی نقشه */
		svg.addEventListener('click', function (evt) {
			var path = evt.target.closest('.atlas-region');
			if (path) {
				openRegion(path.dataset.region);
			}
		});
		svg.addEventListener('keydown', function (evt) {
			if (evt.key !== 'Enter' && evt.key !== ' ') {
				return;
			}
			var path = evt.target.closest('.atlas-region');
			if (path) {
				evt.preventDefault();
				openRegion(path.dataset.region);
			}
		});

		/* دکمه‌های استان */
		wrap.addEventListener('click', function (evt) {
			var chip = evt.target.closest('.atlas-chip');
			if (chip) {
				openRegion(chip.dataset.region);
			}
		});

		/* حالت filter: چیپ‌ها خارج از wrap نقشه‌اند (ستون کناری) — روی کانتینر گوش می‌دهیم */
		if (filterWrap) {
			filterWrap.addEventListener('click', function (evt) {
				var chip = evt.target.closest('.atlas-chip');
				if (chip) {
					openRegion(chip.dataset.region);
				}
			});
		}
	}

	function initDescToggles() {
		document.querySelectorAll('.atlas-desc-toggle').forEach(function (btn) {
			var body = btn.previousElementSibling;
			if (!body || !body.classList.contains('atlas-desc-body')) {
				return;
			}
			btn.addEventListener('click', function () {
				var isCollapsed = body.classList.contains('is-collapsed');
				if (isCollapsed) {
					body.classList.remove('is-collapsed');
					body.classList.add('is-expanded');
					btn.textContent = 'بستن توضیحات';
				} else {
					body.classList.remove('is-expanded');
					body.classList.add('is-collapsed');
					btn.textContent = 'بیشتر بخوانید';
				}
			});
		});
	}

	function boot() {
		document.querySelectorAll('.atlas-wrap').forEach(initAtlas);
		initDescToggles();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
