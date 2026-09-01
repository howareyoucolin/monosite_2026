<?php
/**
 * Continuous chapter numbering.
 *
 * Chapters are numbered across a whole series rather than restarting each arc:
 * if arc 1 ends at 46, the first chapter of arc 2 is 47. Each arc stores the
 * running total that precedes it (akw_chapter_offset) and each chapter stores
 * its own resolved number, so a template reads one meta value instead of
 * counting rows on every request.
 *
 * Recalculation is queued and run once on shutdown, so a bulk edit that touches
 * twenty chapters still only renumbers once.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post statuses that occupy a chapter number.
 *
 * Drafts are excluded so the published sequence a reader sees has no holes in
 * it. Filter this to count drafts if you would rather numbers stay pinned while
 * you write ahead.
 *
 * @return string[]
 */
function akw_counted_statuses() {
	return apply_filters( 'akw_counted_statuses', array( 'publish', 'future', 'private' ) );
}

/**
 * Every series (the top-level terms).
 *
 * @return WP_Term[]
 */
function akw_get_all_series() {
	$series = get_terms(
		array(
			'taxonomy'   => AKW_SERIES,
			'parent'     => 0,
			'hide_empty' => false,
		)
	);

	return is_wp_error( $series ) ? array() : $series;
}

/**
 * A series' arcs, in reading order.
 *
 * Ordered by the akw_order term meta, falling back to a natural-order name
 * compare so arcs without an explicit order still land somewhere sensible.
 *
 * @param int $series_id Top-level term ID.
 * @return WP_Term[]
 */
function akw_get_arcs( $series_id ) {
	$arcs = get_terms(
		array(
			'taxonomy'   => AKW_SERIES,
			'parent'     => (int) $series_id,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $arcs ) ) {
		return array();
	}

	usort(
		$arcs,
		function ( $a, $b ) {
			$order_a = (int) get_term_meta( $a->term_id, 'akw_order', true );
			$order_b = (int) get_term_meta( $b->term_id, 'akw_order', true );

			if ( $order_a === $order_b ) {
				return strnatcasecmp( $a->name, $b->name );
			}

			return $order_a <=> $order_b;
		}
	);

	return $arcs;
}

/**
 * Chapter IDs in one arc, in reading order.
 *
 * include_children is off because an arc has no children; without it a series
 * term would sweep up everything beneath it.
 *
 * @param int $arc_id Arc term ID.
 * @return int[]
 */
function akw_get_arc_chapters( $arc_id ) {
	return get_posts(
		array(
			'post_type'      => AKW_CHAPTER,
			'post_status'    => akw_counted_statuses(),
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
				'ID'         => 'ASC',
			),
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy'         => AKW_SERIES,
					'field'            => 'term_id',
					'terms'            => (int) $arc_id,
					'include_children' => false,
				),
			),
		)
	);
}

/**
 * Renumber one series from scratch.
 *
 * @param int $series_id Top-level term ID.
 * @return int Total chapters in the series.
 */
function akw_recalculate_series( $series_id ) {
	$running  = 0;
	$position = 0;

	foreach ( akw_get_arcs( $series_id ) as $arc ) {
		++$position;

		update_term_meta( $arc->term_id, 'akw_arc_position', $position );
		update_term_meta( $arc->term_id, 'akw_chapter_offset', $running );

		$chapters = akw_get_arc_chapters( $arc->term_id );
		$index    = 0;

		foreach ( $chapters as $chapter_id ) {
			++$index;
			update_post_meta( $chapter_id, 'akw_arc_index', $index );
			update_post_meta( $chapter_id, 'akw_chapter_number', $running + $index );
		}

		update_term_meta( $arc->term_id, 'akw_chapter_count', count( $chapters ) );
		$running += count( $chapters );
	}

	update_term_meta( $series_id, 'akw_chapter_total', $running );

	return $running;
}

/**
 * Renumber every series.
 */
function akw_recalculate_all() {
	foreach ( akw_get_all_series() as $series ) {
		akw_recalculate_series( $series->term_id );
	}
}

/**
 * Queue a renumber for the end of this request.
 *
 * Coalescing matters: moving a chapter between arcs fires several hooks, and
 * each one would otherwise trigger a full pass.
 */
function akw_queue_recalculation() {
	static $queued = false;

	if ( $queued ) {
		return;
	}

	$queued = true;
	add_action( 'shutdown', 'akw_recalculate_all' );
}

/**
 * Keep a chapter's series term attached alongside its arc term.
 *
 * WordPress does not add parent terms implicitly, and without the series on the
 * post every series-wide query would have to expand the arc list first.
 *
 * @param int $post_id Chapter ID.
 */
function akw_normalize_chapter_terms( $post_id ) {
	static $in_progress = array();

	if ( isset( $in_progress[ $post_id ] ) ) {
		return;
	}

	$in_progress[ $post_id ] = true;

	$terms = wp_get_object_terms( $post_id, AKW_SERIES );

	if ( is_wp_error( $terms ) || ! $terms ) {
		unset( $in_progress[ $post_id ] );
		return;
	}

	$term_ids = array();
	foreach ( $terms as $term ) {
		$term_ids[ $term->term_id ] = true;

		if ( $term->parent ) {
			$term_ids[ $term->parent ] = true;
		}
	}

	$term_ids = array_map( 'intval', array_keys( $term_ids ) );

	if ( count( $term_ids ) !== count( $terms ) ) {
		wp_set_object_terms( $post_id, $term_ids, AKW_SERIES, false );
	}

	unset( $in_progress[ $post_id ] );
}

/**
 * Renumber after a chapter is written.
 *
 * @param int $post_id Chapter ID.
 */
function akw_on_chapter_saved( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	akw_normalize_chapter_terms( $post_id );
	akw_queue_recalculation();
}
add_action( 'save_post_' . AKW_CHAPTER, 'akw_on_chapter_saved', 20 );

/**
 * The block editor sets terms after save_post, so catch the REST write too.
 *
 * @param WP_Post $post Chapter.
 */
function akw_on_chapter_rest_saved( $post ) {
	akw_normalize_chapter_terms( $post->ID );
	akw_queue_recalculation();
}
add_action( 'rest_after_insert_' . AKW_CHAPTER, 'akw_on_chapter_rest_saved' );

/**
 * Renumber when a chapter leaves or re-enters the sequence.
 *
 * @param int $post_id Post ID.
 */
function akw_on_chapter_status_change( $post_id ) {
	if ( AKW_CHAPTER === get_post_type( $post_id ) ) {
		akw_queue_recalculation();
	}
}
add_action( 'trashed_post', 'akw_on_chapter_status_change' );
add_action( 'untrashed_post', 'akw_on_chapter_status_change' );

/**
 * A deleted post's type is gone by the time later hooks run, so read it here.
 *
 * @param int          $post_id Post ID.
 * @param WP_Post|null $post    Post object, on WordPress 6.2+.
 */
function akw_on_chapter_deleted( $post_id, $post = null ) {
	$type = $post instanceof WP_Post ? $post->post_type : get_post_type( $post_id );

	if ( AKW_CHAPTER === $type ) {
		akw_queue_recalculation();
	}
}
add_action( 'deleted_post', 'akw_on_chapter_deleted', 10, 2 );

// Arc added, renamed, reordered or removed: the offsets below it all shift.
add_action( 'created_' . AKW_SERIES, 'akw_queue_recalculation' );
add_action( 'edited_' . AKW_SERIES, 'akw_queue_recalculation' );
add_action( 'delete_' . AKW_SERIES, 'akw_queue_recalculation' );

/**
 * Catch arc reassignment that never goes through a post save.
 *
 * WP-CLI, the REST term endpoints and importers all set terms directly, so
 * save_post alone would leave those chapters unnumbered.
 *
 * @param int    $object_id Post ID.
 * @param array  $terms     Terms set (unused).
 * @param array  $tt_ids    Term taxonomy IDs (unused).
 * @param string $taxonomy  Taxonomy.
 */
function akw_on_object_terms_set( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( AKW_SERIES !== $taxonomy || AKW_CHAPTER !== get_post_type( $object_id ) ) {
		return;
	}

	akw_normalize_chapter_terms( $object_id );
	akw_queue_recalculation();
}
add_action( 'set_object_terms', 'akw_on_object_terms_set', 10, 4 );

/**
 * Catch arc reordering written straight to term meta.
 *
 * The term edit screen fires edited_akw_series, but update_term_meta() on its
 * own does not, so WP-CLI and programmatic reordering would leave every offset
 * below the moved arc stale.
 *
 * @param int    $meta_id   Meta row ID (unused).
 * @param int    $term_id   Term ID.
 * @param string $meta_key  Meta key.
 */
function akw_on_arc_order_changed( $meta_id, $term_id, $meta_key ) {
	if ( 'akw_order' !== $meta_key ) {
		return;
	}

	$term = get_term( $term_id );

	if ( $term instanceof WP_Term && AKW_SERIES === $term->taxonomy ) {
		akw_queue_recalculation();
	}
}
add_action( 'added_term_meta', 'akw_on_arc_order_changed', 10, 3 );
add_action( 'updated_term_meta', 'akw_on_arc_order_changed', 10, 3 );
