/**
 * "Set as former version": keep the current body, then empty it for a remaster.
 *
 * A row in the Post tab of the editor sidebar. The click copies the body —
 * serialized, exactly as post_content would hold it — into the
 * akw_content_former meta field, then removes every block. Both are ordinary
 * edits: nothing reaches the database until Update is pressed, and then they
 * go together, so the old body is never lost without the new one being saved
 * with it.
 *
 * Autosave is held off from the click until the next real save. On a draft,
 * WordPress autosaves into the post itself and leaves meta behind, which would
 * write the empty (or half-rewritten) body without the copy of the old one.
 *
 * Buildless like the rest of the theme's editor code, so written against the
 * wp.* globals.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.element || ! wp.data || ! wp.plugins || ! wp.components ) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	var useSelect = wp.data.useSelect;

	var FORMER_META   = 'akw_content_former';
	var AUTOSAVE_LOCK = 'akw-former-version';
	var NOTICE_ID     = 'akw-former-version';

	// 6.6 moved the slot to the editor package; older cores have it on edit-post.
	var PluginPostStatusInfo =
		( wp.editor && wp.editor.PluginPostStatusInfo ) ||
		( wp.editPost && wp.editPost.PluginPostStatusInfo );

	if ( ! PluginPostStatusInfo ) {
		return;
	}

	var autosaveLocked = false;

	/**
	 * One meta key changed, every other key left alone — the Chinese version
	 * box edits meta too, and a bare { key: value } would revert it.
	 *
	 * @param {Object} meta  Current meta.
	 * @param {string} value New former content.
	 * @return {Object} The full meta object.
	 */
	function withFormer( meta, value ) {
		var next = {};

		next[ FORMER_META ] = value;

		return Object.assign( {}, meta, next );
	}

	/**
	 * Put the body back and the previous former version with it.
	 *
	 * Done by hand rather than with undo(): the click is two edits, meta and
	 * blocks, and undo() only takes back the last one.
	 *
	 * @param {string} content    Body that was moved out.
	 * @param {string} oldFormer  What the meta held before the click.
	 */
	function restore( content, oldFormer ) {
		var editor = wp.data.dispatch( 'core/editor' );
		var meta   = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};

		editor.editPost( { meta: withFormer( meta, oldFormer ) } );
		editor.resetEditorBlocks( wp.blocks.parse( content ) );
	}

	/**
	 * Copy the body into the former version and empty the editor.
	 */
	function setAsFormer() {
		var select  = wp.data.select( 'core/editor' );
		var content = select.getEditedPostContent();
		var meta    = select.getEditedPostAttribute( 'meta' ) || {};
		var old     = meta[ FORMER_META ] || '';

		if ( ! content.trim() ) {
			return;
		}

		if (
			old.trim() &&
			! window.confirm( __( 'This chapter already has a former version. Replace it with the current content?', 'kungfu_2026' ) )
		) {
			return;
		}

		var editor = wp.data.dispatch( 'core/editor' );

		if ( editor.lockPostAutosaving ) {
			editor.lockPostAutosaving( AUTOSAVE_LOCK );
			autosaveLocked = true;
		}

		editor.editPost( { meta: withFormer( meta, content ) } );
		editor.resetEditorBlocks( [] );

		var notices = wp.data.dispatch( 'core/notices' );

		if ( notices ) {
			notices.createNotice( 'info', __( 'Saved as former version. Not stored until you update — autosave is paused until then.', 'kungfu_2026' ), {
				type: 'snackbar',
				id: NOTICE_ID,
				isDismissible: true,
				actions: [
					{
						label: __( 'Undo', 'kungfu_2026' ),
						onClick: function () {
							restore( content, old );
						},
					},
				],
			} );
		}
	}

	/**
	 * Lift the autosave lock once a manual save has gone through, which is when
	 * the meta and the emptied body reached the database together.
	 */
	var wasSaving = false;

	function releaseAutosave() {
		if ( ! autosaveLocked ) {
			return;
		}

		var select = wp.data.select( 'core/editor' );
		var saving = select.isSavingPost() && ! select.isAutosavingPost();

		if ( wasSaving && ! saving && select.didPostSaveRequestSucceed() ) {
			autosaveLocked = false;
			wp.data.dispatch( 'core/editor' ).unlockPostAutosaving( AUTOSAVE_LOCK );
		}

		wasSaving = saving;
	}

	function FormerVersion() {
		var state = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			var saved  = editor.getCurrentPostAttribute( 'meta' ) || {};

			return {
				// Not getEditedPostContent(): that serializes the whole chapter on
				// every keystroke.
				isEmpty: editor.isEditedPostEmpty(),
				hasSaved: !! ( saved[ FORMER_META ] || '' ).trim(),
				isPublished: 'publish' === editor.getCurrentPostAttribute( 'status' ),
				permalink: editor.getPermalink(),
			};
		}, [] );

		var viewUrl = null;

		if ( state.hasSaved && state.isPublished && state.permalink ) {
			viewUrl = state.permalink + ( -1 === state.permalink.indexOf( '?' ) ? '?' : '&' ) + 'version=previous';
		}

		return el(
			PluginPostStatusInfo,
			{ className: 'akw-former-version' },
			el(
				'div',
				{ style: { width: '100%' } },
				el(
					wp.components.Button,
					{
						variant: 'secondary',
						onClick: setAsFormer,
						disabled: state.isEmpty,
						accessibleWhenDisabled: true,
						label: __( 'Keep the current content as the former version and empty the editor for a remaster', 'kungfu_2026' ),
						showTooltip: true,
						style: { width: '100%', justifyContent: 'center' },
					},
					__( 'Set as former version', 'kungfu_2026' )
				),
				state.hasSaved
					? el(
						'p',
						{ className: 'description', style: { margin: '8px 0 0' } },
						__( 'A former version is saved.', 'kungfu_2026' ),
						viewUrl ? ' ' : null,
						viewUrl
							? el( 'a', { href: viewUrl, target: '_blank', rel: 'noopener' }, __( 'View it', 'kungfu_2026' ) )
							: null
					)
					: null
			)
		);
	}

	wp.plugins.registerPlugin( 'akw-former-version', { render: FormerVersion } );

	wp.data.subscribe( releaseAutosave );
} )( window.wp );
