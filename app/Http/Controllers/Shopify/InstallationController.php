<?php

namespace App\Http\Controllers\Shopify;


use Exception;
use App\Traits\RequestTrait;
use Illuminate\Http\Request;
use App\Traits\FunctionTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;

class InstallationController extends Controller
{
    //
    use FunctionTrait, RequestTrait;
    
    public function startInstallation(Request $request)
    {
        // New installtion
        // Re-installtion
        // Opening the app
        try {
            $validRequest = $this->validateRequestFormShopify($request->all());
            if ($validRequest) {
                $shop = $request->has('shop'); //Check if shop parameter exists on the request
                if ($shop) {
                    $storeDetails = $this->getStoreByDomain($shop);
                    if ($storeDetails !== null && $storeDetails !== false) {
                        // Store record exists and now determine whether the access token is valid or not
                        // If not then forward them to the re-installation flow
                        // If yes then redirect them to the login page
                        $validAccessToken = $this->checkIfAccessTokenIsValid($storeDetails);

                        if ($validAccessToken) {
                            //Token is valid for shopify API calls so redirect them to the login page
                            echo "Here in the valid token part";
                        } else {
                            // Token is not valid so redirect the user to the re-installation page
                            //https://{shop}.myshopify.com/admin/oauth/authorize?client_id={client_id}&scope={scopes}&redirect_uri={redirect_uri}&state={nonce}&grant_options[]={access_mode}
                            echo "Here in the not token valid part";
                        }
                    } else {
                        // New installation flow should be carried out
                        //https://{shop}.myshopify.com/admin/oauth/authorize?client_id={client_id}&scope={scopes}&redirect_uri={redirect_uri}&state={nonce}&grant_options[]={access_mode}
                        //https://1ce1-2402-e280-2313-3e0-3f25-79a3-5603-4fea.ngrok-free.app/shopify/auth?hmac=ff94046bd1723722dc1c2b8d42be0357e552d75154aa7cbd3450f823641f4b98&host=YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvbGFyYXZlbC1wcm9qZWN0LXRlc3Qtc3RvcmU&shop=laravel-project-test-store.myshopify.com&timestamp=1703598492

                        $endpoint = 'https://'.$request->shop.'/admin/oauth/authorize?client_id='.config('custom.shopify_api_key').
                        '&scope='.config('custom.Shopify_Scopes').
                        '&redirect_uri='.config('app.ngrok_url').'shopify/auth/redirect';

                        return Redirect::to($endpoint);
                    }
                } else throw new Exception('Shop parameter not present in the request !');
            } else throw new Exception('Request is not valid !');
        } catch (Exception $e) {
            //Exception $e;
            Log::error($e->getMessage().' '.$e->getLine());
            dd($e->getMessage().' '.$e->getLine());
        }
        
    }

    private function validateRequestFormShopify ($request) 
    {
        try {
            $arr= [];
            $hmac = $request['hmac'];
            unset($request['hmac']);

            foreach ($request as $key => $value) {

                $key=str_replace("%","%25",$key);
                $key=str_replace("&","%26",$key);
                $key=str_replace("=","%3D",$key);
                $value=str_replace("%","%25",$value);
                $value=str_replace("&","%26",$value);

                $arr[] = $key."=".$value;
            }

            $str = implode('&', $arr);
            $ver_hmac =  hash_hmac('sha256', $str, config('custom.shopify_api_secret'), false);

            return $ver_hmac==$hmac;
                
        } catch (\Exception $e) {
            //Exception $e;
            \Log::error($e->getMessage().' '.$e->getLine());
            return false;
        }
    }

    private function checkIfAccessTokenIsValid($storeDetails) {
        
        try {
            if ($storeDetails !== null && isset($storeDetails->access_token) && strlen($storeDetails->access_token) > 0 ) {
                $token = $storeDetails->access_token;
                // Write some code here that will use the Guzzle library to fetch the shop object from shopify API
                // If it succeeds with 200 status then that means its valid and we can return true
                $endpoint = getShopifyURLForStore('shop.json', $storeDetails);
                $headers = getShopifyHeadersForStore($storeDetails);
                $response = $this->makeAnAPICallToShopify('GET', $endpoint, null, $headers, null);
                return true;
            }
            return false;
        } catch (Exception $e) {
            //throw $th;
            \Log::error($e->getMessage().' '.$e->getLine());
        }
    }

    public function handleRedirect(Request $request) {
        try {
            $validRequest = $this->validateRequestFormShopify($request->all());
            if ($validRequest) {
                //https://example.org/some/redirect/uri?code={authorization_code}&hmac=da9d83c171400a41f8db91a950508985&host={base64_encoded_hostname}&shop={shop_origin}&state={nonce}&timestamp=1409617544
                if ( $request->has('shop') && $request->has('code') ) {
                    $shop = $request->shop;
                    $code = $request->code;
                    $accessToken = $this->requestAccessTokenFromShopifyForThisStore($shop, $code);
                    if ($accessToken !== false && $accessToken !== null) {
                        $shopDetails = $this->getShopDetailsFromShopify($accessToken, $shop);
                    } else throw new Exception('Invalid Access Token ! '.$accessToken);
                } else throw new Exception('Code / Shop param not present in the URL !');
            } else throw new Exception('Request is nor valid !');
            
        } catch (\Exception $e) {
            //throw $th;
            \Log::error($e->getMessage().' '.$e->getLine());
        }
    }

    private function requestAccessTokenFromShopifyForThisStore($shop, $code) {
        try {
            $endpoint = getShopifyURLForStore('shop.json', $shop);
            $headers = ['content-type' => 'application/json'];
            $requestBody = [
                'client_id' => config('custom.shopify_api_key'),
                'client_secret' => config('custom.shopify_api_secret'),
                'code' => $code
            ];
            $response = $this->makeAnAPICallToShopify('POST', $endpoint, null, $headers, $requestBody);
        } catch (\Exception $e) {
            //throw $th;
            \Log::error($e->getMessage().' '.$e->getLine());            
        }
    }
}
