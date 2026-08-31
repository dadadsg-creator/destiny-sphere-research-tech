<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * TEST TEST TEST TEST
 *
 */
class GE_ExecGetPremiumPresent
{
	static public function Get(DE_User $user, $days = null)
	{	
		assert(is_numeric($days) || is_null($days));
		
		if(is_null($days))
		{
			$days = constant('PREMIUM_PRESENT_DAYS');
		}
					
		if(GE_LogicGetPremiumPresent::CheckIfPresentIsReceived($user))
		{
			return;
		}
		
		// Если игрок еще не брал 5 дней премума в подарок на 5тиление сферы
		if (!GE_LogicGetPremiumPresent::CheckIfPresentForDRIsReceived($user->getID())) {
			$days = 5;
		}
		
		self::addPremiumDays($user,$days);
		self::setPresentStatusToReceived($user,$days);
	}
	
	static public function addPremiumDays(DE_User $user, $days)
	{
		assert(is_numeric($days));
		
		return PU_ExecChangePremiumDays::Exec($user, $days, 'INDEX_START', 'trial_days');
	}
	
	static public function setPresentStatusToReceived(DE_User $user,$days)
	{
		$id_user = $user->getID();
		assert(is_numeric($days));
		
		$common = DS_Common::CreateForCurentDB(0);	

		/*$sql = "   
			SELECT
				count(*)
			FROM
				ds_present
			WHERE
				id_user = ".$id_user."
				AND
				type = 2
			";
		$res = $common->getResultArray("", $sql);
		assert(empty($common->errormsg));
		
		if($res[0]['count'] > 0)
		{
			$sql = "
					UPDATE
						ds_present
					SET
						add_date = now()
					WHERE
						id_user = ".$id_user."						
				";
		} else {*/
			$sql = "
					INSERT INTO ds_present
							(id_user, days, type)
					VALUES
      						(".$id_user.", ".$days.", 2);
      			";
		/*}*/
			
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		return true;
	}
	
	// Версия когда все параметры храняться в базе
	static public function GetPremPresent(DE_User $user, $days = null)
	{	
		assert(is_numeric($days) || is_null($days));
		
		if(is_null($days))
		{
			$days = constant('PREMIUM_PRESENT_DAYS');
		}
					
		if(GE_LogicGetPremiumPresent::CheckIfPresentIsReceived($user))
		{
			return;
		}
	
		self::addPremiumDays($user,$days);
		self::setPresentStatusToReceived($user,$days);
	}
	
	// Версия когда все параметры храняться в базе
	static public function GetPremPresentVk($userIdVkontakte)
	{
		assert(is_int($userIdVkontakte) && $userIdVkontakte > 0);
		$_SESSION["dbId"] = 0;
		
		$common = DS_Common::CreateForCurentDB(0);
		GE_SearchStart::SetCommon($common);

		// Сморим, есть ли в базе у нас игрок с таким
		$userId = (int)DS_User::GetByIDVkontakte($userIdVkontakte);
		assert($userId > 0);

		$user = DE_User::CreateFromID($userId);

		// Если игрок не премиум
		if ($_SESSION['profi_game'] == 0) {
			// Если игрок попадает под дату регистрации, которой даем прем
			if (GE_SearchStart::CanUserGetPremPresent($user)) {
				// если игрок уже не взял свой прем
				if (!GE_SearchStart::IfPremPresentIsReceived($user)) {
					// сколько дней премиума даем
					$days = GE_SearchStart::GetPremPresentCount();
					if ($days != 0 ) {
						self::addPremiumDays($user, $days);
						self::setPresentStatusToReceived($user, $days);

						$msg = 'Вы получили в подарок '.$days.' дней премиума';

						echo 'report=<?xml version="1.0" encoding="UTF-8"?>
<ret success="true" msg="'.iconv('cp1251', 'utf8', $msg).'" cmd="getfree"/>
';	
						return;
					}
				}
			}
		}
		
$msg = 'У вас уже есть подарок';

						echo 'report=<?xml version="1.0" encoding="UTF-8"?>
<ret success="true" msg="'.iconv('cp1251', 'utf8', $msg).'" cmd="getfree"/>
';	
	}
}

?>