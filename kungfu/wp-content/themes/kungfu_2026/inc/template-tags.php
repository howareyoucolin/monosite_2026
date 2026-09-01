<?php
/**
 * Reading the chapter structure from templates.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The arc a chapter belongs to.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return WP_Term|null
 */
function akw_get_arc( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return null;
	}

	$terms = wp_get_object_terms( $post->ID, AKW_SERIES );

	if ( is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		if ( $term->parent ) {
			return $term;
		}
	}

	return null;
}

/**
 * The series a chapter belongs to.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return WP_Term|null
 */
function akw_get_series( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return null;
	}

	$terms = wp_get_object_terms( $post->ID, AKW_SERIES );

	if ( is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		if ( ! $term->parent ) {
			return $term;
		}
	}

	// Arc present but series missing: walk up rather than give up.
	$arc = akw_get_arc( $post );

	if ( $arc ) {
		$parent = get_term( $arc->parent, AKW_SERIES );

		if ( $parent && ! is_wp_error( $parent ) ) {
			return $parent;
		}
	}

	return null;
}

/**
 * A chapter's number within its whole series — the continuous one.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return int Zero if the chapter has not been filed under an arc yet.
 */
function akw_get_chapter_number( $post = null ) {
	$post = get_post( $post );

	return $post ? (int) get_post_meta( $post->ID, 'akw_chapter_number', true ) : 0;
}

/**
 * A chapter's position inside its own arc.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return int
 */
function akw_get_arc_index( $post = null ) {
	$post = get_post( $post );

	return $post ? (int) get_post_meta( $post->ID, 'akw_arc_index', true ) : 0;
}

/**
 * Which arc this is within its series: 1, 2, 3…
 *
 * @param WP_Term|int|null $arc Arc term.
 * @return int
 */
function akw_get_arc_position( $arc ) {
	$arc = is_numeric( $arc ) ? get_term( (int) $arc, AKW_SERIES ) : $arc;

	if ( ! $arc instanceof WP_Term ) {
		return 0;
	}

	return (int) get_term_meta( $arc->term_id, 'akw_arc_position', true );
}

/**
 * How many chapters precede an arc.
 *
 * @param WP_Term|int|null $arc Arc term.
 * @return int
 */
function akw_get_arc_offset( $arc ) {
	$arc = is_numeric( $arc ) ? get_term( (int) $arc, AKW_SERIES ) : $arc;

	if ( ! $arc instanceof WP_Term ) {
		return 0;
	}

	return (int) get_term_meta( $arc->term_id, 'akw_chapter_offset', true );
}

/**
 * A series' language: 'en' or 'zh'.
 *
 * @param WP_Term|int|null $series Series term.
 * @return string
 */
function akw_get_series_language( $series ) {
	$series = is_numeric( $series ) ? get_term( (int) $series, AKW_SERIES ) : $series;

	if ( ! $series instanceof WP_Term ) {
		return 'en';
	}

	$lang = get_term_meta( $series->term_id, 'akw_lang', true );

	return $lang ? $lang : 'en';
}

/**
 * Human label for a chapter, e.g. "Arc 2, Chapter 47" or "第2卷 第47章".
 *
 * The chapter number is the series-wide one, which is the whole point: arc 2
 * chapter 1 reads as chapter 47, not chapter 1.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string
 */
function akw_get_chapter_label( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$arc      = akw_get_arc( $post );
	$series   = akw_get_series( $post );
	$number   = akw_get_chapter_number( $post );
	$arc_no   = akw_get_arc_position( $arc );
	$language = akw_get_series_language( $series );

	// Not filed under an arc, or still a draft: there is no number to show, and
	// "Chapter 0" is worse than nothing. Callers test for an empty string.
	if ( ! $number ) {
		return apply_filters( 'akw_chapter_label', '', $post, $arc, $series, 0 );
	}

	if ( 'zh' === $language ) {
		$label = $arc_no
			? sprintf( '第%1$d卷 第%2$d章', $arc_no, $number )
			: sprintf( '第%d章', $number );
	} elseif ( $arc_no ) {
		/* translators: 1: arc number, 2: series-wide chapter number. */
		$label = sprintf( __( 'Arc %1$d, Chapter %2$d', 'kungfu_2026' ), $arc_no, $number );
	} else {
		/* translators: %d: series-wide chapter number. */
		$label = sprintf( __( 'Chapter %d', 'kungfu_2026' ), $number );
	}

	return apply_filters( 'akw_chapter_label', $label, $post, $arc, $series, $number );
}

/**
 * Chapters of a series in reading order, grouped by arc.
 *
 * Handy for a table of contents.
 *
 * @param WP_Term|int $series Series term.
 * @return array[] One entry per arc: 'arc', 'position', 'offset', 'chapters' (post IDs).
 */
function akw_get_series_contents( $series ) {
	$series = is_numeric( $series ) ? get_term( (int) $series, AKW_SERIES ) : $series;

	if ( ! $series instanceof WP_Term ) {
		return array();
	}

	$contents = array();

	foreach ( akw_get_arcs( $series->term_id ) as $arc ) {
		$contents[] = array(
			'arc'      => $arc,
			'position' => akw_get_arc_position( $arc ),
			'offset'   => akw_get_arc_offset( $arc ),
			'chapters' => akw_get_arc_chapters( $arc->term_id ),
		);
	}

	return $contents;
}

/**
 * Order chapter archives by the series-wide number.
 *
 * @param WP_Query $query Query.
 */
function kungfu_2026_order_chapter_archives( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( AKW_CHAPTER ) || $query->is_tax( AKW_SERIES ) ) {
		$query->set( 'meta_key', 'akw_chapter_number' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'kungfu_2026_order_chapter_archives' );

/**
 * Chapters anywhere in a series, in reading order.
 *
 * Queries the series term with its children included, so it spans every arc.
 *
 * @param WP_Term|int $series Series term.
 * @param array       $args   Optional query overrides (number, order).
 * @return int[] Chapter IDs.
 */
function akw_get_series_chapters( $series, $args = array() ) {
	$series = is_numeric( $series ) ? get_term( (int) $series, AKW_SERIES ) : $series;

	if ( ! $series instanceof WP_Term ) {
		return array();
	}

	$defaults = array(
		'post_type'      => AKW_CHAPTER,
		'post_status'    => akw_counted_statuses(),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => 'akw_chapter_number',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'tax_query'      => array(
			array(
				'taxonomy'         => AKW_SERIES,
				'field'            => 'term_id',
				'terms'            => $series->term_id,
				'include_children' => true,
			),
		),
	);

	return get_posts( array_merge( $defaults, $args ) );
}

/**
 * The first chapter of a series, for a "start reading" link.
 *
 * @param WP_Term|int $series Series term.
 * @return int|null Chapter ID.
 */
function akw_get_first_chapter( $series ) {
	$chapters = akw_get_series_chapters( $series, array( 'posts_per_page' => 1 ) );

	return $chapters ? (int) $chapters[0] : null;
}

/**
 * The most recent chapter of a series, by chapter number.
 *
 * @param WP_Term|int $series Series term.
 * @return int|null Chapter ID.
 */
function akw_get_latest_chapter( $series ) {
	$chapters = akw_get_series_chapters(
		$series,
		array(
			'posts_per_page' => 1,
			'order'          => 'DESC',
		)
	);

	return $chapters ? (int) $chapters[0] : null;
}

/**
 * Most recently published chapters across every series.
 *
 * Ordered by publication date rather than chapter number, since this answers
 * "what is new" rather than "where am I".
 *
 * @param int $limit How many.
 * @return int[] Chapter IDs.
 */
function akw_get_recent_chapters( $limit = 8 ) {
	return get_posts(
		array(
			'post_type'      => AKW_CHAPTER,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);
}
