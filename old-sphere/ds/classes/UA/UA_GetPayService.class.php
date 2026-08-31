<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2010/04/20 12:41:02 $';

// Покупка платных сервисов из интерфейса игры
class UA_GetPayService extends UA_Action
{
	/**
	 * Доп переменная
	 *
	 * @var str
	 */
	private $for;
	
	/**
	 * Количество покупаемых услуг
	 *
	 * @var int
	 */
	private $amount;
	
	/**
	 * Тип заказывемой услуги
	 *
	 * @var str
	 */
	private $type;	
	
	/**
	 * Ячейка, на которой стоит здание
	 *
	 * @var int
	 */
	private $cellnumber;		
	
	/**
	 * Системное сообщение о покупке
	 *
	 * @var str
	 */
	private $message;	
		
	/**
	 * Цена для услуг без фиксированной цены
	 *
	 * @var int
	 */
	private $price = 0;		
	
	/**
	 * Комментарий для лога
	 *
	 * @var int
	 */
	private $comment = 0;	
	
	function __construct()
	{
		UA_Action::UA_Action();
	}

	// Проверяем параметры
	function checkLocalParams ()
	{		
		// За что покупаем юниты
		$this->for = $this->root->get_attribute ( "for" );
		// Количество
		$this->amount = (int)$this->root->get_attribute ( "amount" );	
		
		// Тип платной услуги
		$this->type = $this->root->get_attribute ( "type" );
		
		// Ячейка, на которой стоит здание
		$this->cellnumber = (int)$this->root->get_attribute("c");
		
		return true;
	}

	// Проверяем допустимость действия
	function checkAction()
	{
		// Проверяем допустимость действия
		if (!$this->_checkAction())
		{
			return false;
		}

		// Выполняем действия
		if (!$this->_perfomAction())
		{
			return false;
		}
		
		/*// Открываем меню заново
		$menu = new PN_EconomicalOverview();
		$menu->checkLocalParams();
		$menu->performAction();*/
		
		
		// Выводим алерт о покупке 
		$title = ""; 
		SU_OutputHandler::Singleton()->addReadyString('outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf" title="'.$title.'"><txt><![CDATA['.$this->message.']]></txt></swloader>');

		return true;
	}

	// выполняем действие
	function performAction ()
	{
		$noCellData = true;

		// рефреш с актуализацией ресурсов
		if(!$this->refresh( 2, "", "", 1, $noCellData))
		{
			return false;
		}

		return true;
	}

	function _checkAction()
	{	
		assert(is_int($this->amount));
		assert($this->amount >= 1);
		assert(!empty($this->type));
		assert(!empty($this->for));

		return true;
	}

	function _perfomAction()
	{
		switch ($this->type) {
			// Покупка пакетов ресурса
			case 'RES':
				$operation = 'PS_BUY_RESOURCE_'.$this->for;
				$withPrice = 1;
				if (!$this->buyResource()) {
					return false;
				}
				break;
		
			// Покупка бонусов на ресурсы
			case 'BNS':
				$operation = 'PS_BY_BONUS_'.$this->for;
				$withPrice = 1;
				if (!$this->buyBonus()) {
					return false;
				}
				break;
		
			// Моментальное завершение строительства и апгрейда зданий
			case 'BLD':
				$operation = 'PS_BUY_TRAINING_COMPLETION';
				$withPrice = 0;
				if (!$this->buyTrainingCompletion()) {
					return false;
				}
				break;
		
			// Покупка ученых
			case 'BUY_SCIENTIST':
				$operation = 'PS_BY_UNIT_SCIENTIIFIC';
				$withPrice = 1;
				if (!$this->buyUnitScientific()) {
					return false;
				}
				break;
		
			// Покупка ученых
			case 'BUY_ARCHEOLOGIST':
				$operation = 'PS_BY_UNIT_ARCHEOLOG';
				$withPrice = 1;
				if (!$this->buyUnitArheologist()) {
					return false;
				}
				break;
		
			// Ускорение изучения науки
			case 'FAST_RESEARCH':
				$operation = 'PS_BY_SCIENCE_ACCELERATION';
				$withPrice = 0;
				if (!$this->buyScinceAcceleration()) {
					return false;
				}
				break;
		
			default:
				$this->errormsg = " SYSTEM ERROR UA_GetPayService::checkAction1";
				return false;
		}
		
		// Снимаем деньги
		if ($withPrice) {
			UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperationWitPrice(	
																DE_User::CreateFromID((int)$_SESSION['uid']), 
																$operation, 
																DE_World::CreateForCurrentWorld()->getShortName(),
																$this->amount);
		} else {
			assert($this->price > 0);
			UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation(
																DE_User::CreateFromID((int)$_SESSION['uid']), 
																$operation,
																$this->price,
																'SC',
																DE_World::CreateForCurrentWorld()->getShortName());
		}
		
		//Логиреум
		DS_PaymentService::Log((int)$_SESSION['uid'], 't', 'f', 't', 'f', $operation, $this->price, 'SC', $this->comment);
		
		return true;
	}
	
	private function buyResource()
	{
		// Обьект купленных игроком пакетов
		$boughtRes = DE_UserBoughtResources::CreateFromID((int)$_SESSION['uid'].'_'.(int)$_SESSION['world']);
		
		// Количество русурсов в пакете
		$resourcesAmount = array(
			'STASIS_ENERGY' => 200000,
			'FOOD' => 100000,
			'KADERIUM' => 30000,
			'NANO_CRYSTAL' => 20000,
			'PRODIUM' => 5000
		);
		
		//Проверяем, можно ли в этом мире покупать ресы за деньги
		$buyAllowed = (int)DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_FOR_MONEY_ALLOWED');
		if($buyAllowed != 1)
		{
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::performAction1";
			return false;
		}
		
		// Количество ресурса, которое хотим купить 
		$resAmount = (int)($this->amount*$resourcesAmount[$this->for]);
		assert($resAmount > 0);
		
		// Проверяем, не достигнуто ли максимальное количество
		$new = $boughtRes->get($this->for) + $resAmount;
		$max = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_'.$this->for.'_LIMIT');
		if($new > $max)
		{
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::performAction2";
			return false;
		}
		
		$combAttr = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		
		$combAttr->deltaAttribute('CURRENT_AMOUNT', $this->for, $resAmount);
		$boughtRes->delta($this->for, $resAmount);
		
		// Получаем стоимомсть услуги 
		$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BUY_RESOURCE_'.$this->for); 
		
		// Формируем системное сообщение
		$messageParams[0] = DE_Resource::CreateFromName($this->for)->getLocolizedName(); // Название ресурса
		$messageParams[1] = $this->amount; // Количество приобретенных пакетов
		$messageParams[2] = number_format($resAmount, 0, ",", "'"); // Всего ресурсов
		$messageParams[3] = number_format($price*$this->amount, 0, ",", "'"); // Стоимость

		// Вы преобрели:<br>%<br>количество пакетов: %<br>всего ресурсов: %<br>сумма: % серебряных кредитов
		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_RESOURCE_MESS', $messageParams, null, null, false);	
		
		// Для лога
		$this->price = $price*$this->amount;
		$this->comment = "Buy: ".$this->amount." x ".$resourcesAmount[$this->for]." packets of ".$messageParams[0].", World: ".DE_World::CreateForCurrentWorld()->getName();

		return true;
	}
	
	private function buyBonus()
	{
		// Количество дней, на которое предоставляется бонус
		$days = 14;
		
		// Количество процентов бонуса
		$persent = 25;
		
		// Проверяем, чтобы у игрока не было бонуса на этот ресурс
		if (DS_Bonus::IsUserHasBonuser($this->uiid, $this->for)) {
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::performAction1";
			return false;
		}

		// Добавляем игроку ресурс
		DS_Bonus::IsertUserBonus($this->uiid, $days, array($this->for => $persent));
		
		// Получаем стоимомсть услуги  
		$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BY_BONUS_'.$this->for);
		
		// Формируем системное сообщение
		$messageParams[0] = 25; // Значение бонуса 
		$messageParams[1] = 14; // Длительность бонуса
		$messageParams[2] = DE_Resource::CreateFromName($this->for)->getLocolizedName(); // Ресурс
		$messageParams[3] = number_format($price*$this->amount, 0, ",", "'"); // Стоимость
		
		// Вы приобрели бонус % процентов сроком на % дней<br>на % за % %
		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_BONUS_MESS', $messageParams, null, null, false);	
		
		// Для лога
		$this->price = $price*$this->amount;
		$this->comment = "World: ".DE_World::CreateForCurrentWorld()->getName().", ".$messageParams[0].'% '.$messageParams[2];

		return true;
	}

	private function buyTrainingCompletion()
	{
		// Получаем ID здания, которое хотим завершить
		$cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec($this->cellnumber, true);
		
		$attachedbid = $cellData['ATTACHED_BUILDING_ID'];
		assert(is_int($attachedbid) && $attachedbid >= 0);
			
		$bid = $cellData['BUILDING_ID'];
		assert(is_int($bid) && $bid >= 0);
		
		// Провеям, что это не гондола
		if ($bid == 18) {
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::performAction1";
			return false;
		}
		
		// Смотрим, сколько турнов осталось до окончания строительства здания
		$leftTime = DS_PaymentService::GetLeftTimeToCmplitision($attachedbid);
		
		// Если нам нчего завершать
		if ($leftTime <= 0) {
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::performAction2";
			return false;
		}

		// Завершаем ивент
		DS_PaymentService::ComplitTraining($attachedbid);
		
		// Получаем стоимомсть услуги 
		$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BUY_TRAINING_COMPLETION')*$leftTime; 
		
		// Формируем системное сообщение
		$messageParams[0] = number_format($price, 0, ",", "'"); // Стоимость
		// Вы преобрели Окончание строительства и апгрейда здания за сумму % серебряных кредитов. Обновите соту для завершения.
		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_TRAINING_COMPLETION_MESSD', $messageParams, null, null, false);
		
		// Для лога
		$this->price = $price;
		$this->comment = "World: ".DE_World::CreateForCurrentWorld()->getName().", ceil ".$cellData.", building ".$bid;

		return true;
	}
	
	private function buyUnitScientific()
	{
		// Получаем максимальное количество ученых, которое мы можем купить
		$maxAmount = DS_PaymentService::GetMaxUnitAmount($this->comb, $this->uiid, 'SCIENTIST');
		
		if ($maxAmount < $this->amount) {
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::buyUnitScientific1";
			return false;
		}
		
		// Добавляем игроку юнитов
		$combAttr = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		$combAttr->deltaAttribute('CURRENT_AMOUNT', 'SCIENTIST', $this->amount);
		
		// Получаем стоимомсть услуги  
		$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BY_UNIT_SCIENTIIFIC');
		
		// Формируем системное сообщение
		$messageParams[0] = $this->amount; // Количество
		$messageParams[1] = number_format($price*$this->amount, 0, ",", "'"); // Стоимость
		
		// Вы купили % ученых за % серебряных кредитов.
		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BUY_SCIENTISTS_MESS', $messageParams, null, null, false);
		
		// Для лога
		$this->price = $price*$this->amount;
		$this->comment = "World: ".DE_World::CreateForCurrentWorld()->getName().", Comb: ".DE_Comb::CreateFromID($this->comb)->getName().", Amount: ".$this->amount;
		
		return true;
	}
	
	
	private function buyUnitArheologist()
	{
		// Получаем максимальное количество ученых, которое мы можем купить
		$maxAmount = DS_PaymentService::GetMaxUnitAmount($this->comb, $this->uiid, 'ARCHEOLOGIST');
		
		if ($maxAmount < $this->amount) {
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::buyUnitArheologist1";
			return false;
		}
		
		// Добавляем игроку юнитов
		$combAttr = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		$combAttr->deltaAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', $this->amount);
		
		// Получаем стоимомсть услуги  
		$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BY_UNIT_ARCHEOLOG');
		
		// Формируем системное сообщение
		$messageParams[0] = $this->amount; // Количество
		$messageParams[1] = number_format($price*$this->amount, 0, ",", "'"); // Стоимость
		
		// Вы купили % ученых за % серебряных кредитов.
		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BUY_ARCHEOLOGIST', $messageParams, null, null, false);
		
		// Для лога
		$this->price = $price*$this->amount;
		$this->comment = "World: ".DE_World::CreateForCurrentWorld()->getName().", Comb: ".DE_Comb::CreateFromID($this->comb)->getName().", Amount: ".$this->amount;
		
		return true;
	}
	
	
	private function buyScinceAcceleration()
	{
		// Выбираем данные о науке игрока
		$scaeinceData = DS_PaymentService::GetUserScience($this->uiid);
		
		// В данный момент у нас не изучается наука
		if (count($scaeinceData) == 0) {
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::buyScinceAcceleration1";
			return false;
		} 
		
		// Усли у нас наука уже ускорена
		if ($scaeinceData[0]['accelerated'] != 0) {
			$this->errormsg = " SYSTEM ERROR UA_GetPayService::buyScinceAcceleration2";
			return false;
		} 
		
		// Выбираем множитель ускорения науки
		$acceletarorMultiplier = DE_WorldSettings::CreateForCurentWorld()->get('SCIENCE_ACCELERATION_MULTIPLIER');
		assert($acceletarorMultiplier >= 0 && $acceletarorMultiplier <= 1);

		// Время, оставшееся у него до окончания изучения науки
		$now = GB_TypeFactory::CreateBase('GB_Date', time());
		$oldEndDate = GB_TypeFactory::CreateBase('GB_Date', $scaeinceData[0]['eventendtime']);

		//Новое время окончания
		$newEndDTime = $oldEndDate->value - $acceletarorMultiplier*($oldEndDate->value - $now->value);
		$newEndDate = GB_TypeFactory::CreateBase('GB_Date', $newEndDTime);
		$newPeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $newEndDTime - $now->value);

		// Множитель цены (на сколько часов ускоряем науку)
		$priceMultiplier = (int)ceil(($oldEndDate->value - $newEndDate->value)/3600);

		//получаем цену
		$price = DS_PaymentService::GetSciencePrice($scaeinceData[0]['sciencename']);			
		// Умножаем цену на множитель
		$price = $price*$priceMultiplier;
		$this->price = $price;
		
		// Формируем время
		$localization = DE_Localization::CreateForCurentLang();		
		$daysSimvol = $localization->get(30003);
		$hoursSimvol = $localization->get(30004);
		$minsSimvol = $localization->get(30005);
			
		// Старое время окончания науки
		$newDateArr = $newPeriod->getWithParts();
		$newDate = $newDateArr['DAYS']." ".$daysSimvol." ".$newDateArr['HOURS']." ".$hoursSimvol." ".$newDateArr['MINUTES']." ".$minsSimvol;
		
		// Ставим новое время окончания ивента
		DS_PaymentService::SetNewScienceEndDate($this->uiid, $newEndDate->getPgFormat());
		// Помечаем ивент как ускоренный
		DS_PaymentService::SetEventAsAccelerated($scaeinceData[0]['id_eventqueue']);
		
		// Формируем системное сообщение
		$messageParams[0] = number_format($price, 0, ",", "'"); // Стоимость
		$messageParams[1] = $scaeinceData[0]['text']; // Название науки
		$messageParams[2] = $newDate; // Новое время
		
		// Вы приобрели ускорение науки за % серебряных кредитов. До окончания изучения % осталось %
		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SCIENCE_ACCELERATION_MESS', $messageParams, null, null, false);
		
		// Для лога
		$this->price = $price;
		$this->comment = "Science: ".$scaeinceData[0]['text'].". Level: ".$scaeinceData[0]['level'].". World: ".DE_World::CreateForCurrentWorld()->getName().". Old end date: ".$oldEndDate->getPgFormat().". New end date: ".$newEndDate->getPgFormat();
		
		return true;
	}
}

?>