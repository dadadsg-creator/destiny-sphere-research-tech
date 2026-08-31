<?php

/** Есть еще копия этого класса в PageCache.class.php Сделано для оптимизации 
колличества обращений к сетевой файловой системе. */

class Cache 
{
	var $cacheStorePath = '';
	
	var $objectType = '';
	
	var $timeDecriment = 1000000;
	
	function Cache ($objectType, $cacheStorePath)
	{
		assert(!empty($objectType));
		$this->objectType = $objectType;
		
		assert(!empty($cacheStorePath));
		$this->cacheStorePath = $cacheStorePath;
	}
	
	function set($mark, $lifeTime, $data)
	{
		assert(!empty($lifeTime));
		assert(!empty($mark));
		
		if(file_exists($this->_formFileName($mark)))
		{
			unlink($this->_formFileName($mark));
		}
		
		if (!$handle = fopen($this->_formFileName($mark), 'a')) 
		{
        	 trigger_error("Cannot create file for cache store", E_USER_ERROR);
    	}
    	
    	if (fwrite($handle, $data) === FALSE) 
    	{
    		trigger_error("Cannot store data in file for cache store", E_USER_ERROR);        	
    	}
        fclose($handle);
            
    	$setTime = time() + $lifeTime - $this->timeDecriment;
    	touch($this->_formFileName($mark), $setTime);    	
	}
	
	function get($mark){
		
		if(!file_exists($this->_formFileName($mark)))
		{
			return '';
		}
		
		if(filemtime($this->_formFileName($mark)) < (time() - $this->timeDecriment))
		{
			unlink($this->_formFileName($mark));
			return '';
		}else{
			return implode("\n", file($this->_formFileName($mark)));
		}
	}
	
	function reset()
	{
	}
	
	function _formFileName($mark)
	{
		return 	$this->cacheStorePath.'/'.$this->objectType.'_'.$mark;	
	}
}


?>