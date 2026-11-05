# Kungfu WordPress MCP endpoint — 0.8.0

The existing Kungfu 2026 theme owns this separately hosted MCP server. Its URL,
authentication, and existing tool names stay the same. This release adds title
lookup and expands content editing for WordPress posts, pages, and the new `character` post type.

- Registry and dispatch: `wp-content/themes/kungfu_2026/inc/mcp-server.php`
- Revision-checked planning/writes: `wp-content/themes/kungfu_2026/inc/mcp-block-editing.php`
- Endpoint: `https://novel.369usa.com/wp-json/kungfu-mcp/v1/mcp`

## Tools

| Tool | Behavior |
| --- | --- |
| `add_post` | Create and immediately publish an ordinary chapter post. Existing behavior; never use for draft testing. |
| `search_posts` | Find editable posts, pages, and characters by partial or misspelled title; return ranked IDs, titles, types, statuses, and URLs. Read-only. |
| `get_post_for_editing` | Read exact body, title, status, post type, excerpt, tags, revision token, block IDs, H2 sections, all heading sections, and formatting guidance. |
| `update_post` | Replace the complete body and/or update title, excerpt, and tags where supported. Empty `content` clears the body; omitted `content` preserves it. |
| `insert_content` | Insert before/after an exact block or paragraph, at a named section's start/end, or at the whole body's start/end. |
| `update_content_block` | Replace one selected block with any safe text/HTML or any number/type of native blocks. Empty `content` explicitly deletes that block. |

All three update/insertion tools require `post_id` and the exact
`content_revision` from a fresh read. They default to `dry_run:true`, including
metadata-only `update_post` requests. Cached clients must refresh: the old
metadata-only update schema and immediate metadata-save behavior have changed.
Line-range arguments remain unsupported; full replacement or a targeted block
edit covers arbitrary body changes without line-boundary ambiguity.

For full replacement, send **all body content to retain**. For a narrow edit,
prefer a targeted tool. Safe HTML, plain text, mixed classic HTML/native blocks,
and balanced Gutenberg blocks are supported. Formatting preferences do not
reject a write. WordPress permissions, safe-content validation, and revision
checks still apply. Unsafe new markup is rejected rather than silently changed.
Existing unrelated bytes are never normalized or reserialized by targeted edits.

## Formatting guidance (advisory)

Eight live chapters were read on November 5, 2026, including raw Gutenberg
markup through the connected read tool. No live post was edited.

| Chapter | Observed section headings |
| --- | --- |
| [The Unkillable Man](https://novel.369usa.com/the-unkillable-man/) | H2 |
| [Evenly Matched — Part 2](https://novel.369usa.com/evenly-matched-part-2/) | H2 |
| [Long Tailing](https://novel.369usa.com/long-tailing/) | H2 |
| [Raymond vs. Pastor Simon](https://novel.369usa.com/raymond-vs-pastor-simon/) | H3 |
| [Raymond vs. Gangsters](https://novel.369usa.com/raymond-vs-gangsters/) | H3 |
| [The Peeing Man](https://novel.369usa.com/the-peeing-man/) | H3 |
| [The Sakura Spa](https://novel.369usa.com/the-sakura-spa/) | H3 |
| [Skyfall Kick](https://novel.369usa.com/skyfall-kick/) | H3 |

Server initialization, write tool descriptions, and read snapshots suggest:

- Put the chapter title in the title field; avoid repeating it as an H1 in the body.
- Match existing heading levels. For new sections, default to H3 headings named `Section N: Descriptive Title`, numbered within each
  chapter. Most reviewed chapters use H3; some recent chapters use H2. Preserve
  either existing format unless asked to standardize it.
- Use separate native paragraph blocks for narration, dialogue, and short
  action/sound beats. Use `<em>` for internal thoughts; retain existing emphasis.
- Preserve names, voice, punctuation, and unrelated prose. Do not invent story
  details or rewrite text merely to change its formatting.
- Prefer balanced Gutenberg comments with blank lines between blocks.
- Character wiki entries use ordinary readable WordPress content: headings,
  paragraphs, short lists, images, and galleries. JSON is the MCP request format,
  not the recommended page body. Use the character name as the post title.
- Suggested character H3 sections: Overview, Appearance, Skills & abilities,
  Weapons & equipment, Personality & background, and References. Use no chapter
  section numbering. Match existing heading levels when editing an entry.
- Describe known aliases, role, and affiliations in Overview; physical details
  and clothing in Appearance, alongside reference images and useful captions.
  For skills, describe each technique and its known strengths, limitations, or
  weaknesses; bold skill names when useful. Put equipment, personality, history,
  and relationships in their relevant sections.
- Link to source chapters for factual details and include visual reference
  sources or image credits when available. Distinguish confirmed facts from
  proposed ideas or visual inspiration. Do not invent missing facts, abilities,
  images, or links; omit unsupported details or mark them unknown when useful.
- The outline is flexible: rename, add, combine, or omit sections to suit the
  character and user request. Preserve unrelated content during edits.

These are suggestions. Explicit user formatting choices take precedence; H2,
other headings, plain text, and safe HTML remain valid.

## Find an ID by title

Use the read-only `search_posts` tool before editing when the ID is unknown:

```json
{
  "query": "evenly macthed part1",
  "post_type": "post",
  "limit": 10
}
```

`post_type` defaults to `all` (posts, pages, and characters); `limit` defaults to
10 and accepts 1–20. The response contains `matches`, `total_matches`, and
`has_more`. Each match includes `post_id`, `title`, `post_type`, `status`, `url`,
`score` (0–1), and `match_type` (exact, contains, or fuzzy). Exact normalized
titles rank first, followed by title fragments and approximate word matches.
Scores describe title similarity, not a probability that a post is the target.

Matching folds case, accents, punctuation, and letter/number boundaries; it
handles small misspellings, missing letters, adjacent letter swaps, and reordered
words. Unicode edit distance counts characters rather than UTF-8 bytes. Numeric
query words must match exactly, so a request for Part 1 does not suggest Part 2
as a typo. This is title matching, not semantic search or body-content search.

Published, scheduled, draft, pending, and private entries are eligible, but only
when the connected user can edit the specific entry. Trash, auto-drafts,
revisions, and attachments are excluded. Titles are scanned in batches, so a
misspelling is not prematurely excluded by a literal SQL substring filter.
Search returns suggestions without modifying anything or requiring POST
confirmation. Duplicate/similar titles return separate IDs; verify the chosen
entry with `get_post_for_editing`, and clarify ambiguity before proposing edits.

## Read, preview, confirm, apply

1. If the ID is unknown, use `search_posts` with a title fragment or approximate
   title. Verify the selected target with `get_post_for_editing`, including its
   full title, type, and status. Clarify ambiguous matches before editing.
2. Copy its `content_revision`. Block IDs belong only to this snapshot.
3. Preview the intended update with `dry_run:true` (the default). Review the
   returned `change`, including the complete proposed body and metadata fields.
4. Summarize the target, proposed changes, and publication effect. Ask
   **"Reply POST to apply these changes."** Wait for a separate user reply
   containing only `POST`, then send the identical request with `dry_run:false`.
5. Retain the returned recovery revision and reread after saving or a conflict.

The existing POST rule is retained at the user's request. It is an agent
instruction; the API cannot verify conversation replies. Reads and previews
need no confirmation. Confirmation covers only the summarized edit or listed
batch; changed proposals/conflict retries require renewed confirmation.
Existing post publication status is preserved; `add_post` immediately publishes.

### Full update preview

```json
{
  "post_id": 673,
  "content_revision": "COPY_FROM_A_FRESH_READ",
  "content": "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">Section 1: A New Beginning</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>The complete replacement chapter goes here.</p>\n<!-- /wp:paragraph -->",
  "dry_run": true
}
```

This is a schema example, not a proposed change to post 673.

### Insertion positions

`insert_content` accepts either `paragraphs` (escaped plain strings wrapped in
native paragraph blocks) or `content` (text, safe HTML, or serialized blocks).

- `position:"start"` or `"end"`: insert at the whole body's beginning/end,
  including empty/classic bodies. Do not supply a target or section with these.
- `block_id` or unique exact `paragraph_text`: insert immediately after the
  selected block by default; `position:"before"` inserts before it.
- `section_heading` alone: append before the next heading of equal/higher rank,
  or at body end. `position:"before"` inserts at the section's start after its
  heading. All native heading levels are supported, including legacy H3.
- `section_heading` with a block/paragraph target constrains it to that section.
  Duplicate headings/paragraphs fail rather than guess. Select by block ID or
  use full replacement when duplicates make a named target ambiguous.

`heading_sections` describes each native heading's level and block range.
`h2_sections` is retained for clients using the old H2-only field. Only top-level
native blocks have independent IDs; replace a complete parent block or the body
to edit nested blocks. Classic HTML/plain text can be fully replaced or extended
at either body edge. Native blocks within mixed classic/native bodies are also
available as targets. Malformed old serialization may prevent block targeting;
full replacement or edge insertion remains available.

## Integrity and recovery

Applied updates lock the post row with `SELECT ... FOR UPDATE`, recheck the
revision, run WordPress's normal save hooks, and verify the saved values before
committing. The fingerprint includes body, title, status, excerpt, tags, and
modified time. A stale revision is rejected without writing.

Writes require transactional InnoDB posts storage and enabled WordPress
revisions. Every applied edit saves and verifies an explicit pre-edit revision,
including with a revision-retention limit of one. The returned
`restore_revision_id` and `restore_url` recover body, title, and excerpt through
native WordPress revisions. Tags are not part of WordPress revisions;
`update_post` previews/results include `change.metadata_before.tags` for a
manual tag restore. Later revision retention/cleanup still applies.

Unexpected save-filter changes trigger database rollback. Database rollback
cannot undo non-database effects of third-party save hooks.

## Local verification

```sh
docker compose run --rm wpcli eval-file wp-content/themes/kungfu_2026/tests/mcp-block-editing.php
docker compose run --rm wpcli eval-file wp-content/themes/kungfu_2026/tests/mcp-search.php
python3 wp-content/themes/kungfu_2026/tests/mcp-concurrency.py
```

The integration suite refuses non-localhost WordPress environments, uses local
draft fixtures, and removes fixtures/revisions afterward. It exercises full-body
and targeted edits, metadata, legacy/mixed HTML, H3 sections, insertion positions,
block type/count changes, explicit deletion, characters/pages, POST discovery,
previews without writes, permission checks, stale revisions, unsafe/malformed
markup, filter rollback, revision retention, and native restore. The title-search suite checks typo ranking, case/accents/punctuation, numbered
parts, result limits, type filters, duplicate titles, no-match results, Unicode,
permissions, and read-only behavior. The concurrency
suite uses two independent PHP processes to verify locking and stale rejection.

## Deploy the existing server

Changes remain local until the theme is deployed through its existing process:

```sh
npm run deploy:dry -- kungfu_2026
npm run deploy -- kungfu_2026
```

Include the entire updated theme, including character registration when using
character tools. Keep the same endpoint/authentication and temporary secret-path
configuration, if used; do not rotate or expose its secret to refresh discovery.

Refresh the existing MCP connection/tool definitions after deployment. Verify
`initialize.serverInfo.version` is `0.8.0`, all six tools are listed,
`update_post` advertises `content`, `content_revision`, and `dry_run`, and
`insert_content` advertises `position`. A fresh conversation may be needed to
pick up refreshed tool definitions. The deployed server and connected client
must both be checked; local source changes alone do not update the live tools.
