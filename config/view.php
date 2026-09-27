<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | The "src/" directory replaces the default "resources/views/" as the
    | root for all Blade templates. Pages live in src/pages/, layouts in
    | src/layouts/, and components in src/components/.
    |
    */

    'paths' => [
        base_path('src'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    */

    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),

];
