/**
 * A second block editor, for the Chinese version of a chapter.
 *
 * Mounted into the meta box that inc/chinese-version.php prints. Two fields: a
 * plain title input, and a nested BlockEditorProvider whose blocks are
 * serialized into the akw_content_zh meta field.
 *
 * Written against the wp.* globals rather than imported modules on purpose —
 * the theme has no build step, and one editor panel is not worth adding a
 * bundler to a repo where every site is deliberately buildless.
 */
( function ( wp ) {
	'use strict';

	var TITLE_META   = 'akw_title_zh';
	var CONTENT_META = 'akw_content_zh';
	var ROOT_ID      = 'akw-chinese-version-root';

	if ( ! wp || ! wp.element || ! wp.blocks || ! wp.blockEditor || ! wp.coreData || ! wp.data ) {
		return;
	}

	var el        = wp.element.createElement;
	var useState  = wp.element.useState;
	var useMemo   = wp.element.useMemo;
	var useEffect = wp.element.useEffect;

	var __ = wp.i18n.__;

	var useSelect     = wp.data.useSelect;
	var useEntityProp = wp.coreData.useEntityProp;

	var editor     = wp.blockEditor;
	var components = wp.components;

	/**
	 * The writing surface.
	 *
	 * Not iframed, on purpose. The post editor relocates meta boxes in the DOM
	 * after it mounts, and moving an iframe reloads it — which would blank the
	 * editor every time the page settles. The editor-styles-wrapper class is
	 * what scopes core's block styles to this subtree instead.
	 */
	function Canvas() {
		var list = el( editor.BlockList, null );

		if ( editor.ObserveTyping ) {
			list = el( editor.ObserveTyping, null, list );
		}

		var surface = el(
			'div',
			{ className: 'editor-styles-wrapper akw-zh-canvas', lang: 'zh-Hans' },
			el( editor.WritingFlow, null, list )
		);

		return editor.BlockTools ? el( editor.BlockTools, null, surface ) : surface;
	}

	/**
	 * An inserter of our own: the main editor's belongs to post_content.
	 */
	function Toolbar() {
		if ( ! editor.Inserter ) {
			return null;
		}

		return el(
			'div',
			{ className: 'akw-zh-toolbar' },
			el( editor.Inserter, {
				position: 'bottom right',
				renderToggle: function ( toggle ) {
					return el(
						components.Button,
						{
							variant: 'secondary',
							onClick: toggle.onToggle,
							disabled: toggle.disabled,
							'aria-expanded': toggle.isOpen,
						},
						__( 'Add block', 'kungfu_2026' )
					);
				},
			} )
		);
	}

	/**
	 * Both fields.
	 *
	 * @param {Object} props          Component props.
	 * @param {Object} props.meta     Current post meta.
	 * @param {Function} props.setMeta Meta setter from useEntityProp.
	 */
	function ChineseVersion( props ) {
		var meta    = props.meta;
		var setMeta = props.setMeta;

		var parentSettings = useSelect( function ( select ) {
			return select( 'core/block-editor' ).getSettings();
		}, [] );

		// Inherit the post editor's settings — media upload, allowed blocks,
		// patterns — but not its template, which belongs to post_content.
		var settings = useMemo( function () {
			var next = Object.assign( {}, parentSettings );

			delete next.template;
			next.templateLock = false;

			return next;
		}, [ parentSettings ] );

		var state    = useState( function () {
			return wp.blocks.parse( meta[ CONTENT_META ] || '' );
		} );
		var value    = state[ 0 ];
		var setValue = state[ 1 ];

		// Serializing on every keystroke would push a store edit per character
		// and re-render the whole editor with it. Typing stays local and a short
		// debounce lands the content in meta long before any save.
		useEffect( function () {
			var timer = setTimeout( function () {
				var next = wp.blocks.serialize( value );

				if ( next !== ( meta[ CONTENT_META ] || '' ) ) {
					setMeta( patch( meta, CONTENT_META, next ) );
				}
			}, 400 );

			return function () {
				clearTimeout( timer );
			};
		}, [ value, meta ] );

		return el(
			components.SlotFillProvider,
			null,
			el(
				'div',
				{ className: 'akw-zh' },
				el( components.TextControl, {
					label: __( 'Chinese title', 'kungfu_2026' ),
					value: meta[ TITLE_META ] || '',
					onChange: function ( title ) {
						setMeta( patch( meta, TITLE_META, title ) );
					},
					className: 'akw-zh-title',
					placeholder: '中文标题',
					lang: 'zh-Hans',
					__nextHasNoMarginBottom: true,
				} ),
				el(
					editor.BlockEditorProvider,
					{
						value: value,
						onInput: setValue,
						onChange: setValue,
						settings: settings,
					},
					el( Toolbar ),
					el( Canvas )
				)
			),
			components.Popover.Slot ? el( components.Popover.Slot ) : null
		);
	}

	/**
	 * One meta key changed, every other key left alone.
	 *
	 * setMeta replaces the whole meta object, so anything else editing meta
	 * would be reverted by a bare { key: value }.
	 *
	 * @param {Object} meta  Current meta.
	 * @param {string} key   Key to change.
	 * @param {string} value New value.
	 * @return {Object} The full meta object.
	 */
	function patch( meta, key, value ) {
		var next = {};

		next[ key ] = value;

		return Object.assign( {}, meta, next );
	}

	/**
	 * Wait for the meta record before mounting an editor bound to it.
	 *
	 * @param {Object} props          Component props.
	 * @param {string} props.postType Post type.
	 * @param {number} props.postId   Post ID.
	 */
	function Gate( props ) {
		var entity = useEntityProp( 'postType', props.postType, 'meta', props.postId );

		if ( ! entity[ 0 ] ) {
			return el( components.Spinner, null );
		}

		return el( ChineseVersion, { meta: entity[ 0 ], setMeta: entity[ 1 ] } );
	}

	/**
	 * Which post is open.
	 */
	function Root() {
		var post = useSelect( function ( select ) {
			var store = select( 'core/editor' );

			return store ? { type: store.getCurrentPostType(), id: store.getCurrentPostId() } : null;
		}, [] );

		if ( ! post || ! post.type || ! post.id ) {
			return el( components.Spinner, null );
		}

		// Keyed on the post so switching posts cannot carry blocks across.
		return el( Gate, { key: post.id, postType: post.type, postId: post.id } );
	}

	/**
	 * @return {boolean} Whether the mount happened.
	 */
	function mount() {
		var node = document.getElementById( ROOT_ID );

		if ( ! node ) {
			return false;
		}

		if ( node.getAttribute( 'data-akw-mounted' ) ) {
			return true;
		}

		node.setAttribute( 'data-akw-mounted', '1' );

		if ( wp.element.createRoot ) {
			wp.element.createRoot( node ).render( el( Root, null ) );
		} else {
			wp.element.render( el( Root, null ), node );
		}

		return true;
	}

	/**
	 * Meta boxes are printed on admin_footer, which can land after this script,
	 * so keep looking for a few seconds rather than assuming the div is there.
	 *
	 * @param {number} attempt Try count.
	 */
	function mountWhenReady( attempt ) {
		if ( mount() || attempt > 40 ) {
			return;
		}

		setTimeout( function () {
			mountWhenReady( attempt + 1 );
		}, 100 );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			mountWhenReady( 0 );
		} );
	} else {
		mountWhenReady( 0 );
	}
} )( window.wp );
