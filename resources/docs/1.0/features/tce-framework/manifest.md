# The `tobidot-project.json` Manifest

- [Purpose](#purpose)
- [Fields](#fields)
- [Example](#example)
- [SDK Packages](#sdk-packages)
- [Do's and Don'ts](#dos-and-donts)

<a name="purpose"></a>
## Purpose

Every project ships a `tobidot-project.json` at its root. It is the metadata publishing tooling
reads — the v3 workspace's `publish.py`, the site's `tce:import-v3-packages` command — to
upload a package without being told anything on the command line: who publishes it, what it is
called, what kind it is, its description, window size and dependencies. The browser runtime never
reads it; at runtime the same facts come from the entry script's `bind_self()` call and from the
registry response ([Publishing & Discovery](./upload-and-registry)). The manifest, the entry
script and the registry must agree, and the manifest is what tooling trusts.

<a name="fields"></a>
## Fields

| Field                      | Type              | Meaning                                                   |
|----------------------------|-------------------|-----------------------------------------------------------|
| `$schema`                  | string            | `https://game-object.com/tobidot-project-schema.json`, for editor tooling. |
| `namespace`                | string            | Publisher namespace ([Naming](./naming#namespace)). Required. |
| `name`                     | string            | Package name = tag name ([Naming](./naming#package-names)). Required. |
| `title`                    | string            | Human-readable title.                                     |
| `informal.description`     | string            | Shown in the package manager and the registry.            |
| `informal.categories`      | string[]          | Tags. Must contain exactly one kind: `element`, `library` or `sdk`; the generator also adds `custom-element` and `tce`. |
| `development.status`       | string            | `draft` → `beta` → `stable`, maintained by hand.          |
| `execution.type`           | string            | `website` for runtime packages, `npm` for SDK packages.    |
| `execution.resources`      | string            | What gets published: `dist` (runtime packages) or `src` (SDK). |
| `execution.index`          | string            | Entry file inside `resources`: `index.js` / `index.ts`.    |
| `resolution.width/height`  | number            | Default window size in pixels; `0`/`0` for packages without a window. |
| `history`                  | array             | Reserved for a future changelog; always empty.            |
| `code.language`            | string            | `typescript`.                                             |
| `dependencies`             | array (optional)  | Runtime dependencies, each `{ identifier: { namespace, name } }`, optionally with a `version` constraint ([Package Model](./package-model#version-matching-is-caret-range)). |
| `assets.external`          | string[] (optional) | URLs of large assets not shipped in the upload ([Assets & Build](./assets-and-build#external-assets-need-not-ship-in-the-upload)). |

<a name="example"></a>
## Example

```json
{
    "$schema": "https://game-object.com/tobidot-project-schema.json",
    "namespace": "tobidot",
    "name": "tce-notepad",
    "title": "Tce Notepad",
    "informal": {
        "description": "A simple text editor for .txt and .md files.",
        "categories": ["custom-element", "tce", "element"]
    },
    "development": { "status": "draft" },
    "execution": { "type": "website", "resources": "dist", "index": "index.js" },
    "resolution": { "width": 480, "height": 360 },
    "history": [],
    "code": { "language": "typescript" },
    "dependencies": [
        { "identifier": { "namespace": "tobidot", "name": "dll-tce-file-system" } }
    ]
}
```

<a name="sdk-packages"></a>
## SDK Packages

An SDK package's manifest has `sdk` among its categories and
`"execution": { "type": "npm", "resources": "src", "index": "index.ts" }`. Publishing tooling
then ships `package.json` plus `src/` instead of `dist/`, which is what the
[generator](./generator#the-sdk-is-vendored) vendors into new projects.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do keep `namespace`, `name`, `resolution` and `dependencies` identical to what the entry
  script passes to `bind_self()` — tooling publishes the manifest's values.
- ✅ Do add a dependency here the moment you add it to the entry script.
- ✅ Do keep `development.status` current by hand; nothing automates it.
- ❌ Don't list more than one kind in `informal.categories`.
- ❌ Don't read or write `history` from code — it is reserved.
- ❌ Don't ship a large binary in the upload when `assets.external` can reference it by URL.
