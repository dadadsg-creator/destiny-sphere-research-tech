<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

//echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=PaymentServices><font size=3><b>HELP</b></font></a></p>';
class UnitReincarnationStat
{
	private $action = 'index';

	private $dbId = "";

	private $worldId = "";

	private $userName = "";
	
	private $unitName = "";

	private $date = array();

	private $startDate;

	private $endDate;

	private $classes= array();

	private $races= array();

	private $sellFor = array();
	
	private $showStat = 'f';
	
	private $id_reincarnateUnit;

	public function __construct(array $data)
	{
/*echo "<pre>";
print_r($data);
echo "</pre><br><br>";*/

		$this->_getParams($data);
	
		if ($this->showStat == 't') {
			echo '
			<a href="index.php?showStat=f" class="maintxt">Reincarnation Log</a> 
			&nbsp;&nbsp;<img src="/Admin/img/menu-arrow.gif" border=0>&nbsp;&nbsp;
			<span class=select_txt ><b>Reincarnation Unit Stat</b></span>';
		} else {
			echo '
			<span class=select_txt ><b>Reincarnation Log</b></span>
			&nbsp;&nbsp;<img src="/Admin/img/menu-arrow.gif" border=0>&nbsp;&nbsp;
			<a href="index.php?showStat=t" class="maintxt">Reincarnation Unit Stat</a>';
		}

		switch ($this->action)
		{
			case 'index':
				$this->_showIndex();
				break;
			case 'showStat':
				$this->_showIndex();
				$this->_showStat();
				break;
			case 'showOperationStat':
				$this->_showOperationStat();
				break;
		}
	}

	private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
		if(key_exists('userName', $data))
		{
			$this->userName = $data['userName'];
		}

		if(key_exists('from_y', $data) && key_exists('from_m', $data) && key_exists('from_d', $data))
		{
			$this->date['from_y'] = (int)$data['from_y'];
			$this->date['from_m'] = (int)$data['from_m'];
			$this->date['from_d'] = (int)$data['from_d'];

			$this->startDate = mktime(0, 0, 0, (int)$data['from_m'], (int)$data['from_d'], (int)$data['from_y']);
			$this->startDate = date('Y-m-d H:m:i', $this->startDate);
		}

		if(key_exists('to_y', $data) && key_exists('to_m', $data) && key_exists('to_d', $data))
		{
			$this->date['to_y'] = (int)$data['to_y'];
			$this->date['to_m'] = (int)$data['to_m'];
			$this->date['to_d'] = (int)$data['to_d'];

			$this->endDate = mktime(23, 59, 59, (int)$data['to_m'], (int)$data['to_d'], (int)$data['to_y']);
			$this->endDate = date('Y-m-d H:m:i', $this->endDate);
		}

		if(key_exists('class', $data))
		{
			$this->classes = $data['class'];
		}

		if(key_exists('race', $data))
		{
			$this->races = $data['race'];
		}

		if(key_exists('sellFor', $data))
		{
			$this->sellFor = $data['sellFor'];
		}

		if(key_exists('world', $data))
		{
			$tmpArr = explode("_", $data['world']);
			$this->dbId = (int)$tmpArr[0];
			assert($this->dbId >= 0);
			$this->worldId = (int)$tmpArr[1];
			assert($this->worldId > 0);
		}

		if(key_exists('unitName', $data))
		{
			$this->unitName = $data['unitName'];
		}

		if(key_exists('showStat', $data) && $data['showStat'] == 't')
		{
			$this->showStat = 't';
		}
		
		if(key_exists('id_op', $data))
		{
			$this->id_reincarnateUnit = (int)$data['id_op'];
		}
	}

	private function _showIndex()
	{
		$common = DS_Common::CreateForCurentDB(0);


		// Получаем список всех миров
		$worlds = array();
		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbid'] = $dbid;
				$worlds[] = $v;
			}
		}
		
		// Инициализируем дату
		$QuantityOfRows = array(30, 50, 70, 100, All);
		$week = strtotime("-1 week");
		$nowY = date(Y);
		$nowM = date(n);
		$nowD = date(j);

		if ($this->date['from_y'] == '')
		{
			$this->date['from_y'] = date('Y', $week);
		}

		if ($this->date['from_m'] == '')
		{
			$this->date['from_m'] = date('m', $week);
		}
		if ($this->date['from_d'] == '')
		{
			$this->date['from_d'] = date('d', $week);
		}

		if ($this->date['to_y'] == '')
		{
			$this->date['to_y'] = $nowY;
		}

		if ($this->date['to_m'] == '')
		{
			$this->date['to_m'] = $nowM;
		}

		if ($this->date['to_d'] == '')
		{
			$this->date['to_d'] = $nowD;
		}

		
		if ($this->showStat == 't') {
			// Получаем список рас юнитов
			$sql = "
				SELECT
					id_race as id,
					racename as name
				FROM
					ds_race
				";
			$races = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
	
			// Получаем список классов юнитов
			$sql = "
				SELECT
					id_unitclass as id,
					classname as name
				FROM
					ds_unitclass
				";
			$classes = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
		}

		echo '
		<form action="" method="POST">
		<input type="hidden" name="action" value="showStat">
		<table border=0>
			<tr>
				<td valign=top>
					<font color=#F8F8FF><b>World:</b></font>
				</td>
				<td valign=top>
					<select name="world">';

		foreach($worlds as $k => $world)
		{
			if($world['dbid'].'_'.$world['id_world'] == $this->dbId.'_'.$this->worldId)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			echo
			'<option value='.$world['dbid'].'_'.$world['id_world'].' '.$s.'>'.$world['worldname'];
		}

		echo '
					</select>		
				</td>
				<td>
					<font color=#F8F8FF><b>Date from:</b></font>
				</td>
				<td>
					'.$this->GetTimeMenu($this->date['from_y'], $this->date['from_m'], $this->date['from_d'], 'from').'
				</td>
			</tr>
			<tr>
				<td>
					<font color=#F8F8FF><b>User name:</b></font>
				</td>
				<td>
					<font color=#F8F8FF><input type="text" name="userName" size="24" value="'.$this->userName.'"></font>
				</td>
				<td>
					<font color=#F8F8FF><b>Date to:</b></font>
				</td>
				<td>
					'.self::GetTimeMenu($this->date['to_y'], $this->date['to_m'], $this->date['to_d'], 'to').'
				</td>
		</table>';


		echo "
		<table border=0>
			<tr>
				
				<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td valign=top>";



		echo
		'<table border=0>';
		if ($this->showStat == 't') {
			echo '
							<td valign="buttom">
								<b><font color=#F8F8FF>Race:</font></b>
								
							</td>
							<td valign="buttom">
								&nbsp;&nbsp;&nbsp;&nbsp;
							</td>
							<td valign="buttom">
								<b><font color=#F8F8FF>Class:</font></b>
							</td>
							<td valign="buttom">
								&nbsp;&nbsp;&nbsp;&nbsp;
							</td>';
		}
		echo '
							<td valign="buttom">
								<b><font color=#F8F8FF>Sell for:</font></b>
							</td>
						</tr>
						<tr>';
		if ($this->showStat == 't') {
			echo '
							<td valign="buttom">
							';			
		//Выводим рассы юнитов
		foreach ($races as $key => $data)
		{
			if (count($this->races) != 0)
			{
				if(in_array($data['id'], $this->races))
				{
					$c = ' checked ';
				} else {
					$c = '';
				}
			} else {
				$c = ' checked ';
			}
			echo
			'
							<input type=checkbox name="race[]" value='.$data['id'].' '.$c.'><font color=#F8F8FF> '.$data['name'].'</font><br>';
		}
		echo '
								
						</td>
						<td valign="buttom">
								&nbsp;&nbsp;&nbsp;&nbsp;
							</td>
						<td valign="buttom">';

		//Выводим классы юнитов
		foreach ($classes as $key => $data)
		{			
			if (count($this->classes) != 0)
			{
				if(in_array($data['id'], $this->classes))
				{
					$c = ' checked ';
				} else {
					$c = '';
				}
			} else {
				$c = ' checked ';
			}
			
			echo '
							<input type=checkbox name="class[]" value='.$data['id'].' '.$c.' ><font color=#F8F8FF> '.$data['name'].'</font><br>';
		} 
		
		echo "
						</td>
						<td valign='buttom'>
								&nbsp;&nbsp;&nbsp;&nbsp;
						</td>";
		}
		
		$cR = '';
		$cC = '';
		$cS = '';
		
		if (count($this->sellFor) != 0)
		{			
			if(in_array('RES', $this->sellFor))
			{
				$cR = ' checked ';
			} 
			
			if(in_array('SIL', $this->sellFor))
			{
				$cC = ' checked ';
			} 
		} else {
			$cR = ' checked ';
			$cC = ' checked ';
		}
		echo "
						<td valign='buttom'>
							<input type=checkbox name='sellFor[]' value='RES' $cR><font color=#F8F8FF> RESOURSES</font><br>
							<input type=checkbox name='sellFor[]' value='SIL' $cC><font color=#F8F8FF> CREDITS</font><br>
							";
		echo "
						</td>
					</tr>
				</table>";

		echo '</td>
			</tr>
		</table>
		';
		if ($this->showStat == 't') {
			echo '
		<input type="hidden" name="showStat" value="t">
		<br><font color=#F8F8FF><b>Unit Name:</b> </font><input type="text" name="unitName" value="'.$this->unitName.'"><br>
			';
		}
		echo '
		<br><input type="submit"  class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>';
	}

	private function _showStat()
	{
		$common = DS_Common::CreateForCurentDB($this->dbId);

		if (count($this->sellFor) == 0) {
			ShowCMSMessage("Empty Sell For", 2);
			return;
		}


		if ($this->showStat == 't') {
			if (count($this->races) == 0) {
				ShowCMSMessage("Empty Races", 2);
				return;
			}

			if (count($this->classes) == 0) {
				ShowCMSMessage("Empty Classes", 2);
				return;
			}

			$sql = "
				SELECT
					l.text,
                    sum(uh.amount) as amount,
                    uh.id_unit
				FROM 
					ds_army_reincarnation_hystory ah
				LEFT JOIN ds_army_reincarnation_unit_hystory uh ON (ah.id_army_reincarnation_hystory = uh.id_army_reincarnation_hystory)
				LEFT JOIN ds_unit u ON (uh.id_unit = u.id_unit)
				LEFT JOIN ds_unithasclass uc ON (u.id_unit = uc.id_unit)
				LEFT JOIN ds_unitcanbebuildbyrace ur ON (u.id_unit = ur.id_unit)
				LEFT JOIN ds_userinstance ui ON (ah.id_userinstance = ui.id_userinstance)
				LEFT JOIN ds_user us ON (ui.id_user = us.id_user)
				LEFT JOIN ds_localizedtextlanguage l ON (u.id_localizedname = l.id_localizedtext AND l.id_language = 3)
				WHERE
							ui.id_world = ".$this->worldId." ";

			if (count($this->races) != 4) {
				//Где расса юнита
				$sql .= '
					AND
						( ';
				$or = "";
				foreach ($this->races as $k => $idRace)
				{
					$sql .= "$or
							ur.id_race = ".$idRace." ";
					$or = " OR ";
				}
				$sql .= " )";
			}

			if (count($this->classes) != 4) {
				//Где класс юнита
				$sql .= "
						AND
							(";
				$or = "";

				foreach ($this->classes as $k => $unitclass)
				{
					$sql .= "
									$or
									uc.id_unitclass = '$unitclass' ";
					$or = "OR";
				}
				$sql .= ")";
			}

			// Где имя юнита
			if (!empty($this->unitName)) {
				$sql .= "
							AND l.text = '".$this->unitName."' ";
			}
			
			if (count($this->sellFor) != 2) {
			//Где класс юнита
			if ($this->sellFor[0] == 'SIL') {
				$sql .= "
						AND uh.price like '%SIL%'";
			} else {
				$sql .= "
						AND NOT uh.price like '%SIL%'";
			}
		}

		} else {
			$sql = "
				SELECT
					ah.id_army_reincarnation_hystory,
					ah.date, 
					us.username, 
					ah.army_weight, 
					ah.total_price
				FROM 
					ds_army_reincarnation_hystory ah
				LEFT JOIN ds_userinstance ui ON (ah.id_userinstance = ui.id_userinstance)
				LEFT JOIN ds_user us ON (ui.id_user = us.id_user)
				WHERE
							ui.id_world = ".$this->worldId." ";
			
			if (count($this->sellFor) != 2) {
			//Где класс юнита
			if ($this->sellFor[0] == 'SIL') {
				$sql .= "
						AND ah.total_price like '%SIL%'";
			} else {
				$sql .= "
						AND NOT ah.total_price like '%SIL%'";
			}
		}

		}

		if (!empty($this->userName)) {
			$sql .= "
						AND us.username = '".$this->userName."' ";
		}

		$sql .= "
						AND ah.date > '".$this->startDate."' 
						AND ah.date < '".$this->endDate."'";


		if ($this->showStat == 't') {
			$sql .= "
						GROUP BY l.text, uh.id_unit
               			ORDER BY l.text";			
		} else {
			$sql .= "
						ORDER BY ah.date DESC";
		}
/*echo "<pre>";
echo $sql;
echo "<pre><br><br>";*/
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

/*echo "<pre>";
echo print_r($res);
echo "<pre><br><br>";*/
		if (empty($res)) {
			echo '<font color="Red">NO DATA</font>';
			return;
		}

		if ($this->showStat == 't') {
			echo '
			<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
							<tr class=th_2 >
								<th>
									Unit Name
								</th>
								<th>
									Amount
								</th>
								<th>
									Weight
								</th>
							</tr>';
			$total = 0;
			$unitCount = 0;
			$totalWeight = 0;
			foreach ($res as $key => $data)
			{
				// Вес юнита
				$sql = "SELECT
			                SUM(uha.value*res.exchangerate)/10 as weight
			            FROM
			                ds_unit u                    
			            INNER JOIN ds_unithasattribute uha ON (u.id_unit = uha.id_unit)
			            INNER JOIN ds_attribute atr ON (uha.id_attribute = atr.id_attribute)
						INNER JOIN ds_attributename atn ON (atr.id_attributename = atn.id_attributename)
						INNER JOIN ds_attributetype att ON (atr.id_attributetype = att.id_attributetype)
						LEFT JOIN ds_resource res ON (atn.id_attributename = res.id_attributename)			
			    		WHERE
							u.id_unit = ".$data['id_unit']."
			                    	AND 
							att.typename = 'PRODUCTION_COSTS'";
/*echo "<pre>";
echo $sql;
echo "<pre><br><br>";	*/			
				$runitWeigth = $common->getResultArray("", $sql, "", "", "");
				assert(empty($common->errormsg));

				echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.$data['text'].'
								</td>
								<td align="center" class=maintxt_med bgcolor=#444E6C>
									'.$data['amount'].'
								</td>
								<td align="right" class=maintxt_med bgcolor=#444E6C>
									'.number_format($runitWeigth[0]['weight']*$data['amount'], 0, ",", " ").'
								</td>
							</tr>';
				$total ++;
				$unitCount += $data['amount'];
				$totalWeight += $runitWeigth[0]['weight']*$data['amount'];
			}
			echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#2A3E66>
									Total:
								</td>
								<td align="center" class=maintxt_med bgcolor=#2A3E66>
									'.$unitCount.'
								</td>
								<td align="right" class=maintxt_med bgcolor=#2A3E66>
									'.number_format($totalWeight, 0, ",", " ").'
								</td>
							</tr>
						</table>
					</td>
				</tr>	
			</table>
			';
		} else {
			echo '
			<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
							<tr class=th_2 >
								<th>
									<!-- <a href="?action=showStat&service=&show=all&sortBy=user" >User</a>-->
									User
								</th>
								<th>
									Date
								</th>
								<th>
									Price
								</th>
								<!-- <th>
									Weight
								</th> -->
								<th>
									&nbsp;
								</th>
							</tr>';
			$total = 0;
			$unitCount = 0;
			$totalWeight = 0;
			$sil = 0;
			foreach ($res as $key => $data)
			{
				if (strpos($data['total_price'], 'SIL')) {
					$silArr = explode(" ", $data['total_price']);
					$sil += $silArr[0];
				}
				
				echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.$data['username'].'
								</td>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.$data['date'].'
								</td>
								<td align="center" class=maintxt_med bgcolor=#444E6C>
									'.$data['total_price'].'
								</td>
								<!-- <td align="right" class=maintxt_med bgcolor=#444E6C>
									'.number_format($data['army_weight'], 0, ",", " ").'
								</td> -->
								<td align="right" class=maintxt_med bgcolor=#444E6C>
									<a href="index.php?action=showOperationStat&showStat=f&id_op='.$data['id_army_reincarnation_hystory'].'&world='.$this->dbId.'_'.$this->worldId.'" target="_blank">Show detail</a>
								</td>
							</tr>';
				$total ++;
				$unitCount += $data['amount'];
				$totalWeight += $data['army_weight'];
			}
			echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#2A3E66>
									Total:
								</td>
								<td align="center" class=maintxt_med bgcolor=#2A3E66>
									'.$total.' operations
								</td>
								<td align="center" class=maintxt_med bgcolor=#2A3E66>';
			if ($sil > 0) {
				echo '				
									'.number_format($sil, 0, ",", " ").' SC
				';
				
			} else {
				echo '				
									&nbsp;
				';
			}
				echo 					
								'
								</td>
								<!-- <td align="right" class=maintxt_med bgcolor=#2A3E66>
									'.number_format($totalWeight, 0, ",", " ").'
								</td> -->
								<td align="center" class=maintxt_med bgcolor=#2A3E66>
									&nbsp;
								</td>
							</tr>
						</table>
					</td>
				</tr>	
			</table>
			';
		
		}

		return;
	}

	private function GetTimeMenu($year, $month, $day, $direction)
	{
		assert($year > 0);
		assert(0 < $month && $month < 13);
		assert(0 < $day && $day < 32);
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
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select><font color=#F8F8FF> - </font>';

		//Выводим месяц
		$out .= '<select name="'.$direction.'_m">';
		for($i=1; $i<13; $i++)
		{
			if($i == $month)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select><font color=#F8F8FF> - </font>';

		//Выводим число
		$out .= '<select name="'.$direction.'_d">';
		for($i=1; $i<32; $i++)
		{
			if($i == $day)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select>';

		return $out;
	}
	
	private function _showOperationStat()
	{
		$common = DS_Common::CreateForCurentDB($this->dbId);
		
		if ((int)$this->id_reincarnateUnit == 0) {
			ShowCMSMessage("Wrong Data", 2);
			return;
		}

		$sql = "
				SELECT
                    us.username, 
                    l.text, 
                    uh.amount, 
                    uh.price,
                    ah.total_price
                FROM 
                    ds_army_reincarnation_hystory ah
                LEFT JOIN ds_army_reincarnation_unit_hystory uh ON (ah.id_army_reincarnation_hystory = uh.id_army_reincarnation_hystory)
                LEFT JOIN ds_unit u ON (uh.id_unit = u.id_unit)
                LEFT JOIN ds_unithasclass uc ON (u.id_unit = uc.id_unit)
                LEFT JOIN ds_unitcanbebuildbyrace ur ON (u.id_unit = ur.id_unit)
                LEFT JOIN ds_userinstance ui ON (ah.id_userinstance = ui.id_userinstance)
                LEFT JOIN ds_user us ON (ui.id_user = us.id_user)
                LEFT JOIN ds_localizedtextlanguage l ON (u.id_localizedname = l.id_localizedtext AND l.id_language = 3)
                WHERE
                           ah.id_army_reincarnation_hystory = ".$this->id_reincarnateUnit;

/*echo "<pre>";
echo $sql;
echo "<pre><br><br>";*/	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		$total = 0;
		$unitCount = 0;
		
		echo '
			<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
							<tr class=th_2 >
								<th>
									User Name
								</th>
								<th>
									Amount
								</th>
								<th>
									Unit Name
								</th>
								<th>
									Unit Price
								</th>
								<th>
									SUM
								</th>
							</tr>';

			foreach ($res as $key => $data)
			{
				$sum = "";
				$priceArr = explode(";", $data['price']);
				foreach ($priceArr as $priceKey => $priceData) {
					if (empty($priceData)) {
						continue;
					}
					$temp = explode(" ", $priceData);
					
					$sum .= $temp[0]*$data['amount']." ".$temp[1].";";
				}
				
				echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.$data['username'].'
								</td>
								<td align="center" class=maintxt_med bgcolor=#444E6C>
									'.$data['amount'].'
								</td>
								<td align="center" class=maintxt_med bgcolor=#444E6C>
									'.$data['text'].'
								</td>
								<td align="center" class=maintxt_med bgcolor=#444E6C>
									'.$data['price'].'
								</td>
								<td align="right" class=maintxt_med bgcolor=#444E6C>
									'.$sum.'
								</td>
							</tr>';
				$total ++;
				$unitCount += $data['amount'];
			}
			echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#2A3E66>
									Total:
								</td>
								<td align="center" class=maintxt_med bgcolor=#2A3E66>
									'.$unitCount.'
								</td>
								<td align="right" class=maintxt_med bgcolor=#2A3E66>
									&nbsp;
								</td>
								<td align="right" class=maintxt_med bgcolor=#2A3E66>
									&nbsp;
								</td>
								<td align="right" class=maintxt_med bgcolor=#2A3E66>
									'.$data['total_price'].'
								</td>
							</tr>
						</table>
					</td>
				</tr>	
			</table>
			';

		return;
	}

}

$s = new UnitReincarnationStat($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>