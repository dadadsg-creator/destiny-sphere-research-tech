<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * 
 * Класс гейта биллинга, осуществляющий снятие кредитов со 
 * счета игрока за покупку товаров в биллинге
 *
 */

class BG_CreditsBillingPayment extends BG_Common
{
	private $uid;
	
	private $credits;
	
	private $ammount;
	
	public function __construct($data)
	{
		parent::__construct($data);	
		
		$this->_checkParams();	
	}
	
	// проверяем корректность входных данных
	private function _checkParams()
	{
		if($this->error_status != 0)
		{
			return;
		}
		
		// ИД пользователя
		if(!key_exists('uid', $this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer(); 
			return;
		}
		
		$this->uid = (int)$this->incoming_info['uid'];
		
		if($this->uid <= 0)
		{
			$this->error_status = 2;
			$this->outputAnswer(); 
			return;
		}
		
		// вид кредитов
		if(!key_exists('type', $this->incoming_info))
		{
			$this->error_status = 3;
			$this->outputAnswer(); 
			return;
		}
		
		$this->credits = $this->incoming_info['type'];
		
		if(!$this->credits == 'GC' && !$this->credits == 'SC')
		{
			$this->error_status = 4;
			$this->outputAnswer(); 
			return;
		}
		
		// сумма
		if(!key_exists('amount', $this->incoming_info))
		{
			$this->error_status = 5;
			$this->outputAnswer(); 
			return;
		}
		
		$this->ammount = (int)$this->incoming_info['amount'];
		
		if($this->ammount <= 0)
		{
			$this->error_status = 6;
			$this->outputAnswer(); 
			return;
		}
	}
	
	// Перевод денег за оплату в биллинге
	public function Exec()
	{
		if($this->error_status != 0)
		{
			return;
		}

		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromID($this->uid), $world);
		
		$accountData = MU_SearchOperations::GetAccountData($memberFrom);
		
		// проверяем есть ли счет у игрока
		if(count($accountData) == 0)
		{
			$this->error_status = 7;
			$this->outputAnswer(); 
			return;
		}
		
		// активен ли счет
		if(!$accountData['IS_ACTIVE'])
		{
			$this->error_status = 8;
			$this->outputAnswer(); 
			return;
		}
		
		// есть ли на нем необходимая сумма кредитов
		if($accountData[$this->credits] < $this->ammount)
		{
			$this->error_status = 9;
			$this->outputAnswer(); 
			return;
		}
		
		// перевод денег
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm($this->ammount, $this->credits);
		$actionType = DE_MoneyActionType::CreateFromName('BILLING_PAYMENT');

		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID(GB_RandomPassword::Generate(25)); 
		$operation->setGateKey(GB_RandomPassword::Generate(25)); 

		MU_ControlExecOperations::ExecOperation($operation);
		
		$this->error_status = 0;
		$this->outputAnswer();
	}
}

?>