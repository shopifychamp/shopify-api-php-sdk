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
     * Shopify rest API base url
     * @var string
     */
    protected $rest_api_url = 'https://{api_key}:{password}@{shopify_domain}/admin/api/{version}/{resource}.json';

    /**
     * Initialize shop, API details with version for private APP
     *
     * Shopify url : test-shop.myshopify.com
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
        $this->setShop($shop);
        $this->api_key = $api_key;
        $this->password = $password;
        $this->api_params = $api_params;
        $this->setStoreFrontApi($this->api_params);
        $this->setApiVersion($this->api_params);
        $this->setGraphqlApiUrl($this->graphql_api_url);
        $this->setRestApiUrl($this->rest_api_url, self::PRIVATE_APP);
        $this->setRestApiHeaders();
        $this->setGraphqlApiHeaders($this->password);
    }
}