<?

// различные базовые действия
// создание сотового комплекса
// создание юзера
// подготовка соты к заселению
// поселение юзера на соту

include_once ( 'classfactory.php' );

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}


class WorldUtils {

	var $errormsg;
	var $combresupdate;
	var $debug = false;
	var $counter = 0;

	function WorldUtils () {
		$this->combresupdate = array ();
	}

	function setDebug($val){
		$this->debug = $val;
	}

	// создание сотового комплекса
	function createCombComplex ( $worldid, $ringnum, $complextypeid, $count ) {

		global $common;

		if ( empty ( $worldid ) || empty ( $ringnum )|| empty ( $complextypeid ) || empty ( $count ) ) {
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex1";
			return false;
		}

		$this->procOut('Начало создания сот.');

		$common->executeQuery ( "begin" );
		$common->executeQuery( "set STATEMENT_TIMEOUT to 0" );

		$sql = sprintf ( "select id_ring
				from ds_ring
				where id_world = %d
				and ringnumber = %d",
				$worldid, $ringnum );

		$res = $common->getResultArray ( "id_ring_id_world_ringnumber_".$worldid."_".$ringnum, $sql, "", "", "" );
		if ( !$res ){
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex2";
			return false;
		}

		$ringid = $res[0]['id_ring'];

		$sql = sprintf ( "select max(ComplexNumber) as maxnumber
				from DS_CombComplex co,
				DS_World wo, DS_Ring r
				where r.ID_World = wo.ID_World
				and co.ID_Ring = r.ID_Ring
				and r.ID_Ring = %d
				and wo.ID_World = %d",
				$ringid, $worldid );

		$res = $common->getResultArray ( "maxnuber_id_ring_id_world_".$ringid."_".$worldid."_".$ringnum, $sql, "", "", "" );
		if ( $res === false ){
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex3";
			return false;
		}

		$maxnumber = $res[0]['maxnumber'];
		if ( $maxnumber == "" ) $maxnumber = 0;

		for ( $i=0; $i<$count; $i++ ) {

			$d = ++$maxnumber;
			$sql = sprintf ( "insert into DS_CombComplex (id_ring, id_complextype, complexnumber) values (%d, %d, %d)",
					$ringid, $complextypeid, $d );

			$this->procOut('Создаем сотовый комплекс № '.$d);

			$res = $common->executeQuery ( $sql );
			if ( $res === false ){
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex4";
				return false;
			}
		}


		$sql = "select max(id_combcomplex) as maxnumber
			from DS_CombComplex";

		$res = $common->getResultArray ( "maxnuber", $sql, "", "", "" );
		if ( !$res ){
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex5";
			return false;
		}

		$maxnumber = $res[0]['maxnumber'];

		$c = ($maxnumber-$count)+1;

		for ( $i = $c; $i<=$maxnumber; $i++ ) {

			for ( $e=1; $e<=6; $e++ ) {

				$sql = sprintf ( "insert into DS_Comb (id_combcomplex, combnumber, combscore) values (%d, %d, 0)", $i, $e );
				$res = $common->executeQuery ( $sql );

				$this->procOut('Создаем соту № '.$e);

				if ( $res === false ) {
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex6";
					return false;
				}
			}
		}

		$sql = "select max(id_comb) as maxnumber from DS_Comb";

		$res = $common->getResultArray ( "maxnuber", $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex7";
			return false;
		}

		$maxnumber = $res[0]['maxnumber'];

		$c = ($maxnumber-$count*6)+1;
		$cn = 1;

		$this->procOut('Создаем ячейки');
		for ( $i = $c; $i<=$maxnumber; $i++ ) {

			for ( $e = 1; $e <= 217; $e++ ) {

				$sql = sprintf ( "insert into DS_Cell (id_comb, id_celltype, cellnumber) values (%d, 1, %d)", $i, $e );

				$res = $common->executeQuery ( $sql );
				if ( $res === false ){
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex8";
					return false;
				}
			}

			for ( $e = 1; $e <= 28; $e++ ) {

				$sql = sprintf ( "insert into DS_Cell (id_comb, id_celltype, cellnumber) values (%d, 2, %d)",
				$i, $e );

				$res = $common->executeQuery ( $sql );
				if ( $res === false ){
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex9";
					return false;
				}
			}

			$cn++;

			for ( $e = 1; $e <= 217; $e++ ) {

				$sql = sprintf ( "insert into DS_Cell (id_comb, id_celltype, cellnumber) values (%d, 3, %d)", $i, $e );

				$res = $common->executeQuery ( $sql );
				if ( $res === false ){
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex10";
					return false;
				}
			}

			$this->procOut('Заносим атрибуты');
			// заносим аттрируты соты
			$sql = sprintf ( "insert into ds_combhasresource (id_attribute, id_comb, value)
					select a.id_attribute, %d, 0
					from ds_attribute a,
					ds_attributetype att,
					ds_attributename atn
					where a.id_attributename = atn.id_attributename
					and a.id_attributetype = att.id_attributetype
					and att.typename in ('BONUS_ABSOLUTE', 'BONUS_RELATIVE', 'CURRENT_AMOUNT',
					'MAX_AMOUNT', 'AWAY_AMOUNT', 'TURN_UPKEEP', 'TURN_PRODUCTION')
					order by a.id_attribute", $i );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ){
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::createCombComplex11";
				return false;
			}

			if ( !$this->applyTemplate( $i ) ) {
				return false;
			}
		}

		$common->executeQuery ( 'commit' );
		$this->procOut('COMPLIT!');
		return true;
	}

	// размечаем соту по шаблону типов почвы
	function applyTemplate( $id_comb ) {

		global $common;


		for ( $celltype=1; $celltype<4; $celltype++ ) {

			$sql = sprintf ( "select ID_CellTemplate
					from DS_CellTemplate
					where ID_CellType = %d",
					$celltype );

			$res = $common->getResultArray ( "maxnuber", $sql, "", "", "" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::applyTemplate1";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ ){
				$templates[$i] = $res[$i]['id_celltemplate'];
			}

			$templrandid = $templates[rand(0, sizeof($templates)-1)];

			$sql = sprintf ( "update ds_cell
					set id_groundtype = t.id_groundt
					from
					(select id_groundtype as id_groundt,
					cellnumber as cellnum
					from DS_CellTemplateElement
					where ID_CellTemplate = %d) t
					where id_comb = %d
					and id_celltype = %d
					and cellnumber = t.cellnum",
					$templrandid,
					$id_comb,
					$celltype );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ){
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::applyTemplate2";

				return false;
			}

			if ( !$this->setBeginningBuildings ( $templrandid, $id_comb, $celltype ) ) {
				return false;
			}

		}

		return true;
	}

	function procOut($msg){
		if($this->debug){
			echo date('Y-m-d H:i:s')." ".$msg."<br>";
			flush();
			ob_flush();

		}
	}

	// создание новой игры в определенном мире
	function addNewInstance ( $uid, $id_world, $id_race, $combname ) {

		global $common;

		if ( empty ( $id_world ) || empty ( $id_race ) || empty ( $uid ) || empty ( $combname ) ) {
			$this->errormsg .= " SYSTEM ERROR - WorldUtils: Requested parameters missing, maybe forget to name comb"; // WorldUtils::addNewInstance1
			return false;
		}

		$this->procOut('Начало создания сот');
		$common->executeQuery ( "begin" );

		$sql = sprintf ( "select rc.id_comb
				from ds_reservedcomb rc,
				ds_user u
				where rc.username = u.username
				and u.id_user = %d",
				$uid );

		$res = $common->getResultArray ( "reserved", $sql, "", "", "" );
		if ( $res === false ) {
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance2";

			return false;
		}

		// если сота зарезервирована
		if ( sizeof ( $res ) > 0 ) {

			$id_comb_to = $res[0]['id_comb'];

			$sql = sprintf ( "delete from ds_reservedcomb
					where id_comb = %d",
					$id_comb_to );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ){
				$common->executeQuery ( 'rollback' );
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance3";

				return false;
			}

			$sql = sprintf ( "select rhbr.id_race
					from ds_racehasbasering rhbr,
					ds_ring r,
					ds_combcomplex cc,
					ds_comb co
					where rhbr.ringnumber = r.ringnumber
					and cc.id_ring = r.id_ring
					and cc.id_combcomplex = co.id_combcomplex
					and co.id_comb = %d",
					$id_comb_to );

			$res = $common->getResultArray ( "id_race_id_comb", $sql, "", "", "" );
			if ( !$res ) {
				$common->executeQuery ( 'rollback' );
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance5";

				return false;
			}

			$id_race = $res[0]['id_race'];

		} else { // не зарезервирована - любая свободная сота

			$sql = sprintf ( "select min (ID_Comb) as id_comb
					from DS_Comb co,
					DS_CombComplex cc,
					DS_Ring r,
					DS_World w,
					DS_Race ra,
					DS_RaceHasBaseRing rhbr
					where co.ID_UserInstance is null
					and co.ID_CombComplex = cc.ID_CombComplex
					and cc.ID_Ring = r.ID_Ring
					and r.ID_World = w.ID_World
					and rhbr.RingNumber = r.RingNumber
					and rhbr.ID_Race = ra.ID_Race
					and ra.ID_Race = %d
					and w.ID_World = %d
					and not exists
					(select id_comb
					from ds_reservedcomb
					where id_comb = co.id_comb)",
					$id_race, $id_world );

			$res = $common->getResultArray ( "min_id_comb", $sql, "", "", "" );
			if ( !$res ) {
				$common->executeQuery ( 'rollback' );
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance4";

				return false;
			}

			$id_comb_to = $res[0]['id_comb'];
		}

		// Проверка имени соты  - Длина, Допустимость символов, уникальность
		if ( strlen ( $combname ) < 3 ) {
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR WorldUtils::checkCombName1";

			return false;
		}

		if ( strlen ( $combname ) > 20 ) {
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR WorldUtils::checkCombName2";

			return false;
		}

		if ( preg_match ( '/[\n\t\'\"\`\!\@\#\$\%\^\*\(\)\;\:\{\}\[\]\<\>\?\ ]/', $combname ) ) {  //" - проблема с парсерами подсветки =)
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR WorldUtils::checkCombName3";

			return false;
		}

		if ( sizeof ( $this->errorname ) == 0 ) {

			$sql = sprintf ( "select id_comb
				from ds_combname
				where upper(combname) = upper('%s')",
				$combname );

			$res = $common->getResultArray ( "id_comb_combname_".$combname, $sql, "", "", "" );
			if ( $res === false ) {
				$common->executeQuery ( 'rollback' );
				$this->errormsg .= " SYSTEM ERROR WorldUtils::checkCombName4";

				return false;
			}
		}



		// создаем userinstance
		$sql = sprintf ( "select insertUserInstance ( %d, %d, %d ) as uiid",
				$uid, $id_race, $id_world );

		$res = $common->getResultArray ( "maxnuber", $sql, "", "", "" );
		if ( !$res ) {
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance6";

			return false;
		}

		if ( $res[0]['uiid'] == -1 ) {
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance7";

			return false;
		}

		$uiid = $res[0]['uiid'];

		// поселяем игрока на соту
		$sql = sprintf ( "update only DS_Comb set ID_UserInstance = %d,
				CombScore = 0
				where ID_Comb = %d
				and id_userinstance is null",
				$uiid, $id_comb_to);

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance8";

			return false;
		}

		$sql = sprintf ( "select id_comb
				from ds_comb
				where id_userinstance = %d",
				$uiid );

		$res = $common->getResultArray ( "id_comb", $sql, "", "", "" );
		if ( !$res ) {
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance9";

			return false;
		}

		$id_comb = $res[0]['id_comb'];



		// записываем название соты
		$sql = sprintf ( "insert into DS_CombName (ID_Comb, CombName) values ( %d, '%s')",
					$id_comb, $combname );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance10";

			return false;
		}

		// даем начальные науки
		$sql = "insert into ds_userhasscience ( id_userinstance, id_science, inresearch ) select $uiid, id_science, false from ds_science where sciencename ~ '_1$'";

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance11";

			return false;
		}

		// даем начальные ресурсы
		if ( !$this->setCombResource ( $id_comb, $id_race ) ) {
			$common->executeQuery ( 'rollback' );
			return false;
		}

		// создаем дефолтовую армию
/*
		$sql = sprintf ( "select ltl.text
			from ds_localizedtextlanguage ltl,
			ds_interface i,
			ds_user u
			where ltl.id_localizedtext = i.id_localizedname
			and i.interfacename = 'HARNIZON'
			and ltl.id_language = u.id_language
			and u.id_user = %d",
			$uid );

		$res = $common->getResultArray ( "harnizon_name_id_language_".constant('DEFAULT_LANG_ID'), $sql, "", "", "" );
		if ( !$res ) {
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance12";

			return false;
		}

		$sql = sprintf ( "insert into ds_unitcontainer ( id_basecomb, id_unitcontainertype, id_userinstance, moveable,
				inaction, unitcontainername, isDefault ) values ( %d, 1, %d, false, false, '%s', true )",
				$id_comb, $uiid, substr ( $combname." ".$res[0]['text'], 0, 30 ) );
*/
		$sql = sprintf ( "insert into ds_unitcontainer ( id_basecomb, id_unitcontainertype, id_userinstance, moveable,
				inaction, unitcontainername, isDefault ) values ( %d, 1, %d, false, false, '%s', true )",
				$id_comb, $uiid, substr ( $combname." {|HOME_ARMY|}", 0, 30 ) );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance13";

			return false;
		}

		// даем начальные messagescope
		$sql = sprintf ( "insert into ds_userallowedmessagescope
			( id_userinstance, id_messagescope )
			select %d,
			ms.id_messagescope
			from ds_messagescope ms
			where ms.messagescopename in
			( 'PRIVATE', 'COMB_COMPLEX', 'GAME_MASTER', 'GAME_HELPER' )
			order by ms.id_messagescope",
			$uiid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$common->executeQuery ( 'rollback' );
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance14";

			return false;
		}

		// insert flash key

		$sql = sprintf ( "insert into ds_flashkey
			( id_userinstance, key )
			values ( %d, 1 )",
			$uiid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::addNewInstance15";

			$common->executeQuery ( 'rollback' );
			return false;
		}

		$common->executeQuery ( 'commit' );

		$oldDbId = $common->dbId;
		$server = $common->dbWorldAccess[$common->dbId]['server'];
		$common->setDbId(0);
		$common->executeQuery ( 'BEGIN' );
		$sql = "INSERT INTO ds_userinstancecollection
  						(id_user, id_userinstance, id_world, dbname, admin)
  						VALUES
  						(
  						  ".$uid.",
  						  ".$uiid.",
  						  ".$id_world.",
  						  '".$server."',
  						  false
  						)";
		$rsInsert = $common->getResultArray("", $sql, "", "", "");
  		if ( $common->errormsg ) {
			die ('ERROR: Cant insert information about user '.$uid.' in '.$server);
	  	}

	  	$sql = "UPDATE ds_worldcollection
  				SET max_users = max_users - 1
	  			WHERE
	  				id_world = ".$id_world." AND
	  				dbname = '".$server."'";
		$rsInsert = $common->getResultArray("", $sql, "", "", "");
  		if ( $common->errormsg ) {

			die ('ERROR: Cant update information about  '.$server);
	  	}
	  	$common->executeQuery ( 'COMMIT' );
	  	$common->setDbId($oldDbId);
		return true;
	}

	// задает начальные ресурсы соты
	function setCombResource ( $id_comb, $id_race ) {

		global $common;

		$sql = sprintf ( "update ds_combhasresource
				set value = value::real + t.startamount
				from
				(select re.startamount,
				a.id_attribute as id_attr
				from ds_resource re,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and atn.id_attributename = re.id_attributename
				and att.typename = 'CURRENT_AMOUNT') t
				where id_attribute = t.id_attr
				and id_comb = %d",
				$id_comb );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::setCombResource2";

			return false;
		}

		$sql = sprintf ( "update ds_combhasresource
				set value = value::real + t.val
				from
				(select id_attribute as id_attr,
				value as val
				from ds_racehasattribute
				where id_race = %d) t
				where id_attribute = t.id_attr
				and id_comb = %d",
				$id_race, $id_comb );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ){
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::setCombResource3";

			return false;
		}

		$res = "";

		return true;
	}

	// ставит начальные здания на соту ( указываются в шаблоне соты )
	function setBeginningBuildings ($templateid, $id_comb, $celltype) {

		global $common;

		$sql = sprintf("
			select 
				ri.ringname
			from 
				ds_ring ri,
				ds_combcomplex cc,
				ds_comb co
			where 
				ri.id_ring = cc.id_ring
				and cc.id_combcomplex = co.id_combcomplex
				and co.id_comb = %d
			",
			$id_comb );
		$res = $common->getResultArray ( "ringname_id_comb_".$id_comb, $sql, "", "", "" );
		if(!$res)
		{
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings1";
			return false;
		}

		if($res[0]['ringname'] == 'RING_COLONIZE')
		{
			$colony = 1;
		}

		$sql = sprintf("
				select 
					cte.cellnumber,
					cte.id_building,
					b.buildingname
				from 
					DS_CellTemplateElement cte,
					ds_building b
				where 
					cte.id_building = b.id_building
					and cte.ID_CellTemplate = %d
					and cte.ID_CellType = %d
					and cte.ID_Building is not null",
					$templateid, $celltype );

		$res = $common->getResultArray("cellnumber_id_building_id_template_id_celltype_".$templateid."_".$celltype, $sql, "", "", "");
		if($res === false)
		{
			$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings2";
			return false;
		}

		for($i=0; $i<sizeof($res); $i++)
		{
			$sql = sprintf("
				select 
					ID_Cell
				from 
					DS_Cell
				where 
					ID_Comb = %d
					and ID_CellType = %d
					and CellNumber = %d",
					$id_comb,
					$celltype,
					$res[$i]['cellnumber'] 
				);
			$res2 = $common->getResultArray ( "id_cell_id_comb_id_celltype_cellnumber_".$id_comb."_".$celltype."_".$res[$i]['cellnumber'], $sql, "", "", "" );
			if(!$res2)
			{
				$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings3";
				return false;
			}

			$id_cell = $res2[0]['id_cell'];

			// если колония ЦВ открыты
			if(isset($colony) && $res[$i]['buildingname'] == 'CENTRAL_GATE_WRECK')
			{
				$sql = sprintf("
						select 
							id_building
						from 
							ds_building
						where 
							buildingname = 'CENTRAL_GATE'"
					);
				$res2 = $common->getResultArray ( "id_building_central_gate", $sql, "", "", "" );
				if(!$res2)
				{
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings4";
					return false;
				}

				$sql = sprintf("
						insert into DS_AttachedBuilding 
							(ID_Building, ID_Cell, active, underconstruction, starttime, endtime, inupgrade, id_comb)
   						values 
   							( %d, %d, true, false, null, null, false, %d )",
						$res2[0]['id_building'], $id_cell, $id_comb );
				$res2 = $common->executeQuery ( $sql );
				if($res2 === false)
				{
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings3";
					return false;
				}

				$sql  = sprintf("
					select 
						id_attachedbuilding
					from 
						ds_attachedbuilding
					where 
						id_cell = %d",
					$id_cell );
				$res2 = $common->getResultArray ( "id_attachedbuilding", $sql, "", "", "" );
				if(!$res)
				{
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings4";
					return false;
				}

				$mab = $res2[0]['id_attachedbuilding'];
			} else {
				$sql = sprintf("
					insert into DS_AttachedBuilding 
						(ID_Building, ID_Cell, active, underconstruction, starttime, endtime, inupgrade, id_comb)
   					values 
   						( %d, %d, true, false, null, null, false, %d )",
						$res[$i]['id_building'], $id_cell, $id_comb );

				$res2 = $common->executeQuery ( $sql );
				if ( $res2 === false ) {
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings3";

					return false;
				}

				$sql = sprintf("
					select 
						id_attachedbuilding
					from 
						ds_attachedbuilding
					where 
						id_cell = %d",
					$id_cell );
				$res2 = $common->getResultArray ( "id_attachedbuilding", $sql, "", "", "" );
				if(!$res)
				{
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings4";
					return false;
				}

				$mab = $res2[0]['id_attachedbuilding'];

				// увеличиваем upkeep/production соты
				$sql = sprintf("
					update 
						ds_combhasresource
					set 
						value = value::real + t.val
					from
						(
							select 
								a.id_attribute as id_attr,
								bha.value as val
							from 
								ds_buildinghasattribute bha,
								ds_attribute a,
								ds_attributename atn,
								ds_attributetype att
							where 
								bha.id_attribute = a.id_attribute
								and a.id_attributename = atn.id_attributename
								and a.id_attributetype = att.id_attributetype
								and att.typename in ( 'TURN_PRODUCTION', 'TURN_UPKEEP', 'MAX_AMOUNT' )
								and bha.id_building = %d
						) t
					where 
						id_comb = %d
						and id_attribute = t.id_attr",
					$res[$i]['id_building'],
					$id_comb );
				$res2 = $common->executeQuery ( $sql );
				if($res2 === false) 
				{
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings5";
					return false;
				}

				$sql = sprintf("
						select 
							1 as destr
						from 
							ds_buildinghasattribute bha,
							ds_attribute a,
							ds_attributename atn,
							ds_attributetype att
						where 
							bha.id_attribute = a.id_attribute
							and a.id_attributename = atn.id_attributename
							and a.id_attributetype = att.id_attributetype
							and att.typename = 'BEGINNING_FLAG'
							and atn.attributename = 'DESTROYABLE'
							and bha.id_building = %d",
							$res[$i]['id_building'] );

				$res2 = $common->getResultArray ( "beginning_flag_destroyable_id_building_".$res[$i]['id_building'], $sql, "", "", "" );
				if($res2 === false)
				{
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings6";
					return false;
				}

				if(!isset($res2[0]['destr']))
				{
					$res2[0]['destr'] = 0;
				}

				if($res2[0]['destr'] != 1)
				{
					$sql = sprintf("
						insert into DS_AttachedBuildingHasAttribute
							(ID_AttachedBuilding, ID_Attribute)
						values 
							(
								%d, 
								(
									select 
										ID_Attribute 
									from 
										DS_Attribute a, 
										DS_AttributeName atn, 
										DS_AttributeType att
									where 
										a.ID_AttributeName = atn.ID_AttributeName 
										and a.ID_AttributeType = att.ID_AttributeType
										and att.TypeName = 'CURRENT_FLAG' and atn.AttributeName = 'DESTROYABLE'
								)
							)", $mab);
					$res2 = $common->executeQuery ( $sql );
					if($res2 === false)
					{
						$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings7";
						return false;
					}
				}

				$sql = sprintf ( "select 1 as deact
						from ds_buildinghasattribute bha,
						ds_attribute a,
						ds_attributename atn,
						ds_attributetype att
						where bha.id_attribute = a.id_attribute
						and a.id_attributename = atn.id_attributename
						and a.id_attributetype = att.id_attributetype
						and att.typename = 'BEGINNING_FLAG'
						and atn.attributename = 'DEACTIVEABLE'
						and bha.id_building = %d",
						$res[$i]['id_building'] );
				$res2 = $common->getResultArray ( "beginning_flag_deactiveable_id_building_".$res[$i]['id_building'], $sql, "", "", "" );
				if($res2 === false)
				{
					$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings8";
					return false;
				}

				if ( !isset ( $res2[0]['deact'] ) ) $res2[0]['deact'] = 0;

				if ( $res2[0]['deact'] != 1 ) {

					$sql = sprintf("
						insert into DS_AttachedBuildingHasAttribute
							(ID_AttachedBuilding, ID_Attribute)
						values 
							(
								%d, 
								(
									select 
										ID_Attribute 
									from 
										DS_Attribute a, 
										DS_AttributeName atn, 
										DS_AttributeType att
									where 
										a.ID_AttributeName = atn.ID_AttributeName 
										and a.ID_AttributeType = att.ID_AttributeType
										and att.TypeName = 'CURRENT_FLAG' 
										and atn.AttributeName = 'DEACTIVEABLE'
								)
							)", $mab );
					$res2 = $common->executeQuery($sql);
					if($res2 === false)
					{
						$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings9";
						return false;
					}
				}

				// задаем начальный storage_factor
				if($res[$i]['buildingname'] == 'SHELTER')
				{
					$sql = sprintf("
						insert into ds_combhasresource 
							(id_comb, id_attribute, value )
						select 
							%d, 
							a.id_attribute, 
							value
						from 
							ds_buildinghasattribute bha,
							ds_attribute a,
							ds_attributetype att
						where 
							bha.id_attribute = a.id_attribute
							and a.id_attributetype = att.id_attributetype
							and att.typename IN ('STORAGE_FACTOR', 'STORAGE_FACTOR_ABSOLUT')
							and bha.id_building = %d",
							$id_comb,
							$res[$i]['id_building'] );

					$res2 = $common->executeQuery ( $sql );
					if ( $res2 === false ) {
						$this->errormsg .= " SYSTEM ERROR - WorldUtils::setBeginingBuildings10";

						return false;
					}
				}
			}
		}

		return true;
	}
}

?>