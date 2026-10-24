# game-object.de

Laravel site (Nova admin, LaRecipe docs at `/docs`, Sanctum-authenticated MCP server at `/mcp`).
The homepage `/` is the TCE v3 desktop; the v3 packages are built in the separate workspace
`../../tce-elements-project/v3` (see its CLAUDE.md).

## TCE v3 integration

- Spec (pure specification, no "current state" notes, namespace-open): `resources/docs/1.0/features/tce-framework/`.
  When code and spec disagree, fix one of them in the same change.
- Data: `tce_packages` + `tce_package_dependencies`, `App\Models\TcePackage`, `App\Services\Models\TcePackageService`
  (the single publishing path: version bump, carry-over, dependency resolution, name validation).
  Kinds: `element`, `library`, `sdk` (build-time only: `common`, `desktop-protocol`, vendored by the generator).
  The legacy `tobidot_elements` table is read-only reference material for porting.
- This site's own namespace and the SDK package list live in `config/tce.php`; nothing else hardcodes `tobidot`.
- Routes in `TcePackageController`:
  - registry `GET /api/public/tce-packages` (elements + libraries only), grouped by `namespace/name`.
    Keep `TcePackageResource` in sync with `registry_response_schema` in v3 `common`.
  - zero-script embed `/tce-elements/{namespace}/{name}[@x.y.z]`. It prefixes the script with its content root.
  - `/tce/{name}` (latest element, legacy fallback while porting), `/tce-packages/{id}/view`, homepage `/`, `/v3`.
- `/documentation/{path}` serves the raw docs markdown to the `tce-documentation` element.
- Publishing: `POST /api/v1/tce-packages` (with `dependencies[i][namespace|name]`, `width`, `height`),
  MCP tools `upload_tce_package` / `list_tce_packages`, or `php artisan tce:import-v3-packages`
  from the gitignored staging folder `storage/app/v3-import/packages` (manifest-driven, sorted by dependencies).
- Generator (`/tce-generator`, `tce:generate`, `POST /api/public/tce-generator`): `TceGeneratorService` +
  `TceGeneratorConfig` + templates in `resources/tce-templates/`. It vendors the latest `sdk` uploads into
  `vendor/` and lists dependencies from `tce_packages`; it refuses to run until the SDK is published.

## Pulling live data into a local instance

- `LiveSyncService` + `App\Enums\LiveSyncSection` (posts, media, code = projects and custom elements). The live
  site serves a section's raw rows at `GET /api/v1/live-sync/{section}` (any Sanctum token, like the rest of `/api/v1`); the
  local instance replaces its rows with them and downloads the files from live's `/storage/...`, sha256-checked
  against `attachments.hash`, zips unpacked like an upload. Only local instances pull (`LiveSyncService::pullAllowed()`: env `local`/`testing`); elsewhere the action and command are hidden and the service refuses.
- Nova action `PullFromLive` (Pages = posts, Attachments = media, Projects / Tobidot Elements / TCE Packages = code)
  or `php artisan live:pull {posts|media|code}... [--files-only]`. `--files-only` keeps the local rows (e.g. after
  importing a live DB dump) and only fetches their files. Config: `LIVE_URL`, `LIVE_API_TOKEN` (`config/services.php`).

## Working here

- Publish and verify against the local site only, never live, unless asked.
- PHP tests: `docker compose exec app php artisan test` (the app runs in Docker on the host).
