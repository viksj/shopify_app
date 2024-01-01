<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

trait RequestTrait {

    public function makeAnAPICallToShopify($method, $endpoint, $url_params = null, $headers, $requestBody = null) {
        
        // Headers
        /**
         * Content-Type : application/json
         * X-Shopify-Access-Token: value
         */
        try {
            \Log::info('method : '.$method);
            $client = new Client();
            $response = null;
            switch ($method) {
                case 'GET'; $response = $client->request($method, $endpoint, ['headers' => $headers]); break;
                case 'POST'; $response = $client->request($method, $endpoint, [ 'headers' => $headers, 'json' => $requestBody ]); break;
            }
            \Log::info('status code : '.$response->getStatusCode());
            return [
                'statusCode' => $response->getStatusCode(),
                'body' => $response->getBody(),
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

    public function makeAPOSTCallToShopify($payload, $endpoint, $headers = NULL) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers === NULL ? [] : $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $aHeaderInfo = curl_getinfo($ch);
        $curlHeaderSize = $aHeaderInfo['header_size'];
        $sBody = trim(mb_substr($result, $curlHeaderSize));

        return ['statusCode' => $httpCode, 'body' => $sBody];
    }   
}