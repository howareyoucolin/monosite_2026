<?php
/**
 * A series or one of its arcs: the chapters it holds, in reading order.
 *
 * Both levels of the taxonomy land here. An arc lists only its own chapters and
 * drops the Arc column, since every row would name the same arc; a series lists
 * every arc's chapters, keeping the column to mark where one arc ends.
 *
 * @package kungfu_2026
 */

get_header();

$akw_term   = get_queried_object();
$akw_is_arc = $akw_term instanceof WP_Term && $akw_term->parent;
$akw_series = $akw_is_arc ? get_term( $akw_term->parent, AKW_SERIES ) : $akw_term;
$akw_arc_no = $akw_is_arc ? akw_get_arc_position( $akw_term ) : 0;
?>

<header class="archive-head">
	<h1 class="archive-title"><?php echo esc_html( $akw_term->name ); ?></h1>

	<?php if ( $akw_is_arc && $akw_series instanceof WP_Term && ! is_wp_error( $akw_series ) ) : ?>
		<p class="archive-meta">
			<?php
			printf(
				/* translators: 1: arc number, 2: link to the series the arc belongs to. */
				esc_html__( 'Arc %1$s of %2$s', 'kungfu_2026' ),
				esc_html( $akw_arc_no ? number_format_i18n( $akw_arc_no ) : '—' ),
				'<a href="' . esc_url( get_term_link( $akw_series ) ) . '">' . esc_html( $akw_series->name ) . '</a>'
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $akw_term->description ) : ?>
		<p class="archive-desc"><?php echo esc_html( $akw_term->description ); ?></p>
	<?php endif; ?>
</header>

<?php
if ( $akw_is_arc ) {
	akw_the_chapter_table( akw_get_visible_arc_chapters( $akw_term->term_id ), false );
} else {
	akw_the_chapter_table( akw_get_series_index( $akw_term ) );
}

get_footer();
