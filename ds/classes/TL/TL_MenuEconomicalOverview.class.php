<?php $CVS_Version = '$Revision$ ; $Date$';

class TL_MenuEconomicalOverview extends TL_BaseMenu
{
	static private $tmpLocalization = array
	(
	'BUILDINGS_COUNT' => "{:VENDORS_COUNT:}",//'Количество зданий',
	'BASE_PRODUCTION' => "{:BASE_PRODUCTION:}",//'Базовая продукция',
	'SUMMARY_PRODUCTION' => "{:EO_SP:}",//'Продукция с бонусами',
	'SUMMARY_USING' => "{:EO_SU:}",//'Суммарное потребление',
	'BALANCE' => "{:EO_B:}",//'Баланс'
	);

	static private $resourceColours = array
	(
	'FOOD' =>  '#63B263',
	'KADERIUM' => '#FFC77B',
	'PRODIUM' => '#9C9E4A',
	'NANO_CRYSTAL' => '#6396E7',
	'STASIS_ENERGY' => 	'#B5B28C'
	);

	static public function FormMenu(TI_EconomicalOverview $incoming)
	{

		$statistic = $incoming->getStatistic();

		$localization = DE_InterfaceLocalization::CreateForCurentLang();
		//SU_DebugTools::LogAll($_SESSION, 'AnnitaCom.log');
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><table/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();

		$out = "<table>";
		$out .= "<tr><td width=\"38\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\"> - </td>";

		foreach($statistic as $resourceName=>$paramsList)
		{
			$resourceList[] = $resourceName;
			$out .= "<img width=\"12\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\">".$resourceName."</img>";
		}
		$out .= "</tr>";

		foreach($paramsList as $paramName => $v)
		{
			$out .= "<tr>";
			$out .= "<td width=\"38\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\"><FONT COLOR=\"#FFFFFF\" FACE=\"Arial\"><b>".self::$tmpLocalization[$paramName]."</b></FONT></td>";

			foreach($resourceList as $k => $resourceName)
			{
				$val = $statistic[$resourceName][$paramName];
				$outVal = number_format(round($val), 0, '.', ' ');

				if($val >= 0)
				{
					$color = self::$resourceColours[$resourceName];
				}else{
					$color = '#DD0000';
				}

				$out .= "<td width=\"12\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" style=\"center\" alpha=\"80\"><b><FONT COLOR=\"".$color."\" FACE=\"Arial\">".$outVal."</FONT></b></td>";


			}
			$out .= "</tr>";
		}

		// Покупка бонуса ресурсов
		$out .= '<tr>';
		$out .= '<td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:BUY_RESOURCES:}</b></FONT></td>';
		$out .= '</tr>';

		// Покупка пакетов ресурсов
		$out .= '<tr>';
		$out .= '<td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:BUY_BONUSES:}</b></FONT></td>';
		$out .= '</tr>';

		// Подключаем локализацию кабинета
		//require_once($_SERVER['DOCUMENT_ROOT']."/ds/language/".$_SESSION['language']."/user_cabinet_payment_services.lang");
		$i = 1;

		// Покупка пакета ресурсов
		//Сколько еще игрок может купить
		$boughtRes = DE_UserBoughtResources::CreateFromID($_SESSION['uid'].'_'.$_SESSION['world']);
		$resources = array(
		'STASIS_ENERGY' => 200000,
		'FOOD' => 100000,
		'KADERIUM' => 30000,
		'NANO_CRYSTAL' => 20000,
		'PRODIUM' => 5000
		);


		// Получаем количество денег у игрока
		$world = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
		$member = new MU_EntityOperationMember(DE_User::CreateFromID($_SESSION['uid']), $world);
		$account = MU_SearchOperations::GetAccountData($member);
		assert(!empty($account));

		// Проверяем, можно ли в этом мире покупать ресы за деньги
		$buyAllowed = (int)DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_FOR_MONEY_ALLOWED');

		// Если в мире можно покупать ресурсы
		if($buyAllowed == 1)
		{
			foreach($resourceList as $k => $resourceName)
			{
				// Если услуга активна
				if (DS_PaymentService::IsServiceActive('PS_BUY_RESOURCE_'.$resourceName)) {
					// Получаем стоимомсть услуги
					$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BUY_RESOURCE_'.$resourceName);
					assert($price > 0);

					$out .= '<MSG id="'.$i.'" ';
					// Покупка пакетов кадериума
					$out .= 'titl="'.$localization->get('PS_BY_RESOURCE').'" ';
					$out .= 'head="'.$localization->get('PS_BY_RESOURCE').' '.$localization->get('BONUS_'.$resourceName).'" ';

					// 1 пакет = % %\nСтоимость одного пакета % серебряных кредитов\nСеребро списывается со счета в мире
					$messageParams[0] = $resources[$resourceName]; // Количество в одном пакете
					$messageParams[1] = $localization->get('BONUS_'.$resourceName); // Ресурс
					$messageParams[2] = $price; // Цена
					//$messageParams[3] = DE_Money::CreateFromShortName('SC')->getLocolizedName($_SESSION['lang']); // Кредиты
					$txt = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_RESOURCE_DESC', $messageParams, null, null, false);
					$out .= 'txt="'.$txt.'" ';

					// Количество купленных пакетов
					$now = $boughtRes->get($resourceName);
					// Максимальное количество пакетов в мире
					$max = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_'.$resourceName.'_LIMIT');
					// Количество, которое игрок еще может купить
					$maxAvaible = (int)floor(($max - $now)/$resources[$resourceName]);
					// Максимальное количество пакетов, на которое у игрока хватает денег
					$maxAvaibleFinal = min($maxAvaible, (int)floor($account['SC']/$price));



					$out .= 'rem="'.$localization->get('PS_BY_RESOURCE_DESC').' '.$maxAvaible.'%0A'.$localization->get('MAX_AVAIBLE_BUY_RESOURCE').' '.$maxAvaibleFinal.'" ';
					$out .= 'avail="'.$maxAvaibleFinal.'" ';
					$out .= 'price="'.$price.'" ';

					// Если он больше не может покупать больше пакетов, смотрим почему
					if ($maxAvaibleFinal == 0) {
						if ($maxAvaible == 0) {
							// Больше нельзя покупать пакеты в этом мире
							$out .= 'status="'.$localization->get('CAN_NOT_BUY_RESOURCE').'" ';
						} else {
							// Кончились деньги
							$messageParams = array();
							$out .= 'status="'.DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NOT_ENOUGH_CREDIT', $messageParams, null, null, false).'" ';
						}
					} else {
						$out .= ' status="" ';
					}

					$out .= ' res="'.$resources[$resourceName].'" />';
				}
				$i ++;
			}
		}

		// На всякий случай
		$i = 6;

		// Покупка бонуса ресурсов
		foreach($resourceList as $k => $resourceName)
		{
			// Если услуга активна
			if (DS_PaymentService::IsServiceActive('PS_BY_BONUS_'.$resourceName)) {
				// Получаем стоимомсть услуги
				$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BY_BONUS_'.$resourceName);
				assert($price > 0);

				$out .= '<MSG id="'.$i.'" ';
				$out .= 'titl="'.$localization->get('PS_BY_BONUS_RESOURCE').'" ';


				// Купить бонус 25 процентов на производство%0A% за % %
				$messageParams[0] = $localization->get('BONUS_'.$resourceName); // Ресурс
				$messageParams[1] = $price; // Цена
				//$messageParams[2] = DE_Money::CreateFromShortName($credit)->getLocolizedName($_SESSION['lang']); // Кредиты
				$head = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_BONUS_RESOURCE_MESS', $messageParams, null, null, false);
				$out .= 'head="'.str_replace('0A', '%0A', $head).'" ';

				// Вы можете купить бонус 25 процентов на производство любого ресурса на всех ваших сотах и мирах.\nПокупка бонуса возможна, если у вас еще не куплен бонус на этот ресурс или\nпакет бонусов на все ресурсы.\nБонус действует 2 недели.\nСеребро списывается со счета в мире.
				$txt = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_BONUS_RESOURCE_DESC', array(), null, null, false);
				$out .= 'txt="'.str_replace('0A', '%0A', $txt).'" ';

				// Проверяем наличие действующего бонуса у игрока
				if (DS_Bonus::IsUserHasBonuser($_SESSION['uiid'], $resourceName)) {
					// Помечаем, что у игрока уже есть этот бонус
					$out .= 'avail="0" status="'.str_replace('0A', '%0A', $localization->get('BONUS_END_DATE')).' '.DS_Bonus::GetUserBonusEndDate($_SESSION['uiid'], $resourceName)->getPgFormat().'"';
				} else {
					// Проверяем, достаточно ли денег у игрока
					if($account['SC'] < $price)
					{
						$messageParams = array();
						$out .= 'avail="0" status="'.DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NOT_ENOUGH_CREDIT', $messageParams, null, null, false).'"';
					} else {
						$out .= 'avail="1" status=""';
					}
				}

				$out .= ' />';
			}

			$i ++;
		}

		// закладки
		self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());

		$forOut = str_replace('<table>', $out, $doc->dump_mem());

		return $forOut;
	}
}

?>