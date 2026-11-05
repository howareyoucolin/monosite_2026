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

/** Writing conventions observed in live chapters; advice, never a schema rule. */
function kungfu_2026_mcp_formatting_guidance() {
	return 'Suggested house style (advisory, follow explicit user formatting requests): keep the chapter title in the WordPress title field, without repeating an H1 in the body. Match the section heading level already used in the target post. Default to native Gutenberg H3 headings for new chapter sections, usually "Section N: Descriptive Title", numbered within the chapter. Most reviewed chapters use H3; several recent chapters use H2. Preserve either existing format unless the user asks to standardize it. Use separate paragraph blocks for narration, dialogue, and short action/sound beats; use <em> for internal thoughts and retain existing emphasis. Preserve the voice, names, punctuation, and unrelated content; do not invent plot details or rewrite prose just to format it. Prefer balanced Gutenberg comments and blank lines between blocks. Character wiki pages should be ordinary readable WordPress content: headings, paragraphs, short lists, images, and galleries, rather than a JSON document or code block. Use the character name as the post title. Suggested descriptive H3 headings are Overview, Appearance, Skills & abilities, Weapons & equipment, Personality & background, and References, without chapter section numbers. Under Overview summarize known aliases, role, and affiliations. Under Appearance describe known physical features and clothing, with reference images or galleries and useful captions. Under Skills & abilities describe each named technique, what it does, and known strengths, limitations, or weaknesses; bold skill names when useful. Describe equipment, personality, history, and relationships in their relevant sections. Link to source chapters for factual details, and include visual reference sources or image credits when available. Distinguish confirmed story facts from proposed ideas or visual inspiration; do not invent missing details, abilities, images, or source links. Omit unsupported details, or mark them as unknown when useful. Keep the outline flexible: rename, add, combine, or omit sections to suit the character and the user request. Preserve the existing heading style and unrelated content when updating an entry. JSON remains the MCP request format, not the recommended character page body. Other safe HTML, text, heading levels, and block types are allowed; these suggestions do not restrict editing.';
}

function kungfu_2026_mcp_supported_post_type( $type ) {
	return in_array( $type, array( 'post', 'page', 'character' ), true ) && post_type_exists( $type );
}

/** Validate only supplied bytes; do not rewrite unrelated content. */
function kungfu_2026_mcp_validate_new_content( $content ) {
	if ( ! is_string( $content ) || kungfu_2026_mcp_length( $content ) > 100000 ) {
		throw new RuntimeException( 'Content must be a string of at most 100,000 characters.' );
	}
	if ( wp_kses_post( $content ) !== $content || filter_block_content( $content ) !== $content ) {
		throw new RuntimeException( 'New content would be changed by WordPress safe-content filtering. Supply safe markup; no edit was made.' );
	}
	if ( preg_match( '/<!--\s*\/?wp:/', $content ) ) {
		kungfu_2026_mcp_block_spans( $content, true );
	}
}

/** A complete body replacement can also edit classic/plain-text posts. */
function kungfu_2026_mcp_plan_post_update( $post, $args ) {
	$fields = array();
	if ( array_key_exists( 'title', $args ) ) {
		if ( ! is_string( $args['title'] ) || '' === trim( wp_strip_all_tags( $args['title'] ) ) || kungfu_2026_mcp_length( $args['title'] ) > 200 ) {
			throw new RuntimeException( 'Title must contain 1 to 200 characters.' );
		}
		$fields['post_title'] = wp_strip_all_tags( $args['title'] );
	}
	if ( array_key_exists( 'excerpt', $args ) ) {
		if ( ! is_string( $args['excerpt'] ) || kungfu_2026_mcp_length( $args['excerpt'] ) > 10000 || wp_kses_post( $args['excerpt'] ) !== $args['excerpt'] ) {
			throw new RuntimeException( 'Excerpt must be safe text/HTML of at most 10,000 characters.' );
		}
		$fields['post_excerpt'] = $args['excerpt'];
	}
	if ( array_key_exists( 'tags', $args ) ) {
		if ( ! is_object_in_taxonomy( $post->post_type, 'post_tag' ) || ! is_array( $args['tags'] ) || count( $args['tags'] ) > 30 ) {
			throw new RuntimeException( 'Tags must be a list of at most 30 names, and the post type must support tags.' );
		}
		foreach ( $args['tags'] as $tag ) {
			if ( ! is_string( $tag ) || '' === trim( sanitize_text_field( $tag ) ) || kungfu_2026_mcp_length( $tag ) > 100 || false !== strpos( $tag, ',' ) ) {
				throw new RuntimeException( 'Each tag must contain 1 to 100 characters and no comma.' );
			}
		}
		$fields['tags_input'] = array_map( 'sanitize_text_field', $args['tags'] );
	}
	$has_content = array_key_exists( 'content', $args );
	if ( ! $has_content && ! $fields ) {
		throw new RuntimeException( 'Provide content, title, excerpt, or tags.' );
	}
	$content = $has_content ? $args['content'] : $post->post_content;
	if ( $has_content ) {
		kungfu_2026_mcp_validate_new_content( $content );
	}
	return array(
		'content' => $content, 'fields' => $fields,
		'before' => $post->post_content, 'after' => $content,
		'metadata_before' => array( 'title' => $post->post_title, 'excerpt' => $post->post_excerpt, 'tags' => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ) ),
		'start_byte' => 0, 'end_byte' => strlen( $post->post_content ),
	);
}

function kungfu_2026_mcp_editing_tools() {
	$tools = array();
	$common = array(
		'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
		'content_revision' => array( 'type' => 'string', 'pattern' => '^[a-f0-9]{64}$', 'description' => 'Exact fingerprint from get_post_for_editing; reread after every write or conflict.' ),
		'block_id' => array( 'type' => 'string', 'description' => 'Exact top-level block ID from get_post_for_editing. Scoped to content_revision.' ),
		'section_heading' => array( 'type' => 'string', 'minLength' => 1, 'description' => 'Exact visible section heading (match the post; default H3 for new sections; all heading levels supported). Duplicate headings are rejected. With block_id, asserts the block is inside this section.' ),
		'paragraph_text' => array( 'type' => 'string', 'minLength' => 1, 'description' => 'Alternative to block_id: exact full visible paragraph text, unique within section_heading (or the post).' ),
		'dry_run' => array( 'type' => 'boolean', 'default' => true, 'description' => 'Preview by default. Set false only after summarizing the proposed edit and receiving a separate POST reply from the user.' ),
	);
	foreach ( array( 'insert_content', 'update_content_block' ) as $name ) {
		$properties = $common;
		if ( 'insert_content' === $name ) {
			$properties['position'] = array( 'type' => 'string', 'enum' => array( 'before', 'after', 'start', 'end' ), 'default' => 'after', 'description' => 'Before/after a target; section_heading alone defaults to section end (before means section start). Start/end target the whole body and need no heading or block.' );
			$properties['paragraphs'] = array( 'type' => 'array', 'minItems' => 1, 'maxItems' => 100, 'items' => array( 'type' => 'string', 'minLength' => 1 ), 'description' => 'Plain-text paragraphs; each is escaped and wrapped as a native Gutenberg paragraph. Use either paragraphs or content.' );
			$description = 'Insert text, safe HTML, or Gutenberg blocks before/after an exact block/paragraph, inside a named section (including H3 and H2), or at the start/end of the body. Works on posts, pages, and characters. Existing bytes and publication status are preserved. Preview first; stale or ambiguous targets fail.';
		} else {
			$description = 'Replace a selected top-level block with any text, safe HTML, or Gutenberg blocks, including a different block type or several blocks. Empty content deletes the selected block. Optional exact section heading constrains the target. Use update_post for whole-body edits, including legacy content. Preview first.';
		}
		$properties['content'] = array( 'type' => 'string', 'maxLength' => 100000, 'description' => 'Text, WordPress-safe HTML, or balanced Gutenberg blocks. Empty string deletes a selected block. Prefer native blocks and preserve unrelated formatting. ' . kungfu_2026_mcp_formatting_guidance() );
		$schema = array( 'type' => 'object', 'properties' => $properties, 'required' => array_merge( array( 'post_id', 'content_revision' ), 'update_content_block' === $name ? array( 'content' ) : array() ), 'additionalProperties' => false );
		$schema['anyOf'] = array( array( 'required' => array( 'block_id' ) ), array( 'required' => array( 'paragraph_text' ) ) );
		if ( 'insert_content' === $name ) {
			$schema['anyOf'][] = array( 'required' => array( 'section_heading' ) );
			$schema['anyOf'][] = array( 'required' => array( 'position' ), 'properties' => array( 'position' => array( 'enum' => array( 'start', 'end' ) ) ) );
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
		'name' => 'update_post', 'description' => 'Update any combination of title, tags, excerpt, and the COMPLETE body of a post, page, or character. Content replaces the whole body; include all content to retain, or use a targeted tool. Empty content clears the body. Publication status is preserved. Read first, preview, then apply with the same revision after POST confirmation. ' . kungfu_2026_mcp_write_confirmation_instructions(),
		'inputSchema' => array( 'type' => 'object', 'properties' => array(
			'post_id' => $common['post_id'],
			'content_revision' => $common['content_revision'],
			'dry_run' => $common['dry_run'],
			'content' => array( 'type' => 'string', 'maxLength' => 100000, 'description' => 'The complete replacement body as text, safe HTML, or Gutenberg blocks. ' . kungfu_2026_mcp_formatting_guidance() ),
			'excerpt' => array( 'type' => 'string', 'maxLength' => 10000 ),
			'title' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 200 ),
			'tags' => array( 'type' => 'array', 'maxItems' => 30, 'items' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 100 ) ),
		), 'required' => array( 'post_id', 'content_revision' ), 'additionalProperties' => false ),
		'annotations' => array( 'readOnlyHint' => false, 'destructiveHint' => true, 'idempotentHint' => false, 'openWorldHint' => false ),
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
function kungfu_2026_mcp_block_spans( $content, $allow_freeform = false ) {
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
		if ( ! $allow_freeform && empty( $stack ) && '' !== trim( substr( $content, $last_end, $offset - $last_end ) ) ) {
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
	if ( $stack || preg_match( '/<!--\s*\/?wp:/', substr( $content, $parser->offset ) ) || ( ! $allow_freeform && '' !== trim( substr( $content, $last_end ) ) ) ) {
		throw new RuntimeException( 'Incomplete Gutenberg blocks or unwrapped content; no edit was made.' );
	}
	return $spans;
}

/** Fingerprint exact content plus title/status/time, including same-content saves. */
function kungfu_2026_mcp_edit_revision( $post ) {
	return hash( 'sha256', wp_json_encode( array( (int) $post->ID, $post->post_content, $post->post_title, $post->post_status, $post->post_excerpt, wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ), $post->post_modified_gmt ) ) );
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

/** Every heading owns content up to the next heading of equal or higher rank. */
function kungfu_2026_mcp_heading_sections( $blocks ) {
	$sections = array();
	foreach ( $blocks as $index => $block ) {
		if ( 'core/heading' !== $block['block_name'] ) {
			continue;
		}
		preg_match( '/<h([1-6])\b/i', $block['raw'], $match );
		$level = isset( $match[1] ) ? (int) $match[1] : ( $block['attributes']['level'] ?? 2 );
		foreach ( $sections as &$section ) {
			if ( $level <= $section['level'] && count( $blocks ) === $section['end_index'] ) {
				$section['end_index'] = $index;
			}
		}
		unset( $section );
		$sections[] = array( 'heading' => trim( $block['text'] ), 'level' => $level, 'heading_block_id' => $block['block_id'], 'start_index' => $index + 1, 'end_index' => count( $blocks ) );
	}
	return $sections;
}

function kungfu_2026_mcp_editing_snapshot( $post ) {
	$data = array( 'post_id' => (int) $post->ID, 'title' => $post->post_title, 'status' => $post->post_status, 'content' => $post->post_content, 'content_revision' => kungfu_2026_mcp_edit_revision( $post ), 'blocks' => array(), 'h2_sections' => array(), 'heading_sections' => array(), 'post_type' => $post->post_type, 'excerpt' => $post->post_excerpt, 'tags' => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ), 'formatting_guidance' => kungfu_2026_mcp_formatting_guidance(), 'target_scope' => 'Block IDs target top-level blocks. For any body use update_post, or insert_content with position start/end. Reread after edits.' );
	try {
		$data['blocks'] = kungfu_2026_mcp_block_spans( $post->post_content, true );
		$data['h2_sections'] = kungfu_2026_mcp_h2_sections( $data['blocks'] );
		$data['heading_sections'] = kungfu_2026_mcp_heading_sections( $data['blocks'] );
	} catch ( RuntimeException $e ) {
		$data['block_targeting_error'] = $e->getMessage();
	}
	return $data;
}

/** Build one exact byte splice. Does not write, sanitize or normalize old bytes. */
function kungfu_2026_mcp_plan_edit( $post, $args, $operation ) {
	if ( ! hash_equals( kungfu_2026_mcp_edit_revision( $post ), $args['content_revision'] ) ) {
		throw new RuntimeException( 'Revision conflict. Call get_post_for_editing again; no changes were applied.' );
	}
	if ( 'update_post' === $operation ) {
		return kungfu_2026_mcp_plan_post_update( $post, $args );
	}
	$position = $args['position'] ?? 'after';
	$at_edge = 'insert_content' === $operation && in_array( $position, array( 'start', 'end' ), true );
	if ( $at_edge && ( isset( $args['block_id'] ) || isset( $args['paragraph_text'] ) || isset( $args['section_heading'] ) ) ) {
		throw new RuntimeException( 'Start/end cannot be combined with a block, paragraph, or section target.' );
	}
	$blocks = $at_edge ? array() : kungfu_2026_mcp_block_spans( $post->post_content, true );
	$lo = 0;
	$hi = count( $blocks );
	if ( isset( $args['section_heading'] ) ) {
		$sections = array_values( array_filter( kungfu_2026_mcp_heading_sections( $blocks ), function ( $s ) use ( $args ) { return $s['heading'] === $args['section_heading']; } ) );
		if ( count( $sections ) !== 1 ) {
			throw new RuntimeException( 'Section heading is missing or ambiguous. Use exact unique heading text from heading_sections in get_post_for_editing.' );
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
	if ( ! $at_edge && ! $has_target && ( 'update_content_block' === $operation || ! isset( $args['section_heading'] ) ) ) {
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
		if ( ! is_string( $new ) || ( 'insert_content' === $operation && '' === trim( $new ) ) ) {
			throw new RuntimeException( 'Content must be a string; insertion content must be nonempty.' );
		}
	}
	if ( kungfu_2026_mcp_length( $new ) > 100000 ) {
		throw new RuntimeException( 'Inserted/replacement content exceeds 100,000 characters.' );
	}
	kungfu_2026_mcp_validate_new_content( $new );
	if ( 'update_content_block' === $operation ) {
		$start = $matches[0]['start'];
		$end = $matches[0]['end'];
	} else {
		if ( $at_edge ) {
			$start = 'start' === $position ? 0 : strlen( $post->post_content );
		} elseif ( $has_target ) {
			$start = $matches[0][ 'before' === $position ? 'start' : 'end' ];
		} elseif ( 'before' === $position ) {
			$start = $blocks[$lo - 1]['end'];
		} else {
			$start = $hi < count( $blocks ) ? $blocks[$hi]['start'] : strlen( $post->post_content );
		}
		$end = $start;
		$new = "\n\n" . $new . "\n\n";
	}
	$result = substr( $post->post_content, 0, $start ) . $new . substr( $post->post_content, $end );
	return array( 'content' => $result, 'start_byte' => $start, 'end_byte' => $end, 'before' => substr( $post->post_content, $start, $end - $start ), 'after' => $new, 'unchanged_prefix_sha256' => hash( 'sha256', substr( $post->post_content, 0, $start ) ), 'unchanged_suffix_sha256' => hash( 'sha256', substr( $post->post_content, $end ) ) );
}

/**
 * InnoDB row lock makes the check and WP write atomic against concurrent writes,
 * including wp-admin. Always save a pre-edit WordPress revision; reject if that
 * cannot be done. No new public endpoint, credentials, or publication controls.
 */
function kungfu_2026_mcp_edit_content( $args, $operation ) {
	global $wpdb;
	if ( ! in_array( $operation, array( 'update_post', 'insert_content', 'update_content_block' ), true ) || ! is_array( $args ) ) {
		return kungfu_2026_mcp_tool_error( 'Invalid edit operation or arguments.' );
	}
	if ( 'update_content_block' === $operation && ! array_key_exists( 'content', $args ) ) {
		return kungfu_2026_mcp_tool_error( 'Provide content explicitly; use an empty string to delete a block.' );
	}
	$allowed = array( 'post_id', 'content_revision', 'block_id', 'section_heading', 'paragraph_text', 'dry_run', 'content' );
	if ( 'insert_content' === $operation ) {
		$allowed[] = 'paragraphs';
		$allowed[] = 'position';
	}
	if ( 'update_post' === $operation ) {
		$allowed = array( 'post_id', 'content_revision', 'dry_run', 'title', 'tags', 'excerpt', 'content' );
	}
	if ( array_diff( array_keys( $args ), $allowed ) || ! isset( $args['post_id'], $args['content_revision'] ) || ! is_int( $args['post_id'] ) || $args['post_id'] < 1 || ! is_string( $args['content_revision'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $args['content_revision'] ) ) {
		return kungfu_2026_mcp_tool_error( 'Valid integer post_id and content_revision are required; unknown arguments are rejected.' );
	}
	foreach ( array( 'block_id', 'section_heading', 'paragraph_text', 'content' ) as $key ) {
		if ( array_key_exists( $key, $args ) && ( ! is_string( $args[$key] ) || ( 'content' !== $key && '' === trim( $args[$key] ) ) ) ) {
			return kungfu_2026_mcp_tool_error( $key . ' must be a nonempty string.' );
		}
	}
	if ( array_key_exists( 'dry_run', $args ) && ! is_bool( $args['dry_run'] ) ) {
		return kungfu_2026_mcp_tool_error( 'dry_run must be a boolean.' );
	}
	if ( array_key_exists( 'position', $args ) && ! in_array( $args['position'], array( 'before', 'after', 'start', 'end' ), true ) ) {
		return kungfu_2026_mcp_tool_error( 'Invalid insertion position.' );
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
		if ( ! $row || ! kungfu_2026_mcp_supported_post_type( $row->post_type ) ) {
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
			$update = array_merge( array( 'ID' => $post_id, 'post_content' => $plan['content'] ), $plan['fields'] ?? array() );
			$result = wp_update_post( wp_slash( $update ), true );
			$saved = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $post_id ) );
			if ( is_wp_error( $result ) || ! $result || ! $saved || $saved->post_content !== $plan['content'] || $saved->post_status !== $post->post_status || $saved->post_title !== ( $update['post_title'] ?? $post->post_title ) || $saved->post_excerpt !== ( $update['post_excerpt'] ?? $post->post_excerpt ) ) {
				throw new RuntimeException( 'WordPress filters changed content, title, or status unexpectedly. Database edit rolled back.' );
			}
			if ( isset( $update['tags_input'] ) ) {
				$actual_tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
				$expected_tags = array_values( array_unique( $update['tags_input'] ) );
				sort( $actual_tags );
				sort( $expected_tags );
				if ( $actual_tags !== $expected_tags ) {
					throw new RuntimeException( 'WordPress did not save the requested tags. Database edit rolled back.' );
				}
			}
			// Save the pre-edit object after normal revision pruning, so a small
			// retention limit cannot delete the revision we return to the caller.
			$revision_id = _wp_put_post_revision( $post );
			if ( is_wp_error( $revision_id ) || ! $revision_id ) {
				throw new RuntimeException( 'Could not save the pre-edit revision; no edit was applied.' );
			}
			$backup = get_post( $revision_id );
			if ( ! $backup || $backup->post_content !== $post->post_content || $backup->post_title !== $post->post_title || $backup->post_excerpt !== $post->post_excerpt ) {
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
			'message' => $dry_run ? 'Preview only; no post or revision was written.' : 'Edit applied. Publication status preserved.',
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
