<?php $CVS_Version = '$Revision$ ; $Date$';

// Обработчик кабинета вконтакте
class UA_GetCabinet extends UA_Action
{
	/**
	 * Команда кабинета
	 *
	 * @var text
	 */
	public $command;

	/**
	 * Пользователь
	 *
	 * @var DE_User
	 */
	public  $user;

	/**
	 * Сообщение юзверю
	 *
	 * @var str
	 */
	private $message;

	/**
	 * Текущая база 
	 *
	 * @var int
	 */
	private $currentDbId;

	/**
	 * Массив с локализацией
	 *
	 * @var array
	 */
	private $lang;

	function __construct()
	{
		//import('domXML');
		UA_Action::UA_Action();
	}//END OF CONSTRUCTOR

	function checkLocalParams ()
	{
		// Команда, котрую надо выполнить
		$this->command = $this->root->get_attribute ( "command" );
		//assert(!empty($this->command));
        if (empty($this->command))
          $this->command = 'open';
		$this->user = DE_User::CreateFromID((int)$_SESSION['uid']);

		return true;
	}//END OF FUNCTION

	function checkAction ()
	{
		return true;
	}//END OF FUNCTION


	function performAction ()
	{
		$this->currentDbId = $_SESSION['dbId'];

		// Подключаем локализацию
		$language = 'R';

		if ($_SESSION['lang'] == 1)
		   $language = 'G';
		else if ($_SESSION['lang'] == 2)
		   $language = 'E';
		else if ($_SESSION['lang'] == 3)
		   $language = 'R';
		   
		require_once ("language/" . $language . "/user_shop.lang");

		$this->lang = $lang;
		require_once ("language/" . $language . "/user_cabinet_stat_out.lang");
		$this->langstat = $lang;
		
//    SU_DebugTools::LogAll('language='. $language.', lang='.var_export($this->lang, true),'cab.log');
		switch ($this->command)
		{
			// Первая загрузка менюшки
			case 'open':
				$this->open();
				break;

				// Подарить игре деньги
			case 'giftMoney':
				$this->giftMoney();
				$this->open();
				break;

				// Перевод кредитов из игрового мира
			case 'moneyFormWorld':
				$this->moneyFormWorld();
				$this->open();
				break;

				// Перевод кредитов в игровой мир
			case 'moneyToWorld':
				$this->moneyToWorld();
				$this->open();
				break;

				// Обмен золотых кредитов на серебрянные
			case 'changeGoldTOSelver':
				$this->changeGoldTOSelver();
				$this->open();
				break;

				// Вызов подменюшки (платной услуги)
			case 'callsubmenu':
				$this->callsubmenu();
				break;

				// Вызов подменюшки (платной услуги)
			case 'confirmsubmenu':
				$this->confirmsubmenu();
				break;

				// Статистика операциы
			case 'getAllStatistic':
				$this->getAllStatistic();
				//$this->open();
				break;
		}

		$_SESSION['dbId'] = $this->currentDbId;
		$_SESSION['world'] = $this->world;

		return true;
	}

	// Подарить игре деньги
	function giftMoney()
	{
		$gcCount = (int)$this->root->get_attribute("gold");
		$scCount = (int)$this->root->get_attribute("silver");

		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember($this->user, $world);
		$account = MU_SearchOperations::GetAccountData($memberFrom);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);

		if ($account['GC'] < $gcCount && $account['SC'] < $scCount) {
			$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_CREDITS_NOT_ENOUGH', array(), null, null, false);
		}

		$actionType = DE_MoneyActionType::CreateFromName('PVP_TRANSFER');

		if($scCount > 0)
		{
			$summ = new MU_EntityMoneySumm($scCount, 'SC');
			$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
			MU_ControlExecOperations::ExecOperation($operation);
		}

		if($gcCount > 0)
		{
			$summ = new MU_EntityMoneySumm($gcCount, 'GC');
			$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
			MU_ControlExecOperations::ExecOperation($operation);
		}

		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_CREDITS_COMPLETE', array(), null, null, false);
	}

	// Перевод кредитов из игрового мира
	function moneyFormWorld()
	{
		$gcCount = (int)$this->root->get_attribute("gold");
		$scCount = (int)$this->root->get_attribute("silver");
		$world = $this->root->get_attribute("worldId");

		$arr = explode("_", $world);
		$dbId = (int)$arr[1];
		assert($dbId >= 0);

		$worldId = $arr[0];
		assert($worldId > 0);

		// Если у нас не введена сумма
		if ($gcCount == 0 && $scCount == 0) {
			// Ничего не делаем
			return;
		}

		$_SESSION['dbId'] = $dbId;
		$_SESSION['world'] = $worldId;

		$worldFrom = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
		$memberFrom = new MU_EntityOperationMember($this->user, $worldFrom);
		$instanceMoney = MU_SearchOperations::GetAccountData($memberFrom);

		if ($instanceMoney['GC'] < $gcCount && $instanceMoney['SC'] < $scCount) {
			$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_CREDITS_NOT_ENOUGH', array(), null, null, false);
		}

		$memberTo = new MU_EntityOperationMember($this->user, DE_WorldFromCollection::CreateFromID('UC'));

		if($gcCount > 0)
		{
			$summ = new MU_EntityMoneySumm($gcCount, 'GC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
		}

		if($scCount > 0)
		{
			/*// Снимаем налог
			$dbOld = $_SESSION['dbId'];
			$worldOld = $_SESSION['world'];
			$_SESSION['dbId'] = self::$dbId;
			$_SESSION['world'] = DE_WorldFromCollection::CreateFromID(self::$worldId)->getWorldID();
			//Смотрим, есть ли налог на вывод серы из мира
			$tax = DE_WorldSettings::CreateForCurentWorld()->get('USER_CABINET_TRANSFER_TAX');
			if ($tax > 0) {
			$taxSc = ceil($tax*$data['sc']/100);

			$summ = new MU_EntityMoneySumm((int)$taxSc, 'SC');
			$memberSystem = MU_EntityOperationMember::CreateSystemAccount(self::$worldId);
			$actionType = DE_MoneyActionType::CreateFromName('PAY_TRANSFER_TAX');
			$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberSystem);
			$key = GB_RandomPassword::Generate(25);
			$operation->setAdditionalID($key);
			MU_ControlExecOperations::ExecOperation($operation);

			$data['sc'] = (int)($data['sc'] - $taxSc);

			}
			$_SESSION['dbId'] = $dbOld;
			$_SESSION['world'] = $worldOld;

			if ($scCount != 0) {*/

			$summ = new MU_EntityMoneySumm($scCount, 'SC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
			//}
		}

		$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_CREDITS_COMPLETE', array(), null, null, false);
	}

	function moneyToWorld()
	{
		$gcCount = (int)$this->root->get_attribute("gold");
		$scCount = (int)$this->root->get_attribute("silver");
		$world = $this->root->get_attribute("worldId");

		$arr = explode("_", $world);
		$dbId = (int)$arr[1];
		assert($dbId >= 0);

		$worldId = $arr[0];
		assert($worldId > 0);

		// Если у нас не введена сумма
		if ($gcCount == 0 && $scCount == 0) {
			// Ничего не делаем
			return;
		}

		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember($this->user, $world);
		$userMoney = MU_SearchOperations::GetAccountData($memberFrom);

		if ($userMoney['GC'] < $gcCount && $userMoney['SC'] < $scCount) {
			$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_CREDITS_NOT_ENOUGH', array(), null, null, false);
		}

		$_SESSION['dbId'] = $dbId;
		$_SESSION['world'] = $worldId;

		$worldTo = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
		$memberTo = new MU_EntityOperationMember($this->user, $worldTo);

		if($gcCount > 0)
		{
			$summ = new MU_EntityMoneySumm($gcCount, 'GC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
		}

		if($scCount > 0)
		{
			$summ = new MU_EntityMoneySumm($scCount, 'SC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
		}

    	$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_CREDITS_COMPLETE_TO_WORLD', array($gcCount, $scCount, DE_World::CreateForCurrentWorld()->getName()), null, null, false);
	}

	// Обмен золотых кредитов на серебряные
	function changeGoldTOSelver()
	{
		$gcCount = (int)$this->root->get_attribute("count");

		assert($gcCount > 0);

		$_SESSION['dbId'] = 0;

		$worldFrom = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember($this->user, $worldFrom);
		$account = MU_SearchOperations::GetAccountData($member);

		assert($account['GC'] >= $gcCount);

		if ($account['GC'] < $gcCount) {
        	$this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_CREDITS_COMPLETE_TO_WORLD', array($gcCount, $scCount, DE_World::CreateForCurrentWorld()->getName()), null, null, false);
		}

		//Обмен денег в мире
		$worldTo = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember($this->user, $worldFrom);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $worldTo);
		$summ = new MU_EntityMoneySumm($gcCount, 'GC');
		$actionType = DE_MoneyActionType::CreateFromName('EXCHANGE_CREDITS');
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		MU_ControlExecOperations::ExecOperation($operation);

        $this->message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_TRANSFER_EXCHANGE_COMPLETE', array(), null, null, false);	
	}

	
	function callsubmenu()
	{
		// Платная услуга
		$menu = $this->root->get_attribute("menu");


		if ($menu == 'BUY_ART_360' || $menu == 'BUY_ART_180') {
			$this->ShowArtefactsShop($menu);
			return;
		}

		$actionType = DE_MoneyActionType::CreateFromName ( $menu );

		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType ( $actionType );

		// Цена в серебре
		$price = $MoneyActionType->getAmount ();

		// Цена в золоте
		$priceInGold = $MoneyActionType->getPriceInGolg ();

		// Выбираем из базы обработчик этой услуги
		$services = UC_PaymentServiceBase::GetPaymentServicesHeader ( $menu );
		assert ( ! empty ( $services ) );

		// Собираем параметры, уникальные для каждой услуги
	//SU_DebugTools::LogAll($services, 'Annita.log');
		$serviceExec = new $services ();
		$params = $serviceExec->FormMenu ( $this->user, $menu, $this->uiid );

		$params['SERVICE'] = $menu;
		$params['PRICE'] = $price;
		$params['PRICE_IN_GOLD'] = $priceInGold;
		//$params['BUTTON_ID'] = 'next';
		

		$this->showSumMenu($params);

		return;
	}

	private function ShowDeleteCobm($menu)
	{
		$actionType = DE_MoneyActionType::CreateFromName($menu);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		$price = $MoneyActionType->getAmount();

		$comb_delete     = DE_SystemMessages::getFormatedMessage2('COMB_DELETE_WORD', array(), null,$_SESSION["lang"], null, false);
	    $comb_delstr     = DE_SystemMessages::getFormatedMessage2('COMB_DELETE_TITLE', array(), null,$_SESSION["lang"], null, false);
		$comb_delmessage = DE_SystemMessages::getFormatedMessage2('COMB_DELETE_MSG', array(DE_World::CreateForCurrentWorld()->getName(), $price), null,$_SESSION["lang"], null, false);		 
		$comb_delprice   = DE_SystemMessages::getFormatedMessage2('COMB_DELPRICE_MSG', array($price), null,$_SESSION["lang"], null, false);		 

		$str = 'xmldata=<?xml version="1.0" encoding="utf-8"?>
<cabinet action="submenuopen">
<form closebut="true" title="'.$comb_delstr.'" idmenu="'.$menu.'">
<textfield id="id_obj">'.$comb_delmessage.'</textfield>';

		// Смотрим, достаточно ли кредитов для покупки услуги

		$str .= '
<button label="'.$comb_delete.'" confirm="'.$comb_delprice.'"/>
</form>';

		$str .= '
</cabinet>';
		SU_OutputHandler::Singleton()->addReadyString($str);
	}

	function confirmsubmenu()
	{
		// Платная услуга
		$menu = $this->root->get_attribute("menu");

		$menuAction = $this->root->get_attribute ( "button" );
		assert ( $menuAction == 'next' || $menuAction == 'confirm' || $menuAction == 'preconfirm' );
		//SU_DebugTools::LogAll($menuAction, 'AnnitaCab.log');

		$psCount = (int)$this->root->get_attribute ( "ps_count" );
		assert($psCount > 0);

		if ($menuAction == 'next') {
			$credit = $this->root->get_attribute ( "credit" );
			assert($credit == "SC" || $credit == "GC");

			// Смотрим, достаточно ли кредитов для покупки услуги
			if (!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation($this->user, $menu, $psCount, $credit))
			{
				$_SESSION['dbId'] = 0;

				$world = DE_WorldFromCollection::CreateFromID('UC');
				$member = new MU_EntityOperationMember($this->user, $world);
				$data = MU_SearchOperations::GetAccountData($member);

				$goldVal = $data['GC'];
				$silverVal = $data['SC'];

				$actionType = DE_MoneyActionType::CreateFromName($menu);
				$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
				$operationCost = $MoneyActionType->getAmount();
				$priceInGold = $MoneyActionType->getPriceInGolg ();

				// Если мы покупаем услугу за серу
				if ($credit == "SC") {
					// Если нам хватит денег при обмене золота на серебро
					if ($goldVal*1000+$silverVal>=$operationCost)
					{
						$this->message = $this->lang['NOT_EHOUGH_MONEY_BUT_CAN_EXCHANGE'];
					} else {
						$this->message = $this->lang['NOT_EHOUGH_MONEY'];
					}
				} else {
					// Покупаем за голду

					// У нас хватает серыFormConfirmMenu
					if ($silverVal >= $operationCost) {
						$this->message = $this->lang['NOT_EHOUGH_GOLD'];
					} else {
						$this->message = $this->lang['NOT_EHOUGH_MONEY'];
					}
				}

				$this->open();
				return ;
			}

			// Выбираем из базы обработчик этой услуги
			$services = UC_PaymentServiceBase::GetPaymentServicesHeader ( $menu );
			$serviceExec = new $services ();
			$params = $serviceExec->FormConfirmMenu ( $this->user, $menu, $this->uiid, $this->root);

			// Стоимость
			$actionType = DE_MoneyActionType::CreateFromName ( $menu );
			$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType ( $actionType );

			if ($credit == 'SC')
			{
				$params['PRICE'] = $psCount*$MoneyActionType->getAmount ();
				$params['CREDIT'] = 'SC';
			} else {
				$params['PRICE'] = $psCount*$MoneyActionType->getPriceInGolg ();
				$params['CREDIT'] = 'GC';
			}
			$params['SERVICE'] = $menu;

			//SU_DebugTools::LogAll($params, 'AnnitaCab.log');
			$this->showSumMenu($params);
			return;
		} elseif ($menuAction == 'preconfirm') {
			// Выбираем из базы обработчик этой услуги
			$services = UC_PaymentServiceBase::GetPaymentServicesHeader ( $menu );
			$serviceExec = new $services ();
			$params = $serviceExec->FormPreConfirmMenu ( $this->user, $menu, $this->uiid, $this->root);
			
			$params['SERVICE'] = $menu;
			
			$this->showSumMenu($params);
			return;
		} else {
			$credit = $this->root->get_attribute ( "credit" );
			assert($credit == "SC" || $credit == "GC");

			// Выбираем из базы обработчик этой услуги
			$services = UC_PaymentServiceBase::GetPaymentServicesHeader ( $menu );
			assert ( ! empty ( $services ) );

			// Предоставляем услугу
			$serviceExec = new $services ();
			$serviceExec->Exec ($this->user, $menu, $this->currentDbId, $this->uiid, $credit, $this->root, $this->lang);

			// Сообщение о результате
			$this->message = $serviceExec->resultMes;

			$this->open();
			return ;
		}
	}

	function open()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><cabinet />";
		$docXML = domxml_open_mem ($xml);
		$root = $docXML->document_element();

		$root->set_attribute ( "action", "enter" );
		$root->set_attribute ( "billing_url", "/ds/billing.php?SID=".session_id());
		/*********************************************************************/
		/**************** ДАННЫЕ О ИГРОКЕ И БАЛЛАНСЕ В МИРАХ *****************/

		$user = $root->new_child ( "user" );

		$user->set_attribute ( "name", $this->user->getName() );
		$user->set_attribute ( "email", '' );


		$worlds = $user->new_child ( "allow_worlds" );

		$userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($this->user->getID());
		foreach ($userWorlds as $key => $data)
		{
			foreach($GLOBALS['dbWorldAccess'] AS $l => $db)
			{
				if($db['server'] == $data['dbname'])
				{
					$data['dbId'] = $l;
				}
			}

			$_SESSION['dbId'] = $data['dbId'];
			$_SESSION['world'] = $data['id_world'];

			$world = DE_WorldFromCollection::CreateFromID($data['world_identifier']);
			$member = new MU_EntityOperationMember($this->user, $world);
			$member = new MU_EntityOperationMember($this->user, $world);

			$account = MU_SearchOperations::GetAccountData($member);

			if(count($account) > 0)
			{
				$world = $worlds->new_child ( "world" );
				$world->set_attribute ( "name", $data['worldname']);
				$world->set_attribute ( "id", $data['id_world'].'_'.$data['dbId'] );
				$world->set_attribute ( "gold", $account['GC'] );
				$world->set_attribute ( "silver", $account['SC'] );
			}
		}

		/*********************************************************************/
		/********************* ТЕКУЩИЙ БАЛАНС В КАБИНЕТЕ *********************/

		$world = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember($this->user, $world);
		$data = MU_SearchOperations::GetAccountData($member);

		// УБРАНО ВО ИЗБЕЖАНИЯ СОЗДАНИЯ АККАУНТОВ В МИРЕ UC НЕ В МАСТЕРЕ!!
		// если нет аккаунта в кабинете, то создаем
		/*if(count($data) == 0)
		{
		MU_SearchOperations::CreateAccount($this->user, 'UC');
		$data = MU_SearchOperations::GetAccountData($member);
		assert(count($data) > 0);
		}*/

		$goldVal = $data['GC'];
		$silverVal = $data['SC'];

		$stat = $root->new_child ( "current_stat" );
		$gold = $stat->new_child ( "money_gold" );
		$gold->set_attribute ( "value", $goldVal );
		$silver = $stat->new_child ( "money_silver" );
		$silver->set_attribute ( "value", $silverVal );

		/*********************************************************************/
		/************************ СПИСОК ПЛАТНЫХ УСЛУГ ***********************/

		/*// Получаем список платных сервисов
		$services = UC_PaymentServiceBase::GetPaymentServicesLocalized();
		assert(is_array($services));

		foreach ($services as $serviceName => $serviceOptions)
		{
		$activeservices[$serviceOptions['section']][] = $serviceName;
		}*/

		$_SESSION['dbId'] = $this->currentDbId;
		$_SESSION['world'] = $this->world;
/*
		$activeservices['Покупка премиума']['PS_BUY_PREMIUM_360'] = 'Премиум-доступ на 360 дней';
		$activeservices['Покупка премиума']['PS_BUY_PREMIUM_180'] = 'Премиум-доступ на 180 дней';
		$activeservices['Покупка премиума']['PS_BUY_PREMIUM_90'] = 'Премиум-доступ на 90 дней';
		$activeservices['Покупка премиума']['PS_BUY_PREMIUM_30'] = 'Премиум-доступ на 30 дней';
*/

		$activeservices[$this->lang['TITLE_PS_BUY_PREMIUM_360']]['PS_BUY_PREMIUM_360'] = $this->lang['PREMIUM_ON']. ' 360 '.$this->lang['DAYS'];
		$activeservices[$this->lang['TITLE_PS_BUY_PREMIUM_180']]['PS_BUY_PREMIUM_180'] = $this->lang['PREMIUM_ON']. ' 180 '.$this->lang['DAYS'];
		$activeservices[$this->lang['TITLE_PS_BUY_PREMIUM_90']]['PS_BUY_PREMIUM_90'] = $this->lang['PREMIUM_ON']. ' 90 '.$this->lang['DAYS'];
		$activeservices[$this->lang['TITLE_PS_BUY_PREMIUM_30']]['PS_BUY_PREMIUM_30'] = $this->lang['PREMIUM_ON']. ' 30 '.$this->lang['DAYS'];


    $activeservices[$this->lang['TITLE_PS_CHANGE_COORDS_0']]['PS_CHANGE_COORDS'] = $this->lang['TITLE_PS_CHANGE_COORDS'];
		$activeservices[$this->lang['TITLE_PS_CHANGE_COORDS_0']]['PS_CHANGE_COORDS_MELS'] = $this->lang['TITLE_PS_CHANGE_COORDS_MELS'];


		//Смотрим, можно ли в этом мире покупать ресы
		$allowBuyResources = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_FOR_MONEY_ALLOWED');
		if ($allowBuyResources) {

			$activeservices[$this->lang['TITLE_PS_BUY_RESOURCE_FOOD']]['PS_BUY_RESOURCE_FOOD'] = $this->lang['PACKET'].' '.$this->lang['FOOD'];
			$activeservices[$this->lang['TITLE_PS_BUY_RESOURCE_STASIS_ENERGY']]['PS_BUY_RESOURCE_STASIS_ENERGY'] = $this->lang['PACKET'].' '.$this->lang['STASIS_ENERGY'];
			$activeservices[$this->lang['TITLE_PS_BUY_RESOURCE_KADERIUM']]['PS_BUY_RESOURCE_KADERIUM'] = $this->lang['PACKET'].' '.$this->lang['KADERIUM'];
			$activeservices[$this->lang['TITLE_PS_BUY_RESOURCE_NANO_CRYSTAL']]['PS_BUY_RESOURCE_NANO_CRYSTAL'] = $this->lang['PACKET'].' '.$this->lang['NANO_CRYSTAL'];
			$activeservices[$this->lang['TITLE_PS_BUY_RESOURCE_PRODIUM']]['PS_BUY_RESOURCE_PRODIUM'] = $this->lang['PACKET'].' '.$this->lang['PRODIUM'];
		}

		$activeservices[$this->lang['TITLE_PS_BY_PREMIUM_BAN']]['PS_BY_PREMIUM_BAN'] = $this->lang['TITLE_PS_BY_PREMIUM_BAN'].' '.$this->lang['ON'].' 14 '.$this->lang['DAYS'];
		$activeservices[$this->lang['TITLE_PS_BY_PREMIUM_BAN']]['PS_BY_PREMIUM_BAN_30'] = $this->lang['TITLE_PS_BY_PREMIUM_BAN'].' '.$this->lang['ON'].' 30 '.$this->lang['DAYS'];

		if ($_SERVER['REMOTE_ADDR']=='195.182.156.98' || $_SERVER['REMOTE_ADDR']=='89.112.59.226' || $_SESSION['uid'] == 98)
		//if ($_SERVER['REMOTE_ADDR']=='81.201.21.212')
		{
			//$activeservices['Действия с аккаунтом']['PS_DELETE_INSTANCE'] = 'Удаление соты';
			//$activeservices['Действия с аккаунтом']['PS_DELETE_INSTANCE'] = ' Включение режима "Защита Богов"';
			//$activeservices['Бонус на производство всех ресурсов']['PS_BUY_RESOURCE_PRODIUM'] = 'Бонус на производство всех ресурсов';

			//$activeservices['Покупка ученых']['PS_BUY_RESOURCE_PRODIUM'] = 'Покупка 1000 ученых';

			$activeservices[$this->lang['TITLE_PS_UNIQUE_BUILDING']]['PS_GRANT_BUILDING'] = $this->lang['TITLE_PS_GRANT_BUILDING'];
			$activeservices[$this->lang['TITLE_PS_UNIQUE_BUILDING']]['PS_DELETE_UNIQUE_BUILDING'] = $this->lang['TITLE_PS_DELETE_UNIQUE_BUILDING'];
			$activeservices[$this->lang['TITLE_PS_BY_BONUS_RESOURCE']]['PS_BY_BONUS_RESOURCE'] = $this->lang['TITLE_PS_BY_BONUS_RESOURCE'];
		}

		//Смотрим, можно ли в этом мире покупать артефакты
		$allowBuyArt = DE_WorldSettings::CreateForCurentWorld()->get('BUY_ARTEFACT_FOR_MONEY_ALLOWED');
		if ($allowBuyArt)
		{
			$activeservices[$this->lang['BUY_ARTEFACTS']]['BUY_ART_180'] = $this->lang['ARTEFACTS_ON'].' 6 '.$this->lang['MONTH'];
			$activeservices[$this->lang['BUY_ARTEFACTS']]['BUY_ART_360'] = $this->lang['ARTEFACTS_ON'].' 12 '.$this->lang['MONTH'];
		}

		foreach ($activeservices as $section => $serviceArr)
		{
			$serviceList = $root->new_child ( "service_list" );
			$head = $serviceList->new_child ( "head" );
			$head->set_attribute ( "value", $section);
			//$head = $serviceList->new_child ( "split" );

			foreach ($serviceArr as $serviceId => $serviceData)
			{
				$sub = $serviceList->new_child ( "sub" );
				$sub->set_attribute("id", $serviceId);
				$sub->set_attribute("name", $serviceData);
			}
		}

		/*********************************************************************/
		/************************ СПИСОК ПЛАТНЫХ УСЛУГ ***********************/

		$lastOperation = PS_LastOperations::GetLastOperation($this->user, 'UC');
		
		if (count($lastOperation) > 0) {
			$serviceList = $root->new_child ( "last_operation" );
			$serviceList->set_attribute ('date', $lastOperation[0]['add_date']);
			//$textLastOper = $lastOperation[0]['service']."\n";

			$textLastOper = $this->langstat[$lastOperation[0]['action_name']]."\n";
	    
			if($lastOperation[0]['gc_amount'] > 0)
			{
				$textLastOper .= '[b]'.$this->lang['GC'].'[/b]: '.$lastOperation[0]['gc_amount'];
			}
	
			if($lastOperation[0]['sc_amount'] > 0)
			{
				$textLastOper .= '[b]'.$this->lang['SC'].'[/b]: '.$lastOperation[0]['sc_amount'];
			}

			$serviceList->set_attribute ('text', $textLastOper);
		} else {
			$serviceList->set_attribute ('text', '');
		}

		/*********************************************************************/
		/************************ КАКАЯ ТО ФИГНЯ ***********************/

		/*********************************************************************/
		/************************** СООБЩЕНИЕ ИГРОКУ *************************/
		if (!empty($this->message)) {
			$popap = $root->new_child ( "popup" );
			$popap->set_attribute("value", $this->message);
		}

		$alltabs = $root->new_child ( "alltabs" );

		SU_OutputHandler::Singleton()->addString( $docXML->dump_mem() , 'xmldata');
	}

	function ShowArtefactsShop($menu)
	{
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember($this->user, $world);
		$data = MU_SearchOperations::GetAccountData($member);

		$goldVal = $data['GC'];
		$silverVal = $data['SC'];

		$duration = '';
		if ($menu=='BUY_ART_180')
		{
			$duration = '6 '.$this->lang['MONTH_SHORT'];
			$d = 6;
		} else if ($menu=='BUY_ART_360') {
			$duration = '1 '.$this->lang['YEAR_SHORT'];
			$d = 12;
		}


		$xml= "<?xml version=\"1.0\" encoding=\"utf-8\"?><artefact_shop/>";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		$root->set_attribute( 'action', 'open');
		$root->set_attribute( 'gold', $goldVal);
		$root->set_attribute( 'silver', $silverVal);

		$filters = $root->new_child ("filters");

		$ch = $filters->new_child ("filter");
		$ch->set_attribute( 'id', 1);
		$ch2 = $ch->new_child ("item");
		$ch2->set_attribute( 'name', $this->lang['ARTEFACT_RESOURCE']);
		$ch2->set_attribute( 'id', 1);
		$ch2 = $ch->new_child ("item");
		$ch2->set_attribute( 'name', $this->lang['ARTEFACT_WAR']);
		$ch2->set_attribute( 'id', 2);

		$ch = $filters->new_child ("filter");
		$ch->set_attribute( 'id', 2);
		$ch2 = $ch->new_child ("item");
		$ch2->set_attribute( 'name', $this->lang['ARTEFACT_COMMON']);
		$ch2->set_attribute( 'id', 3);
		$ch2 = $ch->new_child ("item");
		$ch2->set_attribute( 'name', $this->lang['ARTEFACT_QUEST']);
		$ch2->set_attribute( 'id', 4);

		$ch = $root->new_child ("artefact_list");

		$arts = DS_Artefact::GetArtefactsForShop($_SESSION['world']);

		usort($arts,"art_sort");
		$res_arr = array('FOOD','PRODIUM','STASIS_ENERGY','KADERIUM','NANO_CRYSTAL' );
		foreach ($arts as $a)
		{
			if (in_array($a['artefactname'], array('GOLD_SPHERE_MOUSE','GOLD_SPHERE_MOBILEPHONE', 'MAJOR_COMBCOMPLEX_IGROMIR_TYPE')))
			continue;

			$ch2 = $ch->new_child('artefact');

			$bonus = '';
			if (($a['scopename']=='BUILDING') || ($a['scopename']=='SPHERE') || ($a['scopename']=='LOVE'))
			{
				$bonus .= '%2B'.$a['value']." ".$a['attrloc'];
			} else {
				//$bonus .= '%2B'.sprintf("%0.2f",$a['value']*100).'#'." ".$a['attrloc'];
				$bonus .= '%2B'.($a['value']*100).'#'." ".$a['attrloc'];
			}

			$ch2->set_attribute( 'id', $a['id_artefact']);
			$ch2->set_attribute( 'name', $a['title']);
			$ch2->set_attribute( 'icon', $a['artefactname']);
			$ch2->set_attribute( 'bonus_text', $bonus);
			$ch2->set_attribute( 'price_s', $a["price{$d}sc"]);
			$ch2->set_attribute( 'price_g', $a["price{$d}gc"]);
			$ch2->set_attribute( 'life', $d);
			$ch2->set_attribute( 'exp', sprintf($this->lang['ARTEFACT_INFO'], $duration, $a['scopetxt']));

			$filter_1 = in_array($a['attributename'],$res_arr)?1:2;
			$filter_2 = $a['scopename']=='SPHERE'?4:3;
			$ch2->set_attribute( 'filter_1', $filter_1);
			$ch2->set_attribute( 'filter_2', $filter_2);
		}


		//$str .= "</artefact_shop>";
		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

		/*if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
		{
		SU_DebugTools::LogAll($doc->dump_mem(),'art.log');
		}	*/
	}

	function getAllStatistic()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><cabinet />";

		$docXML = domxml_open_mem ($xml);
		$root = $docXML->document_element();

		$root->set_attribute ( "action", "operationStatistic" );

		/*********************************************************************/
		/**************** ДАННЫЕ О ПОСЛЕДНИХ ОПЕРАЦИЯХ *****************/
		$filter = $root->new_child ( "filter" );
		$types = $filter->new_child ( "types" );

		$type = $types->new_child ( "type" );
		$type->set_attribute("name", "filter1");
		$type->set_attribute("id", "filter1");

		$type = $types->new_child ( "type" );
		$type->set_attribute("name", "filter2");
		$type->set_attribute("id", "filter2");

		$operationlist = $root->new_child ( "operationlist" );


		$lastOperations = PS_LastOperations::GetAllperation($this->user);

		//SU_DebugTools::LogAll('lastOperations='.var_export($lastOperations, true),'statcab.log');
		foreach ($lastOperations as $key => $data)
		{
			$concurency = '';
			$operation = $operationlist->new_child ( "operation" );
			$operation->set_attribute("date", $data['add_date']);
			$operation->set_attribute("text", $this->langstat[$data['action_name']]);

			if ($data['gc_amount'] != 0) {
				$concurency .= $this->lang['GC'].': '.$data['gc_amount'].' ';
			}
			if ($data['sc_amount'] != 0) {
				$concurency .= $this->lang['SC'].': '.$data['sc_amount'];
			}
			$operation->set_attribute("concurency", $concurency);
		}

		//$alltabs = $root->new_child ( "alltabs" );
		SU_OutputHandler::Singleton()->addString( $docXML->dump_mem() , 'xmldata');
	}

	function showSumMenu($params) {
		require_once constant ( 'INCLUDE_PATH' ) . "/Smarty/Smarty.class.php";

		// создаем объект смарти
		$tpl = new Smarty ();
		$tpl->template_dir = $_SERVER ['DOCUMENT_ROOT'] . "/ds/templates/";
		//$tpl->compile_dir = constant('SMARTY_CACHE_PATH');
		$tpl->compile_dir = $_SERVER ['DOCUMENT_ROOT'] . '/ds/utils/files/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		$tpl->assign('lang', $this->lang);
		$tpl->assign ( 'PARAMS', $params );

		$str = "xmldata=<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" . $tpl->fetch ( 'user_shop.tpl' );
		//SU_DebugTools::LogAll($str, 'AnnitaCab.log');
		SU_OutputHandler::Singleton ()->addReadyString ( $str );

		return;
	}
}

function art_sort($a, $b)
{
	return strcmp($a['scopename'], $b['scopename']);
}

?>