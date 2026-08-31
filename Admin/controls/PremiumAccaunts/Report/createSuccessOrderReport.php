<?
session_start ();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";





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
			


			echo "<form action='createSuccessOrderReport.php' method=POST>";
			if (sizeof($res)>0)
			{
				echo("Payment Type:&nbsp;&nbsp;&nbsp;<select name='paymentType'>");
				echo("<option value=0>ALL</option>");
				for ($i=0;$i<sizeof($res);$i++)
				{
					echo("<option value='".$res[$i]['id_paymenttype']."'>".$res[$i]['name']."</option>");
				}
				echo("</select>&nbsp;&nbsp;");
			}
			echo "Start date:&nbsp;<input type=text name=sDate value='".date('Y-m-d 00:00:00',mktime(0, 0, 0, date('m') -1 , date('d'), date('Y')))."'>&nbsp;&nbsp;";
			echo "End date:&nbsp;<input type=text name=eDate value='".date('Y-m-d H:i:s')."'>&nbsp;&nbsp;";
			echo "<input type=submit value='Create report'><hr size=1><br><br>";
			echo "</form>";
			exit;
		}


		$paymentType=' ';
		if ($_REQUEST['paymentType']!=0) $paymentType=" AND pt.id_paymenttype=".$_REQUEST['paymentType']." ";
		
		$sql="select
p.id_payment,
upd.firstname,
upd.surname,
to_char (upd.startdate, 'DD.MM.YYYY HH24:MI') as startdate,
to_char (upd.enddate, 'DD.MM.YYYY HH24:MI') as enddate,
to_char (p.paymentdate, 'DD.MM.YYYY HH24:MI') as paymentdate,
u.id_user,
u.username,
p.price,
p.tax,
pt.name
from ds_payment p
left join ds_userprofidata upd on (upd.id_user=p.id_user)
left join ds_user u on (u.id_user=p.id_user)
left join ds_paymentmodel pm on (p.id_paymentmodel=pm.id_paymentmodel)
left join ds_paymenttype pt on (p.id_paymenttype = pt.id_paymenttype)
where
p.paymentdate < to_timestamp ('".$_REQUEST['eDate']."', 'YYYY-MM-DD HH24:MI')
and
p.paymentdate > to_timestamp ('".$_REQUEST['sDate']."', 'YYYY-MM-DD HH24:MI')
and
p.verified=true"
.$paymentType.
"order by p.id_payment desc";


		$res = $common->getResultArray ( "payment", $sql, "", "", "" );
                if ( $res === false ) 
		{
	                $this->errormsg[] = " SYSTEM ERROR - SQL1";
        	}

		$fileName = "data/".date('dmYHis').".csv";
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
				$res[$i]['price']=round((1+$res[$i]['tax'])*$res[$i]['price'],2);
				$res[$i]['price']=preg_replace('|\.|',',',$res[$i]['price']);

				fwrite($fh, $res[$i]['id_payment'].";");
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
		Header ("Location:$fileName");

	}




}

$start=new getReport();
?>