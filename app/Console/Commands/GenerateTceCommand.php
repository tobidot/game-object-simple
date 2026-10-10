<?php

namespace App\Console\Commands;

use App\Helpers\AppHelper;
use App\Services\TceGeneratorService;
use Illuminate\Console\Command;

class GenerateTceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tce:generate
                            {name? : The element name (e.g. tce-my-element)}
                            {--title= : Human-readable title}
                            {--description= : Short description}
                            {--element-version=1.0.0 : Initial SemVer version}
                            {--kind=element : Element kind (element or library)}
                            {--width=400 : Window width in pixels}
                            {--height=300 : Window height in pixels}
                            {--template=standard : Template preset (standard, canvas, minimal)}
                            {--tce-dependency=* : Dependent TCE element (e.g. dll-tce-desktop)}
                            {--dependency=* : Additional package dependency (e.g. package@1.0.0)}
                            {--output= : Output path for the zip file or directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new Tobidot Custom Element (TCE) project template';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        if (empty($name)) {
            $name = 'tce-my-element';
        }

        $defaultTitle = ucwords(str_replace(['tce-', '-'], ['', ' '], $name));
        $title = $this->option('title') ?: $defaultTitle;
        $description = $this->option('description') ?: "Tobidot Custom Element for $title";
        $version = $this->option('element-version') ?: '1.0.0';
        $kind = $this->option('kind') ?: 'element';
        $width = (int) ($this->option('width') ?: 400);
        $height = (int) ($this->option('height') ?: 300);
        $template = $this->option('template') ?: 'standard';

        $tceDependencies = (array) $this->option('tce-dependency');
        $npmDependencies = (array) $this->option('dependency');

        $options = [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'version' => $version,
            'kind' => $kind,
            'width' => $width,
            'height' => $height,
            'template' => $template,
            'tce_dependencies' => $tceDependencies,
            'dependencies' => $npmDependencies,
        ];

        /** @var TceGeneratorService $service */
        $service = AppHelper::resolve(TceGeneratorService::class);
        $config = $service->prepareConfig($options);

        $outputPath = $this->option('output');
        if (empty($outputPath)) {
            $outputPath = getcwd().DIRECTORY_SEPARATOR.$config->getZipFilename();
        }

        $this->info("Generating TCE project '$config->name' ($config->title)...");

        // Check if output is a directory or a zip file
        if (str_ends_with(strtolower($outputPath), '.zip')) {
            $tempZip = $service->generateZip($config);
            @copy($tempZip, $outputPath);
            @unlink($tempZip);
            $this->info("TCE ZIP package created successfully at: $outputPath");
        } else {
            // Output as unpacked directory
            $files = $service->generateProjectFiles($config);
            if (! is_dir($outputPath)) {
                mkdir($outputPath, 0755, true);
            }
            foreach ($files as $relPath => $content) {
                $dest = $outputPath.DIRECTORY_SEPARATOR.$relPath;
                $dir = dirname($dest);
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($dest, $content);
            }
            // Add icon
            $assetsDir = $outputPath.DIRECTORY_SEPARATOR.'assets';
            if (! is_dir($assetsDir)) {
                mkdir($assetsDir, 0755, true);
            }
            file_put_contents(
                $assetsDir.DIRECTORY_SEPARATOR.'icon.svg',
                $service->generateDefaultIconSvg($config)
            );
            $this->info("TCE project files created successfully in: $outputPath");
        }

        $this->table(
            ['Property', 'Value'],
            [
                ['Tag Name', "<$config->name>"],
                ['Class Name', $config->className],
                ['Version', $config->version],
                ['Kind', $config->kind],
                ['Resolution', "$config->width".'x'."$config->height"],
                ['Template', $config->template],
            ]
        );

        $this->comment('Next steps:');
        $this->line('  1. Extract/open project');
        $this->line('  2. Run `npm install`');
        $this->line('  3. Run `npm start` to develop live');
        $this->line('  4. Run `make tce` to package for upload');

        return self::SUCCESS;
    }
}
