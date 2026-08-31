<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class CaptureColonies
{
	static private $dbId;

	static private $worldId;

	static private $action = 'index';

	static private $error;

	static private $dateFrom;

	static private $dateFromArr;

	static private $dateTo;

	static private $dateToArr;

	static public function Exec(array $data)
	{
		self::_getParams($data);

		switch (self::$action)
		{
			case 'index':
				self::_showIndex();
				break;

			case 'stat':
				self::_showIndex();
				self::_showStat();
				break;
		}
	}

	static private function _showIndex()
	{
		$worldList = self::_getWorldList();

		if(!empty(self::$error))
		{
			ShowCMSMessage(self::$error, 2);
		}

		if(count(self::$dateFrom) > 0)
		{
			$start = self::$dateFromArr;
		} else {

			$week = strtotime("-1 week");

			$start[0] =  date('Y', $week);
			$start[1] = date('m', $week);
			$start[2] = date('d', $week);
			$start[3] = date('H', $week);
			$start[4] = date('i', $week);
		}

		if(count(self::$dateTo) > 0)
		{
			$end = self::$dateToArr;
		} else {
			$end[0] = date('Y');
			$end[1] = date('m');
			$end[2] = date('d');
			$end[3] = date('H');
			$end[4] = date('i');
		}

		$dateParts = array(
		0 => 'year',
		1 => 'month',
		2 => 'day',
		3 => 'hour',
		4 => 'minutes',
		);

		echo '
		<form action="" method="GET">
		<table border=0>
			<tr>
				<td>
					<font color=#F8F8FF><b> World: </b></font>&nbsp;&nbsp;
				</td>
				<td>
					<select name="world">';
		foreach ($worldList as $id => $name)
		{
			if ($id == self::$dbId."_".self::$worldId) {
				$c = 'checked';
			} else {
				$c = '';
			}

			echo '
						<option value="'.$id.'" '.$c.'>'.$name;
		}

		echo '
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<font color=#F8F8FF><b> Date from: </b></font>
				</td>
				<td>
					'.self::GetTimeMenu($start[0], $start[1], $start[2], $start[3], $start[4], 'from').'
				</td>
			</tr>
			<tr>
			
				<td>
					<font color=#F8F8FF><b> Date to: </b></font>
				</td>
				<td>
					'.self::GetTimeMenu($end[0], $end[1], $end[2], $end[3], $end[4], 'to').'
				</td>
			</tr>
				';

		echo '
			</table>
		<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>
			 ';

	}

	static private function _showStat()
	{
		$stat = self::_getStat();
		$totalAmount = self::_getTotalAmount();
		$amount = self::_getAmount();

		echo "
		<table border='0'>
		<tr>
			<td>
			<table width=100% border=0 bgcolor=#444E6C>
					<tr>
						<td>
							<font color=#F8F8FF>Всего временных колоний в мире: </font>
						</td>
						<td>
							<font color=#F8F8FF><b>$totalAmount</b></font>
						</td>
					</tr>
					<tr>
						<td>
							<font color=#F8F8FF>Временных колоний в мире в руках DestinySphere: </font>
						</td>
						<td>
							<font color=#F8F8FF><b>$amount</b></font>
						</td>
					</tr>
			</table>
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
		</tr>
		<tr>
			<td>
				<font color=#F8F8FF>Статистика по захватам: </font>
			</td>
		</tr>
		<tr>
			<td>";

		if(empty($stat))
		{
			echo "<font color=red>Захватов за данный период не было!</font>";
		} else {
			echo "
				<table border=1 cellspacing=0>
					<tr>
						<td class=th_2>
							Date
						</td>
						<td class=th_2>
							Count
						</td>
					</tr>";

			foreach ($stat as $key => $data)
			{
				echo "
					<tr>
						<td class=maintxt align=right>
							<nobr>&nbsp;".$data['day']."-".$data['month']."-".$data['year']."&nbsp;</nobr>
						</td>
						<td class=maintxt align=right>
							<nobr>&nbsp;".$data['count']."&nbsp;</nobr>
						</td>
					</tr>
					";
			}

			echo "
				</table>
				";
		}
		echo "
			</td>
		</tr>
		</table>";

	}

	static private function _getWorldList()
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
				$keyRes = $dbid."_".$v['id_world'];
				$out[$keyRes] = $v['worldname'];
			}
		}

		return $out;
	}

	static private function _getStat()
	{
		$common = DS_Common::CreateForCurentDB(self::$dbId);

		$sql = "
			SELECT 
				extract(year from capture_date) as year,
				extract(month from capture_date) as month,
				extract(day from capture_date) as day,
				count(*) as count
			FROM 
				ds_capture_colony_history c
			WHERE 
				c.capture_date > '".self::$dateFrom."'
				AND c.capture_date < '".self::$dateTo."'
				AND c.id_world = '".self::$worldId."'
			GROUP BY 
				extract(year from capture_date),
				extract(month from capture_date),
				extract(day from capture_date)
			ORDER BY 
				extract(year from capture_date),
				extract(month from capture_date),
				extract(day from capture_date)
			";

		$res = $common->getResultArray("", $sql, "", "", "");

		assert(empty($common->errormsg));

		return $res;
	}

	static private function _getTotalAmount()
	{
		$common = DS_Common::CreateForCurentDB(self::$dbId);

		$sql = "
			SELECT 
			    count(*) as count
			FROM ds_comb c
			WHERE 
				c.is_capture = 't'
			";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res[0]['count'];
	}
	
	static private function _getAmount()
	{
		$common = DS_Common::CreateForCurentDB(self::$dbId);

		$sql = "
			SELECT 
			    count(*) as count
			FROM ds_comb c
			WHERE 
				c.is_capture = 't'
				AND 
				c.id_userinstance  = 0 
			";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res[0]['count'];
	}

	static private function _getParams(array $data)
	{
		if(key_exists('world', $data))
		{
			$tempArr = explode('_', $data['world']);
			self::$dbId = (int)$tempArr[0];
			self::$worldId = (int)$tempArr[1];

			assert(self::$dbId >= 0);
			assert(self::$worldId > 0);

			self::$action = 'stat';
		}

		if(key_exists('from_y', $data) && key_exists('from_m', $data) && key_exists('from_d', $data))
		{
			self::$dateFrom = (int)$data['from_y'].'-';
			self::$dateFrom .= (int)$data['from_m'].'-';
			self::$dateFrom .= (int)$data['from_d'].' ';
			self::$dateFrom .= (int)$data['from_h'].':';
			self::$dateFrom .= (int)$data['from_min'].':';
			self::$dateFrom .= "00";

			self::$dateFromArr[0] = (int)$data['from_y'];
			self::$dateFromArr[1] = (int)$data['from_m'];
			self::$dateFromArr[2] = (int)$data['from_d'];
			self::$dateFromArr[3] = (int)$data['from_h'];
			self::$dateFromArr[4] = (int)$data['from_min'];
		}

		if(key_exists('to_y', $data) && key_exists('to_m', $data) && key_exists('to_d', $data))
		{
			self::$dateTo = (int)$data['to_y'].'-';
			self::$dateTo .= (int)$data['to_m'].'-';
			self::$dateTo .= (int)$data['to_d'].' ';
			self::$dateTo .= (int)$data['to_h'].':';
			self::$dateTo .= (int)$data['to_min'].':';
			self::$dateTo .= "59";

			self::$dateToArr[0] = (int)$data['to_y'];
			self::$dateToArr[1] = (int)$data['to_m'];
			self::$dateToArr[2] = (int)$data['to_d'];
			self::$dateToArr[3] = (int)$data['to_h'];
			self::$dateToArr[4] = (int)$data['to_min'];
		}
	}

	static private function GetTimeMenu($year, $month, $day, $hour, $minutes, $direction)
	{
		assert($year > 0);
		assert(0 < $month && $month < 13);
		assert(0 < $day && $day < 32);
		assert(0 < $hour && $hour < 24);
		assert(0 <= $minutes && $minutes < 60);
		assert(!empty($direction));

		$out = '<select name="'.$direction.'_y">';

		//Выводим год
		for($i=date('Y')-2; $i<date('Y')+1; $i++)
		{
			if($i == $year)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '
					<option value='.$i.$s.'>'.$i;
		}
		$out .= '
				</select><font color=#F8F8FF> - </font>';

		//Выводим месяц
		$out .= '
				<select name="'.$direction.'_m">';
		for($i=1; $i<13; $i++)
		{
			if($i == $month)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '
					<option value='.$i.$s.'>'.$i;
		}
		$out .= '
				</select><font color=#F8F8FF> - </font>';

		//Выводим число
		$out .= '
				<select name="'.$direction.'_d">';
		for($i=1; $i<32; $i++)
		{
			if($i == $day)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '
					<option value='.$i.$s.'>'.$i;
		}
		$out .= '
				</select>';

		$out .= '
				</select><font color=#F8F8FF>&nbsp;&nbsp;&nbsp;</font>';

		//Выводим Часы
		$out .= '
				<select name="'.$direction.'_h">';
		for($i=0; $i<24; $i++)
		{
			if($i == $hour)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '
					<option value='.$i.$s.'>'.$i;
		}
		$out .= '
				</select>';
		$out .= '
				<font color=#F8F8FF> : </font>';

		//Выводим минуты
		$out .= '
				<select name="'.$direction.'_min">';
		for($i=0; $i<60; $i++)
		{
			if($i == $minutes)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '
					<option value='.$i.$s.'>'.$i;
		}
		$out .= '
				</select>';

		return $out;
	}
}


CaptureColonies::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>