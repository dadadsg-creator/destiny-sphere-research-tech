<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_AddRoundResults
{
	static public function exec(DE_BattleRound $round, DE_Army $attaker, DE_CombDefendArmy $defender)
	{
		$attackerUnitsGroupCollection = $attaker->getUnitsGroupCollection();
		$attackerUnitsGroupCollection->resetNext();
		while($unitsGroup = $attackerUnitsGroupCollection->next())
		{
			DE_BattleRoundResult::CreateInDB($round, $round->getBattle()->getBattleParticipantBy($unitsGroup->getArmy()->getUserInstance()), $unitsGroup);			
		}
				
		$defender->getCombDefendArmysCollection()->resetNext();
		while($defendArmy = $defender->getCombDefendArmysCollection()->next())
		{
						
			$defendArmy->getUnitsGroupCollection()->resetNext();
			while($unitsGroup = $defendArmy->getUnitsGroupCollection()->next())
			{
				$userInstance = $unitsGroup->getArmy()->getUserInstance();
								
				//Это проверка если такой участник в боеЕсли есть то надо вставлять.
				//Это затфка елси участник боя раньше не добавился. То надо добавить сейчас. Что бы уж точно не промахнуться
				//Вобщем случае6ться у нас такая фигня когда бой идет. раунд осчитываеться а учатника такого нет поучему-то.
				//Пока правим вот такой затфкой.
				//Есть идея что такое происходит когда рфреши слишком часто чаще  чем концы раундов и подкрепление приезжает между раундами 
				//и не подает в бой.
				$userInstance = $unitsGroup->getArmy()->getUserInstance();
				$battleParticipant = $round->getBattle()->getBattleParticipantBy($userInstance);				
				if(!($battleParticipant instanceof DE_BattleParticipant))
				{
					$battleParticipant = ME_RefreshBattle::AddDefenderBattleParticipant($round->getBattle(), $userInstance);	
				}
									
				// Тут мы ставим затыку на случай ситуации когда армия прибыла на защиту но почему-то не попала в лог
				// Есть тская ситуация. Елси когда-то захотим именно отловить причину ошибки то надо в таблички 
				// ds_battleresult сделать сложный внешний ключь (id_battleparticipant, id_unitcontainer) на
				// табличку ds_battleparticipanthasunitcontainer и тогда то место где не добавилось вылезит в виде черного экрана
				// Ну и само сабой надо будет закомоентрова эту затыку.
				// ТОлдько надо учесть что в БД уже есть много таких записей. Надо подождать неделю после вставки 
				// этой затыки что бы убалились логи. ВЫставолено 2006-06-17
				ME_RefreshBattle::AddDefenderArmy($round->getBattle(), $battleParticipant, $unitsGroup->getArmy());
														
				DE_BattleRoundResult::CreateInDB($round, $battleParticipant, $unitsGroup);				
			}
		}
	}
}


?>