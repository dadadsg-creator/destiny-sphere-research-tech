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


		
		$sql="select
p.id_payment,
upd.firstname,
upd.surname,
upd.startdate,
upd.enddate,
u.id_user,
u.username,
pm.price,
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
p.verified=true
order by p.id_payment";

		$res = $common->getResultArray ( "payment", $sql, "", "", "" );
                if ( $res === false ) 
		{
	                $this->errormsg[] = " SYSTEM ERROR - SQL1";
        	}

		echo("<a href='".$_SERVER[HTTP_REFERER]."'>Back</a><hr size=1><br>");
		if (sizeof($res)>0)
		{
			echo("<table border=1 cellpadding=2 cellspacing=0 width=90% align=center>");
			echo("<tr>");
			echo("<td><b>Payment</b></td>");
			echo("<td><b>User ID</b></td>");
			echo("<td><b>User Name</b></td>");
			echo("<td><b>User Nick</b></td>");
			echo("<td><b>Subscription</b></td>");
			echo("<td><b>Start Date</b></td>");
			echo("<td><b>End Date</b></td>");
			echo("<td><b>Price</b></td>");
			echo("</tr>");
			for ($i=0;$i<sizeof($res);$i++)
			{
				if($res[$i]['name']=='ASSIST')
				{
					$sql="select transaction_id from ds_paymenttypeassist where id_payment=".$res[$i]['id_payment'];

					$res1 = $common->getResultArray ( "transaction", $sql, "", "", "" );
			                if ( $res1 === false ) 
					{
				                $this->errormsg[] = " SYSTEM ERROR - SQL2";
			        	}
					$res[$i]['name']="<a href='assistInfo.php?order=".$res1[0][transaction_id]."' target=_blank>".$res[$i]['name']."</a>";
				}                                                                    

				echo("<tr>");
				echo("<td>".$res[$i]['id_payment']."</td>");
				echo("<td>".$res[$i]['id_user']."</td>");
				echo("<td>".$res[$i]['firstname']." ".$res[$i]['surname']."</td>");
				echo("<td>".$res[$i]['username']."</td>");
				echo("<td>".$res[$i]['name']."</td>");
				echo("<td>".$res[$i]['startdate']."</td>");
				echo("<td>".$res[$i]['enddate']."</td>");
				echo("<td>".$res[$i]['price']."</td>");
			}
			echo("</table>");

		}

	}




}

$start=new getReport();
?>