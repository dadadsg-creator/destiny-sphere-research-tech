<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";


include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

echo "<style type='text/css'>
     .tbl  {
           border: 1px solid black;
           border-collapse: collapse;
         }
     .tbl td {padding-left:5px;}
</style>";

$act  = @$_REQUEST['act'];
$lang = @$_REQUEST['lang'];
$pg   = @$_REQUEST['pg'];
if (!$lang)
  $lang = 3;


global $filter;
if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}
$_SESSION["dbId"] = 0;
//var_export($_REQUEST);
switch ($act)
{
  case 'show':    
  {
  	showForm(); break;
  }
  case 'sbmedit': submitForm(); break;
	default: 
	{
	  showSelWorldForm(); break;
	}
}

function showSelWorldForm()
{
  global $filter;

  list($id_world, $id_base) = showSelectWorldMenu();

  $_SESSION["dbId"] = $id_base;

}

function showForm()
{
	//die(var_export($_POST, true));
	list($id_world, $id_base) = explode('_', $_POST['id_world']);
  list($id_world2, $id_base2) = showSelectWorldMenu();
  
  $oldId = $_SESSION["dbId"];
	$_SESSION["dbId"] = $id_base;
	$arts = DS_Artefact::GetArtefactsForShop($id_world);
	usort($arts,'art_sort');

	$cnt = count($arts);
	echo "<form action='' method=POST>
	<input type='hidden' name='act' value='sbmedit'>
	<input type='hidden' name='cnt' value='{$cnt}'>
  <input type='hidden' name='id_world' value='{$id_world}_{$id_base}'>
  <input type=\"submit\" value=\"ok\">
  <table class='tbl'>
	<tr><td class='tbl' style='color:yellow'>Артефакт</td><td class='tbl' style='color:yellow'>Цена на <b>6 м.</b> в серебре</td><td class='tbl' style='color:yellow'>Цена на <b>6 м.</b> в золоте</td>
  <td class='tbl' style='color:yellow'>Цена на 1 г. в серебре</td><td class='tbl' style='color:yellow'>Цена на 1 г. в золоте</td>	
	</tr>
	";
	$i = 0;

	foreach($arts as $a)
	{
	  $bonus = '';  
	  if (($a['scopename']=='BUILDING') || ($a['scopename']=='SPHERE')|| ($a['scopename']=='LOVE'))
	  {
	    $bonus .= '+'.$a['value']." ".$a['attrloc'];
	  }
	  else 
	  {
      $bonus .= '+'.($a['value']*100).'%'." ".$a['attrloc'];
    }		
/*
	  $a['price6sc'] = rand(1000,10000);
	  $a['price6gc'] = rand(1,10);
	  $a['price12sc'] = $a['price6sc']*2;
	  $a['price12gc'] = $a['price6gc']*2;
  */
		echo "<input type='hidden' name='artid{$i}' value='{$a['id_artefact']}'>";
	  echo "<tr>
	  <td class='tbl' style='color:white'>{$a['title']}<br><span style='color:red'>{$bonus}, {$a['scopetxt']}</span></td>
	  <td class='tbl'><input type=text name='price6sc_{$i}'  value='{$a['price6sc']}' size=5></td>
	  <td class='tbl'><input type=text name='price6gc_{$i}'  value='{$a['price6gc']}' size=5></td>
	  <td class='tbl'><input type=text name='price12sc_{$i}' value='{$a['price12sc']}' size=5></td>
	  <td class='tbl'><input type=text name='price12gc_{$i}' value='{$a['price12gc']}' size=5></td>
	  </tr>";
	  $i++;
	}
	echo '</table><input type="submit" value="ok"></form>';
}

function submitForm()
{
  $cnt = intval(@$_REQUEST['cnt']);
	list($world, $id_base) = explode('_', $_POST['id_world']);
	$_SESSION["dbId"] = $id_base;	
	//echo var_export($_POST, true);
	for($i = 0;$i < $cnt; $i++)
  {
  	$info['id_artefact'] = intval($_REQUEST['artid'.$i]);
  	$info['price6gc']    = intval($_REQUEST['price6gc_'.$i]);
  	$info['price6sc']    = intval($_REQUEST['price6sc_'.$i]);
    $info['price12gc']   = intval($_REQUEST['price12gc_'.$i]);
    $info['price12sc']   = intval($_REQUEST['price12sc_'.$i]); 

    if ($info['id_artefact'] &&  $info['price6sc'] &&  $info['price6gc']  && $info['price12gc'] && $info['price12sc'])
    {
       DS_Artefact::setPrice($info, $world);
    }
    //echo "id_artefact=$id_artefact, price6sc=$price6sc, price6gc=$price6gc, price12sc=$price12sc,price12gc=$price12gc<br>";
	}
	showForm();
}


	function showSelectWorldMenu ()
	{
    global $common;

		print "
		<table>
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<input type=hidden name='act' value='show'>
		<table cellSpacing='0' cellPadding='0'>
			<tr>
				<td bgcolor='444E6C'>

		 			<div class='FormText'>";

		echo "<strong>".local( 'DS_USER_WORLD' ).":&nbsp;&nbsp;</strong></td><td><select name='id_world'>";

		$sql = sprintf ( "select * from ds_world" );
		$res = $common->getMultySql ($sql);

		assert($res);

		$id_base  = $def_base  = $res[0]['dbId'];
		$id_world = $def_world = $res[0]['id_world'];

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			if ($_REQUEST['id_world'] == $res[$i]['id_world']."_".$res[$i]['dbId']) {
        $id_world = $res[$i]['id_world'];
        $id_base  = $res[$i]['dbId'];
				$selected = 'selected';
			} else {
				$selected = '';
			}
			echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."' $selected>".$res[$i]['worldname']."</option>";
		}

		echo "</select></div></td>
			<td>&nbsp;&nbsp;<input type='submit' class='Submit' value='". local( 'DS_ACTION_SUBMIT' )."' name='next'></td>
		</tr>
		</form>
		</table><br>
		";

		return array($id_world, $id_base);
	}
	
function art_sort($a, $b) 
{
    return strcmp($a['scopename'], $b['scopename']);
}	
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>