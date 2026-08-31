<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecLogOperation
{
	static public function Log(MU_EntityOperationMember $memberFrom, MU_EntityOperationMember $memberTo, MU_EntityMoneySumm $summ, $listID, MU_EntityOperation $operatiooForLog)
	{		
		assert(is_int($listID) && $listID > 0);
		

		$nowDate = GB_TypeFactory::CreateBase('GB_Date', time());		
		
		$dateString = $nowDate->getPgFromat();
		$operationName = $operatiooForLog->getActionType();
		
		$fromUserName = $memberFrom->getUser()->getName();				
		$fromWorldName = $memberFrom->getWorldShortName();
			
		$fromUserName = $memberTo->getUser()->getName();				
		$fromWorldName = $memberTo->getWorldShortName();
		
		$creditName = $summ->getCreditName();
		$creditSumm = $summ->getAmount();
		
		$additionalID = $operatiooForLog->getAdditionalID();
		$gateKey = $operatiooForLog->getGateKey();

		//2006-06-06 12:01:01 PVP_TRANSFER Defo[TEL]=>DestinySphere[TEL] GC:200 LID:12133 AID:123421 GK:asdasd	
		$str = "$dateString $operationName $fromUserName[$fromWorldName]=>$toUserName[$toWorldName] $creditName:$creditSumm LID:$listID AID:$additionalID GK:$gateKey";
		
		SU_DebugTools::WriteToLog($str, 'MoneyOperations.log');
	}
}


/*

*/
?>