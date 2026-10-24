/* Load a single YouTube player only after a visitor selects a preview. */
(function () {
	'use strict';
	var grid = document.getElementById('main-content');
	if (!grid) return;
	var active = null;
	grid.addEventListener('click', function (event) {
		var preview = event.target.closest('.dy-video-preview');
		if (!preview || !grid.contains(preview)) return;
		event.preventDefault();
		if (active) {
			active.frame.remove();
			active.preview.hidden = false;
		}
		var frame = document.createElement('iframe');
		var url = new URL(preview.dataset.embed);
		url.searchParams.set('autoplay', '1');
		frame.src = url.href;
		frame.title = preview.dataset.title;
		frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
		frame.allowFullscreen = true;
		frame.referrerPolicy = 'strict-origin-when-cross-origin';
		preview.hidden = true;
		preview.parentNode.appendChild(frame);
		active = { frame: frame, preview: preview };
		frame.focus();
	});
}());
