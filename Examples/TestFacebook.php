<?php
require __DIR__ . '/../vendor/autoload.php';
use \Netlyt\Client;

$appId = "5f05sdfhsdfgh";
$secret = "BvIsdfhgA=";


$client = new Netlyt\Client($appId, $secret);
$dataClient = $client->getDataClient();

//To fetch the permissions, for facebook, we just call this

//Whenever you have an authentication token from your user
//for example after the user logs in, or in your fb-callback.php
$fbAppId = "{app-id}"; //Replace {app-id} with your app id
$fbAppSecret = "{app-secret}";
//Note: The user must be already created
$user = [
	'id' => 124,
	'name' => 'example name'
];


/**
 * facebook login permissions management example
 */

$fb = new Facebook\Facebook([
	'app_id' => $fbAppId, //
	'app_secret' => $fbAppSecret,
	'default_graph_version' => 'v2.2',
]);
$permissionsArray = ["email"];
$client->requirePermissions("Facebook", $permissionsArray);
$helper = $fb->getRedirectLoginHelper("urlToRedirectToAfterLogin-Optional!");
$url = $helper->getLoginUrl($permissionsArray);
echo "Permissions: \n";
var_dump($permissionsArray);
echo "\n\nUrl: " . $url;




/**
 * facebook login callback example
 */

$fb = new Facebook\Facebook([
	'app_id' => $fbAppId, //
	'app_secret' => $fbAppSecret,
	'default_graph_version' => 'v2.2',
]);

$helper = $fb->getRedirectLoginHelper();
try {

	$accessToken = "usertoken"; //$helper->getAccessToken();
	//Register our session
	$client->addSocialNetwork("Facebook", [
		'appId' => $fbAppId,
		'secret' => $fbAppSecret
	]);
	//If your user does not already exist, please create it first, using
	//$res = $dataClient->createEntity($user);

	$status2 = $dataClient->createEntitySocialProfile([ 'id' => $user['id'] ],"Facebook", [
		'userToken' => $accessToken
	]);
	var_dump($status2);
}catch(\Exception $ex){
	echo "Something went wrong..\n" . $ex->getMessage();
}