<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
$variable = $config->packageVariable();
$version = $config->versionParts();
$json = fn (string $value): string => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$ts = fn (string $value): string => "'".str_replace(["\\", "'"], ["\\\\", "\\'"], $value)."'";
?>
import { TceElementPackage, TceRoot } from '@tce-v3/common';
import { <?= $class ?> } from './<?= $class ?>';

/**
 * The entry point every package shares — see "Element Lifecycle & Rendering: Bootstrapping".
 * Running this script binds the package into the page's one loader and registers the custom
 * element tag, which is all a zero-script embed (`<script src>` + `<<?= $config->name ?>>`) needs.
 */
const package_identifier = { namespace: <?= $ts($config->namespace) ?>, name: <?= $ts($config->name) ?> };
const <?= $variable ?> = new TceElementPackage(<?= $ts($config->name) ?>, <?= $class ?>, { major: <?= $version['major'] ?>, minor: <?= $version['minor'] ?>, patch: <?= $version['patch'] ?> });

TceRoot.bind_self(package_identifier, <?= $variable ?>, {
    kind: 'element',
    description: <?= $ts($config->description) ?>,
    width: <?= $config->width ?>,
    height: <?= $config->height ?>,
<?php if ($config->dependencies !== []): ?>
    dependencies: [
<?php foreach ($config->dependencies as $dependency): ?>
        { identifier: { namespace: <?= $ts($dependency['namespace']) ?>, name: <?= $ts($dependency['name']) ?> } },
<?php endforeach; ?>
    ],
<?php endif; ?>
});

void <?= $variable ?>.init().catch((error: unknown) => TceRoot.report(error instanceof Error ? error : String(error)));

export * from './<?= $class ?>';
