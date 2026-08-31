<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_WorldPatch extends GP_Patch
{
	private $common;
	
	private $worldData;
	
	public function __construct(DS_Common $common, $worldData)
	{
		assert(is_array($worldData) && count($worldData) > 0);
		$this->common = $common;
		
		$this->worldData = $worldData;
	}
	
	public function patch()
	{
		self::_out("---===START PATCH WORLD ".$this->worldData['worldname']."===---");
		
		$sql = "SELECT * 
				FROM 
					ds_userinstance			
				WHERE id_world = ".$this->worldData['id_world'];
		$rs =  $this->common->getResultArray ( "", $sql, "", "", "NO_CACHE" );		
		assert(empty($this->common->errormsg));
		
		foreach($rs as $k=>$userInstanceData)
		{				
			//В БД есть системный инстант с нудевым ID. Его оходим.
			$userInstanceID = (int)$userInstanceData['id_userinstance'];
			if($userInstanceData['id_userinstance'] == 0)
			{
				continue;
			}
			
			$userInstance = new DE_UserInstance($userInstanceID, $userInstanceData);
			$patchUserInstance = new GP_PatchUserInstance($userInstance);
			$patchUserInstance->patch();
		}
	}
	
}




?>