<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
$tsConfig = [
    'compilerOptions' => [
        'target' => 'ESNext',
        'useDefineForClassFields' => true,
        'module' => 'ESNext',
        'lib' => ['ESNext', 'DOM', 'DOM.Iterable'],
        'moduleResolution' => 'bundler',
        'allowImportingTsExtensions' => false,
        'resolveJsonModule' => true,
        'isolatedModules' => true,
        'noEmit' => false,
        'strict' => true,
        'noUnusedLocals' => true,
        'noUnusedParameters' => true,
        'noFallthroughCasesInSwitch' => true,
        'skipLibCheck' => true,
        'esModuleInterop' => true,
        'allowSyntheticDefaultImports' => true,
        'forceConsistentCasingInFileNames' => true,
    ],
    'include' => ['src/**/*.ts', 'src/**/*.d.ts'],
];

echo json_encode($tsConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
