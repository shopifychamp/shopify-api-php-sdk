<?php

namespace Shopify;
use GuzzleHttp\Exception\GuzzleException;
use Shopify\Common\AppInterface;
use Shopify\Exception\ApiException;

/**
 * Class PublicApp
 * @package Shopify
 */
class PublicApp extends Client implements AppInterface
{
    /**
     * Define scope for api access
     */
    const SCOPE = 'read_products,read_orders';

    /**
     * Define Shopify oauth url for access scopes
     *
     * @var string
     */
    private $oauth_url = 'https://{shopify_domain}/admin/oauth/';

    /**
     * Random unique value for each authorization request
     *
     * @var string
     */
    private $state;

    /**
     * PublicApp constructor
     *
     * Shopify domain => testshop.myshopify.com
     * @param $shop
     * Shopify api key
     * @param $api_key
     * Shopify api secret key
     * @param $api_secret_key
     * ['version'=>'2020-01']
     * @param array $api_params
     * @throws ApiException
     */
    public function __construct($shop, $api_key, $api_secret_key, array $api_params = [])
    {
        $this->setShop($shop);
        $this->api_key = $api_key;
        $this->api_secret_key = $api_secret_key;
        $this->api_params = $api_params;
        $this->setApiVersion($this->api_params);
        $this->setGraphqlApiUrl($this->graphql_api_url);
        $this->setRestApiUrl($this->rest_api_url);
    }

    /**
     * Assign access token for api call
     *
     * @param $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token =  $access_token;
        $this->setRestApiHeaders($this->access_token);
        $this->setGraphqlApiHeaders($this->access_token);
    }

    /**
     * Once the User has authorized the app, call to get the access token
     *
     * @param $get_params
     * @return mixed|void
     * @throws ApiException
     * @throws GuzzleException
     */
    public function getAccessToken($get_params)
    {
        if(isset($get_params['code'],$get_params['hmac']))
        {
            if(isset($get_params['state']) && !$this->validateState($get_params))
                throw new ApiException("Previous state value('".$this->getState()."') doesn't match with current value('".$get_params['state']."')",0);
            if(!$this->validateHmac($get_params,$get_params['hmac']))
                throw new ApiException("Hmac validation failed",0);
            $access_token_url = $this->oauth_url.'access_token';
            $access_token_url = strtr($access_token_url,['{shopify_domain}'=> $this->shop]);
            $params['client_id'] = $this->api_key;
            $params['client_secret'] = $this->api_secret_key;
            $params['code'] = $get_params['code'];
            $http_response = $this->request('POST', $access_token_url, ['query'=>$params]);
            $response = \GuzzleHttp\json_decode($http_response->getBody()->getContents(),true);
            if(isset($response['access_token']))
            {
                $this->setAccessToken($response['access_token']);
                return $response['access_token'];
            }
        }
        else {
            throw new ApiException('Unable to authorise app, check your credentials',0);
        }

    }

    /**
     * Prepare url to authorize public app with Oauth for given shop domain
     *
     * @param $scope
     * @param null $redirect_url
     * @param $state
     * @return string
     */
    public function prepareAuthorizeUrl($redirect_url='', $scope='', $state=false)
    {
        /*&redirect_uri={redirect_uri}&state={nonce}*/
        $authorise_url = $this->oauth_url.'authorize?client_id={api_key}&scope={scopes}';
        $authorise_url = strtr($authorise_url, [
                '{shopify_domain}'=> $this->shop,
                '{api_key}'=> $this->api_key,
                '{scopes}'=> !empty($scope)?$scope:self::SCOPE
            ]
        );
        if($redirect_url){
            $authorise_url.='&redirect_uri='.$redirect_url;
        }
        if($state){
            $this->setState($state);
            $authorise_url.='&state='.$this->getState();
        }
        return $authorise_url;
    }

    /**
     * Set random unique value for authorization request
     *
     * @param $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get random unique value for authorization request
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * HMAC verification procedure for OAuth/webhooks
     *
     * @param $data
     * @param $hmac
     * @return bool
     */
    public function validateHmac($data, $hmac)
    {
        if(isset($data['hmac'])) {
            unset($data['hmac']);
            array_values($data);
        }

        return ($hmac === hash_hmac('sha256', is_array($data) ? http_build_query($data) : $data, $this->api_secret_key));
    }

    /**
     * Check random value same with previous value set for authorization request
     *
     * @param $params
     * @return bool
     */
    public function validateState($params)
    {
        if($params['state'] === $this->getState())
            return true;
        return false;
    }
}