<?php
/**
 * Reading the chapter structure, and rendering the parts of it that more than
 * one template needs.
 *
 * Everything here derives from one thing: publication order. Chapter 1 is the
 * oldest counted post, and each arc's position follows the chapter that opens
 * it. Nothing is stored, so there is nothing to renumber and nothing to drift —
 * the previous stored-meta scheme existed only because arcs used to carry a
 * manual order, and tags have no such field.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The whole chapter structure, resolved once per request.
 *
 * Two queries: the ordered chapter IDs, then every arc term attached to them.
 * Deriving each chapter's number on its own would mean a query per table row,
 * which is why this is built in one pass and memoized.
 *
 * @return array {
 *     @type int[]     $order        Chapter IDs in reading order.
 *     @type int[]     $numbers      Chapter ID => its number, 1-based.
 *     @type WP_Term[] $arc          Chapter ID => its arc.
 *     @type int[]     $arc_index    Chapter ID => its position inside its arc.
 *     @type int[]     $arc_position Arc term ID => which arc it is, 1-based.
 *     @type array[]   $arc_chapters Arc term ID => its chapter IDs, in order.
 * }
 */
function akw_chapter_structure() {
	static $structure = null;

	if ( null !== $structure ) {
		return $structure;
	}

	$structure = array(
		'order'        => array(),
		'numbers'      => array(),
		'arc'          => array(),
		'arc_index'    => array(),
		'arc_position' => array(),
		'arc_chapters' => array(),
	);

	$ids = get_posts(
		array(
			'post_type'      => AKW_CHAPTER,
			'post_status'    => akw_counted_statuses(),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => array(
				'date' => 'ASC',
				'ID'   => 'ASC',
			),
		)
	);

	if ( ! $ids ) {
		return $structure;
	}

	$arcs     = akw_map_chapter_arcs( $ids );
	$number   = 0;
	$position = 0;

	foreach ( $ids as $id ) {
		$id = (int) $id;

		$structure['order'][]        = $id;
		$structure['numbers'][ $id ] = ++$number;

		if ( ! isset( $arcs[ $id ] ) ) {
			continue;
		}

		$arc     = $arcs[ $id ];
		$term_id = (int) $arc->term_id;

		$structure['arc'][ $id ] = $arc;

		// First chapter of this arc: that is what fixes the arc's position.
		if ( ! isset( $structure['arc_position'][ $term_id ] ) ) {
			$structure['arc_position'][ $term_id ] = ++$position;
			$structure['arc_chapters'][ $term_id ] = array();
		}

		$structure['arc_chapters'][ $term_id ][] = $id;
		$structure['arc_index'][ $id ]           = count( $structure['arc_chapters'][ $term_id ] );
	}

	return $structure;
}

/**
 * Each chapter's arc, in one query.
 *
 * A chapter is meant to carry a single tag. When one carries several, the
 * oldest tag wins — term_id order — so the answer is at least stable between
 * requests, which alphabetical order would not be as arcs are added.
 *
 * @param int[] $ids Chapter IDs.
 * @return WP_Term[] Chapter ID => arc term. Untagged chapters are absent.
 */
function akw_map_chapter_arcs( $ids ) {
	$terms = wp_get_object_terms(
		$ids,
		AKW_ARC,
		array(
			'fields'  => 'all_with_object_id',
			'orderby' => 'term_id',
			'order'   => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$map = array();

	foreach ( $terms as $term ) {
		$object_id = (int) $term->object_id;

		if ( ! isset( $map[ $object_id ] ) ) {
			$map[ $object_id ] = $term;
		}
	}

	return $map;
}

/**
 * A chapter's number.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return int Zero for a draft, which holds no place in the sequence.
 */
function akw_get_chapter_number( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return 0;
	}

	$numbers = akw_chapter_structure()['numbers'];

	return isset( $numbers[ $post->ID ] ) ? $numbers[ $post->ID ] : 0;
}

/**
 * How many chapters the run holds.
 *
 * @return int
 */
function akw_get_chapter_total() {
	return count( akw_chapter_structure()['order'] );
}

/**
 * The arc a chapter belongs to.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return WP_Term|null Null for an untagged chapter.
 */
function akw_get_arc( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return null;
	}

	$arcs = akw_chapter_structure()['arc'];

	if ( isset( $arcs[ $post->ID ] ) ) {
		return $arcs[ $post->ID ];
	}

	// A draft is not in the structure at all, but the editor still wants to
	// show which arc it is being written into.
	$own = akw_map_chapter_arcs( array( $post->ID ) );

	return isset( $own[ $post->ID ] ) ? $own[ $post->ID ] : null;
}

/**
 * A chapter's position inside its own arc.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return int
 */
function akw_get_arc_index( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return 0;
	}

	$indexes = akw_chapter_structure()['arc_index'];

	return isset( $indexes[ $post->ID ] ) ? $indexes[ $post->ID ] : 0;
}

/**
 * Which arc this is in the run: 1, 2, 3…
 *
 * @param WP_Term|int|null $arc Arc term.
 * @return int Zero for a tag no counted chapter carries.
 */
function akw_get_arc_position( $arc ) {
	$arc = is_numeric( $arc ) ? get_term( (int) $arc, AKW_ARC ) : $arc;

	if ( ! $arc instanceof WP_Term ) {
		return 0;
	}

	$positions = akw_chapter_structure()['arc_position'];

	return isset( $positions[ $arc->term_id ] ) ? $positions[ $arc->term_id ] : 0;
}

/**
 * Chapter IDs in one arc, in reading order.
 *
 * @param WP_Term|int $arc Arc term or term ID.
 * @return int[]
 */
function akw_get_arc_chapters( $arc ) {
	$term_id = $arc instanceof WP_Term ? (int) $arc->term_id : (int) $arc;
	$chapters = akw_chapter_structure()['arc_chapters'];

	return isset( $chapters[ $term_id ] ) ? $chapters[ $term_id ] : array();
}

/**
 * A chapter's number on its own: "Chapter 47".
 *
 * Empty for an unnumbered chapter, same as akw_get_chapter_label().
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string
 */
function akw_get_chapter_number_label( $post = null ) {
	$number = akw_get_chapter_number( $post );

	if ( ! $number ) {
		return '';
	}

	/* translators: %d: chapter number. */
	return sprintf( __( 'Chapter %d', 'kungfu_2026' ), $number );
}

/**
 * Human label for a chapter, e.g. "Arc 2, Chapter 47".
 *
 * The chapter number is the run-wide one, which is the whole point: arc 2
 * chapter 1 reads as chapter 47, not chapter 1.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string Empty for a draft; callers test for that.
 */
function akw_get_chapter_label( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$arc    = akw_get_arc( $post );
	$number = akw_get_chapter_number( $post );
	$arc_no = akw_get_arc_position( $arc );

	// Still a draft: there is no number to show, and "Chapter 0" is worse than
	// nothing.
	if ( ! $number ) {
		return apply_filters( 'akw_chapter_label', '', $post, $arc, 0 );
	}

	if ( $arc_no ) {
		/* translators: 1: arc number, 2: chapter number. */
		$label = sprintf( __( 'Arc %1$d, Chapter %2$d', 'kungfu_2026' ), $arc_no, $number );
	} else {
		$label = akw_get_chapter_number_label( $post );
	}

	return apply_filters( 'akw_chapter_label', $label, $post, $arc, $number );
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
 * Every chapter a visitor can read, in reading order.
 *
 * @return int[] Chapter IDs.
 */
function akw_get_chapter_index() {
	return array_values( array_filter( akw_chapter_structure()['order'], 'akw_is_chapter_visible' ) );
}

/**
 * The chapter a reader goes to next, or the one before.
 *
 * Walks the visible index rather than WordPress' own get_adjacent_post(),
 * which orders by date within a taxonomy at best and knows nothing about arcs.
 * Reading order here spans arcs, so the last chapter of arc 1 is followed by
 * the first of arc 2.
 *
 * @param int|WP_Post|null $post   Chapter.
 * @param int              $offset 1 for the next chapter, -1 for the previous.
 * @return int|null Chapter ID, or null at either end of the run.
 */
function akw_get_adjacent_chapter( $post = null, $offset = 1 ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return null;
	}

	$index = akw_get_chapter_index();
	$at    = array_search( (int) $post->ID, $index, true );

	// A draft, or a chapter the visitor cannot read, is not in the index.
	if ( false === $at ) {
		return null;
	}

	$target = $at + (int) $offset;

	return isset( $index[ $target ] ) ? (int) $index[ $target ] : null;
}

/**
 * The next chapter in reading order.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return int|null Chapter ID, or null on the last chapter.
 */
function akw_get_next_chapter( $post = null ) {
	return akw_get_adjacent_chapter( $post, 1 );
}

/**
 * The chapters of an arc that the current visitor may read, in reading order.
 *
 * @param WP_Term|int $arc Arc term or term ID.
 * @return int[] Chapter IDs.
 */
function akw_get_visible_arc_chapters( $arc ) {
	return array_values( array_filter( akw_get_arc_chapters( $arc ), 'akw_is_chapter_visible' ) );
}

/**
 * The chapter table, as the front page and the arc archives both draw it.
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
			<?php foreach ( $chapters as $chapter_id ) : ?>
				<tr>
					<td class="chapter-table__number">
						<?php echo esc_html( number_format_i18n( akw_get_chapter_number( $chapter_id ) ) ); ?>
					</td>
					<td class="chapter-table__title">
						<a href="<?php echo esc_url( get_permalink( $chapter_id ) ); ?>" rel="bookmark"><?php echo esc_html( get_the_title( $chapter_id ) ); ?></a>
					</td>
					<?php
					if ( $show_arc ) :
						$arc = akw_get_arc( $chapter_id );
						?>
						<td class="chapter-table__arc">
							<?php if ( $arc ) : ?>
								<a href="<?php echo esc_url( get_term_link( $arc ) ); ?>"><?php echo esc_html( $arc->name ); ?></a>
							<?php else : ?>
								<?php // An untagged chapter still has a number; a dash keeps the column aligned without inventing an arc. ?>
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
 * Read arc archives in reading order rather than newest first.
 *
 * The table on tag.php builds its own order, but the loop still runs, and the
 * tag feed reads from it.
 *
 * @param WP_Query $query Query.
 */
function kungfu_2026_order_arc_archives( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tag() ) {
		return;
	}

	$query->set( 'orderby', array( 'date' => 'ASC', 'ID' => 'ASC' ) );
}
add_action( 'pre_get_posts', 'kungfu_2026_order_arc_archives' );
