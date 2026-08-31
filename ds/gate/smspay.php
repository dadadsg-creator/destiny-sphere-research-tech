<?
//require_once("./premiumlogger.php");
class SMSPay
{	
	private $user;
	private $result = '';
	
	public function __construct($id_user, $phone = '', $smsText = '', $countOfDays, $testMode = false)
	{				
		assert(is_int($countOfDays) && $countOfDays > 1);
		$this->user = DE_User::CreateFromID((int)$id_user);
		
		$comments = $phone." || ".$smsText;
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->startTrans();
		
		if(!PU_ExecChangePremiumDays::Exec($this->user, $countOfDays, 'SMS', $comments, true))
		{
			$this->setOutputAnswer("DS_TECH_ERROR");
			$common->closeTrans('ROLLBACK');
			return false;
		}
		
		$res = PU_ExecChangePremiumDays::$result;
		
		if($res == 'ACCOUNT_PROLONGED')
		{
			$restr_sms = "{PREMIUM_MSG_PROLONGED_$countOfDays}";
		} else {
			$restr_sms = "{PREMIUM_MSG_ACTIVATED_$countOfDays}";
		}
		
		$this->setOutputAnswer("{PREMIUM_MSG_START} ".$restr_sms);
		
		if($testMode)
		{
			$common->closeTrans('ROLLBACK');
		} else {
			$common->closeTrans('COMMIT');
		}
	}
		
	private function setOutputAnswer($str)
	{
		$this->result = $str;
	}
	
	public function getResult()
	{
		return $this->result;
	}
}
?>
