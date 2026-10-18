<?php

/*
|--------------------------------------------------------------------------
| TCE v3 — this deployment's own settings
|--------------------------------------------------------------------------
|
| Namespace is publisher-owned data everywhere in the framework (see "Naming & Identifiers:
| Namespace" in the specification). The only place a namespace literal may live is here, as
| THIS site's own default for the short-form routes, the uploader and the project generator.
|
*/

return [

    'default_namespace' => env('TCE_DEFAULT_NAMESPACE', 'tobidot'),

    /*
    | The build-time SDK packages (kind "sdk" in the registry, published from the v3 workspace
    | with `publish.py`). The project generator vendors them into every generated project.
    | `required` SDK packages go into every project; the others only into the kinds listed.
    */
    'sdk_packages' => [
        'common' => ['npm_name' => '@tce-v3/common', 'kinds' => ['element', 'library']],
        'desktop-protocol' => ['npm_name' => '@tce-v3/desktop-protocol', 'kinds' => ['element']],
    ],

];
