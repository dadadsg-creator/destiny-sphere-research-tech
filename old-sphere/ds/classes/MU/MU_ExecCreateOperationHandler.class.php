<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecCreateOperationHandler
{
	static public function Create(GB_MoneyUserInstanceOperation $operation)
	{
		$actionTypeData = MU_SearchUserInstanceOperations::GetActionTypeDataByName($operation->getActionTypeName());
		assert(is_array($actionTypeData) && key_exists('handler_class_name', $actionTypeData));
		
		$handlerClassName = $actionTypeData['handler_class_name'];
		assert(!empty($handlerClassName));
		
		return new $handlerClassName($operation);
	}
}


?>