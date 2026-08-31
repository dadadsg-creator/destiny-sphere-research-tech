<?php $CVS_Version = '$Revision$ ; $Date$';

// трансфер юнитов между своими армиями
class UA_TransferUnit extends UA_Action
{

	public $id_army_from;

	public $id_army_to;

	public $armyname_from;

	public $armyname_to;

	public $id_unit;

	public $count;

	public $transfers;

	// флаг о необходимости перестройки меню в случае ошибки
	private $needRebuildMenu = false;

	function UA_TransferUnit()
	{
		UA_Action::UA_Action();
	}

	// проверяем параметры
	function checkLocalParams()
	{
		// список юнитов
		$unit_xml = $this->root->get_elements_by_tagname ( "unit" );

		assert(count($unit_xml) > 0);

		foreach($unit_xml as $k=>$un)
		{// по каждому типу юнитов - сколько и в какую армию

			$unitID = (int)$un->get_attribute("id");
			assert($unitID > 0);
			$this->armyunits["id"] = $unitID;

			$unitsCount = (int)$un->get_attribute ("count");
			assert($unitsCount > 0);
			$this->armyunits["count"] = $unitsCount;

			$armyFromID = (int)$un->get_attribute("id_army_from");
			assert($armyFromID > 0);
			$this->armyunits["id_army_from"] = $armyFromID;

			$armyToID = (int)$un->get_attribute("id_army_to");
			assert($armyToID > 0);
			$this->armyunits["id_army_to"] = $armyToID;

			$this->transfers[$k] = $this->armyunits;
		}

		return true;
	}

	// проверяем допустимость действия
	function checkAction ()
	{
		global $common;
		global $params;

		foreach($this->transfers as $i=>$transfer)
		{// по каждому типу юнитов

			// проверяем армию откуда
			$sql = "
					SELECT
						id_unitcontainer,
						unitcontainername
					FROM 
						ds_unitcontainer
					WHERE
						id_unitcontainer = ".$transfer['id_army_from']."
						AND
						id_userinstance = ".$params["uiid"]."
						AND
						id_basecomb = ".$this->comb."
						AND 
						inaction = false";
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			if(count($res) == 0)
			{
				$this->needRebuildMenu = true;
				return true;
				ds_error('Sorce army to trasfer is lost. Try another one. ('.$transfer['id_army_from'].')', 1, 1, 1, 0, 'SRC_ARMY_TRANSFERED');
			}

			$this->transfers[$i]['army_from_name'] = trim($res[0]['unitcontainername']);

			// проверям армию куда
			$sql = "
					SELECT
						id_unitcontainer,
						unitcontainername
					FROM 
						ds_unitcontainer
					WHERE
						id_unitcontainer = ".$transfer['id_army_to']."
						AND 
						id_userinstance = ".$params["uiid"]."
						AND 
						id_basecomb = ".$this->comb."
						AND
						inaction = false";								
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			if(count($res) == 0)
			{
				$this->needRebuildMenu = true;
				return true;
				ds_error('Target army to trasfer is lost. Try another one. ('.$transfer['id_army_to'].')', 1, 1, 1, 0, 'DST_ARMY_TRANSFERED');
			}

			$this->transfers[$i]['army_to_name'] = trim($res[0]['unitcontainername']);

			$transferAmount = ceil($transfer['count']);

			// есть ли юниты в наличии
			$sql = "
					SELECT 
						amount
					FROM 
						ds_unitcontainerhasunit
					WHERE 
						id_army = ".$transfer['id_army_from']."
						AND
						id_unit = ".$transfer['id']."";
			$res = $common->getResultArray ( "", $sql, "", "", "" );
			assert(empty($common->errormsg));

			if(count($res) == 0)
			{
				$this->needRebuildMenu = true;
				return true;
				ds_error('Sorce army dont have this type of units. ('.$transfer['id_army_from'].', '.$transfer['id'].')', 1, 1, 1, 0, 'CREATE_ARMY');
			}

			$amount = (int)number_format(ceil($res[0]['amount']), 0, ".", "" );
			if($transferAmount > $amount)
			{
				$this->needRebuildMenu = true;
				return true;
				ds_error('Sorce army dont have need count of this type of units. ('.$transfer['id_army_from'].', '.$transfer['id'].', '.$transferAmount.')', 1, 1, 1, 0, 'CREATE_ARMY');
			}
		}

		return true;
	}

	// выполняем действие
	function performAction ()
	{
		global $params;

		if($this->haveProcessedErrors)
		{
			return true;
		}

		// если в меню были устаревшие данные, то перестраиваем
		if($this->needRebuildMenu)
		{
			$this->_rebuildMenu();
			return true;
		}

		if ( $_SESSION['profi_game'] )
		{
			$noCellData = true;
		} else {
			$noCellData = false;
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

		/*$lockActionID = __CLASS__."_".$_SESSION["uiid"];
		if(SU_Locker::CerateForCurrentDB('ACTION')->isLocked($lockActionID))
		{
		DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $lockActionID, 'ACTION');
		}else{
		SU_Locker::CerateForCurrentDB('ACTION')->lock($lockActionID);
		};*/

		/*if(SU_Locker::CerateForCurrentDB('REFRESH')->isLocked($_SESSION["uiid"]))
		{
		DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $_SESSION["uiid"], 'REFRESH');
		}else{
		SU_Locker::CerateForCurrentDB('REFRESH')->lock($_SESSION["uiid"]);
		};*/

		// Текущая лига игрока
		$CurLeague = DE_UserInstance::CreateFromID($this->uiid)->getLeagueId();

		for ( $i=0; $i<sizeof ( $this->transfers ); $i++ ) { // по каждому типу юнитов

			$sql = sprintf ( "select uchu.amount
					from ds_unitcontainerhasunit uchu
					where uchu.id_army = %d
					and uchu.id_unit = %d",
			$this->transfers[$i]['id_army_from'],
			$this->transfers[$i]['id'] );
			$res = $this->common->getResultArray ( "amount_id_unitconainer_id_unit_".$this->transfers[$i]['id_army_from']."_".$this->transfers[$i]['id'], $sql, "", "", "" );
			assert(empty($this->common->errormsg));
			assert(count($res)>0);

			$default_amount = (float)$res[0]['amount'];
			$transferCount = floor((float)$this->transfers[$i]['count']);

			if($default_amount <= $transferCount)
			{
				$delta = $default_amount;
			}else{
				$delta = $transferCount;
			}

			// вычитаем юниты
			if($default_amount <= $delta)
			{
				$sql = sprintf ( "delete from ds_unitcontainerhasunit
						where id_army = %d
						and id_unit = %d",
				$this->transfers[$i]['id_army_from'],
				$this->transfers[$i]['id'] );
			}else{
				$sql = sprintf ( "update ds_unitcontainerhasunit
						set amount = amount - %d
						where id_army = %d
						and id_unit = %d",
				$delta,
				$this->transfers[$i]['id_army_from'],
				$this->transfers[$i]['id'] );
			}
			$res = $this->common->executeQuery($sql);
			assert( empty( $this->common->errormsg));

			// добавляем юниты
			$sql = sprintf ( "select id_army
					from ds_unitcontainerhasunit
					where id_army = %d
					and id_unit = %d",
			$this->transfers[$i]['id_army_to'],
			$this->transfers[$i]['id'] );

			$res = $this->common->getResultArray ( "is_army_has_this_units", $sql, "", "", "" );

			assert( empty( $this->common->errormsg ) );

			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR UA_TransferUnit::Cant add to army ".$this->transfers[$i]['id_army_to']." units ".$this->transfers[$i]['id'];

				return false;
			}

			if ( sizeof ( $res ) == 0 ) {

				$sql = sprintf ( "insert into ds_unitcontainerhasunit
						( id_army, id_unit, amount )
						values ( %d, %d, %d )",
				$this->transfers[$i]['id_army_to'],
				$this->transfers[$i]['id'],
				$delta );

			} else {

				$sql = sprintf ( "update ds_unitcontainerhasunit
						set amount = amount + %d
						where id_army = %d
						and id_unit = %d",
				$delta,
				$this->transfers[$i]['id_army_to'],
				$this->transfers[$i]['id'] );
			}

			$res = $this->common->executeQuery ( $sql );

			assert( empty( $this->common->errormsg ) );

			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR UA_TransferUnit::performAction4";

				return false;
			}

			// если в армии не осталось юнитов и армия не дефолтовая - удаляем армию
			$sql = "SELECT id_army
					FROM ds_unitcontainerhasunit
					WHERE id_army = ".$this->transfers[$i]['id_army_from'];
			$res = $this->common->getResultArray ("", $sql, "", "", "");
			assert(empty($this->common->errormsg));

			if(count($res) == 0)
			{
				$sql = "DELETE FROM ds_unitcontainer
						WHERE 
							id_unitcontainer = ".$this->transfers[$i]['id_army_from']."
							AND
							isdefault = false";
				$res = $this->common->executeQuery ( $sql );
				assert(empty($this->common->errormsg));
			}

			// название соты
			$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

			$messparams[1] = "{b} ".$this->transfers[$i]['count']."{/b}";

			// название юнита
			$sql = sprintf ( "select ltl.text as unitname
					from ds_unit un,
					ds_localizedtextlanguage ltl
					where un.id_unit = %d
					and un.id_localizedname = ltl.id_localizedtext
					and ltl.id_language = %d",
			$this->transfers[$i]['id'],
			$params["lang"] );

			$res = $this->common->getResultArray ( "unitname_id_unit_id_language_".$this->transfers[$i]['id']."_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

			assert( empty( $this->common->errormsg ) );

			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR UA_TransferUnit::performAction8";
				return false;
			}

			$messparams[2] = "{b} ".$res[0]['unitname']."{/b}";
			$messparams[3] = "{b} ".$this->transfers[$i]['army_from_name']."{/b}";
			$messparams[4] = "{b} ".$this->transfers[$i]['army_to_name']."{/b}";

			// системное сообщение
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "TRANSFER_UNIT_MESS", $messparams );

			$default_amount = "";
		}

		// Если лиги включены и текущая лига не равна максимальной
		if (DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') == 1 && $CurLeague != DS_League::GetMaxLeague()) {
			// Проверяем вес данной армии
			$armyStars = DE_Army::CreateFromID($this->transfers[0]['id_army_to'])->getArmyStars();

			// Лига, соответствующая по суммарному весу армий
			$league = DS_League::GetLeagueOneArmye($armyStars);

			// Если есть соответствующая времени жизни лига и она больше от текущей
			if ($league && ($league['id_league'] > $CurLeague)) {
				//assert($league['id_league'] > $CurLeague);
				// Меняем лигу
				DE_UserInstance::CreateFromID($this->uiid)->setLeagueId((int)$league['id_league']);

				// Алерт
				$messparams[0] = $this->transfers[$i]['army_to_name'];
				$messparams[1] = $league['league_name'];

				$alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE_ONE_ARMIY', $messparams, null, null, false);

				$alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
	<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';

				SU_OutputHandler::Singleton()->addReadyString($alertText);

				// Сообщение
				/*$messparams[0] = $this->armyname;
				$messparams[1] = $league['league_name'];
				$date = GB_TypeFactory::CreateConstant('GB_Date', time());
				// Командор, стало известно, что Вы являетесь счастливым обладателем армии %, мощность которой является слишком высокой для той лиги, в которой вы состоите. Принято решение перевести вас в лигу %!'
				DE_SystemMessages::AddMessageFromMessageCompiler($date, (int)$this->uiid, 'NEXT_LEAGUE_ONE_ARMIY', $messparams, 0, 'BLUE');*/

				// Сообщение в зеленку
				/*$messageScopeID = DS_Message::GetMessageScopeIDByName('PRIVATE');
				assert($messageScopeID > 0);
				$senderName = 'DestinySphere';
				$subject = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE', array(), null, null, false);
				$recieverName = DE_User::CreateFromID(DE_UserInstance::CreateFromID((int)$this->uiid)->getUserID())->getName();
				$fromUIID = 0;

				$message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE_ARMIES', $messparams, null, null, false);
				$messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);

				DS_Message::AddMessageToUser($messageID, (int)$this->uiid);*/
			}
		}


		$res = "";
		$sql = "";

		// рефреш с актуализацией ресурсов
		if(!$this->refresh(2, "", "", 1, $noCellData))
		{
			return false;
		}

		if(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true))
		{
			// SU_DebugTools::LogAll('start', 'Annita.log');

			$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><new_armypower />";
			$doc = domxml_open_mem ( $xml );
			$root = $doc->document_element ();

			$combDefendArmys = DC_CombDefendArmys::CreateFromCombID((int)$this->comb);

			$combDefendArmys->resetNext();
			while($army = $combDefendArmys->next())
			{
				$ch = $root->new_child ( "army" );
				$ch->set_attribute ( "id_army", $army->getID() );

				$armyStars = GL_ArmyPowerToStars::CountForMenu($army->getArmyPower());
				$ch->set_attribute ( "armypower", $armyStars);

				$armyPower = $army->getArmyPower()/10;
				$ch->set_attribute ( "PW", round($armyPower));
			}

			SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );
		}

		return true;
	}

	private function _rebuildMenu()
	{
		$menu = new PN_ArmyOverview();
		// чтобы получить список табов в менюшке нужно знать BID
		// последний полученный BID храниться в сессии
		/*
		if(key_exists('UA_LAST_BID', $_SESSION))
		{
		$bid = (int)$_SESSION['UA_LAST_BID'];
		}
		if(key_exists('UA_LAST_TAB', $_SESSION))
		{
		$tab = $_SESSION['UA_LAST_TAB'];
		}
		*/

		$bid = 26; // военная база
		$tab = 'PN_ArmyOverview';

		$menu->setBID($bid);
		$menu->setTAB($tab);

		assert($menu->getPanel());
	}
}
?>