<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="<?= $config->getIconMimeType() ?>" href="./<?= htmlspecialchars($config->iconPath) ?>" />
    <title><?= htmlspecialchars($config->title) ?> - TCE Dev Harness</title>
    <style>
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        padding: 24px;
        background-color: #0f172a;
        color: #f8fafc;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 100vh;
      }
      header {
        margin-bottom: 20px;
        text-align: center;
      }
      header h1 {
        margin: 0 0 6px 0;
        font-size: 1.5rem;
      }
      header p {
        margin: 0;
        color: #94a3b8;
        font-size: 0.9rem;
      }
      #stage {
        border: 2px dashed #334155;
        border-radius: 8px;
        padding: 16px;
        background: #1e293b;
        display: flex;
        justify-content: center;
        align-items: center;
        width: min(100%, <?= $config->width ?>px + 32px);
        min-height: <?= $config->height ?>px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
      }
      <?= $config->name ?> {
        display: block;
        width: <?= $config->width ?>px;
        height: <?= $config->height ?>px;
      }
    </style>
  </head>
  <body>
    <header>
      <h1><?= htmlspecialchars($config->title) ?></h1>
      <p>Custom Element Tag: <code>&lt;<?= htmlspecialchars($config->name) ?>&gt;</code> | Resolution: <?= $config->width ?> &times; <?= $config->height ?></p>
    </header>

    <div id="stage">
      <<?= $config->name ?>></<?= $config->name ?>>
    </div>

    <script type="module" src="./src/index.ts"></script>
    <script type="module" src="./src/import.ts"></script>
  </body>
</html>
