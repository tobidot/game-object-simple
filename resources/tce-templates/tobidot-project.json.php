<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$manifest = [
    '$schema' => 'https://game-object.com/tobidot-project-schema.json',
    'namespace' => $config->namespace,
    'name' => $config->name,
    'title' => $config->title,
    'informal' => [
        'description' => $config->description,
        'categories' => ['custom-element', 'tce', $config->kind],
    ],
    'development' => ['status' => 'draft'],
    'execution' => ['type' => 'website', 'resources' => 'dist', 'index' => 'index.js'],
    'resolution' => ['width' => $config->width, 'height' => $config->height],
    'history' => [],
    'code' => ['language' => 'typescript'],
];
if ($config->dependencies !== []) {
    $manifest['dependencies'] = array_map(
        fn (array $dependency) => ['identifier' => ['namespace' => $dependency['namespace'], 'name' => $dependency['name']]],
        $config->dependencies,
    );
}

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
