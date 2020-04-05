<?php 
require(__DIR__ . '/../vendor/autoload.php');

use Shopify\PrivateApp;
use rdx\graphqlquery\Query;
$shop = 'jewellerystones.myshopify.com';
$api_key = '4ba2e1a4c6324358ef8d51995b59b8d8';
$password = '21045bc023f2af12733de9f4b737d865';
$api_params['api_version'] = 'unstable1';
$client = new Shopify\PrivateApp($shop, $api_key, $password, $api_params);

//$response = $client->call('GET','products',['limit'=>250]);

$query = 'query{
    product(id: "gid://shopify/Product/1432379031652") {
      title
      description
      onlineStoreUrl
    }
  }';

$query = Query::query('product');
$query->fields('scope', 'friends', 'viewer');
$query->friends->attribute('names', ['marc', 'jeff']);

$response = $client->callGraphql('GET', $query);
?>