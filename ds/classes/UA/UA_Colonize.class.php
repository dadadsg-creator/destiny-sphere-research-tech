<?php $CVS_Version = '$Revision$ ; $Date$';

// обрабатывает команду на колонизацию соты



class UA_Colonize extends UA_Action {

	public $destination;
	public $id_module;
	public $dest_ring;
	public $dest_comb;
	public $dest_combcomplex;
	public $gate;
	public $wrongdestination;
	public $busycomb;
	public $colonyname;
	public $errorname;
	public $id_comb_to;
	public $id_ring_to;
	public $interring;
	
	function UA_Colonize () {
		UA_Action::UA_Action ();
	}

	function checkLocalParams () {

		global $common;

		// названия для колонии
		$ch = $this->root->get_elements_by_tagname ( "colonyname" );
		$node = $ch[0];
		$this->colonyname = utf8_decode ( $node->get_content() );

		if ( strlen ( $this->colonyname ) < 3 ) {
			$this->errorname[] = 'COMBNAME_TOO_SHORT';
		}

		if ( preg_match ( '/[\n\t\'\"\`\!\@\#\$\%\^\*\(\)\;\:\{\}\[\]\<\>\?\ ]/', $this->colonyname ) ) {  //" - проблема с парсерами подсветки =)
			$this->errorname[] = 'COMBNAME_HAS_WRONG_SYMBOLS';
		}

		if ( strlen ( $this->colonyname ) > 20 ) {
			$this->errorname[] = 'COMBNAME_TOO_LONG';
		}

		// Нельзя колонизировать с захватываемой колонии
		if (DE_Comb::CreateFromID((int)$this->comb)->getIsCapture()) {
			$this->errorname[] = 'CANT_COLONIZE_FROM_CAPTURE_COLONY';
		}
		
		if ( sizeof ( $this->errorname ) == 0 ) 
		{
			$sql = sprintf ( "select id_comb
				from ds_combname
				where upper(combname) = upper('%s')",
			$this->colonyname );

			$res = $common->getResultArray ( "", $sql, "", "", "" );
			
			if ( $res === false ) 
			{
				$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkLocalParams1";
				return false;
			}

			if ( sizeof ( $res ) > 0 ) {
				$this->errorname[] = 'COMBNAME_NOT_UNIQUE';
			}
		}

		if ( sizeof ( $this->errorname ) > 0 ) return true;

		// координаты колонии
		$this->destination = $this->root->get_attribute ( "destination" );
		if ( empty ( $this->destination ) ) {
			$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkLocalParams2";
			return false;
		}

		// явно заданы
		if(!ereg("rand", $this->destination))
		{
			if ( !preg_match ( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $this->destination ) ) {
				$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkLocalParams3";
				return false;
			}

			if ( strlen ( $this->destination ) > 50 ) {
				$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkLocalParams4";
				return false;
			}

			$dest = explode ( ".", $this->destination );

			$this->dest_ring = $dest[0];
			$this->dest_combcomplex = $dest[1];
			$this->dest_comb = $dest[2];

			if ( $this->dest_combcomplex == "" || $this->dest_comb == "" ) {
				$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkLocalParams5";
				return false;
			}
		} else {
			// любая свободная сота
			$dest = explode(".", $this->destination);

			$this->dest_ring = $dest[0];
			
			$id_comb = GL_GetCombForPlacement::Get($this->dest_ring, $_SESSION['world']);
			
			// Если свободных мест нет
			if($id_comb == 0)
			{
				$this->comb = 0;
				return true;
			}
				
			$comb = DE_Comb::CreateFromID($id_comb);

			$this->dest_combcomplex = $comb->getComplexNumber();
			
			$this->dest_comb = $comb->getNumber();
		}

		// id модуля
		$this->id_module = $this->root->get_attribute ( "id_module" );
		if ( empty ( $this->id_module ) ) {
			$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkLocalParams7";
			return false;
		}

		return true;
	}

	// проверяем допустимость действия
	function checkAction () {

		global $params;
		global $common;

		if ( sizeof ( $this->errorname ) > 0 ) return true;
		
		// Если свободных мест нет
		if($this->comb == 0)
		{
			$this->errorname[] = 'NO_FREE_SPACE_ON_MELS';
			return true;
		}

		// смотрим есть ли уже колония с этой соты
		$sql = sprintf ( "select id_comb_to
				from ds_colony
				where id_comb_from = %d",
		$this->comb );

		$res = $common->getResultArray ( "id_comb_to_id_comb_from_".$this->comb, $sql, "", "", "" );

		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkAction1";

			return false;
		}

		if ( sizeof ( $res ) > 0 ) {
			$this->errorname[] = 'ALREADY_COLONIZE_COMB';
			return true;
		}

		// смотрим что модуль не в движении
		$sql = sprintf ( "select uc.id_unitcontainer
				from ds_unitcontainer uc,
				ds_unitorcontainertype uct
				where uct.id_unitcontainertype = uc.id_unitcontainertype
				and uct.containertypename = 'COLONIZATION'
				and uc.id_basecomb = %d
				and uc.inaction = false
				and id_unitcontainer = %d",
		$this->comb,
		$this->id_module );
		$res = $common->getResultArray ( "id_unitcontainer_colony_".$this->comb, $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkAction3";

			return false;
		}

		// открыты ли ЦВ
		$sql = sprintf ( "select ab.id_attachedbuilding
				from ds_attachedbuilding ab,
				ds_building b
				where ab.id_comb = %d
				and ab.id_building = b.id_building
				and ab.active = true
				and b.buildingname = 'CENTRAL_GATE'",
		$this->comb );

		$res = $common->getResultArray ( "check_central_gate_id_comb_".$this->comb, $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkAction4";

			return false;
		}

		if ( sizeof ( $res ) == 0 ) { // closed central gate on own comb
		$this->errorname[] = 'NO_GATE2';
		return true;
		}

		// не достигнуто ли максимальное кол-во колоний
		$max_col = (int)$this->worldSettings->get('MAX_COLONIES');

		$max_col_premium_delta = (int)$this->worldSettings->get('MAX_COLONIES_PREMIUM_DELTA');


		if($_SESSION['profi_game'])
		{
			$max_col = $max_col + $max_col_premium_delta;
		}




		$sql =sprintf ( "select count(co.id_comb) as amount
				from ds_comb co,
				ds_combcomplex cc,
				ds_combcomplextype ct
				where co.id_combcomplex = cc.id_combcomplex
				and cc.id_complextype = ct.id_complextype
				and co.id_userinstance = %d
				and ct.complextype = 'COLONIZEABLE'
				and co.is_capture = 'f'",
		$params['uiid'] );

		$res = $common->getResultArray ( "colonies_amount_id_userinstance_".$params['uiid'], $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_Colonize::checkAction6";

			return false;
		}

		$amount = (int)$res[0]['amount'];

		if ( $amount >= $max_col ) {
			$this->errorname[] = 'MAX_COLONIES';
			return true;
		}

		// проверяем координаты колонии
		$sql = sprintf ( "
				select 
					co.id_comb,
					co.id_userinstance,
					ri.id_ring,
					rc.username
				from 
					ds_comb AS co
					INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
					INNER JOIN ds_ring AS ri ON (cc.id_ring = ri.id_ring)
					LEFT JOIN ds_reservedcomb AS rc ON (rc.id_comb = co.id_comb)
				where 
					ri.id_world = %d
					AND 
					co.combnumber = %d
					AND 
					cc.complexnumber = %d
					AND 
					ri.ringnumber = %d
					",
			$this->world,
			$this->dest_comb,
			$this->dest_combcomplex,
			$this->dest_ring );

		$res = $common->getResultArray ( "check_destination_id_world_id_dest_comb_id_dest_combcomplex_id_dest_ring_".$this->world."_".$this->dest_comb."_".$this->dest_combcomplex."_".$this->dest_ring, $sql, "", "", "NO_CACHE" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::checkAction7";

			return false;
		}

		if ( sizeof ( $res ) == 0 ) {

			$this->errorname[] = 'WRONG_COLONIZE_DESTINATION';
			return true;
		}

		// сота уже занята
		if( $res[0]['username'] != '')
		{
			assert(key_exists('uid', $_SESSION));
			$user = DE_User::CreateFromID($_SESSION['uid']);
			if($res[0]['username'] != $user->getName())
			{
				$this->errorname[] = 'BUSY_COMB';
				return true;
			}
		}
		if ( $res[0]['id_userinstance'] != '' ) {

			$this->errorname[] = 'BUSY_COMB';
			return true;
		}

		$this->id_comb_to = $res[0]['id_comb'];
		$this->id_ring_to = $res[0]['id_ring'];

		if ( $this->ring != $this->id_ring_to ) $this->interring = 1;

		$sql = sprintf ( "select *
				from ds_colony
				where id_comb_to = %d",
		$this->id_comb_to );

		$res = $common->getResultArray ( "check_destination_busy_".$this->id_comb_to, $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::checkAction8";

			return false;
		}

		// к соте уже идет чей-то модуль
		if ( sizeof ( $res ) > 0 ) {
			$this->errorname[] = 'BUSY_COMB';
			return true;
		}

		return true;
	}

	// выполняем действие
	function performAction () {

		global $params;
		global $common;

		if ( sizeof ( $this->errorname ) > 0 )
		{
			// если есть ошибки, отправляем системное сообщение и выходим

			$sql = sprintf ( "select ltl.text as message,
					sm.numberofparams
					from ds_localizedtextlanguage ltl,
					ds_systemmessage sm
					where ltl.id_localizedtext = sm.id_localizedtext
					and ltl.id_language = %d
					and sm.messagename = '%s'",
			$params['lang'],
			$this->errorname[0] );

			$res2 = $common->getResultArray ( "system_message_errorname_id_language_".$this->errorname[0]."_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res2 ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Colonize::performAction1";

				return false;
			}

			$message = $res2[0]['message'];

			$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><colonizestatus />";
			$doc = domxml_open_mem ( $xml );
			$root = $doc->document_element ();

			$elem = $doc->create_text_node ( urlencode ( $message ) );
			$root->append_child ( $elem );


			//$panel = "xmldata=";
			//$panel .= $doc->dump_mem();

			//$this->logResponse ( $panel );

			// print $panel;

			SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

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
		
		// получаем время окончания эвента
		if ( !$this->getEventEndTime () ) {
			return false;
		}

		// записываем название колонии
		$sql = sprintf ( "insert into DS_CombName (ID_Comb, CombName) values ( %d, '%s')",
		$this->id_comb_to, $this->colonyname );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - Cannot set colonyname ".$this->colonyname." to comb ".$this->id_comb_to; //UA_Colonize::performAction2
			//
			return false;
		}

		// столбим юзеру колонию
		$sql = sprintf ( "insert into ds_colony ( id_comb_from, id_comb_to ) values ( %d, %d )",
		$this->comb, $this->id_comb_to );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::performAction3";

			return false;
		}

		// пишем модулю действие
		$sql = sprintf ( "update ds_unitcontainer
				set inaction = true
				where id_unitcontainer = %d",
		$this->id_module );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::performAction4";

			return false;
		}

		$sql = sprintf("
				insert into ds_unitcontaineraction
					( id_unitcontainer, id_unitcontaineractiontype, id_startcomb, id_targetcomb, actionstarttime, actionendtime, id_userinstance_from, id_userinstance_to )
				values 
					( %d, (select id_unitcontaineractiontype
				from ds_unitcontaineractiontype
				where actiontypename = 'COLONIZE'), %d, %d, '%s', '%s', %d, %d )",
		$this->id_module, $this->comb, $this->id_comb_to, $this->starttime, $this->endtime, $this->uiid, $this->uiid );
		//DebugTools::logAll('$this->starttime, $this->endtime'."=>".$this->starttime." ||||| ".$this->endtime, '!zya_UA_Colonize.log');

		$res = $common->executeQuery ( $sql );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::performAction5";

			return false;
		}

		// создаем эвент
		$this->createEvent('COLONIZE');

		$sql = sprintf ( "insert into ds_unitcontainerbindeventqueue
				( id_unitcontainer, id_eventqueue )
				values ( %d, %d )",
		$this->id_module, $this->eqid );

		$res = $common->executeQuery ( $sql );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::performAction8";

			return false;
		}

		$messparams[0] = $this->colonyname;
		$messparams[1] = $this->dest_ring.".".$this->dest_combcomplex.".".$this->dest_comb;		

		$sql = sprintf ( "select ltl.text as message,
				sm.numberofparams
				from ds_localizedtextlanguage ltl,
				ds_systemmessage sm
				where ltl.id_localizedtext = sm.id_localizedtext
				and ltl.id_language = %d
				and sm.messagename = 'COLONIZE_MESS5'",
		$params['lang'] );

		$res2 = $common->getResultArray ( "system_message_colonyze_mess5_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res2 ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::performAction9";

			return false;
		}

		$message = $res2[0]['message'];

		// посылаем юзеру сообщения прямо в отктытое меню
		for ($e=0; $e<$res2[0]['numberofparams']; $e++) {
			$pos = strpos($message, "%");
			if ($pos === false) {
				$this->errormsg .= " SYSTEM ERROR - UA_Colonize::performAction10";

				return false;
			}
			$message = substr_replace($message, $messparams[$e], $pos, 1);
		}

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><colonizestatus />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();

		$root->set_attribute ( "valid", "1" );

		$elem = $doc->create_text_node ( urlencode ( $message ) );
		$root->append_child ( $elem );


		//$panel = "xmldata=";
		//$panel .= $doc->dump_mem();

		//$this->logResponse ( $panel );

		// print $panel;

		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

		return true;
	}

	// получаем время окончания эвента
	function getEventEndTime()
	{
		global $common;
		global $params;

		$trevelTime = new GL_TravelTime();
		$this->traveltime = $trevelTime->Count(DE_Comb::CreateFromID((int)$this->comb), DE_Comb::CreateFromID((int)$this->id_comb_to),GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1))->value;

		$sql = sprintf ( "select now() as starttime,
				now()+
				(select (text((%f
				)))::interval) as endtime",
		$this->traveltime);

		$res = $common->getResultArray ( "starttime_endtime", $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_Colonize::getEventEndTime6";
			return false;
		}
		$this->endtime = $res[0]['endtime'];
		$this->starttime = $res[0]['starttime'];

		return true;
	}

	function createEventParams () {

		global $params;

		$this->eventparams["ID_CombFrom"] = $this->comb;
		$this->eventparams["ID_CombTo"] = $this->id_comb_to;
		$this->eventparams["ID_UserInstance"] = $params["uiid"];
		$this->eventparams["ID_Language"] = $params["lang"];
		$this->eventparams["ID_World"] = $this->world;
		$this->eventparams["Stage"] = 1;
		$this->eventparams["ID_Module"] = $this->id_module;
		$this->eventparams["TravelTime"] = $this->traveltime;

		return true;
	}
}

?>