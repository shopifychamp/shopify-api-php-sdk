<?php
namespace Shopify\Common;

/**
 * Interface AppInterface
 * @package Shopify
 */
interface AppInterface
{
    /**
     * AppInterface constructor.
     * @param $shop
     * @param $api_key
     * @param $password or $api_secret_key
     * @param array $api_params
     */
    public function __construct($shop, $api_key, $password, array $api_params);
}