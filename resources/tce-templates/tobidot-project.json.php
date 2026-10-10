<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
$projectData = [
    '$schema' => 'https://game-object.com/tobidot-project-schema.json',
    'title' => $config->title,
    'informal' => [
        'description' => $config->description,
        'categories' => ['custom-element', 'tce', $config->kind],
    ],
    'development' => [
        'status' => 'draft',
    ],
    'execution' => [
        'type' => 'website',
        'resources' => 'dist',
        'index' => 'index.html',
    ],
    'resolution' => [
        'width' => $config->width,
        'height' => $config->height,
    ],
    'history' => [],
    'code' => [
        'language' => 'typescript',
    ],
];

if (! empty($config->tceDependencies)) {
    $projectData['dependencies'] = $config->tceDependencies;
}

echo json_encode($projectData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
