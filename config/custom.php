<?php

return [
    'shopify_api_key' => env('SHOPIFY_API_KEY', ''),
    'shopify_api_secret' => env('SHOPIFY_SECRET_KEY', ''),
    'shopify_api_version' => '2023-10',
    'shopify_scopes' => env('SHOPIFY_SCOPES', 'write_orders,write_fulfillments,write_customers,read_locations,write_products'),
    
];