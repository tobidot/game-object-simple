<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
/**
 * Readonly configuration constants — see "Coding Style: Splitting a Non-Trivial Class".
 */
export class <?= $class ?>Settings {
    /** Low frame rates are part of the aesthetic, not a compromise ("Styling Guide: Typography & Motion"). */
    public readonly frames_per_second = 12;
    public readonly square_size = 24;
    /** Pixels per frame. */
    public readonly speed = 6;
    public readonly background_color = '#6c6c6c';
    public readonly square_color = '#d48c17';
    public readonly border_color = '#111111';
}
