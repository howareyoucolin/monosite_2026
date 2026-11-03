<?php
/** Lossless, revision-checked Gutenberg editing for the existing MCP endpoint. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kungfu_2026_mcp_json_result( $data ) {
	return array(
		'structuredContent' => $data,
		'content' => array( array( 'type' => 'text', 'text' => wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) ),
	);
}

function kungfu_2026_mcp_editing_tools() {
	$tools = array();
	$common = array(
		'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
		'content_revision' => array( 'type' => 'string', 'pattern' => '^[a-f0-9]{64}$', 'description' => 'Exact fingerprint from get_post_for_editing; reread after every write or conflict.' ),
		'block_id' => array( 'type' => 'string', 'description' => 'Exact top-level block ID from get_post_for_editing. Scoped to content_revision.' ),
		'section_heading' => array( 'type' => 'string', 'minLength' => 1, 'description' => 'Exact visible H2 text. Duplicate headings are rejected. With block_id, also asserts that the block is inside this section.' ),
		'paragraph_text' => array( 'type' => 'string', 'minLength' => 1, 'description' => 'Alternative to block_id: exact full visible paragraph text, unique within section_heading (or the post).' ),
		'dry_run' => array( 'type' => 'boolean', 'default' => true, 'description' => 'Defaults to preview only; no confirmation needed for true. Set false only after summarizing the proposed edit and receiving a separate POST reply from the user.' ),
	);
	foreach ( array( 'insert_content', 'update_content_block' ) as $name ) {
		$properties = $common;
		if ( 'insert_content' === $name ) {
			$properties['paragraphs'] = array( 'type' => 'array', 'minItems' => 1, 'maxItems' => 100, 'items' => array( 'type' => 'string', 'minLength' => 1 ), 'description' => 'Plain-text paragraphs; each is escaped and wrapped as a native Gutenberg paragraph. Use either paragraphs or content.' );
			$description = 'Insert paragraphs or complete Gutenberg blocks immediately after block_id / exact paragraph_text. With section_heading alone, append inside that H2 section before the next H1/H2. Existing bytes and publication status are preserved. Preview first; stale or ambiguous targets fail.';
		} else {
			$description = 'Replace exactly one top-level Gutenberg block, selected by block_id or unique exact paragraph_text, optionally constrained to an exact H2 section. content must be one complete block of the same type. Edit its HTML while retaining desired attributes and emphasis. No full-post or line-range replacement. Preview first.';
		}
		$properties['content'] = array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 100000, 'description' => 'Complete balanced serialized Gutenberg block markup. Retain existing block attributes and HTML when replacing a block.' );
		$schema = array( 'type' => 'object', 'properties' => $properties, 'required' => array_merge( array( 'post_id', 'content_revision' ), 'update_content_block' === $name ? array( 'content' ) : array() ), 'additionalProperties' => false );
		$schema['anyOf'] = array( array( 'required' => array( 'block_id' ) ), array( 'required' => array( 'paragraph_text' ) ) );
		if ( 'insert_content' === $name ) {
			$schema['anyOf'][] = array( 'required' => array( 'section_heading' ) );
			$schema['oneOf'] = array( array( 'required' => array( 'content' ) ), array( 'required' => array( 'paragraphs' ) ) );
		}
		$tools[] = array(
			'name' => $name, 'description' => $description . ' ' . kungfu_2026_mcp_write_confirmation_instructions(),
			'inputSchema' => $schema,
			'annotations' => array( 'readOnlyHint' => false, 'destructiveHint' => 'update_content_block' === $name, 'idempotentHint' => false, 'openWorldHint' => false ),
			'securitySchemes' => array( array( 'type' => 'noauth' ) ),
		);
	}
	$tools[] = array(
		'name' => 'update_post', 'description' => 'Update only a post title or tags, preserving content and publication status. For content use get_post_for_editing, then insert_content or update_content_block. Legacy content and line replacement arguments are rejected. ' . kungfu_2026_mcp_write_confirmation_instructions(),
		'inputSchema' => array( 'type' => 'object', 'properties' => array(
			'post_id' => $common['post_id'],
			'title' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 200 ),
			'tags' => array( 'type' => 'array', 'maxItems' => 30, 'items' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 100 ) ),
		), 'required' => array( 'post_id' ), 'additionalProperties' => false ),
		'annotations' => array( 'readOnlyHint' => false, 'destructiveHint' => true, 'idempotentHint' => true, 'openWorldHint' => false ),
		'securitySchemes' => array( array( 'type' => 'noauth' ) ),
	);
	return $tools;
}

/**
 * Use WordPress's tokenizer (including its byte offsets), but reject its normal
 * recovery from unmatched delimiters. Never serialize existing blocks: offsets
 * allow us to splice exactly one span while retaining all other bytes.
 * Nested blocks are exposed as part of their top-level parent, not as independent
 * targets. Editing a child requires replacing that complete parent block.
 */
function kungfu_2026_mcp_block_spans( $content ) {
	$parser = new WP_Block_Parser();
	$parser->document = $content;
	$parser->offset = 0;
	$stack = array();
	$spans = array();
	$last_end = 0;
	$start = 0;
	while ( true ) {
		list( $type, $name, $attrs, $offset, $length ) = $parser->next_token();
		if ( 'no-more-tokens' === $type ) {
			break;
		}
		if ( preg_match( '/<!--\s*\/?wp:/', substr( $content, $parser->offset, $offset - $parser->offset ) ) ) {
			throw new RuntimeException( 'Malformed Gutenberg delimiter; no edit was made.' );
		}
		$comment = substr( $content, $offset, $length );
		if ( false !== strpos( $comment, '{' ) && null === $attrs && 'block-closer' !== $type ) {
			throw new RuntimeException( 'Invalid Gutenberg block attributes.' );
		}
		if ( empty( $stack ) && '' !== trim( substr( $content, $last_end, $offset - $last_end ) ) ) {
			throw new RuntimeException( 'Unwrapped HTML, freeform content, or malformed block comments cannot be edited safely. Convert to native Gutenberg blocks first.' );
		}
		if ( 'block-closer' === $type ) {
			if ( ! $stack || array_pop( $stack ) !== $name ) {
				throw new RuntimeException( 'Unbalanced or mismatched Gutenberg block comments; no edit was made.' );
			}
		} elseif ( 'block-opener' === $type ) {
			if ( ! $stack ) {
				$start = $offset;
			}
			$stack[] = $name;
		} elseif ( 'void-block' === $type && ! $stack ) {
			$start = $offset;
		}
		$parser->offset = $offset + $length;
		if ( ! $stack ) {
			$raw = substr( $content, $start, $parser->offset - $start );
			$parsed = parse_blocks( $raw );
			$block = $parsed[0];
			$spans[] = array(
				'block_id' => 'b' . ( count( $spans ) + 1 ) . '-' . substr( hash( 'sha256', $raw ), 0, 16 ),
				'block_name' => $block['blockName'], 'attributes' => $block['attrs'],
				'start' => $start, 'end' => $parser->offset, 'raw' => $raw,
				'text' => html_entity_decode( wp_strip_all_tags( $block['innerHTML'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'line_start' => substr_count( substr( $content, 0, $start ), "\n" ) + 1,
				'line_end' => substr_count( substr( $content, 0, $parser->offset ), "\n" ) + 1,
			);
			$last_end = $parser->offset;
		}
	}
	if ( $stack || preg_match( '/<!--\s*\/?wp:/', substr( $content, $parser->offset ) ) || '' !== trim( substr( $content, $last_end ) ) ) {
		throw new RuntimeException( 'Incomplete Gutenberg blocks or unwrapped content; no edit was made.' );
	}
	return $spans;
}

/** Fingerprint exact content plus title/status/time, including same-content saves. */
function kungfu_2026_mcp_edit_revision( $post ) {
	return hash( 'sha256', wp_json_encode( array( (int) $post->ID, $post->post_content, $post->post_title, $post->post_status, $post->post_modified_gmt ) ) );
}

function kungfu_2026_mcp_h2_sections( $blocks ) {
	$sections = array();
	foreach ( $blocks as $index => $block ) {
		if ( 'core/heading' !== $block['block_name'] ) {
			continue;
		}
		preg_match( '/<h([1-6])\b/i', $block['raw'], $match );
		$level = isset( $match[1] ) ? (int) $match[1] : ( $block['attributes']['level'] ?? 2 );
		if ( $level <= 2 && $sections && count( $blocks ) === $sections[ count( $sections ) - 1 ]['end_index'] ) {
			$sections[ count( $sections ) - 1 ]['end_index'] = $index;
		}
		if ( 2 === $level ) {
			$sections[] = array( 'heading' => trim( $block['text'] ), 'heading_block_id' => $block['block_id'], 'start_index' => $index + 1, 'end_index' => count( $blocks ) );
		}
	}
	return $sections;
}

function kungfu_2026_mcp_editing_snapshot( $post ) {
	$data = array( 'post_id' => (int) $post->ID, 'title' => $post->post_title, 'status' => $post->post_status, 'content' => $post->post_content, 'content_revision' => kungfu_2026_mcp_edit_revision( $post ), 'blocks' => array(), 'h2_sections' => array(), 'target_scope' => 'Top-level blocks only. IDs and line references are valid for this revision; reread after edits.' );
	try {
		$data['blocks'] = kungfu_2026_mcp_block_spans( $post->post_content );
		$data['h2_sections'] = kungfu_2026_mcp_h2_sections( $data['blocks'] );
	} catch ( RuntimeException $e ) {
		$data['editing_error'] = $e->getMessage();
	}
	return $data;
}

/** Build one exact byte splice. Does not write, sanitize or normalize old bytes. */
function kungfu_2026_mcp_plan_edit( $post, $args, $operation ) {
	if ( ! hash_equals( kungfu_2026_mcp_edit_revision( $post ), $args['content_revision'] ) ) {
		throw new RuntimeException( 'Revision conflict. Call get_post_for_editing again; no changes were applied.' );
	}
	$blocks = kungfu_2026_mcp_block_spans( $post->post_content );
	$lo = 0;
	$hi = count( $blocks );
	if ( isset( $args['section_heading'] ) ) {
		$sections = array_values( array_filter( kungfu_2026_mcp_h2_sections( $blocks ), function ( $s ) use ( $args ) { return $s['heading'] === $args['section_heading']; } ) );
		if ( count( $sections ) !== 1 ) {
			throw new RuntimeException( 'Section heading is missing or ambiguous. Use the exact unique H2 text from get_post_for_editing.' );
		}
		$lo = $sections[0]['start_index'];
		$hi = $sections[0]['end_index'];
	}
	if ( isset( $args['block_id'], $args['paragraph_text'] ) ) {
		throw new RuntimeException( 'Choose block_id or paragraph_text, not both.' );
	}
	$matches = array();
	for ( $i = $lo; $i < $hi; ++$i ) {
		$b = $blocks[$i];
		if ( ( isset( $args['block_id'] ) && $b['block_id'] === $args['block_id'] ) || ( isset( $args['paragraph_text'] ) && 'core/paragraph' === $b['block_name'] && trim( $b['text'] ) === $args['paragraph_text'] ) ) {
			$matches[] = $b;
		}
	}
	$has_target = isset( $args['block_id'] ) || isset( $args['paragraph_text'] );
	if ( $has_target && count( $matches ) !== 1 ) {
		throw new RuntimeException( 'Block/paragraph target is missing, ambiguous, or outside the named section. No edit was made.' );
	}
	if ( ! $has_target && ( 'update_content_block' === $operation || ! isset( $args['section_heading'] ) ) ) {
		throw new RuntimeException( 'An exact block/paragraph target is required (or section_heading alone for insertion at section end).' );
	}
	if ( isset( $args['paragraphs'] ) ) {
		if ( isset( $args['content'] ) || 'insert_content' !== $operation || ! is_array( $args['paragraphs'] ) || ! $args['paragraphs'] || count( $args['paragraphs'] ) > 100 ) {
			throw new RuntimeException( 'Provide either paragraphs (1–100 plain strings) or serialized content.' );
		}
		$new_blocks = array();
		foreach ( $args['paragraphs'] as $text ) {
			if ( ! is_string( $text ) || '' === trim( $text ) ) {
				throw new RuntimeException( 'Each paragraph must be a nonempty string.' );
			}
			$new_blocks[] = "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";
		}
		$new = implode( "\n\n", $new_blocks );
	} else {
		$new = $args['content'] ?? '';
		if ( ! is_string( $new ) || '' === trim( $new ) ) {
			throw new RuntimeException( 'Nonempty serialized Gutenberg content is required.' );
		}
	}
	if ( kungfu_2026_mcp_length( $new ) > 100000 ) {
		throw new RuntimeException( 'Inserted/replacement content exceeds 100,000 characters.' );
	}
	$new_spans = kungfu_2026_mcp_block_spans( $new );
	if ( ! $new_spans ) {
		throw new RuntimeException( 'At least one complete Gutenberg block is required.' );
	}
	// Validate only new bytes. Never run existing content through a serializer or KSES.
	if ( wp_kses_post( $new ) !== $new || filter_block_content( $new ) !== $new ) {
		throw new RuntimeException( 'New content would be changed by WordPress safe-content filtering. Supply safe canonical block markup; no edit was made.' );
	}
	if ( 'update_content_block' === $operation ) {
		if ( count( $new_spans ) !== 1 || $new_spans[0]['block_name'] !== $matches[0]['block_name'] ) {
			throw new RuntimeException( 'Replacement must be exactly one complete block of the same type.' );
		}
		$start = $matches[0]['start'];
		$end = $matches[0]['end'];
	} else {
		$start = $has_target ? $matches[0]['end'] : ( $hi < count( $blocks ) ? $blocks[$hi]['start'] : strlen( $post->post_content ) );
		$end = $start;
		$new = "\n\n" . $new . "\n\n";
	}
	$result = substr( $post->post_content, 0, $start ) . $new . substr( $post->post_content, $end );
	kungfu_2026_mcp_block_spans( $result );
	return array( 'content' => $result, 'start_byte' => $start, 'end_byte' => $end, 'before' => substr( $post->post_content, $start, $end - $start ), 'after' => $new, 'unchanged_prefix_sha256' => hash( 'sha256', substr( $post->post_content, 0, $start ) ), 'unchanged_suffix_sha256' => hash( 'sha256', substr( $post->post_content, $end ) ) );
}

/**
 * InnoDB row lock makes the check and WP write atomic against concurrent writes,
 * including wp-admin. Always save a pre-edit WordPress revision; reject if that
 * cannot be done. No new public endpoint, credentials, or publication controls.
 */
function kungfu_2026_mcp_edit_content( $args, $operation ) {
	global $wpdb;
	$allowed = array( 'post_id', 'content_revision', 'block_id', 'section_heading', 'paragraph_text', 'dry_run', 'content' );
	if ( 'insert_content' === $operation ) {
		$allowed[] = 'paragraphs';
	}
	if ( array_diff( array_keys( $args ), $allowed ) || ! isset( $args['post_id'], $args['content_revision'] ) || ! is_int( $args['post_id'] ) || $args['post_id'] < 1 || ! is_string( $args['content_revision'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $args['content_revision'] ) ) {
		return kungfu_2026_mcp_tool_error( 'Valid integer post_id and content_revision are required; unknown arguments are rejected.' );
	}
	foreach ( array( 'block_id', 'section_heading', 'paragraph_text', 'content' ) as $key ) {
		if ( array_key_exists( $key, $args ) && ( ! is_string( $args[$key] ) || '' === trim( $args[$key] ) ) ) {
			return kungfu_2026_mcp_tool_error( $key . ' must be a nonempty string.' );
		}
	}
	if ( array_key_exists( 'dry_run', $args ) && ! is_bool( $args['dry_run'] ) ) {
		return kungfu_2026_mcp_tool_error( 'dry_run must be a boolean.' );
	}
	$dry_run = $args['dry_run'] ?? true;
	$post_id = $args['post_id'];
	$in_transaction = false;
	$revision_id = null;
	try {
		if ( ! $dry_run ) {
			$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $wpdb->posts ) );
			if ( 'INNODB' !== strtoupper( (string) $engine ) || false === $wpdb->query( 'START TRANSACTION' ) ) {
				throw new RuntimeException( 'Safe edits require transactional InnoDB posts storage. No edit was made.' );
			}
			$in_transaction = true;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d" . ( $dry_run ? '' : ' FOR UPDATE' ), $post_id ) );
		if ( ! $row || 'post' !== $row->post_type ) {
			throw new RuntimeException( 'A valid WordPress post ID is required.' );
		}
		// Bypass stale object caches for authorization and wp_update_post's merge.
		clean_post_cache( $post_id );
		$post = new WP_Post( $row );
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			throw new RuntimeException( 'The connected WordPress user cannot edit this post.' );
		}
		$plan = kungfu_2026_mcp_plan_edit( $post, $args, $operation );
		if ( ! $dry_run ) {
			if ( ! wp_revisions_enabled( $post ) ) {
				throw new RuntimeException( 'WordPress revisions must be enabled before applying a safe edit.' );
			}
			$result = wp_update_post( wp_slash( array( 'ID' => $post_id, 'post_content' => $plan['content'] ) ), true );
			$saved = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $post_id ) );
			if ( is_wp_error( $result ) || ! $result || ! $saved || $saved->post_content !== $plan['content'] || $saved->post_status !== $post->post_status || $saved->post_title !== $post->post_title ) {
				throw new RuntimeException( 'WordPress filters changed content, title, or status unexpectedly. Database edit rolled back.' );
			}
			// Save the pre-edit object after normal revision pruning, so a small
			// retention limit cannot delete the revision we return to the caller.
			$revision_id = _wp_put_post_revision( $post );
			if ( is_wp_error( $revision_id ) || ! $revision_id ) {
				throw new RuntimeException( 'Could not save the pre-edit revision; no edit was applied.' );
			}
			$backup = get_post( $revision_id );
			if ( ! $backup || $backup->post_content !== $post->post_content || $backup->post_title !== $post->post_title ) {
				throw new RuntimeException( 'The pre-edit revision was changed by a filter. Database edit rolled back.' );
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				throw new RuntimeException( 'Database commit failed.' );
			}
			$in_transaction = false;
			$post = new WP_Post( $saved );
			clean_post_cache( $post_id );
		}
		return kungfu_2026_mcp_json_result( array(
			'post_id' => $post_id, 'dry_run' => $dry_run, 'applied' => ! $dry_run,
			'message' => $dry_run ? 'Preview only; no post or revision was written.' : 'Targeted edit applied. Other content bytes and publication status preserved.',
			'status' => $post->post_status, 'content_revision' => kungfu_2026_mcp_edit_revision( $post ),
			'restore_revision_id' => $revision_id,
			'restore_url' => $revision_id ? admin_url( 'revision.php?revision=' . $revision_id ) : null,
			'change' => $plan,
		) );
	} catch ( Throwable $e ) {
		if ( $in_transaction ) {
			$wpdb->query( 'ROLLBACK' );
			clean_post_cache( $post_id );
			if ( is_int( $revision_id ) ) {
				clean_post_cache( $revision_id );
			}
		}
		return kungfu_2026_mcp_tool_error( $e->getMessage() );
	}
}
