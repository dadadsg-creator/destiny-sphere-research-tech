<?php


/**
 * Менеджер проверки входящих переводов
 *
 */
class MU_InterworldExecTestRecive extends MU_InterworldExecTestBase
{
	public function __construct($worldData)
	{
		parent::__construct($worldData);
	}
	
	public function exec()
	{
		self::Out('++++++++++++++++++++++++ Validate recive ++++++++++++++++++++++++');	
		
		$recivedDataAll = MU_InterworldSearch::GetAllRecivedNotChecked($this->worldData['dbname'], $this->worldData['world_identifier']);

		foreach ($recivedDataAll as $k=>$reciveData)
		{			
			$userData = MU_InterworldSearch::GetUserByID((int)$reciveData['id_user']);
			
			$sendWorldData = MU_InterworldSearch::GetWorldDataBayShortName($reciveData['from_world_identifier']);
			
			$sendData = MU_InterworldSearch::GetSendedTransfersByKey($sendWorldData['dbname'], $reciveData['from_world_identifier'], $reciveData['transfer_key'], (int)$reciveData['gc_amount'], (int)$reciveData['sc_amount'], $this->worldData['world_identifier']);
			
			$id = (int)$reciveData['id_list'];
			
			$transferString = "RECIVE: id_list = ".$id." key=".$reciveData['transfer_key']." dtime=".$reciveData['add_date']."  ".
								$userData['username']." [".$reciveData['from_world_identifier']." => ".
								$this->worldData['world_identifier']."] GC: ".$reciveData['gc_amount'].
								" SC: ".$reciveData['sc_amount'];
						
			if(is_array($sendData))
			{
				self::Out($transferString.'			OK');
				
				MU_InterworldSearch::MarkReciveAsValid($this->worldData['dbname'], $id);
			}else{
				self::Out($transferString.'      <<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!!!!');
				
				$memberFrom = MU_EntityOperationMember::CreateFromUserAnWorldShortName(DE_User::CreateFromID((int)$reciveData['id_user']), $reciveData['from_world_identifier']);								
				
				if($reciveData['gc_amount'] > 0)
				{
					$creditName = 'GC';
					$moneyCount = (int)$reciveData['gc_amount'];
				}else{
					$creditName = 'SC';
					$moneyCount = (int)$reciveData['sc_amount'];
				}
				
				$summ = new MU_EntityMoneySumm($moneyCount, $creditName);				
				
				$memberTo = MU_EntityOperationMember::CreateFromUserAnWorldShortName(DE_User::CreateFromID((int)$reciveData['id_user']), 
																					$this->worldData['world_identifier']);
				if(MU_InterworldExecCreateSend::Exec($memberFrom, $memberTo->getCollectionWorld(), $summ, $reciveData['transfer_key']))
				{											
					$sendedTransfer = MU_InterworldSearch::GetSendedTransfersByKey($memberFrom->getCollectionWorld()->getDBName(),
																					$memberFrom->getCollectionWorld()->getShortName(),
																					$reciveData['transfer_key'],
																					(int)$reciveData['gc_amount'],
																					(int)$reciveData['sc_amount'],
																					$this->worldData['world_identifier']);
					MU_InterworldSearch::MarkSendAsValid($memberFrom->getCollectionWorld()->getDBName(), (int)$sendedTransfer['id_list']);						
					
					// теперь начисляем деньги															
					$systemUser = MU_EntityOperationMember::CreateSystemAccount($this->worldData['world_identifier']);					
					$actionType = DE_MoneyActionType::CreateFromName('RECIVE_INTERWORLD_TRANSFER');	
					
					$operation = new MU_EntityOperation($actionType, $summ, $systemUser, $memberTo);
					$operation->setAdditionalID($reciveData['transfer_key']);					
					$operation->setInterworldTransferData($systemUser->getCollectionWorld(), $memberTo->getCollectionWorld());
					MU_ExecTransfer::Exec($id, $operation);			
					
					MU_InterworldSearch::MarkReciveAsValid($this->worldData['dbname'], $id);	
				}
				
				self::Out(MU_InterworldExecCreateSend::$msg);
			}					
		}
	}
}



?>