<?php $CVS_Version = '$Revision$ ; $Date$';

// рефреш соты
class UA_Refresh extends UA_Action
{

	public $full;
	public $cell;
	public $combres;

	public $messages;

	function UA_Refresh()
	{
		UA_Action::UA_Action ();
	}

	// проверка локальных параметров
	function checkLocalParams()
	{
		// если cell=1 берем из базы инфу по всем ячейкам и отправляем клиенту
		$this->cell = $this->root->get_attribute ( "cell" );
		
		if (isset($_SESSION['cells']))
		{
			if ( $_SESSION['cells'] == 1 ) $this->cell = 1;
			unset ( $_SESSION['cells'] );
		}

		$this->combres = 1;
		
		return true;
	}

	// проверка допустимости действия
	function checkAction()
	{
		global $params;
		global $common;

		// достаточно ли прошло времени
		$sql = sprintf ( "select isAllowRefresh (%d) as ar", $params["uiid"] );

		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		if ( !$res ) {
			$this->errormsg = "UA_Refresh::checkAction1";
			
			return false;
		}

		// если нет
		if ( @$res[0]['ar'] != 1 )
		{
			$mparams[0] = $this->worldSettings->get('REFRESH_RATE');

			// системное сообщение
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TO_FAST_REFRESH_MESS', $mparams );			

			$this->full = 0;
		} else { 
			// прошло достаточно времени

			$this->full = 1;
			
			if ( isset ( $_SESSION['profi_game'] ) )
			{
				// профи игра
				// убиваем из сессии данные о всех сотах игрока и будем брать заново из базы
				unset ( $_SESSION["refresh"] );
				unset ( $_SESSION["combres"] );

			} else { // не профи игра

				$refresh = @$_SESSION["refresh"];

				// убиваем только данные о текущей соте
				unset ( $refresh[$this->comb."_1"] );
				unset ( $refresh[$this->comb."_2"] );
				unset ( $refresh[$this->comb."_3"] );

				$_SESSION["refresh"] = $refresh;
			}
		}
		
				
		//Ресетим кеш списка боев
		$_SESSION['BATTLE_OVERVIEW'] = array();
		
		return true;
	}

	// выполняем действие
	//$notCellInfo к рефрешу не будут добавлены сведения с ячейкуами соты и сведения 
	//со зданяими
	function performAction ( $notCellInfo = false )
	{ 
		global $params;
		global $common;	

		//Елси работаем из командной чстроки то всегда полнй рефреш
		if(RD_AdeptUserActionToConsole::$commandLineFlag)
		{			
			$this->full = 1;
			//$this->noscan = 0;
			unset ( $_SESSION["refresh"] );
			unset ( $_SESSION["combres"] );
		}
		
		// корректировка времени
		$delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();	

		if(!empty($_SESSION["complexview"])) 
		{
			// если обзор комплекса - выдаем комплекс и выходим			
			SU_OutputHandler::Singleton()->addReadyString( $_SESSION["complexview"] );
			return true;
		}

		// смотрим есть ли эта часть соты в сессии
		//$refresh = @$_SESSION["refresh"];
		if(($this->full == 1 || !isset ($refresh[$this->comb."_".$this->celltype])) && !$notCellInfo) 
		{
			
			$userInstanceObject = DE_UserInstance::CreateFromID((int)$params["uiid"]);

			$refreshCommand = new CR_RefreshUserInstanceCommand($userInstanceObject);
			
			if($this->noscan == 1 || $this->noscan == 2)
			{
				$withEvents = false;			
			}else{
				$withEvents = true;
			}
			
			if($this->noscan != 2)
			{
				$withTurnCalc = true;
			}else{
				$withTurnCalc = false;
			}
			
			$refreshCommand->setRefreshType($withEvents, $withTurnCalc);					
			
			$refreshController = new CR_RefreshUserInstance($refreshCommand);
			$refreshController->exec();		
							
//////////////////////////////////////////////////////////////////////////////////////////////////////////		
			
			if($notCellInfo && $this->cell)
			{
				$sendCellInfo = true;
			}else{
				$sendCellInfo = false;
			}
			
			SU_OutputHandler::Singleton()->addString($xmldoc->dump_mem() , ME_CombViewFormTotal::Exec(DE_Comb::CreateFromID((int)$this->comb), (int)$this->celltype, $sendCellInfo));
		
///////////////////////////////////////////////////////////////////////////////////////		
	
			// проверяем есть ли новые сообщения
			if(!($this->noscan == 1 || $this->noscan == 2))
			{
				$sql = "
						SELECT 
							id_message
						FROM 
							ds_userhasmessage
						WHERE 
							id_userinstance = ".$params["uiid"]."
							AND readdate IS null
						UNION ALL (
							SELECT 
								id_message
							FROM 
								ds_userhasmessagereserv
							WHERE 
								id_userinstance = ".$params["uiid"]."
								AND readdate IS null
						)";
				$res = $common->getResultArray ( "id_message_id_userinstance".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
				if($res === false)
				{
					$this->errormsg .= " SYSTEM ERROR - UA_Refresh::performAction7";
					return false;
				}

				if(sizeof($res) > 0) 
				{										
					SU_OutputHandler::Singleton()->addString('1' , 'newmessage');
				}
			}
		} else { // просто берем данные из сессии			
			if(!$notCellInfo)
			{
				$a = $refresh[$this->comb."_".$this->celltype];				
				SU_OutputHandler::Singleton()->addReadyString($a);				
			}			
		}

		// данные о ресурсах на соте
		if(key_exists("combres", $_SESSION))
		{
			$combres = $_SESSION["combres"];
		}else{
			$combres = array();
		}		

		if(!key_exists($this->comb, $combres) || $this->combres == 1) 
		{
			// актуализируем
			$xmldoc = domxml_new_xmldoc("1.0");
			$root = $xmldoc->add_root("combres");
			
			$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

			foreach(GB_ResourcesList::$allCombResources as $k=>$resourceName) 
			{
				$haveMax = $combAttributes->haveAttribute('MAX_AMOUNT', $resourceName);
				$haveCurrent = $combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName);

				if($haveMax || $haveCurrent) 
				{
					$ch = $root->new_child($resourceName);

					if($haveCurrent) 
					{
						$current = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);
						$ch->set_attribute ( "current", floor($current));
					}

					if($haveMax) 
					{
						$max = $combAttributes->getAttribute('MAX_AMOUNT', $resourceName);
						$ch->set_attribute ( "max",  floor($max) );
					}
				}
			}
			
			//Добавлеям бабло отображение
			$member = new MU_EntityOperationMember(
				DE_UserInstance::CreateFromID((int)$this->uiid)->getUser(), 
				DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName())
			);
			$moneyData = MU_SearchOperations::GetAccountData($member);
			foreach($moneyData as $moneyName => $moneyAmount)
			{								
				if(!key_exists($moneyName, MU_EntityMoneySumm::$allowCreditName))
				{
					continue;
				}
				
				$money = DE_Money::CreateFromShortName($moneyName);
				
				$ch = $root->new_child($money->getName());
				$ch->set_attribute("current", round($moneyAmount, 2));
			}
			
			$combres[$this->comb] = SU_OutputHandler::Singleton()->addString( $xmldoc->dump_mem() , 'combres' );

			$_SESSION["combres"] = $combres;
			
		} else { // если они есть в сессии, просто берем их оттуда
 			
			SU_OutputHandler::Singleton()->addReadyString( $combres[$this->comb] );
		}		
		
		
		$userInstance = DE_UserInstance::CreateFromID((int)$params['uiid']);				
		if(($userInstance->getLastRefresh()->value + (int)$this->worldSettings->get('REFRESH_RATE')) > time()) 
		{		
			$delta = ($userInstance->getLastRefresh()->value + (int)$this->worldSettings->get('REFRESH_RATE')) - time();
			
			//ВНИМАНИЕ!!!! ЭТО ЗАТЫКА!!! Люди жаловались что во время боя время рефреша увеличиваеться до 20-30 минут
			//правдо после перезахода вс енормлаьно становиться
			if($delta > (int)$this->worldSettings->get('REFRESH_RATE'))
			{
				$delta = (int)$this->worldSettings->get('REFRESH_RATE');
			}
			
			SU_OutputHandler::Singleton()->addString($delta , 'refresh_rate' );		
		}else{					
			SU_OutputHandler::Singleton()->addString( '0' , 'refresh_rate' );
		}
		
		if(isset($_SESSION['profi_game']) && $_SESSION['profi_game'] == true) 
		{
			SU_OutputHandler::Singleton()->addString( '1' , 'mode' );
		} else {			
			$mode = GL_HaveTrialPremium::GetMode(DE_User::CreateFromID((int)$_SESSION['uid']), (int)$_SESSION['dbId']);
			SU_OutputHandler::Singleton()->addString( $mode , 'mode' );					
		}

		
		//Оповещения о том что твариться во время рефреша при режиме отпуска
		$res = GL_IsUserInVocationMode::Test($userInstance);
		if(count($res) > 0)
		{
			//Пишем мессагу
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'VACATION_REFRESH_NOTICE', $messparams, 0);	
		}
		
		//Оповещение о том что рефреш прошел под силовым щито м и нихрена не делаеться.
		if(DE_PowerShild::CreateFromUserInstance($userInstance->getID())->isWorks())
		{
			$powerShild = DE_PowerShild::CreateFromUserInstance((int)$this->uiid);

			$timeDelta = DE_UserInstance::CreateFromID((int)$this->uiid)->getUser()->getTimeDelta();	
		
			$messparams = array(
				0 => $powerShild->getEndDate()->getFormatedFull($timeDelta)
			);
		
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_REFRESH_NOTICE', $messparams, 0);	
		}
		
		if(!GL_NewbDefendTestArmyPower::isStillHaveDefend())
		{
			$userInstance->clearNewbDefend();
			$messparams = array();//DE_WorldSettings::CreateForCurentWorld()->get('MAX_NEWB_ARMY_POWER');
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "CLEAR_NEWB_DEFEND_MAX_ARMY", $messparams, 0, 'YELLOW');	
		}
		
		return true;
	}
}

?>