<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Статический класс-библиотека запросов связанная с премиумом.
 */

class DS_PremiumAnalizer extends DS_Base
{
	/**
	 * Изменяем врямя окончания премиума
	 *
	 * @param integer $uid
	 * @param integer $daysDelta
	 */
	static public function changePremiumEndDate($uid, $daysDelta)
	{		
		settype ($uid, 'integer');
		assert ($uid > 0);
		settype ($daysDelta, 'integer');
		assert ($daysDelta != 0);
		
		$common = self::getCommon();
		
		$sql = "
			UPDATE 
		    	ds_analizer_premium 
		    SET 		    			
		    	end_date = end_date + '".$daysDelta." days'::interval 
		    WHERE 
		    	id_user = ".$uid;
		
  		$res = $common->executeQuery ($sql);				
		if(!empty($common->errormsg))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Устанавливаем статус игрока в профи
	 *
	 * @param integer $uid
	 */
	static public function changeUserStatusToProfi($uid)
	{
		settype ($uid, 'integer');
		assert ($uid > 0);
		
		$common = self::getCommon();
		
		$sql = "
			UPDATE 
		    	ds_analizer_premium 
		    SET 		    			
		    	active = true 
		    WHERE 
		    	id_user = ".$uid;
		
  		$res = $common->executeQuery ($sql);				
		if(!empty($common->errormsg))
		{
			return false;
		}
			
		return true;
	}
	
	/**
	 * Проверить профи или нет игрок
	 *
	 * @param integer $uid
	 * @return bool
	 */
	static public function checkIfUserProfi($uid)
	{
		settype ($uid, 'integer');
		assert ($uid > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				count(*)
			FROM
			    ds_analizer_premium upd
			WHERE
			    upd.active = TRUE
			    AND upd.end_date > now()
			    AND upd.id_user = ".$uid;
		
  		$res = $common->getResultArray ( "", $sql, "", "", "" );		
		assert(empty($common->errormsg));		

		if($res[0]['count'] == 0)
		{
			return false;
		}
		
		return true;
	}
	
	static public function checkIfUserHavePremiumRegistration($uid)
	{
		settype ($uid, 'integer');
		assert ($uid > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_analizer_premium AS upd
			WHERE
			    upd.id_user = ".$uid;
		
  		$res = $common->getResultArray ( "", $sql, "", "", "" );		
		assert(empty($common->errormsg));		

		if($res[0]['count'] == 0) 
		{
			return false;
		}
		
		return true;
	}
	
	static public function insertFakePremiumRegistration($uid)
	{
		settype ($uid, 'integer');
		assert ($uid > 0);
		
		$common = self::getCommon();
		
		$sql = "
			INSERT INTO ds_analizer_premium
            	(id_user, creation_date, start_date, end_date, active)
			VALUES
            	(".$uid.", now(), now(), now(), 't')";
		
  		$res = $common->executeQuery ($sql);				
		if(!empty($common->errormsg))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Устанавливает дату начала и окончания премиум-аккаунта в now
	 * (для тех, у кого закончились премиум-дни)
	 *
	 * @param integer $uid
	 * @return bool
	 */
	static public function setStartEndDateToNow($uid)
	{
		settype ($uid, 'integer');
		assert ($uid > 0);
		
		$common = self::getCommon();
		
		$sql = "
			UPDATE
            	ds_analizer_premium
			SET
				start_date = now(),
				end_date = now()
			WHERE
				id_user = ".$uid;
			
  		$res = $common->executeQuery ($sql);				
		if(!empty($common->errormsg))
		{
			return false;
		}
		
		return true;		
	}
	
	/**
	 * Получить дату окончания премиум аккаунта
	 *
	 * @param integer $uid
	 * @return  array
	 */
	static public function getUserPremiumDates($uid)
	{
		settype ($uid, 'integer');
		assert ($uid > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT 
				end_date as enddate_pg,
				start_date as startdate_pg
			FROM 
			 	ds_analizer_premium 
			WHERE 
				id_user = ".$uid;
		
  		$res = $common->getResultArray ( "", $sql, "", "", "" );		
		assert(empty($common->errormsg));
		return $res;		
	}
	
	
	static public function logPremiumChange($uid, $type, $days_delta, $enddate_old, $enddate_new, $comments)
	{
		settype ($uid, 'integer');
		assert ($uid > 0);
		settype ($days_delta, 'integer');
		assert(strlen($type) > 0);
		
		$common = self::getCommon();
		
		$sql = "
			INSERT INTO ds_analizer_premium_change
           		(
           			id_user,
           			type,
           			date,
           			days_delta,
					end_date_old,
					end_date_new,
					comments
           		)
       		VALUES
            	(
            		$uid,
            		'$type',
            		now(),
            		$days_delta,
            		'$enddate_old',
					'$enddate_new', 
					E'$comments'
				)
			";
							
		$res = $common->executeQuery($sql);				
		assert(empty($common->errormsg));
	}
}
?>