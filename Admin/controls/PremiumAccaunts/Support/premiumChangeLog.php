<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class premiumChangeLog
{
	private $user;
	
	private $action = 'no';
	
	private $daysDelta;
	
	private $comments = '';
	
	public function __construct($data)
	{
		$this->_getParams($data);
		
		switch ($this->action) {
			case 'index':
				$this->_showIndex();
				break;
				
			case 'log':
				$this->_showChangeLog();
				break;
				
			default:
				echo ShowCMSMessage("NO ACTION", 2);
				break;
		}
	}
	
	private function _getParams($data)
	{
		if(key_exists('id_user', $data))
		{
			$uid = (int)$data['id_user'];
			assert($uid > 0);
			$this->user = DE_User::CreateFromID($uid);
			$this->action = 'index';
		}
		
		if(key_exists('days', $data))
		{
			$this->daysDelta = (int)$data['days'];
		}
		
		if(key_exists('log', $data))
		{
			$this->action = 'log';
		}
	}
	
	private function _showIndex()
	{
		$this->_showChangeLog();
	}
	
	private function _showChangeLog()
	{
		$log = $this->_getChangeLog();
		
		echo '
		<table style="border: 1px solid black; border-collapse: collapse;" cellpadding=3 cellspacing=0 >
			<tr style="border: 1px solid black;">
				<td class=th_2 align="center" style="border: 1px solid black;">type</td>
				<td class=th_2 align="center" style="border: 1px solid black;">action date</td>
				<td class=th_2 align="center" style="border: 1px solid black;">days</td>
				<td class=th_2 align="center" style="border: 1px solid black;">end date old</td>
				<td class=th_2 align="center" style="border: 1px solid black;">end date new</td>
				<td class=th_2 align="center" style="border: 1px solid black;">comments</td>
			</tr>
		';
		
		foreach($log as $k => $rec)
		{
			echo '<tr style="border: 1px solid black;">
			<td class=td align="center" bgcolor="#444E6C" style="border: 1px solid black;"><b>'.$rec['type'].'</b></td>
			<td class=td bgcolor="#444E6C" style="border: 1px solid black;">'.$rec['date'].'</td>
			<td class=td align="right" bgcolor="#444E6C" style="border: 1px solid black;"><b>'.$rec['days_delta'].'</b></td>
			<td class=td bgcolor="#444E6C" style="border: 1px solid black;">'.$rec['end_date_old'].'</td>
			<td class=td bgcolor="#444E6C" style="border: 1px solid black;">'.$rec['end_date_new'].'</td>
			<td class=td bgcolor="#444E6C" style="border: 1px solid black;">'.$rec['comments'].'</td>
			</tr>
			';
		}
		
		echo '</table>
		<br>
		<div align=left><a href="javascript:window.close()">закрыть окно</a></div>
		';
	}
	
	private function _getChangeLog()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				*
			FROM
				ds_premium_change_log
			WHERE
				id_user = ".$this->user->GetID()."
			ORDER BY date DESC
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		if(!empty($common->errormsg))
		{
			echo ShowCMSMessage("SQL ERROR :".$common->errormsg, 2);
			return array();
		}
		
		return $res;
	}
}

$p = new premiumChangeLog($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>