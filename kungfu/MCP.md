# Kungfu WordPress MCP endpoint

The active `kungfu_2026` theme exposes a minimal stateless MCP endpoint:

```text
https://novel.369usa.com/wp-json/kungfu-mcp/v1/mcp
```

It exposes three tools:

- `add_post` accepts a title and up to 100,000 characters of plain text, WordPress-safe HTML, or serialized Gutenberg blocks. It immediately publishes the new post.
- `get_post_for_editing` returns a post’s title, status, tags, content fingerprint, and numbered raw content. Nonblank lines are addressable as `S2.L3` (section 2, line 3) or by their global line number.
- `update_post` changes an existing post’s title, WordPress-safe HTML or serialized Gutenberg block content, complete tag list, or an exact numbered line range. Gutenberg delimiters and safe formatting are preserved, while unsafe markup is removed. It never changes the post’s publication status.

## Gutenberg content

Both writing tools support canonical serialized Gutenberg markup. Prefer this
form whenever editable WordPress formatting matters:

```html
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Section 1: Overview</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This paragraph contains <strong>bold text</strong> and <em>italics</em>.</p>
<!-- /wp:paragraph -->
```

The server uses WordPress’s block parser and safe-post HTML filter, which parse
and reserialize blocks while preserving their `<!-- wp:* -->` delimiters and
safe inner HTML. Unsafe elements and attributes are removed. If submitted content
contains Gutenberg delimiters but they cannot be preserved, the tool returns an
error instead of silently flattening the post.

If an existing post already uses Gutenberg blocks, `update_post` rejects a
plain-HTML or plain-text full replacement. The replacement must remain serialized
Gutenberg content. Targeted edits on such a post must select complete block
boundaries and either replace them with complete serialized blocks or delete the
selected blocks. These guards prevent an edit from silently destroying existing
block formatting.

## Editing a section or line

Use this read-before-write workflow whenever a user identifies part of a post instead of replacing all content:

1. Call `get_post_for_editing` with the post ID.
2. Show or interpret the numbered content. A section is a consecutive group of nonblank raw-content lines separated by blank lines.
3. Call `update_post` with either a global range (`line_start`, optional `line_end`) or a section-local range (`section`, `section_line_start`, optional `section_line_end`).
4. Include `replacement` and the exact `content_revision` returned by the read call.
5. Call `get_post_for_editing` again before another targeted edit because inserting or deleting text can change subsequent line numbers.

For example, a user can say “replace section 2, lines 3–4” or “replace global line 18.” An empty `replacement` deletes the selected range. The server rejects the edit if the post changed after it was read, preventing stale line references from modifying the wrong text.

Full-content replacement remains available through `content` and does not use line references. Do not send `content` and a targeted line replacement in the same call. For Gutenberg posts, full-content replacement is safest. A targeted line replacement must include the complete opening block comment, matching HTML, and closing block comment so it cannot leave a broken block behind.

Section titles may be supplied as HTML headings, for example:

```html
<h2>Section 1: Overview</h2>
<h2>Section 2: History and Management</h2>
<h2>Section 3: Name</h2>
```

Both full-content updates and targeted `replacement` updates preserve these headings through WordPress’s safe-HTML filtering. Serialized Gutenberg headings are preferred when the content should remain directly editable as blocks.

Example read call:

```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "get_post_for_editing",
    "arguments": { "post_id": 673 }
  }
}
```

Example section-local update using the fingerprint returned by that read call:

```json
{
  "jsonrpc": "2.0",
  "id": 4,
  "method": "tools/call",
  "params": {
    "name": "update_post",
    "arguments": {
      "post_id": 673,
      "section": 2,
      "section_line_start": 3,
      "section_line_end": 4,
      "replacement": "Replacement line one\nReplacement line two",
      "content_revision": "paste-the-64-character-fingerprint-from-the-read-call"
    }
  }
}
```

## Authentication

Create a WordPress Application Password under **Users → Profile → Application Passwords**. Configure the MCP client to send HTTP Basic authentication using the WordPress username and that Application Password. Do not use the account's normal login password.

The WordPress user must have permission to publish posts. Each published post is assigned to the authenticated user.

## Direct test

Set temporary shell variables (do not save the password in Git), then initialize and list the tools:

```sh
export KUNGFU_MCP_USER='your-wordpress-username'
export KUNGFU_MCP_APP_PASSWORD='xxxx xxxx xxxx xxxx xxxx xxxx'

curl --user "$KUNGFU_MCP_USER:$KUNGFU_MCP_APP_PASSWORD" \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  --data '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"curl-test","version":"1.0"}}}' \
  https://novel.369usa.com/wp-json/kungfu-mcp/v1/mcp

curl --user "$KUNGFU_MCP_USER:$KUNGFU_MCP_APP_PASSWORD" \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  --data '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}' \
  https://novel.369usa.com/wp-json/kungfu-mcp/v1/mcp
```

Calling `add_post` creates a real, publicly visible post, so only run that test when publication is intended.

## Temporary ChatGPT test connection

For a short-lived ChatGPT test, deploy the theme and open **WordPress Admin → Tools → Kungfu MCP Test**. Copy the generated secret URL, then:

1. In ChatGPT on the web, add a custom MCP server/plugin.
2. Use the complete secret URL as the Server URL.
3. Choose **No authentication**.
4. Create and install the plugin, then select it with `@` in a new conversation.

Treat the complete URL as a password. It authenticates as the administrator who generated it, can publish posts, and may appear in HTTP access logs. Use **Rotate secret URL** in WordPress immediately after testing or whenever the URL may have been exposed. A real multi-user integration should replace this temporary mechanism with OAuth 2.1.
