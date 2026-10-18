<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves the raw markdown behind the LaRecipe docs (already public at /docs), for the
 * tce-documentation element, which renders markdown itself.
 */
class RawDocumentationController extends Controller
{
    public const DOCS_VERSION = '1.0';

    public function show(string $path = 'index.md'): Response
    {
        $root = realpath(resource_path('docs/'.self::DOCS_VERSION));
        $file = realpath($root.'/'.$path);

        // realpath resolves "..", so anything outside the docs folder ends up with a different prefix.
        abort_unless(
            $root !== false && $file !== false && str_starts_with($file, $root.DIRECTORY_SEPARATOR)
                && is_file($file) && str_ends_with($file, '.md'),
            404,
        );

        return response((string) file_get_contents($file), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
