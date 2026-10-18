<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
$prefix = str_replace('-', '_', $config->name);
$version = $config->versionParts();
?>
import { TceFunctionContract, TcePackage, TcePackageVersion, parse_or_throw } from '@tce-v3/common';
import * as z from 'zod/mini';

/** Arguments to a library's public functions are external input — one schema per argument. */
export const greeting_name_schema = z.string().check(z.minLength(1), z.maxLength(64));

/**
 * One contract per exposed function. Consumers vendor these constants and assert them at boot
 * ("Interfacing Between Elements: Publishing a Contract"). Bump a function's version only when
 * THAT function's behavior changes — never the whole library's.
 */
export const <?= $prefix ?>_contracts = {
    greet: { name: 'greet', version: { major: 1, minor: 0, patch: 0 }, params: ['name'] },
} satisfies Record<string, TceFunctionContract>;

/** The function types consumers import (as types only, so this library is never bundled into them). */
export type <?= $class ?>Greet = (name: string) => string;

/**
 * <?= $config->description ?>

 *
 * A library has no DOM, so it stays one class extending `TcePackage` ("Coding Style"). Only
 * names listed in `functions` are reachable through `load()`.
 */
export class <?= $class ?> extends TcePackage {
    public functions: Array<keyof this> = ['greet'];
    public contract_list: TceFunctionContract[] = Object.values(<?= $prefix ?>_contracts);

    public version(): TcePackageVersion {
        return { major: <?= $version['major'] ?>, minor: <?= $version['minor'] ?>, patch: <?= $version['patch'] ?> };
    }

    public greet(name: string): string {
        name = parse_or_throw(greeting_name_schema, name, 'name passed to greet()');
        return `Hello, ${name}!`;
    }
}
