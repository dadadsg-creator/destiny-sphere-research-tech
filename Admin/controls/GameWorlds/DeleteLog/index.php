<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

ini_set('error_reporting', E_ALL);

class LogDeleteGameObjects
{
	private $startDate;
	
	private $endDate;
	
	private $userName;
	
	private $objectType;
	
	private $dbId;
	
	private $worldId;
	
	private $action = 'index';
	
	public function __construct($data)
	{
		$this->_getParams($data);
		echo "<h1>Delete Log</h1>";
		
		switch ($this->action) {
			case 'index':
				$this->_outIndex();	
				break;
				
			case 'show_log':
				$this->_outLogs();
				break;
		
			default:
				$this->_outIndex();	
				break;
		}
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('world', $data))
		{
			$arr = explode('_', $data['world']);
			$this->dbId = (int)$arr[0];
			$this->worldId = (int)$arr[1];
			
			assert($this->dbId >= 0);
			assert($this->worldId > 0);
		}
		
		if(key_exists('username', $data))
		{
			$this->userName = $data['username'];
		}
		
		if(key_exists('username', $data))
		{
			$this->userName = $data['username'];
		}
		
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
		
		if(key_exists('S_Y', $data) && key_exists('S_M', $data) && key_exists('S_D', $data))
		{
			$this->startDate = $data['S_Y'].'-'.$data['S_M'].'-'.$data['S_D'];
		}

		if(key_exists('E_Y', $data) && key_exists('E_M', $data) && key_exists('E_D', $data))
		{
			$this->endDate = $data['E_Y'].'-'.$data['E_M'].'-'.$data['E_D'];
		}
	}
	
	private function _getWhereCond()
	{
		$where = ' ui.id_world = '.$this->worldId;
		
		if(isset($this->startDate))
		{
			$where .= " AND l.delete_date > '".$this->startDate."'";
		}
		
		if(isset($this->endDate))
		{
			$where .= " AND l.delete_date < '".$this->endDate."'";
		}
		
		if(isset($this->userName))
		{
			$where .= " AND u.username = '".$this->userName."'";
		}
		
		if(isset($this->objectType))
		{
			$where .= " AND l.id_object_type = ".$this->objectType;
		}
		
		return $where;
	}
	
	private function _getWorldList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				wc.worldname,
				wc.dbname,
				wc.id_world
			FROM
				ds_worldcollection AS wc
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		foreach ($res as $k => $world)
		{
			$res[$k]['dbId'] = $common->getDBIDByName($world['dbname']);
		}
		
		return $res;
	}
	
	private function _getObjectTypes()
	{
		
	}
	
	private function _getLogs()
	{
		$sql = "
select
    l.delete_date,
    
    cn.combname,
    
    case
        when l.id_object_type = 2 THEN
            (
                select
                    ltl.text
                from
                    ds_building as b
                    inner join ds_buildingcanbuildbyrace as bcbr on (bcbr.id_building = b.id_building)
                    inner join ds_localizedtextlanguage as ltl on (ltl.id_localizedtext = bcbr.id_localizedname)
                where
                    b.id_building = l.id_object
                    and
                    bcbr.id_race = ui.id_race
                    and
                    ltl.id_language = 3
            )
        when l.id_object_type = 1 THEN
            (
                select
                    ltl.text
                from
                    ds_artefact as a
                    inner join ds_localizedtextlanguage as ltl on (ltl.id_localizedtext = a.id_localizedname)
                where
                    a.id_artefact = l.id_object
                    and
                    ltl.id_language = 3
            )
    end as name,
    
    case
        when l.id_object_type = 1 then
           'артефакт'

        when l.id_object_type = 2 then
            'здание'
    end as log_type,
    l.artefact_death_time
from
    ds_log_delete_game_objects as l
    inner join ds_comb as c using(id_comb)
    inner join ds_combname as cn using (id_comb)
    inner join ds_userinstance as ui on (ui.id_userinstance = c.id_userinstance)
    inner join ds_user as u on (u.id_user = ui.id_user)
where
   ".$this->_getWhereCond()."
order by l.delete_date
		";		
	
		$common = DS_Common::CreateForCurentDB($this->dbId);
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	
	private function _outIndex()
	{
		$worlds = $this->_getWorldList();
		
		if(isset($this->startDate))
		{
			$start = explode('-', $this->startDate);
			$S_Y = $start[0];
			$S_M = $start[1];
			$S_D = $start[2];
		} else {
			$S_D = 1;
			$S_M = date('m');
			$S_Y = date('Y');
		}
		
		if(isset($this->endDate))
		{
			$end = explode('-', $this->endDate);
			$E_Y = $end[0];
			$E_M = $end[1];
			$E_D = $end[2];
		} else {
			$E_D = date('d');
			$E_M = date('m');
			$E_Y = date('Y');
		}
		
		echo "
<form action='index.php' method='POST'>
<input type='hidden' name='action' value='show_log'>
<b><font color=white>User: </font></b><input type='text' name='username' value='".$this->userName."'>
<select name='world'>
";
		foreach ($worlds as $k => $world)
		{
			if($this->dbId == $world['dbId'] && $this->worldId == $world['id_world'])
			{
				$sel = 'selected';
			} else {
				$sel = '';
			}
			
			echo "<option $sel value=".$world['dbId'].'_'.$world['id_world'].">".$world['worldname'];
		}
		
		echo "
</select><br><br>
<input type='text' name='S_Y' size=4 maxlength=4 value='".$S_Y."'>
<select name='S_M'>
";
		for($i=1;$i<13;$i++) 
		{
			if($S_M == $i)	$sel = 'selected';
			else				$sel = '';
			
			if($i < 10)			$i = '0'.$i;
			
			echo "<option value=$i $sel>".$i;
		}
		
		echo "
</select>
<input type='text' name='S_D' size=2 maxlength=2 value=".$S_D.">&nbsp;&nbsp;<b><font color=white>-</font></b>&nbsp;&nbsp;
<input type='text' name='E_Y' size=4 maxlength=4 value='".$E_Y."'>
<select name='E_M'>
";
		for($i=1;$i<13;$i++)
		{
			if($E_M == $i)	$sel = 'selected';
			else				$sel = '';
				
			if($i < 10) $i = '0'.$i;
				
			echo "<option value=$i $sel>".$i;
		}
		
		echo "
</select>
<input type='text' name='E_D' size=2 maxlength=2 value='".$E_D."'>
<br><br>
<input type='submit' class='submit' value='&nbsp;&nbsp;Search&nbsp;&nbsp;'>
</form>
		";
	}
	
	private function _outLogs()
	{
		$this->_outIndex();
		
		$logs = $this->_getLogs();
		
		echo "
<table cellspacing=0 cellpadding=3 border=1>
	<tr class=th_2>
		<td>DATE</td>
		<td>COMB</td>
		<td>OBJECT</td>
		<td>ART DEATH TIME</td>
		<!--td>TYPE</td-->
	<tr>
";
		
		foreach ($logs as $k => $log)
		{
			echo '<tr class=maintxt><td>'.$log['delete_date'].'</td><td>'.$log['combname'].'</td><td>'.$log['name'].'</td><td>&nbsp;'.$log['artefact_death_time'].'&nbsp;</td></tr>';
		}
			
		echo "</table>";
	}
}

$v = new LogDeleteGameObjects($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>