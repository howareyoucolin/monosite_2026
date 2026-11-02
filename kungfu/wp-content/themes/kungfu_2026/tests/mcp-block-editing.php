<?php
/** Run ONLY locally: wp eval-file wp-content/themes/kungfu_2026/tests/mcp-block-editing.php */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) {
	throw new RuntimeException( 'This suite creates local draft fixtures only; requires localhost WP-CLI.' );
}
$user = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $user[0]->ID );
global $checks, $ids;
$checks = 0;
$ids = array();
function check_mcp( $condition, $message ) {
	global $checks;
	if ( ! $condition ) { throw new RuntimeException( 'FAIL: ' . $message ); }
	++$checks;
}
function mcp_call( $name, $args ) {
	$request = new WP_REST_Request( 'POST', '/kungfu-mcp/v1/mcp' );
	$request->set_header( 'Content-Type', 'application/json' );
	$request->set_body( wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => array( 'name' => $name, 'arguments' => $args ) ) ) );
	return rest_do_request( $request )->get_data()['result'];
}
function fixture_mcp( $raw ) {
	global $ids;
	$id = wp_insert_post( wp_slash( array( 'post_title' => 'MCP fixture: Evenly Matched — Part 1', 'post_status' => 'draft', 'post_content' => $raw ) ), true );
	check_mcp( is_int( $id ), 'local draft created' );
	$ids[] = $id;
	return $id;
}
function read_mcp( $id ) { return mcp_call( 'get_post_for_editing', array( 'post_id' => $id ) )['structuredContent']; }
function error_mcp( $tool, $args, $label ) { check_mcp( true === ( mcp_call( $tool, $args )['isError'] ?? false ), $label ); }
$heading = "<!-- wp:heading {\"level\":2} -->\n<h2 class=\"wp-block-heading\">Section 3: The Die-Hard Spectators</h2>\n<!-- /wp:heading -->";
$paragraph = "<!-- wp:paragraph {\"className\":\"spectators\"} -->\n<p class=\"spectators\">Spectators were <strong>cheering</strong>, <em>arguing</em>, and placing bets.</p>\n<!-- /wp:paragraph -->";
$next = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Section 4: Afterward</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Unrelated content with \\ backslash and &amp; HTML entity.</p>\n<!-- /wp:paragraph -->";
$group = "<!-- wp:group -->\n<div class=\"wp-block-group\"><!-- wp:paragraph -->\n<p>Nested text.</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->";
$raw = $heading . "\n\n" . $paragraph . "\n\n" . $group . "\n\n" . $next;
$texts = array(
	'Several spectators pulled out their phones and began recording the fight from different angles.',
	'One man raised his phone high and shouted with wild enthusiasm.',
	'"Holy cow! This is gonna be epic! I\'m recording the whole thing!"',
);
try {
	$id = fixture_mcp( $raw );
	$initial = read_mcp( $id );
	check_mcp( $initial['content'] === $raw && 'draft' === $initial['status'], 'read returns exact content and status' );
	check_mcp( count( $initial['blocks'] ) === 5 && count( $initial['h2_sections'] ) === 2, 'top-level block and H2 maps' );
	check_mcp( $initial['blocks'][1]['attributes']['className'] === 'spectators', 'attributes exposed' );
	$args = array( 'post_id' => $id, 'content_revision' => $initial['content_revision'], 'section_heading' => 'Section 3: The Die-Hard Spectators', 'paragraph_text' => 'Spectators were cheering, arguing, and placing bets.', 'paragraphs' => $texts );
	$revision_count = count( wp_get_post_revisions( $id ) );
	$preview = mcp_call( 'insert_content', $args );
	check_mcp( ! isset( $preview['isError'] ), 'insertion preview succeeds: ' . wp_json_encode( $preview ) );
	$plan = $preview['structuredContent']['change'];
	check_mcp( get_post( $id )->post_content === $raw && count( wp_get_post_revisions( $id ) ) === $revision_count, 'default dry-run writes neither post nor revisions' );
	check_mcp( strpos( $plan['content'], $paragraph . "\n\n<!-- wp:paragraph -->\n<p>Several spectators" ) !== false, 'exact requested insertion placement' );
	check_mcp( count( kungfu_2026_mcp_block_spans( $plan['content'] ) ) === 8, 'three new Gutenberg paragraphs' );
	$args['dry_run'] = false;
	$applied = mcp_call( 'insert_content', $args );
	check_mcp( ! isset( $applied['isError'] ), 'apply succeeds: ' . wp_json_encode( $applied ) );
	$after = read_mcp( $id );
	check_mcp( $after['content'] === $plan['content'] && 'draft' === $after['status'], 'applied content matches preview and preserves status' );
	check_mcp( substr( $after['content'], 0, $plan['start_byte'] ) === substr( $raw, 0, $plan['start_byte'] ), 'prefix unchanged byte-for-byte' );
	check_mcp( substr( $after['content'], $plan['start_byte'] + strlen( $plan['after'] ) ) === substr( $raw, $plan['end_byte'] ), 'suffix unchanged byte-for-byte' );
	check_mcp( $initial['blocks'][1]['raw'] === $after['blocks'][1]['raw'] && $initial['blocks'][2]['raw'] === $after['blocks'][5]['raw'], 'bold/italic, attributes, and nested blocks unchanged' );
	$revision = get_post( $applied['structuredContent']['restore_revision_id'] );
	check_mcp( $revision && 'revision' === $revision->post_type && $revision->post_content === $raw, 'restorable exact pre-edit revision saved' );
	error_mcp( 'insert_content', $args, 'stale revision rejected' );
	$args['content_revision'] = $after['content_revision'];
	$args['section_heading'] = 'Missing';
	error_mcp( 'insert_content', $args, 'missing named section rejected' );
	$args['section_heading'] = 'Section 4: Afterward';
	error_mcp( 'insert_content', $args, 'paragraph outside named section rejected' );
	error_mcp( 'insert_content', array_merge( $args, array( 'post_id' => 0 ) ), 'invalid post ID rejected' );
	error_mcp( 'insert_content', array_merge( $args, array( 'dry_run' => 'false' ) ), 'nonboolean dry_run rejected' );
	error_mcp( 'update_post', array( 'post_id' => $id, 'content' => 'oops' ), 'legacy full replacement rejected' );
	error_mcp( 'update_post', array( 'post_id' => $id, 'line_start' => 1, 'replacement' => 'oops' ), 'legacy line edit rejected' );
	$update = array( 'post_id' => $id, 'content_revision' => $after['content_revision'], 'block_id' => $after['blocks'][1]['block_id'], 'content' => str_replace( 'placing bets.', 'placing bigger bets.', $paragraph ) );
	$up = mcp_call( 'update_content_block', $update );
	check_mcp( ! isset( $up['isError'] ), 'single-block preview' );
	$update['dry_run'] = false;
	$up = mcp_call( 'update_content_block', $update );
	check_mcp( ! isset( $up['isError'] ), 'single-block apply' );
	$updated = read_mcp( $id );
	check_mcp( $updated['blocks'][1]['raw'] === $update['content'] && $updated['blocks'][0]['raw'] === $heading, 'only selected block replaced' );
	$update['content_revision'] = $updated['content_revision'];
	$update['block_id'] = $updated['blocks'][1]['block_id'];
	foreach ( array( '<!-- wp:paragraph --><p>Oops</p>', '<!-- wp:paragraph --><p>Oops</p><!-- /wp:heading -->', '<!-- wp:paragraph --><p>X</p><!-- /wp:paragraph -->' . $paragraph, '<!-- wp:paragraph --><script>alert(1)</script><!-- /wp:paragraph -->', $heading, '<!-- wp:paragraph {broken} --><p>X</p><!-- /wp:paragraph -->' ) as $bad ) {
		error_mcp( 'update_content_block', array_merge( $update, array( 'content' => $bad ) ), 'malformed/unsafe/multiple/wrong-type replacement rejected' );
	}
	$duplicate_id = fixture_mcp( $raw . "\n\n" . $paragraph );
	$d = read_mcp( $duplicate_id );
	error_mcp( 'insert_content', array( 'post_id' => $duplicate_id, 'content_revision' => $d['content_revision'], 'paragraph_text' => 'Spectators were cheering, arguing, and placing bets.', 'paragraphs' => $texts ), 'ambiguous paragraphs rejected' );
	$duplicate_heading = fixture_mcp( $raw . "\n\n" . $heading );
	$d = read_mcp( $duplicate_heading );
	error_mcp( 'insert_content', array( 'post_id' => $duplicate_heading, 'content_revision' => $d['content_revision'], 'section_heading' => 'Section 3: The Die-Hard Spectators', 'paragraphs' => $texts ), 'ambiguous headings rejected' );
	$crlf = fixture_mcp( str_replace( "\n", "\r\n", $raw ) );
	$d = read_mcp( $crlf );
	$c = mcp_call( 'insert_content', array( 'post_id' => $crlf, 'content_revision' => $d['content_revision'], 'section_heading' => 'Section 3: The Die-Hard Spectators', 'paragraphs' => $texts ) );
	check_mcp( ! isset( $c['isError'] ) && strpos( $c['structuredContent']['change']['content'], str_replace( "\n", "\r\n", $paragraph ) ) !== false, 'CRLF and section-end insertion preserved' );
	check_mcp( strpos( $c['structuredContent']['change']['content'], 'recording the whole thing!' ) < strpos( $c['structuredContent']['change']['content'], 'Section 4:' ), 'section-end insertion before next H2' );
	// Unexpected third-party save filters must roll the database change back.
	$filter = function ( $data ) { $data['post_content'] .= 'CHANGED BY FILTER'; return $data; };
	add_filter( 'wp_insert_post_data', $filter );
	$update['content'] = str_replace( 'bigger bets.', 'smaller bets.', $updated['blocks'][1]['raw'] );
	error_mcp( 'update_content_block', $update, 'filter mutation rolled back' );
	remove_filter( 'wp_insert_post_data', $filter );
	check_mcp( read_mcp( $id )['content'] === $updated['content'], 'rollback leaves original content exact' );
	$no_revisions = function () { return 0; };
	add_filter( 'wp_revisions_to_keep', $no_revisions );
	error_mcp( 'update_content_block', $update, 'revisions disabled rejects apply' );
	remove_filter( 'wp_revisions_to_keep', $no_revisions );
	check_mcp( read_mcp( $id )['content'] === $updated['content'], 'disabled revisions leave post unchanged' );
	$one_revision = function () { return 1; };
	add_filter( 'wp_revisions_to_keep', $one_revision );
	$limited_id = fixture_mcp( $raw );
	$limited = read_mcp( $limited_id );
	$limited_result = mcp_call( 'insert_content', array( 'post_id' => $limited_id, 'content_revision' => $limited['content_revision'], 'section_heading' => 'Section 3: The Die-Hard Spectators', 'paragraphs' => $texts, 'dry_run' => false ) );
	check_mcp( ! isset( $limited_result['isError'] ), 'edit with retention limit of one succeeds' );
	$limited_backup = get_post( $limited_result['structuredContent']['restore_revision_id'] );
	check_mcp( $limited_backup && $limited_backup->post_content === $raw, 'returned backup survives normal revision pruning' );
	remove_filter( 'wp_revisions_to_keep', $one_revision );
	$h1 = "<!-- wp:heading {\"level\":1} -->\n<h1>New chapter</h1>\n<!-- /wp:heading -->";
	$sections = kungfu_2026_mcp_h2_sections( kungfu_2026_mcp_block_spans( $heading . "\n\n" . $paragraph . "\n\n" . $h1 . "\n\n" . $next ) );
	check_mcp( 2 === $sections[0]['end_index'], 'H1 closes H2 section without later headings extending it' );
	// Current user may not bypass per-post authorization.
	wp_set_current_user( 0 );
	$denied = kungfu_2026_mcp_edit_content( $update, 'update_content_block' );
	check_mcp( true === $denied['isError'], 'unauthorized user rejected' );
	wp_set_current_user( $user[0]->ID );
	$restored = wp_restore_post_revision( $revision->ID );
	check_mcp( (int) $restored === $id && get_post( $id )->post_content === $raw && 'draft' === get_post( $id )->post_status, 'native revision restore recovers exact original without publishing' );
	$discovery = array();
	foreach ( array( 'initialize', 'tools/list' ) as $method ) {
		$request = new WP_REST_Request( 'POST', '/kungfu-mcp/v1/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 2, 'method' => $method ) ) );
		$discovery[$method] = rest_do_request( $request )->get_data()['result'];
	}
	check_mcp( '0.6.0' === $discovery['initialize']['serverInfo']['version'], 'initialize advertises updated version' );
	$names = array_column( $discovery['tools/list']['tools'], 'name' );
	check_mcp( count( $names ) === 5 && count( array_unique( $names ) ) === 5 && in_array( 'insert_content', $names, true ), 'exactly five registered tools without duplicates' );
	WP_CLI::success( "$checks checks passed; requested insertion validated on local drafts. Live post 673 was never accessed or modified." );
} finally {
	wp_set_current_user( $user[0]->ID );
	foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
}
