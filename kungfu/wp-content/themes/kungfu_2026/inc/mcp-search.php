<?php
/** Read-only title lookup for the existing MCP endpoint. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kungfu_2026_mcp_search_tool() {
	return array(
		'name' => 'search_posts',
		'description' => 'Find post IDs by an approximate title, partial title, or minor misspelling. Searches titles only across editable posts, pages, and characters, including drafts. Returns ranked IDs, titles, types, statuses, URLs, and similarity scores. Read-only; no POST confirmation needed. Treat fuzzy results as suggestions: verify the title with get_post_for_editing before proposing an edit and do not silently choose between ambiguous matches.',
		'inputSchema' => array(
			'type' => 'object',
			'properties' => array(
				'query' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 200, 'description' => 'Title or title fragment; punctuation/case differences and small typos are tolerated.' ),
				'post_type' => array( 'type' => 'string', 'enum' => array( 'all', 'post', 'page', 'character' ), 'default' => 'all' ),
				'limit' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 10 ),
			),
			'required' => array( 'query' ),
			'additionalProperties' => false,
		),
		'annotations' => array( 'readOnlyHint' => true, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false ),
		'securitySchemes' => array( array( 'type' => 'noauth' ) ),
	);
}

/** Fold punctuation, accents, case, and letter/number boundaries for matching. */
function kungfu_2026_mcp_normalize_title( $title ) {
	$title = remove_accents( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$title = function_exists( 'mb_strtolower' ) ? mb_strtolower( $title, 'UTF-8' ) : strtolower( $title );
	$title = preg_replace( '/(?<=\p{L})(?=\p{N})|(?<=\p{N})(?=\p{L})/u', ' ', $title );
	return trim( (string) preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $title ) );
}

/** Levenshtein similarity by Unicode character, with a fast path for ASCII. */
function kungfu_2026_mcp_title_similarity( $left, $right ) {
	if ( $left === $right ) {
		return 1.0;
	}
	$a = preg_split( '//u', $left, -1, PREG_SPLIT_NO_EMPTY );
	$b = preg_split( '//u', $right, -1, PREG_SPLIT_NO_EMPTY );
	if ( min( count( $a ), count( $b ) ) / max( count( $a ), count( $b ), 1 ) < 0.6 ) {
		return 0.0;
	}
	// A single adjacent swap is a common typing mistake, not two mistakes.
	if ( count( $a ) === count( $b ) ) {
		$different = array();
		foreach ( $a as $index => $character ) {
			if ( $character !== $b[$index] ) {
				$different[] = $index;
			}
		}
		if ( count( $different ) === 2 && $different[1] === $different[0] + 1 && $a[$different[0]] === $b[$different[1]] && $a[$different[1]] === $b[$different[0]] ) {
			return 1 - 1 / count( $a );
		}
	}
	if ( ! preg_match( '/[^\x00-\x7f]/', $left . $right ) ) {
		return 1 - levenshtein( $left, $right ) / max( strlen( $left ), strlen( $right ), 1 );
	}
	$previous = range( 0, count( $b ) );
	foreach ( $a as $i => $character ) {
		$current = array( $i + 1 );
		foreach ( $b as $j => $other ) {
			$current[] = min( $current[$j] + 1, $previous[$j + 1] + 1, $previous[$j] + ( $character === $other ? 0 : 1 ) );
		}
		$previous = $current;
	}
	return 1 - $previous[count( $b )] / max( count( $a ), count( $b ), 1 );
}

/** Score every query word against a distinct title word; tolerate minor typos. */
function kungfu_2026_mcp_score_title( $query, $title ) {
	$words = explode( ' ', $title );
	$terms = explode( ' ', $query );
	// Part numbers must agree: Part 1 should not suggest Part 2 as a typo.
	foreach ( $terms as $term ) {
		if ( preg_match( '/^\p{N}+$/u', $term ) && ! in_array( $term, $words, true ) ) {
			return null;
		}
	}
	if ( $query === $title ) {
		return array( 'score' => 1.0, 'match_type' => 'exact' );
	}
	if ( false !== strpos( $title, $query ) ) {
		return array( 'score' => 0.95, 'match_type' => 'contains' );
	}
	$sum = 0;
	$weight = 0;
	foreach ( $terms as $term ) {
		$length = count( preg_split( '//u', $term, -1, PREG_SPLIT_NO_EMPTY ) );
		$best = 0;
		$best_index = null;
		foreach ( $words as $index => $word ) {
			if ( $length <= 2 || preg_match( '/^\p{N}+$/u', $term ) ) {
				$score = $term === $word ? 1.0 : 0.0;
			} else {
				$score = kungfu_2026_mcp_title_similarity( $term, $word );
			}
			if ( $score > $best ) {
				$best = $score;
				$best_index = $index;
			}
		}
		if ( $best < 0.6 ) {
			return null;
		}
		unset( $words[$best_index] );
		$sum += $best * $length;
		$weight += $length;
	}
	$score = $weight ? 0.9 * $sum / $weight : 0;
	return $score >= 0.6 ? array( 'score' => round( $score, 4 ), 'match_type' => 'fuzzy' ) : null;
}

function kungfu_2026_mcp_search_posts( $args ) {
	global $wpdb;
	if ( ! is_array( $args ) || array_diff( array_keys( $args ), array( 'query', 'post_type', 'limit' ) )
		|| ! isset( $args['query'] ) || ! is_string( $args['query'] ) || kungfu_2026_mcp_length( $args['query'] ) > 200 ) {
		return kungfu_2026_mcp_tool_error( 'Provide query as a title string of 1 to 200 characters; unknown arguments are rejected.' );
	}
	$query = kungfu_2026_mcp_normalize_title( $args['query'] );
	$type = $args['post_type'] ?? 'all';
	$limit = $args['limit'] ?? 10;
	if ( '' === $query || ! in_array( $type, array( 'all', 'post', 'page', 'character' ), true ) || ! is_int( $limit ) || $limit < 1 || $limit > 20
		|| ( array_key_exists( 'post_type', $args ) && null === $args['post_type'] ) || ( array_key_exists( 'limit', $args ) && null === $args['limit'] ) ) {
		return kungfu_2026_mcp_tool_error( 'Query must contain letters or numbers; post_type must be all/post/page/character and limit an integer from 1 to 20.' );
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		return kungfu_2026_mcp_tool_error( 'The connected WordPress user cannot search posts for editing.' );
	}
	$types = 'all' === $type ? array( 'post', 'page', 'character' ) : array( $type );
	$placeholders = implode( ', ', array_fill( 0, count( $types ), '%s' ) );
	$matches = array();
	$last_id = 0;
	do {
		// Scan titles in bounded batches so typos are not lost to an SQL LIKE prefilter.
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_title, post_type, post_status FROM {$wpdb->posts} WHERE post_type IN ($placeholders) AND post_status IN ('publish', 'future', 'draft', 'pending', 'private') AND ID > %d ORDER BY ID ASC LIMIT 200",
			array_merge( $types, array( $last_id ) )
		) );
		if ( ! is_array( $rows ) || $wpdb->last_error ) {
			return kungfu_2026_mcp_tool_error( 'WordPress could not search post titles. Please retry.' );
		}
		foreach ( $rows as $row ) {
			$last_id = (int) $row->ID;
			$score = kungfu_2026_mcp_score_title( $query, kungfu_2026_mcp_normalize_title( $row->post_title ) );
			if ( null === $score || ! current_user_can( 'edit_post', $row->ID ) ) {
				continue;
			}
			$matches[] = array_merge( array(
				'post_id' => (int) $row->ID, 'title' => $row->post_title,
				'post_type' => $row->post_type, 'status' => $row->post_status,
				'url' => get_permalink( $row->ID ),
			), $score );
		}
	} while ( count( $rows ) === 200 );
	usort( $matches, function ( $a, $b ) {
		return ( $b['score'] <=> $a['score'] ) ?: ( strcmp( $a['title'], $b['title'] ) ?: ( $a['post_id'] <=> $b['post_id'] ) );
	} );
	return kungfu_2026_mcp_json_result( array(
		'query' => $args['query'], 'post_type' => $type,
		'total_matches' => count( $matches ), 'has_more' => count( $matches ) > $limit,
		'matches' => array_slice( $matches, 0, $limit ),
		'guidance' => 'These are title-based suggestions, not a confirmed target. Verify the title with get_post_for_editing before editing; clarify ambiguous matches.',
	) );
}
