<?php $CVS_Version = '$Revision$ ; $Date$';

class MC_CoompilerBase extends MC_Base
{
	static protected function CreateSingl($className)
	{
		if(!key_exists($className, self::$compilerInstances))
		{
			self::$compilerInstances[$className] = new $className;
		}
		
		return self::$compilerInstances[$className];
	}
	
	public function compileMessages()
	{
		ds_error('This method must be implemented');
	}
}

?>