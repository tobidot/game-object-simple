<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
<div class="tce-container">
  <div class="card">
    <div class="card-header">
      <span class="badge"><?= htmlspecialchars($config->kind) ?></span>
      <h2 class="title"><?= htmlspecialchars($config->title) ?></h2>
    </div>
    <p class="description"><?= htmlspecialchars($config->description) ?></p>

    <div class="counter-box">
      <div class="counter-label">Live Counter</div>
      <div class="counter-value">0</div>
      <div class="button-group">
        <button class="btn btn-decrement" type="button">-</button>
        <button class="btn btn-reset" type="button">Reset</button>
        <button class="btn btn-increment" type="button">+</button>
      </div>
    </div>
  </div>
</div>
