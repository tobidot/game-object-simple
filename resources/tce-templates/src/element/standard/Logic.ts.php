<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { read_attribute } from '@tce-v3/common';
import * as z from 'zod/mini';
import { <?= $class ?>Properties } from './<?= $class ?>Properties';
import { <?= $class ?>References } from './<?= $class ?>References';
import { <?= $class ?>Renderer } from './<?= $class ?>Renderer';
import { <?= $class ?>Settings } from './<?= $class ?>Settings';

const count_schema = z.coerce.number().check(z.int());

/**
 * Every decision and every event listener — see "Coding Style: Splitting a Non-Trivial Class".
 * `start()`/`stop()` are the element's `connectedCallback`/`disconnectedCallback`: one
 * `AbortController` per connection tears down everything registered here ("Element Lifecycle
 * & Rendering: Connect/Disconnect").
 */
export class <?= $class ?>Logic {
    public constructor(
        private readonly host: HTMLElement,
        private readonly references: <?= $class ?>References,
        private readonly properties: <?= $class ?>Properties,
        private readonly renderer: <?= $class ?>Renderer,
        private readonly settings: <?= $class ?>Settings,
    ) {}

    public start(): void {
        this.properties.abort_controller = new AbortController();
        const signal = this.properties.abort_controller.signal;

        if (!this.properties.is_initialized) {
            this.properties.is_initialized = true;
            // Attributes are external input: parsed through a schema, with a fallback
            // ("Coding Style: Validate External Input").
            this.properties.count = read_attribute(this.host, this.settings.count_attribute, count_schema, this.settings.default_count);
        }

        this.references.$button.addEventListener('click', () => this.increment(), { signal });

        this.renderer.set_title(this.settings.title);
        this.renderer.set_count(this.properties.count);
    }

    public stop(): void {
        this.properties.abort_controller?.abort();
        this.properties.abort_controller = null;
    }

    private increment(): void {
        this.properties.count += this.settings.step;
        this.renderer.set_count(this.properties.count);
    }
}
