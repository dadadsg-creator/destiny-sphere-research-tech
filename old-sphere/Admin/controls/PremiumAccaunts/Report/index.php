<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/InvoiceNumberGenerator.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/classes/Core/Trace.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/classes/Core/TextFileTraceListener.class.php';

Assert::SetDieOnError(false);

if ( !isset ( $GLOBALS["common"] ) ) {

	$common = & ClassFactory::getInstance ( "DS_Common" );

	if ( !$common->setDbId(0) ) {

		echo "DB-connection failed";

		exit;

	}

}

class getReport
{
	var $errormsg;


	function getReport()
	{
		global $common;

		if (!isset ($_REQUEST['sDate']))
		{

			$sql="select id_paymenttype, name from ds_paymenttype";
			$res = $common->getResultArray ( "paymenttype", $sql, "", "", "" );
        	        if ( $res === false ) 
			{
	                	$this->errormsg[] = " SYSTEM ERROR - SQL2";
	        	}
			


			echo "<form action='index.php' method=POST>";
			if (sizeof($res)>0)
			{
				echo("<table cellpadding=8 cellspacing=0><tr><td bgcolor='444E6C' class=maintxt>"); 
				echo("<strong>Payment Type:</strong>&nbsp;&nbsp;&nbsp;<select name='paymentType'>");
				echo("<option value=0>ALL</option>");
				for ($i=0;$i<sizeof($res);$i++)
				{
					echo("<option value='".$res[$i]['id_paymenttype']."'>".$res[$i]['name']."</option>");
				}
				echo("</select>&nbsp;&nbsp;");
			}
			echo "<strong>Start date:</strong>&nbsp;<input type=text name=sDate value='".date('Y-m-d 00:00:00',mktime(0, 0, 0, date('m') -1 , date('d'), date('Y')))."' style='width: 150px;'>&nbsp;&nbsp;";
			echo "<strong>End date:</strong>&nbsp;<input type=text name=eDate value='".date('Y-m-d H:i:s')."' style='width: 150px;'>&nbsp;&nbsp;";
			echo "</td></tr>";	
			echo "<tr><td bgcolor='#444E6C' class=maintxt><input type=submit class=Submit value='Create report'><hr size=1><br><br></td></tr>";
			echo "</table></form>";
			exit;
		}


		$paymentType=' ';
		if ($_REQUEST['paymentType']!=0) $paymentType=" AND pt.id_paymenttype=".$_REQUEST['paymentType']." ";
		
		$sql="
select
p.id_payment,
upd.firstname,
upd.surname,	
to_char (upd.startdate, 'DD.MM.YYYY HH24:MI') as startdate,
to_char (upd.enddate, 'DD.MM.YYYY HH24:MI') as enddate,
to_char (p.paymentdate, 'DD.MM.YYYY HH24:MI') as paymentdate,
to_char ( p.paymentdate, 'YYYYMMDD' ) as paymentdate2,
to_char ( p.paymentdate, 'YYYYMMDDHHMM' ) as paymentdate3,				
u.id_user,
u.username,
p.price,
p.tax,
pt.name,
ptpp.id_transaction
from ds_payment p
left join ds_userprofidata upd on (upd.id_user=p.id_user)
left join ds_user u on (u.id_user=p.id_user)
left join ds_paymentmodel pm on (p.id_paymentmodel=pm.id_paymentmodel)
left join ds_paymenttype pt on (p.id_paymenttype = pt.id_paymenttype)
left join ds_paymenttypepaypal ptpp on (p.id_payment=ptpp.id_payment)
where		
p.paymentdate < to_timestamp ('".$_REQUEST['eDate']."', 'YYYY-MM-DD HH24:MI')
and
p.paymentdate > to_timestamp ('".$_REQUEST['sDate']."', 'YYYY-MM-DD HH24:MI')
and
p.verified=true"
.$paymentType.
"order by p.id_payment";

		



		$res = $common->getResultArray ( "payment", $sql, "", "", "" );
                if ( $res === false ) 
		{
	                $this->errormsg[] = " SYSTEM ERROR - SQL1";
        	}

		$date = date('dmYHis');
		$fileName = "data/".$date.".csv";
		if (!$fh = fopen($fileName, 'w')) 
		{
		         $this->errormsg[]="Can't open file...";
		}
                if (!chmod($fileName, 0666))
		{
			 $this->errormsg[]="Can't set chmod...";
		}
		
		if (sizeof($res)>0)
		{
			$gen = new InvoiceNumberGenerator(false);

			fwrite($fh, "Payment ID;");
			fwrite($fh, "User ID;");
			fwrite($fh, "User Name;");
			fwrite($fh, "User Nick;");
			fwrite($fh, "Subscription;");
			fwrite($fh, "Payment Date;");
			fwrite($fh, "Start Date;");
			fwrite($fh, "End Date;");
			fwrite($fh, "Price;");
			fwrite($fh, "\n");

			for ($i=0;$i<sizeof($res);$i++)
			{
				if ($res[$i]['name']=='PAY_PAL')
				{
					$res[$i]['name'].="(id=".$res[$i]['id_transaction'].")";
				}
#				$res[$i]['price']=round((1+$res[$i]['tax'])*$res[$i]['price'],2);
				$res[$i]['price']=preg_replace('|\.|',',',$res[$i]['price']);
				
				$language = 'R';					
				$pdate = $res[$i]['paymentdate2'];
				$old_inv = '';
				if(!$this->checkSplit($res[$i]['paymentdate3']))
				{
					$old_inv = '  ('.$res[$i]['id_payment'].')';
				}
			
				$str_id = $gen->generateInvoiceNumber($language, $pdate, $res[$i]['id_payment']).
						   $old_inv;


				fwrite($fh, $str_id.";");
				fwrite($fh, $res[$i]['id_user'].";");
				fwrite($fh, $res[$i]['firstname']." ".$res[$i]['surname'].";");
				fwrite($fh, $res[$i]['username'].";");
				fwrite($fh, $res[$i]['name'].";");
				fwrite($fh, $res[$i]['paymentdate'].";");
				fwrite($fh, $res[$i]['startdate'].";");
				fwrite($fh, $res[$i]['enddate'].";");
				fwrite($fh, $res[$i]['price'].";");
				fwrite($fh, "\n");
			}

		}
		fclose($fh);


		$fileName = "data/".$date.".csv";
		if (!file_exists($fileName) ) {
		         $this->errormsg[]="Can't open file...";
		} else {
			ShowCMSMessage('<a href="'.$fileName.'">  Download CSV file </a>', 1);	
		}

		if ( is_array($this->errormsg) ) {
			ShowCMSMessage( implode('<br>',$this->errormsg), 1 );
		}
		
		
		
		//print_r ($file);



//		fclose($file);
//		headerLocation ($fileName);

	}
	
	function checkSplit($date)
	{						
		
		extract($this->parseDate($date)); 
		$new_invoices = mktime($hour,$minuts,0, $month, $day, $year);
		$split_end = mktime(13,0,0,1,28,2005);
		$split_begin = mktime(0,0,0,1,1,2005);
		
		if(($split_begin <= $new_invoices)&&($new_invoices < $split_end))
		{		
			return false;
		}
		return true;
	}
	function parseDate($date)
	{				
		$year = substr($date,0,4);	
		$month =  substr($date,4,2);
		$day = substr($date,6,2);
		$hour =	substr($date,8,2);
		$minuts = substr($date,10,2);	
		return compact('year', 'month', 'day','hour','minuts');		
		
	}




}

$start=new getReport();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>