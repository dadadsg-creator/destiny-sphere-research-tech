<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if ( !isset ( $GLOBALS["common"] ) ) 
{
	$common = &ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}

$out[] = array();

foreach ($common->dbWorldAccess as $k=>$v) 
{
	$common->setDbId($k);
	$sql = "SELECT * 
			FROM ds_world";
	$rs = $common->getQueryArray($sql);
	if(!$rs){
		continue;
	}
	
	foreach ($rs as $k2=>$v2)
	{
		$sql = "
			SELECT
       			count(*) as colvo
 			FROM
     			ds_eventparticipant ep,
     			ds_eventqueue eq,
     			ds_event ev,
     			ds_userinstance ui
 			WHERE
      			ev.eventname in ('ATTACK','BATTLE_ROUND',
      				'ARMY_MOVEMENT_FROM','ARMY_MOVEMENT_TO') AND
      			eq.istrash = false AND
      			ep.id_userinstance = ui.id_userinstance AND
      			ep.id_eventqueue = eq.id_eventqueue AND
      			ev.id_event = eq.id_event AND
      			ui.id_world = ".$v2['id_world']."
		";
		$rst = $common->getQueryArray($sql);
		$out[] = array
		(
			'WORLD_NAME' => $v2['worldname'],
			'EVENTS' => $rst[0]['colvo'],			
		);		
	}
	
}
?>
<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
<tr>
<td class=th_2 bgcolor='444E6C'><? echo local('DS_USER_WORLD'); ?></td>
<td class=th_2 bgcolor='444E6C'><? echo local('DS_USER_FIGHTS'); ?></td>
</tr>
<?

foreach($out as $k=>$v)
{
	echo "<tr><td bgcolor='444E6C' class=maintxt>".$v['WORLD_NAME']."</td><td bgcolor='444E6C' align=center class=maintxt>".$v['EVENTS']."</td></tr>";
}

?>
</table></td></tr></table>
<?

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>