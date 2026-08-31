<?php

/**
 * Класс добовления игровых кредитов по СМС
 *
 */

class SMS_BuyGameCredits
{
	/**
	 * Enter description here...
	 *
	 * @var DE_User
	 */
	private $user;
	
	private $credit;
	
	private $summ;
	
	/**
	 * Режим тестового исполнения
	 *  нужно для Литвы, с их системой двух-стадийной обработки смс
	 *
	 * @var bool
	 */
	private $test;
	
	public function __construct(array $data)
	{
		$this->_getParams($data);
		
		$this->_addCredits();
	}
	
	private function _addCredits()
	{
		DS_Common::$connectWithTransactionAndBlocking = true;
		
		DS_Common::CreateForCurentDB(0)->startTrans();
		
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberTo = new MU_EntityOperationMember($this->user, $world);
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm($this->summ, $this->credit);
		$actionType = DE_MoneyActionType::CreateFromName('ENTER_MONEY');
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID(GB_RandomPassword::Generate(25)); 
		$operation->setGateKey(GB_RandomPassword::Generate(25));

		MU_ControlExecOperations::ExecOperation($operation);
		
		// обманули всех и откатили транзакцию, если тестовый прогон был
		if($this->test)
		{
			DS_Common::CreateForCurentDB(0)->closeTrans('ROLLBACK');
		}
		
		ME_EndDBTransactions::Exec(true);
		
		$this->_returnResult('{CREDIT_MSG_ADDED}', false);
	}
	
	private function _getParams(array $data)
	{
		assert(key_exists('username', $data));
		assert(key_exists('credit', $data));
		assert(key_exists('summ', $data));
		if(key_exists('test', $data))
		{
			$this->test = (bool)$data['test'];
		} else {
			$this->test = false;
		}
		
		$this->_validateUsername($data['username']);
		
		$this->_validateCredit($data['credit']);
		
		$this->_validateSumm($data['summ']);
	}
	
	private function _validateUsername($username)
	{
		if(!VD_UserName::Validate($username))
		{
			$this->_returnResult('{CREDIT_MSG_ERROR}');
		}
		
		$common = DS_Common::CreateForCurentDB(0);
		$id_user = VD_UserName::CheckIfExists($username, $common);
		
		if($id_user === false)
		{
			$this->_returnResult('{CREDIT_MSG_ERROR}');
		}
		
		$this->user = DE_User::CreateFromId((int)$id_user);
	}
	
	private function _validateCredit($credit)
	{
		/*if($credit != 'SC' && $credit != 'GC')
		{
			$this->_returnResult();
		}
		
		$this->credit = $credit;
		*/
		
		// пока не принимаем параметры от гейта, а задаем все здесь жестко
		$this->credit = 'SC';
	}
	
	private function _validateSumm($summ)
	{
		$summ = (int)$summ;
		
		if($summ <= 0)
		{
			$this->_returnResult('{CREDIT_MSG_ERROR}');
		}
		
		// !!!в литовской версии дается 1000 кредитов!!!		
		$this->summ = $summ;
	}
	
	private function _returnResult($msg, $error = true)
	{
		if($error)
		{
			$msg = '0|'.$msg;
		} else {
			$msg = '1|'.$msg;
		}
		
		die($msg);
	}
}

?>