<?php
namespace Peeralytics;

use Peeralytics\Client;

class Data{
	private $client;
	public function __construct(Client $client){
		$this->client = $client;
		$this->client->setPrefix("data");
	}
	
	public function createEntity($data){
		return $this->client->post("Entity" , $data);
	}
	public function addEntityData($data){
		return $this->client->post("EntityData" , $data);
	}
}