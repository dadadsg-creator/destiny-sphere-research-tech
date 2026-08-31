<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Для силового щита рассчет того как долго длится фаза полной готовности к пуску
 *
 */
class GL_PowerShildTimeReady
{
	public function Count(DE_UserInstance $userInstance)
	{
		//Это 1 час в секундах
		return DE_WorldSettings::CreateForCurentWorld()->get('PS_ACTIVE_WAIT_TIME') * 
			DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
	}
}



?>