<?
session_start ();


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
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

require_once ( "IT.php" );

class showSingleInvoce {

	var $errormsg;
	var $errorname;
	var $id_payment;
	var $id_user;

	function showSingleInvoce () {

		$this->errorname = array ();
		$this->id_payment = $_GET['id_payment'];
		$this->id_user = $_REQUEST['id_user'];

		if (!$this->getPostData($_POST)) {
			echo $this->errormsg;
		}
	}

	function getPostData ( $data ) {

		global $common;
		global $trial_days;

		if ( sizeof ( $data ) == 0 || isset ( $data['show_invoice'] ) ) 
		{
			$this->showFirstPage ();
		} 
	}

	function showFirstPage () {

		global $common;
		require_once ( "invoice.php.lang" );

		if ( !@$lang ) {
			$this->errormsg = "showSingleInvoce::showFirstPage1";
			return false;
		}

		$sql = sprintf ( "select kindofpayment,
				pm.id_paymentkind,
				c.currency_short,
				c.id_currency,
				pm.id_paymentmodel,
				pt.name,
				p.price,
				p.tax,
				p.id_payment,
				p.totalprice,
				to_char ( p.paymentdate, 'DD.MM.YYYY' ) as paymentdate,
				to_char ( p.enddate, 'DD.MM.YYYY' ) as paymentenddate,
				to_char ( p.startdate, 'DD.MM.YYYY' ) as paymentstartdate,	
				d.duration,
				ptb.accountnumber,
				ptb.bankcode,
				ptb.bankname
				from ds_paymentmodel pm,
				ds_paymentkindhascurrency pkhc,
				ds_currency c,
				ds_paymentduration d,
				ds_paymentkindhastax pkht,
				ds_paymentkind pk,
				ds_paymenttype pt,
				ds_payment p
				left join ds_paymenttypebank ptb
				on p.id_payment = ptb.id_payment
				left join ds_user u on p.id_user=u.id_user 
				where pkhc.id_paymentkind = pm.id_paymentkind
				and pkhc.id_currency = c.id_currency
				and pkht.id_paymentkind = pm.id_paymentkind
				and pm.id_paymentkind = pk.id_paymentkind
				and p.id_paymentmodel = pm.id_paymentmodel
				and pm.id_duration = d.id_duration
				and p.id_paymenttype = pt.id_paymenttype
				and p.id_payment = %d
				and p.id_user = %d",
				$this->id_payment,
				$this->id_user );

		$res = $common->getResultArray ( "kind_of_payment", $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SE - showSingleInvoce::getPostData2";
			return false;
		}

		if($res[0]['name'] != 'BANK_TRANSFER' && 
			$res[0]['name'] != 'MANUAL_BANK_TRANSFER' &&
			$res[0]['name'] != 'STANDING_ORDER') 
		{
			$tpl = new HTML_Template_IT ("./");

			$template = ($_GET['show']==1?"post.tpl":"post2.tpl");
			$tpl->loadTemplatefile ( $template, true, true);   

			$lang['COMMENT']='';

			$tpl->setVariable("INVOICE_SUM", number_format( $res[0]['totalprice'], 2, ".", "" ));
			$tpl->setVariable("TAX", number_format( $res[0]['totalprice']*0.16, 2, ".", "" ));			
			$tpl->setVariable("ID_USER", $_REQUEST['id_user']);

			$sql = sprintf ( "select firstname, surname, street, city
					from ds_userprofidata 
					where id_user = %d",
					$_REQUEST['id_user']);

			$res = $common->getResultArray("username_address", $sql, "", "", "");

			if ( !$res ) {
				$this->errormsg = "SE - Page12::getPostData2";
				return false;			
			}

			$tpl->setVariable("FIO", $res[0]["surname"]." ".$res[0]["firstname"]);
			$tpl->setVariable("ADDRESS", $res[0]["city"]." ".$res[0]["street"]);
			
			$sql=sprintf("	
				select pd.duration, 
						to_char(paymentdate, 'DD.MM.YYYY') as paymentdate,
						to_char ( p.paymentdate, 'YYYYMMDD' ) as paymentdate2,
						p.id_payment
				from ds_payment p,
				     ds_paymentduration pd,
				     ds_paymentmodel pm
				where
				      pd.id_duration = pm.id_duration
				  and pm.id_paymentmodel = p.id_paymentmodel
				  and p.id_payment = %d", $this->id_payment);

			$res = $common->getResultArray("paymentdate", $sql, "", "", "");

			if ( !$res ) {
				$this->errormsg = "SE - Page12::getPostData2";
				return false;			
			}
			
			
			$language = $_SESSION["language"];
			if(empty($language))
			{
				$language = 'R';
			}
			$id = $res[0]['id_payment'];
			$pdate = $res[0]['paymentdate2'];
			$gen = new InvoiceNumberGenerator();

			$tpl->setVariable("INVOICE_DATE", $res[0]["paymentdate"]);
			$tpl->setVariable("DURATION", $res[0]["duration"]);
			$tpl->setVariable("INVOICE_NUMBER", $gen->generateInvoiceNumber($language, $pdate, $id));

		} else {

			if ( $res[0]['name'] == 'BANK_TRANSFER' ) $template = "invoice.tpl";
			if ( $res[0]['name'] == 'MANUAL_BANK_TRANSFER' ) $template = "manual_bank_transfer.tpl";
			if ( $res[0]['name'] == 'STANDING_ORDER' ) $template = "standing_order.tpl";

			$tpl = new HTML_Template_IT ("./");
			$tpl->loadTemplatefile ( $template, true, true);   

			foreach ( $lang as $key=>$val ) {
				$tpl->setVariable ( $key, $lang[$key] ) ;
			}

			$tpl->setVariable ( "PAYMENT_MODEL", $res[0]['kindofpayment']." ".$res[0]['duration']." ".$lang['DAY'] ) ;
			$tpl->setVariable ( "PRICE", number_format ( $res[0]['price'], 2, ".", "" )." ".$res[0]['currency_short'] );
			$tpl->setVariable ( "TAX", number_format ( $res[0]['price']*$res[0]['tax'], 2, "", "" )." ".$res[0]['currency_short'] );
			$tpl->setVariable ( "TOTAL_PRICE", number_format( $res[0]['totalprice'], 2, ".", "" )." ".$res[0]['currency_short'] );
			$tpl->setVariable ( "PAYMENT_DATE", $res[0]['paymentdate'] );
			$tpl->setVariable ( "LANGUAGE_SHORT", $_SESSION["language"] );
			$tpl->setVariable ( "ID_PAYMENT", $this->id_payment );
			$tpl->setVariable ( "DURATION", $res[0]['duration'] );

			if ( $res[0]['accountnumber'] == "" ) {
				$accountnumber = '________________';
			} else {
				$accountnumber = $res[0]['accountnumber'];
				$accountnumber = substr ( $accountnumber, 0, strlen ( $accountnumber ) - 4 )."XXXX";
			}

			$tpl->setVariable ( "ACCOUNT_NUMBER",  $accountnumber );

			if ( $res[0]['bankcode'] == "" ) {
				$bankcode = '________________';
			} else {
				$bankcode = $res[0]['bankcode'];
			}
			$tpl->setVariable ( "BANK_CODE", $bankcode );

			if ( $res[0]['bankname'] == "" ) {
				$bankname = '________________';
			} else {
				$bankname = $res[0]['bankname'];
			}

			$tpl->setVariable ( "BANK_NAME", $bankname );

			$tpl->setVariable ( "PAYMENT_START_DATE", $res[0]['paymentstartdate'] );
			$tpl->setVariable ( "PAYMENT_END_DATE", $res[0]['paymentenddate'] );

			$sql = sprintf ( "select upd.*,
					to_char ( upd.startdate, 'DD.MM.YYYY' ) as startdate,
					to_char ( upd.enddate, 'DD.MM.YYYY' ) as enddate,
					u.username
					from ds_userprofidata upd,
					ds_user u
					where u.id_user = upd.id_user
					and u.id_user = %d",
					$_REQUEST['id_user'] );	

			$res = $common->getResultArray ( "username", $sql, "", "", "" );
			if ( !$res ) {
				$this->errormsg = "SE - showSingleInvoce::getPostData3";
				return false;
			}

			$tpl->setVariable ( "USERNAME", $res[0]['username'] ) ;
			$tpl->setVariable ( "FIRSTNAME", $res[0]['firstname'] ) ;
			$tpl->setVariable ( "SURNAME", $res[0]['surname'] ) ;
			$tpl->setVariable ( "STREET", $res[0]['street'] ) ;
			$tpl->setVariable ( "POSTCODE", $res[0]['postcode'] ) ;
			$tpl->setVariable ( "CITY", $res[0]['city'] ) ;
			$tpl->setVariable ( "PHONE1", $res[0]['phone1'] ) ;
			$tpl->setVariable ( "EMAIL", $res[0]['email'] ) ;
			$tpl->setVariable ( "ID_USER", $res[0]['id_user'] );
			$tpl->setVariable ( "COUNTRY", $res[0]['country'] );
		}

		if ( !ereg ( "page5.php", $_SERVER['HTTP_REFERER'] ) ) {
			$tpl->setVariable ( "READY", $lang['READY_'] );
		}

		$tpl->show(); 
	}
}

$start = new showSingleInvoce ();

//require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>