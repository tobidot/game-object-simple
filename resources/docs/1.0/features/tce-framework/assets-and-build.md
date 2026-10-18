# Assets & Build

- [Project Layout](#project-layout)
- [Static Assets Are Copied, Never Inlined](#static-assets-are-copied-never-inlined)
- [External Assets Need Not Ship in the Upload](#external-assets-need-not-ship-in-the-upload)
- [`init()` Stays Cheap; Heavy Content Is Explicit](#init-stays-cheap-heavy-content-is-explicit)
- [Code-Splitting for Multi-Scene Projects](#code-splitting-for-multi-scene-projects)
- [Dual Build Output: ES and IIFE](#dual-build-output-es-and-iife)
- [The SDK Is a Build-Time Dependency](#the-sdk-is-a-build-time-dependency)
- [Compression & Cache-Busting](#compression--cache-busting)
- [Publishing Large Projects](#publishing-large-projects)
- [Do's and Don'ts](#dos-and-donts)

A TCE is not always a small widget — it may be a game shipping megabytes of textures, audio and
level data. This page specifies how a project of any size is laid out, built, packaged and
loaded without forcing every visitor to download everything up front.

<a name="project-layout"></a>
## Project Layout

Every project — generated or not — has this shape:

```
package.json           scripts: start, tsc, lint, check, build, pack
tsconfig.json          strict, ES2022, moduleResolution "Bundler", include src
vite.config.ts         lib build, formats ['es', 'iife'], base './'
eslint.config.js       the enforced coding rules (Coding Style: Tooling)
tobidot-project.json   the manifest
icon.svg               64×64, house palette
index.html             dev harness for `npm start` (not part of the build)
public/                static assets, copied into dist/ unchanged
src/index.ts           the entry script (Lifecycle: Bootstrapping)
src/<Class>*.ts        the element or library
src/applet.scss        the element's styles
src/contracts/         vendored contracts of the libraries it calls
vendor/                the SDK packages, as `file:` dependencies
dist/                  build output: index.js, index.global.js, copied public/ files
```

<a name="static-assets-are-copied-never-inlined"></a>
## Static Assets Are Copied, Never Inlined

Anything in `public/` is copied byte-for-byte into `dist/` and referenced at runtime by a path
resolved against the package's own content root — never against the host page:

```ts
const url = resolve_asset_url(package_identifier, 'textures/grass.png', import.meta.url);
```

Served through the [embed route](./integration#assets-behind-the-embed-route), a script's own
URL says nothing about where its `dist/` lives, so that route records the real content root in
`window.tobidot.content_roots["namespace/name"]` and `resolve_asset_url` prefers that
(validated) entry. Loaded as a module (the dev server, the ES build), `import.meta.url` already
points into `dist/`.

Binary or large assets (images, audio, video, level data) go through `public/`, never through
a `?inline`/`?raw` import. Those suffixes are for small text assets that belong *in* the bundle
— an element's SCSS (`applet.scss?inline`). Inlining a binary as base64 bloats the bundle by a
third and defeats the browser's caching of that file.

<a name="external-assets-need-not-ship-in-the-upload"></a>
## External Assets Need Not Ship in the Upload

The manifest's optional `assets.external` lists fully-qualified URLs (CDN or object storage)
for content that is not bundled into the upload at all:

```json
{ "assets": { "external": ["https://cdn.example.com/my-game/textures.ktx2"] } }
```

Code fetches these the same way — by URL. A project with gigabytes of content is never required
to route it through the package upload.

<a name="init-stays-cheap-heavy-content-is-explicit"></a>
## `init()` Stays Cheap; Heavy Content Is Explicit

`init()` only makes the package *resolvable*: registers the tag, asserts contracts, registers
handlers. Loading content happens in an explicit step after the element is mounted — in
`Logic.start()` behind a once-flag, or in a `load_assets(on_progress)` method the host calls —
never in `init()` and never in the constructor. An icon existing for a package must not cost a
single asset request ([Lifecycle](./lifecycle#icons-must-not-eagerly-load-their-element)).

<a name="code-splitting-for-multi-scene-projects"></a>
## Code-Splitting for Multi-Scene Projects

A single bundle is the floor, not the ceiling. A project with distinct scenes or levels splits
them with dynamic `import()` so a player downloads only the scene they are in:

```ts
async function load_scene(id: string) {
  const scene = await import(`./scenes/${id}.ts`); // its own chunk, fetched on demand
  return scene.default;
}
```

The fixed `fileName` of the lib build applies to the entry only; chunks keep Vite's hashed
names and are resolved relative to the entry, which `base: './'` guarantees.

<a name="dual-build-output-es-and-iife"></a>
## Dual Build Output: ES and IIFE

`vite.config.ts` produces **both** formats from the one entry:

```ts
build: {
  lib: {
    entry: path.resolve(__dirname, 'src/index.ts'),
    name: 'TceMyElement',
    formats: ['es', 'iife'],
    fileName: (format) => (format === 'iife' ? 'index.global.js' : 'index.js'),
  },
},
```

- **`index.js`** (ES) is what the dev harness and module consumers import.
- **`index.global.js`** (IIFE) is what the embed route serves to a plain `<script src>` — without
  it the route answers 404 and the package cannot be embedded or installed.

A build is not done until both exist.

<a name="the-sdk-is-a-build-time-dependency"></a>
## The SDK Is a Build-Time Dependency

`@tce-v3/common` (and `@tce-v3/desktop-protocol` for elements that take part in the desktop)
is TypeScript source compiled into every package by its own build. It is never loaded at
runtime, never shared between packages on a page, and never listed in the registry response. A
generated project carries the SDK versions it was generated against under `vendor/` as `file:`
dependencies; the v3 workspace links them as npm workspaces. Either way the package is
self-contained once built.

<a name="compression--cache-busting"></a>
## Compression & Cache-Busting

- Ship pre-compressed binary assets where the format supports it (Basis/KTX2 textures,
  compressed audio) instead of relying on transport compression.
- Code is cache-safe by construction: every uploaded version gets its own immutable content
  root, and a version-pinned embed URL is served immutable. `public/` files keep their original
  names, so a mutable asset that is replaced within one version carries its own version or hash
  in its path (`/assets/v2/texture.png`).

<a name="publishing-large-projects"></a>
## Publishing Large Projects

A project whose zip is large goes through the site's chunked attachment upload, not a single
multipart request; a publishing path accepting large uploads reuses that one chunk-assembly
mechanism rather than introducing a second.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do keep the project layout above; the generator produces exactly it.
- ✅ Do put every binary asset in `public/` and resolve it with `resolve_asset_url`.
- ✅ Do use `assets.external` for anything too large to ship in the upload.
- ✅ Do keep `init()` to registration; load content after mount, explicitly.
- ✅ Do split a multi-scene project with dynamic `import()`.
- ✅ Do build both the ES and the IIFE output.
- ❌ Don't import a binary with `?inline`/`?raw`.
- ❌ Don't compute asset URLs from `location` or from the host page.
- ❌ Don't block `init()` or the constructor on content.
- ❌ Don't load the SDK at runtime or treat it as a registry dependency.
