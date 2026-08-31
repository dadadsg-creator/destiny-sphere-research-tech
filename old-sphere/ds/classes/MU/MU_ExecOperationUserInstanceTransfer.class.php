<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:36:00 $';

class MU_ExecOperationUserInstanceTransfer extends MU_ExecOperationUserInstance
{
	public function exec()
	{
		//Снимаем деньги
		MU_SearchUserInstanceOperations::DeltaUserInstanceAccount($this->operation->getFromUserInstance(), 
					-$this->operation->getSumm()->getAmount(), $this->operation->getSumm()->getCreditName());
					
		//Начиcляем деньги
		MU_SearchUserInstanceOperations::DeltaUserInstanceAccount($this->operation->getToUserInstance(), 
					$this->operation->getSumm()->getAmount(), $this->operation->getSumm()->getCreditName());
	}	
}


?>