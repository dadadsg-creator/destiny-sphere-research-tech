<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_TypeFactory
{	

	static public $flyWeightStorage = array();
	
	static public function CreateBase($type, $value)
	{		
		assert(!empty($type));
		self::_statistic($type);
				
		return new $type ($value, 'BASE');	
	}
	
	static public function CreateMaster($type, $value)
	{
		assert(!empty($type));
		self::_statistic($type);
		
		return new $type ($value, 'MASTER');	
	}
	
	static public function CreateConstant($type, $value)
	{
		assert(!empty($type));		
						
		if(!self::_haveInStorage($type, $value))
		{
			self::_statistic($type);
			self::$flyWeightStorage[$type][(string)$value] = new $type ($value, 'CONSTANT');						
		}else{
			
		}		
		
		return self::$flyWeightStorage[$type][(string)$value];		
	}			
	
	static private function _haveInStorage($type, $value)
	{		
		assert(!empty($type));
		
		if(!key_exists($type, self::$flyWeightStorage))
		{
			return false;
		}
		
		if(!key_exists((string)$value, self::$flyWeightStorage[$type]))
		{
			return false;
		}
		
		if(is_object(self::$flyWeightStorage[$type][(string)$value]))
		{
			return true;	
		}else{
			return false;
		}
		
	}
	
	static private function _statistic($type)
	{
		assert(!empty($type));		
	}
	
	static public function TestFabricCreation()
	{		
	}
}
?>