<?php

namespace App\Services;

use App\DTO\TceGeneratorConfig;
use App\Models\TcePackage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

use function Illuminate\Filesystem\join_paths;

/**
 * Renders a complete, building v3 project — see "TCE Project Generator" in the specification.
 * The Web UI, the CLI and the REST API are thin wrappers around `generateZip()` /
 * `generateProjectFiles()`. Everything registry-related (which packages can be declared as
 * dependencies, which SDK packages get vendored) comes from `tce_packages`, never from the
 * legacy table and never from constants.
 */
class TceGeneratorService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $sdk_packages = null;

    /**
     * The registry packages a generated project may declare as dependencies: the latest
     * version of every element and library with code, keyed `namespace/name`.
     *
     * @return array<string, array{namespace: string, name: string, kind: string, version: string, description: string}>
     */
    public function availableDependencies(): array
    {
        return TcePackage::query()
            ->whereIn('kind', ['element', 'library'])
            ->whereHas('codeAttachment')
            ->orderBy('namespace')->orderBy('name')
            ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
            ->get()
            ->unique(fn (TcePackage $package) => "$package->namespace/$package->name")
            ->mapWithKeys(fn (TcePackage $package) => ["$package->namespace/$package->name" => [
                'namespace' => $package->namespace,
                'name' => $package->name,
                'kind' => $package->kind,
                'version' => "$package->major.$package->minor.$package->patch",
                'description' => (string) $package->description,
            ]])
            ->all();
    }

    /**
     * The SDK packages (`config('tce.sdk_packages')`) as published to this registry, with the
     * files that get vendored into a project.
     *
     * @return array<int, array{name: string, npm_name: string, path: string, version: string, kinds: string[], files: array<string, string>}>
     *
     * @throws RuntimeException when an SDK package was never published
     */
    public function sdkPackages(): array
    {
        if ($this->sdk_packages !== null) {
            return $this->sdk_packages;
        }

        $namespace = (string) config('tce.default_namespace');
        $disk = Storage::disk('public');
        $sdk = [];

        foreach ((array) config('tce.sdk_packages') as $name => $settings) {
            $package = TcePackage::query()
                ->where('namespace', $namespace)
                ->where('name', $name)
                ->where('kind', 'sdk')
                ->whereHas('codeAttachment')
                ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
                ->first();
            if ($package === null) {
                throw new RuntimeException(
                    "The SDK package '$namespace/$name' is not published to this registry. "
                    .'Publish it from the v3 workspace (publish.py) before generating projects.'
                );
            }

            $attachment = $package->codeAttachment()->firstOrFail();
            $root = trim($attachment->path, '/');
            $files = [];
            foreach ($disk->allFiles($root) as $file) {
                $relative = ltrim(Str::after(ltrim($file, '/'), $root), '/');
                if ($relative === $attachment->file_name) {
                    continue; // the uploaded zip itself
                }
                $files[$relative] = (string) $disk->get($file);
            }
            if (! isset($files['package.json'])) {
                throw new RuntimeException("The SDK package '$namespace/$name' has no package.json — it must be published as package.json plus src/.");
            }

            $sdk[] = [
                'name' => $name,
                'npm_name' => $settings['npm_name'],
                'path' => 'vendor/'.Str::slug(str_replace(['@', '/'], ['', '-'], $settings['npm_name'])),
                'version' => "$package->major.$package->minor.$package->patch",
                'kinds' => $settings['kinds'],
                'files' => $files,
            ];
        }

        return $this->sdk_packages = $sdk;
    }

    public function isSdkPublished(): bool
    {
        try {
            $this->sdkPackages();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>|TceGeneratorConfig  $options
     *
     * @throws \InvalidArgumentException for unusable input, RuntimeException when the SDK is missing
     */
    public function prepareConfig(array|TceGeneratorConfig $options): TceGeneratorConfig
    {
        if ($options instanceof TceGeneratorConfig) {
            return $options;
        }

        return TceGeneratorConfig::fromArray(
            $options,
            $this->availableDependencies(),
            $this->sdkPackages(),
            (string) config('tce.default_namespace'),
            url('/'),
        );
    }

    /**
     * Generate a ZIP archive containing the complete project.
     *
     * @param  array<string, mixed>|TceGeneratorConfig  $options
     * @return string Path to the generated temporary ZIP file
     *
     * @throws RuntimeException
     */
    public function generateZip(array|TceGeneratorConfig $options): string
    {
        $config = $this->prepareConfig($options);
        $files = $this->generateProjectFiles($config);

        $tempFile = tempnam(sys_get_temp_dir(), 'tce_gen_').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to create ZIP archive for TCE project.');
        }

        foreach ($files as $filePath => $content) {
            $zip->addFromString("$config->name/$filePath", $content);
        }

        $zip->close();

        return $tempFile;
    }

    /**
     * Every file of the project, relative path => content. The SDK packages are vendored under
     * `vendor/` and referenced from `package.json` as `file:` dependencies, so the project
     * builds offline, pinned to the SDK versions it was generated against.
     *
     * @param  TceGeneratorConfig|array<string, mixed>  $config
     * @return array<string, string>
     */
    public function generateProjectFiles(TceGeneratorConfig|array $config): array
    {
        $config = $this->prepareConfig($config);

        $files = [
            'package.json' => $this->renderTemplate('package.json.php', $config),
            'tsconfig.json' => $this->renderTemplate('tsconfig.json.php', $config),
            'vite.config.ts' => $this->renderTemplate('vite.config.ts.php', $config),
            'eslint.config.js' => $this->renderTemplate('eslint.config.js.php', $config),
            'tobidot-project.json' => $this->renderTemplate('tobidot-project.json.php', $config),
            'index.html' => $this->renderTemplate('index.html.php', $config),
            'README.md' => $this->renderTemplate('README.md.php', $config),
            '.gitignore' => $this->renderTemplate('gitignore.php', $config),
            'scripts/pack.mjs' => $this->renderTemplate('scripts/pack.mjs.php', $config),
            'src/vite-env.d.ts' => $this->renderTemplate('src/vite-env.d.ts.php', $config),
            $config->iconFileName => $config->iconContent ?? $this->generateDefaultIconSvg($config),
        ];

        if ($config->kind === 'library') {
            $files['src/index.ts'] = $this->renderTemplate('src/library/index.ts.php', $config);
            $files["src/$config->className.ts"] = $this->renderTemplate('src/library/Library.ts.php', $config);
        } else {
            $files['src/index.ts'] = $this->renderTemplate('src/element/index.ts.php', $config);
            $files['src/applet.scss'] = $this->renderTemplate("src/element/applet.$config->template.scss.php", $config);
            if ($config->template === 'minimal') {
                $files["src/$config->className.ts"] = $this->renderTemplate('src/element/minimal/Element.ts.php', $config);
            } else {
                foreach (['Element' => '', 'Settings' => 'Settings', 'References' => 'References', 'Properties' => 'Properties', 'Renderer' => 'Renderer', 'Logic' => 'Logic'] as $template => $suffix) {
                    $files["src/$config->className$suffix.ts"] = $this->renderTemplate("src/element/$config->template/$template.ts.php", $config);
                }
            }
        }

        foreach ($config->libraryDependencies() as $dependency) {
            $files["src/contracts/{$dependency['name']}.contract.ts"] = $this->renderTemplate(
                'src/contract.ts.php',
                $config,
                ['dependency' => $dependency],
            );
        }

        foreach ($this->sdkPackages() as $sdk) {
            if (! in_array($config->kind, $sdk['kinds'], true)) {
                continue;
            }
            foreach ($sdk['files'] as $relative => $content) {
                $files[join_paths($sdk['path'], $relative)] = $content;
            }
        }

        return $files;
    }

    public function generateDefaultIconSvg(TceGeneratorConfig $config): string
    {
        return $this->renderTemplate('icon.svg.php', $config);
    }

    public function getTemplatesPath(?string $path = null): string
    {
        $base = resource_path('tce-templates');

        return $path ? "$base/$path" : $base;
    }

    /**
     * Renders a template from resources/tce-templates. Templates see `$config` and any `$extra`
     * variables.
     *
     * @param  array<string, mixed>  $extra
     */
    public function renderTemplate(string $templatePath, TceGeneratorConfig $config, array $extra = []): string
    {
        $fullPath = $this->getTemplatesPath($templatePath);

        if (! file_exists($fullPath)) {
            throw new RuntimeException("TCE template file not found: $fullPath");
        }

        return (static function (string $__path, array $__variables): string {
            extract($__variables);
            ob_start();
            require $__path;

            return (string) ob_get_clean();
        })($fullPath, ['config' => $config, ...$extra]);
    }
}
