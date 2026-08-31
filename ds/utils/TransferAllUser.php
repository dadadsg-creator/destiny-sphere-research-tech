<?php
ini_set('memory_limit', '168M');
set_time_limit(0);

//require_once 'Main.php';
require_once '../Main.php';
	
// Определяем начальные параметры

/*
// Дракон2
$dbFrom = 0;
$woldFrom = 6;
// Дракон 3
$dbTo = 2;
$woldTo = 1;*/

// Телец 1
$dbFrom = 0;
$woldFrom = 5;
// Телец 3
$dbTo = 3;
$woldTo = 2;


/*
$dbFrom = 0;
$woldFrom = 3;


$dbTo = 3;
$woldTo = 1;*/


if (key_exists('action', $_REQUEST)) {
    assert($_REQUEST['ofset'] >= 0);
    assert($_REQUEST['limit'] > 0);

	AddPremium::Exec($dbFrom, $dbTo, $woldFrom, $woldTo);
} else {
echo '
TRANSFER USERS<br><br>
<form action="" method="GET"><input type="hidden" name="action" value="exec">
Ofset: <input type="text" name="ofset" value="0"><br>
Limit: <input type="text" name="limit" value="10"><br>
<input type="submit" value="Transfer">
</form>';}
//TransferUser::Exec($dbFrom, $dbTo, $woldFrom, $woldTo);


class AddPremium
{
	static private $dbFrom;

	static private $dbTo;

	static private $woldFrom;

	static private $woldTo;

	static private $common;
	
	static private $uiid;
	
	static private $newCoords = array();
	
	static private $userInstanceResult;

	static function Exec($dbFrom, $dbTo, $woldFrom, $woldTo)
	{
		assert($dbFrom >= 0);
		assert($dbTo >= 0);
		assert($woldFrom > 0);
		assert($woldTo > 0);

		self::$dbFrom = $dbFrom;
		self::$dbTo = $dbTo;
		self::$woldFrom = $woldFrom;
		self::$woldTo = $woldTo;

		self::$common = DS_Common::CreateForCurentDB(self::$dbFrom);
		$_SESSION["dbId"] = self::$dbFrom;

		// Выбираем всех игроков из исходного мира
		$sql = "SELECT
					ui.*
				FROM 
					ds_userinstance ui 
				WHERE 
					ui.id_world = ".$woldFrom."
                    AND
                    ui.lastrefresh < '2010-08-01 00:00:01'  
                    AND 
                     ui.lastrefresh > '2010-07-01 00:00:01'          
					OFFSET ".$_REQUEST['ofset']."
                    limit ".$_REQUEST['limit']."
                    ";

		$res = self::$common->getResultArray('', $sql, '', '', '');
		assert(empty(self::$common->errormsg));


		/*$res = array();
		$res[0] = array(
		'id_user' => 11,
		'id_userinstance' => 25,
		);*/
		$count = 0;
		// Проходимся по каждому игроку
		foreach ($res as $key => $userInfo)
		{
			$userId = (int)$userInfo['id_user'];
			$userInstanceId = (int)$userInfo['id_userinstance'];
			
			self::$uiid = $userInstanceId;

			assert($userId > 0);
			assert($userInstanceId > 0);

			echo "<br><b>Proccess user ".$userId."(".$userInstanceId.")</b><br>";

			self::$common->setDbId(self::$dbTo);
			$_SESSION["dbId"] = self::$dbTo;
			//Смотрим, есть ли у игрока инстанс во втором мире

			$sql = "SELECT
					ui.id_userinstance  
				FROM 
					ds_userinstance ui 
				WHERE 
					ui.id_world = ".self::$woldTo."
					AND
					ui.id_user = ".$userId ;

			$res = self::$common->getResultArray('', $sql, '', '', '');
			assert(empty(self::$common->errormsg));

			if (count($res) > 0) {
				echo "<font color='red'>ERROR: User Already has comb in new world !!!!!!!!!!!!</font><br>";
				continue;
			}
			
			self::$common->setDbId(3);
			$_SESSION["dbId"] = 3;
			
			// Смотрим, есть ли у игрока сота на Т3 
			$sql = "SELECT
					ui.id_userinstance  
				FROM 
					ds_userinstance ui 
				WHERE 
					ui.id_user = ".$userId ;

			$res = self::$common->getResultArray('', $sql, '', '', '');
			assert(empty(self::$common->errormsg));

			if (count($res) > 0) {
				echo "<font color='red'>ERROR: User Already has comb in TELTC !!!!!!!!!!!!</font><br>";
				continue;
			}
			
			self::$common->setDbId(2);
			$_SESSION["dbId"] = 2;
			
			// Смотрим, есть ли у игрока сота на Т3 
			$sql = "SELECT
					ui.id_userinstance  
				FROM 
					ds_userinstance ui 
				WHERE 
					ui.id_user = ".$userId ;

			$res = self::$common->getResultArray('', $sql, '', '', '');
			assert(empty(self::$common->errormsg));

			if (count($res) > 0) {
				echo "<font color='red'>ERROR: User Already has comb in OREL !!!!!!!!!!!!</font><br>";
				continue;
			}
			
			self::$common->setDbId(self::$dbTo);
			$_SESSION["dbId"] = self::$dbTo;
			
SU_DebugTools::LogAll("User ".$userId."(".$userInstanceId.") ", 'WorldUnion.log', 0);			

			$_SESSION["dbId"] = 0;
			self::$common->setDbId(0);
			
			// Получаем новые координаты
			self::$newCoords = self::_GetNewCombCoords();

			// Перевозим игрока
			self::_transferUser();

			$count ++;
		}

		echo "<br><br><b>___________________________$count users was transfered!___________________________</b><br>";
	}

	static  private function _transferUser()
	{
		echo 'START TRANSFER USER<br>';
		flush();
			
		self::_validateCombMoveAbility();
		
		$_SESSION["dbId"] = self::$dbFrom;
		$_SESSION["world"] = self::$woldFrom;
		$_SESSION["lang"] = 3;
		self::$common->setDbId(self::$dbFrom);
		
		//SI_PrepareUserForTransfer::Exec(self::$uiid);
	
		self::_storeUser();
		
		self::_saveUserInDB();
		
		self::_restoreUser();
		
		echo 'USER TRANSFERED<br>';
		flush();
	}
	
	static  private function _validateCombMoveAbility()
	{
		assert(self::$uiid > 0);
		assert(self::$woldTo > 0);
		assert(count(self::$newCoords) > 0);

		assert(!is_null(self::$dbFrom));
		self::$common->setDbID(self::$dbFrom);
				
		SI_ExecStoreUser::SetCommon(self::$common);
		
		$userData = SI_SearchUserInstance::GetUserDataByUIID(self::$uiid);
			
		assert(!is_null(self::$dbTo));
		self::$common->setDbID(self::$dbTo);
		
		SI_TestRestoreUserInWorld::Test(self::$woldTo, self::$newCoords, 'fffffffffffff', 'fffffffffff@mail.ru');
	}
	
	static  private function _storeUser()
	{
		// Сохраняем пользователя
		assert(!is_null(self::$dbFrom));
		self::$common->setDbID(self::$dbFrom);
		
		self::$userInstanceResult = SI_ControlStoreUserInstance::Exec(self::$uiid, true);
	}	
	
	static  private function _saveUserInDB()
	{
	}	
	
	static  private function _restoreUser()
	{
		// Разворачиваем в новом мире
		assert(!is_null(self::$dbTo));
		self::$common->setDbID(self::$dbTo);
		
		$toUIID = SI_ControlRestoreUserInstance::Restore(self::$woldTo, self::$newCoords, self::$userInstanceResult);
	}

	static  private function _GetNewCombCoords()
	{
		assert(self::$dbFrom >= 0);
		assert(self::$dbTo >= 0);
		assert(self::$uiid > 0);
				
		$combCoords = array();
				
		$_SESSION["dbId"] = self::$dbFrom;

		self::$common->setDbId(self::$dbFrom);
		
		$sql = "
               SELECT
					r.ringnumber,
					cn.combname
				FROM 
					ds_comb c 
				LEFT JOIN ds_combname cn ON (cn.id_comb = c.id_comb)
				LEFT JOIN ds_combcomplex cc ON (cc.id_combcomplex = c.id_combcomplex)
				LEFT JOIN ds_ring r ON (r.id_ring = cc.id_ring)
				WHERE
					c.id_userinstance = ".self::$uiid."
								";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		if (count($res) < 1) {
			echo "ERROR";
			echo "<pre>$sql</pre>";
			exit;
		}
/*echo "<xmp>";
print_r($res);*/
//exit;

		$_SESSION["dbId"] = self::$dbTo;
		self::$common->setDbId(self::$dbTo);

		$existsCombs = array();
		
		// в обычном случае берем любые свободные соты.
		foreach($res as $k => $comb)
		{			
			$ringNumber = (int)$comb['ringnumber'];
			assert($ringNumber > 0 && $ringNumber < 5);
			
			// Минимальный номер комплекса
			if ($ringNumber == 4) {
				$minComplex = 3;
				//$minComplex = 1375;
			} else {
				$minComplex =1;
				//$minComplex = 309;
			}
			
			if(count($existsCombs) > 0)
			{
				$existsCombAddon = " AND c.id_comb NOT IN (".implode(', ', $existsCombs).")";
			}else{
				$existsCombAddon = "";
			}
			
			// Получаем свободную соту на кольце
			$sql = "
			SELECT
                  MIN(c.id_comb) AS id_comb
            FROM
                ds_comb AS c
                INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                LEFT JOIN ds_reservedcomb AS rc ON (rc.id_comb = c.id_comb)
            WHERE
                 c.id_userinstance IS NULL             
                 AND
                 r.ringnumber = ".$ringNumber."
                 AND
                 NOT EXISTS (
                     SELECT id_comb
                     FROM ds_combname
                     WHERE id_comb = c.id_comb
                 )
                 AND
                 cc.complexnumber > ".$minComplex."
                  AND
	 			 rc.id_comb IS NULL ".$existsCombAddon;
			

			$res = self::$common->getResultArray("", $sql, "", "", "");
			assert(empty(self::$common->errormsg));
			
			$combId = $res[0]['id_comb'];
				
			if($combId == 0)
			{
				echo "<br><pre>$sql<br>";
				ds_error('No free space on ring = '.$ringNumber);
			}
			
			if ($ringNumber == 4) {
				$existsCombs[] = $combId;
			}

			$combData = self::GetCombDataByID($combId);
			assert(count($combData) > 0);
			
			$combCoords[$comb['combname']]['RING'] = $combData['ringnumber'];
			$combCoords[$comb['combname']]['COMPLEX'] = $combData['complexnumber'];
			$combCoords[$comb['combname']]['COMB'] = $combData['combnumber'];
			$combCoords[$comb['combname']]['info'] = 'minfree';
			
			echo " New coords ".$combData['ringnumber'].".".$combData['complexnumber'].".".$combData['combnumber']."<br>";
		}
/*echo "<pre>";
print_r($combCoords);
exit;*/		
		return $combCoords;
	}
	
	static public function GetCombDataByID($combID)
	{
		assert((int)$combID > 0);		
				
			$sql = "SELECT
      				co.id_userinstance,
      				co.combscore,
      				co.id_comb,
      				cn.combname,
      				co.combnumber,
      				co.id_combcomplex,
      				cc.complexnumber,
      				cc.id_ring,
      				ri.ringname,
      				ri.ringnumber
				FROM
    				((ds_comb as co
             			LEFT JOIN ds_combname as cn ON (co.id_comb = cn.id_comb))
                   			INNER JOIN ds_combcomplex as cc ON (co.id_combcomplex = cc.id_combcomplex))
                         		INNER JOIN ds_ring as ri ON (cc.id_ring = ri.id_ring)
				WHERE
     				co.id_comb = ".$combID;
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res[0];
	}
}



?>