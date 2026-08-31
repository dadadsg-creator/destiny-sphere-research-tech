<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ControlUserInstanceOperations
{
	static public function ExecOperation(GB_MoneyUserInstanceOperation $operation)
	{				
		
		$operation->validate();
		
		MU_ExecLockUserInstanceAccount::Lock($operation);
		
		$operationHandler = MU_ExecCreateOperationHandler::Create($operation);
		assert($operationHandler instanceof MU_ExecOperationUserInstance);
		
		$operationHandler->exec();
		
		MU_ExecLogUserInstanceOperation::Log($operation);
	}
}

?>