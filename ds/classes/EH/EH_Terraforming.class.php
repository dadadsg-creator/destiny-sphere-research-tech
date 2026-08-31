<?php $CVS_Version = '$Revision$ ; $Date$';

class EH_Terraforming extends EH_Event
{
	private  $cellID;
	private  $combID;
	private  $combObject;
	
	function __construct()
	{
		EH_Event::EH_Event ();
	}

	function handleEvent($eqid)
	{
		$this->eqid = (int)$eqid;
		assert($this->eqid > 0);

		// если внешний рефреш
		if(!$this->checktime)
		{
			$this->checktime = 'now()';
		}

		// получаем параметры эвента
		assert($this->getEventParams());
		
		$this->combID = (int)$this->eventparams["ID_Comb"];	
		assert($this->combID > 0);
		
		$this->combObject = DE_Comb::CreateFromID($this->combID);
		
		$this->cellID = (int)$this->eventparams["ID_Cell"];	
		assert($this->cellID > 0);
		
		if($this->_isTimeToHandle())
		{
			$this->_handle();		
		}else{
			$this->_preHandle();
		}
				
		return true;
	}

	private function _preHandle()
	{		
		$messparams[] = "{b} ".$this->combObject->getName()." {/b}";
		$messparams[] = "{b} ".$this->_getTimePeriodToEnd()->getDSFormat()." {/b}";
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'TERRAFORMING_PROCESS', $messparams, 0, null, 'NOTFORSTORE');
	}
	
	private function _handle()
	{
		//андиндем ивент
		DS_Terraforming::UnBindEventQueue($this->cellID);
						
		//Удаляем здание терраформера
		DS_Terraforming::DeleteTerraformer($this->cellID);
		
		//Рестартим вид соты	
		CR_CombView::CreateFromCombID((int)$this->combID)->resetBuildings(DE_Cell::CreateFromID($this->cellID)->getCellTypeID());
		
		//Пишем мессагу что трансформировали
		$messparams[] = "{b} ".$this->combObject->getName()." {/b}";		
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'TERRAFORMING_END', $messparams, 0);
		
		//Убили ивент
		$this->setTrash();
		
		return true;
	}
	
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
	{
		return false;
	}
}

?>