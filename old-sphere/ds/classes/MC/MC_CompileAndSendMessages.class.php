<?php $CVS_Version = '$Revision$ ; $Date$';

class MC_CompileAndSendMessages extends MC_Base
{
	static public function Exec(GB_Date $sendDate)
	{
		foreach(self::$compilerInstances as $className => $instance)
		{
			$instance->compile($sendDate);
		}
	}
}


?>