<?php $CVS_Version = '$Revision$ ; $Date$';

// команда восстановить защитное поле колонии
class UA_RestoreProtection extends UA_Action
{

	/**
	 * На сколько процентов восстанавливаем поле
	 *
	 * @var array
	 */
	private $persent;

	/**
	 * За что восстанавливаем (ресурсы или серебро)
	 *
	 * @var str
	 */
	private $restoreFor;
	
	/**
	 * Информация о восстановленной армии (нужна для вывода алерта)
	 *
	 * @var array
	 */
	private $messageParams = array();	

	function __construct()
	{
		UA_Action::UA_Action();
	}

	// Проверяем параметры
	function checkLocalParams ()
	{
		// За что покупаем юниты
		$this->restoreFor = $this->root->get_attribute ( "resource" );
		assert($this->restoreFor == 'RES' || $this->restoreFor == 'SILVER');

		// На сколько процентов восстанавливаем поле
		$this->persent = (int)$this->root->get_attribute ( "amount" );
		assert($this->persent > 0 && $this->persent <= 100);

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

		// Открываем меню заново
		$menu = new PN_ProtectedField();
		assert($menu->getPanel(true, $this->messageParams));

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
		global $params;
		global $common;

		// Хватает ли ресурсов
		// Выбираем стоимость восстановления одного процента
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		$res = DS_Comb::GetProtectiveFieldRestorePrice($this->world);

		foreach ($res as $k => $course)
		{
			$resourceName = $course['full_name'];
			$resourceValue = $course['price']*$this->persent;

			if ($this->restoreFor == 'RES') {
				// Если это НЕ серебро
				if ($resourceName != 'SILVER_CREDIT') {
					// Если это ресурс
					if ($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true) < $resourceValue) {
						$this->errormsg = " SYSTEM ERROR UA_RestoreProtection::checkAction2";
						return false;
					}
				}
			} else {
				// Если это серебро
				if ($resourceName == 'SILVER_CREDIT') {
					if (!UC_PaymentServiceMoneyOperation::CheckMoney(DE_User::CreateFromID(DE_UserInstance::CreateFromID($this->uiid)->getUserID()), $resourceValue, 'SC', DE_World::CreateForCurrentWorld()->getShortName())) {
						$this->errormsg = " SYSTEM ERROR UA_RestoreProtection::checkAction1";
						return false;
					}
				}
			}
		}
		return true;
	}

	function _perfomAction()
	{
		global $params;
		global $common;

		// Проверяем, нет ли у игрока режима отпуска
		if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$this->_vocationModeAlert();
			return true;
		}

		// Проверяем, нет ли у игрока включенного щита
		if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
		{
			$this->_shildModeAlert();
			return true;
		}

		if($this->haveProcessedErrors)
		{
			return true;
		}

		// Снимаем стоимость восстановления
		$res = DS_Comb::GetProtectiveFieldRestorePrice($this->world);
		
		$this->messageParams[0] = $this->persent;
		$this->messageParams[1] = "";

		// Если покупаем за ресы
		if ($this->restoreFor == 'RES') {
			$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

			foreach ($res as $k => $course)
			{
				$resourceName = $course['full_name'];
				$resourceValue = $course['price']*$this->persent;
				
				if ($resourceName == 'SILVER_CREDIT') {
					continue;
				}

				$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$resourceValue);
				$this->messageParams[1] .= number_format($resourceValue, 0, ",", "'")." ".DE_Resource::CreateFromName($resourceName)->getLocolizedShortName()."; ";
			}
		} else {
			// Если это серебро
			foreach ($res as $k => $course)
			{
				if ($course['full_name'] == 'SILVER_CREDIT') {
					$resourceValue = $course['price']*$this->persent;
					$resourceName = $course['full_name'];
				
					$this->messageParams[1] .= number_format($resourceValue, 0, ",", "'")." ".DE_Money::CreateFromShortName('SC')->getLocolizedName($_SESSION['lang'])."; ";
				}
			}
		
			assert($resourceValue > 0);	
				
			UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation(DE_User::CreateFromID(DE_UserInstance::CreateFromID($this->uiid)->getUserID()), 'RESTORE_PROTECTIVE_FIELD', (int)$resourceValue, 'SC', DE_World::CreateForCurrentWorld()->getShortName());
		}

		// Обновляем процент
		$protectedField = DE_CombProtectiveField::CreateFromCombID($this->comb);
		$protectedField->deltaProtectiveField($this->persent);

		return true;
	}


	function _invariant ()
	{
		return true;
	}
}

?>