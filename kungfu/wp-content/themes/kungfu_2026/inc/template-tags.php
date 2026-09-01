<?php
/**
 * Reading the chapter structure from templates, and rendering the parts of it
 * that more than one template needs.
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
 * A chapter's number on its own: "Chapter 47".
 *
 * The tree already says which arc a chapter sits in, so repeating it in every
 * row would only be noise. Empty for an unnumbered chapter, same as
 * akw_get_chapter_label().
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string
 */
function akw_get_chapter_number_label( $post = null ) {
	$number = akw_get_chapter_number( $post );

	if ( ! $number ) {
		return '';
	}

	/* translators: %d: series-wide chapter number. */
	return sprintf( __( 'Chapter %d', 'kungfu_2026' ), $number );
}

/**
 * Whether a visitor can actually open a chapter.
 *
 * akw_counted_statuses() includes scheduled and private chapters because they
 * hold a place in the numbering, but a public table of contents must not link
 * to something the reader would only be turned away from.
 *
 * @param int $chapter_id Chapter ID.
 * @return bool
 */
function akw_is_chapter_visible( $chapter_id ) {
	return 'publish' === get_post_status( $chapter_id ) || current_user_can( 'read_post', $chapter_id );
}

/**
 * The chapters of an arc that the current visitor may read, in reading order.
 *
 * @param int $arc_id Arc term ID.
 * @return int[] Chapter IDs.
 */
function akw_get_visible_arc_chapters( $arc_id ) {
	return array_values( array_filter( akw_get_arc_chapters( $arc_id ), 'akw_is_chapter_visible' ) );
}

/**
 * Chapters filed straight on a series, with no arc in between.
 *
 * Every chapter is meant to live under an arc, but one that carries only its
 * series term would otherwise be missing from the tree entirely — which is
 * exactly the state a chapter is in before its arc exists.
 *
 * Deliberately avoids akw_get_series_chapters(): that orders by
 * akw_chapter_number, and the meta join drops the very chapters this looks for,
 * since an unfiled chapter never got numbered.
 *
 * @param WP_Term|int $series Series term.
 * @return int[] Chapter IDs.
 */
function akw_get_unfiled_chapters( $series ) {
	$series = is_numeric( $series ) ? get_term( (int) $series, AKW_SERIES ) : $series;

	if ( ! $series instanceof WP_Term ) {
		return array();
	}

	$filed = array();

	foreach ( akw_get_arcs( $series->term_id ) as $arc ) {
		$filed = array_merge( $filed, akw_get_arc_chapters( $arc->term_id ) );
	}

	$in_series = get_posts(
		array(
			'post_type'      => AKW_CHAPTER,
			'post_status'    => akw_counted_statuses(),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
				'ID'         => 'ASC',
			),
			'tax_query'      => array(
				array(
					'taxonomy'         => AKW_SERIES,
					'field'            => 'term_id',
					'terms'            => $series->term_id,
					'include_children' => true,
				),
			),
		)
	);

	$unfiled = array_diff( $in_series, $filed );

	return array_values( array_filter( $unfiled, 'akw_is_chapter_visible' ) );
}

/**
 * Human label for a chapter, e.g. "Arc 2, Chapter 47".
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

	$arc    = akw_get_arc( $post );
	$series = akw_get_series( $post );
	$number = akw_get_chapter_number( $post );
	$arc_no = akw_get_arc_position( $arc );

	// Not filed under an arc, or still a draft: there is no number to show, and
	// "Chapter 0" is worse than nothing. Callers test for an empty string.
	if ( ! $number ) {
		return apply_filters( 'akw_chapter_label', '', $post, $arc, $series, 0 );
	}

	if ( $arc_no ) {
		/* translators: 1: arc number, 2: series-wide chapter number. */
		$label = sprintf( __( 'Arc %1$d, Chapter %2$d', 'kungfu_2026' ), $arc_no, $number );
	} else {
		$label = akw_get_chapter_number_label( $post );
	}

	return apply_filters( 'akw_chapter_label', $label, $post, $arc, $series, $number );
}

/**
 * Every chapter a visitor can read, in reading order.
 *
 * Series by series, arc by arc, with anything not yet filed under an arc after
 * its series' arcs — an unfiled chapter has no number, so there is nowhere in
 * the sequence to interleave it.
 *
 * @return int[] Chapter IDs.
 */
function akw_get_chapter_index() {
	$chapters = array();

	foreach ( akw_get_all_series() as $series ) {
		$chapters = array_merge( $chapters, akw_get_series_index( $series ) );
	}

	// A chapter filed under two series would otherwise be listed twice.
	return array_values( array_unique( $chapters ) );
}

/**
 * One series' readable chapters, in reading order.
 *
 * Arc by arc, then whatever is not filed under an arc yet.
 *
 * @param WP_Term|int $series Series term.
 * @return int[] Chapter IDs.
 */
function akw_get_series_index( $series ) {
	$series = is_numeric( $series ) ? get_term( (int) $series, AKW_SERIES ) : $series;

	if ( ! $series instanceof WP_Term ) {
		return array();
	}

	$chapters = array();

	foreach ( akw_get_arcs( $series->term_id ) as $arc ) {
		$chapters = array_merge( $chapters, akw_get_visible_arc_chapters( $arc->term_id ) );
	}

	return array_merge( $chapters, akw_get_unfiled_chapters( $series ) );
}

/**
 * The chapter table, as the front page and the series archives both draw it.
 *
 * @param int[] $chapters Chapter IDs, already in reading order.
 * @param bool  $show_arc Whether to include the Arc column. Pass false inside a
 *                        single arc, where every row would repeat one name.
 */
function akw_the_chapter_table( $chapters, $show_arc = true ) {
	if ( ! $chapters ) {
		echo '<p class="empty">' . esc_html__( 'No chapters published yet.', 'kungfu_2026' ) . '</p>';
		return;
	}
	?>
	<table class="chapter-table">
		<caption class="screen-reader-text"><?php esc_html_e( 'Chapters', 'kungfu_2026' ); ?></caption>
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Chapter', 'kungfu_2026' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Title', 'kungfu_2026' ); ?></th>
				<?php if ( $show_arc ) : ?>
					<th scope="col"><?php esc_html_e( 'Arc', 'kungfu_2026' ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $chapters as $chapter_id ) :
				$number = akw_get_chapter_number( $chapter_id );
				?>
				<tr>
					<td class="chapter-table__number">
						<?php
						// An unnumbered chapter is one not filed under an arc yet; a
						// dash keeps the column aligned without inventing a number.
						echo $number ? esc_html( number_format_i18n( $number ) ) : '&mdash;';
						?>
					</td>
					<td class="chapter-table__title">
						<a href="<?php echo esc_url( get_permalink( $chapter_id ) ); ?>" rel="bookmark"><?php echo esc_html( get_the_title( $chapter_id ) ); ?></a>
					</td>
					<?php
					if ( $show_arc ) :
						$arc = akw_get_chapter_arc_or_series( $chapter_id );
						?>
						<td class="chapter-table__arc">
							<?php if ( $arc ) : ?>
								<a href="<?php echo esc_url( get_term_link( $arc ) ); ?>"><?php echo esc_html( $arc->name ); ?></a>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * What the Arc column shows: a chapter's arc, or its series while it has no
 * arc — better than an empty cell for a chapter that has not been filed yet.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return WP_Term|null
 */
function akw_get_chapter_arc_or_series( $post = null ) {
	$arc = akw_get_arc( $post );

	return $arc ? $arc : akw_get_series( $post );
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
 * Order series and arc archives by the series-wide number.
 *
 * Only those: the blog index and the feed are left alone, where newest-first is
 * what a reader coming back expects.
 *
 * @param WP_Query $query Query.
 */
function kungfu_2026_order_chapter_archives( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_tax( AKW_SERIES ) ) {
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
