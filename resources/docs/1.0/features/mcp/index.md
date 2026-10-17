# MCP Server (AI Content Access)

- [What is it?](#what-is-it)
- [Authentication](#authentication)
- [Connecting a client](#connecting-a-client)
- [Tools](#tools)
- [Typical workflow](#typical-workflow)
- [Uploading large files](#uploading-large-files)
- [Behaviour and limits](#behaviour-and-limits)
- [Code locations](#code-locations)

<a name="what-is-it"></a>
## What is it?

The app exposes a [Model Context Protocol](https://modelcontextprotocol.io) (MCP) server, so AI agents
such as Claude Code can read and write site content directly: create and edit `Page`s (blog posts)
and upload, rename and attach media (`Attachment`s).

- **Endpoint:** `POST /mcp` (streamable HTTP transport)
- **Server:** `game-object.de Content` (`App\Mcp\Servers\ContentServer`)
- **Package:** [`laravel/mcp`](https://github.com/laravel/mcp), registered in `routes/ai.php`

<a name="authentication"></a>
## Authentication

The endpoint uses the same **Sanctum API tokens** as the `/api/v1` routes, sent as a Bearer token.
It is rate limited to 120 requests per minute.

1. In Nova, open your user and run the **Generate Api Token** action.
2. Copy the token (format `<id>|<secret>`).
3. Check it with `GET /api/v1/validate-token`.

A missing or invalid token returns `401` with `WWW-Authenticate: Bearer realm="mcp"`.

> **Warning:** a token grants full write access to all pages and media, including private ones. Treat it like a password.

<a name="connecting-a-client"></a>
## Connecting a client

**Claude Code:**

```bash
claude mcp add --transport http game-object http://localhost/mcp \
  --header "Authorization: Bearer <token>"
```

If Claude Code runs inside a Docker sandbox, `localhost` is the sandbox itself. Use
`http://host.docker.internal/mcp` instead. Check the connection with `claude mcp get game-object`
or `/mcp` inside a session.

**Any other MCP client:** configure an HTTP server with URL `<APP_URL>/mcp` and the header
`Authorization: Bearer <token>`.

**Quick check with curl:**

```bash
curl -X POST http://localhost/mcp \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -H 'Authorization: Bearer <token>' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

<a name="tools"></a>
## Tools

### Pages (blog posts)

| Tool | Purpose |
| --- | --- |
| `list_pages` | List pages, newest first. Filters: `search` (title/uri), `publish_state`, `page`, `per_page` (max 100). Without content. |
| `get_page` | One page by `id` or `uri`, including HTML `content`, attachments, `related_page_ids` and `project_ids`. |
| `create_page` | Create a page: `title`, `uri` (unique, `[a-z0-9-/]`), `content` (HTML), optional `publish_state`, `allow_comments`, `related_page_ids`, `project_ids`. |
| `update_page` | Change only the fields that are passed. `content` replaces the whole body; relation lists replace the full set. |

### Media (attachments)

| Tool | Purpose |
| --- | --- |
| `list_media` | List attachments, newest first. Filters: `search` (file name), `type`, `page_id`. |
| `get_media` | One attachment and the pages it is attached to. With `include_preview: true`, png/jpeg/gif/webp images up to 2 MB are returned as image content. |
| `upload_media` | Upload from `content_base64` (with `file_name`) **or** `source_url`. Optional `page_id` + `relation` attach it right away. Returns the public `url`. |
| `update_media` | Change `publish_state` and/or rename `file_name` (moves the file, so the url changes). |
| `attach_media` | Attach an existing attachment to a page with a `relation` (default `attachment`). |
| `detach_media` | Remove the link between an attachment and a page. The file is kept. |

There are **no delete tools** on purpose. Deleting pages and media is still done in Nova.

<a name="typical-workflow"></a>
## Typical workflow

Writing a post with a cover image:

1. `upload_media` with the image. Note the returned `attachment.id` and `attachment.url`.
2. `create_page` with `<img src="{url}">` in the HTML content. It is created as `private`.
3. `attach_media` with `relation: "thumbnail"` to use the image as the page thumbnail in excerpts.
4. Review the page in the browser (logged in), then `update_page` with `publish_state: "published"`.

<a name="uploading-large-files"></a>
## Uploading large files

Base64 through an MCP tool call is only practical for small files. For anything larger, use the
multipart endpoint with the same token:

```bash
curl -X POST http://localhost/api/v1/attachments \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer <token>' \
  -F file=@cover.png \
  -F page_id=13 \
  -F relation=thumbnail
```

| Field | Required | Notes |
| --- | --- | --- |
| `file` | yes | Limited by `upload_max_filesize` / `post_max_size` in `docker/app/php.ini`. |
| `prefix` | no | Storage folder, default `media`. |
| `page_id` | no | Attach to this page. |
| `relation` | no | Default `attachment`; `thumbnail` sets the page thumbnail. |

Responds with `201` and the same attachment fields as `upload_media`.

<a name="behaviour-and-limits"></a>
## Behaviour and limits

- **Same rules as Nova:** `<h1>` in page content becomes `<h2>`, because the page title is the only `<h1>`.
- **Private by default:** `create_page` creates pages as `private` unless `publish_state` is given.
  Authenticated requests see private pages and media too.
- **Deduplication:** uploads go through `AttachmentService::createFromUploadedSingleFile`. Uploading
  an identical file again returns the existing attachment.
- **File names:** uploaded names are slugged (`My Cover.PNG` becomes `my-cover.png`).
- **Size:** `upload_media` accepts up to 50 MB. `source_url` downloads time out after 60 seconds.
- **`source_url`:** the server fetches any http(s) URL it is given. This is acceptable because only
  token holders can call it.
- **Renaming media** changes its url. Pages that embed the old url must be updated with `update_page`.
- **Thumbnails:** a page uses its newest `thumbnail` attachment, then its newest image, then its newest attachment.

<a name="code-locations"></a>
## Code locations

| Path | Contents |
| --- | --- |
| `routes/ai.php` | Registers `/mcp` with `auth:sanctum` and throttling. |
| `app/Mcp/Servers/ContentServer.php` | Server name, instructions and tool list. |
| `app/Mcp/Tools/` | One class per tool. |
| `app/Mcp/Concerns/` | Shared presenters (`PresentsModels`) and page write logic (`WritesPages`). |
| `app/Http/Controllers/AttachmentController.php` | The multipart `/api/v1/attachments` endpoint. |
| `tests/Feature/McpContentServerTest.php` | Feature tests for all tools and both endpoints. |

To add a tool: create a class in `app/Mcp/Tools/` extending `Laravel\Mcp\Server\Tool`, give it
`#[Name]` and `#[Description]` attributes, and add it to `$tools` in `ContentServer`.
