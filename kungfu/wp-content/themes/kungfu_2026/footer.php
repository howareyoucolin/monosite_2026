<?php
/**
 * Closing markup, and the credit line.
 *
 * The year comes from date_i18n() rather than being written in, so the line
 * does not quietly go stale on 1 January.
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

<?php wp_footer(); ?>
</body>
</html>
