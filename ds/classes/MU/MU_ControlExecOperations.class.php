<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ControlExecOperations
{
	static public function ExecOperation(MU_EntityOperation $operation, $WI = '')
	{				
		$operation->validate();
		
		MU_ExecLockOperation::Lock($operation);
		
		$handlerName = $operation->getActionType()->getHandlerName();
		$operationHandler = new $handlerName($operation);
		assert($operationHandler instanceof MU_ExecOperation);
		
		$operationHandler->exec($WI);
		
		//MU_ExecLogOperation::Log($operation);
	}
}

?>