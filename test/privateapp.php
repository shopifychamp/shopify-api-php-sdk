<?php
require(__DIR__ . '/../vendor/autoload.php');

use Shopify\PrivateApp;
use rdx\graphqlquery\Query;

$shop = 'jewellerystones.myshopify.com';
$api_key = '4ba2e1a4c6324358ef8d51995b59b8d8';
$password = '21045bc023f2af12733de9f4b737d865';
$api_params['version'] = '2019-07';
try {
    $client = new Shopify\PrivateApp($shop, $api_key, $password, $api_params);
    /**rest api call**/
    $response = $client->call('GET', 'products', ['limit' => 1]);
    if($client->hasNextPage()){
        $response = $client->call('GET','products',[
            'limit'=>20,
            'page_info'=>$client->getNextPage()
        ]);
        print_r($response);
    }

}
catch (\Shopify\Exception\ApiException $e)
{
    echo "Errors: ".$e->getError().'<br> status code: '.$e->getCode();
}
