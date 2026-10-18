# TCE Project Generator

- [Three Entry Points, One Service](#three-entry-points-one-service)
- [1. Web UI](#1-web-ui)
- [2. Artisan CLI](#2-artisan-cli)
- [3. REST API](#3-rest-api)
- [Input](#input)
- [What You Get](#what-you-get)
- [Presets](#presets)
- [The SDK Is Vendored](#the-sdk-is-vendored)
- [Dependencies Come From the Registry](#dependencies-come-from-the-registry)
- [From Zip to Upload](#from-zip-to-upload)
- [Do's and Don'ts](#dos-and-donts)

The generator produces a complete, building, lint-clean v3 project — not a stub to wire up. A
generated project passes `npm run check` and `npm run build` without a single edit, and follows
every page of this specification, so it is also the reference layout for projects written by
hand.

<a name="three-entry-points-one-service"></a>
## Three Entry Points, One Service

The Web UI, the CLI and the REST API are thin wrappers around `App\Services\TceGeneratorService`
(`generateZip` / `generateProjectFiles`): whatever one can do, the others can.

<a name="1-web-ui"></a>
## 1. Web UI

`/tce-generator`: namespace, name, title, description, version, kind, preset, window size, icon,
and the dependencies to declare, picked from the packages published to this registry. The
submit button downloads the zip.

<a name="2-artisan-cli"></a>
## 2. Artisan CLI

```bash
php artisan tce:generate tce-my-element \
  --title="My Element" --template=canvas --width=640 --height=480 \
  --dependency=tobidot/dll-tce-file-system \
  --output=./tce-my-element.zip        # a .zip path, or a directory for unpacked files
```

`--namespace` defaults to the site's own; `--kind=library` generates a library.

<a name="3-rest-api"></a>
## 3. REST API

```
POST /api/public/tce-generator   (no auth)
```

Same fields as the Web UI, as JSON or form data. With `Accept: application/json` the response
carries the zip as `archive_base64` next to the resolved `config`; otherwise it is the zip
download.

<a name="input"></a>
## Input

| Field          | Rule                                                                    |
|----------------|-------------------------------------------------------------------------|
| `namespace`    | identifier pattern; defaults to the site's own (`config/tce.php`)       |
| `name`         | required; the tag-name pattern of [Naming](./naming#package-names) — never rewritten |
| `kind`         | `element` (default) or `library`                                        |
| `template`     | `standard` (default), `canvas`, `minimal`; elements only                |
| `version`      | `major.minor.patch`, default `0.1.0`                                    |
| `width`, `height` | 50–3840 / 50–2160, default 400×300; elements only                    |
| `dependencies[]` | `namespace/name` of packages published to this registry (elements or libraries) |
| `icon`         | SVG or PNG; without one a placeholder icon in the house palette is generated |

<a name="what-you-get"></a>
## What You Get

The [project layout](./assets-and-build#project-layout), fully filled in:

- `package.json` with the scripts `start`, `tsc`, `lint`, `check`, `build`, `pack`, the SDK as
  `file:vendor/…` dependencies, `zod`, and the build tools.
- `tsconfig.json`, `vite.config.ts` (ES + IIFE), `eslint.config.js` (the rules of
  [Coding Style: Tooling](./coding-style#tooling)).
- `tobidot-project.json` with namespace, name, kind, size and the declared dependencies.
- `index.html`, a dev harness for `npm start` that includes each declared dependency the way an
  embedding page would.
- `src/index.ts`, the entry script binding the package with its identifier, version, metadata
  and dependencies.
- The element or library source per [preset](#presets), with `src/applet.scss` for elements.
- `src/contracts/<library>.contract.ts` for every library dependency: the identifier and an
  empty contract map to fill from the library's source.
- `icon.svg`, `README.md` (commands, layout, the exact upload call), `.gitignore`,
  `scripts/pack.mjs`.
- `vendor/` with the SDK packages.

<a name="presets"></a>
## Presets

| Preset     | Produces                                                                                 |
|------------|------------------------------------------------------------------------------------------|
| `standard` | A split element — `Settings`, `References`, `Properties`, `Renderer`, `Logic` and the shallow delegate — with a small counter that reads an `init-count` attribute through a schema. Replace the demo, keep the shape. |
| `canvas`   | The same split with a `<canvas>` in References, a low-fps frame loop and a `ResizeObserver` in Logic, and a frame painter as Renderer. |
| `minimal`  | One small class: shadow root, styles, one listener on an `AbortController`. For an element that stays small. |
| (library)  | One class extending `TcePackage` with `functions`, exported per-function contracts, a validated example function and its exported type. |

<a name="the-sdk-is-vendored"></a>
## The SDK Is Vendored

The SDK packages (`config('tce.sdk_packages')`: `@tce-v3/common` for every project,
`@tce-v3/desktop-protocol` for elements) are read from the registry — their latest `kind: sdk`
upload — and copied into the project under `vendor/`, referenced from `package.json` as `file:`
dependencies. The project therefore builds offline, pinned to the SDK versions it was generated
against; upgrading is replacing the vendored copy. If an SDK package is not published, the
generator refuses with a message saying so rather than producing a project that cannot build.

<a name="dependencies-come-from-the-registry"></a>
## Dependencies Come From the Registry

The dependency picker lists the latest version of every element and library published to
`tce_packages`, with its namespace. A declared dependency ends up in three places that must
agree: the manifest, the entry script's `bind_self()` metadata, and (for libraries) a contract
file. Nothing is read from the legacy table and no dependency list is hardcoded.

<a name="from-zip-to-upload"></a>
## From Zip to Upload

```bash
unzip tce-my-element-0.1.0.zip && cd tce-my-element
npm install
npm start          # dev harness
npm run check      # tsc + eslint
npm run pack       # dist/ → release/tce-my-element-0.1.0.zip
```

The README's `curl` call uploads `release/*.zip` to `POST /api/v1/tce-packages` with the
project's namespace, name, kind, version, size, icon and dependencies
([Publishing & Discovery](./upload-and-registry#1-rest-upload)).

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do start every new package from the generator, even a one-off; its layout is the reference.
- ✅ Do pick `minimal` instead of deleting the standard preset's demo by hand.
- ✅ Do fill in the generated contract file before calling a library function.
- ✅ Do use the REST API when generation is part of a pipeline.
- ❌ Don't edit the `vendor/` SDK copies; replace them with a newer upload to upgrade.
- ❌ Don't zip `dist/` by hand — `npm run pack` produces what the upload expects.
- ❌ Don't give the generator a name that needs rewriting; it rejects invalid names on purpose.
