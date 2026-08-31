<?php
//Проверям не закрыта ли игра
require_once($_SERVER['DOCUMENT_ROOT'].'/ds/include/GameDown.inc.php');
if (GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}

require_once $_SERVER['DOCUMENT_ROOT']."/sys/define.php";

if( !class_exists( 'DS_Common' ) ){
	require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/DS_Common.php";
}


$common = new DS_Common;
$common->connect();

$sql = "SELECT rangefrom, rangeto, prefix FROM ds_artefactprobability";
$range = $common->getResultArray("", $sql);

$scopetypes = array("'BUILDING'",	"'ALLIANCE'",	"'WAR'", "'COMBCOMPLEX'", "'COMB'");

$worlds = GetAllWorldsList();

if(isset($_POST['cworld'])) {
	list($dbid, $world_id) = explode("_", $_POST['cworld']);
	$db_id = intval($dbid);
	$world_id = intval($world_id);
}
else {
	$db_id = $worlds[0]['dbid'];
	$world_id = $worlds[0]['id_world'];
}

$sql = "SELECT id_modifier, value FROM ds_gamemodifier WHERE id_world={$world_id} AND modifiername ='ARTEFACT_DROP_COMPENSATION'";
$common->setDbId((int)$dbid);
$res = $common->getResultArray('', $sql);
$modifier_value = $res[0]['value'] ? $res[0]['value'] : 0;

$resource_mult = array('KADERIUM' => 5, 'NANO_CRYSTAL' => 2.5, 'STASIS_ENERGY' => 10, 'FOOD' => 10, 'PRODIUM' => 1);
$images = array('PRODIUM' => 'prodium.gif', 'KADERIUM' => 'kaderium.gif', 'FOOD' => 'eda.gif', 'STASIS_ENERGY' => 'energia.gif', 'NANO_CRYSTAL' => 'nano.gif');

?>

<div>
	<form action="/?p=artefact_destruction" method="post">
		<?php echo $lang['FOR_WORLD']?>: <select name="cworld">
		<?php foreach($worlds as $w):?>
			<option value="<?php echo $w['dbid']."_".$w['id_world'] ?>" <?php if($_POST['cworld'] == $w['dbid']."_".$w['id_world']):?> selected="selected" <?php endif?>><?php echo $w['worldname'] ?></option>
			<?php endforeach?>
		</select>
		<input type="submit" value="<?php echo $lang['SHOW']?>">
	</form>
</div>

<style type="text/css">
	#tb{
		border-collapse:collapse;
		border:1px solid #000;
		margin: 30px;
		border: 2px solid #000;
	}
	#tb td{
		background: #DDD;
		color:#000 !important;
		border:1px solid #000;
		padding: 4px;
		font-size: 12px;
		white-space:nowrap;
	}
	#tb td .value{
		position:relative;
		top:-4px;
	}
	#tb td.img{
		text-align:center;
	}
	#tb th{
		background: #999;
		border:1px solid #000;
	}
	#tb tr.row td{
		border-top-width: 2px;
	}

	#mcont, #mcont td{color: #FFF;}

	.PRODIUM{
		background: purple;
		color: white;
	}
	.KADERIUM{
		background:olive;
		color: white;
	}
	.FOOD{
		background: green;
		color: white;
	}
	.STASIS_ENERGY{
		background:gray;
		color:white;
	}
	.NANO_CRYSTAL {
		background:blue;
		color: white;
	}
	span.PRODIUM, span.KADERIUM, span.FOOD, span.STASIS_ENERGY, span.NANO_CRYSTAL {
		margin: 0 5px;
	}
	div.PRODIUM, div.KADERIUM, div.FOOD, div.STASIS_ENERGY, div.NANO_CRYSTAL {
		padding: 3px;
	}
</style>

<?php if($modifier_value): ?>

<table id="tb">
	<?php

	$scopes = array();
	$scope_order = array('BUILDING' => 0, 'COMB' => 1, 'COMBCOMPLEX' => 2, 'ALLIANCE' => 3, 'WAR' => 4);
	$sql = "SELECT baseprobability, scopename FROM ds_artefactscope WHERE scopename IN (".implode(",", $scopetypes).")";
	$ascopes = $common->getResultArray('', $sql);
	foreach($ascopes as $as) {
		$scopes[$scope_order[$as['scopename']]] = array('name' => $as['scopename'], 'probability' => $as['baseprobability']);
	}

	ksort($scopes);

	echo "<tr><td>".$lang['SIZE']."</td><td>".$lang['RESOURCE']."</td>";
	foreach ($scopes as $s) {
		echo "<td>".$lang[$s['name']]."</td>";
	}
	echo "</tr>";

	foreach ($range as $row) {
		if($row['prefix'] == 'COMMON') continue;
		$prob = $row['rangeto'] - $row['rangefrom'];

		$tr = "";
		$bonuses = 0;
		$label = 0;
		foreach ($resource_mult as $r => $v) {
			$picto = 0;
			$tr .= "<tr ".($label == 0 ? "class='row'" : "").">";
			if($label++ ==0) $tr .= "<td rowspan='5'>".$lang[$row['prefix']]."</td>";
			foreach ($scopes as $s) {

				if($picto == 0) {
					$tr .= "<td class='img'><img src='/img/support/resource/".$images[$r]."'></td>";
					$picto = 1;
				}
				if($prob > 0 && $s['probability'] > 0){
					$pr = 1 / ($modifier_value * $prob * $s['probability']);
				}
				else $pr = 0;
				
				if($r != 'PRODIUM' && $s['name'] == 'WAR') $pr = 0;

				$bonuses += $pr;

				$val = round($v * $pr);
				$val = $val != 0 ? number_format(round($v * $pr), 0, '', ' ') : "";
				$tr .= "<td style='text-align:right'>".$val."</td>";
			}
			$tr .= "</tr>";
		}

		if($bonuses) echo $tr;
	}
	?>
</table>

<?else:?>
<?php echo "<p><b>".$lang['NO_COMPENSATION']."</b></p><br><br><br>" ?>
<?endif?>


<?php
function GetAllWorldsList()
{
	$common = DS_Common::CreateForCurentDB(0);
	$out = array();
	foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
	{
		$common->setDbId((int)$dbid);
		$sql = "SELECT id_world, worldname FROM ds_world";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		foreach($res as $k=>$v)
		{
			$v['dbid'] = $dbid;
			$out[] = $v;
		}
	}
	return $out;
}

?>