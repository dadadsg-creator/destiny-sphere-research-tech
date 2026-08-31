<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}


$sql = "SELECT rangefrom, rangeto, prefix FROM ds_artefactprobability";
$range = $common->getResultArray("", $sql);

$scopetypes = array("'BUILDING'",	"'ALLIANCE'",	"'WAR'", "'COMBCOMPLEX'", "'COMB'");

$_POST['multiplier'] = max(1, intval($_POST['multiplier']));
?>
<style type="text/css">
	#tb{
		border-collapse:collapse;
		border:1px solid #000;
	}
	#tb td{
		background: #DDD;
		color:#000 !important;
		border:1px solid #000;
		padding: 3px;
	}
	#tb th{
		background: #999;
		border:1px solid #000;
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
<div id="mcont">

	<?php
	//устанавливаем для выбранного мира
	if(isset($_POST['settoworld']) && $_POST['settoworld'] == 1) {

		if(isset($_POST['world'])) {
			list($dbid, $world_id) = explode("_", $_POST['world']);
			$db_id = intval($dbid);
			$world_id = intval($world_id);
		}

		$sql = "SELECT id_modifier FROM ds_gamemodifier WHERE id_world={$world_id} AND modifiername ='ARTEFACT_DROP_COMPENSATION'";
		$common->setDbId((int)$dbid);
		$res = $common->getResultArray('', $sql);
		if(!empty($res)) {
			$sql = "UPDATE
										ds_gamemodifier
									SET
										value = '".$_POST['multiplier_value']."'
									WHERE
										id_world = {$world_id}
										AND
										modifiername ='ARTEFACT_DROP_COMPENSATION'";
		}
		else {
			$sql = "INSERT INTO ds_gamemodifier(
								id_world, modifiername, \"value\")
								VALUES ({$world_id}, 'ARTEFACT_DROP_COMPENSATION', '".$_POST['multiplier_value']."');";
		}
		$common->executeQuery($sql);

		echo "<p style='color:lime'>Modifier was succeffully setted</p><p><a href='index.php'>&larr;Back</a></p>";
	}
	else {

		?>

	<table>
		<tr><td>
				<form action="" method="post">
					Multiplier: <input id="cmv" name='multiplier' value='<?php echo $_POST['multiplier']?>'> <input type="submit" value='calculate'>
				</form>
			</td><td>

					<?php
					$worlds = GetAllWorldsList();
					?>
				<form action="" method="post" onsubmit="document.getElementById('mv').value = document.getElementById('cmv').value;">
					<input type="hidden" name="settoworld" value="1">
					<input type="hidden" name="multiplier_value" value="" id="mv">
					set to world:
					<select name="world">
						<?php foreach($worlds as $w):?>
						<option value="<?php echo $w['dbid']."_".$w['id_world'] ?>"><?php echo $w['worldname'] ?></option>
						<?php endforeach?>
					</select>
					<input type="submit" value="set">
				</form>

	</td></tr></table>

		<?php
		$resource_mult = array('PRODIUM' => 1, 'KADERIUM' => 5, 'FOOD' => 10, 'STASIS_ENERGY' => 10, 'NANO_CRYSTAL' => 2.5);
		foreach ($resource_mult as $r => $v) {
			echo "<span class='{$r}'>{$r}</span>";
		}
		?>
	<br><br>

	<!-- br >
	<p>PARAMETERS FOR PRODIUM</p -->
	<table id="tb">
		<tr>
			<th rowspan="2">Artefact Size</th>
			<th colspan="20">Bonus</th>
		</tr>

			<?php

			$scopes = array();
			$sql = "SELECT baseprobability, scopename FROM ds_artefactscope WHERE scopename IN (".implode(",", $scopetypes).")";
			$ascopes = $common->getResultArray('', $sql);
			foreach($ascopes as $as) {
				$scopes[] = array('name' => $as['scopename'], 'probability' => $as['baseprobability']);
			}
			echo "<tr>";
			foreach ($scopes as $s) {
				echo "<td>".$s['name']."</td>";
			}
			echo "</tr>";

			foreach ($range as $row) {
				$prob = $row['rangeto'] - $row['rangefrom'];
				echo "<tr><td>".$row['prefix']."</td>";
				foreach ($scopes as $s) {
					if($prob > 0 && $s['probability'] > 0){
						$pr = 1 / ($_POST['multiplier'] * $prob * $s['probability']);
					}
					else $pr = 0;
					echo "<td>";
					foreach ($resource_mult as $r => $v) {
						//echo $r.":&nbsp;".($v * $pr)."<br>";
						echo "<div class='{$r}'>".round($v * $pr)."</div>";
					}
					echo "</td>";
				}
				echo "</tr>";
			}
			?>
	</table>
	<?}?>
</div>

<?php
function GetAllWorldsList()
{
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
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