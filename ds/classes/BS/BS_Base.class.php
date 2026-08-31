<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс предок всех классов в сиситеме
 */
class BS_Base
{
	public function __construct()
	{

		//GP_SystemStatistic::DeltaParam('USERACTION', 'NEW_FORMAT_OBJECTS_CREATED', 1);
			
		//GP_SystemStatistic::DeltaParam('USERACTION', 'COUNT_OBJECT_'.get_class($this), 1);		
			
		/*if(GP_SystemStatistic::GetParam('USERACTION', 'MAX_MEMORY_USAGE') < memory_get_usage())
		{
			GP_SystemStatistic::SetParam('USERACTION', 'MAX_MEMORY_USAGE', number_format(memory_get_usage()));
		}*/
		
		
	}
		
	protected function _exitInvariant($mode = 'public')
	{
		return true;
		
		switch ($mode)
		{
			case 'public':
				return !constant("DEBUG_USE_INVARIANT_IN_PUBLIC");
			case 'construct':
				return !constant("DEBUG_USE_INVARIANT_IN_CONSTRUCT");
			default:
				ds_error('Unknown invariant mode = '.$mode);
		}		
	}
	
	public function __destruct()
	{
		/*if(GP_SystemStatistic::GetParam('USERACTION', 'MAX_MEMORY_USAGE') < memory_get_usage())
		{
			GP_SystemStatistic::SetParam('USERACTION', 'MAX_MEMORY_USAGE', number_format(memory_get_usage()));
		}*/
		
		//$msg = "DESTRUCT ".$this." '".get_class($this)."'";
		//DebugTools::LogAll($msg, 'ObjectsCreation.log', 1); 
	}	
}
?>