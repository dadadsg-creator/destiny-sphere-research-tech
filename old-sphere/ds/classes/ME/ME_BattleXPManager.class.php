<?php

class ME_BattleXPManager
{
	static private $classes = array(
			0 => 'GL_BattleXP',
			1 => 'GL_BattleXPTurnirClassic',
			2 => 'GL_BattleXPTurnirQuest',
		);
	
	static public function Count(DE_Battle $battle)
	{
		$worldBattleXPType = (int)DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_XP_TYPE'); 

		assert(key_exists($worldBattleXPType, self::$classes));
		
		$className = self::$classes[$worldBattleXPType];
		
		switch ($worldBattleXPType) {
			case 0:
				return GL_BattleXP::Count($battle);
				break;
				
			case 1:
				return GL_BattleXPTurnirClassic::Count($battle);
				break;
		
			case 2:
				return GL_BattleXPTurnirQuest::Count($battle);
				break;
				
			default:
				break;
		}
	}
}

?>