<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
import templateHtml from './applet.html?raw';
import templateScss from './applet.scss?inline';

export class <?= $config->className ?> extends HTMLElement {
  private shadow: ShadowRoot;
  private canvas: HTMLCanvasElement | null = null;
  private ctx: CanvasRenderingContext2D | null = null;
  private animationFrameId: number | null = null;
  private angle: number = 0;

  constructor() {
    super();
    this.shadow = this.attachShadow({ mode: 'open' });
  }

  connectedCallback(): void {
    this.shadow.innerHTML = `
      <style>${templateScss}</style>
      ${templateHtml}
    `;

    this.canvas = this.shadow.querySelector('canvas');
    if (this.canvas) {
      this.ctx = this.canvas.getContext('2d');
      this.canvas.width = <?= $config->width ?>;
      this.canvas.height = <?= $config->height ?>;
      this.startLoop();
    }
  }

  disconnectedCallback(): void {
    if (this.animationFrameId !== null) {
      cancelAnimationFrame(this.animationFrameId);
      this.animationFrameId = null;
    }
  }

  private startLoop(): void {
    const loop = () => {
      this.draw();
      this.animationFrameId = requestAnimationFrame(loop);
    };
    this.animationFrameId = requestAnimationFrame(loop);
  }

  private draw(): void {
    if (!this.ctx || !this.canvas) return;

    const { width, height } = this.canvas;
    this.ctx.clearRect(0, 0, width, height);

    // Background
    this.ctx.fillStyle = '#0f172a';
    this.ctx.fillRect(0, 0, width, height);

    // Animated rotating shape
    this.angle += 0.02;
    const cx = width / 2;
    const cy = height / 2;
    const radius = Math.min(width, height) * 0.25;

    this.ctx.save();
    this.ctx.translate(cx, cy);
    this.ctx.rotate(this.angle);

    this.ctx.fillStyle = '#38bdf8';
    this.ctx.fillRect(-radius / 2, -radius / 2, radius, radius);

    this.ctx.restore();

    // Center text
    this.ctx.fillStyle = '#f8fafc';
    this.ctx.font = '14px sans-serif';
    this.ctx.textAlign = 'center';
    this.ctx.fillText('<?= addslashes($config->title) ?>', cx, cy + radius + 24);
  }
}
