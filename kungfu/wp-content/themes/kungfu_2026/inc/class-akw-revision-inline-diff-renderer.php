<?php
/**
 * WordPress word-level diffs rendered as one readable, line-numbered stream.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep deleted and inserted words in one line instead of two table rows.
 */
class AKW_Revision_Inline_Diff_Renderer extends WP_Text_Diff_Renderer_Table {
	/** @var int Previous revision line counter. */
	private $previous_line = 0;

	/** @var int Current version line counter. */
	private $current_line = 0;

	/**
	 * Render one numbered row.
	 *
	 * @param string $type    Row type.
	 * @param string $content Escaped content, with optional del/ins markup.
	 * @param string $old     Previous line number.
	 * @param string $new     Current line number.
	 * @return string
	 */
	private function render_row( $type, $content, $old, $new ) {
		$cell_class = 'diff-context';
		if ( 'deleted' === $type ) {
			$cell_class = 'diff-deletedline';
		} elseif ( 'added' === $type ) {
			$cell_class = 'diff-addedline';
		} elseif ( 'changed' === $type ) {
			$cell_class = 'diff-changedline';
		}

		return sprintf(
			'<tr class="diff-row diff-row--%1$s"><td class="diff-line-numbers" aria-hidden="true"><span class="diff-line-number diff-line-number--old">%2$s</span><span class="diff-line-number diff-line-number--new">%3$s</span></td><td class="%4$s">%5$s</td></tr>',
			esc_attr( $type ),
			esc_html( $old ),
			esc_html( $new ),
			esc_attr( $cell_class ),
			$content
		);
	}

	/**
	 * Render unchanged lines.
	 *
	 * @param array  $lines  Lines.
	 * @param string $prefix Unused renderer prefix.
	 * @return string
	 */
	public function _context( $lines, $prefix = ' ' ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$output = '';
		foreach ( $lines as $line ) {
			$output .= $this->render_row( 'context', esc_html( $line ), (string) ++$this->previous_line, (string) ++$this->current_line );
		}
		return $output;
	}

	/**
	 * Render added-only lines.
	 *
	 * @param array $lines  Lines.
	 * @param bool  $encode Whether to encode the source.
	 * @return string
	 */
	public function _added( $lines, $encode = true ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$output = '';
		foreach ( $lines as $line ) {
			$line    = $encode ? esc_html( $line ) : $line;
			$output .= $this->render_row( 'added', '<ins>' . $line . '</ins>', '', (string) ++$this->current_line );
		}
		return $output;
	}

	/**
	 * Render deleted-only lines.
	 *
	 * @param array $lines  Lines.
	 * @param bool  $encode Whether to encode the source.
	 * @return string
	 */
	public function _deleted( $lines, $encode = true ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$output = '';
		foreach ( $lines as $line ) {
			$line    = $encode ? esc_html( $line ) : $line;
			$output .= $this->render_row( 'deleted', '<del>' . $line . '</del>', (string) ++$this->previous_line, '' );
		}
		return $output;
	}

	/**
	 * Merge paired changed lines into one word-level inline diff.
	 *
	 * @param array $orig  Previous lines.
	 * @param array $final Current lines.
	 * @return string
	 */
	public function _changed( $orig, $final ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore,Universal.NamingConventions.NoReservedKeywordParameterNames.finalFound
		$output = '';
		list( , , $orig_rows, $final_rows ) = $this->interleave_changed_lines( $orig, $final );

		foreach ( array_keys( $orig_rows ) as $row ) {
			$orig_index  = $orig_rows[ $row ];
			$final_index = $final_rows[ $row ];

			if ( $orig_index < 0 && $final_index < 0 ) {
				continue;
			}

			if ( $orig_index < 0 ) {
				$output .= $this->_added( array( $final[ $final_index ] ) );
				continue;
			}

			if ( $final_index < 0 ) {
				$output .= $this->_deleted( array( $orig[ $orig_index ] ) );
				continue;
			}

			$text_diff = new Text_Diff( 'auto', array( array( $orig[ $orig_index ] ), array( $final[ $final_index ] ) ) );
			$renderer  = new WP_Text_Diff_Renderer_inline();
			$inline    = trim( $renderer->render( $text_diff ), "\n" );

			$output .= $this->render_row( 'changed', $inline, (string) ++$this->previous_line, (string) ++$this->current_line );
		}

		return $output;
	}
}
