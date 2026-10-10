---
sessionId: session-261007-083753-aybz
---

# Requirements

### Overview & Goals
The C++ jam game at `collection-of-jams/olc-jam-2026-change` (olc Game Jam "Change", built with olcPixelGameEngine V3) already has a working Emscripten/WebAssembly build (`build-web/` with `index.html`, `index.js`, `index.wasm`, `index.data`), produced manually by following the steps in `README.md`. We want a repeatable `make` target that turns this C++ project into a properly packaged **tce** (Tobidot Custom Element) zip, following the same packaging convention used by the sibling `collection-html-custom-elements` projects (e.g. `tce-point-and-click`, `tce-shape-me`), so the resulting zip can be uploaded as a `TobidotElement` via the Nova admin UI of this Laravel app (`game-object-de`).

### Scope
**In Scope**
- A root-level `Makefile` in `olc-jam-2026-change` with targets to configure/build the Emscripten web build, package it into a tce-compatible zip, and clean build artifacts.
- A plain-text `VERSION` file for manual semantic-version tracking (required format `major.minor.patch`, matching the Laravel upload endpoint's validation).
- Configurable element name/kind (Makefile variables with sane defaults, overridable from the command line), rather than a hardcoded identity.
- Documentation in the project's `README.md` describing the new workflow end-to-end, including how to upload the generated zip via Nova.

**Out of Scope**
- Any changes to the `collection-html-custom-elements` tooling — only used as reference/contract.
- CI/CD integration.

> **Update:** manual-only upload turned out to be unreliable to verify, so a `make upload` target was added afterwards (see Step 5) to automate the HTTP upload using a locally-stored bearer token; this also surfaced and fixed a blocking PHP upload-size limit in `game-object-de`'s dev container.

### User Stories
- As the game's developer, I want to run a single `make` command to produce a ready-to-upload tce package, so that I don't have to manually repeat the emcmake/cmake/zip steps every release.
- As the game's developer, I want the package name, kind, and version to be easily configurable, so that I'm not locked into a single hardcoded identity for the element.
- As an admin, I want the generated zip to match the structure the Laravel app already knows how to unpack and serve, so that the game loads correctly as a tce element without backend changes.

### Functional Requirements
- `make web` builds the Emscripten target (configuring `build-web/` on first run) and produces `index.html`, `index.js`, `index.wasm`, `index.data`.
- `make tce` depends on `make web`, stages those four files inside a `dist/` folder, and zips them into `releases/<name>-v<version>.zip`.
- `NAME`, `KIND`, and the version (from `VERSION`) must be overridable, e.g. `make tce NAME=change KIND=element`.
- The produced zip, once extracted, must expose `index.html` at a discoverable top level (either flat or inside a single wrapping folder), matching the existing `AttachmentService` extraction/flattening behavior in `game-object-de`.
- `make clean-tce` removes staging/package artifacts without touching the CMake `build-web` directory (so repeated packaging is cheap).

# Technical Design

### Current Implementation
- `olc-jam-2026-change/CMakeLists.txt` already has a full Emscripten branch (`if(EMSCRIPTEN)`) that sets `OUTPUT_NAME "index"`, uses `src/index.html` as the shell file, and preloads `assets/` into `index.data` via `--preload-file`. Building with `emcmake cmake -S . -B build-web && cmake --build build-web` already produces `build-web/index.html`, `index.js`, `index.wasm`, `index.data` (verified present in the repo from a prior manual build).
- There is currently **no root `Makefile`**, no `VERSION` file, and no packaging/zip step — only the raw CMake build steps documented in `README.md`. Two stale manually-created zips (`build-web/change-v1.0.0.zip`, `build-web/index-v1.1.0.zip`) show this was previously done by hand, with inconsistent naming/structure.
- In `game-object-de`, `TobidotElementController::upload` → `TobidotElementService::upload` → `AttachmentService::createFromUploadedZipFile` (`app/Services/Models/AttachmentService.php`) extracts the uploaded zip, and **auto-flattens** it if it contains exactly one top-level folder (so a `dist/` wrapper is optional, not required). `AttachmentService::discoverIndex` then searches the extracted root for `{Name}.js`, `{Name}.es.js`, `{kebab-name}.js`, `index.js`, `index.es.js`, or `index.html`, in that order — `index.html` is an explicitly supported candidate, which is exactly what the Emscripten build already produces.
- Studying `collection-html-custom-elements/tce-point-and-click` and `tce-shape-me` (both `"execution": {"type": "website", "resources": "dist", "index": "index.html"}` in `tobidot-project.json`) confirmed the established convention: the committed release zip (e.g. `tce-shape-me/releases/the-landshape-v1_3_0.zip`) contains only a `dist/` folder with the built web assets — the `tobidot-project.json` manifest itself is **not** included in the uploaded zip; it's local metadata used only by the project's own tooling (e.g. `dev-server/upload-element.ts`, which reads `title` from it before zipping `dist/`).

### Key Decisions
- **Upload stays manual** (confirmed by user): the Makefile only builds and packages; the operator uploads the resulting `releases/<name>-v<version>.zip` through the Nova admin UI (`UploadTobidotElement` action) or the authenticated API, as already supported by `game-object-de`. No API token needs to be stored in the C++ project.
- **Version is tracked in a plain `VERSION` file**, bumped manually before each release build; this matches the `major.minor.patch` regex required by `UploadTobidotElementRequest::rules()`.
- **Element name/kind are configurable Makefile variables** (`NAME ?= change`, `KIND ?= element`) rather than hardcoded or derived from a JSON manifest — avoids adding a JSON-parsing dependency (e.g. `jq`) to a C++/Make project, while remaining fully dynamic via `make tce NAME=... KIND=...`.
- **Zip packaging mirrors the established "website" tce convention**: stage the four Emscripten output files inside a `dist/` folder, then zip that folder (so the archive's single top-level entry is `dist/`). This is compatible with `AttachmentService`'s auto-flatten logic and matches what `tce-point-and-click`/`tce-shape-me` release zips look like, rather than copying the ad-hoc flat layout of the stale manual zips already sitting in `build-web/`.

### Proposed Changes
1. **Root `Makefile`** (new file, `olc-jam-2026-change/Makefile`) with:
   - `NAME ?= change`, `KIND ?= element`, `VERSION := $(shell cat VERSION)` variables.
   - `web` target: runs `emcmake cmake -S . -B build-web` only if `build-web/CMakeCache.txt` is missing (idempotent configure), then always `cmake --build build-web`.
   - `tce` target (depends on `web`): creates `build-tce/dist/`, copies `build-web/index.html`, `index.js`, `index.wasm`, `index.data` into it, creates `releases/` if missing, and zips `build-tce` into `releases/$(NAME)-v$(VERSION).zip` (so `dist/` is the single top-level folder in the archive).
   - `clean-tce` target: removes `build-tce/` and the generated zip for the current version (does not touch `build-web`/`build`/`build-debug`).
   - `.PHONY` declarations for all targets.
2. **`VERSION` file** (new, root): initial content `1.0.0` (or `1.1.0` to continue from the existing stale `index-v1.1.0.zip`), bumped manually by the developer before each release.
3. **`.gitignore` update**: add `/build-tce` (ephemeral staging directory); leave `/releases` tracked, consistent with how `tce-clock`/`tce-shape-me` commit their release zips.
4. **`README.md` update**: extend the existing "Build for Web" section with a "Package as tce element" subsection documenting `make web`, `make tce NAME=... KIND=... `, where the zip ends up, and a short note on manually uploading it via the Nova `UploadTobidotElement` action (or `POST /api/v1/tobidot-elements`) in `game-object-de`.

### File Structure
```
olc-jam-2026-change/
├── Makefile                  # new - web / tce / clean-tce targets
├── VERSION                   # new - plain semver string, manually bumped
├── .gitignore                # modified - add /build-tce
├── README.md                 # modified - document the new make workflow
├── build-tce/                # new, gitignored, created by `make tce`
│   └── dist/
│       ├── index.html
│       ├── index.js
│       ├── index.wasm
│       └── index.data
└── releases/                 # new (or existing), committed
    └── change-v1.1.0.zip     # output of `make tce`
```

### Architecture Diagram
```mermaid
graph LR
    A[make web] -->|emcmake + cmake --build| B[build-web/index.html,js,wasm,data]
    B --> C[make tce: stage into build-tce/dist]
    C --> D[zip -> releases/name-vVERSION.zip]
    D -->|manual upload via Nova UploadTobidotElement| E[game-object-de: AttachmentService extracts + auto-flattens]
    E --> F[discoverIndex finds index.html]
    F --> G[TobidotElement served / viewable]
```

### Risks
- **Large asset payload**: the existing `build-web/index.data` is ~119 MB (assets preloaded via `--preload-file`). This may exceed default PHP (`upload_max_filesize`/`post_max_size`) or web-server upload limits on `game-object-de`, and bloats the git repo if `releases/` zips are committed — worth flagging to the user, though adjusting Laravel/php.ini limits is outside this project's scope.
- **Idempotent CMake configure**: guarding `emcmake cmake` behind a `CMakeCache.txt` existence check avoids slow re-configures but could mask stale-cache issues if Emscripten SDK/toolchain changes; a `make reconfigure-web` escape hatch could be added if this becomes a problem.
- **Tooling availability**: the `tce` target relies on a standard `zip` CLI being available in the dev environment (already used manually for the stale zips, so expected to be present).

# Delivery Steps

### ✓ Step 1: Add root Makefile with VERSION file and the `web` build target
A new root-level `Makefile` exists in `olc-jam-2026-change` that reproduces the documented Emscripten build steps as a single, idempotent command.
- Create `olc-jam-2026-change/Makefile` with `NAME ?= change` and `KIND ?= element` variables and `.PHONY` declarations.
- Implement the `web` target: configure `build-web/` with `emcmake cmake -S . -B build-web` only if `build-web/CMakeCache.txt` doesn't exist yet, then always run `cmake --build build-web`.
- Create the `VERSION` file at the repo root (plain `major.minor.patch` string, e.g. `1.1.0`).
- Verify by running `make web` from a clean checkout (or with `build-web` removed) and confirming `build-web/index.html`, `index.js`, `index.wasm`, `index.data` are produced.

### ✓ Step 2: Add `tce` packaging target that stages and zips the web build
Running `make tce` produces `releases/<name>-v<version>.zip`, structured so the Laravel backend can extract and serve it as a tce element.
- Implement the `tce` target (depends on `web`): create `build-tce/dist/`, copy `index.html`, `index.js`, `index.wasm`, `index.data` from `build-web/` into it.
- Create `releases/` if it doesn't exist, then zip `build-tce` so the archive's single top-level entry is `dist/` (`cd build-tce && zip -r ../releases/$(NAME)-v$(VERSION).zip dist`).
- Read `VERSION` from the `VERSION` file via `$(shell cat VERSION)`; allow overrides via `make tce NAME=... KIND=...`.
- Verify by unzipping the produced archive and confirming it matches the known-good structure (single `dist/` folder containing the four build artifacts), analogous to `tce-shape-me/releases/the-landshape-v1_3_0.zip`.

### ✓ Step 3: Add `clean-tce` target and update `.gitignore`
Packaging artifacts can be cleaned without disturbing the CMake build directories, and the staging folder is excluded from version control.
- Implement `clean-tce` target: remove `build-tce/` and the specific `releases/$(NAME)-v$(VERSION).zip` for the current `NAME`/`VERSION`.
- Add `/build-tce` to `.gitignore`; leave `/releases` tracked so release zips remain committed, consistent with `tce-clock`/`tce-shape-me`.
- Verify by running `make tce` then `make clean-tce` and confirming `build-tce/` and the generated zip are removed while `build-web`/`build`/`build-debug` are untouched.

### ✓ Step 4: Document the packaging workflow in README.md
The project's `README.md` explains the full path from source to an uploadable tce package.
- Extend the existing "Build for Web" section with a "Package as tce element" subsection.
- Document `make web`, `make tce NAME=... KIND=...`, the default values, and where the output zip is written.
- Add a short note on manually uploading the generated zip via the Nova `UploadTobidotElement` action (or `POST /api/v1/tobidot-elements`) in `game-object-de`, including the required `name`, `version`, and `kind` fields.

### ✓ Step 5: Add an automated `upload` target and fix the blocking PHP upload limit
Manual uploads were not succeeding, so an `upload` target now POSTs the packaged zip directly to the Laravel API using a locally-stored bearer token, and the root cause of the failures (a too-small PHP upload limit in the app container) is fixed.
- Add an `upload` target to the Makefile (depends on `tce`): POSTs `name`, `version`, `kind` and the zip file via `curl` with a Bearer token to `UPLOAD_URL` (defaults to `http://localhost/api/v1/tobidot-elements`).
- Read the token from a local, gitignored `upload.env` file (`UPLOAD_TOKEN=...`), overridable via `make upload UPLOAD_TOKEN=...`; add `/upload.env` to `.gitignore`.
- Diagnose why uploads failed: `game-object-de`'s dev container (`docker/app/Dockerfile`, `php artisan serve`) only allowed `upload_max_filesize=2M`/`post_max_size=8M`, far below the ~120 MB packaged zip; raise these limits via a custom `php.ini` copied into the image, rebuild and restart the container.
- Verify by running `make upload` against the local `game-object-de` instance with the provided token and confirming the `TobidotElement` is created successfully (HTTP 2xx, record visible via the API/Nova).