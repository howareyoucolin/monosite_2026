<?php
/**
 * Admin affordances for the chapter structure.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extra fields on the "add term" form.
 */
function kungfu_2026_series_add_fields() {
	?>
	<div class="form-field term-akw-order-wrap">
		<label for="akw_order"><?php esc_html_e( 'Order', 'kungfu_2026' ); ?></label>
		<input type="number" name="akw_order" id="akw_order" value="0" step="1">
		<p><?php esc_html_e( 'Position of this arc within its series. Ignored for a series itself.', 'kungfu_2026' ); ?></p>
	</div>
	<div class="form-field term-akw-lang-wrap">
		<label for="akw_lang"><?php esc_html_e( 'Language', 'kungfu_2026' ); ?></label>
		<select name="akw_lang" id="akw_lang">
			<option value="en"><?php esc_html_e( 'English', 'kungfu_2026' ); ?></option>
			<option value="zh"><?php esc_html_e( 'Chinese', 'kungfu_2026' ); ?></option>
		</select>
		<p><?php esc_html_e( 'Set on a series. Decides how chapter labels are formatted.', 'kungfu_2026' ); ?></p>
	</div>
	<?php
}
add_action( AKW_SERIES . '_add_form_fields', 'kungfu_2026_series_add_fields' );

/**
 * Extra fields on the "edit term" form.
 *
 * @param WP_Term $term Term being edited.
 */
function kungfu_2026_series_edit_fields( $term ) {
	$order = (int) get_term_meta( $term->term_id, 'akw_order', true );
	$lang  = akw_get_series_language( $term->parent ? $term->parent : $term );
	?>
	<tr class="form-field term-akw-order-wrap">
		<th scope="row"><label for="akw_order"><?php esc_html_e( 'Order', 'kungfu_2026' ); ?></label></th>
		<td>
			<input type="number" name="akw_order" id="akw_order" value="<?php echo esc_attr( (string) $order ); ?>" step="1">
			<p class="description"><?php esc_html_e( 'Position of this arc within its series. Ignored for a series itself.', 'kungfu_2026' ); ?></p>
		</td>
	</tr>
	<?php if ( ! $term->parent ) : ?>
	<tr class="form-field term-akw-lang-wrap">
		<th scope="row"><label for="akw_lang"><?php esc_html_e( 'Language', 'kungfu_2026' ); ?></label></th>
		<td>
			<select name="akw_lang" id="akw_lang">
				<option value="en" <?php selected( $lang, 'en' ); ?>><?php esc_html_e( 'English', 'kungfu_2026' ); ?></option>
				<option value="zh" <?php selected( $lang, 'zh' ); ?>><?php esc_html_e( 'Chinese', 'kungfu_2026' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Decides how chapter labels are formatted.', 'kungfu_2026' ); ?></p>
		</td>
	</tr>
		<?php
	endif;
}
add_action( AKW_SERIES . '_edit_form_fields', 'kungfu_2026_series_edit_fields' );

/**
 * Persist those fields.
 *
 * Core has already verified its own nonce before firing created_/edited_, so
 * this only needs the capability check.
 *
 * @param int $term_id Term ID.
 */
function kungfu_2026_save_series_fields( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	if ( isset( $_POST['akw_order'] ) ) {
		update_term_meta( $term_id, 'akw_order', absint( wp_unslash( $_POST['akw_order'] ) ) );
	}

	if ( isset( $_POST['akw_lang'] ) ) {
		$lang = sanitize_key( wp_unslash( $_POST['akw_lang'] ) );
		update_term_meta( $term_id, 'akw_lang', in_array( $lang, array( 'en', 'zh' ), true ) ? $lang : 'en' );
	}
}
add_action( 'created_' . AKW_SERIES, 'kungfu_2026_save_series_fields' );
add_action( 'edited_' . AKW_SERIES, 'kungfu_2026_save_series_fields' );

/**
 * Show the resolved numbering on the chapter list table.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function kungfu_2026_chapter_columns( $columns ) {
	$reordered = array();

	foreach ( $columns as $key => $label ) {
		$reordered[ $key ] = $label;

		if ( 'title' === $key ) {
			$reordered['akw_number'] = __( 'Numbering', 'kungfu_2026' );
		}
	}

	return $reordered;
}
add_filter( 'manage_' . AKW_CHAPTER . '_posts_columns', 'kungfu_2026_chapter_columns' );

/**
 * Render that column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Chapter ID.
 */
function kungfu_2026_chapter_column_content( $column, $post_id ) {
	if ( 'akw_number' !== $column ) {
		return;
	}

	$number = akw_get_chapter_number( $post_id );

	if ( ! $number ) {
		echo '<em>' . esc_html__( 'Not filed under an arc', 'kungfu_2026' ) . '</em>';
		return;
	}

	echo esc_html( akw_get_chapter_label( $post_id ) );
	printf(
		' <span class="description">(%s)</span>',
		esc_html(
			sprintf(
				/* translators: %d: position within the arc. */
				__( '#%d in arc', 'kungfu_2026' ),
				akw_get_arc_index( $post_id )
			)
		)
	);
}
add_action( 'manage_' . AKW_CHAPTER . '_posts_custom_column', 'kungfu_2026_chapter_column_content', 10, 2 );

/**
 * Sort that column by the series-wide number.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function kungfu_2026_chapter_sortable_columns( $columns ) {
	$columns['akw_number'] = 'akw_number';

	return $columns;
}
add_filter( 'manage_edit-' . AKW_CHAPTER . '_sortable_columns', 'kungfu_2026_chapter_sortable_columns' );

/**
 * Default the chapter list to reading order.
 *
 * @param WP_Query $query Query.
 */
function kungfu_2026_chapter_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || AKW_CHAPTER !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( 'akw_number' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'akw_chapter_number' );
		$query->set( 'orderby', 'meta_value_num' );
		return;
	}

	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'akw_chapter_number' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'kungfu_2026_chapter_admin_order' );
