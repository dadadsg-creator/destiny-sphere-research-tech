<?php
/**
 * Trace class.
 * 
 * @author Akzhan Abdulin, Anton Fedchin DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

require_once constant('DS_SCRIPT_ROOT').'/classes/Core/LowLevelStaticVariableStorage.class.php';

class Trace	
	extends LowLevelStaticVariableStorage
{	
	function _InitializationNeeded()
	{
		parent::_InitializationNeeded();
		AssertAccumulator::InitProperty('Trace::TraceListeners', array());		
	}
	

	function _GetArgs($args)
	{
		return '';
	}
	
	function _FormatMessage($category, $code, $args)
	{
		return $category. ': '. $code. ' ('. Trace::_GetArgs($args). ')';
	}
	
	function WriteMessage($category, $code, $args = null)
	{
		assert(!empty($category) && is_string($category));
		assert(!empty($code) && is_string($code));
		assert (is_array($args) || is_null($args));
		
		Trace::_InitializationNeeded();
		
		if(!is_array(Trace::GetPropertyValue('Trace::TraceListeners')))
		{
			return;
		}
		
		$message =  Trace::_FormatMessage($category, $code, $args);
		
		foreach(Trace::GetPropertyValue('Trace::TraceListeners') as $index => $traceListener)
		{			
			$traceListener->writeMessage($message);
		}
	}
	
	function AddListener(&$listener)
	{
		
		assert(is_a($listener, 'TraceListener'));				
		Trace::_InitializationNeeded();		
		$list = Trace::GetPropertyValue('Trace::TraceListeners');
		$list[] = &$listener;
		Trace::SetPropertyValue('Trace::TraceListeners', $list);
	}
	
	function RemoveListener(&$listener)
	{
		assert(is_a($listener, 'TraceListener'));		
		Trace::_InitializationNeeded();		
		
		$list = Trace::GetPropertyValue('Trace::TraceListeners');
		foreach ($list as  $index => $testListener)
		{			
			if($testListener === $listener){
				$list = array_splice($list, $index, 1);
				break;
			}
		}
		
		Trace::SetPropertyValue('Trace::TraceListeners', $list);
	}
}

?>