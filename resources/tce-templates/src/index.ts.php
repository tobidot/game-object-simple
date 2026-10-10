<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
import { <?= $config->className ?> } from './<?= $config->className ?>';

// Register the custom element tag if not already registered
if (!customElements.get('<?= $config->name ?>')) {
  customElements.define('<?= $config->name ?>', <?= $config->className ?>);
}

export { <?= $config->className ?> };
export default <?= $config->className ?>;
