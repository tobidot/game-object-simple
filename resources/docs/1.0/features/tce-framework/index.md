# Tobidot Custom Elements (TCE) Framework

- [What is a TCE?](#what-is-a-tce)
- [TCE Project Generator](#tce-project-generator)
- [Current State (October 2026 Audit)](#current-state-october-2026-audit)
- [Where to go next](#where-to-go-next)

This page mirrors the latest TCE framework knowledge/audit document for browsability on this site.
The canonical, always-up-to-date source lives in code, in the `collection-html-custom-elements`
repository: `documentation/tce-framework-audit-2026-10.md`. Update that file first, then copy the
relevant parts here when publishing a new app documentation version.

## What is a TCE?

A "Tobidot Custom Element" (TCE) is a self-contained Web Component (built with TypeScript, usually
bundled with Vite) that can be uploaded as a zip package to this app via
`POST /api/v1/tobidot-elements` (or the Nova `UploadTobidotElement` action) and then dynamically
loaded and rendered in the browser by `resources/js/tobidot-element-viewer.ts`, which fetches
`GET /api/public/tobidot-elements`, loads the latest `dll-tce-package-registry`, and calls
`document.createElement(name)` for the requested element.

**Important naming rule:** an element's `name` is used directly as its HTML custom-element tag, so
it must be a valid, hyphenated tag name (e.g. `tce-change`, not `change`).

## TCE Project Generator

You can quickly scaffold a new, complete TCE project template using the built-in **TCE Generator**:
- **Web UI:** Navigate to `/tce-generator` in your browser. Configure element name, title, description, window resolution, icon, starter preset (Standard, 2D Canvas, Minimal), and **TCE dependencies** (select registered server elements and libraries such as `dll-tce-desktop`, `dll-tce-file-system`, etc. via interactive dropdowns and quick chips), then download the generated ZIP archive.
- **Artisan CLI:** Run `php artisan tce:generate tce-my-element --width=800 --height=600 --template=canvas --tce-dependency=dll-tce-desktop` to generate the project directly from the command line.
- **REST API:** `POST /api/public/tce-generator` or `POST /api/v1/tce-generator`.

The generated package includes full TypeScript config, Vite bundler configuration, SCSS scoping, local dev testing harness (`npm start`), `tobidot-project.json` manifest with structured dependency identifiers, and a `Makefile` for packaging release zips with `make tce`.

## Current State (October 2026 Audit)

- **Package inventory**: shared libraries (`common`, `dll-tce-package-registry`, `dll-tce-desktop`,
  `dll-tce-file-system`) and individual elements (`tce-desktop`, `tce-bootloader`, `tce-clock`,
  `tce-notepad`, `tce-explorer`, `tce-documentation`, `tce-shape-me`, and several prototypes).
- **Runtime loading**: handled by `TcePackageLoader`/`TceRoot` (in `common`) and
  `TcePackageRegistryLib` (binds remote packages; wraps raw `.html` content, e.g. Emscripten/WASM
  builds, in a generic iframe custom element since those builds can't expose a JS module).
- **Loader robustness fixes shipped in this pass**: packages no longer initialize twice,
  `with_binding()` now supports an optional timeout instead of waiting forever, version
  serialization no longer inverts valid/invalid versions, and version matching now uses real
  caret-style SemVer ranges (same major, minor/patch as a floor) instead of requiring an exact
  match.
- **Desktop shell**: `tce-desktop` is an icon grid, taskbar (start button, system tray), draggable/resizable windows and a trash bin. Icon positions are stored through `dll-tce-file-system` (`/system/desktop/layout.json`) and restored on boot (see the desktop update below).
- **Package manager**: `tce-bootloader` browses/installs/uninstalls packages and persists the installed set, autostart and shortcuts in the browser (see the update below); version pinning is not implemented yet.

## Where to Go Next

See the full audit (package-by-package detail, exact file/line references, and the complete list of
open gaps) in code at `collection-html-custom-elements/documentation/tce-framework-audit-2026-10.md`.

## Bootloader, windows and fullscreen play (update)

- `tce-bootloader` is split into a headless `BootManager` and the `<tce-bootloader>` package manager window (a list next to a details pane, pagination, type/status filters, search, visible error banner and boot log). The boot runs when the package is initialised, even if the window is never opened.
- **Installed** packages, **autostart** (opens a window on every boot), **desktop shortcuts** and the already-seen package list are persisted in `localStorage['tce.boot.config']` (schema version 2, older configs are migrated). Freshly uploaded packages get a NEW badge and an info banner.
- **Uninstall** clears installed/autostart/shortcut and closes the windows the bootloader opened; a browser cannot unregister a custom element, so already running code stays until the next reload. System packages and libraries required by installed elements cannot be uninstalled.
- Windows size their app (`::slotted(*)` fills the window). Apps may only bubble `tce.window.request` (`fullscreen`, `maximize`, `restore`, `minimize`, `close`, `resize`); see `documentation/v1/window-protocol.md` and `documentation/v1/bootloader.md` in `collection-html-custom-elements`.
- Every element has a fullscreen page at `/tce/{name}` (latest version), besides `/tobidot-elements/{id}/view`.

## Desktop (update)

- **Persistent icon layout**: icons snap to a 96 px grid, never overlap and keep their cell across reloads. The layout is written to `/system/desktop/layout.json` via `dll-tce-file-system`; icons are identified by `TceIconOptions.id`. A cell that does not fit a small screen is replaced by the nearest free one, but the original position is remembered.
- **Start menu** (left) opens the package manager (`tce-bootloader`) if it is installed, otherwise it shows a notice.
- **System tray** (bottom right): small elements such as a clock via `create_tray_item`; the package manager has a "system tray" option per element.
- **New elements** are previewed on the desktop (image, name, description) via `set_news` before the package manager is ever opened.
- Throwing an icon into the trash bin removes it for good (event `tce.desktop.icon-removed`).
- Details: `documentation/v1/desktop.md` in `collection-html-custom-elements`.

## Explorer and "open with" (update)

- `tce-explorer` shows real folders (derived from the flat keys of `dll-tce-file-system`): double click to enter, path field, up button, Backspace, F5.
- Right click on a file offers **Open** (first fitting application) and **Open with** (all registered applications, fitting ones first).
- Elements that can open files register with `register_file_handler({ element, title, extensions, attribute })` on the file system library when their package initialises (`tce-notepad` does so for text-like files). The opener creates the element, sets the attribute to the absolute path and shows it in a desktop window; the element only reacts to the attribute.
- Details: `documentation/v1/file-handlers.md` in `collection-html-custom-elements`.
