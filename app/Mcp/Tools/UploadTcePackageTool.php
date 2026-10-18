<?php

namespace App\Mcp\Tools;

use App\Helpers\AppHelper;
use App\Http\Resources\TcePackageResource;
use App\Services\Models\TcePackageService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use ZipArchive;

#[Name('upload_tce_package')]
#[Description(<<<'TEXT'
Publish a new version of a v3 TCE package to the package registry.
"zip_base64" is the package zipped, base64 encoded: the built dist/ folder for an element or library (one top-level folder is flattened),
or package.json plus the TypeScript src/ folder for an "sdk" package (the build-time SDK the project generator vendors into new projects).
Without "version" the patch number of the latest version is bumped; an explicit version must be higher than the latest.
Dependencies must already be uploaded, so publish packages in dependency order. Omitted icon/width/height/dependencies carry over from the previous version.
Returns the published package as the public registry (/api/public/tce-packages) lists it.
TEXT)]
class UploadTcePackageTool extends Tool
{
    public const MAX_BYTES = 20 * 1024 * 1024;

    public function handle(Request $request): Response|ResponseFactory
    {
        $identifier_rule = ['string', 'max:255', 'regex:/^[0-9a-zA-Z\-_]+$/'];
        $validated = $request->validate([
            'namespace' => ['nullable', ...$identifier_rule],
            'name' => ['required', ...$identifier_rule],
            'zip_base64' => ['required', 'string'],
            'icon_base64' => ['nullable', 'string'],
            'icon_file_name' => ['nullable', 'string', 'max:200', 'regex:/\.(svg|png|jpe?g|gif|webp)$/i'],
            'version' => ['nullable', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'kind' => ['nullable', 'in:'.implode(',', TcePackageService::KINDS)],
            'description' => ['nullable', 'string'],
            'width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'dependencies' => ['nullable', 'array'],
            'dependencies.*.namespace' => ['required', ...$identifier_rule],
            'dependencies.*.name' => ['required', ...$identifier_rule],
        ]);

        $namespace = $validated['namespace'] ?? config('tce.default_namespace');
        $dependencies = array_key_exists('dependencies', $validated) && $validated['dependencies'] !== null
            ? array_map(
                fn (array $dependency) => ['namespace' => $dependency['namespace'], 'name' => $dependency['name']],
                $validated['dependencies'],
            )
            : null;

        $zip_bytes = $this->decode($validated['zip_base64']);
        if ($zip_bytes === null) {
            return Response::error('zip_base64 is not valid base64, empty, or larger than '.(self::MAX_BYTES / 1024 / 1024).' MB.');
        }
        $icon_bytes = null;
        if (isset($validated['icon_base64'])) {
            $icon_bytes = $this->decode($validated['icon_base64']);
            if ($icon_bytes === null) {
                return Response::error('icon_base64 is not valid base64, empty, or too large.');
            }
        }

        $zip_path = tempnam(sys_get_temp_dir(), 'mcp-tce-zip-');
        $icon_path = $icon_bytes !== null ? tempnam(sys_get_temp_dir(), 'mcp-tce-icon-') : null;
        try {
            file_put_contents($zip_path, $zip_bytes);
            $zip = new ZipArchive;
            if ($zip->open($zip_path, ZipArchive::RDONLY) !== true) {
                return Response::error('zip_base64 does not contain a valid zip archive.');
            }
            $zip->close();

            $icon_file = null;
            if ($icon_path !== null) {
                file_put_contents($icon_path, $icon_bytes);
                $icon_name = $validated['icon_file_name'] ?? 'icon.svg';
                $icon_file = new UploadedFile($icon_path, $icon_name, mime_content_type($icon_path) ?: null, null, true);
            }

            try {
                $package = AppHelper::resolve(TcePackageService::class)->upload(
                    $namespace,
                    $validated['name'],
                    new UploadedFile($zip_path, "{$validated['name']}.zip", 'application/zip', null, true),
                    $validated['version'] ?? null,
                    $validated['kind'] ?? null,
                    $validated['description'] ?? null,
                    $icon_file,
                    $validated['width'] ?? null,
                    $validated['height'] ?? null,
                    $dependencies,
                );
            } catch (\InvalidArgumentException $exception) {
                return Response::error($exception->getMessage());
            }
        } finally {
            @unlink($zip_path);
            if ($icon_path !== null) {
                @unlink($icon_path);
            }
        }

        return Response::structured([
            'package' => (new TcePackageResource($package->fresh('dependencies')))->resolve(),
        ]);
    }

    protected function decode(string $base64): ?string
    {
        $bytes = base64_decode(preg_replace('/^data:[^,]*,/', '', $base64), true);
        if ($bytes === false || $bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            return null;
        }

        return $bytes;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'namespace' => $schema->string()->description('Publisher namespace. Defaults to this site\'s own namespace.'),
            'name' => $schema->string()->description('Package name, e.g. "tce-notepad".')->required(),
            'zip_base64' => $schema->string()->description('Base64 encoded zip: dist/ for element/library, package.json + src/ for sdk.')->required(),
            'icon_base64' => $schema->string()->description('Optional base64 encoded icon (svg/png/jpg/gif/webp).'),
            'icon_file_name' => $schema->string()->description('Icon file name incl. extension. Defaults to "icon.svg".'),
            'version' => $schema->string()->description('Explicit semver "major.minor.patch". Defaults to bumping the latest patch.'),
            'kind' => $schema->string()->enum(TcePackageService::KINDS)->description('Defaults to the previous version\'s kind, or "element".'),
            'description' => $schema->string()->description('Shown in the package manager. Defaults to the previous version\'s description.'),
            'width' => $schema->integer()->min(1)->description('Default window width in px.'),
            'height' => $schema->integer()->min(1)->description('Default window height in px.'),
            'dependencies' => $schema->array()->items($schema->object([
                'namespace' => $schema->string()->required(),
                'name' => $schema->string()->required(),
            ]))->description('Packages this one needs, resolved to their latest uploaded version. Replaces the previous list; omit to keep it, pass [] to clear it.'),
        ];
    }
}
