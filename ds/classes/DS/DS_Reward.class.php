<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Статический класс вознаграждения игрока
 * 
 */
class DS_Reward extends DS_Base
{
	/**
	*  	
	* @desc Получаем трофеи игрока
	* 
	*/
	static function GetByUserInstanceId($UIID) 
	{
		assert(is_int($UIID) ); // && $UIID > 0
		
		$common = self::getCommon();

		// Получаем данные
		$sql = "SELECT 
					*
				FROM
					ds_reward
				WHERE
					id_userinstance = ".$UIID;
		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($common->errormsg));
				
		if (count($res) == 0) {
			return array(
					'KADERIUM' => 0,
					'NANO_CRYSTAL' => 0,
					'STASIS_ENERGY' => 0,
					'FOOD' => 0,
					'PRODIUM' => 0
					);	
		}
		
		assert(count($res) == 1);
		
		$res[0]['KADERIUM'] = $res[0]['kaderium'];
		$res[0]['NANO_CRYSTAL'] = $res[0]['nano_crystal'];
		$res[0]['STASIS_ENERGY'] = $res[0]['stasis_energy'];
		$res[0]['FOOD'] = $res[0]['food'];
		$res[0]['PRODIUM'] = $res[0]['prodium'];
		
		
		return $res[0];

	}//END OF FUNCTION
	
	/**
	*  	
	* @desc Втавляет новые значения построенного и потерянного продовес
	* 
	*/
	static function UpdateReward(DE_Reward $reward) 
	{
		$common = self::getCommon();

		// Смотрим, есть ли записи
		$sql = "SELECT 
					*
				FROM
					ds_reward
				WHERE
					id_userinstance = ".$reward->getID();
		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($common->errormsg));

		// Если нет
		if(count($res) > 0)
		{
			// Обновляем данные в хистори
			$sql = "UPDATE  
						ds_reward
					SET 
						kaderium = '".$reward->getReward('KADERIUM')."',
						nano_crystal = '".$reward->getReward('NANO_CRYSTAL')."',
						stasis_energy = '".$reward->getReward('STASIS_ENERGY')."',
						food = '".$reward->getReward('FOOD')."',
						prodium = '".$reward->getReward('PRODIUM')."'
					WHERE 
						id_userinstance = ".$reward->getID();
		} else {
			// Вставляем данные в таблицу
			$sql = "INSERT INTO  
						ds_reward
					(id_userinstance, kaderium, nano_crystal, stasis_energy, food, prodium)
					VALUES
					(".$reward->getID().", '".$reward->getReward('KADERIUM')."', '".$reward->getReward('NANO_CRYSTAL')."', '".$reward->getReward('STASIS_ENERGY')."', '".$reward->getReward('FOOD')."', '".$reward->getReward('PRODIUM')."')";
				
		}
		$common->executeQuery( $sql );
		assert(empty($common->errormsg));
		
		return true;
	}//END OF FUNCTION
	
	/**
	*  	
	* @desc Вставка записи о взятии вознаграждения
	* 
	*/
	/*static function InsertRewardHytory($UIID, $k, $nk, $se, $f, $p) 
	{
		$common = self::getCommon();


		// Вставляем данные в таблицу
		$sql = "INSERT INTO  
						ds_reward_history
				(id_userinstance, \"KADERIUM\", \"NANO_CRYSTAL\", \"STASIS_ENERGY\", \"FOOD\", \"PRODIUM\")
				VALUES
				($UIID, '$k', '$nk', '$se', '$f', '$p')";
				
	
		return true;
	}//END OF FUNCTION*/
}

?>