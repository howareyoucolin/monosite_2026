<?php
/**
 * An arc: the chapters it holds, in reading order.
 *
 * The Arc column is dropped — every row would name the same arc — so the table
 * is just number and title.
 *
 * @package kungfu_2026
 */

get_header();

$akw_arc      = get_queried_object();
$akw_chapters = $akw_arc instanceof WP_Term ? akw_get_visible_arc_chapters( $akw_arc ) : array();
$akw_arc_no   = akw_get_arc_position( $akw_arc );
?>

<header class="archive-head">
	<h1 class="archive-title"><?php single_term_title(); ?></h1>

	<?php if ( $akw_arc_no ) : ?>
		<p class="archive-meta">
			<?php
			printf(
				/* translators: 1: arc number, 2: number of chapters in the arc. */
				esc_html__( 'Arc %1$s &middot; %2$s', 'kungfu_2026' ),
				esc_html( number_format_i18n( $akw_arc_no ) ),
				esc_html(
					sprintf(
						/* translators: %s: number of chapters. */
						_n( '%s chapter', '%s chapters', count( $akw_chapters ), 'kungfu_2026' ),
						number_format_i18n( count( $akw_chapters ) )
					)
				)
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $akw_arc instanceof WP_Term && $akw_arc->description ) : ?>
		<p class="archive-desc"><?php echo esc_html( $akw_arc->description ); ?></p>
	<?php endif; ?>
</header>

<?php
akw_the_chapter_table( $akw_chapters, false );

get_footer();
