<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Просмотр комплекса
 *
 */
class UA_ComplexView extends UA_Action
{
	public $ringID;

	public $ringname;

	public $complexID;

	public $complexnumber;

	public $direction;

	/**
	 * Объект с данными для формирование протокола
	 *
	 * @var TI_ComplexView
	 */
	private $transportInput;

	/**
	 * Время кеширования вида комплекса
	 *
	 * @var int
	 */
	static private $cacheTime = 10800;// 3 часа

	static private $allowedDirection = array('LEFT', 'RIGHT', 'HOME');

	function UA_ComplexView()
	{
		UA_Action::UA_Action();
	}

	/**
	 *  проверяем параметры
	 *
	 * @return bool
	 */
	function checkLocalParams()
	{
		$this->_checkSessionData();

		$this->ringname = $this->root->get_attribute("ring");
		if(!empty($this->ringname))
		{
			assert(preg_match('/^[A-Z_]+$/', $this->ringname));
		}

		$complexnumber = $this->root->get_attribute("complexnum");
		if(!empty($complexnumber))
		{
			$this->complexnumber = (int)$complexnumber;
		}

		$this->direction = $this->root->get_attribute("direction");
		if(!empty($this->direction))
		{
			assert(in_array($this->direction, self::$allowedDirection));
		}

		if(!empty($this->ringname))
		{
			$this->_getCoordinatesFromRingAndComplex();
		} elseif(!empty($this->direction) && $this->direction != 'HOME') {
			$this->_getCoordinatesFromDirection();
		} else {
			$this->_getDefaultCoordinates();
		}

		$_SESSION["complex_ring"] = $this->ringID;
		$_SESSION["complex_complex"] = $this->complexID;
		$_SESSION["complex_complexnumber"] = $this->complexnumber;

		return true;
	}

	/**
	 * выполняем действие, получаем информацию о выбранном комплексе
	 */
	function performAction()
	{
		// разрешен ли просмотр комплекса
		if(DE_WorldSettings::CreateForCurentWorld()->get('DISABLE_COMPLEX_VIEW'))
		{
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "COMPLEX_VIEW_DISABLED", $messparams);
			return true;
		}

		// проверка ограничения на кол-во просмотров
		$this->_checkComplexViewLimit();

		$_SESSION['complexview'] = 1;

		// если нет данных в кеше, то собираем их
		$this->transportInput = DS_Cache::Singleton()->get($this->_getCacheMark());
		if(!($this->transportInput instanceof TI_ComplexView))
		{
			$this->transportInput = new TI_ComplexView();

			// общая инфа
			$this->_getGeneralInfo();

			// кольца
			$this->_getRings();

			DS_Cache::Singleton()->set($this->_getCacheMark(), self::$cacheTime, $this->transportInput);
		} else {
			// Состояние ворот проверяем каждый раз
			if(DE_Comb::CreateFromID($this->comb)->haveOpenCentralGate())
			{
				$this->transportInput->openGate = 1;
			} else {
				$this->transportInput->openGate = 0;
			}
		}

		// соты
		$this->_getCombs();

		// инфа, которая не кешируется
		$this->_getUserSpecificData();

		// формирование XML
		$data = TL_ComplexView::Form($this->transportInput);

		SU_OutputHandler::Singleton()->addString($data, 'xmldata');

		return true;
	}

	/**
	 * Метка кеша
	 *
	 * @return string
	 */
	private function _getCacheMark()
	{
		return 'c_view_'.$_SESSION['dbId'].'_'.$this->world.'_'.$this->ringID.'_'.$this->complexID;
	}

	static public function ResetCache($worldID, $ringID, $complexID)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($ringID) && $ringID > 0);
		assert(is_int($complexID) && $complexID > 0);

		$cacheMark = 'c_view_'.$_SESSION['dbId'].'_'.$worldID.'_'.$ringID.'_'.$complexID;

		DS_Cache::Singleton()->delete($cacheMark);
	}

	/**
	 * Сбор данных в сессии
	 *
	 */
	private function _checkSessionData()
	{
		// в сессии хранятся текущие координаты просмотра
		if(key_exists("complex_ring", $_SESSION))
		{
			$this->ringID = $_SESSION["complex_ring"];
		} else {
			$this->ringID = $this->ring;
		}

		if(key_exists("complex_complex", $_SESSION))
		{
			$this->complexID = $_SESSION["complex_complex"];
		} else {
			$this->complexID = $this->combcomplex;
		}

		if(key_exists("complex_complexnumber", $_SESSION))
		{
			$this->complexnumber = $_SESSION["complex_complexnumber"];
		} else {
			$this->complexnumber = DS_CombComplex::GetComplexNumberByID($this->combcomplex);
		}
	}

	/**
	 * Выборка комплекса по номеру и кольцу
	 *
	 */
	private function _getCoordinatesFromRingAndComplex()
	{
		// получаем id кольца
		$this->ringID = DS_CombComplex::GetRingIDByRingNameAndWorldID($this->ringname, $this->world);

		// если есть номер комплекса, куда перейти
		if($this->complexnumber != 0)
		{
			$this->complexID = DS_CombComplex::GetComplexIDByRingIDAndComplexNumber($this->ringID, $this->complexnumber);
			if($this->complexID == 0)
			{
				$minComplex = DS_CombComplex::GetComplexIdWithMinNumberOnRing($this->ringID);
				$this->complexID = (int)$minComplex['id_combcomplex'];
				$this->complexnumber = (int)$minComplex['complexnumber'];
			}
		} else { // не указан комплекс
			$minComplex = DS_CombComplex::GetComplexIdWithMinNumberOnRing($this->ringID);
			$this->complexnumber = $minComplex['complexnumber'];
			$this->complexID = $minComplex['id_combcomplex'];
		}
	}

	/**
	 * Выборка комплекса по направлению перемещения (влево, вправо)
	 *
	 */
	private function _getCoordinatesFromDirection()
	{
		$maxComplex = DE_Ring::CreateFromID((int)$this->ringID)->getComplexCount();

		$complex = 1;

		switch($this->direction)
		{
			case "LEFT":
				if(($this->complexnumber - 1) <= 0)
				{
					$complex = $maxComplex;
				} else {
					$complex = $this->complexnumber - 1;
				}
				break;

			case "RIGHT":
				if(($this->complexnumber + 1) > $maxComplex)
				{
					$complex = 1;
				} else {
					$complex = $this->complexnumber + 1;
				}
				break;
		}

		$this->complexnumber = $complex;
		$this->complexID = DS_CombComplex::GetComplexIDByRingIDAndComplexNumber($this->ringID, $complex);
	}

	/**
	 * Домашний комплекс
	 *
	 */
	private function _getDefaultCoordinates()
	{
		$this->complexID = $this->combcomplex;
		$this->ringID = $this->ring;
		$this->complexnumber = DS_CombComplex::GetComplexNumberByID($this->complexID);
	}

	/**
	 * Защита от черезмерного просомтра комплексов
	 *
	 */
	private function _checkComplexViewLimit()
	{
		isset($_SESSION["ComplexViewed"]) ? $_SESSION["ComplexViewed"]++  : $_SESSION["ComplexViewed"] = 1;

		if(isset($_SESSION['profi_game']))
		{
			$viewLimit = 300;
		} else {
			$viewLimit = 300;
		}

		if($_SESSION["ComplexViewed"] > $viewLimit)
		{
			SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"index_start.php\"/>", 'loadkey');
			echo SU_OutputHandler::Singleton()->fetchAll();
			$GLOBALS['CorrectExit'] = 1;
			exit;
		}
	}

	/**
	 * Общая инфа: кольцо, комлекс ..
	 *
	 */
	private function _getGeneralInfo()
	{
		$ring = DS_CombComplex::GetRingInfo($this->ringID);
		assert(is_array($ring));
		assert(key_exists('text', $ring));
		assert(key_exists('ringnumber', $ring));

		$this->transportInput->ringName = $ring['text'];
		$this->transportInput->complexNumber = $this->complexnumber;
		$this->transportInput->ringNumber = (int)$ring['ringnumber'];
		if(DE_Comb::CreateFromID($this->comb)->haveOpenCentralGate())
		{
			$this->transportInput->openGate = 1;
		}
	}

	/**
	 * Информация по сотам в комплексе
	 *
	 */
	private function _getCombs()
	{
		$combs = DS_CombComplex::GetComplexCombs($this->complexID);

		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());

		foreach ($combs as $k => $comb)
		{
			$combData = array();
			$combOb = DE_Comb::CreateFromID((int)$comb['id_comb']);

			$combData['num'] = (int)$comb['combnumber'];
			$combData['cscore'] = (int)$comb['combscore'];
			$combData['id_userinstance'] = (int)$comb['id_userinstance'];
			$combData['home'] = 0;
			$combData['league'] = '';

			if(!empty($comb['combname']))
			{
				$combData['cname'] = $comb['combname'];
			}

			// Захватываемая колония
			$combData['captured'] = 0;
			$capted = $combOb->getIsCapture();
			if ($capted) {
				$combData['captured'] = 1;
				// Владелец DestinySphere
				if ($comb['id_userinstance'] == 0) {
					$combData['uname'] = 'DestinySphere';
					$combData['avatar'] = '';
					$combData['newbee'] = 0;
					$combData['gate'] = 1;
					$this->transportInput->combs[] = $combData;
					continue;
				}
			}

			///////////////////////////////////////////////

			// Если есть у соты владелец, то собираем по нему информацию
			if(empty($comb['id_userinstance']))
			{
				$this->transportInput->combs[] = $combData;
				continue;
			}

			$userInstance = DE_UserInstance::CreateFromID((int)$comb['id_userinstance']);

			$combData['uname'] = $userInstance->getUser()->getName();

			if($userInstance->getUser()->getImage() != '' && file_exists($_SERVER['DOCUMENT_ROOT'].$userInstance->getUser()->getImage()))
			{
				$combData['avatar'] = $userInstance->getUser()->getImage();
			} else {
				$combData['avatar'] = '';
			}

			$combData['race'] = $userInstance->getRaceID();

			if($userInstance->haveNewbDefend())
			{
				$combData['newbee'] = 1;
			} else {
				$combData['newbee'] = 0;
			}

			// Временная защита захватываемой колонии
			if($combOb->getIsCapture())
			{
				$defend = DE_CombProtectiveField::CreateFromCombID((int)$comb['id_comb']);
				if ($defend->getTemporaryDefend()->value > time())
				{
					$combData['newbee'] = 1;
				}
			}

			if($combOb->haveOpenCentralGate())
			{
				$combData['gate'] = 1;
			}

			// Смотрим, забанен ли игрок
			if($userInstance->getUser()->getAccountActive() == 'f' || $userInstance->getUser()->getPremiumBan()->dateInPeriod($checkDate))
			{
				$combData['cban'] = 1;
			}

			// Смотрим, нет ли у игрока активации премиум-бана
			if($userInstance->getUser()->getPremiumBan()->getStartDate()->value > time())
			{
				$combData['godpro'] = ($userInstance->getUser()->getPremiumBan()->getStartDate()->value-time())*1000;
			}

			if($userInstance->getUser()->isProfy())
			{
				$combData['premic'] = 1;
			}

			if(GL_IsUserInVocationMode::Test($userInstance) || DE_PowerShild::CreateFromUserInstance($userInstance->getID())->isWorks())
			{
				$combData['freez'] = 1;
			}

			$combData['league'] = DE_League::CreateFromID((int)$userInstance->getLeagueId())->getLeagueName();
			
			if (constant('VERSION_NAME') == 'VKONTAKTE') {
				$idVk = DS_User::GetIDVkontakteByUserId($userInstance->getUser()->getID());
				$combData['VKUID'] = $idVk;	
			}
			
			// Если это нпс игрок
			if($userInstance->getUser()->getIsUserNpc() == 't')
			{
				$combData['npc'] = 1;
			}
			//$combData['captured'] = 0;
			
			// $combData['sms'] = 1;

			////////////////////////////////////////////////

			// если есть альянс, то и по нему берем инфу
			$alliance = $userInstance->getAlliance();
			if($alliance instanceof DE_AllianceEmpty)
			{
				$this->transportInput->combs[] = $combData;
				continue;
			}

			$combData['alliance'] = $alliance->getShortName();
			$combData['id_alliance'] = $alliance->getID();

			if($alliance->getImage() != '' && file_exists($_SERVER['DOCUMENT_ROOT'].$alliance->getImage()))
			{
				$combData['urlpic'] = $alliance->getImage();
			}
			
			$this->transportInput->combs[] = $combData;
		}
	}

	/**
	 * Информация по кольцам
	 *
	 */
	private function _getRings()
	{
		$rings = DS_CombComplex::GetAllRings($this->world);

		$this->transportInput->rings = $rings;
	}

	/**
	 * Данные которые могут различаться для разных пользователей, живущих в одном комплексе
	 * типа домашняя ли сота и 
	 *
	 */
	private function _getUserSpecificData()
	{
		$userAlliance = DE_UserInstance::CreateFromID($this->uiid)->getAlliance();

		if($userAlliance instanceof DE_AllianceEmpty )
		{
			$allianceID = -1;
		} else {
			$allianceID = $userAlliance->getID();
		}

		foreach ($this->transportInput->combs as $k => $comb)
		{
			assert(key_exists('id_userinstance', $comb));
			if($comb['id_userinstance'] == $this->uiid)
			{
				$this->transportInput->combs[$k]['home'] = 1;
			}

			if(key_exists('id_alliance', $comb) && $comb['id_alliance'] == $allianceID)
			{
				$this->transportInput->combs[$k]['samealliance'] = 1;
			}
		}
	}
}

?>