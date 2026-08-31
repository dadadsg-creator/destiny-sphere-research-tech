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
					
			$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><refresh />";
			$xmldoc = domxml_open_mem ( $xml );
			$root = $xmldoc->document_element ();

			// берем текущее местоположение
			$sql = sprintf ( "select celltypename
					from ds_celltype
					where id_celltype = %d",
					$this->celltype );

			$res = $common->getResultArray ( "celltypenameq_id_celltype_".$this->celltype, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Refresh: Cannot get cell types"; // UA_Refresh::performAction5				
				return false;
			}
			


			$root->set_attribute ( "location", $res[0]['celltypename'] );
			
			$root->set_attribute ( "id_comb", $this->comb );

			$root->set_attribute ( "time", Date ( 'G' ) );


			
			// открыты ли Центральные Ворота						
			if(DS_Comb::HaveOpenCentralGate((int)$this->comb)) 
			{
				$root->set_attribute ( "gate", "1" );
			}


			// если надо передавать ячейки
			if($this->cell)
			{
				if($this->celltype != 2)
				{
					$sql = "
							SELECT 
								ce.cellnumber,
								ce.id_cell,
								gt.groundname
							FROM
								ds_cell AS ce
								INNER JOIN ds_groundtype AS gt ON (ce.id_groundtype = gt.id_groundtype)
							WHERE 
								ce.id_comb = ".$this->comb."
								and ce.id_celltype = ".$this->celltype."
							order by ce.cellnumber";
					$res = $common->getResultArray("comb_cells_id_comb_id_celltype_".$this->comb."_".$this->celltype, $sql, "", "", "GLOBAL_CACHE");
					if(!$res)
					{
						$this->errormsg .= " SYSTEM ERROR - UA_Refresh::performAction8";
						return false;
					}	

					for($i=0; $i<sizeof($res); $i++) 
					{
						$ch = $root->new_child ( "c" );
						$ch->set_attribute ( "t", $res[$i]['groundname'] );
						$ch->set_attribute ( "id_cell", $res[$i]['id_cell'] );

						$ch = "";
					}

				} elseif($this->celltype == 2) { 
					
					// для центральной части соты
					$mask[1] = array ( 0,10,20,21,31,32,33,43,44,45,46,56,57,58,59,60,70,71,72,73,74,75,85,86,87,88,89,90,91 ); 
					$mask[2] = array ( 0,2,3,4,5,6,7,8,12,13,14,15,16,17,23,24,25,26,27,35,36,37,38,48,49,50,62,63,77 );
					$mask[3] = array ( 0,19,29,30,40,41,42,52,53,54,55,65,66,67,68,69,79,80,81,82,83,84,94,95,96,97,98,99,100 );
					$mask[6] = array ( 0,118,119,120,121,122,123,124,134,135,136,137,138,139,149,150,151,152,153,163,164,165,166,176,177, 178,188,189,199 );
					$mask[5] = array ( 0,141,155,156,168,169,170,180,181,182,183,191,192,193,194,195,201,202,203,204,205,206,210,211,212, 213,214,215,216 );
					$mask[4] = array ( 0,127,128,129,130,131,132,133,143,144,145,146,147,148,158,159,160,161,162,172,173,174,175,185,186, 187,197,198,208 );

					$d = 5 - $this->combnumber;

					$sql = "
							SELECT 
								co.id_userinstance,
								co.combnumber, 
								ce.id_cell,
								ce.cellnumber,
								gt.groundname
							FROM 
								ds_comb AS co
								INNER JOIN ds_cell AS ce ON (co.id_comb = ce.id_comb)
								INNER JOIN ds_groundtype AS gt ON (gt.id_groundtype = ce.id_groundtype)
							WHERE 
								co.id_combcomplex = ".$this->combcomplex."
								and ce.id_celltype = ".$this->celltype."
							ORDER by co.combnumber, ce.cellnumber";
					$res = $common->getResultArray ( "comb_cells2_id_combcomplex_id_comb_id_celltype_".$this->combcomplex."_".$this->comb."_".$this->celltype, $sql, "", $params["uiid"], "LOCAL_CACHE" );
					if(!$res)
					{
						$this->errormsg .= " SYSTEM ERROR - UA_Refresh::performAction9";
						return false;
					}

					$i=0;

					for($e=0; $e<sizeof($res); $e++)
					{
						$i++;
						$cn = $res[$e]['combnumber'] + $d;

						if($cn > 6)
						{
							$cn = $cn - 6;
						}
						
						if($cn < 1)
						{
							$cn = $cn + 6;
						}

						$arr[$mask[$cn][$i]]["id_cell"] = $res[$e]['id_cell'];
						$arr[$mask[$cn][$i]]["combnumber"] = $cn;
						$arr[$mask[$cn][$i]]["GroundName"] = $res[$e]['groundname'];
						$arr[$mask[$cn][$i]]["uiid"] = $res[$e]['id_userinstance'];

						if($cn == 5)
						{
							$arr[$mask[$cn][$i]]["act"] = 1;
						}

						if($i == 28)
						{
							$i = 0;
						}
					}

					for($i=1; $i<218; $i++)
					{
						$ch = $root->new_child("c");
					
						if(isset($arr[$i]["act"]))
						{
							if($arr[$i]["act"] == 1)
							{
								$ch->set_attribute("act", "10");
							}
						}
					
						if(isset($arr[$i]["GroundName"]))
						{
							if($arr[$i]["combnumber"] != 5)
							{
								$ch->set_attribute("t", "FROG");
							} else {
								$ch->set_attribute("t", $arr[$i]["GroundName"]);
							}
						} else {
							if(isset($arr[$i]["id_cell"]))
							{
								$ch->set_attribute ( "t", "FROG" );								
							} else {
								$ch->set_attribute ( "t", "E_FIELD" );
							}
						}
						
						if(isset($arr[$i]["id_cell"]))
						{
							$ch->set_attribute ( "id_cell", $arr[$i]["id_cell"] );
						}
					}
				}
			}

			// информация о зданиях на соте
			$sql = "
				SELECT
    				ab.id_attachedbuilding,
					ab.id_cell,
					ab.id_building,
					ab.active,
					ab.underconstruction,	
					ab.inactivation,
					ab.inupgrade,
					ab.busy,
					ab.starttime,
					ab.endtime	
				FROM
    				ds_attachedbuilding AS ab	
					INNER JOIN ds_cell AS ce ON (ab.id_cell = ce.id_cell)
				WHERE
    				ab.id_comb = ".$this->comb."
					AND
    				ce.id_celltype = ".$this->celltype."
				ORDER BY ce.cellnumber";						
			$res = $common->getResultArray("", $sql);
			assert(empty($common->errormsg));
			
			foreach($res AS $i => $v)
			{
				$ch = $root->new_child("b");
				$ch->set_attribute("id", $v['id_building']);
				$ch->set_attribute("id_cell", $v['id_cell']);
				$ch->set_attribute("id_ab", $v['id_attachedbuilding']);
				
				if($v['underconstruction'] != 't' && $v['active'] == 'f')
				{
					$ch->set_attribute("ac", "0");
				}
				
				if($v['underconstruction'] == 't' || $v['inactivation'] == 't' || $v['inupgrade'] == 't')
				{
					$startDate = GB_TypeFactory::CreateBase('GB_Date', $v['starttime']);
					$endDate = GB_TypeFactory::CreateBase('GB_Date', $v['endtime']);
					
					$proc = (time() - $startDate->value) / ($endDate->value - $startDate->value) * 100;
					
					if($proc > 100)
					{
						$proc = 100;
					} elseif ($proc < 0) {
						$proc = 0;
					}
					
					$proc = number_format($proc, 2, ".", " ");
					
					$ch->set_attribute("enddate", $endDate->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta()));
					
					if($v['underconstruction'] == 't')
					{
						$ch->set_attribute("un", $proc);	
					} elseif ($v['inactivation'] == 't') {
						$ch->set_attribute("ia", $proc);
					} elseif ($v['inupgrade'] == 't') {
						$ch->set_attribute("up", $proc);
					} else {
						assert();
					}
				}
			}
			
			$refresh[$this->comb."_".$this->celltype] = SU_OutputHandler::Singleton()->addString($xmldoc->dump_mem() , 'refresh');
			
			$_SESSION["refresh"] = $refresh;

			if(isset($_SESSION["s_xmldata"]))
			{
				if(ereg("refresh", $_SESSION["s_xmldata"]))
				{
					$_SESSION["s_xmldata"] = "";
				}
			}
			
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