<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для получения информации о премиум-днях и 
 * игровых кредитах пользователя в игре
 *
 */
class BG_Balance extends BG_Common 
{
	public function __construct($data)
	{
		parent::__construct($data);		
	}
	
	public function proceed()
	{		

		if($this->error_status != 0)
		{
			return;
		}
		
		if(!array_key_exists('uid',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer(); 
			return;
		}
		
		$uid = (int)$this->incoming_info['uid'];
	
		$dates = DS_Premium::getUserPremiumDates($uid);
		
		if(sizeof($dates) > 0)
		{				
			$this->addToResult('startdate', $dates[0]['startdate']);
			$this->addToResult('enddate', $dates[0]['enddate']);
		} else {
			$this->addToResult('startdate', '');
			$this->addToResult('enddate', '');
		}
		
		$user = DE_User::CreateFromID($uid);
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember($user, $world);
		$accountData = MU_SearchOperations::GetAccountData($member);
		
		$this->addToResult('account', urlencode(serialize($accountData)));
		
		$this->error_status = 0;
		$this->outputAnswer(); 
	}
}
?>