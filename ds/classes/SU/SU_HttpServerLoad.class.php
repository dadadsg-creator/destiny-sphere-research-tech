<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_HttpServerLoad
{
	static public function Get($index = null)
	{
		$str = exec('uptime');				
		$str = str_replace(",", "", $str);
		$arrayTmp = explode(' ', $str);

		$out = array($arrayTmp[count($arrayTmp) - 3], $arrayTmp[count($arrayTmp) - 2], $arrayTmp[count($arrayTmp) - 1]);		
		
		if(is_null($index))
		{
			return $out;	
		}else{
			assert(key_exists($index, $out));			
			return $out[$index];			
		}		
	}
}


?>