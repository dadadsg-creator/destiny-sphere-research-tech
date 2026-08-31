<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_ClientInterface
{
	private $common;
	
	private $action;
	
	public function __construct($data)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->_getParams($data);
		
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		
	}
	
	
	private function _getNextLocalizedTextID()
	{
		$sql = "
			SELECT 
				max(id_localizedtext) + 1
			FROM
				ds_localizedtext
			WHERE
				id_localizedtext > 30000
				AND
				id_localizedtext < 40000
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
	}
}

?>