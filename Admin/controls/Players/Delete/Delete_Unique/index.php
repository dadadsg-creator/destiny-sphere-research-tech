<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Deleteuniquebuildings><font size=3><b>HELP</b></font></a></p>';

function print_rr ($array) {
	echo "<xmp>";
	print_r ( $array );
	echo "</xmp>";
}

//print_rr ( $GLOBALS );







if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}

class DeleteBuildings {
	
	var $dbId;
	var $wid;
	var $username;
	var $errormsg;
	var $request;
	var $buildings_array;
	var $coords;
	
	function DeleteBuildings( ) {
		
		$this->getRequest ( $_POST );
		//print_rr ( $_POST);
	}

	function getRequest ( $request ) 
	{
		$this->request = $request;
		$this->username = $this->request[username];

		//		$this->showFirstMenu();
		if (isset ( $request[showSecondMenu]) ) 
			{
				if (!$this->showSecondMenu() ) 
				{
					$this->showFirstMenu($this->errormsg);
				}
				
			} else { 
				if (is_array ( $this->request[delete_building] ) ) {
					if ( !$this->DelBuild() ){
						
						$this->showFirstMenu();
					} else {
						$this->showFirstMenu();
					}
					
					
					
			
					
				} else {
					if (!$this->showFirstMenu()) {
						echo $this->errormsg;
					}				
				}
			}
		return true;
	}
	
	

	function showFirstMenu ( $str = "" ) {

		global $common;
		
		
		if ( $this->errormsg  ) {
			
			echo $this->errormsg;
			//return false;
		}
		

		$sql = sprintf ( "select id_world, worldname from ds_world" );
		$res = $common->getMultySql ($sql);
		
		if ( !$res ) {
			$this->errormsg = "DeleteBuildings::showFirstMenu !!! ".$sql;
			return false;
		}
		
		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			$this->worlds_list[$res[$i]['id_world']."_".$res[$i]['dbId']] = $res[$i]['worldname'];
		}



		echo Forms::openForm('',$_SERVER['PHP_SELF'], 'post');
		echo Forms::hidden('showSecondMenu',1);
		print $str."
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=4>
			<tr>
				<td bgcolor='444E6C' class=th_2>
					".local( 'DS_USER_WORLD' )."</td>
				<td bgcolor='444E6C' class=th_2>
					".local( 'DS_USER_USERNAME' )."</td>
			</tr>
			<tr>
				<td bgcolor='444E6C'>";
		echo Forms::selectBox($this->worlds_list, $selected=$_REQUEST['id_world'], $name='id_world[]', $size=count($this->worlds_list), $multiple=true, $additional='');
		echo "	</td>
			
				<td bgcolor='444E6C' valign=top>";
		echo Forms::textField($value = $_REQUEST['username'], $name = 'username', $hidden = false );
		echo "</td>
			</tr>
			<tr>
				<td colspan=2 bgcolor='444E6C' class=maintxt>";
		echo Forms::hidden('unique',1);
//		echo "&nbsp;";
/*		echo Forms::checkBox(1,'unique',true, 'class=check').local('DS_SEARCH_ONLY_UNIQUE');*/
		/*Чек-бокс не включать, пока не будет добавлен в скрипт пересчет бонусов при удалении не */
		/*уникальных зданий*/
		echo "</td>
			</tr>
		</table></td></tr></table>";
		echo Forms::submitButton(local( 'DS_ACTION_SEARCH' ),$name = 'next', '','',' class = submit');
		echo Forms::closeForm();
		return true;
	}	//Это запчасть от первой фрмы
	
	
	
	
	function showSecondMenu ( $str = "" ) {
		global $common;

		$wid = explode ('_',$this->request[id_world][0]);
		$this->wid = $wid[0];
		$common->dbId = $wid[1];
		$common->setDbId( $wid[1] );
	
		if( empty($this->username) ){
			$this->errormsg = "Enter username!";
			return false;
		}


		//print_rr ($_POST);
	
	

		/*Выбираем все уникальные здания по инстансу в мире, и делим по комбам*/
	
	
	
	
	$sql = "
			SELECT 
				u.username,
				cn.combname,
				attb.id_attachedbuilding,
				ltl.text AS celltypename_l,
				( ri.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber ) as coords,
				(SELECT ltl3.text FROM ds_localizedtextlanguage as ltl3 where ltl3.id_language = ".constant('DEFAULT_LANG_ID')."AND bcbr.id_localizedname = ltl3.id_localizedtext) AS buildingname,
				--  (SELECT ltl2.text FROM ds_localizedtextlanguage as ltl2 where ltl2.id_language = ".constant('DEFAULT_LANG_ID')." AND bcbr.id_localizeddescription = ltl2.id_localizedtext) AS buildingdescription,
				ct.celltypename,
				b.id_building
			FROM
				ds_user u
				INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
				INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
				LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
				LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
				INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
				INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
				INNER JOIN ds_building b ON (attb.id_building = b.id_building)
				INNER JOIN public.ds_buildingcanbuildbyrace bcbr ON (b.id_building = bcbr.id_building) AND 
				(ui.id_race = bcbr.id_race)
				INNER JOIN ds_cell cell ON (attb.id_cell = cell.id_cell)
				INNER JOIN ds_celltype ct ON (cell.id_celltype = ct.id_celltype)
				INNER JOIN ds_localizedtextlanguage ltl ON (ct.id_localizedname = ltl.id_localizedtext)
			WHERE
				(u.username = '".$this->username."') AND 
				".($this->request[unique] ? "(b.isunique = 't') AND " : "")."
				(ltl.id_language = ".constant('DEFAULT_LANG_ID').")
				AND
				ui.id_world = ".$this->wid."
			ORDER BY
				cell.id_cell	
		";
		//echo $sql;
		$res = $common->getQueryArray ( $sql );
		if( !$res  ){
			$this->errormsg = ShowCMSMessage("Failed get Buildings List", 2);
			return false;
		}
	

		foreach ($res as $key => $value){
			$this->username =  $res[ $key ][username];
			$this->coords[$res[ $key ][combname]] = $res[ $key ][coords];

			$rrr[ $res[ $key ][combname] ][celltype][$res[ $key ][celltypename]] = $res[ $key ][celltypename_l];
//			$rrr[ $res[ $key ][combname] ][celltype][$res[ $key ][celltypename_l]]    [$res[ $key ][id_building]]     [id_attachedbuilding] = $res[ $key ][id_attachedbuilding];
//			$rrr[ $res[ $key ][combname] ][celltype][$res[ $key ][celltypename_l]]    [$res[ $key ][id_building]]     [buildingname] = $res[ $key ][buildingname];
$rrr[ $res[ $key ][combname] ][celltype][$res[ $key ][celltypename_l]]    [$res[ $key ][id_attachedbuilding]]     [buildingname] = $res[ $key ][buildingname];
$rrr[ $res[ $key ][combname] ][celltype][$res[ $key ][celltypename_l]]    [$res[ $key ][id_attachedbuilding]]     [id_building] = $res[ $key ][id_building];
		}
		unset ($res);	

		//print_rr ( $rrr );
		$this->buildings_array = $rrr;


		

		$sql = "SELECT
    				u.username,
    				w.worldname,
    				ltlr.text AS religionname,
    				ltls.text AS statusname
				FROM
    				ds_user AS u
		    
    				INNER JOIN ds_userinstance AS ui ON (ui.id_user = u.id_user)
    				INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
			    
    				LEFT JOIN ds_userhasreligion AS uhr ON (uhr.id_userinstance = ui.id_userinstance)
    				LEFT JOIN ds_religion AS r ON (r.id_religion = uhr.id_religion)
    				LEFT JOIN ds_localizedtextlanguage AS ltlr ON (ltlr.id_localizedtext = r.id_localizedname AND ltlr.id_language = 3)
    				LEFT JOIN ds_religionstatus AS rs ON (rs.id_status = uhr.id_status)
    				LEFT JOIN ds_localizedtextlanguage AS ltls ON (ltls.id_localizedtext = rs.id_localizedname AND ltls.id_language = 3)
				WHERE
    				w.id_world = ".$this->wid."
    				AND
    				u.username = '".$this->username."'";
		$res = $common->getQueryArray($sql);
		assert(empty($common->errormsg));
			
		echo "<b>User:</b> ".$res[0]['username']." <b>World:</b> ".$res[0]['worldname']." <b>Religion:</b> ".$res[0]['religionname']." <b>Status:</b> ".$res[0]['statusname'];
		
		
		$sql = "
			SELECT
				vm.enddate,
				vm.startdate
			FROM
				ds_vocationmode AS vm
		    	INNER JOIN ds_userinstance AS ui USING (id_userinstance)
		    	INNER JOIN ds_user AS u ON (ui.id_user = u.id_user)
			WHERE
				ui.id_world = ".$this->wid."
    			AND
    			u.username = '".$this->username."'
    			AND
    			vm.enddate > now()
			";
		$res = $common->getQueryArray($sql);
		assert(empty($common->errormsg));

		if(count($res) > 0)
		{
			echo "&nbsp;<b>Vocation</b>: start: ".$res[0]['startdate']."end: ".$res[0]['enddate'];
		} else {
			echo "&nbsp;<b>Vocation</b>: - ";
		}
		
		$sql = "
			SELECT
				ps.end_date,
				ps.start_date,
				psn.statusname
			FROM
				ds_powershild_current_status AS ps
				INNER JOIN ds_powershild_status_name AS psn USING (id_powershildstatusname)
		    	INNER JOIN ds_userinstance AS ui USING (id_userinstance)
		    	INNER JOIN ds_user AS u ON (ui.id_user = u.id_user)
			WHERE
				ui.id_world = ".$this->wid."
    			AND
    			u.username = '".$this->username."'
    			AND
    			ps.end_date > now()
			";
		$res = $common->getQueryArray($sql);
		assert(empty($common->errormsg));

		if(count($res) > 0)
		{
			echo "&nbsp;<b>Powershild</b>: status: ".$res[0]['statusname']."; start: ".$res[0]['startdate']."; end: ".$res[0]['enddate'];
		} else {
			echo "&nbsp;<b>Powershild</b>: - ";
		}
			
		echo "<br><a href=index.php><= BACK</a>";
		
		
		$this->ShowBuildingsList();
		
		return true;
	}
	
	function ShowBuildingsList (){
		global $common;
		
		foreach ($this->buildings_array as $key => $value){
		
			foreach ($value[celltype] as $k => $val){
				if (!is_array ($val)  ) {$cellname[$k] = $val;$cell = $k;} else {
					unset ( $$cell );
					//$$cell = $val;
					//print_rr ($$cell);
					foreach ($val as $kk => $v){

						if ($c[id_building] != 5 && $v[id_building] != 6 && $v[id_building] != 7 && $v[id_building] != 8 ){
							$$cell .= Forms::checkBox($kk,'delete_building[]',false, 'class=check')." <span class=maintxt>".$v[buildingname]."</span><br><img src='/Admin/img/hr_line.gif' width=95% height=3 border=0><br>\n";
						} else {
							$$cell .= "<span class=maintxt>".$v[buildingname]."</span><br><img src='/Admin/img/hr_line.gif' width=95% height=3 border=0><br>\n";
						}
						
						$hidden .= Forms::hidden('b_name['.$kk.']', $v[buildingname]);
					}
					
				}
				
				
			}
			
			print Forms::openForm('',$_SERVER['PHP_SELF'], 'post');
			print Forms::hidden('dbId',$common->dbId);
			print Forms::hidden('username',$this->username);
			print $hidden;
			print "<table width=100% border=0 cellspacing=0 cellpadding=0 bgcolor=#222233><tr><td><table border=0 cellspacing=1 cellpadding=3 width=100%>";
			print "<tr><td colspan=3 align=center class=th_3 bgcolor=#444E6C>".$this->username." - ".$key." - ".$this->coords[$key]."</td></tr>";
			
			print "<tr><td align=center bgcolor=#444E6C class=maintxt nowrap><b>".$cellname[HOME]."</b></td><td align=center bgcolor=#444E6C class=maintxt nowrap><b>".$cellname[NEUTRAL]."</b></td><td align=center bgcolor=#444E6C class=maintxt nowrap><b>".$cellname[CENTRAL]."</b></td></tr>";
			print "<tr><td valign=top bgcolor=#444E6C class=maintxt_med nowrap>". $HOME . "</td><td valign=top bgcolor=#444E6C class=maintxt_med nowrap>".$NEUTRAL."</td><td valign=top bgcolor=#444E6C class=maintxt_med nowrap>".$CENTRAL."</td></tr>";
		
		
/*			print "<tr><td colspan=2 align=center bgcolor=#444E6C>".$cellname[CENTRAL]."</td></tr>";
			print "<tr><td colspan=2 valign=top bgcolor=#444E6C>".$CENTRAL."</td></tr>"; */
		

			print "</table></td></tr></table>";
			//print Forms::submitButton('Submit','next');
			print Forms::submitButton(local( 'DS_ACTION_DELETE' ),$name = 'next', '','',' class = submit');
			print Forms::closeForm();
		}
	}
	
	function DelBuild () {
		global $common;
		
		$common->setDbId($this->request[dbId]);
		
		if ( is_array ( $this->request[delete_building]) ) 
		{
			
			
			$res = $common->executeQuery( "begin" );
			if (!$res) 
			{
				$this->errormsg="DeleteBuildings::DelBuild - ERROR begin process";
				return false;
			}
			
			foreach ($this->request[delete_building] as $value)
			{
				$sql = sprintf("SELECT
      									ab.id_comb,
      									bhts.id_tradescope
									FROM
    									ds_attachedbuilding ab,
    									ds_building b,
    									ds_buildinghastradescope bhts
									WHERE
     									ab.id_attachedbuilding = %d
     									AND ab.id_building = b.id_building
     									AND b.id_building = bhts.id_building",
									$value);
									
				$res = $common->getResultArray ( "", $sql, "", "", "" );
		
				if ($common->errormsg) 
				{
					$this->errormsg = "DeleteBuildings::DelBuild - ERROR Delete progress1";
					$common->executeQuery("rollback");
					return false;
				}	
				
				if(sizeof($res)>0)
				{
					foreach ($res as $k=>$v)
					{
						$sql = sprintf("DELETE FROM
           											ds_comballowedtradescope
												WHERE
     												id_comb = %d
     												AND id_tradescope = %d",
										$v['id_comb'],
										$v['id_tradescope']);
						$res = $common->executeQuery($sql);	
						if(!$res)
						{
							$this->errormsg = "DeleteBuildings::DelBuild - ERROR Delete progress2";
							$common->executeQuery("rollback");
							return false;
						} 
					}
				}
				
				$sql = "delete from ds_attachedbuilding where id_attachedbuilding = '".$value."'";
				
				$res = $common->executeQuery( $sql );				
				if(!$res)
				{
					$this->errormsg = "DeleteBuildings::DelBuild - ERROR Delete progress3";
					$common->executeQuery("rollback");
					return false;
				} 
				
				
				actionLog('Delete Unique Building Db: '.$common->dbWorldAccess[$common->dbId][server].' User:'.$this->username.' Building: '.$this->request[b_name][$value].'');
				$this->errormsg = ShowCMSMessage(local('DS_ACTION_DELETED_COMPLITE'), 1);
							
				
			}
						
			$res = $common->executeQuery( "commit" );
			if (!$res) 
			{
				$this->errormsg="DeleteBuildings::DelBuild - ERROR commit.";
				return false;
			}
			
			
		} else {
			$this->errormsg = "DeleteBuildings::DelBuild - ERROR - Failed Load Building List for Delete. Or List is Empty.";
			return false;	
		}
			
		
		
		
	} //END BuildDelete
	
	
	
}

$ChangeArts = new DeleteBuildings();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>