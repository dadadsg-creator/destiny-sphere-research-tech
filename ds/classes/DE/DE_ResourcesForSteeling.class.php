<?

class DE_ResourcesForSteeling
{
	private $resourceForSteel;
	private $resourceForSafe;
	
	public function setResourceForSteel($array)
	{
		assert(is_array($array));
		$this->resourceForSteel = $array;
	}
	
	public function setResourceForSafe($array)
	{
		assert(is_array($array));
		$this->resourceForSafe = $array;
	}

	public function getResourceForSteel()
	{
		return $this->resourceForSteel;
	}
	
	
	public function getResourceForSafe()
	{
		return $this->resourceForSafe;
	}
	
	
	
}


?>