<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { <?= $class ?>Settings } from './<?= $class ?>Settings';

/**
 * Mutable instance state — see "Coding Style: Splitting a Non-Trivial Class".
 */
export class <?= $class ?>Properties {
    /** One per connection; everything registered in `start()` is torn down by aborting it. */
    public abort_controller: AbortController | null = null;
    public width = 1;
    public height = 1;
    public x = 0;
    public y = 0;
    public dx: number;
    public dy: number;

    public constructor(settings: <?= $class ?>Settings) {
        this.dx = settings.speed;
        this.dy = settings.speed;
    }
}
