<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$e = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $e($config->name) ?> — dev harness</title>
    <style>
        html, body { margin: 0; min-height: 100%; background: #6c6c6c; font-family: monospace; color: #111111; }
        body { padding: 24px; }
        p { color: #ffffff; }
<?php if ($config->kind === 'element'): ?>
        <?= $config->name ?> { display: block; width: <?= $config->width ?>px; height: <?= $config->height ?>px; background: #ffffff; border: 2px solid #111111; }
<?php endif; ?>
    </style>
<?php foreach ($config->dependencies as $dependency): ?>
    <!-- Dependency, exactly as an embedding page includes it ("Integration Into Websites: Dependencies Stay Explicit"). -->
    <script src="<?= $e($config->siteUrl) ?>/tce-elements/<?= $e($dependency['namespace']) ?>/<?= $e($dependency['name']) ?>"></script>
<?php endforeach; ?>
    <script type="module" src="/src/index.ts"></script>
</head>
<body>
    <p>&lt;<?= $e($config->name) ?>&gt; — <?= $e($config->title) ?> <?= $e($config->version) ?></p>
<?php if ($config->kind === 'element'): ?>
    <<?= $config->name ?>></<?= $config->name ?>>
<?php else: ?>
    <p>Library packages have no DOM. Open the console: <code>window.tobidot.tce_package_loader.list()</code> lists this package's binding.</p>
<?php endif; ?>
</body>
</html>
