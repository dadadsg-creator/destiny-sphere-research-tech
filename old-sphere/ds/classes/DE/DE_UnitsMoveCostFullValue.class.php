<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_UnitsMoveCostFullValue extends BS_Base
{	
	private $pay = array();
	
	private $return = array();
	
	public function __construct()
	{
		parent::__construct();
		
		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k=>$resourceName)
		{
			$this->pay[$resourceName] = 0;
			$this->return[$resourceName] = 0;
		}
	}
	
	public function setPay($resourceName, $value)
	{
		assert(key_exists($resourceName, $this->pay));
		assert($value>=0);
		
		$this->pay[$resourceName] = $value;
	}
	
	public function setReturn($resourceName, $value)
	{
		assert(key_exists($resourceName, $this->return));
		assert($value>=0);
		
		$this->return[$resourceName] = $value;
	}
	
	public function getPay($resourceName)
	{
		assert(key_exists($resourceName, $this->pay));
		
		return $this->pay[$resourceName];
	}
	
	public function getReturn($resourceName)
	{
		assert(key_exists($resourceName, $this->return));
		
		return $this->return[$resourceName];
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		assert(is_array($this->pay) && count($this->pay) == count(GB_ResourcesList::$unitsLiveUpkeepResources));
		assert(is_array($this->return) && count($this->return) == count(GB_ResourcesList::$unitsLiveUpkeepResources));
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}	
	
	public function getShortPayList()
	{
		return $this->_formResourceList($this->pay);
	}
	
	public function getShortReturnList()
	{
		return $this->_formResourceList($this->return);
	}
	
	public function getShortPayNotLocalizedList()
	{
		return $this->_formResourceList($this->pay, false);
	}
	
	private function _formResourceList($resources, $localized = true)	
	{
		assert(is_array($resources));
		assert(is_bool($localized));
		
		$res = array();
		
		foreach($resources as $resourceName => $value)
		{
			if($value == 0)
			{
				continue;
			}
			
			$resource = DE_Resource::CreateFromName($resourceName);
			
			if($localized)
			{
				$res[] = $resource->getLocolizedShortName().": ".number_format(ceil($value), 0, ".", " "); 	
			}else{
				$res[] = $resource->getShortName().":".number_format(ceil($value), 0, ".", " "); 
			}
			
		}	
		
		if($localized)
		{
			if(count($res) == 0)
			{
				return '-';
			}
			
			return implode(" ", $res);	
		}else{
			return implode(";", $res);
		}
		
	}
}

?>