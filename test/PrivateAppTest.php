<?php
namespace Shopify\Test;
use Shopify\PrivateApp;

class PrivateAppTest extends TestCase
{
    public function setUp() : void
    {
        $this->testClient = new PrivateApp(
            $this->testShop(),
            $this->testApiKey(),
            $this->testApiPassword(),
            ['version'=>$this->testApiVersion()]
        );
        $this->testClient->setShop($this->testShop());
        $this->testClient->setApiKey($this->testApiKey());
        $this->testClient->setApiPassword($this->testApiPassword());
    }

    public function tearDown() : void
    {
        unset($this->testClient);
    }

    public function testGetterMethods()
    {
        $this->assertEquals($this->testShop(), $this->testClient->getShop());
        $this->assertEquals($this->testApiKey(), $this->testClient->getApiKey());
        $this->assertEquals($this->testApiPassword(), $this->testClient->getApiPassword());
    }
}