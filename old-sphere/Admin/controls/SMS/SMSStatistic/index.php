<?
require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right">
		<a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Messages>
			<font size=3>
				<b>
					HELP
				</b>
			</font>
		</a>
	</p>';

$obj = new Messages();

class Messages
{
	var $errmsg = '';
	var $common = false;

	function Messages()
	{
		if(!$this->getPostData($_POST))
		{
			echo $this->errmsg;
		}
	}

	function getPostData($data)
	{
		$week = strtotime("-1 week");

		isset($data['fs'])? $fs = $data['fs']:$fs = date("s", $week);
		isset($data['fmi'])? $fmi = $data['fmi']:$fmi= date("i", $week);
		isset($data['fh'])? $fh = $data['fh']:$fh = date("H", $week);

		isset($data['fd'])? $fd = $data['fd']:$fd = date("d", $week);
		isset($data['fm'])? $fm = $data['fm']:$fm = date("m", $week);
		isset($data['fy'])? $fy = $data['fy']:$fy = date("Y", $week);

		isset($data['ts'])? $ts = $data['ts']:$ts = date("s");
		isset($data['tmi'])? $tmi = $data['tmi']:$tmi= date("i");
		isset($data['th'])? $th = $data['th']:$th = date("H");

		isset($data['td'])? $td = $data['td']:$td = date("d");
		isset($data['tm'])? $tm = $data['tm']:$tm = date("m");
		isset($data['ty'])? $ty = $data['ty']:$ty = date("Y");

		isset($data['shortNumber'])? $shortNumber = $data['shortNumber']:$shortNumber = '';

		isset($data['provider'])? $provider = $data['provider']:$provider = '';
		isset($data['type'])? $type = $data['type']:$type = '';
		isset($data['groupBy'])? $groupBy = $data['groupBy']:$groupBy = 'provider';

		$this->showRequestForm($fs,$fmi,$fh,$fd,$fm,$fy,
		$ts,$tmi,$th,$td,$tm,$ty,
		$provider,
		$shortNumber,
		$type,
		$groupBy
		);

		if(isset($data['showMessages']))
		{
			$this->showMessages(
			$provider,
			$shortNumber,
			$type,
			$groupBy,
			$fs,$fmi,$fh,$fd,$fm,$fy,
			$ts,$tmi,$th,$td,$tm,$ty);
		}

	}

	function showMessages(
	$provider,
	$shortNumber,
	$type,
	$groupBy,
	$fs,$fmi,$fh,$fd,$fm,$fy,
	$ts,$tmi,$th,$td,$tm,$ty)
	{
		$from = $fy.'-'.$fm.'-'.$fd.' '.$fh.':'.$fmi.':'.$fs;
		$to = $ty.'-'.$tm.'-'.$td.' '.$th.':'.$tmi.':'.$ts;

		$conn = $this->getDBConnection();



		$sql = "SELECT count(*),";

		if ($groupBy == 'provider')
		{
			$sql .= ' t.id_provider ';

			$sql .= ',
						t.short_code '; 
		} elseif ($groupBy == 'type')
		{
			$sql .= 't.type  ';
		} else {
			echo "<font color=red>Error: Choose Group By!!!!!</font>";
			exit;
		}



		$sql .= " FROM (SELECT

		";
		if($groupBy == 'type')
		{

			$sql .= ' CASE ';

			$messageTypes = array(  'PREMIUM',
			'ART',
			'ARMY',
			'ARCH',
			'AINF',
			'CREDIT',
			'BEAUTY');

			foreach ($messageTypes as $k => $messageType)
			{
				$typeInLowerCase = strtolower($messageType);


				$sql .= "
			 			 WHEN
			 			 sl.message like '%$messageType%'
                        OR
                         sl.message like '%$typeInLowerCase%'
					    THEN
						 	'$messageType'
					";

			}

			$sql .= "
                        ELSE
					       'INDEFINITE'
					    END AS type,
					    sl.id_provider,
					    sl.short_code
							";

		}

		if ($groupBy == 'provider') {
			$sql .= ' sl.id_provider,
					  sl.short_code ';
		}

		$sql .= "
					FROM
					    ds_sms_log as sl
					WHERE
						 sl.date < to_timestamp('".$to."','YYYY-MM-DD HH24:MI:SS')
						AND
						 sl.date > to_timestamp('".$from."','YYYY-MM-DD HH24:MI:SS')";

		if($type != 'ALL')
		{
			if($type == '')
			{
				echo "<font color=red>Error: Type not selected!</font>";
				exit;
			}

			$typeInLowerCase = strtolower($type);

			$sql .= " AND ( sl.message like '%$type%'
							OR
							sl.message like '%$typeInLowerCase%') 
									";
		}

		if($provider != 'ALL')
		{
			if($provider == '')
			{
				echo "<font color=red>Error: Provider not selected!</font>";
				exit;
			}

			if ($provider == 'smxcom') {
				$id_provider = 0;
			}
			if ($provider == 'iricom') {
				$id_provider = 1;
			}
			$sql .= " AND sl.id_provider = '$id_provider'
					";
		}

		if($shortNumber != 'ALL')
		{
			$sql .= " AND sl.short_code = '$shortNumber'
					";
		}

		$sql .= '  ) as t
						 GROUP BY ';

		if ($groupBy == 'provider')
		{
			$sql .= ' t.id_provider,
						t.short_code '; 
		} elseif ($groupBy == 'type')
		{
			$sql .= 't.type  ';
		} else {
			echo "<font color=red>Error: Choose Group By!!!!!</font>";
			exit;
		}

		$res = $conn->getResultArray ( "",$sql, "", "", "" );

		if($conn->errormsg)
		{
			echo "can't get statistic <br>".$sql;
			return false;
		}

		if ($groupBy == 'provider')
		{
			foreach ($res as $k => $arr)
			{
				$stat[$arr['id_provider']][] = $arr;
			}
		}

		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							$groupBy
							</TD>";
		if ($groupBy == 'provider')
		{
			echo "<TD bgcolor='444E6C' class=th_2 align='center'>
							Short Number
							</TD>";
		}

		echo "<TD bgcolor='444E6C' class=th_2 align='center'>
							Count:
							</TD>
						</TR>";

		$count = 0;

		if ($groupBy == 'type')
		{
			foreach ($res as $k=>$v)
			{
				echo			"<TR>";


				echo "<TD bgcolor='#444E6C' class=maintxt>
								".$v['type']."
					</TD>";

				echo "<TD bgcolor='#444E6C' class=maintxt>
								".$v['count']."
								</TD>
						</TR>";
				$count += $v['count'];
			}
			//¬ыводим сумму
			echo			"<TR bgcolor='#2A3E66'>
								<TD class=maintxt>
								<b>
								TOTAL:
								</b>
								</TD>
								<TD class=maintxt>
								<b>
								$count
								</b>
								</TD>
						</TR>";
		}

		if ($groupBy == 'provider')
		{
			$totalCount = 0;
			foreach ($stat as $k=>$v)
			{
				echo			"<TR>";

				if ($k == 0) {
					$prov = 'smxcom';
				} elseif ($k == 1) {
					$prov = 'iricom';
				} else {
					$prov = 'uncnown';
				}

				echo "<TD rowspan=".count($v)." bgcolor='#444E6C' class=maintxt>
								".$prov."
								</TD>";
				$tr = '';
				$count = 0;

				foreach ($v as $k2 => $v2)
				{
					echo "$tr <TD bgcolor='#444E6C' class=maintxt>
								".$v2['short_code']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v2['count']."
								</TD>";

					$tr = "</TR>";
					$count += $v2['count'];
					$totalCount += $v2['count'];
				}
				//¬ыводим сумму
				echo			"<TR bgcolor='#2A3E66'>
								<TD class=maintxt colspan=2>
								Sum:
								</TD>
								<TD class=maintxt>
								$count
								</TD>
						</TR>";

				echo			"</TR>";


			}
			//¬ыводим сумму
			echo			"<TR bgcolor='#2A3E66'>
								<TD class=maintxt colspan=2>
								<b>
								TOTAL:
								</b>
								</TD>
								<TD class=maintxt>
								<b>
								$totalCount
								</b>
								</TD>
						</TR>";
		}



		echo 			"</TABLE>
					</td>
				</tr>
				
				</table>";

		return true;
	}

	function getDBConnection()
	{
		if(!$this->common)
		{
			$this->common = DS_Common::CreateForCurentDB(0);
		}

		return $this->common;
	}

	function showRequestForm($fs,$fmi,$fh,$fd,$fm,$fy,
	$ts,$tmi,$th,$td,$tm,$ty,
	$provider,
	$shortNumber,
	$type,
	$groupBy
	)
	{
		$messageTypes = array(  'ALL',
		'PREMIUM',
		'ART',
		'ARMY',
		'ARCH',
		'AINF',
		'CREDIT',
		'BEAUTY');
		$providers = array('ALL' => 'ALL', 'smxcom' => '0', 'iricom' => '1');

		$groupByArr = array('provider', 'type');

		foreach ($providers as $name => $id)
		{
			$shotNumbers[$name] = $this->getShotNumbers($id);
		}

		echo '<script type="text/javascript">
			<!--
		
			//массив коротих номеров
			var ashortNumberValues = new Array(';

		$comma = '';
		foreach ($shotNumbers as $name => $numbers)
		{
			$numbersString = $comma_separated = implode(",", $numbers);
			echo $comma.'
			"'.$numbersString.'"';
			$comma = ',';
		}

		echo '
		);
			
			// ф-ци€, возвращающа€ массив домов по заданной улице
			function getshortNumberValuesByprovider(index){
			    var sshortNumberValues = ashortNumberValues[index];
			    return sshortNumberValues.split(","); // преобразуем строку в массив домов
			}
			
			// главна€ ф-ци€, вывод€ща€ динамически список домов
			function MkshortNumberValues(index){
			    var aCurrshortNumberValues = getshortNumberValuesByprovider(index);
			    var nCurrshortNumberValuesCnt = aCurrshortNumberValues.length;
			    var oshortNumberList = document.forms["stat"].elements["shortNumber"];
			    var oshortNumberListOptionsCnt = oshortNumberList.options.length;
			    oshortNumberList.length = 0; // удал€ем все элементы из списка домов
			    for (i = 0; i < nCurrshortNumberValuesCnt; i++){
			        // далее мы добавл€ем необходимые дома в список
			        if (document.createElement){
			            var newshortNumberListOption = document.createElement("OPTION");
			            newshortNumberListOption.text = aCurrshortNumberValues[i];
			            newshortNumberListOption.value = aCurrshortNumberValues[i];
			            // тут мы используем дл€ добавлени€ элемента либо метод IE, либо DOM, которые, alas, не совпадают по параметрамЕ
			            (oshortNumberList.options.add) ? oshortNumberList.options.add(newshortNumberListOption) : oshortNumberList.add(newshortNumberListOption, null);
			        }else{
			            // дл€ NN3.x-4.x
			            oshortNumberList.options[i] = new Option(aCurrshortNumberValues[i], aCurrshortNumberValues[i], false, false);
			        }
			    }
			}
			
			// инициируем изменение элементов в списке домов, в зависимости от текущей улицы
			MkshortNumberValues(document.forms["stat"].elements["provider"].selectedIndex);
			//-->
			</script>';

		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form  name='stat' method='post' action='".$_SERVER["PHP_SELF"]."'>
				<input type='hidden' name='showMessages'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>							
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Type
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Provider
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Short Number
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Time period:
							</TD>
						</TR>
						<TR>															
								<TD bgcolor='444E6C' align='center'>
								<select name=type>";
		foreach ($messageTypes as $key => $typeName)
		{
			if ($type == $typeName) {
				$s = 'selected';
			}else{
				$s = '';
			}
			echo "<option value=$typeName $s>$typeName</option>";
		}

		echo '					</select>
								</TD>
								<TD bgcolor="#444E6C" class=maintxt>
								<select name="provider" onChange="MkshortNumberValues(this.selectedIndex)">';
		foreach ($providers as $providerName => $key)
		{
			if ($provider == $providerName) {
				$s = 'selected';
			}else{
				$s = '';
			}
			echo '
					<option value="'.$providerName.'" '.$s.'>'.$providerName.'</option>';

		}

		echo '
					</select>
					</TD>
					<TD bgcolor="#444E6C" class=maintxt>
					<select name="shortNumber">';

		if ($provider == '')
		{
			$provider = 'ALL';
		}

		foreach ($shotNumbers[$provider] as $k => $v)
		{

			if ($shortNumber == $v) {
				$s = 'selected';
			}else{
				$s = '';
			}

			echo '<option value="'.$v.'" '.$s.'>'.$v.'</option>';
		}


		echo '	</select>';

		echo "					</TD>
								<TD bgcolor='#444E6C' class=maintxt align='right'>
								".
		$this->showTimePanel($fs,$fmi,$fh,$fd,$fm,$fy,
		$ts,$tmi,$th,$td,$tm,$ty)
		."</TD>
							
						</TR>
						<TR>
							<TD bgcolor='#444E6C' align='left' class=maintxt colspan='3'>";

		foreach ($groupByArr as $key => $by)
		{
			if ($by == $groupBy) {
				$c = 'checked';
			}else{
				$c = '';
			}
			echo "<input type=radio name=groupBy value=$by $c>Group by $by<br>";
		}


		echo "
							</TD>
							<TD bgcolor='#444E6C' align='right' class=maintxt>
								<input type='submit'  class='Submit' name='search' 
								value='".local( 'DS_ACTION_SEARCH' )."'>
							</TD>
						</TR>
						</TABLE>
					</td>
				</tr>
				</form>
				</table>";


		return true;
	}

	function showTimePanel($fs,$fmi,$fh,$fd,$fm,$fy,
	$ts,$tmi,$th,$td,$tm,$ty)
	{
		$str = "Since: <select name='fd'> ";
		for($i=1; $i<=31; $i++)
		{
			$selected = '';
			if((int)$fd == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";

		}
		$str .= "</select>
					<select name='fm'>";

		for($i=1;$i<=12;$i++)
		{
			$selected = '';
			if((int)$fm == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='fy'>";

		for ($i=2005;$i<=date('Y');$i++ )
		{
			$selected = '';
			if((int)$fy == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='fh'>";
		for($i=00;$i<=23;$i++)
		{
			$selected = '';
			if((int)$fh == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='fmi'>";
		for($i=00;$i<=59;$i++)
		{
			$selected = '';
			if((int)$fmi == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='fs'>";
		for($i=00;$i<=59;$i++)
		{
			$selected = '';
			if((int)$fs == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}


		$str .= "</select>
				<br>To: <select name='td'>";

		for($i=1; $i<=31; $i++)
		{
			$selected = '';
			if((int)$td == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='tm'>";		
		for($i=1;$i<=12;$i++)
		{
			$selected = '';
			if((int)$tm == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='ty'>";	

		for ($i=2005;$i<=date('Y');$i++ )
		{
			$selected = '';
			if((int)$ty == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='th'>";
		for($i=00;$i<=23;$i++)
		{
			$selected = '';
			if((int)$th == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='tmi'>";
		for($i=00;$i<=59;$i++)
		{
			$selected = '';
			if((int)$tmi == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		$str .= "</select>
					<select name='ts'>";
		for($i=00;$i<=59;$i++)
		{
			$selected = '';
			if((int)$ts == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}

		return $str;
	}

	function showActors($actor)
	{
		$conn = $this->getDBConnection();

		$sql = "SELECT
					distinct type
				FROM
					ds_premium_change_log";

		$res = $conn->getResultArray ( "",$sql, "", "", "" );
		if($conn->errormsg)
		{
			echo "can't get actors <br>".$sql;
			return false;
		}

		$output_str = "<select name='actor'>
						<option value='all'> All </option>";

		foreach ($res as $k=>$v)
		{
			$selected = '';
			if(strcmp($actor,$v['type']) === 0)
			{
				$selected = 'selected';
			}
			$output_str .= "<option ".$selected." value='".$v['type']."' >".
			$v['type'].
			"</option>";
		}
		$output_str .= "</select>";
		return $output_str;
	}

	function getType($message)
	{
		$messageArr   = str_word_count($message, 1);

		$messageTypes = array(  'PREMIUM',
		'ART',
		'ARMY',
		'ARCH',
		'AINF',
		'CREDIT',
		'BEAUTY');

		$messagePrefix = array('DS', 'SD');
		$messagePrefixU = array('DSU', 'SDU');

		foreach ($messageTypes as $k2 => $type)
		{

			foreach ($messagePrefix as $k => $prefix)
			{
				if ( (strcasecmp($prefix, $messageArr[0]) == 0 && strcasecmp($type, $messageArr[1]) == 0) ||
				strcasecmp($prefix.$type, $messageArr[0]) == 0 ||
				strcasecmp($prefix.'_'.$type, $messageArr[0]) == 0)
				{
					return $type;
				}
			}

			foreach ($messagePrefixU as $k => $prefixU)
			{
				if ( ( strcasecmp($prefixU, $messageArr[0]) == 0 && strcasecmp($type, $messageArr[1]) == 0)  ||
				strcasecmp($prefixU.$type, $messageArr[0]) == 0 ||
				strcasecmp($prefixU.'_'.$type, $messageArr[0]) == 0)
				{
					return 'U '.$type;
				}
			}

		}

		return 'INDEFINITE ';
	}

	function getShotNumbers($id_provider)
	{
		assert($id_provider != '');

		$conn = $this->getDBConnection();

		$sql = "SELECT
					   DISTINCT(sl.short_code)
					FROM
					    ds_sms_log as sl
					   ";

		if ($id_provider != 'ALL')
		{
			$sql .= "
					WHERE
					    sl.id_provider = $id_provider";
		}

		$res = $conn->getResultArray ( "",$sql, "", "", "" );

		if($conn->errormsg)
		{
			echo "can't get shot numbers <br>".$sql;
			return false;
		}

		$shotNumbers[] = 'ALL';

		foreach ($res as $key => $data)
		{
			if ($data['short_code'] != '')
			{
				$shotNumbers[] = $data['short_code'];
			}
		}

		return $shotNumbers;
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>