<?php
namespace Shopify\Common;

/**
 * Interface ClientInterface
 * @package Shopify
 */
interface ClientInterface
{
    /**
     * @param $method
     * @param $query
     * @param array $params
     * @return mixed
     */
	public function call($method, $path, array $params);

    /**
     * @param string $method
     * @param string $query
     * @return mixed
     */
    public function callGraphql($query);

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return mixed
     */
    public function request($method, $url, array $options);

    /**
     * @return mixed
     * Get latest version of shopify
     */
	public function getApiVersion();

	public function getHttpMethods();
}