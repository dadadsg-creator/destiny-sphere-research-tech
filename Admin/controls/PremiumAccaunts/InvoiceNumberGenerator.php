<?

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '..', true);
}

require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/Assert.class.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/TypeValidator.class.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/DSCommonValidator.class.php';




class InvoiceNumberGenerator
{
	var $clear;
	
	function GetSplitDate()
	{
		if($this->clear)
		{
			return mktime(0,0,0,1,25,2005);
		}
		
		return mktime(0,0,0,1,1,2005);
		
	}
	
	
	function InvoiceNumberGenerator($param = true)
	{
		$this->clear = $param;
	}
	
	function generateInvoiceNumber($language, $date, $id_payment)
	{
    	Assert::PreCondition(TypeValidator::IsString($date));
    	Assert::PreCondition(TypeValidator::IsWantedPattern('/^200[0-9]{1}[0-1]{1}[0-9]{1}[0-3]{1}[0-9]{1}$/', $date));	
    	Assert::PreCondition(TypeValidator::IsString($language));
    	Assert::PreCondition(TypeValidator::IsInteger($id_payment));
    	Assert::PreCondition(DSCommonValidator::IsConnectionValid());
    	
    	if($this->checkTypeInvoice($date) )
    	{	    
    		extract($this->parseDate($date));    	    	    
    		$strYearLastDigits = substr($year, -1);    	    	

    		$prevId = $this->getLastInvoiceNumberInYear($year - 1);
    		//$currentId = $this->getLastInvoiceNumberInYear($year);    	  
    	
    		$number = $id_payment - $prevId;
	    	$number = str_pad($number, 5, "0", STR_PAD_LEFT);
    	    	    	
			return $language. $strYearLastDigits. '-'. $number;
    	}
    	
    	return $language. '-'. $id_payment;    				
    				
	}	
	
	function  getLastInvoiceNumberInYear($year)
	{
    	Assert::PreCondition(DSCommonValidator::IsConnectionValid());
    	
    	$isoDate = 'CAST(\''.($year + 1).'0101\' as TIMESTAMP)';
    	
    	$sql = '
    		SELECT
    				MAX(id_payment)
    			FROM
    				ds_payment
    			WHERE
    				(paymentdate < '.$isoDate.')
    		';
    	
    	$row = $GLOBALS['common']->getResultArray('last year id payment',$sql,'','','');

    	
    	Assert::InvariantCondition(DSCommonValidator::IsQueryOk($row));
		    	    	
    	
    	if(empty($row[0]['max']))
    	{
    		return 1;
    	}
    	
		return $row[0]['max'];
	}
	
	function getPaymentIdByCode($code)
	{
		Assert::PreCondition(TypeValidator::IsString($code));
		if( strlen($code) === 6)
		{			
			$year = substr($code, 0, 1);			
			settype($year, 'integer');
			$year = $year + 2000;
			$prevId = $this->getLastInvoiceNumberInYear($year - 1);
			
			$number = substr($code, 1);			
			settype($number, 'integer');						
							
			return $prevId + $number;	
		}
				
		return $code;		
			
						
	}
	
	
	function checkTypeInvoice($date)
	{		
		
		Assert::PreCondition(TypeValidator::IsWantedPattern('/^200[0-9]{1}[0-1]{1}[0-9]{1}[0-3]{1}[0-9]{1}$/', $date));	
		
		extract($this->parseDate($date)); 
		$new_invoices = mktime(0,0,0, $month, $day, $year);
		
		if(InvoiceNumberGenerator::GetSplitDate() <= $new_invoices)
		{		
			return true;
		}
		return false;
	}
	
	function parseDate($date)
	{		
		Assert::PreCondition(TypeValidator::IsWantedPattern('/^200[0-9]{1}[0-1]{1}[0-9]{1}[0-3]{1}[0-9]{1}$/', $date));	
		$year = substr($date,0,4);	
		$month =  substr($date,4,2);
		$day = substr($date,6,2);		
		return compact('year', 'month', 'day');		
		
	}
}


?>