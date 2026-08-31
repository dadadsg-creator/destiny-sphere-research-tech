<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для возврата длительности времеи статуса щита которое длиться неограниченно
 *
 */
class GL_PowerShildTimeUnlim
{
	public function Count(DE_UserInstance $userInstance)
	{
		//Это 10 лет в секундах
		return 315360000;
	}
}

?>