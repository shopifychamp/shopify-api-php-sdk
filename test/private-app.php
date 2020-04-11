<?php 
require(__DIR__ . '/../vendor/autoload.php');

use Shopify\PrivateApp;
use rdx\graphqlquery\Query;

$shop = 'jewellerystones.myshopify.com';
$api_key = '4ba2e1a4c6324358ef8d51995b59b8d8';
$password = '21045bc023f2af12733de9f4b737d865';
$api_params['version'] = '2019-07';
try
{
    $client = new Shopify\PrivateApp($shop, $api_key, $password, $api_params);

    /**rest api call**/
    $response = $client->call('GET','products',['limit'=>20]);
    echo "<pre>";print_r($response);
    if($client->hasNextPage())
    {
        $next_page_response = $client->call('GET','products',['limit'=>20,'page_info'=>$client->getNextPage()]);
        print_r($next_page_response);
    }

    /* examples :
    1. Call get product by id
    $query = '{
          products(first: 250) {
            edges {
              cursor
              node {
                id
              }
            }
          }
        }';
        $query = Query::query("");
        $query->fields('products');
        $query->product->attribute('id', "gid://shopify/Product/1432379031652");
        $query->product->field('title');
        $graphqlString = $query->build();
    2. Create new customer in shopify
    mutation {
      customerCreate(input: { firstName: "John", lastName: "Tate", email: "john@johns-apparel.com" }) {
        customer {
          id
        }
      }
    }*/
    /*$query = Query::mutation();
    $query->fields('customerCreate');
    $query->customerCreate->attribute('input',['firstName'=>'John','lastName'=> "Tate", 'email'=> "john@johns-apparel.com"]);
    $query->customerCreate->field('customer');
    $query->customerCreate->customer->field('id');
    $graphqlString = $query->build();*/

    $query = Query::query("");
    $query->fields('products');
    $query->products->attribute('first', 250);
    $query->products->field('edges');
    $query->products->edges->fields(['cursor','node']);
    $query->products->edges->node->fields(['title','description']);
    $reserve_query = $query;
    $graphqlString = $query->build();
    $response = $client->callGraphql($graphqlString);
    if(isset($response['data']['products']['edges']) && $last_array = end($response['data']['products']['edges']))
    {
        if(isset($last_array['cursor'])){
            $query = $reserve_query->products->attribute('after', $last_array['cursor']);
            $graphqlString = $reserve_query->build();
            $next_response = $client->callGraphql($graphqlString);
            print_r($next_response);
        }


    }
}
catch (\Shopify\Exception\ApiException $e)
{
    echo "Errors: ".$e->getError().'<br> status code: '.$e->getCode();
}

?>