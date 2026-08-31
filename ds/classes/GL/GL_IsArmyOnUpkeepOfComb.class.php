<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Принимаем решение находиться ди эта армия на апкипе сейчас у соты.
 *
 */
class GL_IsArmyOnUpkeepOfComb
{
	static public function Count(DE_Comb $comb, DE_Army $army)
	{
		if($army->getBaseCombID() != $comb->getID())
		{
			return false;
		}
		
		$armyAction = $army->getArmyAction();
			
		if($armyAction->isNull())
		{
			$condition = true;
		}else{
			if($armyAction->getActionType() == 'IN_DEFEND')
			{
				$condition = true;
			}else{
				$condition = false;
			}
		}		
		
		return $condition;
	}
}


?>