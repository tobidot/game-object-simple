<?php

namespace App\Services;

use App\DTO\TceGeneratorConfig;
use App\Models\TobidotElement;
use Illuminate\Foundation\Application;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;
use ZipArchive;

class TceGeneratorService
{
    /**
     * Common TCE dependencies available for selection.
     */
    public const array AVAILABLE_DEPENDENCIES = [
        '@game.object/tce-common' => '^1.0.6',
        '@game.object/ts-game-toolbox' => '^5.1.6',
        'dll-tce-desktop' => '^1.0.0',
        'dll-tce-file-system' => '^1.0.0',
        'dll-tce-package-registry' => '^1.0.0',
    ];

    /**
     * Default / well-known TCE elements and libraries.
     */
    public const array DEFAULT_TCE_ELEMENTS = [
        '@game.object/tce-common' => [
            'name' => '@game.object/tce-common',
            'version' => '1.0.6',
            'kind' => 'library',
            'description' => 'Core base class and decorators for Tobidot Custom Elements',
            'recommended' => true,
        ],
        'dll-tce-desktop' => [
            'name' => 'dll-tce-desktop',
            'version' => '1.0.0',
            'kind' => 'library',
            'description' => 'Desktop shell and window manager services',
            'recommended' => true,
        ],
        'dll-tce-file-system' => [
            'name' => 'dll-tce-file-system',
            'version' => '1.0.0',
            'kind' => 'library',
            'description' => 'Virtual file system and asset loader',
            'recommended' => false,
        ],
        'dll-tce-package-registry' => [
            'name' => 'dll-tce-package-registry',
            'version' => '1.0.0',
            'kind' => 'library',
            'description' => 'Package discovery and dependency resolver provider',
            'recommended' => false,
        ],
        '@game.object/ts-game-toolbox' => [
            'name' => '@game.object/ts-game-toolbox',
            'version' => '5.1.6',
            'kind' => 'library',
            'description' => 'Game loop, vector math, input, and canvas utilities',
            'recommended' => false,
        ],
    ];

    /**
     * Retrieve all available TCE elements and libraries (from database and defaults).
     *
     * @return array<string, array{name: string, version: string, kind: string, description: string, recommended: bool}>
     */
    public function getAvailableTceElements(): array
    {
        $elements = self::DEFAULT_TCE_ELEMENTS;

        try {
            $dbElements = TobidotElement::query()
                ->orderBy('name')
                ->orderByDesc('major')
                ->orderByDesc('minor')
                ->orderByDesc('patch')
                ->get()
                ->groupBy('name');

            foreach ($dbElements as $name => $versions) {
                /** @var TobidotElement $latest */
                $latest = $versions->first();
                $elements[$name] = [
                    'name' => $latest->name,
                    'version' => "$latest->major.$latest->minor.$latest->patch",
                    'kind' => $latest->kind ?? 'element',
                    'description' => $latest->description ?? '',
                    'recommended' => isset(self::DEFAULT_TCE_ELEMENTS[$name]) ? self::DEFAULT_TCE_ELEMENTS[$name]['recommended'] : false,
                ];
            }
        } catch (Throwable) {
            // Graceful fallback to default elements if DB is not accessible
        }

        return $elements;
    }

    /**
     * Generate a ZIP archive containing a fully configured TCE project template.
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
            $zip->addFromString($filePath, $content);
        }

        // Handle uploaded icon or add generated default icon
        if ($config->iconFile instanceof UploadedFile) {
            $extension = $config->iconFile->getClientOriginalExtension() ?: 'png';
            $zip->addFile($config->iconFile->getRealPath(), "assets/icon.$extension");
        } elseif (! empty($config->iconSvg)) {
            $zip->addFromString('assets/icon.svg', $config->iconSvg);
        } else {
            $zip->addFromString('assets/icon.svg', $this->generateDefaultIconSvg($config));
        }

        $zip->close();

        return $tempFile;
    }

    /**
     * Prepare normalized configuration DTO from input options.
     *
     * @param  array<string, mixed>|TceGeneratorConfig  $options
     */
    public function prepareConfig(array|TceGeneratorConfig $options): TceGeneratorConfig
    {
        if ($options instanceof TceGeneratorConfig) {
            return $options;
        }

        return TceGeneratorConfig::fromArray($options, $this->getAvailableTceElements());
    }

    /**
     * Get the base path for TCE templates.
     */
    public function getTemplatesPath(?string $path = null): string
    {
        $base = function_exists('resource_path') && app() instanceof Application
            ? resource_path('tce-templates')
            : dirname(__DIR__, 2).'/resources/tce-templates';

        return $path ? "$base/$path" : $base;
    }

    /**
     * Render a TCE template file from resources/tce-templates with the given configuration.
     *
     * @param  string  $templatePath  Relative path within resources/tce-templates/
     * @param  TceGeneratorConfig|array<string, mixed>  $config
     */
    public function renderTemplate(string $templatePath, TceGeneratorConfig|array $config): string
    {
        $configDto = $config instanceof TceGeneratorConfig ? $config : $this->prepareConfig($config);
        $fullPath = $this->getTemplatesPath($templatePath);

        if (! file_exists($fullPath)) {
            throw new RuntimeException("TCE template file not found: $fullPath");
        }

        return (static function (string $__path, TceGeneratorConfig $config): string {
            extract(['config' => $config]);
            ob_start();
            require $__path;

            return ob_get_clean();
        })($fullPath, $configDto);
    }

    /**
     * Generate all source, config, and build files for the project.
     *
     * @param  TceGeneratorConfig|array<string, mixed>  $config
     * @return array<string, string> Map of relative file paths to their content
     */
    public function generateProjectFiles(TceGeneratorConfig|array $config): array
    {
        $configDto = $config instanceof TceGeneratorConfig ? $config : $this->prepareConfig($config);

        $classTemplate = match ($configDto->template) {
            'canvas' => 'src/class.canvas.ts.php',
            'minimal' => 'src/class.minimal.ts.php',
            default => 'src/class.standard.ts.php',
        };

        $appletHtmlTemplate = match ($configDto->template) {
            'canvas' => 'src/applet.canvas.html.php',
            'minimal' => 'src/applet.minimal.html.php',
            default => 'src/applet.standard.html.php',
        };

        return [
            'package.json' => $this->renderTemplate('package.json.php', $configDto),
            'tobidot-project.json' => $this->renderTemplate('tobidot-project.json.php', $configDto),
            'tsconfig.json' => $this->renderTemplate('tsconfig.json.php', $configDto),
            'vite.config.ts' => $this->renderTemplate('vite.config.ts.php', $configDto),
            'index.html' => $this->renderTemplate('index.html.php', $configDto),
            '.gitignore' => $this->renderTemplate('gitignore.php', $configDto),
            'README.md' => $this->renderTemplate('README.md.php', $configDto),
            'Makefile' => $this->renderTemplate('Makefile.php', $configDto),
            'VERSION' => $configDto->version."\n",
            'src/index.ts' => $this->renderTemplate('src/index.ts.php', $configDto),
            'src/'.$configDto->className.'.ts' => $this->renderTemplate($classTemplate, $configDto),
            'src/applet.html' => $this->renderTemplate($appletHtmlTemplate, $configDto),
            'src/applet.scss' => $this->renderTemplate('src/applet.scss.php', $configDto),
            'src/import.ts' => $this->renderTemplate('src/import.ts.php', $configDto),
        ];
    }

    /**
     * Generate default SVG icon content.
     *
     * @param  TceGeneratorConfig|array<string, mixed>  $config
     */
    public function generateDefaultIconSvg(TceGeneratorConfig|array $config): string
    {
        $configDto = $config instanceof TceGeneratorConfig ? $config : $this->prepareConfig($config);

        return $this->renderTemplate('assets/icon.svg.php', $configDto);
    }
}
