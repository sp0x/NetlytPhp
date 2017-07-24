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

	/**
	 * @param string|array $entityFilter
	 * @param array $newEntityData
	 * @return mixed
	 * @internal param array|string $data
	 */
	public function updateEntity($entityFilter, $newEntityData){
		if(!is_string($entityFilter)) $entityFilter = json_encode($entityFilter);
		$data = array('data' => $newEntityData, 'filter' => $entityFilter);
		return $this->client->post("EntityUpdate" , $data);
	}

	/**
	 * @param string $userIdentifierField Example  "{ Id : 5 }" or [ Id => 5]
	 * @param string $socialNetworkType Facebook for example
	 * @param array $details Example [ userToken => social-network-user-token ]
	 * @return mixed
	 * @throws \Exception
	 */
	public function createEntitySocialProfile($userIdentifierField, $socialNetworkType, $details)
	{
//		if(is_array($userIdentifierField)){
//			$userIdentifierField = json_encode($userIdentifierField);
//		}
		$result = $this->client->post("SocialEntity", [
			'userIdentifier' => $userIdentifierField,
			'type' => $socialNetworkType,
			'details' => $details
		]);
		$result = json_decode($result, true);
		if(!$result["success"]){
			throw new \Exception($result['message']);
		}
		return $result;
	}
}