<?php

return [
    'proxies' => env('TRUSTED_PROXIES') ? explode(',', env('TRUSTED_PROXIES')) : [],
];