<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$dependencies = ['zod' => '^4.6.7'];
foreach ($config->sdk as $sdk) {
    $dependencies[$sdk['npm_name']] = 'file:'.$sdk['path'];
}
ksort($dependencies);

$dev_dependencies = [
    'eslint' => '^10.13.0',
    'typescript' => '^5.9.3',
    'typescript-eslint' => '^8.71.2',
    'vite' => '^5.4.21',
];
if ($config->kind === 'element') {
    $dev_dependencies['sass'] = '^1.77.0';
}
ksort($dev_dependencies);

echo json_encode([
    'name' => $config->name,
    'version' => $config->version,
    'description' => $config->description,
    'private' => true,
    'type' => 'module',
    'main' => 'src/index.ts',
    'types' => 'src/index.ts',
    'scripts' => [
        'start' => 'vite',
        'tsc' => 'tsc --noEmit',
        'lint' => 'eslint .',
        'check' => 'npm run tsc && npm run lint',
        'build' => 'vite build',
        'pack' => 'npm run build && node scripts/pack.mjs',
    ],
    'dependencies' => $dependencies,
    'devDependencies' => $dev_dependencies,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
