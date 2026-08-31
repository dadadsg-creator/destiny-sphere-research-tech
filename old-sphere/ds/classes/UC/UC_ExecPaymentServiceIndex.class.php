<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/12/18 10:58:46 $';

/*
class UC_ExecPaymentServiceIndex
{
	public function Exec($data, $services)
	{
		assert(is_array($data));
		assert(is_array($services));
		
		$_SESSION['dbId'] = 0;
		
		$common = DS_Common::CreateForCurentDB(0);

		//Список платных сервисов
		$services = array(
			'SECTION_RESOURCE' => array(
							'PS_BUY_RESOURCE' => 'UC_ExecByResources',
							),
			'SECTION_PREMIUM' => array(
							'PS_BUY_PREMIUM' => 'UC_ExecByPremium',
							),
			'SECTION_ACTION_WITH_ACCOUNT' => array(
							'PS_CHANGE_COMB_NAME' => 'UC_ExecChangeCombName',
							'PS_RESERVE_COMB' => 'UC_ExecReserveComb',
							'PS_GET_LOG' => 'UC_ExecLoginAndMoneyActionsLog',
							'PS_DELETE_INSTANCE' => 'UC_ExecDeleteInstance',
							'PS_DELYVERY_INSTANCE' => 'UC_ExecDeliveryInstance',
							'PS_MERGE_ACCOUNTS' => 'UC_ExecMergeAccount',
							),	
			'SECTION_WORLD_ABILITY'	=> array(
							'PS_WORLD_ABILITY' => 'UC_ExecWorldAbility',
							'PS_MYLTY_WORLD_ACCESS' => 'UC_ExecMultyWorldAccess',
							),				
			'SECTION_TRANSFER'	=> array(
							'PS_CHANGE_COORDS' => 'UC_ExecChangeCoords',
							'PS_CHANGE_COORDS_MELS' => 'UC_ExecChangeCoords',
							'PS_EXCHANGE_COORDS' => 'UC_ExecExchangeCoords',
							'PS_EXCHANGE_COORDS_MELS' => 'UC_ExecExchangeCoords',
							),	
			'SECTION_BLACK_HOLE' => array(
							'PS_TRANSFER_COMB_TO_BLACK_HOLE' => 'UC_ExecTransferCombToBlackHole',
							'PS_RESTORE_COMB_FROM_BLACK_HOLE' => 'UC_ExecRestoreCombFromBlackHole',
							),		
			'SECTION_UNIQUE_BUILDING' => array(
							'PS_GRANT_BUILDING' => 'UC_ExecGrantBuilding',
							'PS_DELETE_UNIQUE_BUILDING' => 'UC_ExecDeleteUniqueBuilding'
							),		
			'SECTION_ANALIZATOR' => array(
							'PS_BUY_ANALIZATOR' => 'UC_ExecByAnalizator',
							),
			'SECTION_TURNIR' => array(
							'PS_BUY_TURNIR' => 'UC_ExecByEntryTurnir',
							),
			'SECTION_BONUS' => array(
							'PS_BY_BONUS_MILITARY' => 'UC_ExecByBonus',							
							'PS_BY_BONUS_RESOURCE' => 'UC_ExecByBonus',
							),
			'SECTION_NUBS' => array (
							'PS_BUY_TRAINING_COMPLETION' => 'UC_ExecByTraining'							
							),
		);
		
		foreach ($services as $group => $secviceArr)
		{
			foreach ($secviceArr as $secvice => $handler)
			{
				if ($secvice == 'PS_BUY_PREMIUM') {
					$secviceCheck = 'PS_BUY_PREMIUM_90';
				} elseif ($secvice == 'PS_BUY_RESOURCE'){
					$secviceCheck = 'PS_BUY_RESOURCE_FOOD';
				} elseif ($secvice == 'PS_BY_BONUS_MILITARY' || $secvice == 'PS_BY_BONUS_RESOURCE') {
					$secviceCheck = 'PS_BY_BONUS';
				}else {
					$secviceCheck = $secvice;
				}
				//Смотрим включенна ли услуга	
				if (UC_PaymentServiceBase::IsServiceActive($secviceCheck) == 't'){
					$activeservices[$group][] = $secvice;
				}
			}
		}	
		
		$data['PAYMENT_SERVICES'] = $activeservices;
		$data['MODE'] = 'LIST_PAYMENT_SERVICES';
		
		UC_OutUserCabinet::Out('PaymantServices', $data);
	}
}
*/
?>