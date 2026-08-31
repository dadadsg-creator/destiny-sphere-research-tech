<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */

class BG_MoneyEnter extends BG_Common 
{
	private $summ;
	
	private $userID;
	
	private $credit;
	
	public function __construct($data)
	{
		parent::__construct($data);
		if(!$this->_checkIncomingInfo())
		{
			$this->outputAnswer();
			return;
		}
	}
	
	private function _checkIncomingInfo()
	{
		if(!key_exists('id_user', $this->incoming_info))
		{
		  $this->error_status = 1;
			return false;
		}
		
		$this->userID = (int) $this->incoming_info['id_user'];
		
		if($this->userID <= 0)
		{
		  $this->error_status = 2;
			return false;
		}
		
		if(!key_exists('summ', $this->incoming_info))
		{
		  $this->error_status = 3;
			return false;
		}
		
		$this->summ = (int)$this->incoming_info['summ'];
		if($this->summ <= 0)
		{
		  $this->error_status = 4;
			return false;
		}
		
		if(!key_exists('credit', $this->incoming_info))
		{
		  $this->error_status = 5;
			return false;
		}
		
		$this->credit = $this->incoming_info['credit'];
		
		if($this->credit !== 'GC' && $this->credit !== 'SC')
		{
		  $this->error_status = 6;
			return false;
		}
		
		if(key_exists('price', $this->incoming_info))
		{		
		  $this->price = $this->incoming_info['price'];
		}
		else
		{
		  $this->price = 0;
		}
		return true;
	}
	
	public function check()
	{
		if($this->error_status != 0)
		{
			return;
		}
		
		DS_Common::$connectWithTransactionAndBlocking = true;
		
		if($this->_checkIfUserHaveAccount())
		{
			$this->addToResult('checking_result','add_money_ok');
		}
		
		ME_EndDBTransactions::Exec(true);
		
		$this->outputAnswer();
	}
	
	public function exec()
	{
		
		if($this->error_status != 0)
		{
			return;
		}

		DS_Common::$connectWithTransactionAndBlocking = true;
		
		if($this->_enterMoney())
		{
			$this->addToResult('money_ok', 1);
		}
		
		ME_EndDBTransactions::Exec(true);
		
		$this->outputAnswer();
	}
	
	private function _checkIfUserHaveAccount()
	{
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember(DE_User::CreateFromID($this->userID), $world);
		$data = MU_SearchOperations::GetAccountData($member);
		assert(is_array($data));
		
		if(count($data) == 0)
		{
			$this->addToResult('checking_result','user_dont_have_account');
			return false;
		}
		
		assert(key_exists('IS_ACTIVE', $data));
		if(!$data['IS_ACTIVE'])
		{
			$this->addToResult('checking_result','user_account_inactive');
			return false;
		}
		
		return true;
	}
	
	private function _enterMoney()
	{		

		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromID($this->userID), $world);
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);

		$summ = new MU_EntityMoneySumm($this->summ, $this->credit);
		$actionType = DE_MoneyActionType::CreateFromName('ENTER_MONEY');
  	$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID(GB_RandomPassword::Generate(25)); 

		$operation->setGateKey(GB_RandomPassword::Generate(25));
		MU_ControlExecOperations::ExecOperation($operation);
		
		if (constant('BILLING_PARTNER_ID')==10)
		{
		   $common = DS_Common::CreateForCurentDB(0); 	
		   $sql  = "SELECT * FROM ds_partnersusers_id WHERE id_user=".$this->userID;
		   $res = $common->getResultArray ( "",$sql, "", "", "NO_CACHE");
		   if ($res)
		   {
		   	 $from_partner = $res[0]['from_partner'];
		   	 if ($from_partner==120)
		   	 {
		   	 	$bid = $res[0]['id_inpartner'];
		   	 	$url = "http://www.adcell.de/event.php?pid=2165&eventid=2414&referenz=".$this->userID;
		   	 	$url .= "&betrag=".$this->price."&bid=".$bid;
		   	 	$r = file_get_contents($url);
		   	 	SU_DebugTools::LogAll("url=$url, r=$r",'adcell.log');
		   	 } 
		   }	
		}
		return true;
	}
}

?>