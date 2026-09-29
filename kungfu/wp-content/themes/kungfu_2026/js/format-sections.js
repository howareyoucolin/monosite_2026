/**
 * "Format sections": turn pasted section titles into level-3 headings.
 *
 * Every top-level paragraph or heading whose whole text reads as a section
 * title becomes <h3 class="wp-block-heading">Section N: Title</h3>. Core adds
 * the wp-block-heading class itself when a heading block saves, so the block
 * only needs level 3 and the normalized text.
 *
 * Recognised, case-insensitively, with or without markdown debris left by the
 * paste (leading #s, surrounding ** or __):
 *
 *   Section 1: Title      SECTION 1 - Title      Section 1. Title
 *   Section One: Title    Section IV — Title     Section 2
 *
 * A paragraph that starts with a title and carries on after a <br> is split:
 * the title becomes the heading and the rest stays a paragraph below it.
 *
 * The same click then removes bold from everything that is not a heading,
 * nested blocks included, so body text reads at the theme's normal weight.
 * Headings (h1–h6) keep whatever bold they have.
 *
 * Beside it, "Clear content" empties the post for the next paste, without
 * saving and with autosave held off until something is pasted.
 *
 * Buildless like the rest of the theme's editor code, so written against the
 * wp.* globals. The button is placed into the header toolbar by hand because
 * the post editor has no slot there; it re-attaches if the header re-renders.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.element || ! wp.blocks || ! wp.data || ! wp.components ) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var _n = wp.i18n._n;
	var sprintf = wp.i18n.sprintf;

	var HOST_ID = 'akw-format-sections';

	// Header toolbar: 6.6+ first, then the older edit-post markup.
	var TOOLBAR_SELECTORS = [ '.editor-header__toolbar', '.edit-post-header-toolbar' ];

	// Longer than this and it is a sentence that happens to start with
	// "Section 3:", not a title.
	var MAX_TITLE = 150;

	var NUMBER = '[0-9]+|[ivxlc]+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty';

	var TITLE = new RegExp(
		'^(?:#{1,6}\\s*)?(?:\\*\\*|__)?\\s*section\\s+(' + NUMBER + ')\\s*(?:[:：.\\-–—)]\\s*(.*?))?\\s*(?:\\*\\*|__)?\\s*$',
		'i'
	);

	/**
	 * A RichText value as an HTML string, whichever form the block holds it in.
	 *
	 * @param {*} value String, or RichTextData on 6.5+.
	 * @return {string} HTML.
	 */
	function toHtml( value ) {
		if ( 'string' === typeof value ) {
			return value;
		}

		if ( value && 'function' === typeof value.toHTMLString ) {
			return value.toHTMLString();
		}

		return value ? String( value ) : '';
	}

	/**
	 * The normalized heading text for an HTML fragment, if it is a title.
	 *
	 * Inline markup is dropped — a heading is styled by the theme, so bold or
	 * italics carried over from the paste would only fight it. Entities are
	 * kept as they are, since the result goes back in as HTML.
	 *
	 * @param {string} html Fragment.
	 * @return {string|null} "Section N: Title", or null.
	 */
	function titleFrom( html ) {
		var text = html
			.replace( /<[^>]*>/g, '' )
			.replace( /&nbsp;| /g, ' ' )
			.replace( /\s+/g, ' ' )
			.trim();

		var match = TITLE.exec( text );

		if ( ! match ) {
			return null;
		}

		var number = /^[0-9]+$/.test( match[ 1 ] )
			? match[ 1 ]
			: /^[ivxlc]+$/i.test( match[ 1 ] )
				? match[ 1 ].toUpperCase()
				: match[ 1 ].charAt( 0 ).toUpperCase() + match[ 1 ].slice( 1 ).toLowerCase();

		var title = ( match[ 2 ] || '' ).replace( /(?:\*\*|__)\s*$/, '' ).trim();

		if ( title.length > MAX_TITLE ) {
			return null;
		}

		return title ? 'Section ' + number + ': ' + title : 'Section ' + number;
	}

	/**
	 * What one block should become, or null to leave it alone.
	 *
	 * @param {Object} block Block.
	 * @return {Object[]|null} Replacement blocks.
	 */
	function reformat( block ) {
		var name = block.name;

		if ( 'core/paragraph' !== name && 'core/heading' !== name ) {
			return null;
		}

		var html = toHtml( block.attributes.content );

		// Split first: stripping tags would otherwise run the title and the
		// body after a <br> together into one long "title".
		var parts = html.split( /<br\s*\/?>/i );

		if ( parts.length < 2 ) {
			var whole = titleFrom( html );

			if ( ! whole || ( 'core/heading' === name && 3 === block.attributes.level && html === whole ) ) {
				return null;
			}

			return [ wp.blocks.createBlock( 'core/heading', { level: 3, content: whole } ) ];
		}

		// "Section 1: Title<br>First line of the body…"
		if ( 'core/paragraph' !== name ) {
			return null;
		}

		var title = titleFrom( parts[ 0 ] );

		if ( ! title ) {
			return null;
		}

		var rest = parts.slice( 1 ).join( '<br>' ).replace( /^(?:\s|&nbsp;)+/, '' );
		var out = [ wp.blocks.createBlock( 'core/heading', { level: 3, content: title } ) ];

		if ( rest ) {
			out.push(
				wp.blocks.createBlock(
					'core/paragraph',
					Object.assign( {}, block.attributes, { content: rest } )
				)
			);
		}

		return out;
	}

	// <strong>/<b>, opening or closing. Not <br> or <blockquote>: the name has
	// to end right after the b.
	var BOLD = /<\/?(?:strong|b)(?:\s[^>]*)?>/gi;

	/**
	 * Unbold one block and everything nested in it, headings excepted.
	 *
	 * Covers both kinds of bold a paste leaves: <strong>/<b> inside any rich
	 * text attribute (paragraphs, list items, quotes, captions…, found through
	 * the block type's attribute sources rather than a list of block names),
	 * and a block-wide font weight set in the typography panel.
	 *
	 * @param {Object} block Block.
	 * @return {number} Blocks changed.
	 */
	function unbold( block ) {
		var changed = 0;

		block.innerBlocks.forEach( function ( inner ) {
			changed += unbold( inner );
		} );

		if ( 'core/heading' === block.name ) {
			return changed;
		}

		var type = wp.blocks.getBlockType( block.name );
		var definitions = ( type && type.attributes ) || {};
		var next = {};
		var dirty = false;

		Object.keys( definitions ).forEach( function ( key ) {
			var source = definitions[ key ].source;

			if ( 'rich-text' !== source && 'html' !== source && 'raw' !== source ) {
				return;
			}

			var html = toHtml( block.attributes[ key ] );

			if ( html && BOLD.test( html ) ) {
				next[ key ] = html.replace( BOLD, '' );
				dirty = true;
			}

			BOLD.lastIndex = 0;
		} );

		var style = block.attributes.style;

		if ( style && style.typography && style.typography.fontWeight ) {
			var typography = Object.assign( {}, style.typography );

			delete typography.fontWeight;
			next.style = Object.assign( {}, style, { typography: typography } );
			dirty = true;
		}

		if ( dirty ) {
			wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( block.clientId, next );
			changed++;
		}

		return changed;
	}

	/**
	 * Reformat the post, and say how it went.
	 *
	 * Titles first, so a bolded title becomes a heading (unbolded by
	 * titleFrom()) before the unbolding pass would have left it a plain line.
	 */
	function run() {
		var editor = wp.data.dispatch( 'core/block-editor' );
		var store = wp.data.select( 'core/block-editor' );
		var titles = 0;
		var unbolded = 0;

		store.getBlocks().forEach( function ( block ) {
			var next = reformat( block );

			if ( next ) {
				editor.replaceBlocks( block.clientId, next );
				titles++;
			}
		} );

		store.getBlocks().forEach( function ( block ) {
			unbolded += unbold( block );
		} );

		var notices = wp.data.dispatch( 'core/notices' );

		if ( ! notices ) {
			return;
		}

		var parts = [];

		if ( titles ) {
			parts.push(
				sprintf(
					/* translators: %d: number of section titles formatted. */
					_n( 'Formatted %d section title.', 'Formatted %d section titles.', titles, 'kungfu_2026' ),
					titles
				)
			);
		}

		if ( unbolded ) {
			parts.push(
				sprintf(
					/* translators: %d: number of blocks bold was removed from. */
					_n( 'Removed bold from %d block.', 'Removed bold from %d blocks.', unbolded, 'kungfu_2026' ),
					unbolded
				)
			);
		}

		notices.createNotice(
			parts.length ? 'success' : 'info',
			parts.length ? parts.join( ' ' ) : __( 'Nothing to format.', 'kungfu_2026' ),
			{ type: 'snackbar', id: 'akw-format-sections', isDismissible: true }
		);
	}

	// Held on core/editor while a cleared post is empty.
	var AUTOSAVE_LOCK = 'akw-clear-content';

	var autosaveLocked = false;

	/**
	 * Empty the post, ready for the next paste.
	 *
	 * An ordinary block removal, so it only changes the editor: nothing reaches
	 * the database until Save or Update is pressed, and Undo brings it all
	 * back. What it does hold off is autosave — on a draft, WordPress autosaves
	 * into the post itself, which would write the empty content without anyone
	 * pressing anything. The lock is lifted by releaseAutosave() once the post
	 * has something in it again.
	 *
	 * Only the body is cleared. The title, tags and the Chinese version box
	 * stay as they are.
	 */
	function clearContent() {
		var store = wp.data.select( 'core/block-editor' );
		var ids = store.getBlocks().map( function ( block ) {
			return block.clientId;
		} );

		if ( ! ids.length ) {
			return;
		}

		var postEditor = wp.data.dispatch( 'core/editor' );

		if ( postEditor && postEditor.lockPostAutosaving ) {
			postEditor.lockPostAutosaving( AUTOSAVE_LOCK );
			autosaveLocked = true;
		}

		wp.data.dispatch( 'core/block-editor' ).removeBlocks( ids, false );

		var notices = wp.data.dispatch( 'core/notices' );

		if ( notices ) {
			notices.createNotice( 'info', __( 'Content cleared. Not saved — autosave is paused until you paste.', 'kungfu_2026' ), {
				type: 'snackbar',
				id: 'akw-format-sections',
				isDismissible: true,
				actions: [
					{
						label: __( 'Undo', 'kungfu_2026' ),
						onClick: function () {
							wp.data.dispatch( 'core/editor' ).undo();
						},
					},
				],
			} );
		}
	}

	/**
	 * Lift the autosave lock once the post holds real content again.
	 *
	 * The empty paragraph the editor drops in when you click into a blank post
	 * does not count, or autosave would resume one click before the paste.
	 */
	function releaseAutosave() {
		if ( ! autosaveLocked ) {
			return;
		}

		var blocks = wp.data.select( 'core/block-editor' ).getBlocks();

		if ( ! blocks.length || ( 1 === blocks.length && wp.blocks.isUnmodifiedDefaultBlock( blocks[ 0 ] ) ) ) {
			return;
		}

		autosaveLocked = false;
		wp.data.dispatch( 'core/editor' ).unlockPostAutosaving( AUTOSAVE_LOCK );
	}

	function Buttons() {
		return el(
			wp.element.Fragment,
			null,
			el(
				wp.components.Button,
				{
					variant: 'secondary',
					size: 'compact',
					onClick: run,
					label: __( 'Turn "Section N: Title" lines into level-3 headings and remove bold outside headings', 'kungfu_2026' ),
					showTooltip: true,
					style: { marginLeft: '8px' },
				},
				__( 'Format sections', 'kungfu_2026' )
			),
			el(
				wp.components.Button,
				{
					variant: 'secondary',
					size: 'compact',
					isDestructive: true,
					onClick: clearContent,
					label: __( 'Remove every block from the content, without saving', 'kungfu_2026' ),
					showTooltip: true,
					style: { marginLeft: '8px' },
				},
				__( 'Clear content', 'kungfu_2026' )
			)
		);
	}

	/**
	 * Put the buttons into the header toolbar if they are not there already.
	 */
	function attach() {
		if ( document.getElementById( HOST_ID ) ) {
			return;
		}

		var toolbar = null;

		for ( var i = 0; i < TOOLBAR_SELECTORS.length && ! toolbar; i++ ) {
			toolbar = document.querySelector( TOOLBAR_SELECTORS[ i ] );
		}

		if ( ! toolbar ) {
			return;
		}

		var host = document.createElement( 'div' );

		host.id = HOST_ID;
		host.style.display = 'flex';
		host.style.alignItems = 'center';
		toolbar.appendChild( host );

		if ( wp.element.createRoot ) {
			wp.element.createRoot( host ).render( el( Buttons ) );
		} else {
			wp.element.render( el( Buttons ), host );
		}
	}

	wp.domReady( function () {
		attach();

		// The header mounts after the editor boots and can be replaced later
		// (distraction-free mode, a changed viewport), taking the button with it.
		wp.data.subscribe( attach );
		wp.data.subscribe( releaseAutosave );
	} );
} )( window.wp );
