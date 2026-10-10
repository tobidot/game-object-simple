<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
$packageData = [
    'name' => $config->name,
    'version' => $config->version,
    'description' => $config->description,
    'type' => 'module',
    'main' => './src/index.ts',
    'scripts' => [
        'start' => 'vite',
        'dev' => 'vite',
        'build' => 'vite build',
        'preview' => 'vite preview',
        'clean' => 'rm -rf dist build-tce',
        'package' => 'npm run build && make tce',
    ],
    'keywords' => [
        'tce',
        'tobidot-custom-element',
        'web-components',
        'typescript',
    ],
    'dependencies' => (object) ($config->dependencies ?? []),
    'devDependencies' => [
        'sass' => '^1.77.0',
        'typescript' => '^5.4.0',
        'vite' => '^5.3.0',
    ],
];

echo json_encode($packageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
