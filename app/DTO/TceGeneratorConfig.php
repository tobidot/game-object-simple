<?php

namespace App\DTO;

use App\Http\Requests\GenerateTceRequest;
use App\Services\TceGeneratorService;
use ArrayAccess;
use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use JsonSerializable;

readonly class TceGeneratorConfig implements Arrayable, ArrayAccess, JsonSerializable
{
    /**
     * @param  array<string, string>  $dependencies
     * @param  array<int, array<string, mixed>>  $tceDependencies
     */
    public function __construct(
        public string $name,
        public string $className,
        public string $title,
        public string $description,
        public string $version,
        public string $kind,
        public int $width,
        public int $height,
        public string $template,
        public array $dependencies,
        public array $tceDependencies,
        public string $iconPath = 'assets/icon.svg',
        public ?UploadedFile $iconFile = null,
        public ?string $iconSvg = null,
    ) {}

    /**
     * Instantiate DTO from raw input options array.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $availableElements
     */
    public static function fromArray(array $options, array $availableElements = []): self
    {
        $rawName = trim((string) ($options['name'] ?? $options['element-name'] ?? 'my-custom-element'));
        $slug = Str::slug($rawName);

        // Ensure the element tag name has a hyphen (valid custom element name)
        $tagName = str_contains($slug, '-') ? $slug : "tce-$slug";
        if (! str_starts_with($tagName, 'tce-') && ($options['prefix_tce'] ?? false)) {
            $tagName = "tce-$tagName";
        }

        $className = Str::studly(str_replace('tce-', '', $tagName));
        if (empty($className)) {
            $className = 'CustomElement';
        }

        $title = ! empty($options['title']) ? trim((string) $options['title']) : Str::headline($tagName);
        $description = ! empty($options['description']) ? trim((string) $options['description']) : "Tobidot Custom Element for $title.";
        $version = ! empty($options['version']) && preg_match('/^\d+\.\d+\.\d+$/', (string) $options['version'])
            ? (string) $options['version']
            : '1.0.0';
        $rawKind = (string) ($options['kind'] ?? 'element');
        $kind = in_array($rawKind, ['element', 'library'], true) ? $rawKind : 'element';
        $width = max(50, (int) ($options['width'] ?? 400));
        $height = max(50, (int) ($options['height'] ?? 300));
        $rawTemplate = (string) ($options['template'] ?? 'standard');
        $template = in_array($rawTemplate, ['standard', 'canvas', 'minimal'], true) ? $rawTemplate : 'standard';

        $dependencies = ['@game.object/tce-common' => '^1.0.6'];
        $tceDependencies = [];

        // 1. Process tce_dependencies
        $rawTceDeps = $options['tce_dependencies'] ?? [];
        if (is_string($rawTceDeps)) {
            $rawTceDeps = preg_split('/[\r\n,]+/', $rawTceDeps);
        }

        // 2. Process dependencies array
        $rawDeps = $options['dependencies'] ?? [];
        if (is_string($rawDeps)) {
            $rawDeps = preg_split('/[\r\n,]+/', $rawDeps);
        }

        $allDepInputs = array_merge((array) $rawTceDeps, (array) $rawDeps);

        foreach ($allDepInputs as $dep) {
            if (empty($dep)) {
                continue;
            }
            if (is_array($dep)) {
                $depName = $dep['name'] ?? '';
                $depVer = $dep['version'] ?? ($availableElements[$depName]['version'] ?? '1.0.0');
            } elseif (is_string($dep)) {
                $dep = trim($dep);
                if (empty($dep)) {
                    continue;
                }
                // Check if it's JSON
                if (str_starts_with($dep, '{') && str_ends_with($dep, '}')) {
                    $decoded = json_decode($dep, true);
                    if ($decoded && ! empty($decoded['name'])) {
                        $depName = $decoded['name'];
                        $depVer = $decoded['version'] ?? ($availableElements[$depName]['version'] ?? '1.0.0');
                    } else {
                        continue;
                    }
                } elseif (str_contains($dep, ':')) {
                    [$depName, $depVer] = explode(':', $dep, 2);
                } elseif (str_contains($dep, '@') && ! str_starts_with($dep, '@')) {
                    [$depName, $depVer] = explode('@', $dep, 2);
                } else {
                    $depName = $dep;
                    if (isset(TceGeneratorService::AVAILABLE_DEPENDENCIES[$depName])) {
                        $depVer = TceGeneratorService::AVAILABLE_DEPENDENCIES[$depName];
                    } elseif (isset($availableElements[$depName])) {
                        $depVer = "^{$availableElements[$depName]['version']}";
                    } else {
                        $depVer = '^1.0.0';
                    }
                }
            } else {
                continue;
            }

            $depName = trim((string) $depName);
            $depVer = trim((string) $depVer);

            if (empty($depName)) {
                continue;
            }

            $dependencies[$depName] = $depVer;

            // Structure for tobidot-project.json
            $tceDepEntry = [
                'identifier' => [
                    'namespace' => 'tobidot',
                    'name' => $depName,
                ],
            ];
            if (preg_match('/(\d+)(?:\.(\d+))?(?:\.(\d+))?/', $depVer, $m)) {
                $tceDepEntry['version'] = array_filter([
                    'major' => isset($m[1]) ? (int) $m[1] : 1,
                    'minor' => isset($m[2]) ? (int) $m[2] : 0,
                    'patch' => isset($m[3]) ? (int) $m[3] : 0,
                ], fn ($v) => $v !== null);
            }
            $tceDependencies[$depName] = $tceDepEntry;
        }

        if (! empty($options['custom_dependencies']) && is_string($options['custom_dependencies'])) {
            $customLines = preg_split('/[\r\n,]+/', $options['custom_dependencies']);
            foreach ($customLines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                if (str_contains($line, ':')) {
                    [$pkg, $ver] = explode(':', $line, 2);
                    $dependencies[trim($pkg)] = trim($ver);
                } elseif (str_contains($line, '@') && ! str_starts_with($line, '@')) {
                    [$pkg, $ver] = explode('@', $line, 2);
                    $dependencies[trim($pkg)] = trim($ver);
                } else {
                    $dependencies[$line] = 'latest';
                }
            }
        }

        $iconFile = (isset($options['icon']) && $options['icon'] instanceof UploadedFile) ? $options['icon'] : null;
        $iconSvg = ! empty($options['icon_svg']) && is_string($options['icon_svg']) ? $options['icon_svg'] : null;

        $iconPath = 'assets/icon.svg';
        if ($iconFile) {
            $extension = $iconFile->getClientOriginalExtension() ?: 'png';
            $iconPath = "assets/icon.$extension";
        }

        return new self(
            name: $tagName,
            className: $className,
            title: $title,
            description: $description,
            version: $version,
            kind: $kind,
            width: $width,
            height: $height,
            template: $template,
            dependencies: $dependencies,
            tceDependencies: array_values($tceDependencies),
            iconPath: $iconPath,
            iconFile: $iconFile,
            iconSvg: $iconSvg,
        );
    }

    /**
     * Instantiate DTO from a GenerateTceRequest instance.
     *
     * @param  array<string, mixed>  $availableElements
     */
    public static function fromRequest(GenerateTceRequest $request, array $availableElements = []): self
    {
        return self::fromArray($request->all(), $availableElements);
    }

    /**
     * Convert DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'className' => $this->className,
            'title' => $this->title,
            'description' => $this->description,
            'version' => $this->version,
            'kind' => $this->kind,
            'width' => $this->width,
            'height' => $this->height,
            'template' => $this->template,
            'dependencies' => $this->dependencies,
            'tceDependencies' => $this->tceDependencies,
            'icon_path' => $this->iconPath,
            'iconPath' => $this->iconPath,
        ];
    }

    /**
     * Get the download package zip filename.
     */
    public function getZipFilename(): string
    {
        return $this->name.'-template.zip';
    }

    /**
     * Get two-letter uppercase class initials for icon rendering.
     */
    public function getInitials(): string
    {
        return strtoupper(substr($this->className, 0, 2));
    }

    /**
     * Get MIME type for the configured icon.
     */
    public function getIconMimeType(): string
    {
        if (str_ends_with($this->iconPath, '.svg')) {
            return 'image/svg+xml';
        }
        if (str_ends_with($this->iconPath, '.png')) {
            return 'image/png';
        }

        return 'image/x-icon';
    }

    /**
     * ArrayAccess implementation: offsetExists.
     */
    public function offsetExists(mixed $offset): bool
    {
        $normalized = $this->normalizeKey($offset);

        return property_exists($this, $normalized);
    }

    /**
     * ArrayAccess implementation: offsetGet.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $normalized = $this->normalizeKey($offset);

        return $this->{$normalized} ?? null;
    }

    /**
     * ArrayAccess implementation: offsetSet.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('TceGeneratorConfig DTO is immutable.');
    }

    /**
     * ArrayAccess implementation: offsetUnset.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('TceGeneratorConfig DTO is immutable.');
    }

    /**
     * JsonSerializable implementation.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Normalize array offset key to property name.
     */
    private function normalizeKey(mixed $offset): string
    {
        return match ($offset) {
            'icon_path', 'icon-path' => 'iconPath',
            'element-name' => 'name',
            'class-name' => 'className',
            'tce-dependencies' => 'tceDependencies',
            default => (string) $offset,
        };
    }
}
