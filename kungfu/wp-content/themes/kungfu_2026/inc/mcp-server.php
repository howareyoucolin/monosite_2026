<?php
/**
 * Minimal stateless MCP server exposed through the WordPress REST API.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KUNGFU_MCP_PROTOCOL_VERSION = '2025-06-18';
const KUNGFU_MCP_TOOL_NAME        = 'add_post';
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

/**
 * Describe the tools this test server exposes.
 *
 * @return array
 */
function kungfu_2026_mcp_tools() {
	return array(
		array(
			'name'        => KUNGFU_MCP_TOOL_NAME,
			'description' => 'Create a new draft WordPress post with a plain title and short plain-text content.',
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
						'maxLength'   => 2000,
						'description' => 'Short plain-text post content. HTML is removed.',
					),
				),
				'required'             => array( 'title', 'content' ),
				'additionalProperties' => false,
			),
			'annotations' => array(
				'title'           => 'Add a draft post',
				'readOnlyHint'    => false,
				'destructiveHint' => false,
				'idempotentHint'  => false,
				'openWorldHint'   => false,
			),
			'securitySchemes' => array(
				array(
					'type' => 'noauth',
				),
			),
		),
		array(
			'name'        => KUNGFU_MCP_UPDATE_TOOL_NAME,
			'description' => 'Update an existing WordPress post title, plain-text content, or tags without changing its publication status. Use this when the user asks to revise a post by ID.',
			'inputSchema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => 'The WordPress post ID to update.',
					),
					'title'   => array(
						'type'        => 'string',
						'minLength'   => 1,
						'maxLength'   => 200,
						'description' => 'Optional replacement post title.',
					),
					'content' => array(
						'type'        => 'string',
						'minLength'   => 1,
						'maxLength'   => 100000,
						'description' => 'Optional replacement plain-text post content. HTML is removed.',
					),
					'tags'    => array(
						'type'        => 'array',
						'maxItems'    => 30,
						'uniqueItems' => true,
						'description' => 'Optional complete replacement list of tag names. Send an empty list to remove all tags.',
						'items'       => array(
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 100,
						),
					),
				),
				'required'             => array( 'post_id' ),
				'anyOf'                => array(
					array( 'required' => array( 'title' ) ),
					array( 'required' => array( 'content' ) ),
					array( 'required' => array( 'tags' ) ),
				),
				'additionalProperties' => false,
			),
			'annotations' => array(
				'title'           => 'Update a post',
				'readOnlyHint'    => false,
				'destructiveHint' => true,
				'idempotentHint'  => true,
				'openWorldHint'   => false,
			),
			'securitySchemes' => array(
				array(
					'type' => 'noauth',
				),
			),
		),
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

	if ( kungfu_2026_mcp_length( $title ) > 200 || kungfu_2026_mcp_length( $content ) > 2000 ) {
		return array(
			'isError' => true,
			'content' => array(
				array(
					'type' => 'text',
					'text' => 'Title must be at most 200 characters and content at most 2,000 characters.',
				),
			),
		);
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'draft',
			'post_title'   => wp_strip_all_tags( $title ),
			'post_content' => sanitize_textarea_field( $content ),
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
					'text' => 'WordPress could not create the draft.',
				),
			),
		);
	}

	return array(
		'content' => array(
			array(
				'type' => 'text',
				'text' => sprintf(
					'Draft created successfully. Post ID: %d. Title: %s.',
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
 * Run update_post and return an MCP tool result.
 *
 * @param array $arguments Tool arguments.
 * @return array
 */
function kungfu_2026_mcp_update_post( $arguments ) {
	$post_id = isset( $arguments['post_id'] ) && is_int( $arguments['post_id'] ) ? $arguments['post_id'] : 0;
	$post    = $post_id > 0 ? get_post( $post_id ) : null;

	if ( ! $post || 'post' !== $post->post_type ) {
		return kungfu_2026_mcp_tool_error( 'A valid WordPress post ID is required.' );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return kungfu_2026_mcp_tool_error( 'The connected WordPress user cannot edit this post.' );
	}

	$has_title   = array_key_exists( 'title', $arguments );
	$has_content = array_key_exists( 'content', $arguments );
	$has_tags    = array_key_exists( 'tags', $arguments );

	if ( ! $has_title && ! $has_content && ! $has_tags ) {
		return kungfu_2026_mcp_tool_error( 'Provide at least one of title, content, or tags.' );
	}

	$update         = array( 'ID' => $post_id );
	$updated_fields = array();

	if ( $has_title ) {
		$title = is_string( $arguments['title'] ) ? trim( $arguments['title'] ) : '';
		if ( '' === $title || kungfu_2026_mcp_length( $title ) > 200 ) {
			return kungfu_2026_mcp_tool_error( 'Title must contain 1 to 200 characters.' );
		}
		$update['post_title'] = wp_strip_all_tags( $title );
		$updated_fields[]     = 'title';
	}

	if ( $has_content ) {
		$content = is_string( $arguments['content'] ) ? trim( $arguments['content'] ) : '';
		if ( '' === $content || kungfu_2026_mcp_length( $content ) > 100000 ) {
			return kungfu_2026_mcp_tool_error( 'Content must contain 1 to 100,000 characters.' );
		}
		$update['post_content'] = sanitize_textarea_field( $content );
		$updated_fields[]       = 'content';
	}

	$tag_names = array();
	if ( $has_tags ) {
		if ( ! is_array( $arguments['tags'] ) || count( $arguments['tags'] ) > 30 ) {
			return kungfu_2026_mcp_tool_error( 'Tags must be a list containing at most 30 tag names.' );
		}

		foreach ( $arguments['tags'] as $tag ) {
			$tag = is_string( $tag ) ? trim( $tag ) : '';
			if ( '' === $tag || kungfu_2026_mcp_length( $tag ) > 100 ) {
				return kungfu_2026_mcp_tool_error( 'Each tag must contain 1 to 100 characters.' );
			}
			$tag_names[] = sanitize_text_field( $tag );
		}

		$tag_names        = array_values( array_unique( $tag_names ) );
		$updated_fields[] = 'tags';
	}

	if ( $has_title || $has_content ) {
		$result = wp_update_post( wp_slash( $update ), true );
		if ( is_wp_error( $result ) ) {
			return kungfu_2026_mcp_tool_error( 'WordPress could not update this post.' );
		}
	}

	if ( $has_tags ) {
		$result = wp_set_post_tags( $post_id, $tag_names, false );
		if ( is_wp_error( $result ) ) {
			return kungfu_2026_mcp_tool_error( 'WordPress could not update this post’s tags.' );
		}
	}

	return array(
		'content' => array(
			array(
				'type' => 'text',
				'text' => sprintf(
					'Post %d updated successfully. Updated: %s. Publication status remains %s.',
					$post_id,
					implode( ', ', $updated_fields ),
					$post->post_status
				),
			),
		),
	);
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
					'version' => '0.2.0',
				),
				'instructions'    => 'Use add_post to create a draft. Use update_post to change an existing post title, plain-text content, or tags. Neither tool publishes posts or changes publication status.',
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

		if ( KUNGFU_MCP_UPDATE_TOOL_NAME === $tool_name ) {
			return kungfu_2026_mcp_result( $id, kungfu_2026_mcp_update_post( $arguments ) );
		}

		return kungfu_2026_mcp_error( $id, -32602, 'Unknown tool.' );
	}

	return kungfu_2026_mcp_error( $id, -32601, 'Method not found.' );
}
