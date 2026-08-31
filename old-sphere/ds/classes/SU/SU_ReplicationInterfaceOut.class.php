<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/06/26 10:58:51 $';

class SU_ReplicationInterfaceOut
{
	static private function _outHeader($withBackLink = false)
	{
		$str = "<a name=top></a><h1>Replication Control Interface</h1>";
		if($withBackLink)
		{
			$str .= "<a href='ReplicationControl.php'><< Back</a><br><br>";
		} else {
			$str .= "<a href='ReplicationControl.php?r=".rand()."'>Refresh</a><br><br>";
		}
		
		echo $str;
	}
	
	static public function OutIndex($status, $info)
	{
		self::_outHeader();
		
		if($status == 'OK')
		{
			$statusStr = '<b><font color=green>'.$status.'</font></b>';
		} else {
			$statusStr = '<b><font color=red>'.$status.'</font></b>';
		}
		
		$str = "<b>REPLICATION STATUS: </b>".$statusStr."<br><br>\n";
	
		$str .= "<b>CONTROLS:</b><br><br>";
		$str .= "<table border=1 width=450>";
		
		if(file_exists($GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN']."Replication.log"))
		{
			$str .= "<tr><td><a target='_blank' href='".$GLOBALS['GAME_HOST']."/ds/utils/LogView.php?action=show&file=".$GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN']."Replication.log#down'>Replication log file</a></td><td>View replication system log</td></tr>\n";
		} else {
			$str .= "<tr><td>NO LOG</td><td>View replication system log</td></tr>\n";
		}
		if($status == 'OK' || $status == 'PAUSE')
		{
			$str .= "<tr><td><a href='ReplicationControl.php?action=stop'>Stop replication</a></td><td>Stop replication system</td></tr>\n";
		}
		if($status == 'OK')
		{
			$str .= "<tr><td><a href='ReplicationControl.php?action=pause'>Pause replication</a></td><td>Pause replication system</td></tr>\n";
		} elseif($status == 'PAUSE') {
			$str .= "<tr><td><a href='ReplicationControl.php?action=resume'>Resume replication</a></td><td>Resume replication system</td></tr>\n";
		}
		$str .= "<tr><td><a href='ReplicationControl.php?action=view_slave'>Slave list</a></td><td>View replication slave DB list</td></tr>\n";
		
		$str .= "<tr><td><a href='ReplicationControl.php?action=view_table'>Slave tables</a></td><td>View DB table list</td></tr>\n";
		$str .= "</table><br><br>";
		
		$str .= "<b>DETAILS</b>:\n";
		
		$arr = explode('|', $info);
		if(count($arr) >= 7)
		{
			$reqDate = $arr[0];
			$id = $arr[1];
			$startTime = $arr[2];
			$countIns = $arr[3];
			$countUpd = $arr[4];
			$countDel = $arr[5];
			$errorLog = $arr[6];
		}
		
		if($status == 'OK' && count($arr) >= 7)
		{
			$str .= "<ul>\n";
			$str .= "<li>DEAMON ID: <b>".$id."</b>\n";
			$str .= "<li>START TIME: <b>".date('d-m-Y H:i:s', $startTime)."</b>\n";
			$str .= "<li>INSERTS: <b>".$countIns."</b>\n";
			$str .= "<li>UPDATES: <b>".$countUpd."</b>\n";
			$str .= "<li>DELETES: <b>".$countDel."</b>\n";
			$str .= "<li>ERROR LOG: <b><pre>".$errorLog."</pre></b>\n";
			$str .= "</ul>\n";
			$str .= "<br>\n";
		} else {
			$str .= "<pre>".$info."</pre><br>\n";
		}
		
		echo $str;
	}
	
	static public function OutSlaveList(array $slaveList, array $dbnames)
	{		
		self::_outHeader(true);
		
		$str = "<table border=1 width=450>\n";
		
		$str .= "<tr><td bgcolor=#ACACAC align=center><b>ID</b></td><td bgcolor=#ACACAC align=center><b>DB NAME</b></td><td align=center bgcolor=#ACACAC><b>REMOVE</b></td></tr>\n";
		
		foreach ($slaveList as $k => $slave)
		{
			$str .= "<tr><td align=center>".$slave['id_slave']."</td><td>".$slave['server']."</td><td align=center><a href='ReplicationControl.php?action=remove_slave&slave=".$slave['id_slave']."'>remove</a></td></tr>\n";
		}
		
		$str .= "</table>\n";
		
		if(count($dbnames) > 0)
		{
			$str .= "<form action='ReplicationControl.php' method='GET'>";
			$str .= "<input type='hidden' name='action' value='add_slave'>";
			$str .= "<b>DB:</b> <select name='dbname'>";
		
			foreach ($dbnames as $k => $dbname)
			{
				$str .= "<option value=$dbname>$dbname\n";
			}
			
			$str .= "<input type='submit' value='add db'>";
			$str .= "</select>";
			$str .= "</form>";
		}
		
		echo $str;
	}
	
	static public function OutTableList(array $replicatedTables, array $canBeReplicatedTables, array $cantBeReplicatedTables)
	{
		self::_outHeader(true);
		
		$str .= "<a href='#bottom'>down</a>";
		
		$str .= "<h2>Replicated tables:</h2>";
		$str .= "<table border=1 width=450>\n";
		$str .= "<tr><td bgcolor=#ACACAC align=center><b>TABLE NAME</b></td><td bgcolor=#ACACAC align=center><b>TRIGGERS</b></td></tr>\n";
		foreach ($replicatedTables as $k => $table)
		{					
			$str .= "<tr><td><b>".$table['NAME']."</b></td><td align=center>";
			$str .= "<a href=ReplicationControl.php?action=remove_table&table=".$table['NAME']."><font color=red>REMOVE</font></a>\n";
			
			$str .= "</td></tr>";
		}
		$str .= "</table>\n";
		
		$str .= "<h2>Can be replicated:</h2>";
		$str .= "<table border=1 width=450>\n";
		$str .= "<tr><td bgcolor=#ACACAC align=center><b>TABLE NAME</b></td><td bgcolor=#ACACAC align=center><b>ADD REPLICATION USING FIELD</b></td></tr>\n";
		foreach ($canBeReplicatedTables as $k => $table)
		{					
			$str .= "<tr><td>".$table['NAME']."</td><td align=left style='width: 270px' nowrap>";
			
			$colStr = '';
			foreach ($table['COLS'] as $k => $col)
			{
				$colStr .= "<option value=".$col['attname'].">".$col['attname'];
			}
			
			$str .= "<form action='ReplicationControl.php' method='GET'>";
			$str .= "<input type='hidden' name='action' value='add_table'>"; 
			$str .= "<input type='hidden' name='table' value='".$table['NAME']."'>"; 
			$str .= "<select name='col[]' style='width: 210px' MULTIPLE size=3>$colStr</select>"; 
			$str .= "&nbsp;&nbsp;<input type='submit' value='ADD'>"; 
			$str .= "</form>\n";
			
			$str .= "</td></tr>";
		}
		$str .= "</table>\n";
		
		$str .= "<h2>Can't be replicated (not present in slave DB):</h2>";
		$str .= "<table border=1 width=450>\n";
		$str .= "<tr><td bgcolor=#ACACAC align=center><b>TABLE NAME</b></td></tr>\n";
		foreach ($cantBeReplicatedTables as $k => $table)
		{					
			$str .= "<tr><td><font color=#9c9c9c>".$table."</font></td></tr>";
		}
		$str .= "</table>\n";
		
		$str .= "<a name=bottom></a><a href='#top'>up</a>";
		
		echo $str;
	}
}


?>