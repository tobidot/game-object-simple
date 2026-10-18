<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { <?= $class ?>Logic } from './<?= $class ?>Logic';
import { <?= $class ?>Properties } from './<?= $class ?>Properties';
import { <?= $class ?>References } from './<?= $class ?>References';
import { <?= $class ?>Renderer } from './<?= $class ?>Renderer';
import { <?= $class ?>Settings } from './<?= $class ?>Settings';

/**
 * <?= $config->description ?>

 *
 * This class is a shallow delegate — see "Coding Style: Splitting a Non-Trivial Class". It
 * wires the five collaborators together; every decision lives in `<?= $class ?>Logic`.
 */
export class <?= $class ?> extends HTMLElement {
    private readonly settings: <?= $class ?>Settings;
    private readonly references: <?= $class ?>References;
    private readonly properties: <?= $class ?>Properties;
    private readonly renderer: <?= $class ?>Renderer;
    private readonly logic: <?= $class ?>Logic;

    public constructor() {
        super();
        this.settings = new <?= $class ?>Settings();
        this.references = new <?= $class ?>References(this);
        this.properties = new <?= $class ?>Properties(this.settings.default_count);
        this.renderer = new <?= $class ?>Renderer(this.references);
        this.logic = new <?= $class ?>Logic(this, this.references, this.properties, this.renderer, this.settings);
    }

    public connectedCallback(): void {
        this.logic.start();
    }

    public disconnectedCallback(): void {
        this.logic.stop();
    }
}
