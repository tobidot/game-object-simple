<?php

namespace App\Helpers;

class AppHelper
{
    /**
     * @param class-string<T> $class class to resolve
     * @return T
     * @template T
     */
    public static function resolve(string $class): mixed
    {
        return resolve($class);
    }
}
