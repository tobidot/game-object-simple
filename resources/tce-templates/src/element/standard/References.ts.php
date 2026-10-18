<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { attach_styles } from '@tce-v3/common';
import applet_scss from './applet.scss?inline';

/**
 * Every DOM element this applet touches, built exactly once in the constructor and exposed
 * publicly so Logic and Renderer never re-query the shadow tree — see "Coding Style" and
 * "Element Lifecycle & Rendering: Shadow DOM". Nothing here sets an attribute on the host.
 */
export class <?= $class ?>References {
    public shadow: ShadowRoot;
    public $title: HTMLHeadingElement;
    public $count: HTMLDivElement;
    public $button: HTMLButtonElement;

    public constructor(host: HTMLElement) {
        this.shadow = host.attachShadow({ mode: 'open' });
        attach_styles(this.shadow, { css: applet_scss });

        const $applet = document.createElement('div');
        $applet.className = 'applet';

        this.$title = document.createElement('h1');
        this.$title.className = 'applet__title';

        this.$count = document.createElement('div');
        this.$count.className = 'applet__count';
        this.$count.setAttribute('role', 'status');

        this.$button = document.createElement('button');
        this.$button.type = 'button';
        this.$button.className = 'applet__button';
        this.$button.textContent = 'Count';

        $applet.append(this.$title, this.$count, this.$button);
        this.shadow.appendChild($applet);
    }
}
