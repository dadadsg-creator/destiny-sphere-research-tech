<?php

class ArtList
{
	private $artList = array();
	
	public function setArtList(array $artList)
	{
		foreach ($artList as $k => $artData)
		{
			assert(key_exists('id_artefact', $artData));
			assert(key_exists('artefact_name', $artData));
			if(!key_exists('count', $artData))
			{
				$artList[$k]['count'] = 0;
			}
		}
		
		$this->artList = $artList;
	}
	
	public function getFullArtList()
	{
		return $this->artList;
	}
	
	public function getArtListForAdd()
	{
		$res = array();
		
		foreach ($this->artList as $k => $v)
		{
			assert(key_exists('id_artefact', $v));
			assert(key_exists('artefact_name', $v));
			assert(key_exists('count', $v));
			if($v['count'] > 0)
			{
				$res[] = $v;
			}
		}
		
		return $res;
	}
}

?>