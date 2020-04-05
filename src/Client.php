<?php
namespace Shopify;

use Shopify\Common\ClientInterface;
use Shopify\Exception\ApiException;

/**
 * Class Client
 */
class Client implements ClientInterface
{
    /**
     * define constant for current Shopify api version
     */
    const SHOPIFY_API_VERSION = '2020-01';

    /**
     * define rest api call
     */
    const REST_API = 'rest';

    /**
     * header parameter of shopify access token
     */
    const SHOPIFY_ACCESS_TOKEN = 'X-Shopify-Access-Token';

    /**
     * response header parameter of shopify api limit
     */
    const SHOPIFY_API_LIMIT_HEADER = 'http_x_shopify_shop_api_call_limit';

    /**
     * define graphQL api call
     */
    const GRAPHQL = 'graphql';

    /**
     * @var string
     * Shopify graphql base url
     */
    protected $graphql_api_url = "https://{shopify_domain}/admin/api/{version}/graphql.json";

    /**
     * @var string
     * Shopify domain name
     */
    protected $shop;

    /**
     * @var string
     * Shopify api key
     */
    protected $api_key;

    /**
     * @var string
     * Shopify password for private app
     */
    protected $password;

    /**
     * @var string
     * Shopify shared secret key for private app
     */
    protected $shared_secret;

    /**
     * @var array
     * array of version
     */
    protected $api_params;

    /**
     * @var array
     * Shopify api call url
     */
    protected $base_urls;

    /**
     * @var array
     * get api header array according to private and public app
     */
    protected $requestHeaders;

    /**
     * @var string
     * Shopify api version
     */
    protected $api_version;

    /**
     * @var string
     * get response header
     */
    private $last_response_headers;

    /**
     *
     */
    public function init(){
        echo "hello";
    }

    /**
     * @param $method
     * @param $query
     * @param array $params
     * @return mixed|void
     * @throws ApiException
     */
    public function call($method, $path , array $params = [])
    {
        $url = $this->base_urls[self::REST_API];
        $options = [];
        $allowed_http_methods = $this->getHttpMethods();
        if(!in_array($method, $allowed_http_methods)){
            throw new ApiException(implode(",",$allowed_http_methods)." http methods are allowed.");
        }
        if(isset($this->requestHeaders[self::REST_API]) && is_array($this->requestHeaders[self::REST_API])) {
            $options['headers'] = $this->requestHeaders[self::REST_API];
        }
        $url=strtr($url, [
            '{resource}' => $path,
        ]);
        if(in_array($method,['GET','DELETE'])) {
            $options['query'] = $params;
        }else {
            $options['json'] = $params;
        }
        return $this->request($method,$url,$options);
    }

    public function callGraphql($method, $query)
    {
        $url = $this->base_urls[self::GRAPHQL];
        $options = [];
        $allowed_http_methods = $this->getHttpMethods();
        if(!in_array($method, $allowed_http_methods))
        {
            throw new ApiException(implode(",",$allowed_http_methods)." http methods are allowed.");
        }
        if(isset($this->requestHeaders[self::GRAPHQL]) && is_array($this->requestHeaders[self::GRAPHQL])) {
            $options['headers'] = $this->requestHeaders[self::GRAPHQL];
        }
        $options['body'] = json_encode([
            'query' => $query,
        ]);
       // var_dump($options);die;
        return $this->request($method,$url,$options);
    }

    public function request($method,$url,array $options)
    {
        $client  = new \GuzzleHttp\Client();
        $http_response = $client->request($method, $url, $options);
        echo "<pre>";
        print_r($http_response->getBody()->getContents());
        print_r($http_response->getHeader(self::SHOPIFY_API_LIMIT_HEADER));
        print_r($http_response->getStatusCode());
        print_r($http_response->getHeaders());
        die;
    }


    /**
     * @return string
     * @throws ApiException
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    protected function setApiVersion()
    {
        $this->api_version = !empty($this->api_params['version'])?$this->api_params['version']:self::SHOPIFY_API_VERSION;
        if (!preg_match('/^[0-9]{4}-[0-9]{2}$|^unstable$/', $this->api_version))
        {
            throw new ApiException('Api Version must be of YYYY-MM or unstable');
        }
    }

    /**
     * @return array
     */
    public function getHttpMethods()
    {
        return ['POST', 'PUT','GET', 'DELETE'];
    }

    protected function setShop($shop)
    {
        if (!preg_match('/^[a-zA-Z0-9\-]{3,100}\.myshopify\.(?:com|io)$/', $shop)) {
            throw new ApiException(
                'Shop name should be 3-100 letters, numbers, or hyphens eg mypetstore.myshopify.com'
            );
        }
        $this->shop = $shop;
    }

    public function getShop()
    {
        return $this->shop;
    }
}
