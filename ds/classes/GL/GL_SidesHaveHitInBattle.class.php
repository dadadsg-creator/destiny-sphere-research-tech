<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс логики игровой который определят во время боя какие 
 * стороны в текущем раунде могут наносить удары.
 *
 */
class GL_SidesHaveHitInBattle
{
	static public function Count(DE_Battle $battle)
	{
		$result = array(
			'ATTACKER' => true,
			'DEFENDER' => true
		);
		
		$retreatSideName = $battle->getRetreatSideName()->value;
		
		if(!empty($retreatSideName) && !$battle->isRetreatWithEmptyDamage())
		{
			switch ($retreatSideName)
			{
				case 'A':
					$result['ATTACKER'] = false;
					break;
				case 'D':
					$result['DEFENDER'] = false;
					break;
				default:
					assert(false);
			}
		}
		
		assert($result['ATTACKER'] || $result['DEFENDER']);
		return $result;
	}
}


?>