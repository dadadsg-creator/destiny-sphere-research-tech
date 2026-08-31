<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=PremiumLog><b>HELP</b></a></p>';

$obj = new PremiumOnViewer();

class PremiumOnViewer
{
	var $errmsg = '';
	var $common = false;
	
	function PremiumOnViewer()
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
		
		isset($data['actor'])? $actor = $data['actor']:$actor = '';
		isset($data['username'])? $username = $data['username']:$username = '';
				
		$this->showRequestForm($fs,$fmi,$fh,$fd,$fm,$fy,
								$ts,$tmi,$th,$td,$tm,$ty,
								$actor,
								$username);
								
		if(isset($data['actor']) || isset($data['username']))
		{
			$this->showOns($actor,$username,
							$fs,$fmi,$fh,$fd,$fm,$fy,
								$ts,$tmi,$th,$td,$tm,$ty);
		}
		
	}
	
	function showOns($actor,$username,
						$fs,$fmi,$fh,$fd,$fm,$fy,
								$ts,$tmi,$th,$td,$tm,$ty)
	{
		$from = $fy.'-'.$fm.'-'.$fd.' '.$fh.':'.$fmi.':'.$fs;
		$to = $ty.'-'.$tm.'-'.$td.' '.$th.':'.$tmi.':'.$ts; 
		
		$conn = $this->getDBConnection();
		
		$sql = "SELECT								
						u.username,
						pl.*
					FROM
						ds_premium_change_log as pl
					LEFT JOIN ds_user as u ON (u.id_user = pl.id_user)
					WHERE
						 pl.date < to_timestamp('".$to."','YYYY-MM-DD HH24:MI:SS')
						AND
						 pl.date > to_timestamp('".$from."','YYYY-MM-DD HH24:MI:SS')
				";	
		if($actor != '' && strcmp($actor,'all') != 0)
		{
			$sql .= " AND pl.type = '".$actor."' ";
		}
		
		if($username != '')
		{
			$sql .= " AND u.username = '".$username."' ";
		}
		
		$sql .= ' ORDER BY
						 pl.date';
/*echo "<pre>";
echo $sql;
exit;*/
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
							Who was change:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Date:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Username:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Duration:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							New end date:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Comments:
							</TD>
						</TR>";
		
		foreach ($res as $k=>$v)
		{
			echo			"<TR>							
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['type']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['date']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['username']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['days_delta']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['end_date_new']."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								".$v['comments']."
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
			$this->common = &ClassFactory::getInstance("DS_Common");
			if (!$this->common->connect()) 
			{
				$this->outputResult('DB connection failed');
				exit();
			}	
		}
		
		return $this->common;		
	}
	
	function showRequestForm($fs,$fmi,$fh,$fd,$fm,$fy,
								$ts,$tmi,$th,$td,$tm,$ty,$actor,$username)
	{
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Who was change:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Username:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Time period:
							</TD>
						</TR>
						
						<TR>
							
								<TD bgcolor='#444E6C' class=maintxt>
								".$this->showActors($actor)."
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								<input type='text' name='username' value='".$username."'>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt align='right'>
								".
									$this->showTimePanel($fs,$fmi,$fh,$fd,$fm,$fy,
															$ts,$tmi,$th,$td,$tm,$ty)
								."</TD>
							
						</TR>
						<TR>
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
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>