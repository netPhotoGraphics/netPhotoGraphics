/* netPhotoGraphics administration: collapsible side menu on narrow screens. Without this script the menu is simply shown in full. */
(function () {
	'use strict';
	function init() {
		var nav = document.querySelector('.navigation');
		if (!nav) {
			return;
		}
		var narrow = window.matchMedia('(max-width: 900px)');
		var list = nav.querySelector(':scope > ul');
		if (!list) {
			return;
		}

		/* menu button */
		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'npg-menu-button ignoredirty';
		button.setAttribute('aria-expanded', 'false');
		button.setAttribute('aria-label', 'Menu');
		button.innerHTML = '<span aria-hidden="true">&#9776;</span> Menu';
		nav.insertBefore(button, list);
		nav.classList.add('npg-js');

		/* language switch: moved from the page footer under the side menu so it is always in sight */
		var flags = document.querySelector('#footer_left .flags, #footer_left .languageSelect');
		if (flags) {
			var lang = document.createElement('div');
			lang.className = 'npg-lang';
			lang.appendChild(flags);
			nav.appendChild(lang);
		}

		button.addEventListener('click', function () {
			var open = !nav.classList.contains('npg-open');
			nav.classList.toggle('npg-open', open);
			button.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		/* on a narrow screen the first tap on a menu item with sub items opens it, the second one follows the link */
		list.addEventListener('click', function (e) {
			if (!narrow.matches) {
				return;
			}
			var a = e.target.closest ? e.target.closest('a') : null;
			var li = a ? a.parentNode : null;
			if (!li || !li.classList.contains('has-sub') || li.parentNode !== list) {
				return;
			}
			if (!li.classList.contains('npg-sub-open')) {
				e.preventDefault();
				var all = list.querySelectorAll(':scope > li.npg-sub-open');
				for (var i = 0; i < all.length; i++) {
					all[i].classList.remove('npg-sub-open');
				}
				li.classList.add('npg-sub-open');
			}
		});

		/* the menu of the current page is open from the start */
		var active = list.querySelector(':scope > li > a.active');
		if (active && active.parentNode.classList.contains('has-sub')) {
			active.parentNode.classList.add('npg-sub-open');
		}
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', init);
		} else {
			init();
	}
})();
