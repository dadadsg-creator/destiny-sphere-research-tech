<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_PatchWorld extends GP_Patch
{
	private $worldData;
	
	public function __construct($worldData)
	{
		assert(is_array($worldData) && count($worldData) > 0);		
		$this->worldData = $worldData;
	}
	
	public function patch()
	{
		self::_out("---===START PATCH WORLD ".$this->worldData['worldname']."===---");
		
		$sql = "SELECT * 
				FROM 
					ds_userinstance AS ui
					INNER JOIN ds_user AS u ON (u.id_user = ui.id_user) 		
				WHERE ui.id_world = ".$this->worldData['id_world']."
				ORDER BY u.username";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );		
		assert(empty(self::$common->errormsg));
		
		foreach($rs as $k=>$userInstanceData)
		{				
			//В БД есть системный инстант с нудевым ID. Его оходим.
			$userInstanceID = (int)$userInstanceData['id_userinstance'];
			if($userInstanceData['id_userinstance'] == 0)
			{
				continue;
			}
						
			$patchUserInstance = new GP_PatchUserInstance($userInstanceData);
			$patchUserInstance->patch();
		}
	}
	
}




?>