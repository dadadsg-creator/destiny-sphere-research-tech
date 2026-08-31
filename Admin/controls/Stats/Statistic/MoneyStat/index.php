<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class ShowStat
{
	static private $dbId;
	
	static private $worldIdn;

	static private $action = 'index';
	
	static private $credit;
	
	static private $worldname;


	static public function Exec(array $data)
	{
		self::_getParams($data);
			
		echo "<h1>Диагностика баланса денежной системы</h1>";

		switch (self::$action)
		{
			case 'index':
				self::_showIndex();
				break;

			case 'showStat':
				self::_showIndex();
				self::_showStat();
				break;

			case 'ExportCsv':
				//self::_showIndex();
				self::_ExportCsv();
				break;
		}
	}

	static private function _showIndex()
	{
		$worldList = self::_getWorldList();
		
		echo '
		<form action="" method="POST">
		<input type="hidden" name="action" value="showStat">
		<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Диагностика&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>';
		
		echo '
		<form action="" method="POST">
		<input type="hidden" name="action" value="ExportCsv">
		<input type="hidden" name="credit" value="gold">
		<table border=0>
			<tr>
				<td>
					<select name="world">';
		foreach ($worldList as $id => $name)
		{
			echo '
						<option value="'.$id.'">'.$name;
		}

		echo '
					</select>
				</td>
				<td>
					<input type="submit" class="Submit" value="&nbsp;Экспортировать в CVS топ по золоту&nbsp;">
				</td>
			</tr>
		</table>
		</form>';
		
		echo '
		<form action="" method="POST">
		<input type="hidden" name="action" value="ExportCsv">
		<input type="hidden" name="credit" value="silver">
		<table border=0>
			<tr>
				<td>
					<select name="world">';
		foreach ($worldList as $id => $name)
		{
			echo '
						<option value="'.$id.'">'.$name;
		}

		echo '
					</select>
				</td>
				<td>
					<input type="submit" class="Submit" value="Экспортировать в CVS топ по серебру">
				</td>
			</tr>
		</table>
		</form>';
	}

	static private function _showStat()
	{
		// Количество денег в кабинете
		$userstat = self::_getStatCabinet();
		
		if (empty($userstat)) {
			echo "<h2><font color='red'>No Data!</font></h2><br>";
			return ;
		}
	
		echo "<br><br>";

		echo "
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
			<tr>
				<td>
					<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>
						<tr>
							<th class=th_2 rowspan=2 valign=top>
								Мир
							</th>
							<th class=th_2 colspan=2> 
								DestinySphere
							</th>
							<th class=th_2 colspan=2>
								Игроки
							</th>
							<th class=th_2 colspan=2>
								Сумма
							</th>
						</tr>
							<th class=th_2>
								Золото
							</td>
							<th class=th_2>
								Серебро
							</td>
							<th class=th_2>
								Золото
							</td>
							<th class=th_2>
								Серебро
							</td>
							<th class=th_2>
								Золото
							</td>
							<th class=th_2>
								Серебро
							</td>
						</tr>
						<tr>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>Кабинет</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$userstat['ds']['gc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$userstat['ds']['sc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$userstat['users']['gc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$userstat['users']['sc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".($userstat['ds']['gc'] + $userstat['users']['gc'])."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".($userstat['ds']['sc'] + $userstat['users']['sc'])."</font>
							</td>
						</tr>";
		// Количество денег по мирам
		$stat = self::_getStat();
		
		foreach ($stat as $worldname => $worldstat)
		{
			echo "
						<tr>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>$worldname</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$worldstat['ds']['gc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$worldstat['ds']['sc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$worldstat['users']['gc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".$worldstat['users']['sc']."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".($worldstat['ds']['gc'] + $worldstat['users']['gc'])."</font>
							</td>
							<td bgcolor=#444E6C align=center>
								<font color=#F8F8FF>".($worldstat['ds']['sc'] + $worldstat['users']['sc'])."</font>
							</td>
			
						</tr>";
		}
		echo "
					</table>
				</td>
			</tr>
		</table>";
	}

	static private function _ExportCsv()
	{
		assert(self::$dbId >= 0);
		assert(!empty(self::$worldIdn));
		assert(!empty(self::$credit));
		
		if (self::$credit == 'silver') {
			$order = 'sc_amount';
		} else {
			$order = 'gc_amount';
		}
		
		echo "<h2>Мир: ".self::$worldname."</h2>";
		$common = DS_Common::CreateForCurentDB(self::$dbId);
		
		$sql = "
			SELECT 
				u.username, 
				m.gc_amount,
				m.sc_amount
		    FROM 
		    	ds_money_account m
		    LEFT JOIN ds_user u ON (m.id_user = u.id_user)
		    WHERE 
		    	m.id_user != 0
		    	AND 
		    	m.$order != 0 
		    	AND m.world_identifier = '".self::$worldIdn."'
		    ORDER BY 
		    	m.$order DESC
		    LIMIT 500
                    ";
		$res = $common->getResultArray("", $sql);
		
		if(!$res)
		{
			ShowCMSMessage("В мире нет данной валюты у игроков!", 2);
			return false;
		}

		$array = "";

		foreach ($res as $key => $value)
		{
			$array .= implode(';',$value ).";\r\n";
		}

		$title = Array
		(
			0 => 'Игрок',			
			1 => 'Золото',
			2 => 'Серебро'
		);

		$output = implode(';',$title).";\r\n".$array;

		/* Пишем в файл */
		$fileurl = "/Admin/controls/GameSettings/Localization/Export_CVS/data/money_top.csv";
		$fileName = $_SERVER['DOCUMENT_ROOT'].$fileurl;
		$mode = 'w';                                                                  

		if(!file_exists($fileName))
		{
			$mode = 'w+';
		} else {
			unlink($fileName);
		}

		if(!$fh = fopen($fileName, $mode))
		{
			ShowCMSMessage("Can't open file... $fileName", 2);
			return false;
		}
		$fwrite = fwrite($fh, $output);
		
		fclose($fh);

		if(file_exists($fileName))
		{
			ShowCMSMessage('<a href="'.$fileurl.'"> Download CSV file </a>', 1);
			return true;
		}
	}
	
	static private function _getStatCabinet()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$stat = array();
		
		$sql = "SELECT 
                        m.gc_amount as gc, 
                        m.sc_amount as sc
					FROM 
						ds_money_account m
                    LEFT JOIN ds_world w ON (m.world_identifier = w.world_identifier)
					WHERE 
						m.id_user = 0
						AND 
						m.world_identifier = 'UC'";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		$stat['ds']['gc'] = $res[0]['gc'];
		$stat['ds']['sc'] = $res[0]['sc'];	
				
		$sql = "SELECT 
                        sum(m.gc_amount) as gc, 
                        sum(m.sc_amount) as sc
					FROM 
						ds_money_account m
                    LEFT JOIN ds_world w ON (m.world_identifier = w.world_identifier)
					WHERE 
						m.id_user != 0
						AND 
						m.world_identifier = 'UC'";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		$stat['users']['gc'] = $res[0]['gc'];
		$stat['users']['sc'] = $res[0]['sc'];	

		return $stat;
	}
	
	static private function _getStat()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		// Проходимся по каждой базе
		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);
			
			// Список миров 
			$sql = "SELECT 
						worldname,
						world_identifier
					FROM 
						ds_world";
			$world = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			if (count($world) > 0) {
				// Проходимся по каждом миру
				foreach ($world as $key => $data) {
					$wi = $data['world_identifier'];
					$wname = $data['worldname'];
					
					$sql = "
					SELECT 
                        m.gc_amount as gc, 
                        m.sc_amount as sc
					FROM 
						ds_money_account m
					WHERE 
						m.id_user = 0
						AND 
						m.world_identifier = '$wi'";
					$res = $common->getResultArray('', $sql, '', '', '');
					assert(empty($common->errormsg));
					
					$stat[$wname]['ds']['sc'] = $res[0]['sc'];
					$stat[$wname]['ds']['gc'] = $res[0]['gc'];

					$sql = "
					SELECT
						sum(m.gc_amount) as gc, 
                        sum(m.sc_amount) as sc
					FROM 
						ds_money_account m
					WHERE 
						m.id_user != 0
						AND 
						m.world_identifier = '$wi'";
					$res = $common->getResultArray('', $sql, '', '', '');
					assert(empty($common->errormsg));

					$stat[$wname]['users']['sc'] = $res[0]['sc'];
					$stat[$wname]['users']['gc'] = $res[0]['gc'];
				}
			}
		}
		
		return $stat;
	}

	static private function _getParams(array $data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}
		
		if(key_exists('credit', $data))
		{
			self::$credit = $data['credit'];
		}
		
		if(key_exists('world', $data))
		{
			$tempArr = explode('_', $data['world']);
			self::$dbId = (int)$tempArr[0];
			self::$worldIdn = pg_escape_string($tempArr[1]);
			self::$worldname = pg_escape_string($tempArr[2]);

			assert(self::$dbId >= 0);
			assert(!empty(self::$worldIdn));
		}
	}
	
	static private function _getWorldList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$out = array();
		
		// Кабинет
		$out['0_UC_Кабинет'] = 'Кабинет';

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT world_identifier, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$keyRes = $dbid."_".$v['world_identifier']."_".$v['worldname'];
				$out[$keyRes] = $v['worldname'];
			}
		}

		return $out;
	}
}

ShowStat::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>