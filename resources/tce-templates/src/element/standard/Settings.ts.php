<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
/**
 * Readonly configuration constants — see "Coding Style: Splitting a Non-Trivial Class".
 */
export class <?= $class ?>Settings {
    /** The count shown when no `init-count` attribute is given. */
    public readonly default_count = 0;
    /** Read once at first connection — hence the `init-` prefix ("Declarative Structure: Attributes"). */
    public readonly count_attribute = 'init-count';
    public readonly step = 1;
    public readonly title = '<?= str_replace(["\\", "'"], ["\\\\", "\\'"], $config->title) ?>';
}
