<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_PatchDB extends GP_Patch 
{	
	
	public function __construct(DS_Common $common)
	{			
		self::$common = $common;
	}
	
	public function patch()
	{
		self::_out("PATCHINFO");
		
		if(self::$onlyValidate)
		{
			self::_out("Mode: VALIDATE ERRORS");
		}else{
			self::_out("Mode: CORRECT ERRORS");
		}
		
		self::_out("\n");
		
		$this->getPatchesList();
		
		self::_out("\n");
		
		self::_out("---===START PATCH DB===---");
		
		$sql = "SELECT * 
				FROM ds_world";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
			
		foreach($rs as $k=>$worldData)
		{					
			$worldPatch = new GP_PatchWorld($worldData);
			$worldPatch->patch();
		}
	}
				
}


?>
