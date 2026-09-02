/**
 * The chapter copy button.
 *
 * Reads the title and body out of the page rather than from a hidden copy of
 * the text, so it always copies the language currently on screen.
 *
 * Two clipboard paths: the async Clipboard API where it is available, and the
 * old execCommand route where it is not. navigator.clipboard exists only in a
 * secure context, so a site served over plain http would otherwise have a
 * button that silently does nothing.
 */
( function () {
	'use strict';

	var strings = window.akwCopy || {};

	document.addEventListener( 'DOMContentLoaded', function () {
		var button = document.querySelector( '.copy-button' );
		var body   = document.querySelector( '.chapter-body' );

		if ( ! button || ! body ) {
			return;
		}

		var title  = document.querySelector( '.chapter__title' );
		var status = document.querySelector( '.chapter-tools__status' );
		var idle   = button.getAttribute( 'data-copy-label' ) || '';
		var timer;

		function name( label ) {
			button.setAttribute( 'aria-label', label );
			button.setAttribute( 'title', label );
		}

		/*
		 * The button has no text to replace — it is an icon — so the state
		 * shows as a class that swaps the icon, and the name it reports to
		 * assistive tech is moved along with it.
		 */
		function report( label, message, ok ) {
			button.classList.toggle( 'is-copied', ok );
			name( label );

			if ( status ) {
				status.textContent = message;
			}

			clearTimeout( timer );
			timer = setTimeout( function () {
				button.classList.remove( 'is-copied' );
				name( idle );

				if ( status ) {
					status.textContent = '';
				}
			}, 2500 );
		}

		function legacyCopy( text ) {
			var field = document.createElement( 'textarea' );

			field.value = text;
			field.setAttribute( 'readonly', '' );
			// Off-screen rather than hidden: a display:none field cannot be selected.
			field.style.position = 'absolute';
			field.style.left = '-9999px';
			document.body.appendChild( field );
			field.select();
			field.setSelectionRange( 0, text.length );

			var ok = false;

			try {
				ok = document.execCommand( 'copy' );
			} catch ( e ) {
				ok = false;
			}

			document.body.removeChild( field );

			return ok;
		}

		button.addEventListener( 'click', function () {
			var text = ( title ? title.textContent.trim() + '\n\n' : '' )
				+ ( body.innerText || body.textContent || '' ).trim();

			function succeeded() {
				var label = button.getAttribute( 'data-copied-label' ) || strings.copied || 'Copied!';

				report( label, strings.status || label, true );
			}

			function failed() {
				report( strings.failed || 'Copy failed', strings.failed || '', false );
			}

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text ).then( succeeded, function () {
					// A denied permission still leaves the old path worth trying.
					if ( legacyCopy( text ) ) {
						succeeded();
					} else {
						failed();
					}
				} );

				return;
			}

			if ( legacyCopy( text ) ) {
				succeeded();
			} else {
				failed();
			}
		} );
	} );
} )();
