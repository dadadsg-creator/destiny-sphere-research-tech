<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/10/19 09:50:22 $';

/** Функция финализатор */
function finilazer()
{			
	
	//Разлачиваем все.
	SU_Locker::UnlockAllInAllObjects();
	
	//Ели работаем из консоли то подчищаем файл сесси за нами
	if(RD_AdeptUserActionToConsole::$commandLineFlag)
	{
		SU_GameSessionWork::SessionDestroy();
	}	
	
	//Формируем статистику	
	GP_SystemStatistic::DeltaParam('USERACTION', 'EXEC_TIME', SU_DebugTools::TimerStop('SCRIPT_START_TIME'));
	GP_SystemStatistic::FormStatistic();
};//END OF FUNCTION

?>