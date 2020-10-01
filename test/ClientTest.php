<?php

namespace Shopify\Test;

use Shopify\PrivateApp;

/**
 * Class PrivateAppTest
 * @package Shopify
 */
class ClientTest extends TestCase
{
    public function setUp() : void
    {
        $this->testClient = new PrivateApp(
            $this->testShop(),
            $this->testApiKey(),
            $this->testApiSecretKey(),
            ['version'=>$this->testApiVersion()]
        );
    }

    public function tearDown() : void
    {
        unset($this->client);
        unset($this->testClient);
    }

    public function testClient()
    {
        $this->assertClassHasAttribute('shop','Shopify\PrivateApp');
        $this->assertClassHasAttribute('api_key','Shopify\PrivateApp');
        $this->assertClassHasAttribute('password','Shopify\PrivateApp');
        $this->assertClassHasAttribute('api_params','Shopify\PrivateApp');
        $this->assertClassHasAttribute('rest_api_url','Shopify\PrivateApp');
        $this->testClient->setShop('foo.myshopify.com');
        $this->testClient->setApiKey('foobarfoobar123');
        $this->testClient->setApiPassword('foobarfoobar123');
        $this->testClient->setApiParams(['version'=>$this->getTestApiVersion()]);
        $this->testClient->setApiVersion($this->testClient->getApiParams());
        $this->testClient->setGraphqlApiUrl($this->getTestGraphqlUrl());
        $this->testClient->setRestApiUrl($this->getTestRestUrl(), 'private');
        $this->testClient->setRestApiHeaders();
        $this->testClient->setGraphqlApiHeaders($this->testClient->getApiPassword());
    }

    public function testShop()
    {
        $this->assertEquals(
            'foo.myshopify.com',
            $this->testClient->getShop()
        );
    }

    public function testApiKey()
    {
        $this->assertEquals(
            'foobarfoobar123',
            $this->testClient->getApiKey()
        );
    }

    public function testApiPassword()
    {
        $this->assertEquals(
            'foobarfoobar123',
            $this->testClient->getApiPassword()
        );
    }

    public function testApiParams()
    {
        $this->assertArrayHasKey(
            'version',
            $this->testClient->getApiParams()
        );
    }

    public function testApiVersion()
    {
        $this->assertEquals(
            $this->getTestApiVersion(),
            $this->testClient->getApiVersion(),
            "api version should be 2020-04"
        );
    }

    public function testGraphqlApiUrl(){
        $this->assertEquals(
            $this->getTestGraphqlUrl(),
            $this->testClient->getGraphqlApiUrl()
        );
    }

    public function testRestApiUrl(){
        $this->assertEquals(
            $this->getTestRestUrl(),
            $this->testClient->getRestApiUrl()
        );
    }

    public function testRestApiHeaders(){
        $this->assertEquals(
            $this->getTestRestApiHeaders(),
            $this->testClient->getRestApiHeaders()
        );
    }

    public function testGraphqlApiHeaders(){
        $this->assertEquals(
            $this->getTestGraphqlApiHeaders(),
            $this->testClient->getGraphqlApiHeaders()
        );
    }

    public function getTestApiVersion(){
        return '2020-04';
    }

    public function getTestGraphqlUrl(){
        return 'https://'.$this->testClient->getShop().'/admin/api/'.$this->testClient->getApiVersion().'/graphql.json';
    }

    public function getTestRestUrl(){
        return 'https://'.$this->testClient->getApiKey()
            .':'.$this->testClient->getApiPassword()
            .'@'.$this->testClient->getShop()
            .'/admin/api/'.$this->testClient->getApiVersion()
            .'/{resource}.json';
    }

    public function getTestRestApiHeaders(){
        return ['Content-Type' => "application/json"];
    }

    public function getTestGraphqlApiHeaders(){
        return [
            'Content-Type'                  =>  "application/graphql",
            'X-GraphQL-Cost-Include-Fields' =>  true,
            'X-Shopify-Access-Token'        =>  $this->testClient->getApiPassword()
        ];
    }
}