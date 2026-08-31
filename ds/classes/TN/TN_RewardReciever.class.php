<?php

class TN_RewardReciever
{
	static public function Exec(array $data)
	{
		assert(key_exists('data', $data));
		$dataStr = base64_decode($data['data']);
		
		assert(key_exists(md5('gate_magic'), $data));
		assert($data[md5('gate_magic')] == md5('TURNIR_RESULT'.date('Y-d-m').$dataStr));
		
		$result = explode(';', $dataStr);
		
		foreach ($result as $k => $userData)
		{
			list($username, $prize) = explode(':', $userData);
			self::_addCredits($username, (int)$prize);
		}
		
		echo md5('TURNIR_RESULT_OK'.date('Y-d-m'));
	}
	
	static private function _addCredits($username, $prize)
	{
		assert(VD_UserName::Validate($username));
		assert(is_int($prize) && $prize > 0);
		
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName($username), $world);
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm($prize, 'SC');
		$actionType = DE_MoneyActionType::CreateFromName('TURRNIR_PRIZE');
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID(GB_RandomPassword::Generate(25)); 
		$operation->setGateKey(GB_RandomPassword::Generate(25));

		MU_ControlExecOperations::ExecOperation($operation);
	}
}

?>