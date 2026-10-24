# Integration Into Websites

- [Four Ways to Embed](#four-ways-to-embed)
- [1. Zero-Script Embed](#1-zero-script-embed)
    - [The Served Script Self-Bootstraps](#the-served-script-self-bootstraps)
    - [Dependencies Stay Explicit](#dependencies-stay-explicit)
    - [Versioning the Embed URL](#versioning-the-embed-url)
    - [Assets Behind the Embed Route](#assets-behind-the-embed-route)
- [2. Single Element, Fullscreen](#2-single-element-fullscreen)
- [3. The Desktop](#3-the-desktop)
- [4. Installing Packages at Runtime](#4-installing-packages-at-runtime)
- [Do's and Don'ts](#dos-and-donts)

Any site — not just this one — can embed a TCE: the only hard dependency is the embed route of
the registry that hosts the package. A package must be portable to any website with no
framework and no build step on the embedding side.

<a name="four-ways-to-embed"></a>
## Four Ways to Embed

| Page                          | Route                                     | Embedder writes JS? |
|-------------------------------|-------------------------------------------|---------------------|
| Zero-script embed             | `/tce-elements/{namespace}/{name}`        | No                  |
| Fullscreen single element     | `/tce/{name}`, `/tce-packages/{id}/view`  | No (server-rendered)|
| The desktop                   | `/` (below the site header), `/v3` (bare) | No (server-rendered)|
| Installed at runtime          | the desktop's package manager             | No                  |

<a name="1-zero-script-embed"></a>
## 1. Zero-Script Embed

The baseline every package supports: one `<script>` tag per package on an otherwise unrelated
page, and as many element tags as wanted:

```html
<script src="https://game-object.de/tce-elements/tobidot/tce-clock"></script>

<tce-clock></tce-clock>
```

The page writes no JavaScript. Tag order does not matter; custom-element upgrade is not
order-dependent.

<a name="the-served-script-self-bootstraps"></a>
### The Served Script Self-Bootstraps

`GET /tce-elements/{namespace}/{name}` serves the package's **IIFE** build
(`dist/index.global.js`) as `application/javascript` — not an ES module, so a plain
`<script src>` without `type="module"` works. The script is the package's own entry script
([Lifecycle: Bootstrapping](./lifecycle#bootstrapping-the-entry-point-binds-the-package)); on
execution it

1. obtains or creates the page's one loader (`TceRoot.package_loader()`),
2. binds itself under its identifier (`TceRoot.bind_self`), and
3. runs `init()`: registers its tag immediately — tags already in the DOM upgrade and render
   their static shell right away — and asserts its library contracts, which may finish later.

Short form: `/tce-elements/{name}` expands to the registry's own default namespace
(`config/tce.php`). Any other publisher is addressed with the two-part form.

<a name="dependencies-stay-explicit"></a>
### Dependencies Stay Explicit

A package's library dependencies are **not** auto-injected by its script. The embedder adds
them as their own `<script>` tags, dependencies first:

```html
<script src="https://game-object.de/tce-elements/tobidot/dll-tce-file-system"></script>
<script src="https://game-object.de/tce-elements/tobidot/tce-notepad"></script>
```

This is deliberate: a page embedding several elements that share a library must load that
library **once**, as one instance in the page's one loader — not once per element, privately.
A package waits for its dependencies with `with_binding()`/`has_binding()` against the shared
loader, which is satisfied the moment the dependency's own script has run. If a required
dependency never binds, `with_binding` rejects after its bounded timeout with a message naming
the identifier and the `<script>` tag to add; the package reports that through `TceRoot.report`
and an element shows it in its own shadow DOM. "Hangs forever with no visible error" is not an
acceptable failure mode for an embedder who does not have this codebase open.

<a name="versioning-the-embed-url"></a>
### Versioning the Embed URL

Without a version the route serves the latest upload (cached briefly). With
`/tce-elements/{namespace}/{name}@{major}.{minor}.{patch}` it serves exactly that version, as an
immutable, long-cached response. Anything beyond a quick demo pins a version.

<a name="assets-behind-the-embed-route"></a>
### Assets Behind the Embed Route

A script served from `/tce-elements/...` cannot find its `dist/` from its own URL. The route
therefore prefixes the script with one line recording the package's real content root in
`window.tobidot.content_roots["namespace/name"]`, and `resolve_asset_url` uses it
([Assets & Build](./assets-and-build#static-assets-are-copied-never-inlined)).

<a name="2-single-element-fullscreen"></a>
## 2. Single Element, Fullscreen

`/tce/{name}` (latest element version of that name in the site's default namespace) and
`/tce-packages/{id}/view` (one specific upload) render a bare page containing exactly the
zero-script embed: the package's declared dependency graph as `<script>` tags in load order
(dependencies before dependents, each once), then the package's own tag, filling the viewport.
Link externally to `/tce/{name}` so the link follows new versions.

<a name="3-the-desktop"></a>
## 3. The Desktop

The homepage is the same mechanism with `tce-desktop` as the embedded element: the server
renders the desktop's dependency chain (`tce-icon`, `tce-window`, `tce-bootloader`,
`dll-tce-settings`, …) plus the default applications as script tags, then
`<tce-desktop data-apps="…" data-app-icons="…">`. `/v3` is the same page without the site
header. No bundler and no page-specific bootstrap script run on either page. While no
`tce-desktop` is published, the homepage is the site's regular homepage and `/v3` answers 404.

Three more attributes dress the desktop, each a comma-separated list of package names:
`data-window-decoration="tce-flames,tce-cat"` for every window's `decoration` slot,
`data-effects="tce-klabautermann,tce-snowfall"` for the desktop's own `effects` slot and
`data-tray="tce-clock"` for its `task-bar-icons` slot
([Declaration: Slots as Attributes](./declaration#slots-as-attributes)). The named packages are
ordinary packages: they need their own `<script>` tag on the page or to be installed through the
package manager — the desktop only names them, and a name it cannot load is a warning, never a
broken desktop. The same choices, and the attributes each composed element is created with, are
edited in the properties dialog ("Desktop Properties…" on the desktop menu; `tce-inspector` is a
dependency of the desktop) or in the Settings program, and are remembered across reloads.

Everything the desktop remembers — background, composition, component and icon attributes,
requested icons — is a file below `/system/tce-desktop/` in `dll-tce-file-system`, a dependency of
the desktop. A file that cannot be read counts as missing and the page's defaults apply; storage
never breaks the desktop.

<a name="4-installing-packages-at-runtime"></a>
## 4. Installing Packages at Runtime

The desktop's package manager (`tce-bootloader`) reads the public registry
([Publishing & Discovery](./upload-and-registry#public-discovery-endpoint)), lists every package,
and installs one by injecting its embed script with `TceRoot.load_script('/tce-elements/{namespace}/{name}')`
— dependencies first, each only if not yet bound. Installing is therefore exactly the
zero-script embed performed at runtime; there is no second loading mechanism.

The installed set is recorded in `dll-tce-file-system` (a dependency of the package manager) as
`/system/tce-bootloader/installed.json`: a JSON array of `namespace/name` in install order, every
dependency before the packages that need it, and only what was installed through the package
manager — never what the page itself embeds. The package manager's own boot (its `init()`, run
when its script loads, not when a window opens) re-installs every recorded package the same way,
in that order. A package that fails to re-install is reported and skipped; the others still
install. Activating an installed package right after a reload simply waits for its binding.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do make every package work as a zero-script `<script src>` embed — this is the baseline.
- ✅ Do build the IIFE output; without it the embed route answers 404.
- ✅ Do keep library dependencies as separate, explicit `<script>` tags, dependencies first.
- ✅ Do fail loudly, in the element's own rendered output, when a required dependency never
  binds.
- ✅ Do pin a version in the embed URL for anything beyond a demo.
- ✅ Do keep what the desktop and the package manager remember in `dll-tce-file-system`, never in
  `localStorage`.
- ❌ Don't auto-inject dependencies from a package script.
- ❌ Don't write a page-specific bootstrap; the entry script is the bootstrap.
- ❌ Don't rely on cross-origin cookies or authentication from an embedded element — the embed
  and registry routes are intentionally unauthenticated reads.
