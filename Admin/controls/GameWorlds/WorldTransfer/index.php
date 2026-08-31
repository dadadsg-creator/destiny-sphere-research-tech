<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

// START
$_SESSION["dbId"] = 0;
$common = DS_Common::CreateForCurentDB(0);

// function
function transferData($id_world_from, $id_world_to)
{
	$common = DS_Common::CreateForCurentDB(0);
	$sql = 'select * from ds_worldtransfer_worlds_relation where id_world_from = ' . $id_world_from . ' and id_world_to = ' . $id_world_to;
	$result = $common->getResultArray('', $sql, '', '', '');
	
	assert(empty($common->errormsg));
	assert(is_array($result));
	
	return $result;
}

function updateRecord($fromId, $toId, $forPremium, $allow, $free, $priority = 0, $frequency = 0)
{
	$common = DS_Common::CreateForCurentDB(0);

	//Получаем имя первого мира по его id
	$sql = "SELECT
						w.worldname
						FROM ds_worldcollection as w
						WHERE w.world_identifier = (SELECT wt.world_identifier 
													FROM ds_worldtransfer_worlds_list as wt
                                                     WHERE wt.id_worldtransfer_world = $fromId)";
		
	$res = $common->getResultArray("", $sql, "", "", "");

	assert(empty($common->errormsg));
	$masterWorldName = $res[0]['worldname'];
	
	//Получаем имя второго мира по его id
	$sql = "SELECT
						w.worldname
						FROM ds_worldcollection as w
						WHERE w.world_identifier = (SELECT wt.world_identifier 
													FROM ds_worldtransfer_worlds_list as wt
                                                     WHERE wt.id_worldtransfer_world = $toId)";
		
	$res = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));
	$slaveWorldName = $res[0]['worldname'];

	if ($allow) {
		$sql = '
			select 
				count(*) as cnt 
			from 
				ds_worldtransfer_worlds_relation 
			where 
				id_world_from = '.$fromId.' 
				and 
				id_world_to = '.$toId;
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		assert(is_array($res));
		
		
				
		if (0 === intval($res[0]['cnt'])) {
			$sql = '
				insert into
					ds_worldtransfer_worlds_relation
					(id_world_from, id_world_to, only_for_premium, free, priority, frequency)
				values
					('.$fromId.', '.$toId.', '.$forPremium.', '.$free.', '.$priority.', '.$frequency.')';
			$common->executeQuery($sql);
			assert(empty($common->errormsg));

		} else {
			$sql = '
				update 
					ds_worldtransfer_worlds_relation 
				set 
					only_for_premium = '.$forPremium.',
					free = '.$free.',
					priority = '.$priority.',
					frequency = '.$frequency.'
				where 
					id_world_from = '.$fromId.' 
					and 
					id_world_to = '.$toId;
			$common->executeQuery($sql);
			assert(empty($common->errormsg));
		}
	} else {
		$sql = '
			delete from 
				ds_worldtransfer_worlds_relation 
			where 
				id_world_from = '.$fromId.' 
				and 
				id_world_to = '.$toId;
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
			
	}
	
	//Логируем действие
	$log = "Update transfer from world '$masterWorldName' to world '$slaveWorldName'";

	if ($allow == '1')
	{
		$allowlog = 'Yes';
	} else {
		$allowlog = 'No';
	}

	if ($forPremium == 'true')
	{
		$forPremiumlog = 'Yes';
	} else {
		$forPremiumlog =  'No';
	}

	if ($free == 'true')
	{
		$freelog = 'Yes';
	} else {
		$freelog = 'No';
	}

	$detail = "Set Разрешено='$allowlog', Только для премиума='$forPremiumlog', Бесплатно='$freelog', Порядок='$priority', Частота переезда='$frequency'";
	actionLog($log, $detail);
}

if (key_exists('action', $_GET)) {
	$action = $_GET['action'];
} else {
	$action = 'view';
}

if (key_exists('from', $_GET) && is_numeric($_GET['from'])) {
	$fromId = intval($_GET['from']);
} else {
	$fromId = false;
}

if (key_exists('to', $_GET) && is_numeric($_GET['to'])) {
	$toId = intval($_GET['to']);
} else {
	$toId = false;
}

if (key_exists('allow', $_GET) && is_numeric($_GET['allow'])) {
	$allow = true;
} else {
	$allow = false;
}

if (key_exists('premium', $_GET) && is_numeric($_GET['premium'])) {
	if (1 === intval($_GET['premium'])) {
		$forPremium = 'true';
	} else {
		$forPremium = 'false';
	}
} else {
	$forPremium = 'false';
}

if (key_exists('free', $_GET) && is_numeric($_GET['free'])) {
	if (1 === intval($_GET['free'])) {
		$free = 'true';
	} else {
		$free = 'false';
	}
} else {
	$free = 'false';
}

if (key_exists('priority', $_GET) && is_numeric($_GET['priority'])) {
	$priority = intval($_GET['priority']);
} else {
	$priority = 0;
}

if (key_exists('frequency', $_GET) && is_numeric($_GET['frequency'])) {
	$frequency = intval($_GET['frequency']);
} else {
	$frequency = 0;
}

switch ($action) {
	case 'update':
		assert(false !== $fromId && false !== $toId && isset($forPremium) && isset($free) && isset($priority) && is_int($priority) && is_int($frequency));
		updateRecord($fromId, $toId, $forPremium, $allow, $free, $priority, $frequency);
		break;
	default:
}

// get worlds
$sql = '
	select
		wl.id_worldtransfer_world as wt_id,
		wl.world_identifier as short,
		wc.worldname as name,
		wc.id_world as id
	from
		ds_worldtransfer_worlds_list wl, ds_worldcollection wc
	where
		wl.world_identifier = wc.world_identifier';

$result = $common->getResultArray('', $sql, '', '', '');
assert(empty($common->error));

foreach ($result as $item) {
	echo '<h1>' . $item['name'] . '</h1>' . "\n";
	$w_id = intval($item['wt_id']);
	
	echo "<table>\n";
	echo "<tr><th class=\"SMenuLink_Active\">Разр.</th><th class=\"SMenuLink_Active\">Прем.</th><th class=\"SMenuLink_Active\">Бесп.</th><th class=\"SMenuLink_Active\">Порядок</th><th class=\"SMenuLink_Active\">Частота</th><th class=\"SMenuLink_Active\">Мир</th><th></th></tr>\n";
	
	foreach ($result as $w) {
		$w_to = intval($w['wt_id']);
		
		if ($w_to === $w_id) {
			continue;
		}

		$transferData = transferData($w_id, $w_to);
		if(count($transferData) > 0)
		{
			$isPossible = ' checked="checked"';
			
			if($transferData[0]['only_for_premium'] == 't')
			{
				$forPremiumOnly = ' checked="checked"';
			} else {
				$forPremiumOnly = '';
			}
			
			if($transferData[0]['free'] == 't')
			{
				$free = ' checked="checked"';
			} else {
				$free = '';
			}
			
			$priority = $transferData[0]['priority'];
			$frequency = $transferData[0]['frequency'];
			
		} else {
			$isPossible = '';
			$forPremiumOnly = '';
			$free = '';
			$priority = 0;
			$frequency = 0;
		}
		
		echo '<form action="?" method="GET">' . "\n";
		echo '<input type="hidden" name="from" value="' . $w_id . '">' . "\n";
		echo '<input type="hidden" name="to" value="' . $w_to . '">' . "\n";
		echo '<input type="hidden" name="action" value="update" />' . "\n";
		echo "<tr>\n";
		echo '<td><input type="checkbox" name="allow" value="1" ' . $isPossible . ' /></td>' . "\n";
		echo '<td><input type="checkbox" name="premium" value="1" ' . $forPremiumOnly . ' /></td>' . "\n";
		echo '<td><input type="checkbox" name="free" value="1" ' . $free . ' /></td>' . "\n";
		echo '<td><input type="text" size=3 name="priority" value="'.$priority.'"/></td>' . "\n";
		echo '<td><input type="text" size=3 name="frequency" value="'.$frequency.'"/></td>' . "\n";
		echo '<td class="SMenuLink_Active">' . $w['name'] . '</td>' . "\n";
		echo '<td><input type="submit" value="Update"></td>';
		echo "</tr>\n";
		echo "</form>\n";
	}

	echo "</table><br /><br />\n";
}
// END

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>