<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
import templateHtml from './applet.html?raw';
import templateScss from './applet.scss?inline';

export class <?= $config->className ?> extends HTMLElement {
  private shadow: ShadowRoot;
  private count: number = 0;
  private counterElement: HTMLElement | null = null;

  constructor() {
    super();
    this.shadow = this.attachShadow({ mode: 'open' });
  }

  connectedCallback(): void {
    this.render();
    this.setupListeners();
  }

  disconnectedCallback(): void {
    // Clean up event listeners or timers if necessary
  }

  private render(): void {
    this.shadow.innerHTML = `
      <style>${templateScss}</style>
      ${templateHtml}
    `;

    this.counterElement = this.shadow.querySelector('.counter-value');
    this.updateCounter();
  }

  private setupListeners(): void {
    const incrementBtn = this.shadow.querySelector('.btn-increment');
    const decrementBtn = this.shadow.querySelector('.btn-decrement');
    const resetBtn = this.shadow.querySelector('.btn-reset');

    incrementBtn?.addEventListener('click', () => {
      this.count++;
      this.updateCounter();
    });

    decrementBtn?.addEventListener('click', () => {
      this.count--;
      this.updateCounter();
    });

    resetBtn?.addEventListener('click', () => {
      this.count = 0;
      this.updateCounter();
    });
  }

  private updateCounter(): void {
    if (this.counterElement) {
      this.counterElement.textContent = String(this.count);
    }
  }
}
