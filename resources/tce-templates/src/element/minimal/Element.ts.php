<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { attach_styles } from '@tce-v3/common';
import applet_scss from './applet.scss?inline';

/**
 * <?= $config->description ?>

 *
 * Small enough to stay one class ("Coding Style: Splitting a Non-Trivial Class"). Once it grows
 * real state and decisions, split it into Settings / References / Properties / Renderer / Logic.
 */
export class <?= $class ?> extends HTMLElement {
    private readonly shadow: ShadowRoot;
    private readonly $message: HTMLParagraphElement;
    private abort_controller: AbortController | null = null;

    public constructor() {
        super();
        // Static Shadow DOM is built exactly once, here; no attribute of `this` is touched
        // ("Element Lifecycle & Rendering: Shadow DOM: Attach and Build Once").
        this.shadow = this.attachShadow({ mode: 'open' });
        attach_styles(this.shadow, { css: applet_scss });

        this.$message = document.createElement('p');
        this.$message.className = 'applet__message';
        this.$message.textContent = 'Hello from <<?= $config->name ?>>';
        this.shadow.appendChild(this.$message);
    }

    public connectedCallback(): void {
        this.abort_controller = new AbortController();
        this.$message.addEventListener('click', () => {
            this.$message.textContent = 'Clicked';
        }, { signal: this.abort_controller.signal });
    }

    public disconnectedCallback(): void {
        this.abort_controller?.abort();
        this.abort_controller = null;
    }
}
