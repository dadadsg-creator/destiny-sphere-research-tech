<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

new CreatePayment();

class CreatePayment
{
	var $common;
	
	function CreatePayment()
	{
		$this->common = &ClassFactory::getInstance("DS_Common");
		if (!$this->common->connect()) 
		{
			$this->outputResult('DB connection failed');
			exit();
		}	
		
		$this->showRequestForm();
		if(sizeof($_POST) > 0)
		{
			if($this->getPostData($_POST))
				$this->outputResult("Payment sucessfully added");
		}
	}
	
	function outputResult($str)
	{
		echo ShowCMSMessage($str);
		return true;
	}
	
	function getPostData($data)
	{
		if(!isset($data['username'] ) ||
			!isset($data['id_paymentmodel'] ) ||
			!isset($data['id_paymenttype'] )
			)
			{
				$this->outputResult("Wrong params");
				return false;
			}
			
			$data['id_user'] = $this->getUserIDByName($data['username']);
			if(!$data['id_user'])
				return false;
				
			if(strlen($data['paymentdate']) != 0)
			{
				if(preg_match("/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}\:[0-9]{2}\:[0-9]{2}$/",
					$data['paymentdate']) == 0)
				{
					$this->outputResult("Wrong paymentdate");
					return false;
				}
				$data['paymentdate'] = "to_timestamp('".$data['paymentdate']."','YYYY-MM-DD HH24:MI:SS')";
			}
			else 
			{
				$data['paymentdate'] = "now()";
			}
						
			if(!$this->getPriceByIDPaymentModel(&$data))
				return false;
				
			isset($data['verified'])? $data['verified'] = 't':$data['verified'] = 'f';
			
			return $this->insertPayment($data);
	}
	/*
	id_user
id_paymentmodel,
id_paymenttype
paymentdate

price
verified
*/
	function insertPayment($data)
	{
		$sql = "INSERT INTO
           					ds_payment
           					(id_user , id_paymentmodel , id_paymenttype,
           					nextpayment , paymentdate , price ,
           					totalprice, startdate, enddate, verified)
					VALUES
      						(".$data['id_user'].",".$data['id_paymentmodel'].",".$data['id_paymenttype'].",
      						".$data['paymentdate']." + '".$data['duration']." DAY'::interval,
      						".$data['paymentdate'].",
      						".$data['price'].",
      						".$data['price'].",
      						".$data['paymentdate'].",
      						".$data['paymentdate']." + '".$data['duration']." DAY'::interval,
      						'".$data['verified']."')";
		$this->common->executeQuery($sql);
		
		if ($this->common->errormsg) 
		{
			$this->incomingData['error'] = 'Error in ûףו creation date for user '.$this->id_user;
			$this->logWrongRequest();			 
			return false;									
		}	
		
		return true;
	}
	
	function getPriceByIDPaymentModel($data)
	{
		$sql = "SELECT
    					pm.price,
    					pd.duration
					FROM
    					ds_paymentmodel pm,
    					ds_paymentduration pd
					WHERE
     					pm.id_paymentmodel =".$data['id_paymentmodel'].
						"AND pm.id_duration = pd.id_duration";
		
		$res = $this->common->getResultArray ( "",$sql, "", "", "" );			
					
		if($this->common->errormsg || sizeof($res) == 0)
		{
			$this->outputResult("can't get price for id_paymentmodel ".$id_paymentmodel);		
			return false;	
		}		
		
		$data['price'] = $res[0]['price'];		
		$data['duration'] = $res[0]['duration'];
		
		return true;
	}
	
	function getUserIDByName($username)
	{
		$sql = "SELECT
      					u.id_user
					FROM
    					ds_user u
					WHERE
     					u.username = '".$username."'";
		
		$res = $this->common->getResultArray ( "",$sql, "", "", "" );			
					
		if($this->common->errormsg)
		{
			$this->outputResult("can't get id_user");		
			return false;	
		}
		
		if(sizeof($res) == 0)
		{
			$this->outputResult("No such user!");		
			return false;	
		}
		
		return $res[0]['id_user'];		
	}
	
	function showRequestForm()
	{
		?>
		<FORM method="POST" action="<?echo $_SERVER["PHP_SELF"];?>">
		<TABLE border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
		<TR>
			<TD>
			<TABLE width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 width='140' align="center">
							<? echo local( 'DS_USER_USERNAME' );?>
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140' align="center">
							<? echo local( 'DS_PAYMENTTYPE' );?>
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140' align="center">							
							<? echo local( 'DS_SUM' );?>
							</TD>						
							<TD bgcolor='444E6C' class=th_2 width='140' align="center">
							<? echo local( 'DS_PAYMENTDATE' );?>
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='80' align="center">
							<? echo local( 'DS_VERIFIED' );?>
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140' align="center">
							<? echo local( 'DS_ACTION' );?>
							</TD>
						</TR>
						<TR>
							<TD bgcolor='#444E6C' class=maintxt width='140' align="center">
							<INPUT type="text" size="20" maxlength="20" name="username">
							</TD>
							<TD bgcolor='#444E6C' class=maintxt width='140' align="center">
							<? $this->showPaymenttypesList();?>
							</TD>
							<TD bgcolor='#444E6C' class=maintxt width='140' align="center">							
							<? $this->showListOfPrices();?>
							</TD>						
							<TD bgcolor='#444E6C' class=maintxt width='150' align="center">
							<INPUT type="text" maxlength="19" size="19" name="paymentdate">
							</TD>
							<TD bgcolor='#444E6C' class=maintxt width='80' align="center">
							<INPUT type="checkbox" name="verified" class="check">
							</TD>
							<TD bgcolor='#444E6C' class=maintxt width='140' align="center">
							<INPUT type="submit" class='Submit' value="<? echo local( 'DS_ADD_PAYMENT' );?>">
							</TD>
						</TR>
			</TABLE>
			</TD>
		</TR>
		</TABLE>
		</FORM>
		<?
	}
	
	function showListOfPrices()
	{
		$sql = "SELECT
      					pm.price,
      					pm.id_paymentmodel
					FROM
    					ds_paymentmodel pm
					ORDER BY pm.price";
		
		$res = $this->common->getResultArray ( "",$sql, "", "", "" );						
		if($this->common->errormsg)
		{
			$this->outputResult("can't get list of prices");		
			return false;	
		}
		?>
		<SELECT name="id_paymentmodel">
		<?							
		foreach ($res as $k=>$v)
		{
			?>
			<OPTION value="<?echo $v['id_paymentmodel'];?>">
				<?echo $v['price'];?>
			</OPTION>
			<?							
		}
		?>
		</SELECT>
		<?							
		return true;
	}
	
	function showPaymenttypesList()
	{
		$sql = "SELECT
      					pt.name,
      					pt.id_paymenttype
					FROM
    					ds_paymenttype pt
					WHERE
     					pt.name not in ('SMS')";
		
		$res = $this->common->getResultArray ( "",$sql, "", "", "" );						
		if($this->common->errormsg)
		{
			$this->outputResult("can't get list for paymenttypes");		
			return false;	
		}
		?>
		<SELECT name="id_paymenttype">
		<?							
		foreach ($res as $k=>$v)
		{
			?>
			<OPTION value="<?echo $v['id_paymenttype'];?>">
				<?echo $v['name'];?>
			</OPTION>
			<?							
		}
		?>
		</SELECT>
		<?							
		return true;
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
exit();
?>