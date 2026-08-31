<?php

class TMP_GetCombsWithSameName
{
	static private $mainBase = 'priemka';
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static public function Exec()
	{
		self::$common = DS_Common::CreateForCurentDB(0);
	}
	
	static private function _getUsersWithMoreThanOneWorld()
	{
		self::$common->setDbId(0);
		
		$sql = "
			SELECT
				id_user,
				count(*)
			FROM
				ds_userinstancecollection
			GROUP BY id_user
			HAVING count(*) = 2
			";
	}
	
	static private function _getMainCombNamesList()
	{
		self::$common->setDbId(self::$common->getDBIDByName(self::$mainBase));
		
		$sql = "
			SELECT
				combname,
				id_comb
			FROM
				ds_combname
			";
				
	}
}

?>