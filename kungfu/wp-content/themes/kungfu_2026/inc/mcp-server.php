<?php
/**
 * Minimal stateless MCP server exposed through the WordPress REST API.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/mcp-block-editing.php';
require_once __DIR__ . '/mcp-search.php';

const KUNGFU_MCP_PROTOCOL_VERSION = '2025-06-18';
const KUNGFU_MCP_TOOL_NAME        = 'add_post';
const KUNGFU_MCP_READ_TOOL_NAME   = 'get_post_for_editing';
const KUNGFU_MCP_UPDATE_TOOL_NAME = 'update_post';
const KUNGFU_MCP_TOKEN_OPTION     = 'kungfu_mcp_test_token';
const KUNGFU_MCP_OWNER_OPTION     = 'kungfu_mcp_test_owner';

/**
 * Register the Streamable HTTP endpoint.
 */
function kungfu_2026_register_mcp_route() {
	register_rest_route(
		'kungfu-mcp/v1',
		'/mcp',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'kungfu_2026_handle_mcp_request',
			'permission_callback' => 'kungfu_2026_mcp_user_can_edit',
		)
	);

	register_rest_route(
		'kungfu-mcp/v1',
		'/mcp/(?P<token>[A-Za-z0-9]{64})',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'kungfu_2026_handle_mcp_request',
			'permission_callback' => 'kungfu_2026_mcp_token_is_valid',
		)
	);
}
add_action( 'rest_api_init', 'kungfu_2026_register_mcp_route' );

/**
 * Allow the regular endpoint to use WordPress authentication.
 *
 * @return bool
 */
function kungfu_2026_mcp_user_can_edit() {
	return current_user_can( 'edit_posts' );
}

/**
 * Validate the temporary secret-path endpoint and restore its owner as the
 * current WordPress user for post authorship and capability checks.
 *
 * @param WP_REST_Request $request REST request.
 * @return bool
 */
function kungfu_2026_mcp_token_is_valid( WP_REST_Request $request ) {
	$stored_token  = (string) get_option( KUNGFU_MCP_TOKEN_OPTION, '' );
	$provided_token = (string) $request->get_param( 'token' );
	$owner_id       = (int) get_option( KUNGFU_MCP_OWNER_OPTION, 0 );

	if ( 64 !== strlen( $stored_token ) || 64 !== strlen( $provided_token ) || ! hash_equals( $stored_token, $provided_token ) || $owner_id < 1 ) {
		return false;
	}

	wp_set_current_user( $owner_id );

	return current_user_can( 'edit_posts' );
}

/**
 * Generate a token containing only URL-safe characters.
 *
 * @return string
 */
function kungfu_2026_generate_mcp_token() {
	return wp_generate_password( 64, false, false );
}

/**
 * Return the test token, creating it for the current administrator if needed.
 *
 * @return string
 */
function kungfu_2026_get_mcp_token() {
	$token    = (string) get_option( KUNGFU_MCP_TOKEN_OPTION, '' );
	$owner_id = (int) get_option( KUNGFU_MCP_OWNER_OPTION, 0 );

	if ( 64 !== strlen( $token ) ) {
		$token = kungfu_2026_generate_mcp_token();
		add_option( KUNGFU_MCP_TOKEN_OPTION, $token, '', false );
		update_option( KUNGFU_MCP_TOKEN_OPTION, $token, false );
		update_option( KUNGFU_MCP_OWNER_OPTION, get_current_user_id(), false );
	} elseif ( $owner_id < 1 ) {
		update_option( KUNGFU_MCP_OWNER_OPTION, get_current_user_id(), false );
	}

	return $token;
}

/**
 * Add the temporary connection page under Tools.
 */
function kungfu_2026_add_mcp_admin_page() {
	add_management_page(
		__( 'Kungfu MCP Test', 'kungfu_2026' ),
		__( 'Kungfu MCP Test', 'kungfu_2026' ),
		'manage_options',
		'kungfu-mcp-test',
		'kungfu_2026_render_mcp_admin_page'
	);
}
add_action( 'admin_menu', 'kungfu_2026_add_mcp_admin_page' );

/**
 * Render the copyable secret URL and rotation control.
 */
function kungfu_2026_render_mcp_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$token = kungfu_2026_get_mcp_token();
	$url   = rest_url( 'kungfu-mcp/v1/mcp/' . $token );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Kungfu MCP Test', 'kungfu_2026' ); ?></h1>
		<?php if ( isset( $_GET['rotated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The secret URL was rotated. The previous URL no longer works.', 'kungfu_2026' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Add this URL to ChatGPT as a custom MCP server using “No authentication”. Treat the entire URL as a password.', 'kungfu_2026' ); ?></p>
		<p>
			<input type="text" class="large-text code" readonly value="<?php echo esc_attr( $url ); ?>" onfocus="this.select();">
		</p>
		<p><strong><?php esc_html_e( 'Temporary testing only:', 'kungfu_2026' ); ?></strong> <?php esc_html_e( 'the URL may appear in server logs. Rotate it immediately if it is shared.', 'kungfu_2026' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="kungfu_regenerate_mcp_token">
			<?php wp_nonce_field( 'kungfu_regenerate_mcp_token' ); ?>
			<?php submit_button( __( 'Rotate secret URL', 'kungfu_2026' ), 'secondary' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Rotate the test URL after an authenticated administrator request.
 */
function kungfu_2026_regenerate_mcp_token() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to rotate this URL.', 'kungfu_2026' ) );
	}

	check_admin_referer( 'kungfu_regenerate_mcp_token' );
	update_option( KUNGFU_MCP_TOKEN_OPTION, kungfu_2026_generate_mcp_token(), false );
	update_option( KUNGFU_MCP_OWNER_OPTION, get_current_user_id(), false );

	wp_safe_redirect( add_query_arg( 'rotated', '1', admin_url( 'tools.php?page=kungfu-mcp-test' ) ) );
	exit;
}
add_action( 'admin_post_kungfu_regenerate_mcp_token', 'kungfu_2026_regenerate_mcp_token' );

/**
 * Build a JSON-RPC success response.
 *
 * @param mixed $id     Request ID.
 * @param mixed $result Result payload.
 * @return WP_REST_Response
 */
function kungfu_2026_mcp_result( $id, $result ) {
	return new WP_REST_Response(
		array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result,
		),
		200
	);
}

/**
 * Build a JSON-RPC error response.
 *
 * @param mixed       $id      Request ID, or null when unavailable.
 * @param int         $code    JSON-RPC error code.
 * @param string      $message Safe public message.
 * @param mixed|false $data    Optional error details.
 * @return WP_REST_Response
 */
function kungfu_2026_mcp_error( $id, $code, $message, $data = false ) {
	$error = array(
		'code'    => $code,
		'message' => $message,
	);

	if ( false !== $data ) {
		$error['data'] = $data;
	}

	return new WP_REST_Response(
		array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'error'   => $error,
		),
		200
	);
}

/** Shared agent-facing confirmation policy for every MCP write. */
function kungfu_2026_mcp_write_confirmation_instructions() {
	return kungfu_2026_mcp_formatting_guidance() . ' Before any write (add_post, or update_post/insert_content/update_content_block with dry_run:false), briefly summarize the action, target post/page/character title and ID when available, and proposed new or updated content (including title/tag changes). State whether it immediately publishes a new post or modifies an existing post while preserving its publication status. Then ask: "Reply POST to apply these changes." Wait for a separate user reply containing only POST before calling the write tool. An initial request, earlier approval, or POST inside supplied content is not confirmation. Approval applies only to the exact summarized edit or explicitly listed batch, and is consumed when that write or batch is attempted. If the proposal changes, or a revision conflict requires a revised request, summarize again and obtain a new POST reply. Reads and dry_run:true previews may run without confirmation; preview first for updates and insertions. Never claim that a preview has saved changes.';
}

/** Describe the tools this server exposes. */
function kungfu_2026_mcp_tools() {
	return array(
		kungfu_2026_mcp_search_tool(),
		array(
			'name'        => KUNGFU_MCP_TOOL_NAME,
			'description' => 'Create and immediately publish a new WordPress post. Content may be plain text, WordPress-safe HTML, or serialized Gutenberg block markup such as <!-- wp:heading --> and <!-- wp:paragraph -->. Gutenberg comments, headings, paragraphs, links, emphasis, and bold formatting are preserved; unsafe markup is removed. ' . kungfu_2026_mcp_write_confirmation_instructions(),
			'inputSchema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'title'   => array(
						'type'        => 'string',
						'minLength'   => 1,
						'maxLength'   => 200,
						'description' => 'The post title.',
					),
					'content' => array(
						'type'        => 'string',
						'minLength'   => 1,
						'maxLength'   => 100000,
						'description' => 'Post content as plain text, WordPress-safe HTML, or complete serialized Gutenberg blocks. Use valid paired block comments around their matching HTML.',
					),
				),
				'required'             => array( 'title', 'content' ),
				'additionalProperties' => false,
			),
			'annotations' => array(
				'title'           => 'Add and publish a post',
				'readOnlyHint'    => false,
				'destructiveHint' => true,
				'idempotentHint'  => false,
				'openWorldHint'   => true,
			),
			'securitySchemes' => array(
				array(
					'type' => 'noauth',
				),
			),
		),
		array(
			'name'        => KUNGFU_MCP_READ_TOOL_NAME,
			'description' => 'Read a WordPress post before editing it. Returns its title, status, tags, content revision fingerprint, and raw content grouped into numbered sections and numbered lines. Also returns exact raw content, block IDs, named H2 sections, and heading_sections covering all heading levels. Call before update_post, insert_content, or update_content_block. Supports posts, pages, and characters.',
			'inputSchema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => 'The WordPress post ID to inspect.',
					),
				),
				'required'             => array( 'post_id' ),
				'additionalProperties' => false,
			),
			'annotations' => array(
				'title'           => 'Read a post for editing',
				'readOnlyHint'    => true,
				'destructiveHint' => false,
				'idempotentHint'  => true,
				'openWorldHint'   => false,
			),
			'securitySchemes' => array(
				array(
					'type' => 'noauth',
				),
			),
		),
		// Body and metadata editing tools share this registry.
		...kungfu_2026_mcp_editing_tools(),
	);
}

/**
 * Count characters without requiring the mbstring extension.
 *
 * @param string $value Text to measure.
 * @return int
 */
function kungfu_2026_mcp_length( $value ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
}

/**
 * Run add_post and return an MCP tool result.
 *
 * @param array $arguments Tool arguments.
 * @return array
 */
function kungfu_2026_mcp_add_post( $arguments ) {
	$title   = isset( $arguments['title'] ) && is_string( $arguments['title'] ) ? trim( $arguments['title'] ) : '';
	$content = isset( $arguments['content'] ) && is_string( $arguments['content'] ) ? trim( $arguments['content'] ) : '';

	if ( '' === $title || '' === $content ) {
		return array(
			'isError' => true,
			'content' => array(
				array(
					'type' => 'text',
					'text' => 'Both title and content are required.',
				),
			),
		);
	}

	if ( kungfu_2026_mcp_length( $title ) > 200 || kungfu_2026_mcp_length( $content ) > 100000 ) {
		return array(
			'isError' => true,
			'content' => array(
				array(
					'type' => 'text',
					'text' => 'Title must be at most 200 characters and content at most 100,000 characters.',
				),
			),
		);
	}

	if ( ! current_user_can( 'publish_posts' ) ) {
		return kungfu_2026_mcp_tool_error( 'The connected WordPress user cannot publish posts.' );
	}

	$sanitized_content = kungfu_2026_mcp_sanitize_post_content( $content );
	if ( '' === trim( $sanitized_content ) ) {
		return kungfu_2026_mcp_tool_error( 'Content is empty after WordPress safe-content filtering.' );
	}
	if ( has_blocks( $content ) && ! has_blocks( $sanitized_content ) ) {
		return kungfu_2026_mcp_tool_error( 'WordPress could not preserve the supplied Gutenberg block markup. Send valid serialized blocks with paired block comments.' );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => wp_strip_all_tags( $title ),
			'post_content' => $sanitized_content,
			'post_author'  => get_current_user_id(),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return array(
			'isError' => true,
			'content' => array(
				array(
					'type' => 'text',
					'text' => 'WordPress could not publish the post.',
				),
			),
		);
	}

	return array(
		'content' => array(
			array(
				'type' => 'text',
				'text' => sprintf(
					'Post published successfully. Post ID: %d. Title: %s.',
					$post_id,
					wp_strip_all_tags( $title )
				),
			),
		),
	);
}

/**
 * Return an MCP tool-level error.
 *
 * @param string $message Public error message.
 * @return array
 */
function kungfu_2026_mcp_tool_error( $message ) {
	return array(
		'isError' => true,
		'content' => array(
			array(
				'type' => 'text',
				'text' => $message,
			),
		),
	);
}

/**
 * Normalize line endings so MCP line references are stable across clients.
 *
 * @param string $content Raw post content.
 * @return string
 */
function kungfu_2026_mcp_normalize_content( $content ) {
	return str_replace( array( "\r\n", "\r" ), "\n", (string) $content );
}

/**
 * Sanitize post content without flattening Gutenberg serialization.
 *
 * filter_block_content() parses serialized blocks, filters their attributes,
 * and serializes the result back with its <!-- wp:* --> delimiters intact.
 * wp_kses_post() then applies WordPress's normal safe-post HTML rules. The
 * fallback supports older WordPress versions while retaining safe HTML.
 *
 * @param string $content Raw post content.
 * @return string
 */
function kungfu_2026_mcp_sanitize_post_content( $content ) {
	$content = kungfu_2026_mcp_normalize_content( $content );

	if ( function_exists( 'filter_block_content' ) ) {
		$content = filter_block_content( $content );
	}

	return wp_kses_post( $content );
}

/**
 * Split raw content into global lines and nonblank sections.
 *
 * A section is a consecutive group of nonblank lines separated by one or
 * more blank lines. Global indexes are zero-based internally; every number
 * presented to an MCP client is one-based.
 *
 * @param string $content Raw post content.
 * @return array
 */
function kungfu_2026_mcp_content_map( $content ) {
	$lines            = explode( "\n", kungfu_2026_mcp_normalize_content( $content ) );
	$sections         = array();
	$current_section  = -1;
	$inside_section   = false;

	foreach ( $lines as $global_index => $line ) {
		if ( '' === trim( $line ) ) {
			$inside_section = false;
			continue;
		}

		if ( ! $inside_section ) {
			++$current_section;
			$sections[ $current_section ] = array();
			$inside_section = true;
		}

		$sections[ $current_section ][] = array(
			'global_index' => $global_index,
			'global_line'  => $global_index + 1,
			'section_line' => count( $sections[ $current_section ] ) + 1,
			'text'         => $line,
		);
	}

	return array(
		'lines'    => $lines,
		'sections' => $sections,
	);
}

/**
 * Create the content fingerprint required for a targeted edit.
 *
 * @param string $content Raw post content.
 * @return string
 */
function kungfu_2026_mcp_content_revision( $content ) {
	return hash( 'sha256', (string) $content );
}

/**
 * Run get_post_for_editing and return numbered content for precise edits.
 *
 * @param array $arguments Tool arguments.
 * @return array
 */
function kungfu_2026_mcp_get_post_for_editing( $arguments ) {
	$post_id = isset( $arguments['post_id'] ) && is_int( $arguments['post_id'] ) ? $arguments['post_id'] : 0;
	$post    = $post_id > 0 ? get_post( $post_id ) : null;

	if ( ! $post || ! kungfu_2026_mcp_supported_post_type( $post->post_type ) ) {
		return kungfu_2026_mcp_tool_error( 'A valid WordPress post ID is required.' );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return kungfu_2026_mcp_tool_error( 'The connected WordPress user cannot read this post for editing.' );
	}

	$content_map = kungfu_2026_mcp_content_map( $post->post_content );
	$line_labels = array();

	foreach ( $content_map['sections'] as $section_index => $section_lines ) {
		foreach ( $section_lines as $line ) {
			$line_labels[ $line['global_index'] ] = sprintf(
				'S%d.L%d [global %d]',
				$section_index + 1,
				$line['section_line'],
				$line['global_line']
			);
		}
	}

	$tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
	if ( is_wp_error( $tags ) ) {
		$tags = array();
	}

	$output = array(
		sprintf( 'Post ID: %d', $post_id ),
		'Title: ' . $post->post_title,
		'Status: ' . $post->post_status,
		'Tags: ' . ( $tags ? implode( ', ', $tags ) : '(none)' ),
		'Content revision: ' . kungfu_2026_mcp_edit_revision( $post ),
		'Reference format: S<section>.L<section-line> [global <line>]. Sections are groups of nonblank raw-content lines separated by blank lines.',
		'',
		'Numbered raw content:',
	);

	foreach ( $content_map['lines'] as $global_index => $line ) {
		if ( isset( $line_labels[ $global_index ] ) ) {
			$output[] = $line_labels[ $global_index ] . ' | ' . $line;
		} else {
			$output[] = sprintf( '[global %d] | (blank)', $global_index + 1 );
		}
	}

	$snapshot = kungfu_2026_mcp_editing_snapshot( $post );
	return array(
		'structuredContent' => $snapshot,
		'content' => array(
			array( 'type' => 'text', 'text' => wp_json_encode( $snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
			array(
				'type' => 'text',
				'text' => implode( "\n", $output ),
			),
		),
	);
}

/**
 * Run update_post and return an MCP tool result.
 *
 * @param array $arguments Tool arguments.
 * @return array
 */
function kungfu_2026_mcp_update_post( $arguments ) {
	return kungfu_2026_mcp_edit_content( $arguments, 'update_post' );
}

/**
 * Process one stateless MCP JSON-RPC request.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function kungfu_2026_handle_mcp_request( WP_REST_Request $request ) {
	if ( strlen( $request->get_body() ) > 262144 ) {
		return kungfu_2026_mcp_error( null, -32600, 'Request is too large.' );
	}

	$message = json_decode( $request->get_body(), true );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return kungfu_2026_mcp_error( null, -32700, 'Parse error.' );
	}

	if ( ! is_array( $message ) || '2.0' !== ( $message['jsonrpc'] ?? null ) || ! isset( $message['method'] ) || ! is_string( $message['method'] ) ) {
		return kungfu_2026_mcp_error( $message['id'] ?? null, -32600, 'Invalid Request.' );
	}

	$id     = $message['id'] ?? null;
	$method = $message['method'];
	$params = isset( $message['params'] ) && is_array( $message['params'] ) ? $message['params'] : array();

	if ( 'notifications/initialized' === $method || 'notifications/cancelled' === $method ) {
		return new WP_REST_Response( null, 202 );
	}

	if ( 'initialize' === $method ) {
		return kungfu_2026_mcp_result(
			$id,
			array(
				'protocolVersion' => KUNGFU_MCP_PROTOCOL_VERSION,
				'capabilities'    => array(
					'tools' => array(
						'listChanged' => false,
					),
				),
				'serverInfo'      => array(
					'name'    => 'kungfu-wordpress',
					'version' => '0.8.0',
				),
				'instructions'    => kungfu_2026_mcp_write_confirmation_instructions() . ' When a post ID is unknown, call search_posts with the approximate title. Results are suggestions; verify the title and clarify ambiguous matches before editing. For editing posts, pages, or characters first call get_post_for_editing. update_post accepts a complete replacement body plus title, excerpt, and tags where supported; always include all body content to retain. insert_content supports before/after a block or paragraph, inside an exact heading section (including H3 and H2), or position start/end without a target. update_content_block may replace a block with any safe content or delete it with an empty string. Formatting guidance is advisory; do not reject user-requested formats merely because they differ. Pass content_revision and dry_run:true to preview updates, then dry_run:false after POST confirmation. Reread after writes or conflicts. IDs are scoped to the returned revision. Existing publication status is preserved and a restorable revision is saved. add_post immediately publishes; do not use it for testing drafts.',
			)
		);
	}

	if ( 'ping' === $method ) {
		return kungfu_2026_mcp_result( $id, new stdClass() );
	}

	if ( 'tools/list' === $method ) {
		return kungfu_2026_mcp_result(
			$id,
			array(
				'tools' => kungfu_2026_mcp_tools(),
			)
		);
	}

	if ( 'tools/call' === $method ) {
		$arguments = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();
		$tool_name = $params['name'] ?? null;

		if ( KUNGFU_MCP_TOOL_NAME === $tool_name ) {
			return kungfu_2026_mcp_result( $id, kungfu_2026_mcp_add_post( $arguments ) );
		}

		if ( KUNGFU_MCP_READ_TOOL_NAME === $tool_name ) {
			return kungfu_2026_mcp_result( $id, kungfu_2026_mcp_get_post_for_editing( $arguments ) );
		}

		if ( 'search_posts' === $tool_name ) {
			return kungfu_2026_mcp_result( $id, kungfu_2026_mcp_search_posts( $arguments ) );
		}

		if ( KUNGFU_MCP_UPDATE_TOOL_NAME === $tool_name ) {
			return kungfu_2026_mcp_result( $id, kungfu_2026_mcp_update_post( $arguments ) );
		}

		if ( in_array( $tool_name, array( 'insert_content', 'update_content_block' ), true ) ) {
			return kungfu_2026_mcp_result( $id, kungfu_2026_mcp_edit_content( $arguments, $tool_name ) );
		}

		return kungfu_2026_mcp_error( $id, -32602, 'Unknown tool.' );
	}

	return kungfu_2026_mcp_error( $id, -32601, 'Method not found.' );
}
