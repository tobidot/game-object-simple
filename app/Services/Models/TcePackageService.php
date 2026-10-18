<?php

namespace App\Services\Models;

use App\Helpers\AppHelper;
use App\Models\TcePackage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The one publishing path for v3 packages — the REST upload, the MCP tool and the import
 * command all end up here, so the rules of "Publishing & Discovery" are implemented once.
 */
class TcePackageService
{
    public const array KINDS = ['element', 'library', 'sdk'];

    /** A valid custom-element name: lower-case, at least one hyphen ("Naming & Identifiers"). */
    public const string NAME_PATTERN = '/^[a-z][a-z0-9]*(-[a-z0-9]+)+$/';

    /**
     * Publishes a new version of `(namespace, name)`.
     *
     * Everything optional carries over from the previous version when omitted (`null`): kind,
     * description, icon, width/height and the dependency list. Pass `[]` as `$dependencies` to
     * clear the list. Dependencies are declared by identifier and resolved to their latest
     * uploaded version; a dependency that was never uploaded rejects the whole upload, so a
     * published version can never miss part of its dependency list.
     *
     * @param  array<int, array{namespace: string, name: string}>|null  $dependencies
     *
     * @throws InvalidArgumentException on a bad version or a missing dependency
     * @throws \Throwable
     */
    public function upload(
        string $namespace,
        string $name,
        UploadedFile $zip,
        ?string $version = null,
        ?string $kind = null,
        ?string $description = null,
        ?UploadedFile $icon = null,
        ?int $width = null,
        ?int $height = null,
        ?array $dependencies = null,
    ): TcePackage {
        if ($kind !== null && ! in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException("Unknown package kind '$kind'");
        }

        // Version resolution is keyed on (namespace, name) — two different publishers may use
        // the same name without colliding. See "Publishing & Discovery: Versioning on Upload".
        $last_package = $this->latest($namespace, $name);

        // A runtime package's name is its tag name, so it must be a valid custom-element name —
        // see "Naming & Identifiers: Package Names". SDK packages are npm-only and exempt.
        $resolved_kind = $kind ?? ($last_package?->kind ?? 'element');
        if ($resolved_kind !== 'sdk' && ! preg_match(self::NAME_PATTERN, $name)) {
            throw new InvalidArgumentException("'$name' is not a valid package name: lower-case letters, digits and hyphens, with at least one hyphen.");
        }

        [$major, $minor, $patch] = $this->determineNextVersion($last_package, $version);

        $resolved_dependencies = $dependencies === null
            ? ($last_package?->dependencies->all() ?? [])
            : $this->resolveDependencies($dependencies);

        return DB::transaction(function () use (
            $namespace,
            $name,
            $zip,
            $major,
            $minor,
            $patch,
            $kind,
            $description,
            $last_package,
            $icon,
            $width,
            $height,
            $resolved_dependencies,
        ) {
            $attachmentService = AppHelper::resolve(AttachmentService::class);
            $attachment = $attachmentService->createFromUploadedZipFile(
                $zip,
                'tce-packages',
                "$namespace-$name",
            );

            $iconAttachment = null;
            if ($icon) {
                $iconAttachment = $attachmentService->createFromUploadedSingleFile(
                    $icon,
                    'media',
                    "$namespace-$name",
                );
            }

            $package = new TcePackage;
            $package->namespace = $namespace;
            $package->name = $name;
            $package->major = $major;
            $package->minor = $minor;
            $package->patch = $patch;
            $package->kind = $kind ?? ($last_package?->kind ?? 'element');
            $package->description = $description ?? ($last_package?->description ?? null);
            $package->width = $width ?? ($last_package?->width ?? 200);
            $package->height = $height ?? ($last_package?->height ?? 200);
            $package->extra = $last_package?->extra ?? null;

            $package->save();

            $package->attachments()->attach($attachment->id, ['relation' => 'code']);

            if ($iconAttachment) {
                $package->attachments()->attach($iconAttachment->id, ['relation' => 'icon']);
            } elseif ($last_package) {
                $lastIconAttachment = $last_package->iconAttachment()->first();
                if ($lastIconAttachment) {
                    $package->attachments()->attach($lastIconAttachment->id, ['relation' => 'icon']);
                }
            }

            $package->dependencies()->sync(
                collect($resolved_dependencies)->mapWithKeys(fn (TcePackage $dependency) => [$dependency->id => []])->all()
            );

            return $package;
        });
    }

    /**
     * The latest uploaded version of `(namespace, name)`, if any.
     */
    public function latest(string $namespace, string $name): ?TcePackage
    {
        return TcePackage::query()
            ->where('namespace', $namespace)
            ->where('name', $name)
            ->orderByDesc('major')
            ->orderByDesc('minor')
            ->orderByDesc('patch')
            ->first();
    }

    /**
     * Resolves declared dependencies — by identifier — to their latest uploaded version.
     *
     * @param  array<int, array{namespace: string, name: string}>  $dependencies
     * @return TcePackage[]
     *
     * @throws InvalidArgumentException naming every dependency that was never uploaded
     */
    public function resolveDependencies(array $dependencies): array
    {
        $resolved = [];
        $missing = [];
        foreach ($dependencies as $dependency) {
            $package = $this->latest($dependency['namespace'], $dependency['name']);
            if ($package === null) {
                $missing[] = "{$dependency['namespace']}/{$dependency['name']}";

                continue;
            }
            $resolved["$package->namespace/$package->name"] = $package;
        }

        if ($missing !== []) {
            throw new InvalidArgumentException('Dependencies not uploaded yet: '.implode(', ', $missing).'. Upload them first.');
        }

        return array_values($resolved);
    }

    /**
     * @return array{int, int, int}
     */
    protected function determineNextVersion(
        ?TcePackage $last_package = null,
        ?string $version = null,
    ): array {
        if (empty($version) && empty($last_package)) {
            return [0, 0, 1];
        }

        if (empty($version) && $last_package instanceof TcePackage) {
            return [
                $last_package->major,
                $last_package->minor,
                $last_package->patch + 1,
            ];
        }

        if (! preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
            throw new InvalidArgumentException('Invalid Semver Version');
        }
        $major = (int) $matches[1];
        $minor = (int) $matches[2];
        $patch = (int) $matches[3];

        if ($last_package instanceof TcePackage) {
            $last_version = "{$last_package->major}.{$last_package->minor}.{$last_package->patch}";
            if (version_compare($version, $last_version, '<=')) {
                throw new InvalidArgumentException("Version must be higher than last release '$last_version'");
            }
        }

        return [$major, $minor, $patch];
    }
}
