<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Everything a generated v3 project is rendered from — normalized, validated input plus the
 * registry facts (dependency versions, SDK packages) resolved for it. See "TCE Project
 * Generator" in the specification.
 */
readonly class TceGeneratorConfig implements JsonSerializable
{
    public const array KINDS = ['element', 'library'];

    public const array TEMPLATES = ['standard', 'canvas', 'minimal'];

    /** A valid custom-element name: lower-case, at least one hyphen. Libraries follow the same shape. */
    public const string NAME_PATTERN = '/^[a-z][a-z0-9]*(-[a-z0-9]+)+$/';

    public const string IDENTIFIER_PATTERN = '/^[0-9a-zA-Z\-_]+$/';

    /**
     * @param  array<int, array{namespace: string, name: string, kind: string, version: string}>  $dependencies  resolved registry packages this project declares
     * @param  array<int, array{name: string, npm_name: string, path: string, version: string}>  $sdk  SDK packages vendored into the project
     */
    public function __construct(
        public string $namespace,
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
        public array $sdk,
        public string $siteUrl,
        public string $iconFileName = 'icon.svg',
        public ?string $iconContent = null,
    ) {}

    /**
     * Builds a config from (already validated) request/CLI input.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, array{namespace: string, name: string, kind: string, version: string}>  $available_dependencies  keyed `namespace/name`
     * @param  array<int, array{name: string, npm_name: string, path: string, version: string, kinds: string[]}>  $sdk_packages
     *
     * @throws InvalidArgumentException for a name/namespace/dependency that cannot be used
     */
    public static function fromArray(
        array $options,
        array $available_dependencies,
        array $sdk_packages,
        string $default_namespace,
        string $site_url,
    ): self {
        $namespace = trim((string) ($options['namespace'] ?? '')) ?: $default_namespace;
        if (! preg_match(self::IDENTIFIER_PATTERN, $namespace)) {
            throw new InvalidArgumentException("'$namespace' is not a valid namespace (letters, digits, '-' and '_' only).");
        }

        $name = strtolower(trim((string) ($options['name'] ?? '')));
        if (! preg_match(self::NAME_PATTERN, $name)) {
            throw new InvalidArgumentException("'$name' is not a valid package name: lower-case, digits and hyphens, with at least one hyphen (e.g. tce-my-element, dll-my-library).");
        }

        $raw_kind = (string) ($options['kind'] ?? 'element');
        if (! in_array($raw_kind, self::KINDS, true)) {
            throw new InvalidArgumentException("Unknown kind '$raw_kind'.");
        }
        $kind = $raw_kind;

        $raw_template = (string) ($options['template'] ?? 'standard');
        if (! in_array($raw_template, self::TEMPLATES, true)) {
            throw new InvalidArgumentException("Unknown template '$raw_template'.");
        }
        $template = $kind === 'library' ? 'standard' : $raw_template;

        $version = trim((string) ($options['version'] ?? ''));
        if ($version === '') {
            $version = '0.1.0';
        }
        if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new InvalidArgumentException("'$version' is not a major.minor.patch version.");
        }

        $title = trim((string) ($options['title'] ?? '')) ?: Str::headline($name);
        $description = trim((string) ($options['description'] ?? '')) ?: "$title, a Tobidot Custom Element.";
        $width = $kind === 'library' ? 0 : max(50, (int) ($options['width'] ?? 400));
        $height = $kind === 'library' ? 0 : max(50, (int) ($options['height'] ?? 300));

        $dependencies = [];
        foreach ((array) ($options['dependencies'] ?? []) as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            $key = str_contains($raw, '/') ? $raw : "$namespace/$raw";
            if (! isset($available_dependencies[$key])) {
                throw new InvalidArgumentException("Dependency '$key' is not published in this registry.");
            }
            $dependencies[$key] = $available_dependencies[$key];
        }
        unset($dependencies["$namespace/$name"]);

        $icon_file_name = 'icon.svg';
        $icon_content = null;
        $icon = $options['icon'] ?? null;
        if ($icon instanceof UploadedFile) {
            $extension = strtolower($icon->getClientOriginalExtension() ?: 'svg');
            $icon_file_name = "icon.$extension";
            $icon_content = (string) $icon->get();
        } elseif (is_string($icon) && $icon !== '') {
            $icon_content = $icon;
        }

        return new self(
            namespace: $namespace,
            name: $name,
            className: Str::studly($name),
            title: $title,
            description: $description,
            version: $version,
            kind: $kind,
            width: $width,
            height: $height,
            template: $template,
            dependencies: array_values($dependencies),
            sdk: array_values(array_map(
                fn (array $sdk) => ['name' => $sdk['name'], 'npm_name' => $sdk['npm_name'], 'path' => $sdk['path'], 'version' => $sdk['version']],
                array_filter($sdk_packages, fn (array $sdk) => in_array($kind, $sdk['kinds'], true)),
            )),
            siteUrl: rtrim($site_url, '/'),
            iconFileName: $icon_file_name,
            iconContent: $icon_content,
        );
    }

    /**
     * @return array{major: int, minor: int, patch: int}
     */
    public function versionParts(): array
    {
        [$major, $minor, $patch] = array_map('intval', explode('.', $this->version));

        return ['major' => $major, 'minor' => $minor, 'patch' => $patch];
    }

    /** The package instance variable in `src/index.ts`, e.g. `tce_my_element_package`. */
    public function packageVariable(): string
    {
        return str_replace('-', '_', $this->name).'_package';
    }

    /**
     * @return array<int, array{namespace: string, name: string, kind: string, version: string}>
     */
    public function libraryDependencies(): array
    {
        return array_values(array_filter($this->dependencies, fn (array $dependency) => $dependency['kind'] === 'library'));
    }

    public function getZipFilename(): string
    {
        return "$this->name-$this->version.zip";
    }

    /** Two upper-case letters for the generated default icon. */
    public function getInitials(): string
    {
        $words = explode('-', $this->name);
        $meaningful = array_values(array_filter($words, fn (string $word) => ! in_array($word, ['tce', 'dll'], true)));
        $letters = implode('', array_map(fn (string $word) => substr($word, 0, 1), $meaningful ?: $words));

        return strtoupper(substr($letters, 0, 2));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'namespace' => $this->namespace,
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
            'sdk' => $this->sdk,
            'icon' => $this->iconFileName,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
