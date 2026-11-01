# Kungfu WordPress MCP endpoint

The active `kungfu_2026` theme exposes a minimal stateless MCP endpoint:

```text
https://novel.369usa.com/wp-json/kungfu-mcp/v1/mcp
```

It exposes two tools:

- `add_post` accepts a title and up to 2,000 characters of plain text. It always creates a draft.
- `update_post` changes an existing post’s title, plain-text content, or complete tag list. It never changes the post’s publication status.

## Authentication

Create a WordPress Application Password under **Users → Profile → Application Passwords**. Configure the MCP client to send HTTP Basic authentication using the WordPress username and that Application Password. Do not use the account's normal login password.

The WordPress user must have permission to edit posts. Each created draft is assigned to the authenticated user.

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

Calling `add_post` writes a real draft, so only run that test when a test draft is wanted.

## Temporary ChatGPT test connection

For a short-lived ChatGPT test, deploy the theme and open **WordPress Admin → Tools → Kungfu MCP Test**. Copy the generated secret URL, then:

1. In ChatGPT on the web, add a custom MCP server/plugin.
2. Use the complete secret URL as the Server URL.
3. Choose **No authentication**.
4. Create and install the plugin, then select it with `@` in a new conversation.

Treat the complete URL as a password. It authenticates as the administrator who generated it, only creates drafts, and may appear in HTTP access logs. Use **Rotate secret URL** in WordPress immediately after testing or whenever the URL may have been exposed. A real multi-user integration should replace this temporary mechanism with OAuth 2.1.
