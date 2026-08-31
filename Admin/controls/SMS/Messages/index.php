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

		isset($data['phone'])? $phone = $data['phone']:$phone = '';
		isset($data['shortNumber'])? $shortNumber = $data['shortNumber']:$shortNumber = '';
		isset($data['text'])? $text = $data['text']:$text = '';
		isset($data['onlyEmptyResultField'])? $onlyEmptyResultField = $data['onlyEmptyResultField']:$onlyEmptyResultField = '';

		$this->showRequestForm($fs,$fmi,$fh,$fd,$fm,$fy,
		$ts,$tmi,$th,$td,$tm,$ty,
		$phone,
		$shortNumber,
		$text,
		$onlyEmptyResultField);

		if(isset($data['showMessages']))
		{
			$this->showMessages($phone,
			$shortNumber,
			$text,
			$onlyEmptyResultField,
			$fs,$fmi,$fh,$fd,$fm,$fy,
			$ts,$tmi,$th,$td,$tm,$ty);
		}

	}

	function showMessages($phone,
	$shortNumber,
	$text,
	$onlyEmptyResultField,
	$fs,$fmi,$fh,$fd,$fm,$fy,
	$ts,$tmi,$th,$td,$tm,$ty)
	{
		$from = $fy.'-'.$fm.'-'.$fd.' '.$fh.':'.$fmi.':'.$fs;
		$to = $ty.'-'.$tm.'-'.$td.' '.$th.':'.$tmi.':'.$ts;

		$conn = $this->getDBConnection();

		$sql = "SELECT
					    sl.phone,
					    sl.date,
					    sl.short_code,
					    sl.message,
					    sl.result
					FROM
					    ds_sms_log as sl
					WHERE
						 sl.date < to_timestamp('".$to."','YYYY-MM-DD HH24:MI:SS')
						AND
						 sl.date > to_timestamp('".$from."','YYYY-MM-DD HH24:MI:SS')";

		if($phone != '')
		{
			$sql .= "AND sl.phone like '%$phone%'
					";
		}

		if($shortNumber != '')
		{
			$sql .= " AND sl.short_code like '%$shortNumber%'
					";
		}

		if($text != '')
		{
			$sql .= " AND sl.message like '%$text%'
					";
		}

		if($onlyEmptyResultField != '')
		{
			$sql .= " AND sl.result = ''
					";
		}

		$sql .= ' ORDER BY
						 sl.date DESC';

		$res = $conn->getResultArray ( "",$sql, "", "", "" );

		if($conn->errormsg)
		{
			echo "can't get statistic <br>".$sql;
			return false;
		}

		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Phone:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Date:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Short number:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Type:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Text:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Result:
							</TD>
						</TR>";

		foreach ($res as $k=>$v)
		{
		$type = $this->getType($v['message']);
		
			echo			"<TR>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['phone']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['date']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['short_code']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								$type
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['message']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['result']."
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
	$phone,
	$shortNumber,
	$text,
	$onlyEmptyResultField)
	{
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<input type='hidden' name='showMessages'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Phone:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Short number:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Text:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Time period:
							</TD>
						</TR>
						
						<TR>
							
								<TD bgcolor='#444E6C' class=maintxt>
								<input type='text'size=15 name='phone' value='".$phone."'>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								<input type='text' size=10 name='shortNumber' value='".$shortNumber."'>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								<input type='text' name='text' value='".$text."'>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt align='right'>
								".
		$this->showTimePanel($fs,$fmi,$fh,$fd,$fm,$fy,
		$ts,$tmi,$th,$td,$tm,$ty)
		."</TD>
							
						</TR>
						<TR>
							<TD bgcolor='#444E6C' align='left' class=maintxt colspan='3'>
								<input type='checkbox' name='onlyEmptyResultField' $onlyEmptyResultField value='checked'>Show messages only with empty result field
							</TD>
							<TD bgcolor='#444E6C' align='right' class=maintxt colspan='3'>
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
		for($i=0;$i<=23;$i++)
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
		for($i=0;$i<=59;$i++)
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
		for($i=0;$i<=59;$i++)
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
		for($i=0;$i<=23;$i++)
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
		for($i=0;$i<=59;$i++)
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
		for($i=0;$i<=59;$i++)
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

		// Если тип 500
		if (strpos($message, '500')) {
			$messageArr[1] = 500;
		}

		
		$messageTypes = array(  'PREMIUM',
		'ART',
		'ARMY',
		'ARCH',
		'AINF',
		'CREDIT',
		'500',
		'BEAUTY');
		
		
		$messagePrefix = array('DS', 'SD', 'DSR');
		$messagePrefixU = array('DSU', 'SDU', 'DSRU');

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
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>