<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 *  оманда открыти€ меню логов разведки
 * 
 * <getSpyMeny/>
 *
 */
class UA_GoToSpyMenu extends UA_Action 
{
	function __construct() 
	{
		UA_Action::UA_Action();
	}	
	
	function checkLocalParams() 
	{
		return true;
	}
	
	function checkAction() 
	{
		
		return true;
	}
	
	function performAction() 
	{
		/*$navigate = new UA_Navigate();
		
		$navigate->cell = 1;
		$this->destination = "CENTRAL";
		//$navigate::UA_Action ();
		$navigate->checkLocalParams();
		$navigate->checkAction();
		$navigate->performAction();*/
		

		
		
		$menu = new PN_SpyLogs();
		//$menu = new PN_SpyAction();

		$menu->setBID(33);
		$menu->colony = array();
		$menu->spyActionTypes = array();
		//$menu->setTAB('PN_SpyAction');
		$menu->setTAB('PN_SpyLogs');

		assert($menu->getPanel(true));
		
		return true;
	}

}

?>