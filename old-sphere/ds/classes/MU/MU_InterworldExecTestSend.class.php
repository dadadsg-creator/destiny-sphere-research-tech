<?php
/**
 * Менеджер проверки иходящих переводов
 *
 */
class MU_InterworldExecTestSend extends MU_InterworldExecTestBase
{
	public function __construct($worldData)
	{
		parent::__construct($worldData);
	}
	
	public function exec()
	{		
		self::Out('++++++++++++++++++++++++ Validate send ++++++++++++++++++++++++');	
		
		$sendDataAll = MU_InterworldSearch::GetAllSendedNotChecked($this->worldData['dbname'], $this->worldData['world_identifier']);
		
		foreach ($sendDataAll as $k=>$sendData)
		{									
			$userData = MU_InterworldSearch::GetUserByID((int)$sendData['id_user']);

			$reciveWorldData = MU_InterworldSearch::GetWorldDataBayShortName($sendData['to_world_idetifier']);
			
			$reciveData = MU_InterworldSearch::GetRecivedTransfersByKey($reciveWorldData['dbname'], $sendData['to_world_idetifier'], 
																		$sendData['transfer_key'], (int)$sendData['gc_amount'], 
																		(int)$sendData['sc_amount'], $this->worldData['world_identifier']);
	
			$id = (int)$sendData['id_list'];
			
			$transferString = "SEND: ($id) (".$sendData['transfer_key'].") ".$sendData['add_date']." ".
								$userData['username']." [".$this->worldData['world_identifier']." => ".
								$sendData['to_world_idetifier']."] GC: ".$sendData['gc_amount']." SC: ".
								$sendData['sc_amount'];
			
			if(is_array($reciveData))
			{
				self::Out($transferString.'			OK');
				MU_InterworldSearch::MarkSendAsValid($this->worldData['dbname'], $id);				
			}else{
								
				self::Out($transferString.'      <<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!!!!');				
				
				$memberTo = MU_EntityOperationMember::CreateFromUserAnWorldShortName(DE_User::CreateFromID((int)$sendData['id_user']), 
																					$sendData['to_world_idetifier']);
				
				if($sendData['gc_amount'] > 0)
				{
					$creditName = 'GC';
					$moneyCount = (int)$sendData['gc_amount'];
				}else{
					$creditName = 'SC';
					$moneyCount = (int)$sendData['sc_amount'];
				}
				
				$summ = new MU_EntityMoneySumm($moneyCount, $creditName);				
				
				$memberFrom = MU_EntityOperationMember::CreateFromUserAnWorldShortName(DE_User::CreateFromID((int)$sendData['id_user']), 
																					$this->worldData['world_identifier']);
				if(MU_InterworldExecCreateRecive::Exec($memberTo, $memberFrom->getCollectionWorld(), $summ, $sendData['transfer_key']))
				{					
					$receivedTransfer = MU_InterworldSearch::GetRecivedTransfersByKey($memberTo->getCollectionWorld()->getDBName(),
																						$memberTo->getCollectionWorld()->getShortName(),
																						$sendData['transfer_key'],
																						(int)$sendData['gc_amount'],
																						(int)$sendData['sc_amount'],
																						$this->worldData['world_identifier']);
					// помечаем ресив как проверенный
					MU_InterworldSearch::MarkReciveAsValid($memberTo->getCollectionWorld()->getDBName(), (int)$receivedTransfer['id_list']);
					
					//////////////////////////////////////////////////////////////
					// теперь снимаем деньги															
					$systemUser = MU_EntityOperationMember::CreateSystemAccount($this->worldData['world_identifier']);					
					$actionType = DE_MoneyActionType::CreateFromName('SEND_INTERWORLD_TRANSFER');	
					
					$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $systemUser);
					$operation->setAdditionalID($sendData['transfer_key']);					
					$operation->setInterworldTransferData($systemUser->getCollectionWorld(), $memberTo->getCollectionWorld());
					MU_ExecTransfer::Exec($id, $operation);		
					/////////////////////////////////////////////////////////////
					
					MU_InterworldSearch::MarkSendAsValid($this->worldData['dbname'], $id); 								
				}
				self::Out(MU_InterworldExecCreateRecive::$msg);
			}		
		}
	}
}


?>