<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Http\Requests\UploadTcePackageRequest;
use App\Http\Resources\TcePackageResource;
use App\Models\TcePackage;
use App\Services\Models\AttachmentService;
use App\Services\Models\TcePackageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use function Illuminate\Filesystem\join_paths;

/**
 * Serves v3 packages — see "Publishing & Discovery: Data Model". Never reads from the legacy
 * `tobidot_elements` table; that table is kept read-only, for porting reference only.
 */
class TcePackageController extends Controller
{
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
     * While elements are still being ported, a name with no v3 package yet falls back to the
     * legacy element of that name so existing links keep working.
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
            return AppHelper::resolve(TobidotElementController::class)->play($name);
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
     * page header/nav, the way the previous desktop.ts-based homepage was.
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
            ->with('dependencies')
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
    public function upload(UploadTcePackageRequest $request): JsonResponse
    {
        try {
            $package = AppHelper::resolve(TcePackageService::class)
                ->upload(
                    $request->namespace,
                    $request->name,
                    $request->zip,
                    $request->version ?? null,
                    $request->kind ?? null,
                    $request->description ?? null,
                    $request->file('icon'),
                    $request->has('width') ? (int) $request->width : null,
                    $request->has('height') ? (int) $request->height : null,
                    $request->dependencies(),
                );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['version' => [$exception->getMessage()]]);
        }

        return response()->json([
            'message' => 'Tce package uploaded successfully',
            'package' => new TcePackageResource($package->fresh('dependencies')),
        ]);
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

        if (! $disk->exists($script_path)) {
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

        return response($register_root.$disk->get($script_path), 200, [
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
