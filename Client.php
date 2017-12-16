<?php 
namespace Netlyt;

class Client
{
	/**
	 * @var resource
	 */ 
	private $_appId;
	private $_endpoint;
	private $_useragent = "Netlyt PHP";
	private $_secret;
	private $_prefix;
	private $_initialized;
	private $_cookieFile;
	private $_cacheCookies;
	private $iv = "452871829734829374289375892375892";

	const DEFAULT_ROUTE = "http://api.netlyt.com";

	public function __construct($appId , $secret, $destination = Client::DEFAULT_ROUTE, $keepCookies = true){
		if(strlen($destination)==null || $destination===null){
			$destination = Client::DEFAULT_ROUTE;
		}
		$this->_prefix = "";
		$this->_endpoint = $destination;
		$this->_appId = $appId;
		$this->_secret = $secret;
		$this->_initialized = false;
		$this->curl = curl_init();
		$path = getcwd() . DIRECTORY_SEPARATOR . "cookie_" . time() . ".tmp";
		$this->_cookieFile = $path;
		$this->_cacheCookies = $keepCookies;
		if($keepCookies){
			if(file_exists($this->_cookieFile)){
				try{
					unlink($this->_cookieFile);
				}catch(\Exception $e){

				}
			}
			curl_setopt( $this->curl, CURLOPT_COOKIEJAR, $this->_cookieFile);
			curl_setopt( $this->curl, CURLOPT_COOKIEFILE, $this->_cookieFile);
		}
	}

	/**
	*
	**/
	public function getDataClient(){
		$dtc = new \Netlyt\Data($this->clone());
		return $dtc;
	}

	public function clone(){
		$cl = new Client($this->_appId, $this->_secret, $this->_endpoint, false);
		//we share the handle to libcurl
		$cl->curl = $this->curl;
//		if($this->_cacheCookies){
//			curl_setopt( $cl->curl, CURLOPT_COOKIESESSION, false);
//			curl_setopt( $cl->curl, CURLOPT_COOKIEJAR, $this->_cookieFile);
//			curl_setopt( $cl->curl, CURLOPT_COOKIEFILE, $this->_cookieFile);
//		}
		$cl->_cacheCookies = $this->_cacheCookies;
		$cl->_cookieFile = $this->_cookieFile;
		$cl->_useragent = $this->_useragent;
		$cl->_prefix = $this->_prefix;

		return $cl;
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
		$url = $this->getUrl($route);
		//echo "Executing: $url\n";
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
			if(mb_strlen($data)>0){
				//$data = $this->xorString($data);
				$data = $this->secureString($data);
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
		curl_setopt_array($this->curl, [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => $this->_useragent,
			CURLOPT_HTTPHEADER => $headers
		]);
		if($isPost){                                                                 
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST"); 
			curl_setopt($this->curl, CURLOPT_POST, 1);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);                                                                
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true); 
		}else{
			curl_setopt($this->curl, CURLOPT_POST, 0);
		}
		$response = curl_exec($this->curl);
		if(!$response){
			$statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
			throw new \Exception("Error[$statusCode]: \"" . curl_error($this->curl) . '" - Err#: ' . curl_errno($this->curl));
		}
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

	/**
	 * Gets the required permissions
	 * @param $type
	 * @param array $permissionsArray Array in which to merge the new permissions
	 * @return array The permissions that were required
	 */
	public function requirePermissions($type, &$permissionsArray){
		$perms = $this->get("api/SocialPermissions?type=$type");
		$perms = json_decode($perms, true);
		if($perms && $perms["success"]){
			$perms = $perms["data"]["required"];
			$permissionsArray = array_merge($permissionsArray, $perms);
			return $perms;
		}
		return [];
	}

	/**
	 * @param $type
	 * @param array $details Example [ appId => social-network-app-id, secret => social-network-app-secret]
	 * @return mixed
	 */
	public function addSocialNetwork($type, $details){
		return $this->post("api/RegisterSocialNetwork", [
			'type' => $type,
			'details'=> $details
		]);
	}




	private function guid()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
	
	public function close(){
		curl_close($this->curl);
	}

	function __destruct()
	{
		$this->close();
		unset($this->curl);
		if(file_exists($this->_cookieFile)){
			try{
				$res = unlink($this->_cookieFile);
				//$res = unlink($targetCookieFile);
				//echo "Deleted $this->_cookieFile\n ";
				//var_dump($res);
			}catch(\Exception $e){
				echo "Destructor err:R " . $e->getMessage();
			}
		}
	}

	private function secureString($data){
		// to append string with trailing characters as for PKCS7 padding scheme
		$data = $this->xorString($data);
		return $data;
	}
	/**
	 * @param $data
	 * @return string
	 */
	private function xorString($data){
		// Our output text
		$outText = '';
		// Iterate through each character
		for($i=0; $i<strlen($data); )
		{
			for($j=0; ($j<strlen($this->_secret) && $i<strlen($data)); $j++,$i++)
			{
				$outText .= $data{$i} ^ $this->_secret{$j};
				//echo 'i=' . $i . ', ' . 'j=' . $j . ', ' . $outText{$i} . '<br />'; // For debugging
			}
		}
		return $outText;
	}
}