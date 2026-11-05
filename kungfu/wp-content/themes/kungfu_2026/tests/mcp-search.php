<?php
/** Local-only integration checks: wp eval-file wp-content/themes/kungfu_2026/tests/mcp-search.php */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) {
	throw new RuntimeException( 'Search fixtures require localhost WP-CLI.' );
}
$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
$admin_id = $admins[0]->ID;
wp_set_current_user( $admin_id );
$ids = array();
$author_id = 0;
global $checks;
$checks = 0;
function search_check( $condition, $message ) {
	global $checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$checks;
}
function search_call( $args ) {
	$request = new WP_REST_Request( 'POST', '/kungfu-mcp/v1/mcp' );
	$request->set_body( wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => array( 'name' => 'search_posts', 'arguments' => $args ) ) ) );
	$request->set_header( 'Content-Type', 'application/json' );
	return rest_do_request( $request )->get_data()['result'];
}
try {
	$definitions = array(
		array( 'FuzzLookup Crane', 'post', 'draft' ),
		array( 'FuzzLookup Crane Flight', 'post', 'draft' ),
		array( 'FuzzLookup Café & Crane — Part 1', 'post', 'draft' ),
		array( 'FuzzLookup Café & Crane — Part 2', 'post', 'draft' ),
		array( 'FuzzLookup Crane', 'character', 'draft' ),
		array( 'FuzzLookup Crane Archive', 'page', 'draft' ),
		array( 'FuzzLookup Crane Discarded', 'post', 'trash' ),
		array( 'FuzzLookup Crane Unfinished', 'post', 'auto-draft' ),
		array( 'FuzzLookup Crane Private', 'post', 'private' ),
	);
	foreach ( $definitions as $definition ) {
		$id = wp_insert_post( array( 'post_title' => $definition[0], 'post_type' => $definition[1], 'post_status' => $definition[2], 'post_author' => $admin_id, 'post_content' => 'Search fixtures must remain unchanged.' ), true );
		search_check( ! is_wp_error( $id ) && $id > 0, 'Fixture creation failed' );
		$ids[] = $id;
	}
	$before = get_post( $ids[0] );
	$r = search_call( array( 'query' => 'FuzzLookup Crane' ) )['structuredContent'];
	search_check( 7 === $r['total_matches'], 'All editable matching types/statuses should be searchable, except trash/autodraft' );
	search_check( $r['matches'][0]['score'] === 1.0 && 'exact' === $r['matches'][0]['match_type'], 'Exact matches must rank first' );
	search_check( count( array_filter( $r['matches'], function ( $m ) { return 'exact' === $m['match_type']; } ) ) === 2, 'Duplicate titles must return separate IDs' );
	search_check( isset( $r['matches'][0]['post_id'], $r['matches'][0]['post_type'], $r['matches'][0]['status'], $r['matches'][0]['url'] ), 'Useful identity fields must be returned' );
	$r = search_call( array( 'query' => 'fuzzlookup CRANE', 'post_type' => 'post', 'limit' => 1 ) )['structuredContent'];
	search_check( $r['matches'][0]['post_id'] === $ids[0] && $r['has_more'] && count( $r['matches'] ) === 1, 'Type filter, limit, ranking, and case folding' );
	$r = search_call( array( 'query' => 'fuzzlookup cafe crane part1' ) )['structuredContent'];
	search_check( count( $r['matches'] ) === 1 && $r['matches'][0]['post_id'] === $ids[2], 'Accents, punctuation, letter/number boundaries, and part-number isolation' );
	$r = search_call( array( 'query' => 'fuzzlookup crnae', 'post_type' => 'character' ) )['structuredContent'];
	search_check( count( $r['matches'] ) === 1 && $r['matches'][0]['post_id'] === $ids[4] && 'fuzzy' === $r['matches'][0]['match_type'], 'Adjacent letter swap must find the character' );
	$r = search_call( array( 'query' => 'fuzzlookup crae', 'post_type' => 'page' ) )['structuredContent'];
	search_check( count( $r['matches'] ) === 1 && $r['matches'][0]['post_id'] === $ids[5], 'Missing-letter typo and partial title lookup' );
	$r = search_call( array( 'query' => 'zzzzqqqqxxxx' ) )['structuredContent'];
	search_check( $r['matches'] === array() && 0 === $r['total_matches'], 'No match must return an empty list' );
	foreach ( array( array(), array( 'query' => '!!!' ), array( 'query' => array( 'bad' ) ), array( 'query' => str_repeat( 'a', 201 ) ), array( 'query' => 'Crane', 'post_type' => 'revision' ), array( 'query' => 'Crane', 'limit' => 0 ), array( 'query' => 'Crane', 'limit' => 21 ), array( 'query' => 'Crane', 'limit' => '2' ), array( 'query' => 'Crane', 'limit' => null ), array( 'query' => 'Crane', 'post_type' => null ), array( 'query' => 'Crane', 'unknown' => true ) ) as $invalid ) {
		search_check( ! empty( search_call( $invalid )['isError'] ), 'Invalid search arguments must fail' );
	}
	search_check( kungfu_2026_mcp_title_similarity( '张三丰传', '张三峰传' ) === 0.75, 'Unicode comparison must count characters, not UTF-8 bytes' );
	search_check( null === kungfu_2026_mcp_score_title( '甲', '乙' ), 'Unrelated short Unicode queries must not match' );
	search_check( ! empty( kungfu_2026_mcp_search_tool()['annotations']['readOnlyHint'] ), 'Search discovery must be read-only' );
	$author_id = wp_insert_user( array( 'user_login' => 'mcp-search-check-' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password( 30 ), 'role' => 'author' ) );
	search_check( ! is_wp_error( $author_id ), 'Temporary author creation failed' );
	$own_id = wp_insert_post( array( 'post_title' => 'FuzzLookup Crane Owned', 'post_status' => 'draft', 'post_author' => $author_id ) );
	$ids[] = $own_id;
	wp_set_current_user( $author_id );
	$r = search_call( array( 'query' => 'FuzzLookup Crane' ) )['structuredContent'];
	search_check( count( $r['matches'] ) === 1 && $r['matches'][0]['post_id'] === $own_id && 1 === $r['total_matches'], 'Authors must not see other authors editable/private fixtures or counts' );
	wp_set_current_user( 0 );
	search_check( ! empty( kungfu_2026_mcp_search_posts( array( 'query' => 'FuzzLookup' ) )['isError'] ), 'Unauthenticated lookup must fail' );
	wp_set_current_user( $admin_id );
	$after = get_post( $ids[0] );
	search_check( $after->post_content === $before->post_content && $after->post_modified_gmt === $before->post_modified_gmt && ! wp_get_post_revisions( $ids[0] ), 'Searching must not modify posts or create revisions' );
	WP_CLI::success( "$checks title search checks passed; no live posts modified." );
} finally {
	wp_set_current_user( $admin_id );
	foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
	if ( is_int( $author_id ) && $author_id > 0 ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $author_id );
	}
}
