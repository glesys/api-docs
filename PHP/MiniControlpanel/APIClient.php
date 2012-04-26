<?php


class APIClient
{
    /*
     * This should be 1 in production and can be 0 in development.
     * Makes sure that curl verifies the certificate.
     */
    private $_verify_ssl_validity = 0;

    protected $_username;
    protected $_apikey;
    private $_last_response;
    private $_responseformat = 'json';
    protected $_baseurl = "https://api.glesys.com/";


    public function __construct($username = null , $apikey = null)
    {
        $this->setUserCredentials($username, $apikey);
    }
    
    public function setUserCredentials($username, $apikey)
    {
        $this->_username = $username;
        $this->_apikey = $apikey;
    }

    public function get($function, $argumentsarray=array())
    {
        return $this->_make_request($function, $argumentsarray, 'get');
    }

    public function post($function, $argumentsarray=array())
    {
        return $this->_make_request($function, $argumentsarray, 'post');
    }
    
    private function _make_request($function, $argumentsarray,$requesttype){
        if($this->_username == null || $this->_apikey == null){
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_username . ":" . $this->_apikey);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->_verify_ssl_validity);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->_verify_ssl_validity);
        
        $url='';
        if($requesttype=='get'){
            $query_string = '';
            if(count($argumentsarray) > 0)
            {
                foreach ($argumentsarray as $key => $value)
                {
                    $query_string .= "$key/" . urlencode($value) . "/";
                }
            }
            $query_string.="format/" . $this->_responseformat;

            $url = $this->_baseurl . $function . "/" . $query_string;
            $this->_full_call = "GET: " . $url;
        }
        else if($requesttype=='post'){

            $post = array("format=" . $this->_responseformat);
            foreach ($argumentsarray as $key => $value)
            {
                $post[] = urlencode($key) . "=" . urlencode($value);
            }
            $post = implode("&", $post);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            
            $url = $this->_baseurl . $function . "/";
            $this->_full_call = "POST: " . $this->_baseurl . "/" . $function . "/<br>DATA: $post";
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        if($response === FALSE)
        {
            throw new Exception(curl_error($ch));
        }
        
        if($this->_responseformat == 'json')
        {
            $info = curl_getinfo($ch);
            $json = json_decode($response, true);
            if(!is_array($json))
                throw new Exception($response);
            $response = $json;
            $response = $response['response'];
            $this->_last_response = $response;
            if($info['http_code'] == 401)
            {
                throw new Exception($response['status']['text'], $info['http_code']);
            }
            if($response['status']['code'] != 200)
            {
                throw new Exception($response['status']['text'], $response['status']['code']);
            }
        }
        curl_close($ch);
        return $response;
    }
    
    public function getLastCall(){
        return $this->_full_call;
    }
    
    public function getLastResponse(){
        return $this->_last_response;
    }
}