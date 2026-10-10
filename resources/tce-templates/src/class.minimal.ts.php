<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
import templateHtml from './applet.html?raw';
import templateScss from './applet.scss?inline';

export class <?= $config->className ?> extends HTMLElement {
  constructor() {
    super();
    const shadow = this.attachShadow({ mode: 'open' });
    shadow.innerHTML = `
      <style>${templateScss}</style>
      ${templateHtml}
    `;
  }
}
