<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * класс перебирает инстансы мира и все. пока сам патчей не содржит
 *
 */
class MP_PatchWorld extends MP_Patch
{
	/**
	 * Масив с днными по миру
	 *
	 * @var array
	 */
	private $worldData;
	
	public function __construct($worldData)
	{
		assert(is_array($worldData) && count($worldData) > 0);		
		$this->worldData = $worldData;
	}
	
	public function patch()
	{
		self::_out("---===START PATCH WORLD ".$this->worldData['worldname']."===---");
		
		//Извлекаем список инстнсов из мира
		$sql = "SELECT * 
				FROM 
					ds_userinstance AS ui
					INNER JOIN ds_user AS u ON (u.id_user = ui.id_user) 		
				WHERE 
					ui.id_world = ".$this->worldData['id_world']."
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
						
			$patchUserInstance = new MP_PatchUserInstance($userInstanceData);
			$patchUserInstance->patch();
		}
	}
	
}




?>