<?php

namespace App\Traits;

use Exception;

trait RequestTrait {

    public function makeAnAPICallToShopify($method = 'GET', $endpoint, $url_params = null, $headers, $requestBody = null) {
        
        // Headers
        /**
         * Content-Type : application/json
         * X-Shopify-Access-Token: value
         */
        try {
            $client = new \GuzzleHttp\Client();
            $response = null;
            switch ($method) {
                case 'GET'; $response = $client->request($method, $endpoint, ['headers' => $headers]); break;
                case 'POST'; $response = $client->request($method, $endpoint, [ 'headers' => $headers, 'form_params' => $requestBody ]); break;
            }
            
            return [
                'statusCode' => $response->getStatusCode(),
                'body' => $request->getBody(),
            ];
        } catch (Exception $e) {
            //throw $th;
            \Log::error($e->getMessage().' '.$e->getLine());
            return [
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
                'body' => null
            ];
        }
    }
}