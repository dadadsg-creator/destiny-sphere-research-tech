<?php $CVS_Version = '$Revision$ ; $Date$';

// Подклбчение класса предка

/**
 * Клас реализации ивента принятия религии.
 *
 * Класс добавляет бонусы и пинальти положеннве религии и меняет статус
 * религии у пользоватлея на принятый
 * 
 */

class EH_ReligionConnect extends EH_Event
{
	/** ID религии которой принимаем */
	public $religionId = 0;

	/** ID статуса религии */
	public $statusId = 1;

	/** Имя религии которой принимаем */
	public $religionName = '';

	/** Объект для работ с БД*/
	public $common = '';
	

	/** UIID пользователя с которым работаем */
	public $uiid = 0;

	/** ID языка текущего */
	public $langId = 0;

	/** Массив с SQL-запросами ивента */
	public $eventOptionsSQL = array();
	
	public $checktime;

	/**
	 * Конструткор класса
	 */
	function EH_ReligionConnect ()
	{
		EH_Event::EH_Event ();
		$this->common = DS_Common::CreateForCurentDB();		
	} //END OF METHOD EH_ReligionConnect ()
	

	/**
	 * Метод реализующий все действия по ивенту
	 */
	function handleEvent($eqid)
	{
		settype ( $eqid, 'integer' );
		assert ( $eqid > 0 );
		$this->eqid = $eqid;
		
		// получаем параметры
		assert($this->getEventParams());

		$this->religionId = (int)$this->eventparams['ID_Religion'];
		assert($this->religionId>0);

		$religion = DE_Religion::CreateFromID((int)$this->religionId);
		$religionAttributes = $religion->getAttributes();
		
		$this->religionName = $religion->getLocolizedName();
		
		// если не время
		if(!$this->_isTimeToHandle() )
		{
			$timeParts = $this->_getTimePeriodToEnd()->getWithParts();

			$messparams[1] = $timeParts['DAYS'];
			$messparams[2] = $timeParts['HOURS'];
			$messparams[3] = $timeParts['MINUTES'];
			$messparams[4] = $timeParts['SECONDS'];

			$messparams[0] = "{b} ".$religion->getLocolizedName()."{/b}";

			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'RELIGION_CONNECT_PROGRESS', $messparams, null, null, 'NOACCUMULATE');
			return true;
		}

		$eventQueue = DE_EventQueue::CreateFromID((int)$eqid);

		$eventParticipantCollection = $eventQueue->getEventParticipants();
		$eventParticipantCollection->resetNext();
		
		$userInstance = $eventParticipantCollection->next();
		$uiid = $userInstance->getID();

		//Изменяем статус религии на принятый.
		$sql = "UPDATE ds_userhasreligion
				SET id_status = 2
				WHERE
					id_userinstance = ".$uiid;
		$rs = $this->common->executeQuery ( $sql );
		assert ( empty ( $this->common->errormsg ) );

		//Начиляем бонусы религии
		$userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID((int)$uiid);

		$attributeTypes = $religionAttributes->getAttributeTypes();
		foreach($attributeTypes as $k=>$attributeTypeName)
		{
			$attributeNames = $religionAttributes->getAttributeNamesForAttributeType($attributeTypeName);
			foreach($attributeNames as $k=>$attributeName)
			{
				$value = (float)$religionAttributes->getAttribute($attributeTypeName, $attributeName);
				if($value < 0)
				{
					continue;
				}

				$userCombsCollection->resetNext();

				while($comb = $userCombsCollection->next())
				{
					if($comb->getAttributes()->haveAttribute($attributeTypeName, $attributeName))
					{
						$comb->getAttributes()->deltaAttribute($attributeTypeName, $attributeName, $value);
					}
				}
			}
		}

		//Отсыаем сообщение о том что

		$messparams = array();
		$messparams[0] = "{b} ".$this->religionName."{/b}";
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'RELIGION_CONNECT_EVENT', $messparams, 0 );
		
		assert($this->setTrash($this->eqid));

		return true;
		
	} //END OF METHOD handleEvent ( $eqid )

} //END OF CLASS EH_ReligionConnect



?>