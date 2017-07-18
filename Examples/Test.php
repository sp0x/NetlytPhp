<?php


use \Peeralytics\Client;
require __DIR__ . '../vendor/autoload.php';

$appId = "5f059dbe7c3b48dz6b3aed518b54f1dc5";
$secret = "tvIn6qz2sOKrgjPBP3PCfSVXESb0Hhn7IhZBKqhObVA=";

$client = new Peeralytics\Client($appId, $secret);
$status = $client->get("data/GetStatus");
var_dump($status);

$dataClient = $client->getDataClient();
$status = $dataClient->createEntity([
    'Name' => "Pesho"
]);
var_dump($status);

$status = $dataClient->addEntityData([
	'Action' => 'SiteVisit'
]);

var_dump($status);
die(1);