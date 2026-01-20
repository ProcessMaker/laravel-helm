<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Helm Binary Path
    |--------------------------------------------------------------------------
    |
    | Path for the helm binary.
    |
    */

    'path' => env('HELM_BINARY_PATH', '/usr/bin/helm'),
    'process_timeout' => env('HELM_PROCESS_TIMEOUT', 3600),
];
