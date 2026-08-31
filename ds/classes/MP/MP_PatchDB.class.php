<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Скрипт который перебирает для патчинг все миры в БД 
 *
 */

class MP_PatchDB extends MP_Patch 
{	
	
	static public $patchesDB = array
	(		
		//'MP_DBEventsWithEmptyArmys',//Убиваем ивенты с пустыми армиями
		'MP_DBEventsWithDeletedUsers',//Чистим военные ивнты связаные с удаленными пользхователями
		'MP_DBBadBuildingEvents',//ЗДание числиться в работе, активируеться строить сяи т.п. а иента к ней нет
		'MP_DBScienceResearchWithoutEvent',//НАуки пользователя которые числяться в изученеи а ивента нет
		'MP_DBUnitProductionWithoutEvent', //Есть запись в табличке ds_unitinproduction а ивента нет уже 
		'MP_DBNotEndedBattlesWithoutEvent', //Бои у которых нет даты окончани яно и нет ивента
		'MP_DBAllianceMembers', // Поле с кол-вом участников в альянсе не совпадает с реальным кол-вом, может не быть лидера
		'MP_DBInActionArmysWithoutEvents', // При удалении мертвяков армии других игроков продолжают числится в полете на их соты но уже без евента
	);
	
	public function __construct(DS_Common $common)
	{			
		self::$common = $common;
		self::$common->executeQuery("set STATEMENT_TIMEOUT to 120000");
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

		foreach(self::$patchesDB as $k=>$patchName)
		{
			self::_out("Start patch ".$patchName);
			$patch = new $patchName;
			$patch->patch();
		}	
		
		$sql = "SELECT * 
				FROM ds_world";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
			
		foreach($rs as $k=>$worldData)
		{					
			$worldPatch = new MP_PatchWorld($worldData);
			$worldPatch->patch();
		}
	}
				
}


?>
