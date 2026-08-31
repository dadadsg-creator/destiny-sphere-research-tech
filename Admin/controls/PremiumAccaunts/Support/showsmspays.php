<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

$obj = new ShowSMSPays();

class ShowSMSPays
{
	var $common;
	
	function ShowSMSPays()
	{
		if($this->getDBConnection())
		{
			$this->getData($_GET);
		}
	}
	
	function getDBConnection()
	{
		if(!$this->common)
		{
			$this->common = &ClassFactory::getInstance("DS_Common");
			if (!$this->common->connect()) 
			{
				$this->outputResult('DB connection failed');
				return false;
			}	
		}
		
		return $this->common;		
	}
	
	function getData($data)
	{
		if(!isset($data['id_user']))
		{
			echo 'lost data';
			return false;
		}
		
		$sql = sprintf("SELECT
								to_char (spl.paydate, 'YYYY-MM-DD HH24:MI:SS' ) as paydata,      							
      							spl.payresult,
      							spl.phonenumber,
      							spl.usersms
							FROM
    							ds_smspaylog spl
							WHERE
     							spl.id_user = %d
						ORDER BY
      							spl.paydate",
							$data['id_user']);
							
		$res = $this->common->getResultArray ( "", $sql, "", "", "" );
		
		if ($this->common->errormsg) 
		{			
			echo "ShowSMSPays error: can't get user data";
			return false;
		}	
		
		if(sizeof($res)>0)
		{
			echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>				
					<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 width='140'>
								Payment date
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140'>
								Result of payment
							</TD>		
							<TD bgcolor='444E6C' class=th_2 width='140'>
								User's phone
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140'>
								Text of sms
							</TD>					
						</TR>";
			foreach ($res as $k=>$v)
			{
				echo "<TR>
							<TD bgcolor='#444E6C' class=maintxt width='140'>".
							$v['paydata']
							."</TD>
							<TD bgcolor='#444E6C' class=maintxt>".
							$v['payresult']
							."</TD>
							<TD bgcolor='#444E6C' class=maintxt>".
							$v['phonenumber']
							."</TD>
							<TD bgcolor='#444E6C' class=maintxt>".
							$v['usersms']
							."</TD>
						</TR>";
			}
			echo "</TABLE>
				</td>
				</tr>				
				</table>";
						
		}
		else
		{
			echo "User has no sms-payments";
		}
		
		return true;
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>