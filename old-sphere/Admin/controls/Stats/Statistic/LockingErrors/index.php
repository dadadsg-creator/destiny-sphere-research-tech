<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class ShowStat
{
	static private $world = 'ALL';

	static private $action = 'index';

	static private $error;

	static private $dateFrom;

	static private $dateFromArr;

	static private $dateTo;

	static private $dateToArr;

	static private $detail = 3;


	static public function Exec(array $data)
	{
		self::_getParams($data);

		switch (self::$action)
		{
			case 'index':
				self::_showIndex();
				break;

			case 'showStat':
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
		}

		if(count(self::$dateTo) > 0)
		{
			$end = self::$dateToArr;
		} else {
			$end[0] = date('Y');
			$end[1] = date('m');
			$end[2] = date('d');
		}

		$dateParts = array(
		0 => 'year',
		1 => 'month',
		2 => 'week',
		3 => 'day',
		4 => 'hour',
		);


		echo '
		<form action="" method="POST">
		<input type="hidden" name="action" value="showStat">
		<table border=0>
			<tr>
				<td>
					<font color=#F8F8FF><b>World: </b></font>
				</td>
				<td>
					<select name="world">';
		foreach ($worldList as $k => $w)
		{
			if ($w['id_worldcollection'] == self::$world) {
				$selected = 'selected=1';
			} else {				
				$selected = '';
			}
			
			echo '
					<option value="'.$w['id_worldcollection'].'" '.$selected.'>'.$w['worldname'];
		}
		echo '
					</select>
				</td>
				<td>
					<font color=#F8F8FF><b> Date from: </b></font>
				</td>
				<td>
					'.self::GetTimeMenu($start[0], $start[1], $start[2], 'from').'
				</td>
			</tr>
			<tr>
				<td>
					<font color=#F8F8FF><b>Detail: </b></font>
				</td>
				<td>
					<select name="detail">';
		foreach ($dateParts as $k => $v)
		{
			if ($k == self::$detail) {
				$selected = 'selected=1';
			} else {				
				$selected = '';
			}

			echo '<option value="'.$k.'" '.$selected.'>'.$v;
		}
		echo '
					</select>
				</td>
				<td>
					<font color=#F8F8FF><b> Date to: </b></font>
				</td>
				<td>
					'.self::GetTimeMenu($end[0], $end[1], $end[2], 'to').'
				</td>
			</tr>
		</table>
		<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>
			 ';
	}

	static private function _showStat()
	{
		$stat = self::_getStat();

		if (empty($stat)) {
			echo "<h2><font color='red'>No Data!</font></h2><br>";
			return ;
		}
		// Вычисляем наименьшее и наибольшее значения
		$max = 0;
		$min = $stat[0]['count'];
		$total = 0;
		$colspan = 1;
		foreach ($stat as $key => $data)
		{
			if ($max < $data['count']) {
				$max = $data['count'];
			}

			if ($min > $data['count']) {
				$min = $data['count'];
			}
			
			$total += $data['count'];
		}

		echo "<br><br>";

		echo "
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
			<tr>
				<td>
					<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>
						<tr>";

		foreach ($stat[0] as $detail => $data)
		{
			echo "
						
							<th class=th_2>
								<b>".strtoupper($detail)."</b>
							</th>";
			$colspan ++;
		}

		echo "
							<th class=th_2>
								<b>PART</b>
							</th>
						</tr>";

		foreach ($stat as $key => $data)
		{
			// Ширина графика
			$persent = round(100.0 * (int)$data['count'] / (int)$max);
		
			echo "
						<tr>";

			foreach ($data as $detail => $dataDetail)
			{
				if ($detail == 'count' && $dataDetail == $max) {
					$color = '#00dd00';
				} elseif ($detail == 'count' && $dataDetail == $min)
				{
					$color = 'red';
				} else {
					$color = '#F8F8FF';
				}
				
				echo "
							<td bgcolor=#444E6C align=center>
								<font color=$color>".$dataDetail."</font>
							</td>";
			}

			echo "
							<td bgcolor=#444E6C align=center>
								<img src='/ds/drowimage.php?width=$persent&ws=1.5' alt='$persent% from max'><font color=#F8F8FF>&nbsp;$persent%</font>
							</td>
						</tr>";


		}

		echo "
						<tr class=th_2>
							<td colspan=$colspan>
								Total: <b>$total</b>.&nbsp;&nbsp;&nbsp;&nbsp;Min: <span style='color: red'><b>".$min."</b></span>.&nbsp;&nbsp;&nbsp;&nbsp;Max: <span style='color: #00dd00'><b>".$max."</b></span>.
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>";
	}

	static private function _getWorldList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				id_worldcollection,
				worldname
			FROM
				ds_worldcollection
			WHERE
				world_identifier != 'UC'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		$key = count($res);
		$res[$key]['id_worldcollection'] = 'ALL';
		$res[$key]['worldname'] = 'ALL';
	
		return $res;
	}

	static private function _getStat()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$where = "";
		if (self::$world != 'ALL') {
			$where = "
			AND 
			 id_worldcollection = '".self::$world."'";
		}
		$sql = "
		SELECT  
			".self::formSQLPart(self::$detail, true).",
			count(*) as count 
		FROM
			ds_user_locking_error ul
        WHERE
        	 date > '".self::$dateFrom."'
			AND
			 date < '".self::$dateTo."'
			 $where
        GROUP BY
        	".self::formSQLPart(self::$detail)."
        ORDER BY
        	".self::formSQLPart(self::$detail);

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}

	static private function _getParams(array $data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}

		if(key_exists('world', $data))
		{
			self::$world = $data['world'];
		}

		if(key_exists('from_y', $data) && key_exists('from_m', $data) && key_exists('from_d', $data))
		{
			self::$dateFrom = (int)$data['from_y'].'-';
			self::$dateFrom .= (int)$data['from_m'].'-';
			self::$dateFrom .= (int)$data['from_d'].' ';
			self::$dateFrom .= "00:00:00";
			
			self::$dateFromArr[0] = (int)$data['from_y'];
			self::$dateFromArr[1] = (int)$data['from_m'];
			self::$dateFromArr[2] = (int)$data['from_d'];
		}

		if(key_exists('to_y', $data) && key_exists('to_m', $data) && key_exists('to_d', $data))
		{
			self::$dateTo = (int)$data['to_y'].'-';
			self::$dateTo .= (int)$data['to_m'].'-';
			self::$dateTo .= (int)$data['to_d'].' ';
			self::$dateTo .= "23:59:59";
			
			self::$dateToArr[0] = (int)$data['to_y'];
			self::$dateToArr[1] = (int)$data['to_m'];
			self::$dateToArr[2] = (int)$data['to_d'];
			
		}

		if(key_exists('detail', $data))
		{
			self::$detail = (int)$data['detail'];
			assert(self::$detail >= 0);
		}
	}

	static private function formSQLPart($detail, $alias = false)
	{
		$dateParts = array(
		0 => 'year',
		1 => 'month',
		2 => 'week',
		3 => 'day',
		4 => 'hour',
		);

		for($i=0; $i<=$detail; $i++)
		{
			$sql[$i] = "extract(".$dateParts[$i]." from date)";
			if($alias)
			{
				$sql[$i] .= ' as '.$dateParts[$i];
			}
		}

		return implode(', ', $sql);
	}

	static private function GetTimeMenu($year, $month, $day, $direction)
	{
		assert($year > 0);
		assert(0 < $month && $month < 13);
		assert(0 < $day && $day < 32);
		assert(!empty($direction));
	
		$out = '<select name="'.$direction.'_y">';
		
	//<td><input type="text" size=4 name="dateFrom[0]" value="'.$start[0].'">&nbsp;-&nbsp;</td>
		
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
		
		return $out;
	}
	
}class ShowLog
{
	static private $userName = "";
	
	static private $world;
	
	static private $action = 'index';
	
	static private $error;
	
	static public function Exec(array $data)
	{
		self::_getParams($data);
		
		switch (self::$action)
		{
			case 'index':
				self::_showIndex();
				break;
				
			case 'stat':
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
		
		$str = '
		<form action="" method="GET">
		<table border=0>
			<tr>
				<td>
					<span class="maintxt">USERNAME:</span>&nbsp;&nbsp;
				</td>
				<td>
					<input type="text" size=20 name="name">
				</td>
			</tr>
			<tr>
				<td>
					<span class="maintxt">WORLD:</span>&nbsp;&nbsp;
				</td>
				<td>
					<select name="world">';
		foreach ($worldList as $k => $w)
		{
			$str .= '
						<option value="'.$w['id_worldcollection'].'">'.$w['worldname'];
		}
		$str .= '
					</select>
				</td>
			</tr>
		</table>
		<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;">
		<input type="hidden" name="log" value="1">
		</form>';
		
		echo $str;
	}
	
	static private function _showStat()
	{
		$stat = self::_getStat();
		
		echo <<<EOT
		<script language="JavaScript">
<!--

function switchInfo(num)
{
	var infoDiv = document.getElementById('detail_' + num);
	var href = document.getElementById('href_' + num);
	
	if(infoDiv.style.display == 'block')
	{
		infoDiv.style.display = 'none';
		href.innerHTML = '+';
	} else {
		infoDiv.style.display = 'block';
		href.innerHTML = '-';
	}
}

//-->
</script>
EOT;
		
		$str = '<a href=index.php?log=1><< BACK</a><br><br>';
		
		$str .= '<table border=1 cellspacing=0>';
		$str .= "<tr><td class=th_2>&nbsp;&nbsp;&nbsp;USER&nbsp;&nbsp;&nbsp;</td><td class=th_2>&nbsp;&nbsp;&nbsp;DATE&nbsp;&nbsp;&nbsp;</td><td>reason</td><td>lock time</td><td>&nbsp;</td></tr>";
		foreach ($stat as $k => $s)
		{
			$str .= "<tr>";
			$str .= "<td class=\"maintxt\">&nbsp;&nbsp;".$s['username']."&nbsp;&nbsp;</td>";
			$str .= "<td class=\"maintxt\">".$s['date']."</td>";
			$str .= "<td>".$s['reason']."</td>";
			$str .= "<td>".$s['lock_time']."</td>";
			$str .= "<td><a style='color: #000000' href='javascript:switchInfo(".$k.")' id='href_".$k."'>+</a><br><div style='display: none;' id='detail_".$k."'><pre>".$s['info']."</pre></div></td>";
			$str .= "</tr>";
		}
		$str .= '</table>';
		
		echo $str;
	}
	
	static private function _getWorldList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				id_worldcollection,
				worldname
			FROM
				ds_worldcollection
			WHERE
				world_identifier != 'UC'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static private function _getStat()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		if(!empty(self::$userName))
		{
			$where = " u.username ilike '".self::$userName."' AND ";
		} else {
			$where = " le.date > now() - '5 DAY'::interval AND ";
		}
		
		$sql = "
			SELECT
				u.username,
				le.*
			FROM
				ds_user_locking_error AS le
				INNER JOIN ds_user AS u USING (id_user)
			WHERE
				$where
				id_worldcollection = ".self::$world."
			ORDER BY le.date DESC
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static private function _getParams(array $data)
	{
		if(key_exists('name', $data) && key_exists('world', $data))
		{
			self::$userName = $data['name'];
			if(!empty(self::$userName) && !VD_UserName::Validate(self::$userName))
			{
				self::$error = VD_UserName::FetchErrorCode();
				return;
			}
		
			self::$world = (int)$data['world'];
			if(self::$world <= 0)
			{
				self::$error = 'WRONG_WORLD';
				return;
			}
			
			self::$action = 'stat';
		}
	}
}

if (key_exists('log', $_REQUEST)) {
	
	echo '
			<a href="?stat=1" class="maintxt">LockingStat</a> 
			<img src="/Admin/img/menu-arrow.gif" border=0> 
			<span class=select_txt ><b>Locking Log</b></span></b><br><br>';
	
	ShowLog::Exec($_REQUEST);
} else {
	echo '
			<span class=select_txt ><b>Locking Stat</b></span>
			<img src="/Admin/img/menu-arrow.gif" border=0> 
			<a href="?log=1" class="maintxt">Locking Log</a><br><br>';
	
	ShowStat::Exec($_REQUEST);
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>