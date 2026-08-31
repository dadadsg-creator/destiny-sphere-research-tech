<?php
/**
 * $Author: 40min
 * $Date: 2008/02/15 14:10:28 $
 *
 *
 */

class BG_ReferalsPay extends BG_Common
{
	public function __construct($data)
	{
		parent::__construct($data);
	}

	public function proceed()
	{
		if(!key_exists('pay_data', $this->incoming_info)
			|| strlen($this->incoming_info['pay_data']) == 0){

			$this->error_status = 1;
			$this->outputAnswer();
      return;
		}

		DS_Common::$connectWithTransactionAndBlocking = true;

		$payDataArr = split(';', $this->incoming_info['pay_data']);
		
		foreach ($payDataArr as $userPay){

			list($userID, $sum) = split(':', $userPay);

      if(!$this->_checkIfUserHaveAccount($userID))
      {
				continue;
        //$this->addToResult('wrongUserID', $userID);
				//return false;
			}

			if(!$this->_enterMoney($userID, $sum)){

				$this->error_status = 4;
				$this->addToResult('wrongUserID', $userID);
				return false;
			}
		}

		ME_EndDBTransactions::Exec(true);

		$this->outputAnswer();
	}

	private function _checkIfUserHaveAccount($userID)
  {

		$world = DE_WorldFromCollection::CreateFromID('UC');
    $member = new MU_EntityOperationMember(DE_User::CreateFromID((int)$userID), $world);
		$data = MU_SearchOperations::GetAccountData($member);
		assert(is_array($data));

		if(count($data) == 0){
			return false;
		}

		assert(key_exists('IS_ACTIVE', $data));
		if(!$data['IS_ACTIVE'])
		{
			return false;
		}

		return true;
	}

	private function _enterMoney($userID, $sum)
	{
/*	  
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromID((int)$userID), $world);
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm((int)$sum, 'SC');
		$actionType = DE_MoneyActionType::CreateFromName('REFERRAL_PAYMENT');
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID(GB_RandomPassword::Generate(25));
		$operation->setGateKey(GB_RandomPassword::Generate(25));

		MU_ControlExecOperations::ExecOperation($operation);
*/
    UC_PaymentServiceMoneyOperation::EnterMoney($userID, $sum, 'SC', 'REFERRAL_PAYMENT');
		return true;
	}
}

?>