<?php

require(__DIR__ . '/../vendor/autoload.php');

use Shopify\PublicApp;
use rdx\graphqlquery\Query;

$shop = 'jewellerystones.myshopify.com';
$api_key = '7776d626ae278fdf33cbc04109209743';
$api_secret_key = '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';
$api_params['version'] = '2019-10';

try
{
    $client = new PublicApp($shop,$api_key,$api_secret_key,$api_params);
    if(isset($_GET['code']))
    {
        if($access_token = $client->getAccessToken($_GET)){
            $client->setAccessToken($access_token);

        }
    }else{
        $redirect_url= isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"?'https://':'http://';
        if ($_SERVER["SERVER_PORT"] != "80") {
            $redirect_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $redirect_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }

        header('Location: '.urldecode($client->prepareAuthorizeUrl($redirect_url)));
    }


}catch (\Shopify\Exception\ApiException $e){
    echo "Errors: ".$e->getError().'<br> status code: '.$e->getCode();
}