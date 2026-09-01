<?php
/**
 * Front page: the two series, and what is new in them.
 *
 * @package kungfu_2026
 */

get_header();

$akw_all_series = akw_get_all_series();
$akw_recent     = akw_get_recent_chapters( 8 );
?>


<?php if ( $akw_all_series ) : ?>
<section class="series-grid" aria-label="<?php esc_attr_e( 'Series', 'kungfu_2026' ); ?>">
	<?php
	foreach ( $akw_all_series as $akw_series ) :
		$akw_lang    = akw_get_series_language( $akw_series );
		$akw_total   = (int) get_term_meta( $akw_series->term_id, 'akw_chapter_total', true );
		$akw_arcs    = akw_get_arcs( $akw_series->term_id );
		$akw_first   = akw_get_first_chapter( $akw_series );
		$akw_latest  = akw_get_latest_chapter( $akw_series );
		?>
		<article class="series-card" lang="<?php echo esc_attr( 'zh' === $akw_lang ? 'zh-Hans' : 'en' ); ?>">
			<header class="series-card__head">
				<h2 class="series-card__title">
					<a href="<?php echo esc_url( get_term_link( $akw_series ) ); ?>"><?php echo esc_html( $akw_series->name ); ?></a>
				</h2>
				<span class="badge badge--<?php echo esc_attr( $akw_lang ); ?>">
					<?php echo esc_html( 'zh' === $akw_lang ? __( 'Chinese', 'kungfu_2026' ) : __( 'English', 'kungfu_2026' ) ); ?>
				</span>
			</header>

			<?php if ( $akw_series->description ) : ?>
				<p class="series-card__desc"><?php echo esc_html( $akw_series->description ); ?></p>
			<?php endif; ?>

			<p class="series-card__stats">
				<?php
				printf(
					/* translators: 1: number of arcs, 2: number of chapters. */
					esc_html( _n( '%1$d arc', '%1$d arcs', count( $akw_arcs ), 'kungfu_2026' ) ) . ' &middot; ' .
					esc_html( _n( '%2$d chapter', '%2$d chapters', $akw_total, 'kungfu_2026' ) ),
					count( $akw_arcs ),
					$akw_total
				);
				?>
			</p>

			<?php if ( $akw_latest ) : ?>
				<p class="series-card__latest">
					<span class="series-card__latest-label"><?php esc_html_e( 'Latest', 'kungfu_2026' ); ?></span>
					<?php $akw_latest_label = akw_get_chapter_label( $akw_latest ); ?>
					<a href="<?php echo esc_url( get_permalink( $akw_latest ) ); ?>">
						<?php echo esc_html( $akw_latest_label ? $akw_latest_label . ' — ' . get_the_title( $akw_latest ) : get_the_title( $akw_latest ) ); ?>
					</a>
				</p>
			<?php endif; ?>

			<p class="series-card__actions">
				<?php if ( $akw_first ) : ?>
					<a class="button" href="<?php echo esc_url( get_permalink( $akw_first ) ); ?>"><?php esc_html_e( 'Start reading', 'kungfu_2026' ); ?></a>
				<?php endif; ?>
				<a class="button button--quiet" href="<?php echo esc_url( get_term_link( $akw_series ) ); ?>"><?php esc_html_e( 'All chapters', 'kungfu_2026' ); ?></a>
			</p>

			<?php if ( ! $akw_total ) : ?>
				<p class="empty"><?php esc_html_e( 'No chapters published yet.', 'kungfu_2026' ); ?></p>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</section>
<?php else : ?>
	<p class="empty">
		<?php esc_html_e( 'No series yet. Add one under Series & Arcs, then file chapters beneath its arcs.', 'kungfu_2026' ); ?>
	</p>
<?php endif; ?>

<?php if ( $akw_recent ) : ?>
<section class="recent">
	<h2 class="section-title"><?php esc_html_e( 'Latest chapters', 'kungfu_2026' ); ?></h2>
	<ul class="recent__list">
		<?php foreach ( $akw_recent as $akw_chapter_id ) : ?>
			<?php $akw_chapter_series = akw_get_series( $akw_chapter_id ); ?>
			<li class="recent__item">
				<?php $akw_chapter_label = akw_get_chapter_label( $akw_chapter_id ); ?>
				<a class="recent__link" href="<?php echo esc_url( get_permalink( $akw_chapter_id ) ); ?>">
					<?php if ( $akw_chapter_label ) : ?>
						<span class="recent__number"><?php echo esc_html( $akw_chapter_label ); ?></span>
					<?php endif; ?>
					<span class="recent__title"><?php echo esc_html( get_the_title( $akw_chapter_id ) ); ?></span>
				</a>
				<?php if ( $akw_chapter_series ) : ?>
					<span class="recent__series"><?php echo esc_html( $akw_chapter_series->name ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<?php
get_footer();
