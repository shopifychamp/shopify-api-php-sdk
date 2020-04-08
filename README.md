# SHOPIFY API PHP SDK
PHP SDK helps to connect with shopify [Custom App](https://shopify.dev/concepts/apps#custom-apps), [Public App](https://shopify.dev/concepts/apps#public-apps) and [Private App](https://shopify.dev/concepts/apps#private-apps) using [RESTApi](https://shopify.dev/docs/admin-api/rest/reference) and [Graphql](https://shopify.dev/docs/admin-api/graphql/reference).
* Call GET, POST, PUT and DELETE RestApi method.
* Process GraphQL Admin API for [Query root](https://shopify.dev/docs/admin-api/graphql/reference/queryroot) and [Mutations](https://shopify.dev/docs/admin-api/graphql/reference/mutation).
* Queryroot is used to get resources and mutations is used to update resources (products/orders/customers). 
* Automatic manage Shopify API rate limits.
* Compatible with [Cursor Based Pagination](https://shopify.dev/tutorials/make-paginated-requests-to-rest-admin-api) to resource with pagination.

## Requirements
1. For Api call need [Guzzle](https://github.com/guzzle/guzzle). The recommended way to install Guzzle is through [Composer](https://getcomposer.org/).
    ```
    composer require guzzlehttp/guzzle
    ```
2. To prepare graphql query with [GraphQL query builder](https://github.com/rudiedirkx/graphql-query).
   
    Clone with Clone with SSH
    ```
    git@github.com:rudiedirkx/graphql-query.git
    ```
## Getting started
### Initialize the client
#### 1.  For Private App
* To create instance of Client, you need `shop`, `api_key`, `password` of private app, `api_params` is an array to pass api version with `YYYY-DD/unstable` format otherwise latest version will be assigned.
    
    ```
    <?php 
    require(__DIR__ . '/../vendor/autoload.php');
    use Shopify\PrivateApp;
    
    $api_params['version'] = '2019-10';
    $client = new Shopify\PrivateApp($shop, $api_key, $password, $api_params);
    ```
#### 2. For Public/Custom App (under development)
* To create instance of Client, you need `shop`, `api_key`, `api_secret_key` of private app, `api_params` is an array to pass api version with `YYYY-DD/unstable` format otherwise latest version will be assigned.
    
    ```    
    <?php 
    require(__DIR__ . '/../vendor/autoload.php');
    use Shopify\PuplicApp;
    
    $api_params['version'] = '2019-10';
    $client = new Shopify\PrivateApp($shop, $api_key, $api_secret_key, $api_params);
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
    
    ####### Check next page available with `hasNextPage()` function ####### 

    ```
        if($client->hasNextPage())
        {
            $next_page_response $client->call('GET','products',['limit'=>20,'page_info'=>$client->getNextPage()]);
            print_r($next_page_response);
        }
    ```
    
    ####### Check if previous page available with `hasPrevPage()` function #######

    ```
        if($client->hasPrevPage())
        {
            $next_page_response $client->call('GET','products',['limit'=>20,'page_info'=>$client->getPrevPage()]);
            print_r($next_page_response);
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
    
    ####### Prepare query #######
    
    ```
    <?php 
    use rdx\graphqlquery\Query;
    
    $query = Query::query("");
    $query->fields('product');
    $query->product->attribute('id', "gid://shopify/Product/1432379031652");
    $query->product->field('title');
    $query->product->field('description');
    $graphqlString = $query->build();
    
    ```
    
    ####### Call GraphQL qith `callGraphql()` function #######
    
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
    
    ####### Prepare mutation #######
    
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
    
    ####### Call GraphQL qith `callGraphql()` function #######
    
    ```
    $response = $client->callGraphql($graphqlString);
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






