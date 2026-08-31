<?PHP

session_start ();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/InvoiceNumberGenerator.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/classes/Core/Trace.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/classes/Core/TextFileTraceListener.class.php';

Assert::SetDieOnError(false);
//$testListener = &new TextFileTraceListener('../Report/trace.txt');
//Trace::AddListener($testListener);

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	if ( !$common->setDbId(0) ) {

		echo "DB-connection failed";

		exit;
	}
}

class ShowInvoice {

	var $errormsg;
	var $id_user;
	var $language;

	function ShowInvoice () {

		global $common;

		if ( !isset ( $_GET['id_user'] ) ) {

			echo "usage: showInvoice?id=XXX";
			return true;				
		}
		
		$this->id_user = $_GET['id_user'];

		$sql = sprintf ( "select l.shortname
				from ds_user u,
				ds_language l
				where id_user = %d
				and u.id_language = l.id_language",
				$this->id_user );
				
		$res = $common->getResultArray ( "id_language_id_user", $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg = "SE ShowInvoice::ShowInvoice1";
			return false;
		}
		
		$this->language = $res[0]['shortname'];

		if ( !$this->getPostData ( $_POST ) ) {
			echo $this->errormsg;
		}
	}

	function getPostData ( $data ) {

		global $common;

		if ( sizeof ( $data ) == 0 ) {

			if ( !$this->show () ) {
				return false;
			}

		} else if ( isset ( $data['find'] ) ) {


		} else if ( isset ( $data['edit'] ) ) {

			$sql = sprintf ( "select pt.name,
					p.verified
					from ds_paymenttype pt,
					ds_payment p
					where pt.id_paymenttype = p.id_paymenttype
					and p.id_payment = %d",
					$data['id_payment'] );
					
			$res = $common->getResultArray ( "payment_type", $sql, "", "", "" );
			if ( !$res ) {
				$this->errormsg = "SE page5::showFirstPage1";
				return false;
			}
					
			$paymenttype = $res[0]['name'];
			if ( $res[0]['verified'] == 't' ) $ver = "true"; else $ver = "false";

			$res = $common->executeQuery ( "begin" );

			if ( $data['paymentdate'] != "") {

				$paymentdate = "to_timestamp ( '".$data['paymentdate']."', 'YYYY-MM-DD HH24:MI' )"; 

			} else {

				$paymentdate = "null";
			}

			if ( $data['nextpayment'] != "" ) {

				$nextpayment = "to_timestamp ( '".$data['nextpayment']."', 'YYYY-MM-DD HH24:MI' )"; 

			} else {

				$nextpayment = "null";
			}

			if ( $data['startdate'] != "") {

				$startdate = "to_timestamp ( '".$data['startdate']."', 'YYYY-MM-DD HH24:MI' )"; 

			} else {

				$startdate = "null";
			}

			if ( $data['enddate'] != "" ) {

				$enddate = "to_timestamp ( '".$data['enddate']."', 'YYYY-MM-DD HH24:MI' )"; 

			} else {

				$enddate = "null";
			}

			if ( $paymenttype != 'BANK_TRANSFER' ) {

				if ( @$data['verified'] ) 
				{
													     			        
			        $sql = sprintf("SELECT 
											pd.duration
         								FROM
             								ds_payment p,
             								ds_paymentduration pd,
             								ds_paymentmodel pm
             							WHERE
                  							p.id_payment = %d
                  							AND p.id_paymentmodel = pm.id_paymentmodel
                  							AND pm.id_duration = pd.id_duration",
										$data['id_payment']);
										
		            $res = $common->getResultArray ( "", $sql, "", "", "" );		            
                    if ($res === false) 
					{
                		$this->errormsg = "SE showInvoice::Unable to get payment duration".$sql."|".pg_last_error();
                		return false;
			        }
			        
					if($this->checkDays($result[0]['id_user']))
					{

						$sql = sprintf ( "update 
												ds_userprofidata
											set 
												active = true,
												profi = true,
												trial = false,												
												enddate = enddate + interval '%s days'																									
											where id_user = %d",											
											$res[0]['duration'],
											$this->id_user );
					}
					else 
					{						
						$sql = sprintf ( "update 
												ds_userprofidata
											set 
												active = true,
												profi = true,
												trial = false,
												startdate = now(),												
												enddate = now() + interval '%s days'																									
											where id_user = %d",											
											$res[0]['duration'],
											$this->id_user );
					}										
				
					$res = $common->executeQuery ( $sql );
					if ( $res === false ) {	
						$this->errormsg = "SE showInvoice::getPostData1";
						return false;				
					}

					$verified = "true";

				} 
				else 
				{				
					$verified = "false";
				}
				
			} else {
				
				$verified = $ver;
			}

			if ( @$data['recurring'] ) {

				$recurring = "true";

			} else {
				
				$recurring = "false";
			}

			$sql = sprintf ( "update ds_payment
					set paymentdate = %s,
					nextpayment = %s,
					startdate = %s,
					enddate = %s,
					verified = %s,
					recurring = %s
					where id_payment = %d",
					$paymentdate,
					$nextpayment,
					$startdate,
					$enddate,
					$verified,
					$recurring,
					$data['id_payment'] );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				$this->errormsg = "SE showInvoice::getPostData2";
				return false;				
			}

			$res = $common->executeQuery ( "commit" );

			if ( !$this->show () ) {
				return false;
			}
		}
	}

	function show () {

		global $common;
		
		$sql = sprintf ( "select p.id_payment,
				pt.name,
				p.verified,
				p.recurring,
				p.paymentdate,
				p.nextpayment,
				p.startdate,
				p.enddate,
				to_char ( p.paymentdate, 'YYYYMMDD' ) as paymentdate2
				from ds_payment p,
				ds_paymenttype pt
				where p.id_user = %d
				and p.id_paymenttype = pt.id_paymenttype
				order by p.id_payment desc",
				$this->id_user );

		$res = $common->getResultArray ( "payments", $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SE page5::showFirstPage11";
			return false;
		}


		echo "<TABLE width='100%' border='1' cellpadding='5' cellspacing='0' align='center'>";

		echo "<tr><td><b>id_payment</b></td>

			<td><b>Check</b></td>
			<td><b>PaymentType</b></td>
			<td><b>PaymentDate</b></td>
			<td><b>NextPayment</b></td>
			<td><b>Startdate</b></td>
			<td><b>Enddate</b></td>
			<td><b>Verified</b></td>
			<td><b>Recurring</b></td>
			<td>&nbsp;</td>
			</tr>";

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			if ($res[$i]['name']=='ASSIST')
			{
				$sql1="select transaction_id from ds_paymenttypeassist where id_payment=".$res[$i]['id_payment'];

				$res1 = $common->getResultArray ( "payments1", $sql1, "", "", "" );

				if ( $res1 === false ) {

						$this->errormsg = "SE page5::showFirstPage11";

					return false;
			}

			}
			
			$language = $this->language;			
			$id = $res[$i]['id_payment'];
			$pdate = $res[$i]['paymentdate2'];
			$gen = new InvoiceNumberGenerator();
						
			echo "<form method=post action=showInvoice.php?id_user=".$this->id_user.">".
				"<tr><td>
				<a target='_blank' href='showsingleinvoice.php?id_payment=".$res[$i]['id_payment']."&id_user=".$_REQUEST[id_user]."'><b>".$gen->generateInvoiceNumber($language, $pdate, $id)."</b></a>

								
				</td>".

				"<td>&nbsp;<a href='https://account.destinysphere.net/ru/data/".$res1[0][transaction_id].".html' target=_blank>".$res1[0][transaction_id]."</td>".
                                                    
				"<td>";
				if ( $res[$i]['name'] == 'BANK_TRANSFER' ) {

					echo "<a href='showTransactions?id_payment=".$res[$i]['id_payment']."'><b>".$res[$i]['name']."</b></a>";

				} else {
					
					echo "<b>".$res[$i]['name']."</b>";
				}

				echo "</td>
				<td><input type=text name=paymentdate value='".$res[$i]['paymentdate']."'></td>".
				"<td><input type=text name=nextpayment value='".$res[$i]['nextpayment']."'></td>".
				"<td><input type=text name=startdate value='".$res[$i]['startdate']."'></td>".
				"<td><input type=text name=enddate value='".$res[$i]['enddate']."'></td>".
				"<td><input type=checkbox name=verified";
				
				if ( $res[$i]['verified'] == 't' ) echo " checked disabled";
				
				echo "></td>".
				"<td><input type=checkbox name=recurring";
				
				if ( $res[$i]['recurring'] == 't' ) echo " checked";
				
				echo "></td>".
				"<td><input type=hidden name=id_payment value=".$res[$i]['id_payment'].
				"><input type=submit name=edit value=Edit>
				</td></tr></form>";
		}

		echo "</table>";

	}
	
	function checkDays()
	{
		global $common;
		
		$sql="select 
					to_char ( enddate, 'YYYYMMDDHH24MISS' ) as enddate
				 from 
				 		ds_userprofidata 
				 where id_user=".$this->id_user;
		
		$res=$common->getResultArray ( "profi_enddate", $sql, "", "", "" );
        if ( !is_array($res)) 
		{
        	$this->errormsg ="Unable to check profi status ".$sql."|".pg_last_error();
        	return false;
		} 
		if(sizeof($res[0]['enddate']) === 0)
		{
			return false;
		}
		
		$year = substr($res[0]['enddate'],0,4);	
		$month = substr($res[0]['enddate'],4,2);
		$day = substr($res[0]['enddate'],6,2);
		$hours = substr($res[0]['enddate'],8,2);
		$minuts = substr($res[0]['enddate'],10,2);
		$seconds = substr($res[0]['enddate'],12,2);
				
		$now = mktime(date("H"), date("m"), date("s"), date("m"), date("d"), date("Y"));
		$enddate = mktime($hours,$minuts,$seconds,$month,$day,$year);
		
		if($now < $enddate)
		{
			return true;			
		}
		else 
		{
			return false;
		}
	}
}

$si = new ShowInvoice ();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>