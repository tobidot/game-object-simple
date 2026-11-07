# Publishing & Discovery

- [Data Model: `tce_packages`](#data-model-tce_packages)
- [What Gets Uploaded](#what-gets-uploaded)
- [Three Ways to Publish](#three-ways-to-publish)
- [1. REST Upload](#1-rest-upload)
- [2. MCP Tool](#2-mcp-tool)
- [3. Manual, via Nova](#3-manual-via-nova)
- [Versioning on Upload](#versioning-on-upload)
- [Dependencies on Upload](#dependencies-on-upload)
- [Tags](#tags)
- [Public Discovery Endpoint](#public-discovery-endpoint)
- [Routes](#routes)
- [Do's and Don'ts](#dos-and-donts)

<a name="data-model-tce_packages"></a>
## Data Model: `tce_packages`

Every published version is one row of `tce_packages` (`App\Models\TcePackage`): `namespace`,
`name`, `kind` (`element` | `library` | `sdk`), `description`, `major`/`minor`/`patch`,
`width`/`height`, `extra` (free-form JSON; `extra.tags` holds the [tags](#tags)). Its code zip and icon are attachments (`relation: code` /
`relation: icon`), reusing the site's polymorphic attachment mechanism unchanged. Declared
dependencies are rows of `tce_package_dependencies`, pointing at the dependency's row, with
optional `required_major/minor/patch` constraints.

<a name="what-gets-uploaded"></a>
## What Gets Uploaded

| Kind                 | Zip contents                                   | Produced by                                  |
|----------------------|------------------------------------------------|----------------------------------------------|
| `element`, `library` | `dist/index.js`, `dist/index.global.js`, copied `public/` files | `npm run pack` (`release/<name>-<version>.zip`), or `npm run publish`, which zips `dist/` itself |
| `sdk`                | `package.json` + `src/**/*.ts`                 | `npm run publish`                            |

One top-level folder in the zip is flattened on extraction, so zipping `dist/` as a folder or
zipping its contents both work.

<a name="three-ways-to-publish"></a>
## Three Ways to Publish

| Path                             | Who          | Declares dependencies? | One request?         |
|----------------------------------|--------------|------------------------|----------------------|
| `POST /api/v1/tce-packages`      | script / CI  | yes                    | yes                  |
| MCP tool `upload_tce_package`    | an agent, `tce:import-v3-packages` | yes | yes |
| Nova admin                       | a human      | by hand                | no — row, then two attachments |

All three hand their input to one service (`TcePackageService::upload`), so the rules below hold
for every path.

<a name="1-rest-upload"></a>
## 1. REST Upload

```
POST /api/v1/tce-packages      (auth:sanctum, multipart/form-data)
```

| Field                        | Required | Notes                                                               |
|------------------------------|----------|---------------------------------------------------------------------|
| `namespace`                  | yes      | The publisher ([Naming](./naming#namespace)).                       |
| `name`                       | yes      | The package name; elements and libraries must match the tag-name pattern. |
| `zip`                        | yes, or `zip_upload_id` | See [What Gets Uploaded](#what-gets-uploaded). |
| `zip_upload_id`              | instead of `zip` | A zip sent in [chunks](#large-uploads), by its upload id.   |
| `version`                    | no       | `major.minor.patch`. Omitted: the previous patch + 1.                |
| `kind`                       | no       | `element` \| `library` \| `sdk`. Omitted: the previous kind, else `element`. |
| `description`                | no       | Omitted: carried over.                                              |
| `icon`                       | no       | SVG or PNG. Omitted: carried over.                                  |
| `width`, `height`            | no       | Default window size. Omitted: carried over.                         |
| `dependencies[i][namespace]`, `dependencies[i][name]` | no | Declared dependencies. Omitted: carried over; an empty list clears. |
| `tags[i]`                    | no       | [Tags](#tags). Omitted: carried over; an empty list clears.          |

<a name="large-uploads"></a>
### Large Uploads

A zip above the server's request limit (PHP `post_max_size`) goes up in chunks first:

```
POST /api/v1/tce-packages/chunks   (auth:sanctum, multipart/form-data)
upload_id      client-chosen, [A-Za-z0-9_-], at most 100 characters
chunk_index    0 … total_chunks - 1
total_chunks   at most 50
chunk          the bytes, at most 10 MB
```

Each chunk answers `{"status": "chunk_uploaded"}`; the one that completes the set assembles the
zip and answers `{"status": "completed", "size": …}`. `POST /api/v1/tce-packages` then names it
by `zip_upload_id` instead of sending `zip`; the session is deleted once the upload ran. Chunks
are stored through the same chunk assembly the Nova upload uses, scoped to the token's user — an
upload id never reaches another user's chunks.

The response is the published version as the registry lists it. A README generated by the
[generator](./generator) contains the exact `curl` call for its project.

Versions are removed by hand only. The newest version of a package always stays when older ones
are cleared, and so does a version that a dependency requires or a page embeds as `name@x.y.z`.
A dependency on a removed version moves to the newest remaining version of that package.

<a name="2-mcp-tool"></a>
## 2. MCP Tool

The site's MCP server exposes `upload_tce_package` with the same fields (`zip_base64`,
`icon_base64` + `icon_file_name` instead of files) and `list_tce_packages` to inspect the
registry. `php artisan tce:import-v3-packages` uploads through the same service, reading
everything from each package's [manifest](./manifest) in a staging folder inside the app
container.

<a name="3-manual-via-nova"></a>
## 3. Manual, via Nova

The Nova `TcePackage` resource creates the row by hand (namespace, name, version, kind,
description, size, dependencies), then the **Upload Attachment** action attaches the code zip
(`relation: code`) and the icon (`relation: icon`) in two steps. Nothing auto-increments the
version here.

<a name="versioning-on-upload"></a>
## Versioning on Upload

Version resolution is keyed on `(namespace, name)`:

- No `version` given, no prior upload for the pair → `0.0.1`.
- No `version` given, prior upload exists → same `major.minor`, `patch + 1`.
- `version` given → must be strictly greater than the pair's latest; the same or an older number
  is rejected. A bad release is fixed by a new patch, never by re-uploading its number.

<a name="dependencies-on-upload"></a>
## Dependencies on Upload

Dependencies are declared by identifier and resolved to the dependency's latest uploaded
version at upload time. A dependency that was never uploaded rejects the whole upload, naming
every missing identifier — so packages are published dependencies first, and a published version
can never be missing part of its dependency list. The declared list is what the embed pages and
the package manager use to include libraries before the packages that need them.

<a name="tags"></a>
## Tags

A package's tags are topic words the package manager offers as filters (`game`, `tool`,
`effect`, …): lower-case words joined by hyphens, de-duplicated and lower-cased on upload; any
other tag rejects the upload. Publishing tooling takes them from the manifest's
`informal.categories`, minus the categories every package carries (`custom-element`, `tce` and
the kind). They are stored in `extra.tags` and listed as `tags` in the registry.

<a name="public-discovery-endpoint"></a>
## Public Discovery Endpoint

```
GET /api/public/tce-packages   (no auth)
```

Every `element` and `library` version that has a code attachment, grouped by `namespace/name`
(two publishers using the same name never share a group). SDK packages are build-time only and
are not listed.

```json
{
  "version": 1,
  "packages": {
    "tobidot/tce-notepad": [
      {
        "namespace": "tobidot", "name": "tce-notepad", "kind": "element",
        "description": "A simple text editor for .txt and .md files.",
        "major": 0, "minor": 2, "patch": 0,
        "root": "https://…/storage/tce-packages/<uuid>",
        "content": "https://…/storage/tce-packages/<uuid>/index.js",
        "icon": "https://…/storage/media/<uuid>/icon.svg",
        "width": 480, "height": 360, "tags": ["tool", "files"],
        "extra": { "tags": ["tool", "files"] }, "created_at": "2026-10-17T18:45:35.000000Z",
        "dependencies": [{ "identifier": { "namespace": "tobidot", "name": "dll-tce-file-system" } }]
      }
    ]
  }
}
```

A version entry is `TcePackageResource`; the client-side schema is `registry_response_schema`
in `@tce-v3/common`, and every consumer reads the endpoint through
`fetch_json(url, registry_response_schema)`. The two change together.

<a name="routes"></a>
## Routes

| Route                                                  | Serves                                                        |
|--------------------------------------------------------|---------------------------------------------------------------|
| `GET /tce-elements/{namespace}/{name}`                 | the latest version's IIFE, prefixed with its content root ([Integration](./integration#the-served-script-self-bootstraps)) |
| `GET /tce-elements/{namespace}/{name}@{x}.{y}.{z}`     | exactly that version, immutable                               |
| `GET /tce-elements/{name}`                             | the same, in the site's default namespace                     |
| `GET /tce/{name}`                                      | fullscreen page of the latest element of that name, default namespace |
| `GET /tce-packages/{id}/view`                          | fullscreen page of one specific upload                        |
| `GET /`, `GET /v3`                                     | the desktop (with / without the site header)                  |
| `GET /api/public/tce-packages`                         | the registry response above                                   |
| `POST /api/v1/tce-packages`, `POST /api/v1/tce-packages/chunks` | publishing ([REST Upload](#1-rest-upload), [Large Uploads](#large-uploads)) |
| `GET /documentation/{path}`                            | this documentation as raw markdown, for `tce-documentation`   |

The embed route answers 404 for a version without an IIFE build and for SDK packages.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do publish dependencies first; the upload rejects an undeclared-yet-uploaded dependency.
- ✅ Do publish with the package's tooling (`npm run publish`, or `npm run pack` + REST), which
  reads the manifest; never zip by hand what the tooling zips.
- ✅ Do link externally to `/tce/{name}`, not an `{id}`, unless you want to pin a version.
- ✅ Do change `TcePackageResource` and `registry_response_schema` together.
- ❌ Don't re-upload a version number; bump the patch.
- ❌ Don't list or load SDK packages at runtime.
