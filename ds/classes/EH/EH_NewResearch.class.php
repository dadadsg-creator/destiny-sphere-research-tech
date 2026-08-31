<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2010/07/07 09:08:17 $';


// обработчик исследования науки

class EH_NewResearch extends EH_Event
{

	public $checktime;

	function EH_NewResearch ()
	{
		EH_Event::EH_Event ();
	}

	function handleEvent($eqid)
	{
		settype($eqid, 'integer');
		assert($eqid > 0);

		$this->eqid = $eqid;

		if ( !$this->checktime )
		{
			$this->checktime = 'now()';
		}

		if(!$this->getEventParams())
		{
			return false;
		}

		// Название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"])->getName()."{/b}";

		// Данные о науке
		$ehres = DS_Science::GetScienceDataById((int)$_SESSION['lang'], (int)$this->eventparams["ID_Science"]);

		// Название науки
		$science['sciencename'] = "{b} ".$ehres[0]['sciencename']."{/b}";
		$messparams[1] = "{b} ".$ehres[0]['sciencename']."{/b}";
		// Уровень науки
		$science['level'] = "{b} ".$ehres[0]['level']."{/b}";

		$ehres = "";

		// Турн тайм
		$this->turntime = $this->worldSettings->get('TURN_TIME');

		if ($this->_isTimeToHandle())
		{ // если закончено

			// Добавляем игроку науку
			DS_Science::AddScinceToUser((int)$this->eventparams["ID_UserInstance"], (int)$this->eventparams["ID_Science"]);

			// Очки за науку
			$score = DS_Science::GetScienceScore((int)$this->eventparams["ID_Science"]);
			
			// Увеличиваем научный рейтинг игрока
			DE_Ranking::CreateFromID((int)$this->eventparams["ID_UserInstance"])->deltaScienceRanking($score);
			
			// Системное сообщение
			// На соте % % завершено.
			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_RESEARCH_MESS", $messparams, 0, 'BLUE' );
			
			// прибиваем эвент
			$this->setTrash ();
		  
		} else {  // не закончено

			// общее кол-во ученых на всех сотах
			$userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID((int)$this->eventparams["ID_UserInstance"]);

			$newscientistscount = 0;

			$userCombsCollection->resetNext();
			while($comb = $userCombsCollection->next())
			{
				$newscientistscount += $comb->getAttributes()->getAttribute('CURRENT_AMOUNT', 'SCIENTIST');
			}

			// если отличается от начального
			if ( $newscientistscount != $this->eventparams["ScientistsCount"] ) {

				// Проверяем, ускорена ли наука
				if (DS_PaymentService::IsScienceAcceleraded((int)$this->eqid)) {
					// Нафиг ничего не делаем

					// Высылаем сообщение
					$prog = $this->_getComplitPart() * 100;

					if ( $prog > 100 ) $prog = 100;
					if ( $prog < 0 ) $prog = 0;

					$messparams[2] = number_format ( $prog, 1, ".", "" );

					$event = DE_EventQueue::CreateFromID((int)$this->eqid);
					$messparams[3] = $event->getEndDate()->getSystemMessageFormat();

					if ( $prog > 0 )
					{
						DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_RESEARCH_MESS", $messparams, 0, 'BLUE', 'NOACCUMULATE' );
					}
					return true;
				}

				if ( $newscientistscount == 0 )
				{ // если их совсем нет

					// Убиваем изучение
					DS_Science::CansecSciense((int)$this->eventparams["ID_UserInstance"], (int)$this->eventparams["ID_Science"], (int)$this->eqid);

					// Cистемное сообщение
					// На соте % исследование % отменено, так как не осталось исследователей.
					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "CANCEL_RESEARCH_MESS1", $messparams, 0, 'BLUE' );

					// Прибиваем эвент
					$this->setTrash ();

					return true;
				}

				$raceID = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"])->getRaceID();

				// Бонус на науку
				$science_bonus = DS_Science::GetScienceBonusByRace($raceID);

				// Считаем новое время окончания изучания
				$eventQueue = DE_EventQueue::CreateFromID((int)$this->eqid);

				$science = ceil( $newscientistscount + ($newscientistscount * $science_bonus) );
				$newendtime = round($eventQueue->getStartDate()->value + $this->turntime*60*$this->eventparams["TotalResearchCosts"]/$science);

				// окончилось ли исследование с новым кол-вом ученых
				if($newendtime >= 2147000000)
				{
					// Прибиваем исследование
					DS_Science::CansecSciense((int)$this->eventparams["ID_UserInstance"], (int)$this->eventparams["ID_Science"], (int)$this->eqid);

					// Системное сообщение
					$mess[0] = "{b} ".$science['sciencename']."{/b}";
					//Количество ученых недостаточно для изучения науки: %. Исследование отменено.
					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "EH_NEW_RESEARCH_ERROR_NOT_ENOUGH_SCIENTISTS", $mess, 0, 'BLUE', 'NOACCUMULATE' );

					// прибиваем эвент
					$this->setTrash ();

					return true;
				}

				if($this->checktime == 'now()')
				{
					$checkTimeStamp = time();
				} else {
					$checkTimeStamp = GB_TypeFactory::CreateBase('GB_Date', $this->checktime)->value;
				}

				if ($newendtime < $checkTimeStamp)
				{ // закончилось

					// Добавляем игроку науку
					DS_Science::AddScinceToUser((int)$this->eventparams["ID_UserInstance"], (int)$this->eventparams["ID_Science"]);

					// Системное сообщение
					// На соте % % завершено.
					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_RESEARCH_MESS", $messparams, 0, 'BLUE' );

					$this->setTrash ();

					return true;
				}

				// Не закончилось
				$newEndTimeObject = GB_TypeFactory::CreateBase('GB_Date', $newendtime);

				//изменяем время окончания
				$eventQueue->setEndDate($newEndTimeObject);

				$ehsql = sprintf ( "update only ds_userhasscience
							set endtime = '%s'
							where id_userinstance = %d
							and id_science = %d",
				$newEndTimeObject->getPgFormat(),
				$this->eventparams["ID_UserInstance"],
				$this->eventparams["ID_Science"] );

				$ehres = $this->common->executeQuery ( $ehsql );
				if ( $ehres === false ) {
					$this->errormsg = "SYSTEM ERROR - EH_NewResearch::handlEvent14";

					return false;
				}

				// изменяем параметры эвента
				$eventAttributes = DE_EventQueueAttributes::CreateFromEventID((int)$this->eqid);
				$eventAttributes->setAttribute('ScientistsCount', $newscientistscount);
			}

			// Высылаем сообщение
			$prog = $this->_getComplitPart() * 100;

			if ( $prog > 100 ) $prog = 100;
			if ( $prog < 0 ) $prog = 0;

			$messparams[2] = number_format ( $prog, 1, ".", "" );

			$event = DE_EventQueue::CreateFromID((int)$this->eqid);
			$messparams[3] = $event->getEndDate()->getSystemMessageFormat();

			if ( $prog > 0 )
			{
				DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_RESEARCH_MESS", $messparams, 0, 'BLUE', 'NOACCUMULATE' );
			}
		}
		return true;
	}

}

?>