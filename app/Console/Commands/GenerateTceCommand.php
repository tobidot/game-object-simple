<?php

namespace App\Console\Commands;

use App\Helpers\AppHelper;
use App\Services\TceGeneratorService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class GenerateTceCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tce:generate
                            {name : The package name, e.g. tce-my-element or dll-my-library}
                            {--namespace= : Publisher namespace (defaults to this site\'s own)}
                            {--title= : Human-readable title}
                            {--description= : Short description}
                            {--element-version=0.1.0 : Initial major.minor.patch version}
                            {--kind=element : element or library}
                            {--width=400 : Default window width in pixels (elements)}
                            {--height=300 : Default window height in pixels (elements)}
                            {--template=standard : Element preset: standard, canvas or minimal}
                            {--dependency=* : A registry package to depend on, as namespace/name}
                            {--output= : A .zip path, or a directory to write the project into}';

    /**
     * @var string
     */
    protected $description = 'Generate a new, complete Tobidot Custom Element (TCE) v3 project';

    public function handle(): int
    {
        /** @var TceGeneratorService $service */
        $service = AppHelper::resolve(TceGeneratorService::class);

        try {
            $config = $service->prepareConfig([
                'name' => $this->argument('name'),
                'namespace' => $this->option('namespace'),
                'title' => $this->option('title'),
                'description' => $this->option('description'),
                'version' => $this->option('element-version'),
                'kind' => $this->option('kind'),
                'width' => (int) $this->option('width'),
                'height' => (int) $this->option('height'),
                'template' => $this->option('template'),
                'dependencies' => (array) $this->option('dependency'),
            ]);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $outputPath = $this->option('output') ?: getcwd().DIRECTORY_SEPARATOR.$config->getZipFilename();

        $this->info("Generating '$config->namespace/$config->name' ($config->title)...");

        if (str_ends_with(strtolower($outputPath), '.zip')) {
            $tempZip = $service->generateZip($config);
            if (! @rename($tempZip, $outputPath) && ! (@copy($tempZip, $outputPath) && @unlink($tempZip))) {
                $this->error("Could not write $outputPath");

                return self::FAILURE;
            }
            $this->info("Project zip written to: $outputPath");
        } else {
            foreach ($service->generateProjectFiles($config) as $relPath => $content) {
                $dest = $outputPath.DIRECTORY_SEPARATOR.$relPath;
                if (! is_dir(dirname($dest))) {
                    mkdir(dirname($dest), 0755, true);
                }
                file_put_contents($dest, $content);
            }
            $this->info("Project written to: $outputPath");
        }

        $this->table(
            ['Property', 'Value'],
            [
                ['Identifier', "$config->namespace/$config->name"],
                ['Class', $config->className],
                ['Version', $config->version],
                ['Kind', $config->kind],
                ['Template', $config->kind === 'element' ? $config->template : '-'],
                ['Resolution', $config->kind === 'element' ? "{$config->width}x{$config->height}" : '-'],
                ['Dependencies', implode(', ', array_map(fn (array $d) => "{$d['namespace']}/{$d['name']}", $config->dependencies)) ?: '-'],
                ['SDK', implode(', ', array_map(fn (array $s) => "{$s['npm_name']}@{$s['version']}", $config->sdk))],
            ]
        );

        $this->comment('Next steps:');
        $this->line('  1. Unzip / open the project and run `npm install`');
        $this->line('  2. `npm start` for the live dev harness, `npm run check` for tsc + lint');
        $this->line('  3. `npm run pack` builds dist/ and zips it into release/ — upload that zip');

        return self::SUCCESS;
    }
}
