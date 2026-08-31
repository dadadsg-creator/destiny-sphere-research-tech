<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Клаасс логики опредлеящий находиться ли человек в режиме отпуска.
 * кели да то возвращает массив с данным об отауске.
 *
 */

class GL_IsUserInVocationMode
{	
	/**
	 * Кеш опредлений в режиме отпуска ли пользователь
	 *
	 * @var unknown_type
	 */
	static private $cache = array();	
	
	static public function Test(DE_UserInstance $userInstance, $chekDate = false)	
	{
		if(RD_AdeptUserActionToConsole::$prepareTransfer)
		{
			return array();
		}
		
		// проверяем не мир ли это трупиков
		assert(key_exists('world', $_SESSION));
		if('FROZEN' == DS_World::GetWorldStatus((int)$_SESSION['world']))
		{
			$arr = array(
					'id_userinstance' 	=> $userInstance->getID(),
					'start_date' 		=> date('d-m-Y H:i:s'),
					'end_date'			=> '',
					'password'			=> ''
				);
			return $arr;
		}
		
		// Проверяем на паузу
		$pauseEndDate = DS_Turnir::GetCurentPause((int)$_SESSION['world'], $chekDate);
		if(count($pauseEndDate) > 0)
		{			
			return 	array('pause' => $pauseEndDate);
		}
		
		if(!key_exists($userInstance->getID(), self::$cache))
		{
			self::$cache[$userInstance->getID()] = 	DS_Freez::GetNotEndedVocations($userInstance);
		}
				
		return self::$cache[$userInstance->getID()];
	}
	
	static public function ResetCache(DE_UserInstance $userInstance)
	{
		unset(self::$cache[$userInstance->getID()]);
	}
}

?>