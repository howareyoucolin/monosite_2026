# Kungfu WordPress MCP endpoint — 0.6.1

## POST confirmation policy (0.6.1)

The server's `initialize` instructions and every write tool description require
the agent to briefly summarize the action, target post/page, proposed content or
metadata changes, and publication effect, then ask **"Reply POST to apply these
changes."** The agent must wait for a separate user reply containing only `POST`
before calling `add_post`, `update_post`, or either block tool with `dry_run:false`.
Reads and `dry_run:true` previews do not require confirmation. Approval covers
only the exact summarized edit or explicitly listed batch; changed proposals and
revision-conflict retries require a new summary and confirmation.

This is an agent instruction policy, not server-enforced proof of user consent.
The API cannot verify conversation replies and its write schemas are unchanged.
Deploy the updated theme through the existing process and refresh the MCP
connection to load the new instructions and tool descriptions (version `0.6.1`).

The existing active `kungfu_2026` theme owns the server. No separate plugin,
endpoint, credentials, or deployment service is introduced.

- Registration and JSON-RPC dispatch: `wp-content/themes/kungfu_2026/inc/mcp-server.php`
- Lossless block planning and safe writes: `wp-content/themes/kungfu_2026/inc/mcp-block-editing.php`
- Existing endpoint: `https://novel.369usa.com/wp-json/kungfu-mcp/v1/mcp`

## Inspection findings

Before this change, this repository already registered **three** tools:
`add_post`, `get_post_for_editing`, and `update_post`. The read tool returned
numbered raw lines and a content fingerprint. `update_post` accepted safe HTML,
Gutenberg serialization, full replacement, and revision-checked line ranges.
Those numbered "sections" were blank-line groups, not named H2 sections.
The connected ChatGPT app currently advertises only `add_post` and the older
plain-text `update_post`. Its metadata differs from this source. This establishes
a source/connection mismatch; deployment state and cached action definitions
must both be checked before attributing it to either one.

No dedicated insertion tool, block-target tool, dry-run preview, atomic revision
check/write, or explicit recovery revision existed. The old line guard checked
for opening/closing comments without proving balanced complete block boundaries,
and re-filtered the whole result, potentially changing unrelated bytes.

The available installed skill catalog and local custom skills contained no
Kungfu WordPress editing skill implementing these capabilities. This is a
separately hosted PHP MCP server; its `tools/list` registry and `initialize`
instructions own discovery. There is no MCP plugin manifest in this repository
to update. The update-plugin workflow was used for the existing server source.

**Existing behavior discrepancy:** the installed app describes `add_post` as
creating a draft, but the repository implementation immediately publishes.
This release retains that existing implementation and describes it accurately.
Do not use `add_post` for safe draft testing. Tests create drafts through local
WordPress APIs.

## Exact tool names after deployment and refresh

| Tool | Behavior |
| --- | --- |
| `add_post` | Existing immediate-publication behavior; accepts safe HTML/Gutenberg. |
| `get_post_for_editing` | Title, status, exact raw content, revision token, top-level block IDs/attributes/raw markup/line spans, named H2 sections, plus legacy numbered raw text. |
| `insert_content` | Insert native blocks or escaped plain paragraphs after a unique block/paragraph, or at the end of a named H2 section. |
| `update_content_block` | Replace exactly one selected complete block with a block of the same type. |
| `update_post` | Title and tags only. **Content and legacy line replacements are rejected**, including calls from cached schemas. |

The server advertises version `0.6.1`. Existing `update_post` content integrations
must migrate to the two block tools. There is no full-content replacement path
through `update_post`, which prevents a partial request from accidentally
replacing the post.

## Safe editing workflow

1. Call `get_post_for_editing` with an integer `post_id`.
2. Verify the returned title and status. Inspect `blocks` and `h2_sections`.
   Use the exact `content_revision`; it hashes exact bytes, post ID, title,
   status, and modified time. Block IDs/line references are scoped to this
   snapshot and must be reread after any successful edit or conflict.
3. Select one `block_id`, or the exact complete visible `paragraph_text`.
   Optional `section_heading` constrains the target to the exact visible H2.
   Duplicated paragraphs/headings and targets outside that section fail.
4. Call `insert_content` or `update_content_block` with `dry_run: true` (the
   default). Review `change.before`, `change.after`, and the complete preview
   `change.content`. No post/revision is written by the preview.
5. Briefly summarize the action, target, proposed changes and publication effect.
   Ask **"Reply POST to apply these changes."** Wait for the user's separate
   `POST` reply, then apply the identical request with `dry_run: false`.
   Revision checks run again; preview does not reserve a revision or target.
6. Keep the returned `restore_revision_id` / `restore_url`. Reread before another edit.

`insert_content` accepts **either** `paragraphs` (a list of plain strings, escaped
and wrapped in native paragraph blocks) **or** `content` (one or more complete
serialized blocks). It inserts immediately after the selected block. With only
`section_heading`, insertion occurs before the next H1/H2, or at post end.

`update_content_block` requires `content` containing exactly one complete block
of the target's type. Copy its original attributes and inline HTML, changing only
the desired text. This supports sentence/line changes inside a paragraph while
preserving its `<strong>`, `<em>`, classes, and attributes. The server never chooses
text by fuzzy match or edits a raw line that might cut through a block boundary.

Only top-level native blocks are independent targets. Nested groups/columns are
preserved as complete parent blocks; edit a child by supplying the complete
replacement parent. Freeform/classic HTML and malformed serialization can be
read, but partial writes are rejected until converted to native Gutenberg blocks.
New markup must be balanced and unchanged by WordPress safe-content filtering;
otherwise it is rejected, with no silent sanitization. This is conservative and
may require canonical markup for otherwise renderable HTML.

Example native replacement:

```html
<!-- wp:paragraph {"className":"spectators"} -->
<p class="spectators">Spectators were <strong>cheering</strong>, <em>arguing</em>, and placing bigger bets.</p>
<!-- /wp:paragraph -->
```

## Post 673: requested insertion preview

Post 673 was **not present in the local database**. The exact supplied three
paragraphs were tested on local draft fixtures with an H2 section named
`Section 3: The Die-Hard Spectators` and a representative spectator paragraph.
This verifies behavior, not the actual contents of the live post.
No request to read, edit, or publish the live post was made during testing.

After deployment/refresh, first read **673** and verify the title
**Evenly Matched — Part 1**. Copy the `block_id` of the actual paragraph describing
cheering, arguing, and bets. Do not guess its exact wording or block ID.
Then preview:

```json
{
  "post_id": 673,
  "content_revision": "COPY_THE_REVISION_FROM_GET_POST_FOR_EDITING",
  "section_heading": "Section 3: The Die-Hard Spectators",
  "block_id": "COPY_THE_ACTUAL_SPECTATOR_PARAGRAPH_BLOCK_ID",
  "paragraphs": [
    "Several spectators pulled out their phones and began recording the fight from different angles.",
    "One man raised his phone high and shouted with wild enthusiasm.",
    "\"Holy cow! This is gonna be epic! I'm recording the whole thing!\""
  ],
  "dry_run": true
}
```

## Write protection and recovery

Applied edits require InnoDB for the WordPress posts table. The server starts a
transaction, reads the current row with `SELECT ... FOR UPDATE`, authorizes the
post, checks the revision, and calculates one exact byte splice. It sends only
the content field to `wp_update_post` and checks the saved content, title, and
status against the intended values before commit. Unexpected save-filter changes
cause a database rollback. Unrelated existing content is never reserialized or
sanitized. Preview hashes identify the unchanged prefix and suffix.

WordPress revisions must be enabled. A pre-edit snapshot is explicitly saved
inside the transaction and verified before commit. This snapshot is created
after ordinary revision pruning so even a retention limit of one cannot remove
the recovery revision returned for this edit. Subsequent retention/cleanup rules
still apply. Restore through the returned native WordPress revision page, or
`wp post revision restore REVISION_ID` on the target environment.
Normal WordPress save hooks run; database rollback cannot undo third-party hook
side effects outside the database.

## Local tests

Run from the repository root with Docker running:

```sh
docker compose run --rm wpcli eval-file wp-content/themes/kungfu_2026/tests/mcp-block-editing.php
python3 wp-content/themes/kungfu_2026/tests/mcp-concurrency.py
```

The integration suite uses real WordPress REST dispatch and local draft fixtures,
then removes its fixtures/revisions. It refuses non-localhost environments.
It covers exact insertion placement, byte-identical prefix/suffix, attributes,
bold/italic, nested blocks, H2/H1 boundaries, CRLF, previews without writes,
stale revisions, ambiguous/missing/out-of-section targets, authorization,
malformed/unsafe/multiple/wrong-type replacements, legacy replacement rejection,
filter rollback, disabled revisions, revision retention, and native restore.
The concurrency test runs two independent PHP processes: the first holds a row
lock and applies once; the second waits, sees the new revision, and fails.

Validated locally: **49 integration checks plus the two-process concurrency test**
on WordPress 7.1 / PHP 8.2 / MariaDB InnoDB. Editor UI validation and the live
post's actual content remain unverified.

## Deploy and refresh the existing ChatGPT connection

The changes are local until the existing theme deployment is run. Review the
normal theme deployment diff first; include **both** PHP files:

```sh
npm run deploy:dry -- kungfu_2026
npm run deploy -- kungfu_2026
```

Use the existing server URL/authentication. The regular route uses a WordPress
Application Password with HTTP Basic auth; the temporary secret-path route is
available under **WordPress Admin → Tools → Kungfu MCP Test** for the existing
no-auth ChatGPT connection. Do not rotate its secret merely to refresh tools.
Treat the full secret URL as a password and never put it in docs or test output.

1. In the existing ChatGPT app/plugin detail page, **Refresh** the connection to
   reload tools, schemas, descriptions, and server instructions. For a managed
   workspace, the owner/admin may need to refresh its tool actions.
2. Enable the newly discovered read/edit actions if they are deselected.
3. Start a new conversation with the existing Kungfu WordPress app selected.
4. Verify all five exact names above are available, then read/preview only.
5. If the list is still old, inspect the deployed server's `initialize` and
   `tools/list`: version must be `0.6.1` and the list must contain five names.
   Check that the connected URL serves this theme/version (and clear PHP opcode
   cache if deployment requires it). Refresh again once that is correct.
6. If this client has no refresh operation, reconnect the same existing server
   URL with the same authentication. No duplicate WordPress plugin is needed.

OpenAI documents refresh as the way to pull updated tool definitions and server
instructions: [Add custom MCP server](https://developers.openai.com/api/docs/guides/custom-mcp-server).
