<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Дейсвтие начала режима отпуска
 *
 */

class UA_VacationStart extends UA_Action
{	
	/**
	 * Пароль для страта режим аотпуска
	 *
	 * @var string
	 */
	private $password;
	
	/**
	 * Объект инстанса игрока
	 *
	 * @var DE_UserINstance
	 */
	private $userInstance;
	
	public function __construct()
	{
		UA_Action::UA_Action ();
		
		$this->userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
	}
	
	public function checkLocalParams() 
	{
		$this->password = $this->root->get_attribute("password");						
		assert(strlen($this->password) <= 12);		
		
		return true;
	}
		
	public function checkAction ()
	{		
		$vocationResult = GL_IsUserInVocationMode::Test($this->userInstance);		
		assert(count($vocationResult) == 0);
		
		if(!GL_CanFreez::Test($this->userInstance))
		{
			ds_error("You cant start vacation mode. Becouse you have battles on your comb.");
		}
		
		return true;
	}
	
	public function performAction()
	{		
		//Лочин все что можеи м неможем
		/*if(SU_Locker::CerateForCurrentDB('REFRESH')->isLocked($_SESSION["uiid"]))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $_SESSION["uiid"], 'REFRESH');
		}else{
			SU_Locker::CerateForCurrentDB('REFRESH')->lock($_SESSION["uiid"]);
		};	*/		
		
		
		
		//Совершаем действия по заморозки игрока
		ME_FreezUser::Exec($this->userInstance);
		
		// Отбираем временные колонии
		$usersColonies = DS_Comb::GetCombsByUserInstance($this->uiid);
		foreach ($usersColonies as $key => $data)
		{
			if ($data['is_capture'] == 't') {
				// ОПокидаем колонию
				ME_CaprureColony::Leave(DE_Comb::CreateFromID((int)$data['id_comb']));
			}
		}
		
		//Старттуем режим отпуска
		DS_Freez::StartVacationMode($this->userInstance, $this->password);

		//Пишем мессагу
		$messparams = array();
		DE_SystemMessages::AddMessageFromAction(__CLASS__, 'VACATION_START', $messparams, 0);	
		
		//Запрещаем удаление здания после нчала процесса отпуска
		$attachedBuildingData = DS_Building::GetAttachedBuildingByCombIDAndBuildingID($this->comb, 65);
		assert(count($attachedBuildingData) == 1);		
		

		assert(key_exists('id_attachedbuilding', $attachedBuildingData[0]));
		$attachedBuildingID = (int)$attachedBuildingData[0]['id_attachedbuilding'];
		assert($attachedBuildingID > 0);
		
		DS_Building::DeleteAttachedBuildingAttribute($attachedBuildingID, 'CURRENT_FLAG', 'DESTROYABLE');
		
		//Просто вид чоты + ресы
		if(!$this->refresh(2, "", 1, 1))
		{
			return false;
		}
		
		return true;
	}	
}

?>