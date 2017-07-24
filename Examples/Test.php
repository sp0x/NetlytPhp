<?php
require __DIR__ . '/../vendor/autoload.php';
use \Peeralytics\Client;

$appId = "5f0sdfghc5";
$secret = "Bvsdfgj=";


$client = new Peeralytics\Client($appId, $secret);
//Get the status of our client
$status = $client->get("data/GetStatus");

$newUser = [
	'Name' => "Pesho",
	"Id" => 1
];


//Creating a data client, to work with user data
$dataClient = $client->getDataClient();
//create an user entity
$status = $dataClient->createEntity($newUser);

//Add event data to this entity
$status = $dataClient->addEntityData([ "Id" => $newUser['Id']], [
	'Action' => 'SiteVisit'
]);

var_dump($status);