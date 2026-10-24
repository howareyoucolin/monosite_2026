(function () {
	'use strict';
	var header = document.getElementById('nav-wrap');
	if (!header) return;
	var toggle = header.querySelector('.dy-mobile-menu');
	var links = header.querySelector('#dy-navigation-list');
	if (!toggle || !links) return;
	header.classList.add('is-enhanced');
	function closeMenu() {
		header.classList.remove('is-open');
		toggle.setAttribute('aria-expanded', 'false');
	}
	toggle.addEventListener('click', function () {
		var open = toggle.getAttribute('aria-expanded') !== 'true';
		toggle.setAttribute('aria-expanded', String(open));
		header.classList.toggle('is-open', open);
	});
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && header.classList.contains('is-open')) {
			closeMenu();
			toggle.focus();
		}
	});
	document.addEventListener('click', function (event) {
		if (!header.contains(event.target)) closeMenu();
	});
	links.addEventListener('click', function (event) {
		if (event.target.closest('a')) closeMenu();
	});
	window.matchMedia('(max-width: 767px)').addEventListener('change', closeMenu);
}());
