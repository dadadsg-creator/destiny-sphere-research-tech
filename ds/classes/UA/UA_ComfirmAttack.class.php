<?php $CVS_Version = '$Revision$ ; $Date$';
// обрабатывает команду отправки армии на захватываемую колонию

class UA_ComfirmAttack extends UA_Action {
	
	private $action;
	
	function UA_ComfirmAttack () {
		UA_Action::UA_Action();
		
		$this->action = new UA_Attack;
	}
	
	function checkLocalParams () {
		if (!$this->action->checkLocalParams()) {
			return false;		
		}
		return true;		
	}
	
	// выполняем действие
	function performAction () {
		$this->action->performAction(true);
		return true;
	}
}

?>