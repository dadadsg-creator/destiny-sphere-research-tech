<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

$obj = new BattleLosses();

class BattleLosses
{ 
	var $errormsg = '';
	
	function BattleLosses()
	{
		$this->getPostData($_POST);
	}
	
	function getPostData($data)
	{
		isset($data['username'])? $username = $data['username']:$username='';
		isset($data['dbw'])? $dbw = $data['dbw']:$dbw = '';
		
		isset($data['fs'])? $fs = $data['fs']:$fs = date("s");
		isset($data['fmi'])? $fmi = $data['fmi']:$fmi= date("i");
		isset($data['fh'])? $fh = $data['fh']:$fh = date("H");
		
		isset($data['fd'])? $fd = $data['fd']:$fd = date("d");
		isset($data['fm'])? $fm = $data['fm']:$fm = date("m");
		isset($data['fy'])? $fy = $data['fy']:$fy = date("y");
		
		
		isset($data['ts'])? $ts = $data['ts']:$ts = date("s");
		isset($data['tmi'])? $tmi = $data['tmi']:$tmi= date("i");
		isset($data['th'])? $th = $data['th']:$th = date("H");
				
		isset($data['td'])? $td = $data['td']:$td = date("d");
		isset($data['tm'])? $tm = $data['tm']:$tm = date("m");
		isset($data['ty'])? $ty = $data['ty']:$ty = date("y");
		
		$this->showRequestForm($username,$dbw,
								$fs,$fmi,$fh,$fd,$fm,$fy,
								$ts,$tmi,$th,$td,$tm,$ty);
								
		if(isset($data['dbw']) && isset($data['username']) && isset($data['fs']))
		{	
		}
	}
	
	function showLoses($data)
	{
		list($dbid,$world_id) = explode('-',$data['dbw']);
		$conn = $this->getDBConnection();
		$conn = $this->setDBId($dbid);	
		
		$sql = sprintf("SELECT
      							b.battlestartdate,
      							b.battleenddate,
      							br.roundnumber,
      							br.roundstarttime,
      							br.id_battleround,
      							br.roundendtime,
      							ltl.text as unitname,
      							bres.amount,
      							b.id_battle,
      							un.id_unit,
								(
 								SELECT
       								u2.username
   								FROM
       								ds_user u2,
       								ds_userinstance ui2,
       								ds_battleparticipant bp2
   								WHERE
        							bp2.id_battle = b.id_battle
        							AND bp2.id_userinstance != bp.id_userinstance
        							AND bp2.id_userinstance = ui2.id_userinstance
        							AND ui2.id_user = u2.id_user
								) as second_member,
     							cn.combname
							FROM
    							ds_battleresult bres,
    							ds_battleround br,
    							ds_battle b,
    							ds_battleparticipant bp,
    							ds_userinstance ui,
    							ds_user u,
    							ds_unit un,
    							ds_localizedtextlanguage ltl,
    							ds_comb c,
    							ds_combname cn
						WHERE
     						u.username = '%s'
     						and u.id_user = ui.id_user
     						and ui.id_world = %d
     						and ui.id_userinstance = bp.id_userinstance
     						and bp.id_battle = b.id_battle
     						and b.battlestartdate > to_timestamp('%d-%d-%d %d:%d:%d',
     												 'YYYY-MM-DD HH24:MI:SS')
     						and b.battleenddate < to_timestamp('%d-%d-%d %d:%d:%d', 
     												'YYYY-MM-DD HH24:MI:SS')
     						and b.id_battle = br.id_battle
     						and br.id_battleround = bres.id_battleround
     						and bres.id_battleparticipant = bp.id_battleparticipant
     						and bres.id_unit = un.id_unit
     						and un.id_localizedname = ltl.id_localizedtext
     						and ltl.id_language = 3
     						and b.id_battlecomb = c.id_comb
     						and c.id_comb = cn.id_comb
					order by b.id_battle,br.id_battleround",
					$data['username'],
					$world_id,
					$data['fy'],
					$data['fm'],
					$data['fd'],
					$data['fh'],
					$data['fmi'],
					$data['fs'],
					$data['ty'],
					$data['tm'],
					$data['td'],
					$data['th'],
					$data['tmi'],
					$data['ts']);
					
		$res = $conn->getResultArray ( "",$sql, "", "", "" );						
		if($conn->errormsg)
		{
			$this->err_msg = "can't get user loses";		
			return false;	
		}	
		
		if($conn->errormsg)
		{
			$this->err_msg = "can't get user loses";		
			return false;	
		}	
		if(sizeof($res) === 0)
		{
			$this->outputMessage("User has no battles for this period");		
			return true;	
		}	

		$battle_loses = array();
		$all_loses = array();
		$current_round = $res[0]['id_battleround'];
		$current_battle = $res[0]['id_battle'];
		for($i=0; $i<sizeof($res); $i++)
		{
			if($current_round != (int)$res[0]['id_battleround'])
			{
				$current_round = (int)$res[0]['id_battleround'];
			}
		}		
		
	}
	
	function outputMessage($str)
	{
		echo ShowCMSMessage($str);
		return true;
	}
	
	function getWorldsSelect($dbw)
	{
		$conn = $this->getDBConnection();
		$str = "<SELECT name='dbw'>";
		$sql = "SELECT worldname, id_world from ds_world";
		$res = $conn->getMultyArray("", $sql,"","","");	
		if($conn->errormsg || (sizeof($res) === 0))
		{
			$this->outputResult("can't get list of worlds");			
		}
		
		foreach ($res as $k=>$v)		
		{
			if(strcmp($dbw,$v['dbId'].'-'.$v['id_world']) === 0)
			{
				$str.= "<OPTION selected value='".
						$v['dbId'].'-'.$v['id_world'].
						"'>".$v['worldname']."</OPTION>";
			}
			else 
			{
				$str.= "<OPTION value='".
						$v['dbId'].'-'.$v['id_world'].
						"'>".$v['worldname']."</OPTION>";
			}
		}
		
		$str.="</SELECT>";
		return $str;
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
	
	function showTimePanel($fs,$fmi,$fh,$fd,$fm,$fy,
							$ts,$tmi,$th,$td,$tm,$ty)
	{
		$str = "Бои с: <select name='fd'> ";
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
		for($i=1;$i<=23;$i++)
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
		for($i=1;$i<=59;$i++)
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
		for($i=1;$i<=59;$i++)
		{
			$selected = '';			
			if((int)$fs == $i)
			{
				$selected = 'SELECTED';
			}
			$str.= "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		
		
		$str .= "</select>
			  &nbsp &nbsp 	По: <select name='td'>";
		
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
		for($i=1;$i<=23;$i++)
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
		for($i=1;$i<=59;$i++)
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
		for($i=1;$i<=59;$i++)
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
	
	function showRequestForm($username,$dbw,
								$fs,$fmi,$fh,$fd,$fm,$fy,
								$ts,$tmi,$th,$td,$tm,$ty)
	{
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 width='330'>
							".local( 'DS_USER_USERNAME' ).":
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='330'>
							".local('ADMIN_WORLD_NAME')."
							</TD>
						</TR>
						
						<TR>
							
								<TD bgcolor='#444E6C' class=maintxt width='330'>
								<input name = 'username' type ='text'
								value='".$username."'> 
								</TD>
								<TD bgcolor='#444E6C' class=maintxt width='330'>
								".
									$this->getWorldsSelect($dbw)
								."</TD>
							
						</TR>
						<TR>
							
								<TD bgcolor='#444E6C' 
								class=maintxt width='330' colspan='2' align='center'>".
								$this->showTimePanel($fs,$fmi,$fh,$fd,$fm,$fy,
													$ts,$tmi,$th,$td,$tm,$ty)
								."</TD>
							
						</TR>
						<TR>
							<TD bgcolor='#444E6C' align='right' class=maintxt colspan='2'>
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
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>