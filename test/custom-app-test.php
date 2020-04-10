<?php

require(__DIR__ . '/../vendor/autoload.php');

use Shopify\PublicApp;
use rdx\graphqlquery\Query;

$shop = 'jewellerystones.myshopify.com';
$api_key = '183b37775e1ac0b8315aee9a01ba5fd6';
$api_secret_key = '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';
$api_params['version'] = '2019-07';

try
{
    $client = new PublicApp($shop,$api_key,$api_secret_key,$api_params);
    if(isset($_GET['code']))
    {
        if($access_token = $client->getAccessToken($_GET)){
            $client->setAccessToken($access_token);
        }
    }
}catch (\Shopify\Exception\ApiException $e){
    echo "Errors: ".$e->getError().'<br> status code: '.$e->getCode();
}