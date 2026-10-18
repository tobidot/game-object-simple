<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { <?= $class ?>References } from './<?= $class ?>References';

/**
 * Pure DOM painting: given data, updates the DOM. Decides nothing and listens to nothing —
 * that is Logic's job. See "Coding Style: Splitting a Non-Trivial Class".
 */
export class <?= $class ?>Renderer {
    public constructor(private readonly references: <?= $class ?>References) {}

    public set_title(title: string): void {
        this.references.$title.textContent = title;
    }

    public set_count(count: number): void {
        this.references.$count.textContent = String(count);
    }
}
