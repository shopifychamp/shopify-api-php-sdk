<?php
namespace Shopify;
use Shopify\Common\AppInterface;
use Shopify\Exception\ApiException;

/**
 * Class PublicApp
 * @package Shopify
 */
class PublicApp extends Client implements AppInterface
{
    /**
     * define scope for api access
     */
    const SCOPE = 'read_products,read_orders';

    /**
     * Shopify rest base url
     * @var string
     */
    private $rest_api_url = 'https://{shopify_domain}/admin/api/{version}/{resource}.json';

    /**
     *
     * @var string
     */
    private $oauth_url = 'https://{shopify_domain}/admin/oauth/';

    /**
     * random unique value for each authorization request
     * @var state
     */
    private $state;

    /**
     * PublicApp constructor.
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
        $this->setApiVersion();
        $this->prepareBaseUrl();
    }

    /**
     * return Shopify base api url for rest and graphql
     * @return string
     */
    public function prepareBaseUrl()
    {
        $this->base_urls = [
            self::GRAPHQL => strtr($this->graphql_api_url, [
                '{shopify_domain}' => $this->shop, '{version}' => $this->getApiVersion()
            ]),
            self::REST_API => strtr($this->rest_api_url, [
                '{shopify_domain}' => $this->shop,
                '{version}' => $this->getApiVersion(),
            ])
        ];
    }

    /**
     * request header array for rest and graphql api call
     * @return array|void
     */
    public function requestHeaders()
    {
        $this->requestHeaders[self::REST_API]['Content-Type'] = "application/json";
        $this->requestHeaders[self::REST_API][self::SHOPIFY_ACCESS_TOKEN] = $this->access_token;
        $this->requestHeaders[self::GRAPHQL]['Content-Type'] = "application/graphql";
        $this->requestHeaders[self::GRAPHQL][self::SHOPIFY_ACCESS_TOKEN] = $this->access_token;
    }

    /**
     * assign access token for api call
     * @param $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token =  $access_token;
        $this->requestHeaders();
    }

    /**
     * prepare url to authorize public app with Oauth for given shop domain
     * @param $scope
     * @param null $redirect_url
     * @param $state
     * @return false|string
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
            $authorise_url.='&state='.$this->state;
        }
        return $authorise_url;
    }

    /**
     * Once the User has authorized the app, call to get the access token
     * @param $get_params
     * @return mixed
     * @throws ApiException
     */
    public function getAccessToken($get_params)
    {
        if(isset($get_params['code'],$get_params['hmac']))
        {
            if(isset($get_params['state']) && !$this->validateState($get_params))
                throw new ApiException("Previous state value('".$this->state."') doesn't match with current value('".$get_params['state']."')",0);
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
     * set random unique value for authorization request
     * @param $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * HMAC verification procedure for OAuth/webhooks
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
     * check random value same with previous value set for authorization request
     * @param $state
     * @return bool
     */
    public function validateState($params)
    {
        if($params['state'] === $this->state)
            return true;
        return true;
    }
}