<?php
/**
 * Created by IntelliJ IDEA.
 * User: cyb3r
 * Date: 02-Jul-17
 * Time: 7:08 PM
 */

namespace Peeralytics;



class Client
{
	/**
	 * @var resource
	 */ 
	private $_appId;
	private $_endpoint;
	private $_useragent = "Peeralytics PHP";
	private $_secret;
	private $_prefix;
	const DEFAULT_ROUTE = "http://api.vaskovasilev.eu:5000";

	public function __construct($appId , $secret, $destination = Client::DEFAULT_ROUTE){ 
		if(strlen($destination)==null || $destination===null){
			$destination = Client::DEFAULT_ROUTE;
		}
		$this->_prefix = "";
		$this->_endpoint = $destination;
		$this->_appId = $appId;
		$this->_secret = $secret;
	}

	/**
	*
	**/
	public function getDataClient(){
		$dtc = new Data($this);
		return $dtc;
	}

	public function setPrefix($pfx){
		$this->_prefix = $pfx;
	}

	public function getUrl($action){
		$array = [$this->_endpoint];
		if($this->_prefix!==null && strlen($this->_prefix)>0) $array[] = $this->_prefix;
		$array[] = $action;
		array_walk_recursive($array, 
			function(&$component) {
				$component = rtrim($component, '/');
			});
		$url = implode('/', $array);
		return $url;
	}
 

	public function execute($method, $route, $data){
		$method = strtoupper($method);
		$curl = curl_init();
		$url = $this->getUrl($route);		
		if(!isset($data)) $data = "";
		if($method === "POST" && ($data==null) ){
			throw new \Exception("Invalid input data for post request!");
		}
		$tnow = time();
		$nonce = $this->guid();  
		$safeRoute = urlencode($url);
		//Data parsing
		if($data!==null){
			if(is_string($data)){
			}else{
				$data = json_encode($data);
			}
		}
		$requestBodyHash = $data;
		if($requestBodyHash!=null && strlen($requestBodyHash)>0){
			$requestBodyHash = md5($requestBodyHash, true);
			$requestBodyHash = base64_encode($requestBodyHash);
		} else {
			$requestBodyHash = "";
		}

		$signiture = "{$this->_appId}{$method}{$safeRoute}{$tnow}{$nonce}{$requestBodyHash}";
		$secretBytes = base64_decode($this->_secret);
		$signiture = hash_hmac("sha256", $signiture, $secretBytes, true);
		$signiture = base64_encode($signiture);

		$authHeaderValue = "{$this->_appId}:$tnow:$nonce:$signiture";
		$isPost = $method==="POST";
		$headers = [];
		$headers[] = "Accept-Encoding: gzip, deflate";
		$headers[] = "Accept-Language: en-US,en;q=0.5";
		$headers[] = "Cache-Control: no-cache";
		$headers[] = "Authorization: Hmac " . base64_encode($authHeaderValue);
		if($isPost){
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			if($data!=null){
				$headers[] = 'Content-Length: ' . mb_strlen($data);
			}
		}
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => $this->_useragent,
			CURLOPT_HTTPHEADER => $headers
		]);
		if($isPost){                                                                 
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);                                                                
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		}else{
			curl_setopt($curl, CURLOPT_POST, 0);
		}
		$response = curl_exec($curl);
		if(!$response){
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			throw new \Exception("Error[$statusCode]: \"" . curl_error($curl) . '" - Err#: ' . curl_errno($curl));
		}
		curl_close($curl);
		return $response;
	}

	/**
	 * @param $route
	 * @param $data
	 * @return mixed
	 * @throws \Exception
	 */
	public function post($route, $data){
		return $this->execute("post", $route, $data);
	}

	public function get($route)
	{
		return $this->execute("get", $route, "");	
	}

	private function guid()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
}