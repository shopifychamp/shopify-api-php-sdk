# SHOPIFY API PHP SDK

[![Latest Stable Version](https://poser.pugx.org/shopifychamp/shopify-api-php-sdk/v/stable)](https://packagist.org/packages/shopifychamp/shopify-api-php-sdk)
[![License](https://poser.pugx.org/shopifychamp/shopify-api-php-sdk/license)](https://packagist.org/packages/shopifychamp/shopify-api-php-sdk)

PHP SDK helps to connect with shopify [Public App](https://shopify.dev/concepts/apps#public-apps) and [Private App](https://shopify.dev/concepts/apps#private-apps) using [REST Api](https://shopify.dev/docs/admin-api/rest/reference) and [Graphql](https://shopify.dev/docs/admin-api/graphql/reference).
* Call GET, POST, PUT and DELETE RestApi method.
* Process GraphQL Admin API for [Query root](https://shopify.dev/docs/admin-api/graphql/reference/queryroot) and [Mutations](https://shopify.dev/docs/admin-api/graphql/reference/mutation).
* Queryroot is used to get resources and mutations is used to update resources (products/orders/customers). 
* Automatic manage Shopify API rate limits.
* Compatible with [Cursor Based Pagination](https://shopify.dev/tutorials/make-paginated-requests-to-rest-admin-api) to resource with pagination.

## Installation
* Install package with Composer
```
$ composer require shopifychamp/shopify-api-php-sdk
```
## Requirements
1. For Api call need [Guzzle](https://github.com/guzzle/guzzle). The recommended way to install Guzzle is through [Composer](https://getcomposer.org/).
    ```
    $ composer require guzzlehttp/guzzle
    ```
2. To prepare graphql query with [GraphQL query builder](https://github.com/rudiedirkx/graphql-query).
   
    ```
    $ composer require rdx/graphql-query
    ```
## Getting started
### Initialize the client
#### 1.  For Private App
* To create instance of `Client` class, need `shop`, `api_key`, `password` of private app, `api_params` is an array to pass api version with `YYYY-DD/unstable` format otherwise, Api latest version will be assigned.
    
    ```
    <?php 
    require(__DIR__ . '/../vendor/autoload.php');
    use Shopify\PrivateApp;
    
    $api_params['version'] = '2019-10';
    $client = new Shopify\PrivateApp($shop, $api_key, $password, $api_params);
    ```
#### 2. For Public App
* To create instance of `Client` class, need `shop`, `api_key`, `api_secret_key` of public app.
    
    ```    
    <?php 
    require(__DIR__ . '/../vendor/autoload.php');
    use Shopify\PuplicApp;
    
    $api_params['version'] = '2019-10';
    $client = new Shopify\PublicApp($shop, $api_key, $api_secret_key, $api_params);
    ```
* Prepare authorise url to install public app and get `access_token` to store in database or session for future api call.
    ```
    if(isset($_GET['code']))
    {
        //get access_token after authorization of public app
        if($access_token = $client->getAccessToken($_GET)){
            //set access_token for api call
            $client->setAccessToken($access_token);
            $response = $client->call('GET','products',['limit'=>250]);
        }
    }else{
        //$redirect_uri (mention in App URL in your public app)
        $redirect_url= isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"?'https://':'http://';
        if ($_SERVER["SERVER_PORT"] != "80") {
            $redirect_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $redirect_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }

        header('Location: '.urldecode($client->prepareAuthorizeUrl($redirect_url)));
    }
    ```
### Call REST Api    
* Get Products with limit 250  with `call()` function
    ```
    $response = $client->call('GET','products',['limit'=>250]);
    print_r($response);
    ```
* Get products of next page with page_info
    ```
    $response = $client->call('GET','products',['limit'=>250]);
    ```
    
    Check next page available with `hasNextPage()` function: 

    ```
    if($client->hasNextPage())
    {
        $next_page_response = $client->call('GET','products',['limit'=>20,'page_info'=>$client->getNextPage()]);
        print_r($next_page_response);
    }
    ```
    
    Check if previous page available with `hasPrevPage()` function:

    ```
    if($client->hasPrevPage())
    {
        $prev_page_response = $client->call('GET','products',['limit'=>20,'page_info'=>$client->getPrevPage()]);
        print_r($prev_page_response);
    }
    ```
    
### Call GraphQL Api   

* Get product title, description with id by `query()` function
    
    ```
    {
        product(id: "gid://shopify/Product/1432379031652") {
          title
          description
        }
    }
    ```
    Note: 
    * For single attribute and field
    ``` 
        attribute('id','gid://shopify/Product/1432379031652') and field('title')
    ```    
    * For multiple attributes and fields 
    ``` 
    attributes(['product_type','fragrance','limit'=>250]) and `fields(['title','description'])
    ```
    Prepare query:
    
    ```
    <?php 
    use rdx\graphqlquery\Query;
    
    $query = Query::query("");
    $query->fields('product');
    $query->product->attribute('id', "gid://shopify/Product/1432379031652");
    $query->product->fields(['title','description']);
    $graphqlString = $query->build();
    
    ```
    
    Call GraphQL with `callGraphql()` function:
    
    ```
    $response = $client->callGraphql($graphqlString);
    ```
    
* Create customer with graphql `mutation()` function

    ```
    mutation {
      customerCreate(input: { firstName: "John", lastName: "Tate", email: "john@johns-apparel.com" }) {
        customer {
          id
        }
      }
    }
    ```
    
    Prepare mutation:
    
    ```
    <?php 
    use rdx\graphqlquery\Query;
    
    $query = Query::mutation();
    $query->fields('customerCreate');
    $query->customerCreate->attribute('input',['firstName'=>'John','lastName'=> "Tate", 'email'=> "john@johns-apparel.com"]);
    $query->customerCreate->field('customer');
    $query->customerCreate->customer->field('id');
    $graphqlString = $query->build();
    ```
    
    Call GraphQL qith `callGraphql()` function:
    
    ```
    $response = $client->callGraphql($graphqlString);
    ```
* Cursor Pagination with graphQL

    ```
    {
      products(first: 250) {
        edges {
          cursor
          node {
            id
          }
        }
      }
    }
  ```
    Prepare Query and `$reserve_query` variable to store query for next page call:
        
    ```
    <?php 
    use rdx\graphqlquery\Query;
    
    $query = Query::query("");
    $query->fields('products');
    $query->products->attribute('first', 250);
    $query->products->field('edges');
    $query->products->edges->fields(['cursor','node']);
    $query->products->edges->node->fields(['title','description']);
    $reserve_query = $query;
    $graphqlString = $query->build();
  
    ```
    Call GraphQL qith `callGraphql()` function. And 
        
    ```
    $response = $client->callGraphql($graphqlString);
    
    ```
    If you continue repeating this step with the cursor value of the last product in each response you get until your response is empty. So need to check `cursor` index available in last array of `$response` then repeat call by adding cursor value with `after` attribute in products field. 
    
    ```
    if(isset($response['data']['products']['edges']) && $last_array = end($response['data']['products']['edges']))
    {
        if(isset($last_array['cursor']))
        {
            //assign cursor value in `last` attribute
            $query = $reserve_query->products->attribute('after', $last_array['cursor']);
            $graphqlString = $reserve_query->build();
            $next_response = $client->callGraphql($graphqlString);
            print_r($next_response);
        }
    }
    ```
    
    
### Error Handling

Below errors handled with `ApiException` Class
* Trying to pass invalid api version
* Trying to pass invalid shop domain
* Http REST api call exception from Guzzle(`GuzzleHttp\Exception\RequestException`) 
    ```
    try
    {
        $client = new Shopify\PrivateApp($shop, $api_key, $password, $api_params);
        $response = $client->call('GET','products',['limit'=>20]);
        print_r($response);
    }
    catch (\Shopify\Exception\ApiException $e)
    {
        echo "Errors: ".$e->getError().'<br> status code: '.$e->getCode();
    }
    ```
## References
* [Shopify API Reference](https://shopify.dev/docs/admin-api/)
* [GraphQL Query Builder](https://github.com/rudiedirkx/graphql-query)
* [Guzzle Documentation](http://docs.guzzlephp.org/en/stable/)






