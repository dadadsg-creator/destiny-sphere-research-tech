<?php $CVS_Version = '$Revision$ ; $Date$';

// Подклбчение класса предка
/**
 * Клас реализации ивента отказа от религии.
 *
 * Класс добавляет бонусы и пинальти положеннве религии и меняет статус
 * религии у пользоватлея на принятый
 * 
 */
class EH_ReligionCancel extends EH_Event {

	/** ID религии которой принимаем */
	private $religionId = 0;	

	/** Имя религии от которой отрекаемся */
	private $religionName = '';

	/** Объект для работ с БД*/
	public $common = '';	

	/** Массив с SQL-запросами ивента */
	private $eventOptionsSQL = array();

	private $religion;

	private $religionAtttributes;

	/**
	 * Конструткор класса
	 */
	function EH_ReligionCancel () 
	{
		EH_Event::EH_Event ();

		$this->common = DS_Common::CreateForCurentDB();
	
	}//END OF METHOD EH_ReligionConnect ()
	

	/**
	 * Метод реализующий все действия по ивенту
	 */
	function handleEvent ( $eqid )
	{

		assert ( !empty ( $eqid ) );
		settype ( $eqid, 'integer' );
		assert ( $eqid > 0 );
		$this->eqid = $eqid;

		// получаем параметры
		assert($this->getEventParams());

		$this->religionId = (int)$this->eventparams['ID_Religion'];
		assert($this->religionId > 0);

		$this->religion = DE_Religion::CreateFromID($this->religionId);
		$this->religionAttributes = $this->religion->getAttributes();
		$this->religionName = $this->religion->getLocolizedName();
		
		if(!$this->_isTimeToHandle())
		{

			$timeParts = $this->_getTimePeriodToEnd()->getWithParts();

			$messparams[1] = $timeParts['DAYS'];
			$messparams[2] = $timeParts['HOURS'];
			$messparams[3] = $timeParts['MINUTES'];
			$messparams[4] = $timeParts['SECONDS'];

			$messparams[0] = "{b} ".$this->religion->getLocolizedName()."{/b}";

			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'RELIGION_CANCEL_PROGRESS', $messparams, null, null, 'NOACCUMULATE');

			return true;
		}

		$eventQueue = DE_EventQueue::CreateFromID((int)$eqid);

		$eventParticipantCollection = $eventQueue->getEventParticipants();
		$eventParticipantCollection->resetNext();

		$userInstance = $eventParticipantCollection->next();
		$uiid = $userInstance->getID();

		//Изменяем статус религии на отреченный.
		$sql = "DELETE FROM ds_userhasreligion
				WHERE
					id_userinstance = ".$uiid;
		$rs = $this->common->executeQuery ( $sql );
		assert ( empty ( $this->common->errormsg ) );

		//Снимаем пенальти религии
		$userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID((int)$uiid);

		$attributeTypes = $this->religionAttributes->getAttributeTypes();
		foreach($attributeTypes as $k=>$attributeTypeName)
		{
			$attributeNames = $this->religionAttributes->getAttributeNamesForAttributeType($attributeTypeName);
			foreach($attributeNames as $k=>$attributeName)
			{
				$value = (float)$this->religionAttributes->getAttribute($attributeTypeName, $attributeName);
				
				if($value > 0)
				{
					continue;
				}

				$userCombsCollection->resetNext();
				while($comb = $userCombsCollection->next())
				{
					if($comb->getAttributes()->haveAttribute($attributeTypeName, $attributeName))
					{
						$comb->getAttributes()->deltaAttribute($attributeTypeName, $attributeName, -$value);
					}
				}
			}
		}

		//Отсыаем сообщение о том что
		$messparams = array();

		//Название религии
		$messparams[0] = "{b} ".$this->religionName."{/b}";
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'RELIGION_CANCEL_EVENT', $messparams, 0 );
		
		assert ( $this->setTrash ( $this->eqid ) );

		return true;
	}//END OF METHOD handleEvent ( $eqid )
}//END OF CLASS EH_ReligionConnect

?>