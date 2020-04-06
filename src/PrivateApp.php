<?php
namespace Shopify;
use Shopify\Common\AppInterface;
use Shopify\Exception\ApiException;

/**
 * Class PrivateApp
 * @package Shopify
 */
class PrivateApp extends Client implements AppInterface
{
    /**
     * Shopify rest base url
     * @var string
     */
    private $rest_api_url = 'https://{api_key}:{password}@{shopify_domain}/admin/api/{version}/{resource}.json';

    /**
     * PrivateApp constructor.
     * Shopify url : testshop.myshopify.com
     * @param $shop
     * Shopify api key of private app
     * @param $api_key
     * Shopify password of private app
     * @param $password
     * ['version'=>'2020-01']
     * @param array $api_params
     * @throws ApiException
     */
    public function __construct($shop, $api_key, $password, array $api_params = [])
    {
        try{
            $this->setShop($shop);
            $this->api_key = $api_key;
            $this->password = $password;
            $this->api_params = $api_params;
            $this->setApiVersion();
            $this->prepareBaseUrl();
            $this->requestHeaders();
        }
        catch (ApiException $e)
        {
            echo "Uncaught exception ".$e->getMessage();
            exit();
        }
    }

    /*
     * return Shopify base api url based on api call type
     * @param array
     */
    public function prepareBaseUrl()
    {
        $this->base_urls = [
            self::GRAPHQL => strtr($this->graphql_api_url, [
                '{shopify_domain}' => $this->shop, '{version}' => $this->getApiVersion()
            ]),
            self::REST_API => strtr($this->rest_api_url, [
                '{api_key}' => $this->api_key,
                '{password}' => $this->password,
                '{shopify_domain}' => $this->shop,
                '{version}' => $this->getApiVersion(),
            ])
        ];
    }

    /**
     * get request headers for api call
     * @return array
     */
    public function requestHeaders()
    {
        $this->requestHeaders[self::REST_API]['Content-Type'] = "application/json";
        $this->requestHeaders[self::GRAPHQL]['Content-Type'] = "application/graphql";
        $this->requestHeaders[self::GRAPHQL]['X-GraphQL-Cost-Include-Fields'] = true;
        $this->requestHeaders[self::GRAPHQL][self::SHOPIFY_ACCESS_TOKEN] = $this->password;
    }
}