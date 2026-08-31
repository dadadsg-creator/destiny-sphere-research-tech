<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
set_time_limit(0);

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=CreateComplex><font size=3><b>HELP</b></font></a></p>';

if(!isset($GLOBALS["common"]))
{
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}

class CreateCombComplex
{

	var $errormsg;

	function CreateCombComplex()
	{
		if(!$this->getRequest($_POST))
		{
			echo $this->errormsg;
			return false;
		}
	}

	function showFirstMenu($str="")
	{
		global $common;
		print $str;

		print "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
		print "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>";

		$sql = "select ID_World, WorldName from DS_World";
		$res = $common->getMultySql ( $sql );
 		if ( !$res ) {
     		$this->errormsg = " SYSTEM ERROR - select world";
     		return false;
 		}

		print "<tr><td class=th_2>".local( 'DS_ACTION_SELECT_WORLD' )."</td></tr>
		<tr><td bgcolor=\"444E6C\" class=maintxt><select name='world'>";

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            		$opt = "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."'";
            		$opt .= ">".$res[$i]['worldname']."</option>";
            		print $opt;
        		}

		print "</select></td></tr>\n";

		$sql = "select ringname, ringnumber from ds_ringtemplate order by ringnumber DESC";

		$res = $common->getQueryArray($sql);
        		if ( !$res ) {
            			$this->errormsg = " SYSTEM ERROR - select ring";
            			return false;
        		}

		print "<tr><td class=th_2>".local( 'DS_ACTION_SELECT_RING' )."</td></tr>
		<tr><td bgcolor=\"444E6C\" class=maintxt><select name='ring'>";

		for($i=0;$i<sizeof($res);$i++)
		{
            		$opt = "<option value='".$res[$i]['ringnumber']."'";
            		$opt .= ">".local('ADMIN_'.$res[$i]['ringname'])."</option>";
            		print $opt;
		}

		print "</select></td></tr>\n";

		$sql = sprintf(
		"
		SELECT
			cct.id_complextype,
			cct.complextype,
			ltl.text
		FROM
			ds_combcomplextype cct
			LEFT JOIN ds_localizedtext lt ON ( cct.id_localizedtype = lt.id_localizedtext )
			LEFT JOIN ds_localizedtextlanguage ltl ON ( lt.id_localizedtext = ltl.id_localizedtext )
		WHERE
			ltl.id_language = '%d'
		",
		constant( 'DEFAULT_LANG_ID' )
		);

		$res = $common->getResultArray ( "all_types", $sql, "", "", "" );
        		if ( !$res ) {
            		$this->errormsg = " SYSTEM ERROR - select type ".$sql." ".pg_last_error();
            		return false;
        		}

		print "<tr><td class=th_2>".local( 'DS_ACTION_SELECT_COMPLEX_TYPE' )."</td></tr>
		<tr><td bgcolor=\"444E6C\" class=maintxt><select name='complextype'>";

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			print "<option value='".$res[$i]['id_complextype']."' ".( $res[$i]['complextype'] == 'COLONIZEABLE' ? 'selected' : '' )." >".$res[$i]['text']."</option>";
        }

		print "</select></td></tr>\n";
		print "<tr class=><td class=th_2>".local( 'DS_ACTION_COUNT' )."</td></tr>
		<tr><td bgcolor=\"444E6C\" class=maintxt><input type='text' name='count' size='4' maxlength='4'></td></tr>";
		print "<tr><td bgcolor=\"444E6C\" class=maintxt><input type='submit' class=Submit value='".local( 'DS_ACTION_CREATE' )."' name='create'></td></tr>";
		print "</table></td></tr></table>";
	}

	function getRequest($request)
	{

		if(!isset($request["create"]))
		{

			if(!isset($_SERVER["HTTP_REFERER"]))
			{
				$_SERVER["HTTP_REFERER"] = "";
			}

			if(ereg($_SERVER["PHP_SELF"],$_SERVER["HTTP_REFERER"]))
			{
				$this->showFirstMenu ( local( 'DS_ACTION_SUCCEED' ) );
			} else {
				$this->showFirstMenu ( "<div class=\"FormText\">".local( 'DS_COMBCOMPLEX_START_NOTICE' )."</div>" );
			}

		} else {

			if(!$this->create($request["world"], $request["ring"], $request["complextype"], $request["count"]))
			{
				return false;
			} else {
				//headerLocation ("index.php");
				//exit;
				return true;
			}
		}
	}

	function create ( $world, $ring, $complextype, $count )
	{
		global $common;

		$t = explode('_', $world);
		$world = $t[0];
		$dbId = $t[1];
		$common->setDbId( $dbId );


		if ( $world == "" || $ring == "" || $complextype == "" || $count == "" )
		{
			$this->errormsg = ShowCMSMessage("missing argument", 2);
			return false;
		}

		$wu = & ClassFactory::getInstance ( "WorldUtils" );
		$wu->setDebug(true);

		if ( !$wu )
		{
		            $this->errormsg = ShowCMSMessage(" SYSTEM ERROR - createcombcoplex1", 2);
            		return false;
		}

		if(!$wu->createCombComplex($world, $ring, $complextype, $count))
		{
			$this->errormsg = $wu->errormsg;
			return false;
		}

		actionLog( 'Creating CombComplex on DB:'.$common->dbId.' in World:'.$world.' Ring:'.$ring);
		return true;
	}
}

$cc = new CreateCombComplex;

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>