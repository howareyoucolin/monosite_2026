/* Automatically load maps near the viewport, retaining a loading placeholder. */
(function () {
	'use strict';
	var content = document.getElementById('main-content');
	if (!content) return;
	function loadMap(preview) {
		if (preview.dataset.loaded) return;
		preview.dataset.loaded = 'true';
		var media = preview.parentNode;
		media.classList.add('is-loading');
		var frame = document.createElement('iframe');
		frame.title = '地图：' + preview.dataset.title;
		frame.allowFullscreen = true;
		frame.referrerPolicy = 'strict-origin-when-cross-origin';
		frame.addEventListener('load', function () {
			preview.hidden = true;
			media.classList.remove('is-loading');
		}, { once: true });
		frame.src = preview.dataset.embed;
		media.appendChild(frame);
	}
	var previews = content.querySelectorAll('.dy-map-preview');
	if (!('IntersectionObserver' in window)) {
		previews.forEach(loadMap);
		return;
	}
	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) return;
			observer.unobserve(entry.target);
			loadMap(entry.target);
		});
	}, { rootMargin: '250px' });
	previews.forEach(function (preview) { observer.observe(preview); });
}());
