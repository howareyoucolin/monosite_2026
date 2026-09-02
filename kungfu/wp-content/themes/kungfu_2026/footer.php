<?php
/**
 * Closing markup, and the credit line.
 *
 * The year comes from date_i18n() rather than being written in, so the line
 * does not quietly go stale on 1 January.
 *
 * The back-to-top button lives here rather than in header.php because it is
 * position:fixed — where it sits in the markup is irrelevant to where it
 * paints, and last in the document keeps it last in the tab order.
 *
 * @package kungfu_2026
 */

?>
</div><!-- .site -->

<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="site-footer__copyright">
			<?php
			printf(
				/* translators: 1: current year. 2: link to the site's builder. */
				esc_html__( '&copy; %1$s, built by %2$s, all rights reserved.', 'kungfu_2026' ),
				esc_html( date_i18n( 'Y' ) ),
				sprintf(
					// rel=noopener with target=_blank: without it the new tab can reach back into this one.
					'<a class="site-footer__link" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url( 'https://369usa.com/' ),
					esc_html__( '369USA', 'kungfu_2026' )
				)
			);
			?>
		</p>
	</div>
</footer>

<?php
$akw_to_top = __( 'Back to top', 'kungfu_2026' );
?>
<button type="button" class="to-top" aria-label="<?php echo esc_attr( $akw_to_top ); ?>" title="<?php echo esc_attr( $akw_to_top ); ?>">
	<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
		<line x1="12" y1="19" x2="12" y2="6"/>
		<polyline points="5 13 12 6 19 13"/>
	</svg>
</button>

<?php wp_footer(); ?>
</body>
</html>
