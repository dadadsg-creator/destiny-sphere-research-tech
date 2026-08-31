<?php $CVS_Version = '$Revision$ ; $Date$';
// Подключаем на всякий случай предка данного класса


/**
 * Метод экшена для принятия религии
 *
 * В результате своей работы генерит Ивент EH_ReligionConnect,
 * Устанавливает в БД связку инстанса пользователя с религией
 *
 *
 * Использует:
 *  - Являеться потомком UA_Action
 */

class UA_ReligionConnect extends UA_Action {

	/** ID религии которой принимаем */
	public $religionId = 0;

	/** ID статуса религии */
	public $statusId = 0;

	/** Массив с возможными ошибками при проверки допустимости действия */
	public $actionErrors = array(
		//пользователь уже имеет религию в принятие.
		'HAVE_RELIGION_IN_CONNECT' => false,
		//пользователь еще не закончил отказ от другой религии.
		'HAVE_RELIGION_IN_CANCEL' => false,
		//пользователь уже имеет религию.
		'HAVE_RELIGION' => false,
		//передан ID несуществующей религии
		'UNCORRECT_RELIGION_ID' => false,
		//Некорректный атрибут состояния религии
		'UNCORRECT_RELIGION_STATUS' => false,
		//для принятия религии нет необходимого храма
		'DONT_HAVE_TEMPLE' => false,
		//В данный момент имеються ивенты приема или отмена религии запущеннывми
		'HAVE_RELIGION_EVENTS' => false,
		//В данный момент религия активна
		'RELIGION_STATUS_ACTIVE' => false
	);

	/** Конструктор класса */
	function UA_ReligionConnect () {

		UA_Action::UA_Action ();

		$this->setReligionId( $this->root->get_attribute( "religy" ) );

	}//END OF METHOD UA_ReligionConnect ()

	/**
	 * Метод для проверки целостности класса
	 *
	 * Провряет атрибуты класса на корректность.
	 * Предназначен для использования в начале и конце каждого метода
	 *
	 * Параметры:
	 * $line - нлмер строки откуда был вызван метод
	 */
	function invariant ( $file, $line ){

		assert ( !empty ( $file ) );
		settype ( $line, 'integer' );
		assert ( $line > 0 );

		/** Вызваем родительский варинт */
		if ( !parent::invariant ( $file, $line ) ){
			return false;
		};

		//Проверка корректности ID религии
		settype ( $this->religionId, 'integer' );
		if ( $this->religionId <= 0  ) {
			$this->errormsg = $this->invariantError ( __FILE__, $file, $line, '1-1' );
			return false;
		}

		$sql = "SELECT *
				FROM ds_religion
				WHERE
					id_religion = ".$this->religionId;
		$rs = $this->common->getResultArray ( "RELIGION_BY_ID_".$this->religionId,
								$sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

		assert ( empty ( $this->common->errormsg ) );
		if ( !$rs ) {
			$this->errormsg = $this->invariantError ( __FILE__, $file, $line, '1-2' );
			return false;
		}

		//Проверям массив для ошибюок
		if ( empty ( $this->actionErrors ) ) {
			$this->errormsg = $this->invariantError ( __FILE__, $file, $line, '2-1' );
			return false;
		}

		if ( !is_array ( $this->actionErrors ) ) {
			$this->errormsg = $this->invariantError ( __FILE__, $file, $line, '2-2' );
			return false;
		}

		if ( count ( $this->actionErrors ) == 0 ) {
			$this->errormsg = $this->invariantError ( __FILE__, $file, $line, '2-3' );
			return false;
		}

		return true;
	}//END OF METHOD invariant ()


	/**
	 * Устанавливаем значение ID религии
	 * Параметры:
	 * $id - целочисленное ID религии не равный 0
	 */
	function setReligionId ( $id ){
		settype ( $id, 'integer' );
		$this->religionId = $id;
		assert ( $this->religionId > 0 );
		return true;
	}//END OF METHOD setIdReligion($id)

	/**
	 * Проверка входных параметров
	 * ВСя логика отсюда была перенесена в конструтор
	 */
	function checkLocalParams () {
		if ( !$this->invariant ( __FILE__,  __LINE__ ) ) {
			return false;
		};

		return true;
	}//END OF METHOD checkLocalParams ()

	function makeEventParamsLog( $sql='' )
	{

	}

	/**
	 * Устанавливает рошибку в массив $this->actionErrors
	 *
	 * Параметры:
	 * $key - ключь в массиве
	 * $val - значение устаналиваемое
	 */
	function setActionErrors ( $key, $val ) {
		assert ( !empty ( $key ) );
		assert ( !empty ( $val ) );
		assert ( is_bool ( $val ) );
		assert ( !empty ( $this->actionErrors ) );
		assert ( is_array ( $this->actionErrors ) );
		assert ( array_key_exists ( $key, $this->actionErrors ) );
		settype( $val, 'boolean' );
		$this->actionErrors[$key] = $val;
		return true;
	}//END OF METHOD setActionErrors($key, $val)

	/**
	 * Берем  ошибку из массива $this->actionErrors
	 *
	 * Параметры:
	 * $key - ключь в массиве
	 */
	function getActionErrors ( $key ) {
		assert ( !empty ( $this->actionErrors ) );
		assert ( is_array ( $this->actionErrors ) );
		assert ( array_key_exists ( $key, $this->actionErrors ) );
		settype( $this->actionErrors[$key], 'boolean' );
		return $this->actionErrors[$key];
	}//END OF METHOD getActionErrors($key)

	/**
	 * Проверяем допустимость действия
	 */
	function checkAction ()
	{
		if ( !$this->invariant ( __FILE__,  __LINE__ ) ) {
			return false;
		};

		//Существует ли вобще религия с такми ID
		$sql = "SELECT *
				FROM ds_religion
				WHERE id_religion = ".$this->religionId;
		$rs = $this->common->getQueryArray ( $sql );
		assert ( empty ( $this->common->errormsg ) );
		if(!$rs){
			$this->setActionErrors ( 'UNCORRECT_RELIGION_ID', true );
		}

		//Есть ли у человека необходимые здания для принятия религии
		$sql = "
			SELECT
			      *
			FROM
			    ds_attachedbuilding AS ab
			    INNER JOIN  ds_religionneedsbuilding AS rnb ON (rnb.id_building = ab.id_building),
			    (
			     SELECT
			           id_comb
			     FROM
			         ds_comb c
			     WHERE
			          c.id_userinstance = ".$this->uiid."
			    ) as co

			WHERE
			     rnb.id_religion = ".$this->religionId."
			     AND ab.id_comb = co.id_comb;";

		$rs = $this->common->getQueryArray ( $sql );
		assert ( empty ( $this->common->errormsg ) );

		if( !is_array ( $rs ) )
		{
			$this->setActionErrors ( 'DONT_HAVE_TEMPLE', true );
		}

		//Принята ли сейчас у человека религия
		$sql = "SELECT id_status
				FROM
					ds_userhasreligion
				WHERE
					id_userinstance = ".$this->uiid;
//					id_religion = ".$this->religionId." AND

		$rs = $this->common->getQueryArray ( $sql );
		assert ( empty ( $this->common->errormsg ) );

		if (empty($rs[0]['id_status']) )
		{
			$this->statusId = 0;
		}else{
			$this->statusId = $rs[0]['id_status'];
		}



		if ( $this->statusId != 0 ) {
			switch( $this->statusId ){
				case 1:
					$this->setActionErrors ( 'HAVE_RELIGION_IN_CONNECT', true );
					break;
				case 2:
					$this->setActionErrors ( 'RELIGION_STATUS_ACTIVE', true );
					break;
				case 3:
					$this->setActionErrors ( 'HAVE_RELIGION_IN_CANCEL', true );
					break;
				default:
					$this->setActionErrors ( 'UNCORRECT_RELIGION_STATUS', true );
					break;
			}
		}

		if( !$this->invariant ( __FILE__, __LINE__ ) ) {
			return false;
		};

		return true;
	}//END OF METHOD checkAction ()

	/**
	 * Выполняем действия по реализации экшена
	 */
	function performAction () {

		if ( !$this->invariant ( __FILE__, __LINE__ ) ) {
			return false;
		};

		//Обрабатываем сиситемыне ошибки
		if ( $this->getActionErrors ( 'HAVE_RELIGION_IN_CONNECT' ) ) {
			$this->errormsg = "ERROR! UA_ReligionConnect: ".
							  "HAVE_RELIGION_IN_CONNECT";
			return false;
		}

		if ( $this->getActionErrors ( 'HAVE_RELIGION_IN_CANCEL' ) ) {
			$this->errormsg = "ERROR! UA_ReligionConnect: ".
							  "HAVE_RELIGION_IN_CANCEL";
			return false;
		}

		if ( $this->getActionErrors ( 'HAVE_RELIGION' ) ) {
			$this->errormsg = "ERROR! UA_ReligionConnect: ".
							  "HAVE_RELIGION";
			return false;
		}

		if ( $this->getActionErrors( 'DONT_HAVE_TEMPLE' ) ) {
			$this->errormsg = "ERROR! UA_ReligionConnect: DONT_HAVE_TEMPLE";
			return false;
		}

		if ( $this->getActionErrors( 'UNCORRECT_RELIGION_ID' ) ) {
			$this->errormsg = "ERROR! UA_ReligionConnect: UNCORRECT_RELIGION_ID = ".
							$this->religionId;
			return false;
		}

		if ( $this->getActionErrors ( 'UNCORRECT_RELIGION_STATUS' ) ) {
			$this->errormsg = "ERROR! UA_ReligionConnect: ".
							  "UNCORRECT_RELIGION_STATUS";
			return false;
		}

		if ( $this->getActionErrors ( 'HAVE_RELIGION_EVENTS' ) ) {
			$this->errormsg = "ERROR! UA_ReligionConnect: HAVE_RELIGION_EVENTS";
			return false;
		}

		if ( $this->getActionErrors ( 'RELIGION_STATUS_ACTIVE' ) ) {


			$this->errormsg = "ERROR! UA_ReligionConnect: RELIGION_STATUS_ACTIVE";
			return false;
		}

		if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$this->_vocationModeAlert();			
			return true;
		}

		if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
		{
			$this->_shildModeAlert();			
			return true;
		}
		
		
		assert($this->getEventEndTime());

		//Создаем ивент на принятие религии
		$this->createEvent('RELIGION_CONNECT');

		//Приписываем пользователю в базе религию
		$sql = "INSERT INTO ds_userhasreligion
				(id_religion, id_userinstance, id_status)
				VALUES
				(".$this->religionId.", ".$this->uiid.", 1)";
		$this->common->executeQuery ( $sql );
		assert(empty($this->common->errormsg));


		//Добавляем пенальти, а бонусы религии оставляем для Ивента
		$religionAttributes = DE_ReligionAttributes::CreateFromReligionID((int)$this->religionId);
		$userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID((int)$this->uiid);

		$attributeTypeNames = $religionAttributes->getAttributeTypes();
		foreach($attributeTypeNames as $k1=>$attributeTypeName)
		{
			$attributeNames = $religionAttributes->getAttributeNamesForAttributeType($attributeTypeName);
			foreach($attributeNames as $k2=>$attributeName)
			{
				$bonus = $religionAttributes->getAttribute($attributeTypeName, $attributeName);

				if($bonus>0)
				{
					continue;
				}

				$userCombsCollection->resetNext();
				while($comb = $userCombsCollection->next())
				{

					if ($comb->getAttributes()->haveAttribute($attributeTypeName, $attributeName))
					{
						$str = "
						attributeTypeName = ".$attributeTypeName."
						attributeName = ".$attributeName."
						bonus = ".$bonus."

						";
						$comb->getAttributes()->deltaAttribute($attributeTypeName, $attributeName, (float)$bonus);
					}

				}
			}
		}

		//Отсылаем ссообщение о том что религия начала приниматься
		$messparams = array();
		$sql = "SELECT ltl.text
				FROM
					ds_religion AS r 
					INNER JOIN ds_localizedtextlanguage AS ltl ON (r.id_localizedname = ltl.id_localizedtext )
				WHERE
					ltl.id_language = ".$this->langId."
					AND
					r.id_religion = ".$this->religionId;
		$rs = $this->common->getResultArray ( "RELIGION_NAME_".$this->langId."_".$this->religionId, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($this->common->errormsg));
		assert(is_array($rs));
		assert(!empty($rs[0]["text"]));

		//Название религии
		$messparams[0] = "{b} ".$rs[0]['text']."{/b}";

		DE_SystemMessages::AddMessageFromAction(__CLASS__, 'RELIGION_CONNECT', $messparams, 0 );


		return true;
	}//END OF METHOD performAction ()

	/**
	 * Метод для определния параметров ивента используеться
	 * родителем для создания ивентов
	 */
	function createEventParams () {
		$this->eventparams["ID_Religion"] = $this->religionId;
		return true;
	}//END OF METHOD createEventParams ()

	/**
	 * Рассчитывает время  начала и кончания ивентов
	 */
	function getEventEndTime () {


		$acceptPeriod = floatval ( $this->worldSettings->get('RELIGION_ACCEPT_PERIOD') );
		$turnTime = floatval ( $this->worldSettings->get('TURN_TIME') );

		$acceptTime = $acceptPeriod * $turnTime * 60;

		// время приема
		$sql = "SELECT
					now() as starttime,
					now()+ text(".$acceptTime.")::interval as endtime";
		$res = $this->common->getQueryArray ( $sql );
		assert ( empty ( $this->common->errormsg ) );
		assert ( count ( $res ) > 0 );

		$this->endtime = $res[0]['endtime'];
		$this->starttime = $res[0]['starttime'];

		assert ( !empty ( $this->starttime ) );
		assert ( !empty ( $this->endtime ) );

		return true;
	}//END OF METHOD getEventEndTime ()
}//END OF CLASS
?>