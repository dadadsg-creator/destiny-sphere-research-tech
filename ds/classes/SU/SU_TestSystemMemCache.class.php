<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemMemCache extends SU_TestSystemBase
{
	public function exec()
	{
		$string = "Start test of Mem Cache";
		$this->_outString($string, 8);
		
		$key = time();
		$var = 'Test System Function!';
		
		$host = constant('MEM_CACHE_HOST');	
		
		if (empty($host))
		{
			$result = "ERROR: Can't finde MemCache host!!!!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('MC', "Can't finde MemCache host");
			$this->_outString($result, 8);
			return;
		}
		
		$memcache = new Memcache();
		
		if (!$memcache->connect(MEM_CACHE_HOST, 11211))
		{
			$result = "ERROR: Can't connect to MamCache!!!!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('MC', "Can't connect to MemeCashe");
			$this->_outString($result, 8);
			return;
		}
		
		if (!$memcache->set($key, $var, false, 5))
		{
			$result = "ERROR: Can't set MamCache var!!!!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('MC', "Can't set MemCache var");
			$this->_outString($result, 8);
			return;
		}

		if (!$newVar = $memcache->get($key))
		{
			$result = "ERROR: Can't get MamCache var!!!!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('MC', "Can't get MemCache var");
			$this->_outString($result, 8);
			return;
		}

		if ($var != $newVar) 
		{
			$result = "ERROR: MamCache not work!!!!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('MC', "MemCache not work");
			$this->_outString($result, 8);
			return;
		}
		
		$string = "OK!";
		$this->_outString($string, 8);
	}
}

?>