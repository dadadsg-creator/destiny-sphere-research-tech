<?php

/**
 * Класс для общего управления турнирами
 *
 */
final class TN_TurnirController
{
/***************************************************/
/*       ОБЩИЕ НАСТРОЙКИ ТУРНИРОВ                  */
/*         менять только их надо	 		   	   */
/***************************************************/

	/**
	 * Хост турнира
	 *
	 * @var string
	 */
	static private $turnirHost = 'http://turnir.destinysphere.ru';
	//static private $turnirHost = 'http://unlim';

	/**
	 * ИД турнирной базы
	 *
	 * @var int
	 */
	static private $turnirDbID = 3;
	
	/**
	 * ИД турнирного мира
	 *
	 * @var int
	 */
	static private $turnirWorldID = 4;
	
	/**
	 * набор шаблонов с настройками
	 *
	 * @var array
	 */
	static private $turnirTemplates = array();
		
/************* КОНЕЦ НОСТРОЕК ТУРНИРОВ ************/
	
	/**
	 * объект для работы с базой
	 * 
	 * @var DS_Common
	 */
	static private $common;

	/**
	 * возвращает DS_Common, подключеный к турнирной базе
	 *
	 * @return DS_Common
	 */
	static public function GetCommon()
	{
		if(!self::$common instanceof DS_Common)
		{
			self::$common = DS_Common::CreateForCurentDB(self::$turnirDbID);
		}
		
		self::$common->setDbId(self::$turnirDbID);
		
		return self::$common;
	}
	
	static public function GetDBID()
	{
		return self::$turnirDbID;
	}
	
	static public function GetWorldID()
	{
		return self::$turnirWorldID;
	}
	
	static public function GetTemplates()
	{
		//if(empty(self::$turnirTemplates))
		//{
			$templates = TN_Search::GetTemplates();
			foreach ($templates as $k => $t)
			{
				self::$turnirTemplates[(int)$t['id_turnir_template']] = $t;
			}
		//}

		return self::$turnirTemplates;
	}
	
	static public function GetHost()
	{
		return self::$turnirHost;
	}
	
	/**
	 * Применить набор настроек
	 *
	 * @param string $id
	 */
	static public function ApplySettings($id)
	{
		assert(key_exists($id, self::GetTemplates()));
		
		$_SESSION['world'] = TN_TurnirController::GetWorldID();
		$_SESSION['dbId'] = TN_TurnirController::GetDBID();
		
		$templateName = self::$turnirTemplates[$id]['class_name'];
		$template = new $templateName();
		
		TN_SettingsManager::ApplyTemplate($template);
	}
	
	/**
	 * Создать новый турнир
	 *
	 * @param unknown_type $template
	 * @param unknown_type $comments
	 */
	static public function CreateNewTurnir($template, $comments = '')
	{
		self::GetCommon()->startTrans();
		
		TN_Search::InsertNewTurnir($template, $comments);
		
		// self::ApplySettings($template);
		
		self::GetCommon()->closeTrans('COMMIT');
	}
	
	/**
	 * Начать турнир
	 *
	 */
	static public function StartTurnir()
	{
		TN_StartManager::Start();
	}
	
	/**
	 * Остановить турнир
	 *
	 */
	static public function StopTurnir()
	{
		TN_StopManager::Stop();
	}
	
	/**
	 * Поставить на паузу
	 *
	 */
	static public function PauseTurnir()
	{
		TN_PauseManager::Pause();
	}
	
	/**
	 * Обновить рейтинг
	 *
	 */
	static public function UpdateRanking()
	{
		$updater = new SU_UpdateRanking(self::GetDBID());
		$updater->Update();		
	}
}

?>