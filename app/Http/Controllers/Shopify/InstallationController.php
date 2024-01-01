<?php

namespace App\Http\Controllers\Shopify;


use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use App\Traits\RequestTrait;
use Illuminate\Http\Request;
use App\Models\Shopify\Store;
use App\Traits\FunctionTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
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
            $validRequest = $this->validateRequestFromShopify($request->all());
            if ($validRequest) {
                $shop = $request->has('shop'); //Check if shop parameter exists on the request
                \Log::info('shop : '.$shop);
                if ($shop) {
                    \Log::info('if shop : '.print_r($shop));
                    $storeDetails = $this->getStoreByDomain($shop);
                    \Log::info('storeDetails : '.print_r($storeDetails, true));
                    if ($storeDetails !== null && $storeDetails !== false) {
                        // Store record exists and now determine whether the access token is valid or not
                        // If not then forward them to the re-installation flow
                        // If yes then redirect them to the login page
                        $validAccessToken = $this->checkIfAccessTokenIsValid($storeDetails);
                        \Log::info('validAccessToken : '.$validAccessToken);
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
                        '&scope='.config('custom.shopify_scopes').
                        '&redirect_uri='.config('app.url').'shopify/auth/redirect';
                        // '&redirect_uri='.route('shopify.app_install_redirect');
                        // dd('route : ',config('app.url'));
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

    private function checkIfAccessTokenIsValid($storeDetails) {
        
        try {
            if ($storeDetails !== null && isset($storeDetails->access_token) && strlen($storeDetails->access_token) > 0 ) {
                $token = $storeDetails->access_token;
                // Write some code here that will use the Guzzle library to fetch the shop object from shopify API
                // If it succeeds with 200 status then that means its valid and we can return true
                $endpoint = getShopifyURLForStore('shop.json', $storeDetails);
                $headers = getShopifyHeadersForStore($storeDetails);
                $response = $this->makeAnAPICallToShopify('GET', $endpoint, null, $headers, null);
                \Log::info('response : '.$response);
                return $response['statusCode'] === 200;
            }
            return false;
        } catch (Exception $e) {
            //throw $th;
            \Log::error($e->getMessage().' '.$e->getLine());
        }
    }

    public function handleRedirect(Request $request) {
        try {
            $validRequest = $this->validateRequestFromShopify($request->all());
            if ($validRequest) {
                //https://example.org/some/redirect/uri?code={authorization_code}&hmac=da9d83c171400a41f8db91a950508985&host={base64_encoded_hostname}&shop={shop_origin}&state={nonce}&timestamp=1409617544
                if ( $request->has('shop') && $request->has('code') ) {
                    $shop = $request->shop;
                    $code = $request->code; 
                    $accessToken = $this->requestAccessTokenFromShopifyForThisStore($shop, $code);
                    if ($accessToken !== false && $accessToken !== null) {
                        $shopDetails = $this->getShopDetailsFromShopify($accessToken, $shop);
                        $saveDetails = $this->saveStoreDetailsToDatabase($shopDetails, $accessToken);

                        if ($saveDetails) {
                            //At this point the installation process is complete.
                            // Session::flash('success', 'Installation for your store '.$saveDetails['name'].' has completed. Please Login');
                            // return Redirect::to(route('app_install_complete'));
                            // return Redirect::to(config('app.ngrok_url').'shopify/auth/complete');
                            return Redirect::route('login');
                        } else {
                            \Log::info('Problem during saving shop details into the db');
                            \Log::info($saveDetails);
                            dd('Problem during installation. please check logs');
                        }

                    } else throw new Exception('Invalid Access Token ! '.$accessToken);
                } else throw new Exception('Code / Shop param not present in the URL !');
            } else throw new Exception('Request is nor valid !');
            
        } catch (\Exception $e) {
            //throw $th;
            \Log::error($e->getMessage().' '.$e->getTraceAsString());
        }
    }
    public function saveStoreDetailsToDatabase($shopDetails, $accesstoken) {
        try {
            //code...
            $payload = [
                'access_token' => $accesstoken,
                'myshopify_domain' => $shopDetails['myshopify_domain'],
                'id' => $shopDetails['id'],
                'shop_owner' => $shopDetails['shop_owner'],
                'name' => $shopDetails['name'],
                'phone' => $shopDetails['phone'],
                'zip' => $shopDetails['zip'],
                'domain' => $shopDetails['domain'],
                'customer_email' => $shopDetails['customer_email'],
                'email' => $shopDetails['email'],
                'address1' => $shopDetails['address1'],
                'address2' => $shopDetails['address2'],
                'checkout_api_supported' => $shopDetails['checkout_api_supported'],
            ];
            // dd(print_r($payload, true));
            $store_db = Store::updateOrCreate(['myshopify_domain' => $shopDetails['myshopify_domain']], $payload);
            $random_password = Str::random(10);
             \Log::info('Password generated : '.$random_password);
            $user_payload = [
                'name' => $shopDetails['myshopify_domain'],
                'email' => $shopDetails['email'],
                'password' => $random_password,
                'store_id' => $store_db->table_id,
                'email_verified_at' => date('Y-m-d h:i:s')
            ];

            $user = User::updateOrCreate(['email' => $shopDetails['email']], $user_payload);
            $user->markEmailAsVerified(); // To make this user verified witout requiring them to.
            Session::flash('success', 'Installation for your store '.$shopDetails['name'].' has completed and the credentiials have been send to '.$shopDetails['email'].' Please Login');
            //Send the credentials on the register email address on Shopify.
            // Mail::to($shopDetails['email'])->send(new \App\Mail\InstallComplete($user_payload, $random_password));
            return true;
        } catch (\Exception $e) {
            //throw $th;
            \Log::error($e->getMessage().' Line Number : '.$e->getLine());
            return false;
        }
    }
    public function completeInstallation(Request $request) {
        //At this point the installation is complete so redirect the browser to either the login or anywhere u want.
        echo "Installation complete !!";exit;
    }
    private function getShopDetailsFromShopify($accessToken, $shop) {
        try {
            //code...
            $endpoint = getShopifyURLForStore('shop.json', ['myshopify_domain' => $shop]);
            $headers = getShopifyHeadersForStore(['access_token' => $accessToken]);
            $response = $this->makeAnAPICallToShopify('GET', $endpoint, null, $headers);
            if ($response['statusCode'] == 200) {
                $body = $response['body'];
                if (!is_array($body)) $body = json_decode($body, true);
                return $body['shop'] ?? null;
            } else {
                \Log::info('Response Recieved for shop detaiils');
                \Log::info($response);
                return null;
            }

        } catch (\Exception $e) {
            //throw $th;
            \Log::error($e->getMessage.' Line Number : '.$e->getLine());
            return null;
        }
    }
    private function requestAccessTokenFromShopifyForThisStore($shop, $code) {
        try {
            $endpoint = 'https://' . $shop . '/admin/oauth/access_token';
            $headers = ['Content-Type: application/json']; // Fix: Remove the space before colon
    
            $requestBody = json_encode([
                'client_id' => config('custom.shopify_api_key'),
                'client_secret' => config('custom.shopify_api_secret'),
                'code' => $code,
            ]);
    
            $response = $this->makeAPOSTCallToShopify($requestBody, $endpoint, $headers);
    
            \Log::info('Request body for getting the access token');
            \Log::info($requestBody);
    
            \Log::info('Response for getting the access token');
            \Log::info(json_encode($response));
    
            if ($response['statusCode'] == 200) {
                $body = $response['body'];
                
                if (!is_array($body)) {
                    $body = json_decode($body, true);
                }
    
                if (is_array($body) && isset($body['access_token']) && $body['access_token'] !== null) {
                    \Log::info('Access Token: ' . $body['access_token']);
                    return $body['access_token'];
                }
            }
    
            return false;
        } catch (\Exception $e) {
            \Log::error($e->getMessage() . ' ' . $e->getLine());
            return false; // Return false in case of an exception
        }
    }
    
}
