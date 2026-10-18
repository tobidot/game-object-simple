<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { <?= $class ?>Properties } from './<?= $class ?>Properties';
import { <?= $class ?>References } from './<?= $class ?>References';
import { <?= $class ?>Settings } from './<?= $class ?>Settings';

/**
 * Pure painting: given the state, draws one frame. Decides nothing — that is Logic's job.
 * See "Coding Style: Splitting a Non-Trivial Class".
 */
export class <?= $class ?>Renderer {
    public constructor(
        private readonly references: <?= $class ?>References,
        private readonly settings: <?= $class ?>Settings,
    ) {}

    public resize(width: number, height: number): void {
        this.references.$canvas.width = width;
        this.references.$canvas.height = height;
    }

    public paint(state: <?= $class ?>Properties): void {
        const context = this.references.context;
        context.fillStyle = this.settings.background_color;
        context.fillRect(0, 0, state.width, state.height);
        context.fillStyle = this.settings.square_color;
        context.fillRect(state.x, state.y, this.settings.square_size, this.settings.square_size);
        context.strokeStyle = this.settings.border_color;
        context.lineWidth = 2;
        context.strokeRect(state.x + 1, state.y + 1, this.settings.square_size - 2, this.settings.square_size - 2);
    }
}
