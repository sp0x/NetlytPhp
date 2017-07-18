<?php
namespace Peeralytics;

class Data{
	private $client;
	public function __construct(Client $client){
		$this->client = $client;
		$this->client->setPrefix("data");
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	public function createEntity($data){
		return $this->client->post("Entity" , $data);
	}

	/**
	 * @param string|array $entityFilter
	 * @param string|array $data
	 * @return mixed
	 */
	public function addEntityData($entityFilter, $data){
		if(!is_string($entityFilter)) $entityFilter = json_encode($entityFilter);
		$data = array('data' => $data, 'filter' => $entityFilter);
		return $this->client->post("EntityData" , $data);
	}
}