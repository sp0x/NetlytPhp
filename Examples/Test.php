<?php
use \Peeralytics\Client;
require __DIR__ . '/../vendor/autoload.php';

$appId = "5ffghj9dbe7c3bsfgh18b54f1dc5";
$secret = "BdfyjfghjbVA=";

$client = new Peeralytics\Client($appId, $secret);
$status = $client->get("data/GetStatus");
var_dump($status);
$newUser = [
	'Name' => "Pesho",
	"Id" => 1
];

$dataClient = $client->getDataClient();
$status = $dataClient->createEntity($newUser);
var_dump($status);

$status = $dataClient->addEntityData([ "Id" => $newUser['Id']], [
	'Action' => 'SiteVisit'
]);

var_dump($status);
die(1);