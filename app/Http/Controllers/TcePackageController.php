<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Http\Requests\UploadTcePackageRequest;
use App\Http\Resources\TcePackageResource;
use App\Models\TcePackage;
use App\Services\LiveSyncService;
use App\Services\Models\AttachmentService;
use App\Services\Models\TcePackageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tobidot\ChunkedUpload\ChunkManager;

use function Illuminate\Filesystem\join_paths;

/**
 * Serves v3 packages — see "Publishing & Discovery: Data Model".
 */
class TcePackageController extends Controller
{
    /** Chunked package uploads: at most this many chunks of at most this size (500 MB in all). */
    public const int MAX_CHUNKS = 50;

    public const int MAX_CHUNK_KILOBYTES = 10 * 1024;

    /**
     * This deployment's own default publisher namespace (`config/tce.php`) — used only as a
     * convenience default for the short-form routes (`/tce/{name}`, `/tce-elements/{name}`).
     * It is one namespace among many, never a framework-level assumption — see "Naming &
     * Identifiers: Namespace".
     */
    protected function defaultNamespace(): string
    {
        return (string) config('tce.default_namespace');
    }

    /**
     * Show a single package loaded fullscreen and borderless, via the zero-script embed.
     */
    public function view(TcePackage $tcePackage): View
    {
        return view('tce-packages.view', [
            'package' => $tcePackage,
            'embed_chain' => $this->resolveEmbedChain($tcePackage),
        ]);
    }

    /**
     * Fullscreen play page for the latest version of an element, addressed by name within this
     * deployment's own default namespace — see "Publishing & Discovery: Fullscreen Play Routes".
     */
    public function play(string $name): View
    {
        $package = TcePackage::query()
            ->where('namespace', $this->defaultNamespace())
            ->where('name', $name)
            ->where('kind', 'element')
            ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
            ->first();

        if ($package === null) {
            abort(404);
        }

        return $this->view($package);
    }

    /**
     * The v3 desktop, bare/fullscreen — a stable preview URL with no site chrome, independent
     * of wherever the desktop is also promoted to (see `home()`).
     */
    public function desktop(): View
    {
        $desktop = $this->latestDesktop();
        abort_if($desktop === null, 404);

        return view('tce-packages.desktop', $this->desktopViewData($desktop));
    }

    /**
     * The v3 desktop as the site's actual homepage — same desktop, rendered below the normal
     * page header/nav.
     */
    public function home(): View
    {
        $desktop = $this->latestDesktop();
        if ($desktop === null) {
            // Until a desktop is published (e.g. right after deploying), keep the regular homepage.
            return parent::home();
        }

        return view('tce-packages.home', $this->desktopViewData($desktop));
    }

    protected function latestDesktop(): ?TcePackage
    {
        return TcePackage::query()
            ->where('namespace', $this->defaultNamespace())
            ->where('name', 'tce-desktop')
            ->where('kind', 'element')
            ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
            ->first();
    }

    /**
     * @return array{package: TcePackage, embed_chain: TcePackage[], apps: string, app_icons: string}
     */
    protected function desktopViewData(TcePackage $desktop): array
    {
        $app_packages = TcePackage::query()
            ->where('namespace', $this->defaultNamespace())
            ->whereIn('name', ['tce-notepad', 'tce-settings'])
            ->where('kind', 'element')
            ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
            ->get()
            ->unique('name');

        $attachment_service = AppHelper::resolve(AttachmentService::class);
        $app_icons = $app_packages
            ->mapWithKeys(function (TcePackage $package) use ($attachment_service) {
                $icon = $package->iconAttachment()->first();

                return $icon ? [$package->name => $attachment_service->getUrl($icon)] : [];
            });

        return [
            'package' => $desktop,
            'embed_chain' => $this->resolveEmbedChain($desktop, ['tce-notepad', 'tce-settings']),
            'apps' => $app_packages->pluck('name')->implode(','),
            'app_icons' => $app_icons->isEmpty() ? '{}' : $app_icons->toJson(JSON_UNESCAPED_SLASHES),
        ];
    }

    public function index(): JsonResponse
    {
        $grouped = TcePackage::query()
            // A package without code can't be loaded and would break client-side registry
            // resolution — see "Publishing & Discovery: Public Discovery Endpoint". SDK
            // packages are build-time only (vendored by the generator), never loaded at runtime.
            ->whereHas('codeAttachment')
            ->whereIn('kind', ['element', 'library'])
            ->with(['dependencies', 'codeAttachment', 'iconAttachment'])
            ->get()
            ->groupBy(fn (TcePackage $package) => "$package->namespace/$package->name")
            ->map(
                fn (Collection $items) => $items->values()->map(fn (TcePackage $package) => new TcePackageResource($package))
            );

        return response()->json([
            'version' => 1,
            'packages' => $grouped->isEmpty() ? (object) [] : $grouped,
        ]);
    }

    /**
     * @throws ValidationException|\Exception
     */
    public function upload(UploadTcePackageRequest $request, ChunkManager $chunks): JsonResponse
    {
        $zip = $request->file('zip');
        $chunk_upload_id = null;
        if (! $zip) {
            $chunk_upload_id = $this->chunkUploadId($request, $request->input('zip_upload_id'));
            $zip = $chunks->createUploadedFile($chunk_upload_id, "{$request->name}.zip", 'application/zip');
            if (! $zip) {
                throw ValidationException::withMessages(['zip_upload_id' => ['No complete upload with this id: send every chunk to /api/v1/tce-packages/chunks first.']]);
            }
        }

        try {
            $package = AppHelper::resolve(TcePackageService::class)
                ->upload(
                    $request->namespace,
                    $request->name,
                    $zip,
                    $request->version ?? null,
                    $request->kind ?? null,
                    $request->description ?? null,
                    $request->file('icon'),
                    $request->has('width') ? (int) $request->width : null,
                    $request->has('height') ? (int) $request->height : null,
                    $request->dependencies(),
                    $request->tags(),
                );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['version' => [$exception->getMessage()]]);
        } finally {
            if ($chunk_upload_id !== null) {
                $chunks->cleanupUpload($chunk_upload_id);
            }
        }

        return response()->json([
            'message' => 'Tce package uploaded successfully',
            'package' => new TcePackageResource($package->fresh('dependencies')),
        ]);
    }

    /**
     * One chunk of a package zip too large for a single request (PHP's post_max_size), stored
     * through the same `ChunkManager` the Nova upload uses; the last chunk assembles the zip.
     * `POST /api/v1/tce-packages` then names it by `zip_upload_id` — see "Publishing &
     * Discovery: Large Uploads".
     */
    public function uploadChunk(Request $request, ChunkManager $chunks): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chunk_index' => ['required', 'integer', 'min:0', 'lt:total_chunks'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:'.self::MAX_CHUNKS],
            'chunk' => ['required', 'file', 'max:'.self::MAX_CHUNK_KILOBYTES],
        ]);

        $id = $this->chunkUploadId($request, $validated['upload_id']);
        $chunks->saveChunk($id, (int) $validated['chunk_index'], $request->file('chunk'));
        if (! $chunks->hasAllChunks($id, (int) $validated['total_chunks'])) {
            return response()->json(['status' => 'chunk_uploaded', 'chunk_index' => (int) $validated['chunk_index']]);
        }
        $path = $chunks->assembleChunks($id, (int) $validated['total_chunks'], 'package.zip');

        return response()->json(['status' => 'completed', 'upload_id' => $validated['upload_id'], 'size' => filesize($path)]);
    }

    /** Chunk sessions are scoped to the token's user: an upload id never reaches another user's chunks. */
    protected function chunkUploadId(Request $request, string $upload_id): string
    {
        return 'tce-package-'.$request->user()->id.'-'.$upload_id;
    }

    /**
     * Serves the package's IIFE build directly, for a plain `<script src>` with no
     * `type="module"` — see "Integration Into Websites: Zero-Script Embed". An optional
     * `@major.minor.patch` suffix on `$name` pins a version; otherwise the latest is served.
     */
    public function embedScript(Request $request): Response
    {
        // Read route parameters explicitly by name rather than via controller-method
        // positional binding: Laravel binds plain (non-model) route parameters to method
        // arguments by their position in the route URI, not by matching parameter names —
        // with two routes sharing this action at different arities ({name} vs
        // {namespace}/{name}), positional binding silently swapped namespace and name.
        $namespace = $request->route('namespace') ?? $this->defaultNamespace();
        $name = (string) $request->route('name');
        [$name, $version] = $this->splitVersionSuffix($name);

        $query = TcePackage::query()
            ->where('namespace', $namespace)
            ->where('name', $name);

        if ($version !== null) {
            [$major, $minor, $patch] = $version;
            $query->where('major', $major)->where('minor', $minor)->where('patch', $patch);
        }

        $package = $query->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')->firstOrFail();
        $attachment = $package->codeAttachment()->firstOrFail();

        $disk = Storage::disk('public');
        $script_path = join_paths($attachment->path, 'index.global.js');

        // A local instance that pulled the package without its files reads the script from live.
        $script = $disk->exists($script_path)
            ? $disk->get($script_path)
            : AppHelper::resolve(LiveSyncService::class)->liveFile(ltrim($script_path, '/'))?->body();
        if ($script === null) {
            abort(404, "'$namespace/$name' has no IIFE build (index.global.js) — see \"Assets & Build: Dual Build Output\".");
        }

        // The script is served from this route, not from its dist/ folder, so it can't find its own
        // assets relative to its URL. Record the real content root first; resolve_asset_url() reads it.
        $content_root = rtrim((string) AppHelper::resolve(AttachmentService::class)->getBaseUrl($attachment), '/').'/';
        $json_flags = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;
        $register_root = sprintf(
            "((window.tobidot ??= {}).content_roots ??= {})[%s] = %s;\n",
            json_encode("$package->namespace/$package->name", $json_flags),
            json_encode($content_root, $json_flags),
        );

        return response($register_root.$script, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => $version !== null ? 'public, max-age=31536000, immutable' : 'public, max-age=60',
        ]);
    }

    /**
     * @return array{0: string, 1: array{0: int, 1: int, 2: int}|null}
     */
    protected function splitVersionSuffix(string $name): array
    {
        if (preg_match('/^(.+)@(\d+)\.(\d+)\.(\d+)$/', $name, $matches)) {
            return [$matches[1], [(int) $matches[2], (int) $matches[3], (int) $matches[4]]];
        }

        return [$name, null];
    }

    /**
     * Walks a package's declared dependencies (recursively, deduped) and returns them in load
     * order — dependencies before the package that needs them — for rendering as a zero-script
     * embed's `<script>` tag chain. `$extra_names` are additional sibling packages (same
     * namespace) to append after the dependency chain but before `$package` itself.
     *
     * @return TcePackage[]
     */
    protected function resolveEmbedChain(TcePackage $package, array $extra_names = []): array
    {
        $ordered = [];
        $seen = [];

        $visit = function (TcePackage $node) use (&$visit, &$ordered, &$seen) {
            if (isset($seen[$node->id])) {
                return;
            }
            $seen[$node->id] = true;
            foreach ($node->dependencies as $dependency) {
                $visit($dependency);
            }
            $ordered[] = $node;
        };

        foreach ($package->dependencies as $dependency) {
            $visit($dependency);
        }

        foreach ($extra_names as $extra_name) {
            $extra = TcePackage::query()
                ->where('namespace', $package->namespace)
                ->where('name', $extra_name)
                ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
                ->first();
            if ($extra) {
                $visit($extra);
            }
        }

        $visit($package);

        return $ordered;
    }
}
