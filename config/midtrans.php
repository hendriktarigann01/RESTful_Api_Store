<?php

return [
    'server_key'    => env('MIDTRANS_SERVER_KEY'),
    'client_key'    => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'base_url'      => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://api.midtrans.com/v2/'
        : 'https://api.sandbox.midtrans.com/v2/',
];
