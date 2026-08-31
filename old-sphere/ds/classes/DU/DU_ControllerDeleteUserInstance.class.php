<?php $CVS_Version = '$Revision$ ; $Date$';

/** 
	Class for deleting  user instances
*/
class DU_ControllerDeleteUserInstance
{		
	/**
	 * 	Работа с БД
	 *
	 * @var DS_Common
	 */
	private $common = '';
			

	/**
	 * ID инстанса ск оторым рботаем
	 *
	 * @var unknown_type
	 */
	private $UIID;
	
	private $deleteCompanents = array(
		'DU_DeleteEvents', 
		'DU_DeleteArchiologyTeams',
		'DU_DeleteArmys',
		'DU_DeleteBattles',
		'DU_DeleteMessages',
		'DU_DeleteArtefacts',
		'DU_DeleteAlliance',
		'DU_DeleteScience',
		'DU_DeleteReligion',
		'DU_DeleteBuildings',
		'DU_DeleteCombs',
		'DU_DeleteUserInstanceDetails'
	);
	
	function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
				
	function out($msg)
	{
		$str = date('Y-m-d H:i:s')." ".$msg."\n";
		echo $str;
		flush(); 
	}
	
	function drop($UIID) 
	{				
		assert(is_int($UIID) && $UIID > 0);		
				
		$this->UIID = $UIID;
				
		$this->out(' - BEGIN DELETE UIID = '.$this->UIID);
		
		/*
		foreach($this->deleteCompanents as $k=>$companentName)
		{
			$this->out(" - Start $companentName");
			flush();
			$companent = new $companentName($this->common, $this->UIID);
			$companent->Exec();
		}
		
		/**/
		$time = microtime();

		/*// Смотрим, сколько денег на счету игрока в мире
		$id_user = (int)DE_UserInstance::CreateFromID((int)$this->UIID)->getUserID();
		assert($id_user > 0);
		$user = DE_User::CreateFromID($id_user);
		$worldFrom = DE_WorldFromCollection::CreateFromID('PLS');
		$memberFrom = new MU_EntityOperationMember($user, $worldFrom);
		$instanceMoney = MU_SearchOperations::GetAccountData($memberFrom);
		
		$gcCount = $instanceMoney['GC'];
		$scCount = $instanceMoney['SC'];
		
		$memberTo = new MU_EntityOperationMember($user, DE_WorldFromCollection::CreateFromID('UC'));

		if($gcCount > 0)
		{
			$summ = new MU_EntityMoneySumm($gcCount, 'GC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
		}

		if($scCount > 0)
		{
			$summ = new MU_EntityMoneySumm($scCount, 'SC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
		}*/
		
		
		$sql = "SET STATEMENT_TIMEOUT to 0; SELECT drop_uiid(".$this->UIID.")";
		$time2 = microtime();
		
		$dres = $this->common->getResultArray("", $sql);
		assert(empty($this->common->errormsg));
		/**/	
		$this->out(' - DELETE OF UIID COMPLIT');
		
		return true;
	
	} //END OF METHOD drop ( $this->UIID)
	
}//END OF CLASS DropUser

?>