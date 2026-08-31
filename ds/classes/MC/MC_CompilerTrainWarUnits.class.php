<?php $CVS_Version = '$Revision$ ; $Date$';

class MC_CompilerTrainWarUnits extends MC_CoompilerBase 
{

	/**
	 * Enter description here...
	 *
	 * @var array()
	 */
	private $data = array();
		
	public function __construct()
	{
		
	}
		
	static public function GetSingl()
	{
		return parent::CreateSingl(__CLASS__);
	}
	
	public function addData(DE_Comb $comb, DE_Unit $unit, $trained, $leftToTrain)
	{
		assert(is_int($trained) && $trained > 0);
		assert(is_int($leftToTrain) && $leftToTrain >= 0);
		
		$this->data[$comb->getUserInstanceID()][$comb->getID()][$unit->getID()][] = array
		(
			'TRAINED' => $trained, 
			'LEFT_TO_TRAIN' => $leftToTrain			
		); 
	}
	
	public function compile(GB_Date $messageDate)
	{
		foreach($this->data as $UIID => $trainData)
		{
			foreach($trainData as $combID => $unitData)
			{				
				foreach($unitData as $unitID => $productionData)
				{					
					
					$trained = 0;
					$leftToTrain = 0;
					$freeCount = 0;
					$totalCount = 0;
				
					foreach($productionData as $k=>$v)
					{
						$trained += $v['TRAINED'];
						$leftToTrain += $v['LEFT_TO_TRAIN'];
						
						/*
						if(($trained - $leftToTrain) <= 0)
						{
							$freeCount++;
						}*/
						
						if(($v['LEFT_TO_TRAIN'] - $v['TRAINED']) <= 0)
						{
							$freeCount++;
						}
						
						$totalCount++;
					}
					
					
					$messageParams[0] = DE_Unit::CreateFromID((int)$unitID)->getUnitNameLocolized();
					$messageParams[1] = DE_Comb::CreateFromID((int)$combID)->getFullName();
					$messageParams[2] = $trained;
					$messageParams[3] = $leftToTrain;
					$messageParams[4] = $freeCount;
					$messageParams[5] = $totalCount;
					
					DE_SystemMessages::AddMessageFromMessageCompiler($messageDate, $UIID, "TRAIN_WAR_UNIT", $messageParams, 0, 'GREEN', $accumulatorStatus = 'ACCUMULATE');			
				}								
			}			
		}
		
		
	}
}


?>