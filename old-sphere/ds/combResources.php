<?php

function vardump($str)
{
	print "<xmp>\n";
	print_r($str);
	print "\n</xmp>";
}


require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;
$_SESSION["worldtype"] = "";

error_reporting(E_ALL);
//require_once("include/DecodeInput.inc.php");
require_once('classes/Validator.class.php');





//$_SESSION['dbId'] = 0;
//$common = DS_Common::CreateForCurentDB();




class ViewResources
{
	var $dbId;
	var $id_world;
	var $username;
	var $combParams;
	var $errormsg;
	var $common;

	function ViewResources()
	{
		$_SESSION['dbId'] = 0;
		$this->common = DS_Common::CreateForCurentDB();
		$this->getRequest($_POST);
	}

	function getRequest($request)
	{
		if ( isset ($request["showSecondMenu"]) )
		{
			list( $this->id_world, $this->dbId ) = explode('_', $request['id_world']);
			$this->username = $request['username'];
			if( !$this->showSecondMenu() )
			{
				$this->showFirstMenu ( "<div class='FormText'>".$this->errormsg."</div>" );
			}

		}else{
			$this->showFirstMenu ( "<div class=\"FormText\"></div>" );
		}

		return true;
	}






	function showFirstMenu ( $str = "" )
	{
		print $str."
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<input type='hidden' name='showSecondMenu' value='1'>
		
		<table border=0 cellspacing=0 cellpadding=0><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
			<tr>
				<td>
					<b>".'Мир'."</b> : 
				</td>
			</tr>
			<tr>
				<td><select name='id_world' style='width:250px;'>";

		$sql = sprintf ( "select * from ds_world" );
		$res = $this->common->getMultySql ($sql);

		if ( !$res )
		{
			$this->errormsg = "Resources::showFirstMenu !!! ".$sql;
			return false;
		}

		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{
			echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."'>".$res[$i]['worldname']."</option>";
		}

		echo "</select>
					<br>
					<b>".'Логин'."</b> : <input type='text' name='username' size=\"30\" value=''><br>
					<b>".'Пароль'."</b> : <input type='password' name='password' size=\"30\" value=''>
				</td>
			</tr>
			<tr>
				<td colspan=2><input type='submit' class=submit name=\"next\" value=\"".'Смотреть'."\"></td>
			</tr>
		</table></td></tr></table>
		</form>
		";

		return true;
	}


	function showSecondMenu( )
	{
		global $common;

		$this->common->setDbId( $this->dbId );

		if ( $this->id_world == "" )
		{
			$this->errormsg = "Select world";
			return false;
		}

		if ( $this->username == "" )
		{
			$this->errormsg = "Enter Username";
			return false;
		}

		$sql = sprintf (
		"
			SELECT
			      u.username,
			      ui.id_userinstance,
				  ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
				  co.id_comb,
			      cn.combname,
			      chre.id_combhasresource,
			      attrname.attributename,
			      chre.value
			FROM
			     ds_user u
			     LEFT JOIN ds_userinstance ui ON ( u.id_user = ui.id_user )
			     LEFT JOIN ds_comb co ON ( ui.id_userinstance = co.id_userinstance )
			     LEFT JOIN ds_combname cn ON ( co.id_comb = cn.id_comb )
			     LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
			     LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
			     LEFT JOIN ds_combhasresource chre ON ( co.id_comb = chre.id_comb )
			     LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
			     LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
			     LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
			WHERE
			      attrtype.typename = 'CURRENT_AMOUNT'
			      AND attrname.attributename IN ( 'WORKER', 'SCIENTIST', 'ARCHEOLOGIST', 'FOOD', 'KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'STASIS_ENERGY' )
			      AND u.username = '%s'
			      AND ui.id_world = '%d'
			ORDER BY
				ri.ringnumber,
				cc.complexnumber,
				co.combnumber,
				attrname.id_attributename
				
			",
		pg_escape_string( $this->username ),
		$this->id_world
		);

		$res = $this->common->getResultArray ( "resources", $sql, "", "", "" );
		if ( !$res )
		{
			//			$this->errormsg = " SYSTEM ERROR - Resources::showSecondMenu !!! ".$sql;
			$this->errormsg = "No such User";
			return false;
		}
		echo "
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
		";

		$temp_id_comb = '';

		foreach ( $res as $row )
		{
			if( $temp_id_comb != $row['id_comb'] )
			{
				print "
			<tr>
				<th colspan=3 bgcolor='444E6C' class='th_2'>CombName ( Coords )</th>
			</tr>
			<tr>
				<td colspan=3 bgcolor='444E6C' class='maintxt'><strong>".$row['combname']." ( ".$row['coords']." )</strong></td>
			</tr>
			<tr>
				<th bgcolor='444E6C' class='th_2'>ResourceName</th>
				<th bgcolor='444E6C' class='th_2'>ResourceAmount</th>
			</tr>
			";
				$temp_id_comb = $row['id_comb'];
			}

			print "
			<tr>
				<td bgcolor='444E6C' class='maintxt'>".$row['attributename']."</td>
				<td bgcolor='444E6C' class='maintxt'>".sprintf( '%.3f', $row['value'])."</td>
			</tr>";
		}

		echo "
		</table></td></tr></table>
		<a href='".basename($_SERVER['PHP_SELF'])."'>Back</a>
		";
		return true;
	}
}

$view = new ViewResources();
?>