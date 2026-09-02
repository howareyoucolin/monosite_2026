/**
 * The back-to-top button.
 *
 * Appears once the page has been scrolled 1000px and scrolls back to the top
 * when pressed.
 */
( function () {
	'use strict';

	/** How far down the page the button starts showing, in pixels. */
	var THRESHOLD = 1000;

	function reducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * How far down the page we are.
	 *
	 * Not `window.scrollY || documentElement.scrollTop`: zero is a legitimate
	 * offset and a falsy one, so that idiom falls through to the next value at
	 * the very top of the page. Test for the property instead, and fall back to
	 * body as well as documentElement — in quirks mode the offset lives there.
	 *
	 * @return {number} Pixels scrolled from the top.
	 */
	function offset() {
		if ( typeof window.scrollY === 'number' ) {
			return window.scrollY;
		}

		if ( typeof window.pageYOffset === 'number' ) {
			return window.pageYOffset;
		}

		var doc = document.documentElement || document.body;

		return doc && typeof doc.scrollTop === 'number' ? doc.scrollTop : 0;
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var button = document.querySelector( '.to-top' );

		if ( ! button ) {
			return;
		}

		var pending = false;

		function sync() {
			pending = false;

			button.classList.toggle( 'is-visible', offset() >= THRESHOLD );
		}

		/*
		 * Scroll fires far more often than the class needs changing, and
		 * reading scrollY in the handler forces layout. One read per frame is
		 * enough for a button that only has two states.
		 */
		function onScroll() {
			if ( pending ) {
				return;
			}

			pending = true;
			window.requestAnimationFrame( sync );
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );

		// A reload can restore a scrolled position, so decide before any scroll.
		sync();

		button.addEventListener( 'click', function () {
			try {
				window.scrollTo( { top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' } );
			} catch ( e ) {
				// Browsers that reject the options object still take two numbers.
				window.scrollTo( 0, 0 );
			}

			/*
			 * The button hides itself on the way up, which would drop focus to
			 * nowhere. Hand it to the top of the document instead.
			 * preventScroll keeps that from cancelling the smooth scroll.
			 */
			var home = document.querySelector( '.site-title a' );

			if ( home ) {
				try {
					home.focus( { preventScroll: true } );
				} catch ( e ) {
					// An options object here is also recent; losing focus is survivable.
				}
			}
		} );
	} );
} )();
