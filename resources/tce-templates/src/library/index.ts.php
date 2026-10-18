<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
$variable = $config->packageVariable();
$ts = fn (string $value): string => "'".str_replace(["\\", "'"], ["\\\\", "\\'"], $value)."'";
?>
import { TceRoot } from '@tce-v3/common';
import { <?= $class ?> } from './<?= $class ?>';

/**
 * The entry point every package shares — see "Element Lifecycle & Rendering: Bootstrapping".
 * Running this script binds the library into the page's one loader; consumers reach its
 * functions through `TceRoot.package_loader().load()` plus `assert_contract`.
 */
const package_identifier = { namespace: <?= $ts($config->namespace) ?>, name: <?= $ts($config->name) ?> };
const <?= $variable ?> = new <?= $class ?>();

TceRoot.bind_self(package_identifier, <?= $variable ?>, {
    kind: 'library',
    description: <?= $ts($config->description) ?>,
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
