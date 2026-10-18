<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { attach_styles } from '@tce-v3/common';
import applet_scss from './applet.scss?inline';

/**
 * Every DOM element this applet touches, built exactly once in the constructor — see
 * "Coding Style" and "Element Lifecycle & Rendering: Shadow DOM".
 */
export class <?= $class ?>References {
    public shadow: ShadowRoot;
    public $canvas: HTMLCanvasElement;
    public context: CanvasRenderingContext2D;

    public constructor(host: HTMLElement) {
        this.shadow = host.attachShadow({ mode: 'open' });
        attach_styles(this.shadow, { css: applet_scss });

        this.$canvas = document.createElement('canvas');
        this.$canvas.className = 'applet__canvas';
        this.$canvas.width = 1;
        this.$canvas.height = 1;
        this.shadow.appendChild(this.$canvas);

        const context = this.$canvas.getContext('2d');
        if (!context) {
            throw new Error('<<?= $config->name ?>>: 2D canvas is not available');
        }
        this.context = context;
    }
}
