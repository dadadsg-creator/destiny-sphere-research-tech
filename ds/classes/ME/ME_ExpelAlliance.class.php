<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_ExpelAlliance
{
	static public function Exec(DE_Alliance $alliance, DE_UserInstance $userInstance)
	{
		// Отзываем армии пользователя, находящиеся на защите соальянсовикв	
		$userArmies = DS_Alliance::GetUserArmyOnDefend($alliance, $userInstance);
		
		for ( $i=0; $i<sizeof ( $userArmies ); $i++ )
		{
			$army = DE_Army::CreateFromID($userArmies[$i]['id_unitcontainer']);
			$GB_Date = GB_TypeFactory::CreateBase('GB_Date', time());
			
			//ME_CancelDefend::Cancel($army, $GB_Date);

			// Смотрим, бьется ли сейчас армия
			if(count (DS_Battle::NotEndedBattlesOnComb($army->getCurentComb())) > 0) {
				// Помечаем армии пользователя, находящиеся в бою на соте соальянсовика,
				//чтобы они отозвались после окончания боя
				ME_ArmyOccurrence::CreateOccurrence($army, 'END_BATTLE', 'RETURN_ARMY_FROM_DEFEND');
			} else {
				// Отзаваем армию с защиты
				ME_CancelDefend::Cancel($army, $GB_Date);
			}
		}

		//Отзаваем армии соальянсовиков, находящиеся на защите игрока
		$userArmies = DS_Alliance::GetArmyOnUserCombDefend($alliance, $userInstance);
		for ( $i=0; $i<sizeof ( $userArmies ); $i++ )
		{
			$army = DE_Army::CreateFromID($userArmies[$i]['id_unitcontainer']);
			$GB_Date = GB_TypeFactory::CreateBase('GB_Date', time());
			
			// Смотрим, бьется ли сейчас армия
			if(count (DS_Battle::NotEndedBattlesOnComb($army->getCurentComb())) > 0) {
				// Помечаем армии пользователя, находящиеся в бою на соте соальянсовика,
				//чтобы они отозвались после окончания боя
				ME_ArmyOccurrence::CreateOccurrence($army, 'END_BATTLE', 'RETURN_ARMY_FROM_DEFEND');
			} else {
				// Отзаваем армию с защиты
				ME_CancelDefend::Cancel($army, $GB_Date);
			}		
		}
		
		//Вызываем хранимую процедуру
		DS_Alliance::ExpelAlliance($alliance, $userInstance);
	}
}

?>