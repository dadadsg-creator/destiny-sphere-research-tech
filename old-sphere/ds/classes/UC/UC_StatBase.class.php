<?php $CVS_Version = '$Revision$ ; $Date$';
class UC_StatBase
{
	protected $common;
	
	protected $data;
	
	protected $dbId;
		
	function __construct($data)
	{
		assert(key_exists('dbid', $data));
		$this->dbId = (int) $data['dbid'];
		assert($this->dbId >= 0);
		
		assert(key_exists('log_action_table', $data));
		assert(key_exists('id_list', $data));
		
		$this->data = $data;
		
		$this->common = DS_Common::CreateForCurentDB($this->dbId);
		
		$this->_getAdditionalData();	
	}
	
	private function _getAdditionalData()
	{
		$sql = "
			SELECT
				*
			FROM
				".$this->data['log_action_table']."
			WHERE
				id_list = ".$this->data['id_list']."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		assert(count($res) == 1);
		
		foreach($res[0] as $key => $val)
		{
			$this->data[$key] = $val;
		}			
	}
	
	
	protected function _getWorldName($worldIdentifier)
	{
		$sql = "
			SELECT
				worldname
			FROM
				ds_worldcollection
			WHERE
				world_identifier = '$worldIdentifier'
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		assert(count($res) == 1);
		
		return $res[0]['worldname'];
	}
	
	protected function _getText($text)
	{
		return UC_LocalizationStat::Get($text);
	}
}
?>