<?php
/**
 * @var \App\DTO\TceGeneratorConfig $config
 * @var array{namespace: string, name: string, kind: string, version: string} $dependency
 */
$prefix = str_replace('-', '_', $dependency['name']);
$ts = fn (string $value): string => "'".str_replace(["\\", "'"], ["\\\\", "\\'"], $value)."'";
?>
import { TceFunctionContract } from '@tce-v3/common';

/**
 * The `<?= $dependency['name'] ?>` contracts this package is built and tested against, vendored on
 * purpose — see "Interfacing Between Elements: Vendoring and Asserting a Contract". Copy each
 * function's contract constant from the library's source (generated against
 * <?= $dependency['namespace'] ?>/<?= $dependency['name'] ?> <?= $dependency['version'] ?>), declare its type next to it, and assert every
 * function you call with `assert_contract` inside `init()` — never lazily on first call.
 */
export const <?= $prefix ?>_identifier = { namespace: <?= $ts($dependency['namespace']) ?>, name: <?= $ts($dependency['name']) ?> };

export const <?= $prefix ?>_contracts = {
    // example: { name: 'read', version: { major: 1, minor: 0, patch: 0 } },
} satisfies Record<string, TceFunctionContract>;
