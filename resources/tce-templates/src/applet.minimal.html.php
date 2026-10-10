<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
<div class="tce-container">
  <h2><?= htmlspecialchars($config->title) ?></h2>
  <p><?= htmlspecialchars($config->description) ?></p>
</div>
