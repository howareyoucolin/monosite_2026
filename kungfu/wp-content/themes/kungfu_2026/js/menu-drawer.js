( function () {
	'use strict';

	var toggle = document.querySelector( '.menu-toggle' );
	var drawer = document.getElementById( 'primary-menu-drawer' );
	if ( ! toggle || ! drawer || typeof drawer.showModal !== 'function' ) {
		return;
	}

	var closeTimer;
	toggle.hidden = false;

	function closeDrawer() {
		drawer.classList.remove( 'is-open' );
		window.clearTimeout( closeTimer );
		closeTimer = window.setTimeout( function () {
			drawer.close();
		}, window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 0 : 220 );
	}

	toggle.addEventListener( 'click', function () {
		window.clearTimeout( closeTimer );
		drawer.showModal();
		document.documentElement.classList.add( 'menu-drawer-open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		// Commit the off-screen position before starting the slide.
		drawer.getBoundingClientRect();
		drawer.classList.add( 'is-open' );
	} );

	drawer.querySelector( '.menu-drawer__close' ).addEventListener( 'click', closeDrawer );
	drawer.addEventListener( 'cancel', function ( event ) {
		event.preventDefault();
		closeDrawer();
	} );
	drawer.addEventListener( 'click', function ( event ) {
		var bounds = drawer.getBoundingClientRect();
		if ( event.target === drawer && ( event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom ) ) {
			closeDrawer();
		}
		if ( event.target.closest( 'a' ) ) {
			closeDrawer();
		}
	} );
	drawer.addEventListener( 'close', function () {
		drawer.classList.remove( 'is-open' );
		document.documentElement.classList.remove( 'menu-drawer-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		toggle.focus( { preventScroll: true } );
	} );
} )();
