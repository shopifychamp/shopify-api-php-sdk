<?php

namespace Shopify\Test;
use Shopify\Exception\ApiException;
use Shopify\PublicApp;
use Shopify\Test\TestCase;

class PublicAppTest extends TestCase
{
    public function setUp() : void
    {
        $this->testClient = new PublicApp(
            $this->testShop(),
            $this->testApiKey(),
            $this->testApiSecretKey(),
            ['version'=>$this->testApiVersion()]
        );
        $this->testClient->setShop($this->testShop());
        $this->testClient->setApiKey($this->testApiKey());
        $this->testClient->setApiSecretKey($this->testApiSecretKey());
        $this->testClient->setState('foo');
    }

    public function tearDown() : void
    {
        unset($this->testClient);
    }

    public function testState()
    {
        $this->assertEquals('foo',$this->testClient->getState());
    }

    /**
     * validate state
     * get access token with code and hmac
     * validate hmac
     * set access-token
     */
    public function testAccessToken()
    {
        $api_data = ['code'=>'foo','hmac'=>'foobarfoobar','state'=>'foo'];
        $this->assertTrue($this->testClient->validateState($api_data));
        $this->assertFalse($this->testClient->validateHmac($api_data,$api_data['hmac']));
        $this->testClient->setAccessToken('foobar');
        //exception Hmac validation failed
        $this->expectException(ApiException::class);
        $this->testClient->getAccessToken($api_data);
    }

    /**
     * prepare oauth url with redirect uri
     * set scope and request_uri
     * @return void
     */
    public function testAuthUrl()
    {
        $redirect_uri = 'test.com';
        $scope = 'foo';

        $test_oauth_url = 'https://'.$this->testClient->getShop().'/admin/oauth/authorize?client_id='.$this->testClient->getApiKey().'&scope='.$scope.'&redirect_uri='.$redirect_uri.'&state='.$this->testClient->getState();
        $this->assertEquals(
            $test_oauth_url,
            $this->testClient->prepareAuthorizeUrl($redirect_uri, $scope, $this->testClient->getState())
        );
    }

    
}