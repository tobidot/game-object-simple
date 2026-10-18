<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
/**
 * Mutable instance state — see "Coding Style: Splitting a Non-Trivial Class".
 */
export class <?= $class ?>Properties {
    /** One per connection; everything registered in `start()` is torn down by aborting it. */
    public abort_controller: AbortController | null = null;
    public count: number;
    /** `init-count` is read once; re-attaching the element keeps the running count. */
    public is_initialized = false;

    public constructor(default_count: number) {
        this.count = default_count;
    }
}
