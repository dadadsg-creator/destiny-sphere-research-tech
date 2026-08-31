<?php

class ArtFilter
{
	public $filters;
	
	private $currentFilter;
	
	public function __construct()
	{
		$this->filters = array('scope' => array(), 'bonus' => array(), 'size' => array());
		
		$this->currentFilter = array('currentScope' => 0, 'currentBonus' => 0, 'currentSize' => 0);
	}
	
	public function __set($filterName, $data)
	{
		if(strstr($filterName, 'current') === false)
		{
			$this->_setFilterData($filterName, $data);
		} else {
			$this->_setCurrentFilter($filterName, $data);
		}
	}
	
	private function _setFilterData($filterName, array $data)
	{
		assert(is_array($this->filters));
		assert(key_exists($filterName, $this->filters));
		foreach ($data as $k => $v)
		{
			assert(key_exists('id', $v));
			assert(key_exists('name', $v));
		}
		$this->filters[$filterName] = $data;
	}
	
	private function _setCurrentFilter($filterName, $value)
	{
		assert(is_array($this->currentFilter));
		assert(key_exists($filterName, $this->currentFilter));
		assert(is_int($value) && $value >= 0);
		$this->currentFilter[$filterName] = $value;
	}
	
	public function __get($filterName)
	{
		if(strstr($filterName, 'HTML') !== false)
		{
			return $this->_getFilterDataHTML($filterName);
		} elseif(strstr($filterName, 'current') !== false) {
			return $this->_getCurrentFilter($filterName);
		} else {
			return $this->_getFilterData($filterName);
		}
	}
	
	private function _getFilterDataHTML($filterName)
	{
		$filterName = substr($filterName, 0, strpos($filterName, 'HTML'));
		$filters = $this->_getFilterData($filterName);
		$str = "<select name='$filterName'>\n";
		$str .= "\t<option value='0'>ALL\n";
		foreach ($filters as $k => $f)
		{
			if($f['id'] == $this->_getCurrentFilter('current'.ucfirst($filterName)))
			{
				$s = ' selected="selected"';
			} else {
				$s = '';
			}
			$str .= "\t<option value='".$f['id']."'$s>".$f['name']."\n";
		}
		$str .= "</select>";
		
		return $str;
	}
	
	private function _getFilterData($filterName)
	{
		assert(is_array($this->filters));
		assert(key_exists($filterName, $this->filters));
		
		return $this->filters[$filterName];
	}
	
	private function _getCurrentFilter($filterName)
	{
		assert(is_array($this->currentFilter));
		assert(key_exists($filterName, $this->currentFilter));
		
		return $this->currentFilter[$filterName];
	}
}

?>