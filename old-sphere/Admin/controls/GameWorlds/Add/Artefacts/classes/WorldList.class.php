<?php

class WorldList
{
	private $worlds;	
	
	private $selected;
	
	public function __construct(array $worldData)
	{
		foreach ($worldData as $k => $w)
		{
			assert(key_exists('id_world', $w));
			assert(key_exists('worldname', $w));
			assert(key_exists('dbname', $w));
			foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
			{
				if($db['server'] == $w['dbname'])
				{
					$this->worlds[$dbId."_".$w['id_world']] = $w['worldname'];
					break;
				}
			}
		}
	}
	
	public function setCurrent($cur)
	{
		assert(strstr($cur, '_') !== false);
		$this->selected = $cur;
	}
	
	public function getCurrent()
	{
		if(key_exists($this->selected, $this->worlds))
		{
			return $this->worlds[$this->selected];
		}
		
		return '';
	}
	
	public function getWorldList()
	{
		assert(is_array($this->worlds));
		return $this->worlds;
	}
	
	public function getInHTML($name = 'world')
	{
		if(empty($name))
		{
			$name = 'world';
		}
			
		$str = "<select name='$name'>\n";
		foreach ($this->worlds as $id => $name)
		{
			if($id == $this->selected)
			{
				$s = 'selected="selected"';
			} else {
				$s = '';
			}
			$str .= "\t<option value='$id' $s>$name\n";
		}
		$str .= "</select>\n";
		
		return $str;
	}
}

?>