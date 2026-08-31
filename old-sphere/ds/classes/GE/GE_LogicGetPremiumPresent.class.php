<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_LogicGetPremiumPresent
{
	static public function checkUserForPresent(DE_User $user)
	{
		if (self::CheckIfPresentIsReceived($user))
		{
			return 'PRESENT_RECEIVED';
		} elseif (!self::CheckIfUserHavePremiumRegistration($user)) {
			return 'PREMIUM_REGISTRATION_NEEDED';
		} else {
			return 'USER_OK';
		}
	}
	
	static public function CheckIfPresentIsReceived(DE_User $user)
	{
		$id_user = $user->getID();
		
		$common = DS_Common::CreateForCurentDB(0);	
		
		$sql = "
				SELECT
      				count(*)
				FROM
    				ds_present
				WHERE
     				id_user = ".$id_user."
     				AND
     				type = 2
     				AND
     				add_date > now() - '".constant('PREMIUM_PRESENT_CAN_TAKE_AFTER')." MONTH'::interval
     			";
		$res = $common->getResultArray("", $sql);
		assert(empty($common->errormsg));
		
		if($res[0]['count'] != 0)
		{
			return true;
		}
			
		return false;
	}	
	
	// Функция для затыки према на 5ти летие, возвращает, брал ли человек прем на 5 дней
	static public function CheckIfPresentForDRIsReceived($id_user)
	{
		assert($id_user > 0);
		
		$common = DS_Common::CreateForCurentDB(0);	
		
		$sql = "
				SELECT
      				count(*)
				FROM
    				ds_present
				WHERE
     				id_user =  ".$id_user."
     				AND
     				type = 2
     				AND
                    days = 5
                    AND
     				add_date > '2010-09-01 00:00:00'
     			";
		$res = $common->getResultArray("", $sql);
		assert(empty($common->errormsg));
		
		if($res[0]['count'] != 0)
		{
			return true;
		}
			
		return false;
	}	
	
	static public function CheckIfUserHavePremiumRegistration(DE_User $user)
	{
		$id_user = $user->getID();
		$common = DS_Common::CreateForCurentDB(0);	
		
		$sql = "SELECT
				    count(*)
				FROM
				    ds_userprofidata
				WHERE
				    id_user = ".$id_user;
		
		$res = $common->getResultArray("", $sql);
		assert(empty($common->errormsg));
		
		if($res[0]['count'] != 0)
		{
			return true;
		}
			
		return false;
	}	
}

?>