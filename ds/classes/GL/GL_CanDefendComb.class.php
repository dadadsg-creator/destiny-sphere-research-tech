<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CanDefendComb
{
	static private $errorCode = '';

	static public function GetErrorCode()
	{
		return self::$errorCode;
	}

	static public function CanDefend(DE_Comb $combFrom, DE_CombInterface $combTo, GB_Date $chekDate)
	{
		//Корректные коррдинаты цели
		if($combTo->isNull())
		{
			self::$errorCode = 'WRONG_TARGET';
			return false;
		}

		$combName = $combTo->getName();
		if(empty($combName))
		{
			self::$errorCode = 'EMPTY_COMB';
			return false;
		}

		// Если сота принадлежит DestinySphere
		if($combTo->getUserInstanceID() == 0)
		{
			self::$errorCode = 'CANT_DEFEND_COMB';
			return false;
		}

		//Открыты центральные ворота у отправителя
		if(!$combFrom->haveOpenCentralGate())
		{
			self::$errorCode = 'NO_FROM_CENTRAL_GATE';
			return false;
		}

		//Есть ли гондонотранспортер у отправителя
		if(!$combFrom->haveGandolaTransporter())
		{
			self::$errorCode = 'NO_GONDONOTRANSPORTER';
			return false;
		}

		//Если сота получаеля на другом кольце то есть межкольцевые ворота
		if(($combTo->getRingNumber() != $combFrom->getRingNumber()) && !$combFrom->haveOpenRingGate())
		{
			self::$errorCode = 'NO_RING_GATE';
			return false;
		}

		//Попытка ставить на защиту своей же соты
		if($combTo->getID() == $combFrom->getID())
		{
			self::$errorCode = 'SAME_USER_COMB';
			return false;
		}

		//Открыты центральные ворота у получаетля
		if(!$combTo->haveOpenCentralGate())
		{
			self::$errorCode = 'NO_TO_CENTRAL_GATE';
			return false;
		}

		// Если лиги включены и лига защитника больще
		if(DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') == 1)
		{
			$attackerLeague = $combFrom->getUserInstance()->getLeagueId();
			$defenderLeague = $combTo->getUserInstance()->getLeagueId();

			// Если лига атакера равна 1, а лига атакуемого равна 3 или наоборот, запрещаем защиту
			if (($attackerLeague == 1 && $defenderLeague == 3) || ($attackerLeague == 3 && $defenderLeague == 1)) {
				self::$errorCode = 'DENIED_DEFEND_COMB_WHIT_SMALLER_LEAGUE';
				return false;
			}

			// Если стоит модифаер запрет полет между этими лигами
			if(
			($attackerLeague < $defenderLeague && DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_CAN_ATTACK_'.$attackerLeague.'_'.$defenderLeague.'_LEAGUE') == 0)
			||
			($attackerLeague > $defenderLeague && DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_CAN_ATTACK_'.$defenderLeague.'_'.$attackerLeague.'_LEAGUE') == 0)
			)
			{
				self::$errorCode = "DENIED_DEFEND_COMB_WHIT_SMALLER_LEAGUE";
				return false;
			}
		}

		//Если мир RVR то нельзя ехать к другой рассе
		assert(key_exists('worldtype', $_SESSION));
		$worldTypeName = $_SESSION["worldtype"];

		$raceIDFrom = $combFrom->getUserInstance()->getRaceID();
		$raceIDTo = $combTo->getUserInstance()->getRaceID();

		if(($worldTypeName == 'RACE_VS_RACE') && ($raceIDFrom != $raceIDTo))
		{
			self::$errorCode = 'OTHER_RACE_RVR';
			return false;
		}


		//Нельзя высылать на азщиты соты которую атакуешь
		$userArmysCollection = DC_UserInstanceArmys::CreateFromUserInstanceID($combFrom->getUserInstanceID());

		$userArmysCollection->resetNext();
		while($army = $userArmysCollection->next())
		{
			if(!$army->getInAction())
			{
				continue;
			}

			$armyAction	= $army->getArmyAction();

			if(($armyAction->getActionType() == 'MOVE_TO_ATTACK' || $armyAction->getActionType() == 'IN_BATTLE')
			&& $armyAction->getCombToID() == $combTo->getID())
			{
				self::$errorCode = "DENIED_DEFEND_COMB_WHEN_YOU_ATTACK_HER";
				return false;
			}
		}

		//Проверка периддов мира
		/*$peacePeriod = DS_Battle::GetCurrentPeacePeriod();

		if(is_array($peacePeriod))
		{
		self::$errorCode = 'PEACE_PERIOD';
		return false;
		}*/

		if ($combTo->getUserInstance()->getUser()->getAccountActive() == 'f')
		{
			self::$errorCode = "DENIED_DEFEND_BANNED_USER";
			return false;
		}

		if($combFrom->getUserInstance()->getUser()->getPremiumBan()->dateInPeriod($chekDate))
		{
			// !!!!
			self::$errorCode = "ARMY_ACTION_PREMIUM_BAN";
			return false;
		}

		if($combTo->getUserInstance()->getUser()->getPremiumBan()->dateInPeriod($chekDate))
		{
			self::$errorCode = "DENIED_DEFEND_BANNED_USER";
			return false;
		}

		//проверкаа режима отпуска
		$res = GL_IsUserInVocationMode::Test($combTo->getUserInstance());
		if(count($res) > 0)
		{
			self::$errorCode = "CANT_DEFEND_USER_IN_VOCATION_MODE";
			return false;
		}

		if (!$combTo->getIsCapture()) {
			//Проверка на наличие щита (ЕСЛИ СОТА НЕ ЗАХВАТЫВАЕМАЯ!!!)
			$powerShild = DE_PowerShild::CreateFromUserInstance($combTo->getUserInstance()->getID());
			if($powerShild->isWorks($chekDate))
			{
				self::$errorCode = "CANT_DEFEND_USER_IN_POWER_SHILD";
				return false;
			}
		}

		if(GL_CanMooveArmysAfterFreez::Test($combFrom->getUserInstance(), $chekDate))
		{
			self::$errorCode = "CANT_MOOVE_ARMY_AFTER_FREEZ";
			return false;
		}

		if($combTo->getUserInstance()->haveNewbDefend())
		{
			self::$errorCode = "CANT_DEFEND_NEWB";
			return false;
		}

		// Разрешение защищать временную колонию
		$allowDefendCaptureColony = DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_DEFEND_CAPTURE_COLONIES');

		// Если можно атаковать сокомплексников, то их, соответственно, нельзя защищать
		$allowDefend = (boolean)DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_DEFEND_COMBS');

		if (!$allowDefend)
		{
			// Если это не временная колония, или ее защащать нельзя
			if (!$combTo->getIsCapture() ||  $allowDefendCaptureColony != 1) {
				self::$errorCode = 'NO_DEFEND_ANY_COMB';
				return false;
			}
		}

		// Если можно атаковать сокомплексников, то их, соответственно, нельзя защищать
		$allowCombComlexAttack = (boolean)DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_COMBCOMPLEX_ATTACK');
		if(!(
		// соты в одном комлексе
		(($combTo->getComplexID() == $combFrom->getComplexID()) && !$allowCombComlexAttack)
		||
		// игроки в одном альянсе
		(
		($combTo->getUserInstance()->getAlliance()->getID() == $combFrom->getUserInstance()->getAlliance()->getID())
		&&
		!$combFrom->getUserInstance()->getAlliance()->isEmpty()
		)
		// соты одного игрока
		||
		($combTo->getUserInstance()->getID() == $combFrom->getUserInstance()->getID())
		)
		)
		{
			self::$errorCode = 'NO_ALLIANCE';
			return false;
		}
		
		// Соту npc защищать нельзя
		if($combTo->getUserInstance()->getUser()->getIsUserNpc() == 't')
		{
			self::$errorCode = 'DEFEND_NPC_COMB';
			return false;
		}

		// Смотрим, захватываемая это колония или нет
		// РЕШИЛИ РАЗРЕШИТЬ ЗАШЩИТУ
		/*if ($combTo->getIsCapture()) {
		// Есть ли временная защита
		$defend = DE_CombProtectiveField::CreateFromCombID($combTo->getCombID());
		if ($defend->getTemporaryDefend()->value > time())
		{
		self::$errorCode = "CANT_DEFEND_TEMPORARY_DEFEND_COLONY";
		return false;
		}
		}*/


		return true;
	}
}


?>