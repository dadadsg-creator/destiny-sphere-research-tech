<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Дейсвтие начала режима отпуска
 *
 */

class UA_VacationStop extends UA_Action
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
	 * @var DE_UserInstance
	 */
	private $userInstance;
	
	/**
	 * Текущее время
	 *
	 * @var GB_Date
	 */
	private $nowDate;

	/**
	 * Времф начала отпуска
	 *
	 * @var GB_Date
	 */
	private $startDate;
	
	/**
	 * Минимальнео время отпуска в секундах
	 *
	 * @var integer
	 */
	private $minVocationTime = 0;
	
	
	/**
	 * Флаг того что пароль неверен
	 *
	 * @var unknown_type
	 */
	private $invalidPassFlag = false;
	
	public function __construct()
	{
		UA_Action::UA_Action ();
		
		$this->userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
		
		$this->nowDate = GB_TypeFactory::CreateBase('GB_Date', time());	
		
		$this->minVocationTime = DE_WorldSettings::CreateForCurentWorld()->get('MIN_VACATION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
	}
	
	public function checkLocalParams() 
	{
		$this->password = (string)$this->root->get_attribute("password");						
		assert(strlen($this->password) <= 12);
		
		return true;
	}
		
	public function checkAction ()
	{
		$vacationDate = GL_IsUserInVocationMode::Test($this->userInstance);
		
		//Проверка находиться ли сейчас в режиме отпуска		
		assert(count($vacationDate) > 0);				
		
		$this->startDate = GB_TypeFactory::CreateBase('GB_Date', $vacationDate['startdate']);
		
		//Проверка пароля
		if($vacationDate['password'] != $this->password)
		{
			$this->invalidPassFlag = true;
		}
			
		//Проверка истечения мин срока		
		assert(($this->startDate->value + $this->minVocationTime) < $this->nowDate->value);
		
		return true;
	}
	
	public function performAction()
	{
		//Если указали неверный пароль то поcылаем
		if($this->invalidPassFlag)
		{
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'VACATION_WRONG_PASS', $messparams, 0);
		
			return true;
		}
		
		//Лочин все что можеи м неможем
		/*if(SU_Locker::CerateForCurrentDB('REFRESH')->isLocked($_SESSION["uiid"]))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $_SESSION["uiid"], 'REFRESH');
		}else{
			SU_Locker::CerateForCurrentDB('REFRESH')->lock($_SESSION["uiid"]);
		};	*/		
				
		
		//Действия по разморозки пользователей
		ME_UnFreezUser::Exec($this->userInstance, $this->startDate, $this->nowDate);
		
		//Завершаем режим отпуска
		DS_Freez::EndVacationMode($this->userInstance);
		
		//Удаляем лицензию на строительство домов отдыха
		DS_Freez::DeleteVacationLicenzion($this->userInstance);
		
		//Удаляем здание дома отдыха
		DS_Freez::DeleteVacationBuilding($this->userInstance);

		//Сбрасываем вид домиков в нейтрали на всех сотах
		$combCollection = DC_UserInstanceCombs::CreateFromUserInstanceID($this->userInstance->getID());		
		while($comb = $combCollection->next())
		{
			CR_CombView::CreateFromCombID($comb->getID())->resetBuildings(3);	
		}
		
		//Пишем мессагу
		$messparams = array();
		DE_SystemMessages::AddMessageFromAction(__CLASS__, 'VACATION_STOP', $messparams, 0);	
		
		if(!$this->refresh(2, "", 1, 1))
		{
			return false;
		}
		
		return true;
	}	
}

?>