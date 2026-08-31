<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:36:00 $';

class MU_ExecOperationUserInstanceReturnReserv extends MU_ExecOperationUserInstance
{
	public function Exec()
	{
		//Снимаем деньги
		MU_SearchUserInstanceOperations::DeltaUserInstanceAccount($this->operation->getFromUserInstance(), 
					$this->operation->getSumm()->getAmount(), $this->operation->getSumm()->getCreditName());
					
		//Начиcляем деньги в резерв
		MU_SearchUserInstanceOperations::DeleteReservData($this->operation->getFromUserInstance(), 
			$this->operation->getResrvReasonName(), $this->operation->getAdditionalID());
	}	
}


?>