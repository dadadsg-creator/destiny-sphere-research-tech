<?php $CVS_Version = '$Revision$ ; $Date$';

// команда создать армию
class UA_CreateArmy extends UA_Action {

	public $armyname;
	public $armyunits;
	public $errorname;

	function UA_CreateArmy () {
		UA_Action::UA_Action ();
		$this->armyunits = array ();
		$this->errorname = array ();
	}

	// проверяем параметры
	function checkLocalParams () {

		global $common;
		global $params;

		//if ( $_SESSION['profi_game'] )
		//{
		$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}

		// название армии
		$ch = $this->root->get_elements_by_tagname ( "armyname" );
		$node = $ch[0];
		$this->armyname = trim ( utf8_decode ( $node->get_content() ) );

		if ( strlen ( $this->armyname ) < 3 ) {
			$this->errorname[] = 'ARMY_NAME_TOO_SHORT';
		}

		if(!preg_match('/^[ .a-zA-Z0-9_-]+$/', $this->armyname)) {
			$this->errorname[] = 'ARMY_NAME_HAS_WRONG_SYMBOLS';
		}

		if ( strlen ( $this->armyname ) > 20 ) {
			$this->errorname[] = 'ARMY_NAME_TOO_LONG';
		}

		// уникальность названия
		if ( sizeof ( $this->errorname ) == 0 ) {

			$sql = sprintf("select id_unitcontainer
				from ds_unitcontainer
				where upper(unitcontainername) = upper('%s')",
			$this->armyname );

			$res = $common->getResultArray ( "id_unitcontainer_armyname_".$this->armyname, $sql, "", "", "" );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::checkLocalParams1";

				return false;
			}

			if ( sizeof ( $res ) > 0 ) {
				$this->errorname[] = 'ARMY_NAME_NOT_UNIQUE';
			}
		}

		// юниты армии
		$unit_xml = $this->root->get_elements_by_tagname ( "unit" );

		for ( $i=0; $i < sizeof ( $unit_xml ); $i++ ) {
			$un = $unit_xml[$i];
			$this->armyunits[$un->get_attribute ( "id" )] = (int)number_format ( $un->get_attribute ( "count" ), 0, "", "" );
			if ( $this->armyunits[$un->get_attribute ( "id" )] <= 0 ) {
				$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::checkLocalParams2";
				return false;
			}
		}

		if ( sizeof ( $this->armyunits ) == 0 ) {
			$this->errorname[] = 'NO_ARMY_UNITS';
		}

		return true;
	}

	// проверяем допустимость действия
	function checkAction () {

		global $common;
		global $params;

		$common->clearLocalCache ( $params["uiid"] );

		// проверяем не идет ли на соте бой
		$sql = sprintf ( "select id_battle
				from ds_battle
				where id_battlecomb = %d
				and battleenddate is null",
		$this->comb );

		$res = $common->getResultArray ( "id_battle_id_comb_".$this->comb, $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::checkAction1";

			return false;
		}
		if ( sizeof ( $res ) > 0 ) {
			$this->errorname[] = 'BATTLE_IN_PROGRESS';
		}

		if ( sizeof ( $this->errorname ) > 0 ) return true;

		// по каждому типу юнитов проверяем есть ли они в наличии
		foreach ( $this->armyunits as $id_unit => $count )
		{
			$sql = sprintf ( "
				SELECT uchu.amount
				FROM
					ds_unitcontainer AS uc,
					ds_unitcontainerhasunit AS uchu
				WHERE
						uc.id_unitcontainer = uchu.id_army
					AND uc.id_basecomb = %d
					AND uc.isdefault = true
					AND uchu.id_unit = %d
					-- AND uchu.amount >= %d",
			$this->comb, $id_unit, $count);
			$res = $common->getResultArray ( "amount_id_userinstance_id_unit_".$params["uiid"]."_".$id_unit, $sql, "", "", "" );
			assert(empty($common->errormsg));
			if(count($res) == 0 || (float)$res[0]['amount'] < (float)$count)
			{
				ds_error('You try use more units thet you have. ('.$id_unit.', '.$count.', '.$this->comb.')', 1, 1, 1, 0, 'CREATE_ARMY');
			}
		}

		// проверяем ограничение на общее кол-во армий на текущей соте
		if((DS_Army::GetCountUserArmiesOnComb(DE_Comb::CreateFromID($this->comb)) + 1) > DE_WorldSettings::CreateForCurentWorld()->get('MAX_USER_ARMIES_PER_COMB'))
		{
			$this->errorname[] = 'PER_COMB_ARMY_LIMIT';
		}

		// проверяем ограничение на общее кол-во армий на всех сотах
		if((DS_Army::GetTotalCountUserArmies($this->uiid) + 1) > DE_WorldSettings::CreateForCurentWorld()->get('MAX_USER_ARMIES_TOTAL'))
		{
			$this->errorname[] = 'TOTAL_ARMY_LIMIT';
		}

		return true;
	}

	// совершаем действие
	function performAction () {

		global $params;
		global $common;

		if ( $_SESSION['profi_game'] ){
			$noCellData = true;
		} else {
			$noCellData = false;
		}

		if ( sizeof ( $this->errorname ) == 0 ) {


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

			// создаем армию
			$sql = sprintf ( "insert into ds_unitcontainer
					( id_basecomb, id_unitcontainertype, id_userinstance, moveable,
					inaction, unitcontainername, isdefault ) values
					( %d, 1, %d, true, false, '%s', false )",
			$this->comb, $params["uiid"], $this->armyname );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::performAction1";

				return false;
			}

			// получаем ее id
			$sql = sprintf ( "select max(id_unitcontainer) as id_army
					from ds_unitcontainer
					where id_userinstance = %d
					and isDefault = false",
			$params["uiid"] );

			$res = $common->getResultArray ( "id_unitcontainer_id_userinstance_".$params["uiid"], $sql, "", "", "" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::performAction2";

				return false;
			}

			$id_army = $res[0]['id_army'];

			// id дефолтовой армии соты
			$sql = sprintf ( "select id_unitcontainer as id_default_army
					from ds_unitcontainer
					where id_basecomb = %d
					and isdefault = true",
			$this->comb );

			$res = $common->getResultArray ( "id_default_army_id_comb_".$this->comb, $sql, "", "", "" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::performAction3";

				return false;
			}

			$id_default_army = $res[0]['id_default_army'];

			// трансферим юниты в новую армию
			foreach ( $this->armyunits as $id_unit => $count ) {

				$count = (int)$count;

				$sql = sprintf ( "insert into ds_unitcontainerhasunit
						( id_army, id_unit, amount )
						values ( %d, %d, %d )",
				$id_army, $id_unit, $count );

				$res = $common->executeQuery ( $sql );
				if ( $res === false ) {
					$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::performAction4";

					return false;
				}

				// смотрим сколько осталось в дефолтовой армии
				$sql = sprintf ( "select uchu.amount
						from ds_unitcontainerhasunit uchu,
						ds_unitcontainer uc
						where uchu.id_army = uc.id_unitcontainer
						and uc.isdefault = true
						and uc.id_basecomb = %d
						and uchu.id_unit = %d",
				$this->comb,
				$id_unit );

				$res = $common->getResultArray ( "amount_id_userinstance_id_unit_".$params["uiid"]."_".$id_unit, $sql, "", $params["uiid"], "LOCAL_CACHE" );
				if ( !$res ) {
					$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::performAction5";

					return false;
				}

				$default_amount = intval((real)$res[0]['amount']);

				// если не осталось удаляем, если осталось - вычиаем
				if ( $count >= $default_amount ) {

					$sql = sprintf ( "delete from ds_unitcontainerhasunit
							where id_army = %d
							and id_unit = %d",
					$id_default_army, $id_unit );

				} else {

					$sql = sprintf ( "update ds_unitcontainerhasunit
							set amount = amount - %d
							where id_army = %d
							and id_unit = %d",
					$count, $id_default_army, $id_unit );

				}

				$res = $common->executeQuery ( $sql );
				if ( $res === false ) {
					$this->errormsg .= " SYSTEM ERROR UA_CreateArmy::performAction6";

					return false;
				}
			}

			// название соты
			$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

			$messparams[1] = "{b} ".$this->armyname."{/b}";

			// системное сообщение
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "CREATE_ARMY_MESS", $messparams, 0 );

			$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><armycreate />";
			$doc = domxml_open_mem ( $xml );
			$root = $doc->document_element ();

			// Флаг_успешности_создания_армии.новое_число_существующих_армий_на_соте:максимальное_колво_армий_на_соте.новое_число_существующих_армий_на_ВСЕХ_сотах:максимальное_колво_армий_на_ВСЕХ_сотах
			$elem = $doc->create_text_node ('1.'.DS_Army::GetCountUserArmiesOnComb(DE_Comb::CreateFromID($this->comb)).":".$this->worldSettings->get('MAX_USER_ARMIES_PER_COMB').".".DS_Army::GetTotalCountUserArmies($params["uiid"]).":".$this->worldSettings->get('MAX_USER_ARMIES_TOTAL'));
			$root->append_child ( $elem );
			SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );

			// Проверяем смену лиги
			$this->_checkLevelLeague($id_army);

		} else { // если какая-нибудь ошибка - отправляем сообщение и выходим

			$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><armycreate />";
			$doc = domxml_open_mem ( $xml );
			$root = $doc->document_element ();
			// Флаг_успешности_создания_армии.новое_число_существующих_армий_на_соте:максимальное_колво_армий_на_соте.новое_число_существующих_армий_на_ВСЕХ_сотах:максимальное_колво_армий_на_ВСЕХ_сотах
			$elem = $doc->create_text_node ('0.'.DS_Army::GetCountUserArmiesOnComb(DE_Comb::CreateFromID($this->comb)).":".$this->worldSettings->get('MAX_USER_ARMIES_PER_COMB').".".DS_Army::GetTotalCountUserArmies($params["uiid"]).":".$this->worldSettings->get('MAX_USER_ARMIES_TOTAL'));
			$root->append_child ( $elem );
			SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );

			$messparams[0] = "";
			for ( $i=0; $i < sizeof ( $this->errorname ); $i++ ) {
				DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errorname[$i], $messparams );
			}
		}

		$res = "";
		$sql = "";

		if ( !$this->refresh ( 2, "", "", "", $noCellData ) ) {
			return false;
		}

		return true;
	}

	private function _checkLevelLeague($id_army)
	{
		// Если лиги выключены, ничего не делаем
		if (DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') != 1) {
			return;
		}

		// Текущая лига
		$CurLeague = DE_UserInstance::CreateFromID($this->uiid)->getLeagueId();

		// Если лига равна максимальной, то нет смысла что-то считать
		if ($CurLeague == DS_League::GetMaxLeague()) {
			return;
		}

		// Проверяем количество звезд данной армии
		$armyStars = DE_Army::CreateFromID($id_army)->getArmyStars();

		// Текущая лига
		$CurLeague = DE_UserInstance::CreateFromID($this->uiid)->getLeagueId();

		// Лига, соответствующая по суммарному весу армий
		$league = DS_League::GetLeagueOneArmye($armyStars);

		// Если есть соответствующая времени жизни лига и она больше от текущей
		if ($league && ($league['id_league'] > $CurLeague)) {
			//assert($league['id_league'] > $CurLeague);
			// Меняем лигу
			DE_UserInstance::CreateFromID($this->uiid)->setLeagueId((int)$league['id_league']);

			// Алерт
			$messparams[0] = $this->armyname;
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
}

?>