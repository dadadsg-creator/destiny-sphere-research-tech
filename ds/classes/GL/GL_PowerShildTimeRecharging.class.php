<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Время необходимое щиту на перезарядку.
 *
 */

class GL_PowerShildTimeRecharging
{
	public function Count(DE_UserInstance $userInstance)
	{
		$powerShild = DE_PowerShild::CreateFromUserInstance($userInstance->getID());


		// Смотрим, есть ли у игрока захваченная колония
		$usersColonies = DS_Comb::GetCombsByUserInstance($userInstance->getID());

		$usersHasCapCol = 0;
		foreach ($usersColonies as $key => $data)
		{
			if ($data['is_capture'] == 't') {
				$usersHasCapCol = 1;
			}
		}

		if ($usersHasCapCol == 1) {
			$delta = DE_WorldSettings::CreateForCurentWorld()->get('CAPTURE_COLONY_PS_RECHARGE_TIME') *
			DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		} else {

			$delta = DE_WorldSettings::CreateForCurentWorld()->get('PS_RECHARGE_DECRIMENT') *
			DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;

		}

		if($powerShild->getLastWorkLength() > $delta)
		{
			$rechargeTime = $powerShild->getLastWorkLength() - $delta;
		}else{
			$rechargeTime = 1;
		}

		settype($rechargeTime, 'integer');
		assert($rechargeTime > 0);

		return $rechargeTime;
	}
}

?>