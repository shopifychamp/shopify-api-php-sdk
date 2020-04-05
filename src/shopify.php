<?php
class ShopifyClient {
	public $shop_domain;
    private $token;
    private $api_key;
    private $secret;
    
    private $last_response_headers = null;

    const MAX_ATTEMPT = 4;

    const SHOPIFY_API_VERSION = '2019-10';

    private $urlFormat = '{shopify_domain}/admin/api/{version}/{endpoint}';

    public function __construct($shop_domain, $token, $api_key, $secret)
    {
        $this->shop_domain = $shop_domain;
        $this->token = $token;
        $this->api_key = $api_key;
        $this->secret = $secret;
    }

    // Get the URL required to request authorization
    public function getAuthorizeUrl($scope, $redirect_url = '')
    {
        $url = "https://{$this->shop_domain}/admin/oauth/authorize?client_id={$this->api_key}&scope=" . urlencode($scope);
        if ($redirect_url != '') {
            $url .= "&redirect_uri=" . $redirect_url;
        }

        return $url;
    }

    // Once the User has authorized the app, call this with the code to get the access token
    public function getAccessToken($code)
    {
        // POST to https://SHOP_NAME.myshopify.com/admin/oauth/access_token
        $url = "https://{$this->shop_domain}/admin/oauth/access_token";
        $payload = "client_id={$this->api_key}&client_secret={$this->secret}&code=$code";
        $response = $this->curlHttpApiRequest('POST', $url, '', $payload, array());
        $response = json_decode($response, true);

        if (isset($response['access_token']))
            return $response['access_token'];
        return '';
    }

    public function callsMade()
    {
        return $this->shopApiCallLimitParam(0);
    }

    public function callLimit()
    {
        return $this->shopApiCallLimitParam(1);
    }

    public function callsLeft($response_headers)
    {
        return $this->callLimit() - $this->callsMade();
    }

    public function call($method, $path, $params = array())
    {
        $baseurl = "https://{$this->shop_domain}/";

        $url = $baseurl . ltrim($path, '/');
        $query = in_array($method, array('GET', 'DELETE')) ? $params : array();
        $payload = in_array($method, array('POST', 'PUT')) ? json_encode($params) : array();
        $request_headers = in_array($method, array('POST', 'PUT')) ? array("Content-Type: application/json; charset=utf-8", 'Expect:') : array();

        // add auth headers
        $request_headers[] = 'X-Shopify-Access-Token: ' . $this->token;

        $response = $this->curlHttpApiRequest($method, $url, $query, $payload, $request_headers);
        $response = json_decode($response, true);

        if (isset($response['errors']) or ($this->last_response_headers['http_status_code'] >= 400)) {
            return $response;
        }
        return (is_array($response) and (count($response) > 0)) ? array_shift($response) : $response;
    }

    public function validateSignature($query)
    {
        if (!is_array($query) || empty($query['signature']) || !is_string($query['signature']))
            return false;

        foreach ($query as $k => $v) {
            if ($k != 'shop' && $k != 'code' && $k != 'timestamp') continue;
            $signature[] = $k . '=' . $v;
        }

        sort($signature);
        $signature = md5($this->secret . implode('', $signature));

        return $query['signature'] == $signature;
    }

    public function curlHttpApiRequest($method, $raw_url, $query = '', $payload = '', $request_headers = array(), $attempt=1)
    {
        $url = $this->curlAppendQuery($raw_url, $query);
        $url = $this->prepareApiVersionEndpoint($url);
        if($this->shop_domain== 'ced-testr-purpose-15-new.myshopify.com') {
           // var_dump($url);die;
        }
        $ch = curl_init($url);
        $this->curlSetopts($ch, $method, $payload, $request_headers);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        //if ($errno) throw new \ShopifyCurlException($error, $errno);

        if ($errno){
            $error_msg = ['errors'=> 'Curl error: '.$error];
            return json_encode($error_msg);
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $message_headers = substr($response, 0, $header_size);
        $message_body = substr($response, $header_size);

        // list($message_headers, $message_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
        $this->last_response_headers = $this->curlParseHeaders($message_headers);

        curl_close($ch);

        if(isset($this->last_response_headers['http_status_code']) && $this->last_response_headers['http_status_code']==429 && $attempt <= self::MAX_ATTEMPT) {
            $wait_time = isset($this->last_response_headers['retry-after']) ? (int)$this->last_response_headers['retry-after'] : 3;
            sleep($wait_time);
            return $this->curlHttpApiRequest($method, $raw_url, $query, $payload, $request_headers, $attempt++);
        } else {
            return $message_body;
        }
    }

    private function curlAppendQuery($url, $query)
    {
        if (empty($query)) return $url;
        if (is_array($query)) return "$url?" . http_build_query($query);
        else return "$url?$query";
    }

    private function curlSetopts($ch, $method, $payload, $request_headers)
    {
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//deepak
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ohShopify-php-api-client');
        /*curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);*/
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($request_headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        if ($method != 'GET' && !empty($payload)) {
            if (is_array($payload)) $payload = http_build_query($payload);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
    }

    private function curlParseHeaders($message_headers)
    {
        $header_lines = preg_split("/\r\n|\n|\r/", $message_headers);
        $headers = array();
        $statusArr = explode(' ', trim(array_shift($header_lines)), 3);
        if (isset($statusArr[1]))
            $headers['http_status_code'] = $statusArr[1];
        if (isset($statusArr[2]))
            $headers['http_status_message'] = $statusArr[2];

        foreach ($header_lines as $key=>$header_line) {
            $info = explode(':', $header_line,2);
            if (isset($info[1]) && $info[1]){
                $headers[trim($info[0])] = trim($info[1]);
            }
        }
        return $headers;
    }

    private function shopApiCallLimitParam($index)
    {
        if ($this->last_response_headers == null) {
            throw new Exception('Cannot be called before an API call.');
        }
        $params = explode('/', $this->last_response_headers['http_x_shopify_shop_api_call_limit']);
        return (int)$params[$index];
    }

    public function getResponseHeaders()
    {
        return $this->last_response_headers;
    }

    public function prepareApiVersionEndpoint($url)
    {
        if(strpos($url, 'oauth') === false)
        {
            $explode = explode('/admin/', $url);

            if(count($explode) === 2)
            {
                list($shop, $endpoint) = $explode;

                $endpoint = str_replace('api/', '', $endpoint);
                if(strpos($endpoint, '.json') === false) {
                    $endpoint .= '.json';
                }

                $url = strtr($this->urlFormat, ['{shopify_domain}'=>$shop, '{version}'=>self::SHOPIFY_API_VERSION, '{endpoint}'=>$endpoint]);

                return $url;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return $url;
        }
    }

	public function callGql($method, $body)
	{
		$url = "https://{$this->shop_domain}/admin/api/graphql.json";
		
		$query = in_array($method, array('GET', 'DELETE')) ? $body :'';
		$payload = in_array($method, array('POST', 'PUT')) ? $body : '';
		$request_headers = array("Content-Type: application/graphql");
		
		// add auth headers
		$request_headers[] = 'X-Shopify-Access-Token: ' . $this->token;
		
		$response = $this->curlHttpApiRequest($method, $url, $query, $payload, $request_headers);
		
		$response = json_decode($response, true);
		//var_dump($response['errors']);die('hellp');
		
		if (isset($response['errors']) or ($this->last_response_headers['http_status_code'] >= 400)) {
			return $response;
		}
		return (is_array($response) and (count($response) > 0)) ? array_shift($response) : $response;
	}

    public function getPageParams() {
        $headerRes = $this->getResponseHeaders();

        if(!is_null($headerRes)) {
            if(!empty($headerRes) && (isset($headerRes['link']) || isset($headerRes['Link']))) {
                $linkVal = $headerRes['link'] ?? $headerRes['Link'];
                if(!is_null($linkVal)) {
                    $linkArr = array_map('trim',explode(',',$linkVal));
                    $returnParam = [];
                    if(isset($linkArr[0])) {
                        foreach ($linkArr as $key => $link) {
                            $this->getParamstype($link,$returnParam);
                        }
                        return $returnParam;
                    }
                } else {
                    return [];
                }
            } else {
                return [];
            }
        }
        return [];
    }
    public function getParamstype($link,&$returnParam) {
        $linkArr = array_map('trim',explode(';',$link));
        if(isset($linkArr[0])) {
            $format = '/(%s)(.*)(%s)/';
            $pattern = sprintf($format, '<', '>');
            preg_match($pattern, $linkArr[0], $matches);
            if (isset($matches[2]) && filter_var($matches[2], FILTER_VALIDATE_URL)) {
                $linkExp = explode('?', $matches[2]);
                if(isset($linkExp[1])) {
                    parse_str($linkExp[1], $output);
                    if(!empty($output)) {
                        if(isset($linkArr[1])) {
                            if($linkArr[1]=='rel="next"'){
                                $returnParam['next'] = true;
                                $returnParam['paramsNext'] = $output;
                            }elseif($linkArr[1]=='rel="previous"'){
                                $returnParam['previous'] = true;
                                $returnParam['paramsPrevious'] = $output;
                            }
                        }
                        return $returnParam;
                    }
                }
            }
        }
    }
}

class ShopifyCurlException extends Exception { }
class ShopifyApiException extends Exception
{
	protected $method;
	protected $path;
	protected $params;
	protected $response_headers;
	protected $response;
	
	function __construct($method, $path, $params, $response_headers, $response)
	{
		$this->method = $method;
		$this->path = $path;
		$this->params = $params;
		$this->response_headers = $response_headers;
		$this->response = $response;
		
		parent::__construct($response_headers['http_status_message'], $response_headers['http_status_code']);
	}

	function getMethod() { return $this->method; }
	function getPath() { return $this->path; }
	function getParams() { return $this->params; }
	function getResponseHeaders() { return $this->response_headers; }
	function getResponse() { return $this->response; }
}
?>