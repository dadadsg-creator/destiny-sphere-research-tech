<?php $CVS_Version = '$Revision$ ; $Date$';

class PU_ExecChangeAnalizerPremium
{
	static private $endDateOld;
	
	static private $endDateNew;
	
	static public $result = 'ERROR';
	
	/**
	 * Метод включения игроку премиум-доступа к анализатору
	 *
	 * @param DE_User $user
	 * @param integer $daysDelta
	 * @param string $paymentcode
	 */
	static public function Exec(DE_User $user, $daysDelta, $type, $comments, $denayStartTrans = false)
	{		
		assert(is_numeric($daysDelta));
		settype($type, 'string');
		assert(strlen($type) > 0);
		
		$uid = $user->getID();
		
		$_SESSION['dbId'] = 0;
		
		$common = DS_Common::CreateForCurentDB(0);
		
		if ($denayStartTrans != true) 
		{
			if(!$common->startTrans())
			{
				return false;
			}
		}
						
		self::$result = "ACCOUNT_PROLONGED";
		
		if(!DS_PremiumAnalizer::checkIfUserHavePremiumRegistration($uid))
		{
			if(!DS_PremiumAnalizer::insertFakePremiumRegistration($uid))
			{
				if ($denayStartTrans != true) {
					$common->closeTrans('rollback');
				}
				self::$result = 'ERROR';
				return false;
			}		
			
			self::$endDateOld = GB_TypeFactory::CreateConstant('GB_Date', 'now()');
			
			self::$result = "NEW_REG";
			
		} elseif(self::checkIfPremiumAccountEnded($user)) {
			if(!DS_PremiumAnalizer::setStartEndDateToNow($uid))
			{
				if ($denayStartTrans != true) {
					$common->closeTrans('rollback');
				}
				self::$result = 'ERROR';
				return false;
			}
			
			self::$result = "ACCOUNT_ENDED";
		}
				
		if(!DS_PremiumAnalizer::changeUserStatusToProfi($uid))
		{
			if ($denayStartTrans != true) {
					$common->closeTrans('rollback');
			}
			self::$result = 'ERROR';
			return false;
		}
		
		if(!DS_PremiumAnalizer::changePremiumEndDate($uid, $daysDelta))
		{
			if ($denayStartTrans != true) {
					$common->closeTrans('rollback');
			}
			self::$result = 'ERROR';
			return false;
		}
		
		$dates = DS_PremiumAnalizer::getUserPremiumDates($uid);
		self::$endDateNew = GB_TypeFactory::CreateConstant('GB_Date', $dates[0]['enddate_pg']);
		
		DS_PremiumAnalizer::logPremiumChange($uid, $type, $daysDelta, self::$endDateOld->getPGFormat(), self::$endDateNew->getPGFormat(), $comments);
		
		if ($denayStartTrans != true) {
			$common->closeTrans('commit');
		}
		
		return true;		
	}
	
	static public function checkIfPremiumAccountEnded(DE_User $user)
	{
		$uid = $user->getID();
		$dates = DS_PremiumAnalizer::getUserPremiumDates($uid);
		assert(sizeof($dates) == 1);
		
		if(empty($dates[0]['enddate_pg']))
		{
			self::$endDateOld = GB_TypeFactory::CreateConstant('GB_Date', 'now()');
			return true;
		}
				
		self::$endDateOld = GB_TypeFactory::CreateConstant('GB_Date', $dates[0]['enddate_pg']);
		
		if(time() < self::$endDateOld->value)
		{
			return false;						
		} else {
			return true;
		}
	}
}
?>