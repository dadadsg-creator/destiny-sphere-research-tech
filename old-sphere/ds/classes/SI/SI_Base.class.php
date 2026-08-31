<?php $CVS_Version = '$Revision$ ; $Date$';
class SI_Base
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static protected $common;
	
	/**
	 * Словарик соотвествиястарых и новых ID
	 *
	 * @var unknown_type
	 */
	static public $idDictionary = array
	(
		'ID_Cell' => array(),
		'ID_UserInstance' => array(),
		'ID_Comb' => array(),
		'ID_AttachedBuilding' => array(),
		'ID_World' => array()
	);
	
	static public $logRecordId;
	
	static public function SetCommon(DS_Common $common)
	{
		self::$common = $common;
	}
	
	static public function GetCommon()
	{
		return self::$common;
	}
	
	static public function GetNextValFromSeq($seqName)
	{
		assert(!empty($seqName));
		
		$sql = "select nextval('".$seqName."') as id";
		$res = self::$common->getResultArray("", $sql, "", "", "");	
		assert(empty(self::$common->errormsg) && count($res) == 1);							
		$id = (int)$res[0]['id'];
		assert($id > 0);		
		
		return $id;
	}		
	
	static protected function _out($str, $notForConsole = false)
	{
		$sapi = php_sapi_name();
		if( $sapi != "apache" && $notForConsole)
		{
			return;
		}
		
		echo $str."\n";
		flush();
	}
}



?>