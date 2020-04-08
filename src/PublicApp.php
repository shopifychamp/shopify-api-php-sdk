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
     * Shopify rest base url
     * @var string
     */
    private $rest_api_url = 'https://{shopify_domain}/admin/api/{version}/{resource}.json';

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
        $this->requestHeaders();
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
     * @return array
     */
    public function requestHeaders()
    {
        $this->requestHeaders[self::REST_API]['Content-Type'] = "application/json";
        $this->requestHeaders[self::REST_API][self::SHOPIFY_ACCESS_TOKEN] = $this->access_token;
        $this->requestHeaders[self::GRAPHQL]['Content-Type'] = "application/graphql";
        $this->requestHeaders[self::GRAPHQL][self::SHOPIFY_ACCESS_TOKEN] = $this->password;
    }
}