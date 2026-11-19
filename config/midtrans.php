<?php

return [
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => trim((string) env('MIDTRANS_CLIENT_KEY', '')),
    'signature_secret_key' => env('MIDTRANS_SIGNATURE_SECRET_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
];
