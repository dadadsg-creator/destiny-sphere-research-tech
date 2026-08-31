<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмена обороны
class UA_CancelDefend extends UA_Action
{

	public $id_army;
	public $combnamefrom;
	public $combnameto;
	public $bindeqid;
	public $armyname;
	public $errorname;
	public $id_userinstanceto;
	public $direct;
	public $traveltime;
	public $stage;

	/**
	 * Обьект Армии
	 *
	 * @var DE_Army
	 */
	private $army;
	
	private $messParams = array();
	
	private $resourceName;
	private $needResource;
	private $haveResource;

	function UA_CancelDefend ()
	{
		UA_Action::UA_Action ();
	}

	// проверяем параметры
	function checkLocalParams()
	{
		$this->id_army = (int)$this->root->get_attribute ( "id_army" );
		assert($this->id_army >0);

		return true;
	}

	// проверяем доступность действия
	function checkAction()
	{		
		return true;
	}

	// выполняем действие
	function performAction ()
	{
		
		$this->army = DE_Army::CreateFromID($this->id_army);

		// учавствует ли в битве
		if(!GL_CanCancelDefend::Count($this->army))
		{
			switch (GL_CanCancelDefend::$errorCode)
			{
				case 'NEED_REFRESH':
					
					DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANCEL_DEFEND_NEED_REFRESH', array(), 1, 'GREEN');
					return true;
										
					break;
				default:
					ds_error("Cant cancel defend. Reason: ".GL_CanCancelDefend::$errorCode);							
			}
			
		}
				
			
		if($this->errorname == 'NOT_ENOUGH_RESOURCE')
		{
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'DEFEND_NOT_ENOUGH_RESOURCE', $this->messParams, 1, 'GREEN');
			$this->_sendHalper();
			return true;
		}
		
		if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$this->_vocationModeAlert();			
			return true;
		}

		if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
		{
			$this->_shildModeAlert();			
			return true;
		}
		
		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());

		ME_CancelDefend::Cancel($this->army, $checkDate);

		switch(ME_CancelDefend::$returnMode)
		{
			case 'FROM_DEFEND':

				$messparams[0] = "{b} ".ME_CancelDefend::$combTo->getFullName()."{/b}";
				$messparams[1] = "{b} ".ME_CancelDefend::$combTo->getUserInstance()->getUser()->getName()."{/b}";
				$messparams[2] = "{b} ".$this->army->getName()."{/b}";
				$messparams[3] = "{b} ".ME_CancelDefend::$combFrom->getName()."{/b}";
				$messparams[4] = "{b} ".ME_CancelDefend::$travelTime->getDSFormat()."{/b}";

				// системное сообщение
				DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_DEFEND_MESS2", $messparams, 1, 'GREEN');

				break;

			case 'FROM_MOVEMENT':

				$messparams[0] = "{b} ".ME_CancelDefend::$combFrom->getName()."{/b}";
				$messparams[1] = "{b} ".ME_CancelDefend::$combFrom->getUserInstance()->getUser()->getName()."{/b}";
				$messparams[2] = "{b} ".$this->army->getName()."{/b}";
				$messparams[3] = "{b} ".ME_CancelDefend::$combFrom->getFullName()."{/b}";
				$messparams[4] = "{b} ".ME_CancelDefend::$passedTime->getDSFormat()."{/b}";
				$messparams[5] = "{b} ".ME_CancelDefend::$returnResources->getShortReturnList()."{/b}";


				// системное сообщение
				DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_DEFEND_MESS1", $messparams, 1, 'GREEN');

				break;
			default:
				ds_error('Uncorrect army return type = '.ME_CancelDefend::$returnMode);
		}

		if(!$this->direct)
		{
			//if($_SESSION['profi_game'])
			//{
				$noCellData = true;
			//} else {
			//	$noCellData = false;
			//}

			if(!$this->refresh(2, "", "", 1, $noCellData))
			{
				return false;
			}
		}

		$this->_sendHelper();

		return true;
	}

	private function _sendHelper()
	{
		if(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == false))
		{
			return true;
		}

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><army />";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();

		$root->set_attribute("id_army", $this->id_army);
		$root->set_attribute("inaction", 2);

		$ch = $root->new_child("transfers");
		$ch = $root->new_child("actions");

		// корректировка времени
		$delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
			
		$messageparams[0] = ME_CancelDefend::$combFrom->getFullName();
		$messageparams[1] = ME_CancelDefend::$dateTo->getFormatedDate($delta);
		$messageparams[2] = ME_CancelDefend::$dateTo->getFormatedTime($delta);
		
		$currentaction = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ARMY_RETURN', $messageparams, null, null, false);

		$elem = $doc->create_text_node($currentaction);
		$ch->append_child($elem);

		SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'helper');
	}//END OF METHOD _sendHelper()
}
?>