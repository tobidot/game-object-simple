<?php

namespace App\Console\Commands;

use App\Helpers\AppHelper;
use App\Services\Models\TcePackageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

/**
 * Local dev tooling: bulk-imports the v3 TCE packages built in the separate
 * `tce-elements-project/v3` workspace, directly through TcePackageService — no HTTP, no auth,
 * trusted because it's a console command run by whoever has shell access to this app.
 *
 * Every package directory carries a `tobidot-project.json` (see "The tobidot-project.json
 * Manifest"); namespace, name, kind, size and dependencies are read from it. Packages are
 * uploaded dependencies-first, in the order the manifests imply; a dependency that is in
 * neither the import folder nor the registry fails the import loudly.
 *
 * That workspace is a sibling directory on the host, outside this repo, and the `app` container
 * only mounts this repo (`.:/var/www/html` in docker-compose.yml) — it cannot see the sibling
 * directory. The default `--path` therefore points at `storage/app/v3-import/packages`, a copy
 * of each package directory (manifest, icon and `dist/`, or `package.json` + `src/` for the
 * SDK packages) placed inside this repo (gitignored) so the container can reach it.
 */
class ImportV3PackagesCommand extends Command
{
    protected $signature = 'tce:import-v3-packages
                            {--path= : Path to the v3 packages directory; defaults to storage/app/v3-import/packages}
                            {--only=* : Import only these package names (dependencies must already be in the registry)}';

    protected $description = 'Import the built v3 TCE packages into tce_packages, in dependency order';

    public function handle(): int
    {
        $base_path = $this->option('path') ?: storage_path('app/v3-import/packages');

        if (! is_dir($base_path)) {
            $this->error("Packages directory not found: $base_path");

            return self::FAILURE;
        }

        $manifests = $this->readManifests($base_path);
        $only = (array) $this->option('only');
        if ($only !== []) {
            $manifests = array_intersect_key($manifests, array_flip($only));
        }

        /** @var TcePackageService $service */
        $service = AppHelper::resolve(TcePackageService::class);
        $uploaded = [];

        foreach ($this->sortByDependencies($manifests) as $directory => $manifest) {
            $namespace = $manifest['namespace'];
            $name = $manifest['name'];
            $kind = $this->kindOf($manifest);
            $package_dir = "$base_path/$directory";

            $this->info("Uploading $namespace/$name ($kind) ...");

            $zip_path = $kind === 'sdk'
                ? $this->packSdk($package_dir, $name)
                : $this->packDist("$package_dir/dist", $name);
            $icon_path = "$package_dir/icon.svg";
            $icon_file = is_file($icon_path)
                ? new UploadedFile($icon_path, 'icon.svg', 'image/svg+xml', null, true)
                : null;

            $width = (int) ($manifest['resolution']['width'] ?? 0);
            $height = (int) ($manifest['resolution']['height'] ?? 0);

            try {
                $package = $service->upload(
                    $namespace,
                    $name,
                    new UploadedFile($zip_path, "$name.zip", 'application/zip', null, true),
                    null,
                    $kind,
                    $manifest['informal']['description'] ?? null,
                    $icon_file,
                    $width > 0 && $height > 0 ? $width : null,
                    $width > 0 && $height > 0 ? $height : null,
                    array_map(fn (array $dependency) => $dependency['identifier'], $manifest['dependencies'] ?? []),
                );

                $uploaded[] = "$namespace/$name";
                $this->line("  -> v{$package->major}.{$package->minor}.{$package->patch} (#$package->id)");
            } catch (RuntimeException|InvalidArgumentException $exception) {
                $this->error("  Failed: {$exception->getMessage()}");

                return self::FAILURE;
            } finally {
                @unlink($zip_path);
            }
        }

        $this->info('Done. Uploaded: '.implode(', ', $uploaded));

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, mixed>> manifest by directory name
     */
    protected function readManifests(string $base_path): array
    {
        $manifests = [];
        foreach (scandir($base_path) ?: [] as $directory) {
            $manifest_path = "$base_path/$directory/tobidot-project.json";
            if (str_starts_with($directory, '.') || ! is_file($manifest_path)) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($manifest_path), true);
            if (! is_array($manifest) || empty($manifest['namespace']) || empty($manifest['name'])) {
                $this->warn("Skipping '$directory' — tobidot-project.json must declare namespace and name");

                continue;
            }
            $manifests[$directory] = $manifest;
        }

        return $manifests;
    }

    /**
     * Dependencies first; packages whose dependencies are not part of this import keep their
     * relative (alphabetical) order and rely on the registry.
     *
     * @param  array<string, array<string, mixed>>  $manifests
     * @return array<string, array<string, mixed>>
     */
    protected function sortByDependencies(array $manifests): array
    {
        $by_identifier = [];
        foreach ($manifests as $directory => $manifest) {
            $by_identifier["{$manifest['namespace']}/{$manifest['name']}"] = $directory;
        }

        $ordered = [];
        $visiting = [];
        $visit = function (string $directory) use (&$visit, &$ordered, &$visiting, $manifests, $by_identifier): void {
            if (isset($ordered[$directory])) {
                return;
            }
            if (isset($visiting[$directory])) {
                throw new RuntimeException("Dependency cycle at '$directory'");
            }
            $visiting[$directory] = true;
            foreach ($manifests[$directory]['dependencies'] ?? [] as $dependency) {
                $identifier = $dependency['identifier'];
                $key = "{$identifier['namespace']}/{$identifier['name']}";
                if (isset($by_identifier[$key])) {
                    $visit($by_identifier[$key]);
                }
            }
            $ordered[$directory] = $manifests[$directory];
        };

        foreach (array_keys($manifests) as $directory) {
            $visit($directory);
        }

        return $ordered;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function kindOf(array $manifest): string
    {
        $categories = $manifest['informal']['categories'] ?? [];
        foreach (['sdk', 'library', 'element'] as $kind) {
            if (in_array($kind, $categories, true)) {
                return $kind;
            }
        }

        throw new RuntimeException("'{$manifest['name']}': informal.categories must contain element, library or sdk");
    }

    /**
     * Zips `$dist_dir`'s contents under a single top-level `dist/` folder, which
     * `AttachmentService::createFromUploadedZipFile`'s single-folder flattening unwraps.
     */
    protected function packDist(string $dist_dir, string $package_name): string
    {
        if (! is_dir($dist_dir)) {
            throw new RuntimeException("'$package_name' has no dist/ folder — build it first");
        }

        return $this->pack($package_name, function (ZipArchive $zip) use ($dist_dir) {
            $this->addTree($zip, $dist_dir, 'dist/');
        });
    }

    /**
     * An SDK package ships what npm needs: its `package.json` and TypeScript `src/`.
     */
    protected function packSdk(string $package_dir, string $package_name): string
    {
        if (! is_file("$package_dir/package.json") || ! is_dir("$package_dir/src")) {
            throw new RuntimeException("'$package_name' is an sdk package and needs package.json and src/");
        }

        return $this->pack($package_name, function (ZipArchive $zip) use ($package_dir) {
            $zip->addFile("$package_dir/package.json", 'package.json');
            $this->addTree($zip, "$package_dir/src", 'src/');
        });
    }

    protected function pack(string $package_name, callable $fill): string
    {
        $zip_path = tempnam(sys_get_temp_dir(), 'tce_import_').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create temp zip for '$package_name'");
        }
        $fill($zip);
        $zip->close();

        return $zip_path;
    }

    protected function addTree(ZipArchive $zip, string $directory, string $prefix): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }
            $relative = $prefix.ltrim(substr($file->getPathname(), strlen($directory)), '/');
            $zip->addFile($file->getPathname(), $relative);
        }
    }
}
