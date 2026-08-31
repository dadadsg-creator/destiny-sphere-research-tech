<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecStoreCombTemplate
{
	static private $common;
	
	static public function GetFromStoreUserData($id_storeuserdata)
	{
		self::$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				count(id_storeusercombs) AS comb_count
			FROM	
				ds_storeusercombs
			WHERE
				id_storeuserdata = $id_storeuserdata
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
	
		if($res[0][comb_count] != 1)
		{
			echo "ERROR: template can have only one comb\n";
			return;
		}
		
		
		// создаем объект с данными игрока		
		$sql = "
			SELECT
				suid.userinstance_data,
				suc.coordinates_old
			FROM 
				ds_storeuserinstancedata AS suid
				INNER JOIN ds_storeusercombs AS suc ON (suc.id_storeuserdata = suid.id_storeuserdata)
			WHERE
				suid.id_storeuserdata = ".$id_storeuserdata."
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		if(!$res)
		{
			echo "ERROR: user data not found\n";
			return;
		}
		
		$userInstanceData = str_replace("<{0}>", "\0", $res[0]['userinstance_data']);
		
		$userInstance = unserialize($userInstanceData);
		
		//$templateName = '/home/www/htdocs/test/ds/UI_templates/'.$userInstance->getUserName().'_race'.$res[0]['coordinates_old'][0];
		$templateName = $_SERVER['DOCUMENT_ROOT'].'/ds/UI_templates/'.$userInstance->getUserName().'_race'.$res[0]['coordinates_old'][0];
		
		$fp = fopen($templateName, 'w+');
		if(fwrite($fp, $userInstanceData) === false)
		{
			echo "ERROR: unable to write data\n";
		}
		fclose($fp);
	}	
}

?>