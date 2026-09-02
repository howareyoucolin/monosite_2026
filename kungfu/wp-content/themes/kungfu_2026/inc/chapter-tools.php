<?php
/**
 * What a chapter page carries besides the chapter: a letter count, a button
 * that copies the whole thing, and a link on to the next one.
 *
 * Both follow fk.369usa.com, which is the site this theme is a remake of. The
 * behaviour there is reproduced; the mechanics are not, and the differences are
 * noted where they occur.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How many characters of prose a rendered chapter holds.
 *
 * "Letter count" is the reference site's own label, and like the reference this
 * counts characters rather than words — which is the useful measure for the
 * Chinese version, where there are no spaces to count words between.
 *
 * The reference reads .entry-content's innerText in the browser. Doing it in
 * PHP means no layout shift and a number that survives JavaScript being off,
 * at the cost of having to flatten the markup by hand: block ends become
 * newlines, because wp_strip_all_tags() alone would run "<p>a</p><p>b</p>"
 * together into "ab", and runs of whitespace then collapse so that the
 * indentation of the markup does not inflate the total.
 *
 * @param string $html Rendered chapter HTML.
 * @return int
 */
function akw_count_letters( $html ) {
	$text = preg_replace( '#<(?:br|/p|/h[1-6]|/li|/div|/blockquote|/figcaption)[^>]*>#i', "\n", (string) $html );
	$text = wp_strip_all_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
	$text = preg_replace( '/\s+/u', ' ', $text );

	return mb_strlen( trim( (string) $text ) );
}

/**
 * The row above the chapter body: how long the chapter is, and a button to
 * take it.
 *
 * Count and button share a line because they are the same kind of thing — a
 * small strip of chrome about the chapter rather than part of it. The count is
 * shaped like the button and left flat, so the one that does something is the
 * one that looks pressable.
 *
 * The button comes first in the markup as well as on the left of the row, so
 * the tab order reaches the control before the label describing the chapter.
 *
 * Three deliberate departures from fk.369usa.com, all in the mechanics rather
 * than the result:
 *
 * - A real <button>, not a clickable <div>, so it can be tabbed to and pressed
 *   with the keyboard.
 * - No hidden <textarea> holding a second copy of the chapter. The script reads
 *   the text out of the page instead, which halves the weight of a long chapter
 *   and — the reason it matters here — copies whichever language is on screen
 *   without the server having to render the body twice.
 * - The confirmation swaps the icon for a tick instead of raising an alert()
 *   the reader has to dismiss.
 * - The button is an icon, so its name lives in aria-label and title rather
 *   than in visible text. Both are kept in step with the state, and the tick is
 *   also announced through the status region beside it — an icon that changes
 *   silently is a confirmation only sighted users get.
 *
 * @param int $count Character count, from akw_count_letters().
 */
function akw_the_chapter_tools( $count ) {
	?>
	<div class="chapter-tools">
		<?php
		$akw_copy_label   = __( 'Copy Title + Content', 'kungfu_2026' );
		$akw_copied_label = __( 'Copied!', 'kungfu_2026' );
		?>
		<button
			type="button"
			class="copy-button"
			aria-label="<?php echo esc_attr( $akw_copy_label ); ?>"
			title="<?php echo esc_attr( $akw_copy_label ); ?>"
			data-copy-label="<?php echo esc_attr( $akw_copy_label ); ?>"
			data-copied-label="<?php echo esc_attr( $akw_copied_label ); ?>"
		>
			<svg class="copy-button__icon copy-button__icon--copy" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
				<rect x="9" y="9" width="13" height="13" rx="2"/>
				<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
			</svg>
			<svg class="copy-button__icon copy-button__icon--done" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
				<polyline points="20 6 9 17 4 12"/>
			</svg>
		</button>

		<?php if ( $count > 0 ) : ?>
			<p class="chapter-tools__count">
				<?php
				printf(
					/* translators: %s: number of characters in the chapter. */
					esc_html__( 'Total letter count: %s', 'kungfu_2026' ),
					esc_html( number_format_i18n( $count ) )
				);
				?>
			</p>
		<?php endif; ?>

		<span class="chapter-tools__status" role="status" aria-live="polite"></span>
	</div>
	<?php
}

/**
 * The link on to the next chapter, for the foot of the page.
 *
 * Prints nothing on the last chapter — a dead "next" is worse than no next.
 *
 * @param int|WP_Post|null $post Chapter.
 */
function akw_the_next_chapter_link( $post = null ) {
	$next = akw_get_next_chapter( $post );

	if ( ! $next ) {
		return;
	}
	?>
	<nav class="chapter-next" aria-label="<?php esc_attr_e( 'Next chapter', 'kungfu_2026' ); ?>">
		<a class="chapter-next__link" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next">
			<span class="chapter-next__label"><?php esc_html_e( 'Next chapter', 'kungfu_2026' ); ?></span>
			<span class="chapter-next__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
		</a>
	</nav>
	<?php
}

/**
 * Load the copy script, on chapter pages only.
 */
function kungfu_2026_chapter_tools_assets() {
	if ( ! is_singular( AKW_CHAPTER ) ) {
		return;
	}

	wp_enqueue_script(
		'kungfu-2026-copy-chapter',
		get_theme_file_uri( 'js/copy-chapter.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_localize_script(
		'kungfu-2026-copy-chapter',
		'akwCopy',
		array(
			'copied' => __( 'Copied!', 'kungfu_2026' ),
			'status' => __( 'Chapter copied to clipboard', 'kungfu_2026' ),
			'failed' => __( 'Copy failed', 'kungfu_2026' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'kungfu_2026_chapter_tools_assets' );
