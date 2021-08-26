<?php
namespace Shopify;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Shopify\Common\ClientInterface;
use Shopify\Exception\ApiException;

/**
 * Class Client
 * @package Shopify
 */
class Client implements ClientInterface
{
    /**
     * Define constant for current Shopify api version
     */
    const SHOPIFY_API_VERSION = '2021-07';

    /**
     * Define rest api call
     */
    const REST_API = 'rest';

    /**
     * Define private app
     */
    const PRIVATE_APP = 'private';

    /**
     * Header parameter of shopify access token
     */
    const SHOPIFY_ACCESS_TOKEN = 'X-Shopify-Access-Token';

    /**
     * Header parameter of shopify access token
     */
    const SHOPIFY_STOREFRONT_ACCESS_TOKEN = 'X-Shopify-Storefront-Access-Token';

    /**
     * Define response header pagination string
     */
    const PAGINATION_STRING = 'Link';

    /**
     * Response header parameter of shopify api limit
     */
    const API_CALL_RATE_LIMIT_HEADER = 'http_x_shopify_shop_api_call_limit';

    /**
     * Define graphQL api call
     */
    const GRAPHQL = 'graphql';

    /**
     * Shopify graphql base url
     *
     * @var string
     */
    protected $graphql_api_url = "https://{shopify_domain}/admin/api/{version}/graphql.json";

    /**
     * Shopify api type either admin API or Storefront API
     *
     * @var string
     */
    protected $api_type = "admin";

    /**
     * Rest api url for custom/public app
     *
     * @var string
     */
    protected $rest_api_url = 'https://{shopify_domain}/admin/api/{version}/{resource}.json';

    /**
     * Shopify domain name
     *
     * @var string
     */
    protected $shop;

    /**
     * Shopify api key
     *
     * @var string
     */
    protected $api_key;

    /**
     * Shopify password for private app
     *
     * @var string
     */
    protected $password;

    /**
     * Shopify shared secret key for private app
     *
     * @var string
     */
    protected $api_secret_key;

    /**
     * access token for public app
     *
     * @var string
     */
    protected $access_token;

    /**
     * array('version')
     *
     * @var array
     */
    protected $api_params;

    /**
     * Shopify api call url
     *
     * @var array
     */
    protected $base_urls;

    /**
     * Get api header array according to private and public app for rest api
     * @var array
     */
    protected $restApiRequestHeaders;

    /**
     * Get api header array according to private and public app for graphql api
     * @var array
     */
    protected $graphqlApiRequestHeaders;

    /**
     * Shopify api version
     * @var string
     */
    protected $api_version;

    /**
     * Get response header
     * @var string
     */
    protected $next_page;

    /**
     * Get response header
     * @var string
     */
    protected $prev_page;

    /**
     * Static variable to api is going to reach
     * @var bool
     */
    protected static $wait_next_api_call = false;

    /**
     * @param $method
     * @param $path
     * @param array $params
     * @return mixed
     * @throws ApiException
     * @throws GuzzleException
     */
    public function call($method, $path , array $params = [])
    {
        $url = $this->getRestApiUrl();
        $options = [];
        $allowed_http_methods = $this->getHttpMethods();
        if(!in_array($method, $allowed_http_methods)){
            throw new ApiException(implode(",",$allowed_http_methods)." http methods are allowed.",0);
        }
        if(is_array($this->getRestApiHeaders()) && count($this->getRestApiHeaders())) {
            $options['headers'] = $this->getRestApiHeaders();
        }

        // Change url in case of access_scopes
        if($path == 'access_scopes'){
            $url = $this->apiScopeUrl($url);
        }

        $url=strtr($url, [
            '{resource}' => $path,
        ]);
        if(in_array($method,['GET','DELETE'])) {
            $options['query'] = $params;
        } else {
            $options['json'] = $params;
        }

        if(self::$wait_next_api_call) {
            usleep(1000000 * rand(3, 6));
        }

        $http_response = $this->request($method,$url,$options);
        if (strtoupper($method) === 'GET'  && $http_response->getHeaderLine(self::PAGINATION_STRING)) {
            $this->next_page = $this->parseLinkString($http_response->getHeaderLine(self::PAGINATION_STRING),'next');
            $this->prev_page = $this->parseLinkString($http_response->getHeaderLine(self::PAGINATION_STRING),'previous');
        }
        if($http_response->getHeaderLine(self::API_CALL_RATE_LIMIT_HEADER)) {
            list($api_call_requested, $api_call_Limit) = explode('/', $http_response->getHeaderLine(self::API_CALL_RATE_LIMIT_HEADER));
            static::$wait_next_api_call = $api_call_requested / $api_call_Limit >= 0.8;
        }
        return \GuzzleHttp\json_decode($http_response->getBody()->getContents(),true);
    }

    /**
     * Prepare data for graphql api request
     *
     * @param string $url
     * @return string
     *
     */
    public function apiScopeUrl($url)
    {
        return str_replace('api/'.$this->getApiVersion().'/', 'oauth/', $url);
    }

    /**
     * Prepare data for graphql api request
     *
     * @param string $query
     * @return mixed
     * @throws ApiException
     * @throws GuzzleException
     */
    public function callGraphql($query)
    {
        $url = $this->getGraphqlApiUrl();
        $options = [];
        if(is_array($this->getGraphqlApiHeaders()) && count($this->getGraphqlApiHeaders()))   {
            $options['headers'] = $this->getGraphqlApiHeaders();
        }
        $options['body'] = $query;
        $http_response = $this->request('POST', $url, $options);
        $response = \GuzzleHttp\json_decode($http_response->getBody()->getContents(),true);
        if(isset($response['errors']))
        {
            $http_bad_request_code = 400;

            $error_message = $response['errors'];
            if(is_array($response['errors']))
                $error_message = json_encode($response['errors']);

            throw new ApiException($error_message,$http_bad_request_code);
        }
        return $response;
    }

    /**
     * Send http request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     * @throws ApiException|GuzzleException
     */
    public function request($method,$url,array $options)
    {
        try
        {
            $client  = new \GuzzleHttp\Client();
            return $client->request($method, $url, $options);
        }
        catch (RequestException $e)
        {
            $json_error = json_decode($e->getResponse()->getBody()->getContents(),true);
            $error_message = $json_error['errors'] ?? $e->getMessage();
            if(is_array($error_message))
                $error_message = json_encode($error_message);
            throw new ApiException($error_message,$e->getCode());
        }
    }

    /**
     * Get previous page_info for any resource(products/orders)
     *
     * @return string
     */
    public function getPrevPage()
    {
        return $this->prev_page;
    }

    /**
     * Check previous page_info for any resource(products/orders)
     *
     * @return bool
     */
    public function hasPrevPage()
    {
        return !empty($this->prev_page);
    }

    /**
     * Get next page_info for any resource(products/orders)
     *
     * @return string
     */
    public function getNextPage()
    {
        return $this->next_page;
    }

    /**
     * Check next page_info for any resource(products/orders)
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return !empty($this->next_page);
    }

    /**
     * Parse header string for previous and next page_info
     *
     * @param $pagination_string
     * @param $page_link
     * @return string
     */
    public function parseLinkString($pagination_string,$page_link)
    {
        $matches = [];
        preg_match("/<(.*page_info=([a-z0-9\-]+).*)>; rel=\"?{$page_link}\"?/i", $pagination_string, $matches);
        return isset($matches[2]) ? $matches[2] : NULL;
    }

    /**
     * Return allowed http api methods
     *
     * @return array
     */
    public function getHttpMethods()
    {
        return ['POST', 'PUT','GET', 'DELETE'];
    }

    /**
     * Set shopify domain
     *
     * @param $shop
     * Exception for invalid shop name
     * @throws ApiException
     */
    public function setShop($shop)
    {
        if (!preg_match('/^[a-zA-Z0-9\-]{3,100}\.myshopify\.(?:com|io)$/', $shop)) {
            throw new ApiException(
                'Shop name should be 3-100 letters, numbers, or hyphens eg mypetstore.myshopify.com',0
            );
        }
        $this->shop = $shop;
    }

    /**
     * Return latest api version
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * Set api version
     *
     * @param api_version
     * Exception for valid value
     * @throws ApiException
     */
    public function setApiVersion($ap_params)
    {
        $this->api_version = !empty($ap_params['version'])?$ap_params['version']:self::SHOPIFY_API_VERSION;
        if (!preg_match('/^[0-9]{4}-[0-9]{2}$|^unstable$/', $this->api_version))
        {
            throw new ApiException('Api Version must be of YYYY-MM or unstable',0);
        }
    }

    /**
     * Return Shopify domain
     *
     * @return string
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * Set new graphql api url for storefront API
     *
     * @param array
     */
    public function setStoreFrontApi($api_params)
    {
        // Change graphql url and api_type for Storefront API
        if (isset($api_params['api_type']) && $api_params['api_type'] == 'storefront') {
            $this->graphql_api_url = 'https://{shopify_domain}/api/{version}/graphql.json';
            $this->api_type = 'storefront';
        }
    }

    /**
     * Set rest api url
     *
     * @param $rest_api_url
     * @param $app_type
     */
    public function setRestApiUrl($rest_api_url, $app_type = '')
    {
        if($app_type == self::PRIVATE_APP)
        {
            $this->rest_api_url = strtr($rest_api_url, [
                '{api_key}' => $this->api_key,
                '{password}' => $this->password,
                '{shopify_domain}' => $this->shop,
                '{version}' => $this->getApiVersion(),
            ]);
        }else{
            $this->rest_api_url = strtr($rest_api_url, [
                '{shopify_domain}' => $this->shop,
                '{version}' => $this->getApiVersion(),
            ]);
        }
    }

    /**
     * Get rest api url app
     *
     * @return string
     */
    public function getRestApiUrl()
    {
        return $this->rest_api_url;
    }

    /**
     * Set graphql api url
     *
     * @param $graphql_api_url
     */
    public function setGraphqlApiUrl($graphql_api_url)
    {
        $this->graphql_api_url = strtr($graphql_api_url, [
            '{shopify_domain}' => $this->shop, '{version}' => $this->getApiVersion()
        ]);
    }

    /**
     * Get graphql api url
     *
     * @return string
     */
    public function getGraphqlApiUrl()
    {
        return $this->graphql_api_url;
    }

    /**
     * Set rest api headers
     *
     * @return void
     */
    public function setRestApiHeaders($access_token = '')
    {
        $this->restApiRequestHeaders['Content-Type'] = "application/json";
        if($access_token){
            $this->restApiRequestHeaders[self::SHOPIFY_ACCESS_TOKEN] = $this->access_token;
        }
    }


    /**
     * Get rest api headers
     *
     * @return array
     */
    public function getRestApiHeaders()
    {
        return $this->restApiRequestHeaders;
    }

    /**
     * Get graphql api url
     *
     * @param $access_token
     */
    public function setGraphqlApiHeaders($access_token)
    {
        $this->graphqlApiRequestHeaders['Content-Type'] = "application/graphql";
        $this->graphqlApiRequestHeaders['X-GraphQL-Cost-Include-Fields'] = true;

        // Set api token header according to api type either admin or storefront API
        $token_header = $this->api_type == 'storefront' ? self::SHOPIFY_STOREFRONT_ACCESS_TOKEN
            : self::SHOPIFY_ACCESS_TOKEN;
        $this->graphqlApiRequestHeaders[$token_header] = $access_token;
    }

    /**
     * Get graphql api url
     *
     * @return array
     */
    public function getGraphqlApiHeaders()
    {
        return $this->graphqlApiRequestHeaders;
    }


    /**
     * Set api_key of public or private app
     *
     * @param $api_key
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * Get api_key of public or private app
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * Set api_key of public or private app
     *
     * @param $api_password
     */
    public function setApiPassword($api_password)
    {
        $this->password = $api_password;
    }

    /**
     * Get api_password of private app
     *
     * @return string
     */
    public function getApiPassword()
    {
        return $this->password;
    }

    /**
     * Set api secret key for public app
     *
     * @param $api_secret_key
     */
    public function setApiSecretKey($api_secret_key)
    {
        $this->api_secret_key = $api_secret_key;
    }

    /**
     * Get api secret key for public app
     *
     * @return string
     */
    public function getApiSecretKey()
    {
        return $this->api_secret_key;
    }

    /**
     * Set api_params of public or private app
     *
     * @param  $api_params
     */
    public function setApiParams($api_params)
    {
        $this->api_params = $api_params;
    }

    /**
     * Get api_params of public or private app
     *
     * @return string
     */
    public function getApiParams()
    {
        return $this->api_params;
    }
}
