<?php
namespace Shopify\Common;

/**
 * Interface AppInterface
 * @package Shopify
 */
interface AppInterface
{
    /**
     * @return string
     */
    public function prepareBaseUrl();

    /**
     * @return array
     */
    public function requestHeaders();
}