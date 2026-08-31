<?
ini_set('memory_limit', '128M');
set_time_limit(0);

require_once '../Main.php';


//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
define('DEBUG_MODE', 1);
/*********** BEGIN WORK AREA **************/

$sapi = php_sapi_name();
if(strpos($sapi, "apache") !== false)
{
	$action = '';
	if(key_exists('action', $_REQUEST))
	{
		$action = $_REQUEST['action'];	
	}
	
	if(key_exists('world', $_REQUEST))
	{
		$world = $_REQUEST['world'];	
	}
	
	$worlds = RecountCoursesSearch::GetAllWorldsList();

	echo "<h1>RECOUNT COURSES</h1>";
	echo "<b>Select world for recount:</b><br>";

	echo "<form action='".$_SERVER['PHP_SELF']."' method='GET'>";
	foreach($worlds AS $k=>$worldData)
	{
		echo "<input type='checkbox' name='world[]' value=".$worldData['dbid']."_".$worldData['id_world'].">".$worldData['worldname']."<br>";
	}
	echo '<input type="hidden" name="action" value="scan">';
	echo "<br>";
	echo '<input type="submit" class="Submit" value="Recount">';
	echo "</form>";


	if($action == 'scan')
	{
		assert(!empty($world));
		
		echo "<xmp>";
		RecountCourses::Exec($world);
		echo "</xmp>";
	}
}else{
	RD_AdeptUserActionToConsole::$commandLineFlag = true;

	assert(key_exists('1', $_SERVER['argv']));
	
	$worldData = explode(',', $_SERVER['argv'][1]); // 'dbid'._.'id_world,dbid'._.'id_world,dbid'._.'id_world'
	if (empty($worldData[0])) {
		echo "Неверный аргумент ";
		exit;
	} 
	
	RecountCourses::Exec($worldData);
}

/**
 * Скрипт по вычищению мусора из базы скопившегося за день
 * 
 */
class RecountCourses
{
	/**
	 * @var DS_Common
	 */
	static private $common;
	
	static private $worldData;
	
	static private $action = 'index';
	
	// Время хранения торгов в базе
	static private $perionLength = 60;	
	
	// Дата последнего пересчета курсов
	static private $startDate;
	
	// Коэффициенты для пересчета за нану 
	static private $k = 0.368; // 1/e
	static private  $t = 0.01;	
		
	// Коэффициенты для пересчета за серу 
	static private $k_sc = 0.184; // все в 2 раза меньше
	static private  $t_sc = 0.005;	
	
	static public function Exec($worldData)
	{		
		assert(!empty($worldData));
		self::$worldData = $worldData;
		
		 $_SESSION['lang'] = 3;         
			
		    foreach (self::$worldData as $worldKey => $data) 
		    {
				$temp = explode('_', $data);
		    	$dbID = (int)$temp[0];
		    	$worldID = (int)$temp[1];
		    	
		    	assert(is_int($dbID) && $dbID >= 0);
				assert(is_int($worldID) && $worldID > 0);
		
		    	$_SESSION['dbId'] = $dbID;
		    	RecountCoursesSearch::$common = DS_Common::CreateForCurrentDB($dbID);
		    	
		    					
				$worldName  = RecountCoursesSearch::GetWorldNameById($worldID);
		    	
		    	// Начинаем транзакцию
		    	RecountCoursesSearch::$common->startTrans();
		    	
		    	RecountCoursesSearch::$common->executeQuery('SET STATEMENT_TIMEOUT TO 120000');
			    
self::_out("================================================================================================\n");
				self::_out("START RECOUNT COURCES\n");
                
                // Получаем дату последнего пересчета курсов 
                self::$startDate = RecountCoursesSearch::GetLastRecountDate($worldID);
               
                $nano = DE_Resource::CreateFromName('NANO_CRYSTAL');
				$weightNanoInNAno = $nano->getStazis();
				
				// Пересчитываем курс ресурсов
                self::_out("Start recount courses for [RESOURCES]\n\n");

                // Выбираем все ресы
				$resourses = RecountCoursesSearch::GetAllResourses();
				// Проходимся по каждому ресу
				foreach ($resourses as $key => $value) {
					// Если ресурс - нана, пропускаем его
					if ($value['id_attributename'] == 2) {
						continue;
					}
					// Получаем все торги по этому ресурсу
					$tradesData = RecountCoursesSearch::GetResourceTrades($worldID, self::$startDate, (int)$value['id_attributename']);
					// Вес ресурса в нане
					$resource = DE_Resource::CreateFromID((int)$value['id_attributename']);
					$weight = $resource->getStazis();
					$weight_nano = $weight/$weightNanoInNAno;
					// Название ресурса
					$resName = $resource->getLocolizedName();
					// Получам текущий торговый курс
					$currentCourse = (float)RecountCoursesSearch::CurrentCourseForResource((int)$value['id_attributename'], $worldID);

					// Пересчитываем курс за нану
					self::_recountCourse($worldID, $tradesData, 1, (int)$value['id_attributename'], $resName, $currentCourse, $weight_nano);
					
					// Получаем все торги по этому ресурсу за серу
					$tradesData = RecountCoursesSearch::GetResourceTrades($worldID, self::$startDate, (int)$value['id_attributename'], true);
					// Получам текущий торговый курс за серу
					$currentCourse = (float)RecountCoursesSearch::CurrentCourseForResource((int)$value['id_attributename'], $worldID, true);
					//Пересчитываем курс за серу
					self::_recountCourse($worldID, $tradesData, 1, (int)$value['id_attributename'], $resName, $currentCourse, $weight_nano, true);
				}
				 self::_out("\nEnd recount courses for [RESOURCES]\n
________________________________\n\n");

				// Пересчитываем курс юнитов
				self::_out("Start recount courses for [UNITS]\n\n");
				
				// Выбираем все юниты
				$units = RecountCoursesSearch::GetAllUnits();
				// Проходимся по каждому ресу
				foreach ($units as $key => $value) {
					$id_unit = (int)$value['id_unit'];
					assert(is_int($id_unit) && $id_unit > 0);
					// Получаем все торги по этому юниту
					$tradesData = RecountCoursesSearch::GetUnitTrades($worldID, self::$startDate, $id_unit);
					// Вес ресурса в нане
					$unit = DE_Unit::CreateFromID($id_unit);
					$weight = $unit->getStazis();
					$weight_nano = $weight/$weightNanoInNAno;
					// Название юнита
					$unitName = $unit->getName();
					// Получам текущий торговый курс
					$currentCourse = (float)RecountCoursesSearch::CurrentCourseForUnit($id_unit, $worldID);

					// Пересчитываем курс
					self::_recountCourse($worldID, $tradesData, 2, $id_unit, $unitName, $currentCourse, $weight_nano);
					
					// Получаем все торги по этому юниту за серу
					$tradesData = RecountCoursesSearch::GetUnitTrades($worldID, self::$startDate, $id_unit, true);
					// Получам текущий торговый курс за серу
					$currentCourse = (float)RecountCoursesSearch::CurrentCourseForUnit((int)$id_unit, $worldID, true);
					//Пересчитываем курс за серу
					self::_recountCourse($worldID, $tradesData, 2, $id_unit, $unitName, $currentCourse, $weight_nano, true);
				
				}
				 self::_out("\nEnd recount courses for [UNITS]\n
________________________________\n\n");							
				
                // Пересчитываем курс артов
                self::_out("Start recount courses for [ARTEFACTS]\n\n");
				
				// Выбираем все арты
				$arts = RecountCoursesSearch::GetAllArts();
				// Проходимся по каждому ресу
				foreach ($arts as $key => $value) {
					$id_art = (int)$value['id_artefact'];
					assert(is_int($id_art) && $id_art > 0);
					// Получаем все торги по этому ресурсу
					$tradesData = RecountCoursesSearch::GetArtefactTrades($worldID, self::$startDate, $id_art);
					// Вес ресурса в нане
					$art = DE_Artefact::CreateFromArtefactID($id_art);
					$weight = $art->getStazis();
					$weight_nano = $weight/$weightNanoInNAno;
					// Название юнита
					$artName = $art->getLocalizedName();
					// Получам текущий торговый курс
					$currentCourse = (float)RecountCoursesSearch::CurrentCourseForArt($id_art, $worldID);
					if (empty($currentCourse)) {
						self::_out("<font color='red'>На артефакт $artName не установлен курс <<<<<<<<<< ERROR</font>\n");
					} else {
						// Пересчитываем курс
						self::_recountCourse($worldID, $tradesData, 3, $id_art, $artName, $currentCourse, $weight_nano);
					}
					
					// Получаем все торги по этому юниту за серу
					$tradesData = RecountCoursesSearch::GetArtefactTrades($worldID, self::$startDate, $id_art, true);
					// Получам текущий торговый курс за серу
					$currentCourse = (float)RecountCoursesSearch::CurrentCourseForArt((int)$id_art, $worldID, true);
					if (empty($currentCourse)) {
						echo "<font color='red'>На артефакт $artName не установлен курс <<<<<<<<<< ERROR</font>\n";
					} else {
						//Пересчитываем курс за серу
						self::_recountCourse($worldID, $tradesData, 3, $id_art, $artName, $currentCourse, $weight_nano, true);
					}
				}
				 self::_out("\nEnd recount courses for [ARTEFACTS]\n
________________________________\n\n");
			
                // Обновляем дату последнего пересчета курсов
               RecountCoursesSearch::InsertRecountDate($worldID);

                // Заканчиваем транзакцию
                RecountCoursesSearch::$common->closeTrans('COMMIT');
                //RecountCoursesSearch::$common->closeTrans('ROLLBACK');
				
				echo "\n\n";
		    }

		   self::_out("\nEND RECOUNT COURCES\n");
		   self::_out("================================================================================================\n");
		    
		    // echo SU_DebugTools::GetInternalDate()." - RECOUNT COURSES ENEDED\n";
		   	// echo "</font>";
	
	}

	static private function _recountCourse($worldID, $tradesData, $idFromCategory, $idFromArticle, $reaName, $currentCourse, $weight_nano, $silver = false)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_array($tradesData));
		assert(is_int($idFromCategory) && $idFromCategory > 0);
		assert(is_int($idFromArticle) && $idFromArticle > 0);
		assert(is_string($reaName) && !empty($reaName));
		assert(is_float($currentCourse) && $currentCourse > 0);
		assert(is_float($weight_nano) || is_int($weight_nano));
		assert($weight_nano > 0);
		
		// Если это нана, ничего не считаем
		if ($idFromCategory == 1 && $idFromArticle == 2) {
			return;
		}
		
		
		if ($silver == true) {
			$forRes = " за серу";
		} else {
			$forRes = " за нану";
		}
		self::_out($reaName.$forRes."\n");
		
		if ($silver == true) {
			// Курс на серу
			$id_tradecategory = 4;
			$id_article = 1;
			$id_tradecourse = 6;
			$t = self::$t_sc;
			$k = self::$k_sc;
			$primaryCourse = RecountCoursesSearch::GetPrimaryCourceForSC($worldID, $idFromCategory, $idFromArticle);
			if (!$primaryCourse)
			{
				self::_out("<font color='red'>На артефакт $reaName не установлен стартовый курс <<<<<<<<<< ERROR</font>\n");
				return;
			}
		} else {
			// Курс на нанку
			$id_tradecategory = 1;
			$id_article = 2;
			$id_tradecourse = 2;
			$t = self::$t;
			$k = self::$k;
			$primaryCourse = $weight_nano;
		}

		// Вес продаж выше среднего курса(т.е. их общее количество)
		$totalWeightH = 0;
		// Вес продаж ниже среднего курса(т.е. их общее количество)
		$totalWeightL = 0;
		// Параметр P
		$P = 0;
		// Параметр R
		$R = 0;
		// Количество торгов
		$countTrades = 0;
		
/*echo "<font color = 'white'>";
echo "--------------------------------------------\n";*/

		// Проходимся по каждому торгу
		foreach ($tradesData as $key => $data)
		{
			if ($data['delta'] == 'h') {
				// Увеличиваем вес продаж выше среднего курса
				$totalWeightH += $data['from_ammount'];
			} elseif ($data['delta'] == 'l') {
				$totalWeightL += $data['from_ammount'];
			}

			$countTrades ++;
		}

/*echo "\n\nКоличество продаж: ".$countTrades."\n";

echo "Вес продаж выше среднего курса ".$totalWeightH."\n";
echo "Вес продаж ниже среднего курса ".$totalWeightL."\n";

$pl = '(';
$pl2 = '(';
$pStr = '';
$rStr = '';*/

if (count($tradesData) == 0) {
	echo "Торгов не было";
}

		// Проходимся по каждому торгу
		foreach ($tradesData as $key => $data)
		{
			$cor = RecountCoursesSearch::GetCorForDenic($idFromCategory, $idFromArticle, $data['id_article'], $worldID);
//echo $cor." --- ".$data['id_article']." ---- ".number_format($data['to_ammount'], 0, ',', ' ')."!!\n";
$for = $data['to_ammount']*$cor;
if ($for < 1 ) {
	$namberformat = 5;
} else {
	$namberformat = 0;
}
echo number_format($data['from_ammount'], 0, ',', ' ')." for ".number_format(($data['to_ammount']*$cor), $namberformat, ',', ' ');	
			if ($data['delta'] == 'h') {
				// Считаем P
				$P += $data['from_ammount']*$data['kv'];
echo "(/\)";	


//$pStr .= $pl.$data['from_ammount']."*".$data['kv'];
//$pl = '+';				
			} elseif ($data['delta'] == 'l') {
				$R += $data['from_ammount']*$data['kn'];
//$rStr .= $pl2.$data['from_ammount']."*".$data['kn'];
//$pl2 = '+';
echo "(\/)";	
			} else {
echo "(=)";	
				
			}
echo "; ";	

		}
echo "\n";	
//$pStr = $pStr.")/$totalWeightH";
//$rStr = $rStr.")/$totalWeightL";



		if ($P != 0) {
			$P = $P/$totalWeightH;
		}

		if ($R != 0) {
			$R = $R/$totalWeightL;
		}

//echo "P = ".$pStr." = ".$P."\n";
//echo "R = ".$rStr." = ".$R."\n";

//echo "Текущий курс: ".$currentCourse."\n";
//echo "Вес в нане: ".$weight_nano."\n";			

		$recountMethod = 99;
		
		// Если с момента последнего изменения курса этот товар ни разу не купили или купили по курсу
		if ($P == $R) {
			// Если продажи присутствуют, то считаем по формуле $P = $R
			if (count($tradesData) > 0) {
				$recountMethod = 1; // $P = $R
			} else {
				// Если продаж нет
				// Сравниваем текущий курс с начальным
				if ($currentCourse > $primaryCourse) {
					// И курс больше начального курса, считаем по формуле $P == 0
					$recountMethod = 3; // P = 0
				} elseif ($currentCourse < $primaryCourse) {
					// И курс меньше начального курса, считаем по формуле $R == 0
					$recountMethod = 2; // R = 0
				} else {
					// Если стартовый курс равен текущему
					$recountMethod = 1; // $P = $R
				}				
			}
		} elseif ($P == 0) {
			$recountMethod = 6; // P = 0 && R != 0 
		} elseif ($R == 0) {
			$recountMethod = 7; // R = 0 && P != 0 
		} elseif ($P > $R) {
			$recountMethod = 4; // P > R
		} elseif ($R > $P) {
			$recountMethod = 5; // R > P
		} else {
			echo "Error!";
		}
/*echo "
".self::$startDate->getPgFormat()."
";	*/	
		$perionLength = RecountCoursesSearch::GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID, false, true);
//echo " recountMethod = $recountMethod\n";		
		
		switch ($recountMethod)
		{
			// P = R && countOfTrades > 0
			case $recountMethod == 1:			
				$course = $currentCourse;
				$C = 0;
				break;

			// R = P = 0 && $currentCourse < $primaryCourse
			case $recountMethod == 2:
				// Если с момента последнего изменения курса товар за цену ниже среднего курса ни разу не купили
				
				// Получаем количество ростов курса за период
				$countGrowth = RecountCoursesSearch::GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID);
				assert(is_int($countGrowth) && $countGrowth >= 0);
				
				$C = $k*sqrt(2)*exp((2*$t*$countGrowth)/$perionLength);
//echo '$C = '.$k.'*'.sqrt(2).'*exp((2*'.$t.'*'.$countGrowth.')/'.$perionLength.')';				
				break;

			// R = P = 0 && $currentCourse > $primaryCourse
			case $recountMethod == 3:
				// Если с момента последнего изменения курса товар за цену выше среднего курса ни разу не купили
				
				// Получаем количество падений курса за период
				$countGrowth = RecountCoursesSearch::GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID, true);
				assert(is_int($countGrowth) && $countGrowth >= 0);
//echo '$C = -'.$k.'*'.sqrt(2).'*exp((2*'.$t.'*'.$countGrowth.')/'.$perionLength.')';				
				
				$C = -$k*sqrt(2)*exp((2*$t*$countGrowth)/$perionLength);
				break;

			// P > R
			case $recountMethod == 4:
				//Покупок по курсу больше текущего было больше покупок по курсу меньше текущего;
				
				// Получаем количество ростов курса за период
				$countGrowth = RecountCoursesSearch::GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID);
				assert(is_int($countGrowth) && $countGrowth >= 0);
echo "P = ".$P."\n";
echo "R = ".$R."\n";						
				$C = $k*($R/$P)*exp((2*$t*$countGrowth)/$perionLength);
				break;

			// R > P
			case $recountMethod == 5:
				//Покупок по курсу меньше текущего было больше покупок по курсу больше текущего;
				
				// Получаем количество падений курса за период
				$countGrowth = RecountCoursesSearch::GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID, true);
				assert(is_int($countGrowth) && $countGrowth >= 0);
echo "P = ".$P."\n";
echo "R = ".$R."\n";
				$C = -$k*($P/$R)*exp((2*$t*$countGrowth)/$perionLength);
				break;
				
			// P = 0 && R != 0 
			case $recountMethod == 6:
				// Если с момента последнего изменения курса товар за цену ниже среднего курса ни разу не купили
				
				// Получаем количество ростов курса за период
				$countGrowth = RecountCoursesSearch::GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID, true);
				assert(is_int($countGrowth) && $countGrowth >= 0);
				
				$C = -$k*exp((2*$t*$countGrowth)/$perionLength);
//echo '$C = -'.$k.'*exp((2*'.$t.'*'.$countGrowth.')/'.$perionLength.')';				
				break;

			// R = 0 && P != 0 
			case $recountMethod == 7:
				// Если с момента последнего изменения курса товар за цену выше среднего курса ни разу не купили
				
				// Получаем количество падений курса за период
				$countGrowth = RecountCoursesSearch::GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID);
				assert(is_int($countGrowth) && $countGrowth >= 0);
				
				$C = $k*exp((2*$t*$countGrowth)/$perionLength);
				break;

			default:
				echo "\nERROR!!";
				exit;
		}

		/*if ($C != 0 )
		{
			// Если дельта Минимальный курс
			$course = $currentCourse + $C;

			// Если курс меньше минимального, то ставим ему равным ему
			if ($course < $weight_nano) {
				//$course = $weight_nano;
			}

			if ($course < 0) {
				echo "Error!";
			}
		}*/
		$course = $currentCourse + $C*$currentCourse;
		//$course = $currentCourse + $C;
		if($course <= 0) { echo "Wrong course: ".$course." delta: ".$C;
		assert(false); }
		
		
		//$goods = self::_getGoodsName($idFromCategory, $idFromArticle);
		
		// Меняем курс
		self::_ChangeCourse($idFromCategory, $idFromArticle, $id_tradecategory, $id_article, (float)$course, $worldID);
		
		self::_out("Set Course: ".$course." (".$C.") [".$currentCourse."]\n\n");
	}
	
	static private function _ChangeCourse($idFromCategory, $idFromArticle, $id_tradecategory, $id_article, $cource, $worldID)
	{
		assert(is_int($idFromArticle) && $idFromArticle > 0);
		assert(is_int($idFromCategory) && $idFromCategory > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($worldID) && $worldID > 0);
		assert($cource > 0);
		
		switch ($idFromCategory)
		{
			// Ресы
			case 1:
				// Получаем текущий курс
				RecountCoursesSearch::InsertResourceCourse($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource);
				break;

			// Юниты
			case 2:
				RecountCoursesSearch::InsertUnitsCourse($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource);
				break;

			// Арты
			case 3:
				RecountCoursesSearch::InsertArtefactCourse($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource);
				break;

			default:
				self::_out("Wrong Data!\n");
				exit;
		}
	}
	
	static protected function _out($msg)
	{				
		//$out = SU_DebugTools::GetInternalDate()." - ".$msg."\n";
		$out = $msg;
		echo $msg;
						
		$fileName = "RecountCourses.log";	
		$str = date('Y-m-d H:i:s')." ".$msg."\n";	
		SU_DebugTools::WriteToLog($str, $fileName);
				
		flush();
	}
}

class RecountCoursesSearch
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common;
	 */
	static public $common;

	static public function GetAllWorldsList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbid'] = $dbid;
				$out[] = $v;
			}
		}
		return $out;
	}

	static public function GetLastRecountDate($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "
			SELECT 
				max(tcr.add_date) AS start_date
			FROM 
				ds_trade_course_recount AS tcr
			WHERE 
				tcr.id_world = ".$worldID;
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		if(count($res) == 0 || empty($res[0]["start_date"]))
		{
			$time = 1000;
		}else{
			$time = $res[0]["start_date"];
		}
		return GB_TypeFactory::CreateBase('GB_Date', $time);
	}

	static public function InsertRecountDate($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "INSERT INTO ds_trade_course_recount (id_world) VALUES (".$worldID.")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
	}

	static public function GetResourceTrades($worldID, GB_Date $startDate, $resourseId, $silver = false)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);
		assert(is_int($resourseId) && $resourseId > 0);

		$where = '';
		if ($silver == true) {
			$where = "
			AND
                th.id_to_category  = 4"; 
		} else {
			$where = "
			AND
                th.id_to_category  != 4"; 
		}

		$sql = "
			SELECT
	            th.id_from_category,
                th.id_from_article,
                r.id_attributename AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	from_ammount,
                to_ammount,
                th.kv,
                th.kn,
                th.delta,
                th.weight_nano
        	FROM
	            ds_resource AS r
            	INNER JOIN ds_trade_course AS tc ON (1 = 1)
            	LEFT JOIN ds_trade_history AS th ON
            	(
	                th.id_from_article = r.id_attributename
                	AND
                	th.id_to_category = tc.id_tradecategory
                	AND
                	th.id_to_article = tc.id_article
            	)
        	WHERE
	            th.id_from_category = 1
                AND 
                th.id_from_article = ".$resourseId."
            	AND
            	r.trade_resources = true
            	AND
            	th.world_id = ".$worldID."
            	AND
            	th.end_date > '".$startDate->getPgFormat()."'
            	".$where;

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res;
	}

	static public function GetUnitTrades($worldID, GB_Date $startDate, $id_unit, $silver = false)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($id_unit) && $id_unit > 0);

		assert(self::$common instanceof DS_Common);

		$where = '';
		if ($silver == true) {
			$where = "
			AND
                th.id_to_category  = 4"; 
		} else {
			$where = "
			AND
                th.id_to_category  != 4"; 
		}

		$sql = "
			SELECT
	            th.id_from_category,
                th.id_from_article,
                u.id_unit AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	from_ammount,
                to_ammount,
                th.kv,
                th.kn,
                th.delta,
                th.weight_nano
            FROM
                ds_unit AS u
                INNER JOIN ds_trade_course AS tc ON (1 = 1)
                LEFT JOIN ds_trade_history AS th ON
                (
                    th.id_from_article = u.id_unit
                    AND
                    th.id_to_category = tc.id_tradecategory
                    AND
                    th.id_to_article = tc.id_article
                )
            WHERE
                th.id_from_category = 2
                AND 
                th.id_from_article = ".$id_unit."
            	AND
            	th.world_id = ".$worldID."
            	AND
            	th.end_date > '".$startDate->getPgFormat()."'
            	".$where;

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res;
	}

	static public function GetArtefactTrades($worldID, GB_Date $startDate, $id_art, $silver = false)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($id_art) && $id_art > 0);

		assert(self::$common instanceof DS_Common);

		$where = '';
		if ($silver == true) {
			$where = "
			AND
                th.id_to_category  = 4"; 
		} else {
			$where = "
			AND
                th.id_to_category  != 4"; 
		}

		$sql = "
			SELECT
	            th.id_from_category,
                th.id_from_article,
                a.id_artefact AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	from_ammount,
                to_ammount,
                th.kv,
                th.kn,
                th.delta,
                th.weight_nano
            FROM
                ds_artefact AS a
                INNER JOIN ds_trade_course AS tc ON (1 = 1)
                LEFT JOIN ds_trade_history AS th ON
                (
                    th.id_from_article = a.id_artefact
                    AND
                    th.id_to_category = tc.id_tradecategory
                    AND
                    th.id_to_article = tc.id_article
                )
            WHERE
                th.id_from_category = 3
                 AND 
                th.id_from_article = ".$id_art."
                 AND
            	th.world_id = ".$worldID."
            	 AND
            	th.end_date > '".$startDate->getPgFormat()."'
            	".$where;

		/*echo "<br><pre>";
		print_r($sql);
		echo "<br>";
		exit;*/		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res;
	}

	static public function GetResuorceName($articleID)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(self::$common instanceof DS_Common);


		$sql = "
				SELECT
		            l.text
		        FROM
		            ds_attributename a
		        INNER JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = a.id_localizedname)
		        WHERE
		            id_attributename = ".$articleID."
		             AND
		            l.id_language = ".constant('DEFAULT_LANGUAGE')."
					";

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res[0]['text'];
	}

	static public function GetUnitName($articleID)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "
				SELECT
		            l.text
		        FROM
		            ds_unit u
		        INNER JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = u.id_localizedname)
		        WHERE
		            id_unit = ".$articleID."
		             AND
		            l.id_language = ".constant('DEFAULT_LANGUAGE')."
					";

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res[0]['text'];
	}

	static public function GetArtefactName($articleID)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "
				SELECT
		            l.text
		        FROM
		            ds_artefact a
		        INNER JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = a.id_localizedname)
		        WHERE
		            id_artefact = ".$articleID."
		             AND
		            l.id_language = ".constant('DEFAULT_LANGUAGE')."
					";

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res[0]['text'];
	}

	static public function GetCountGrowth($idFromCategory, $idFromArticle, $id_tradecourse, $worldID, $drop = false, $total = false)
	{
		assert(is_int($idFromCategory) && $idFromCategory > 0);
		assert(is_int($idFromArticle) && $idFromArticle > 0);
		assert(is_int($id_tradecourse) && $id_tradecourse > 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_bool($drop));

		assert(self::$common instanceof DS_Common);

		if ($drop == true) {
			$sign = '<';
		} else {
			$sign = '>';
		}
		
		if ($total == false) {
			 $where = "
			 	AND
					t.delta_course ".$sign." 0";
		}

		switch ($idFromCategory)
		{
			// Ресурсы
			case 1:
				$from  = 'ds_trade_resorce_course_data';
				$good = 'id_resource';
				break;

				// Юниты
			case 2:
				$from  = 'ds_trade_units_cours_data';
				$good = 'id_unit';
				break;

				// Артефакты
			case 3:
				$from  = 'ds_trade_artefact_course_data';
				$good = 'id_artefact';
				break;
		}

		$sql = "
				SELECT 
					count(*) as count_growth
				FROM 
					".$from." t
				WHERE 
					t.".$good." = ".$idFromArticle."
					 AND
					t.id_world = ".$worldID."
					 AND
					t.id_tradecourse = ".$id_tradecourse."
					 AND
					t.add_date > (now() - interval '60 days')
					".$where;
//echo "<pre>".$sql."</pre><br>";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		if (count($res) == 0) {
			return 0;
		}

		return (int)$res[0]['count_growth'];
	}

	static public function InsertResourceCourse($worldID, $resourceID, $categoryID, $articleID, $course)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($resourceID) && $resourceID > 0);
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		//		assert(is_float($course) && $course > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "SELECT insert_resource_trade_course($worldID, $resourceID, $categoryID, $articleID, ".number_format($course, 5, ".", "").")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
	}

	static public function InsertUnitsCourse($worldID, $resourceID, $categoryID, $articleID, $course)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($resourceID) && $resourceID > 0);
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		//		assert(is_float($course) && $course > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "SELECT insert_unit_trade_course($worldID, $resourceID, $categoryID, $articleID, ".number_format($course, 5, ".", "").")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
	}

	static public function InsertArtefactCourse($worldID, $resourceID, $categoryID, $articleID, $course)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($resourceID) && $resourceID > 0);
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		//		assert(is_float($course) && $course > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "SELECT insert_artefact_trade_course($worldID, $resourceID, $categoryID, $articleID, ".number_format($course, 5, ".", "").")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
	}

	static public function GetWorldNameById($worldId)
	{
		assert(is_int($worldId) && $worldId >0);
		assert(self::$common instanceof DS_Common);

		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = ".$worldId;

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res[0]['worldname'];
	}

	static public function GetAllResourses()
	{
		assert(self::$common instanceof DS_Common);

		//Получаем id  всех ресов
		$sql = "
				SELECT
					r.id_attributename, 
					an.attributename
				FROM 
					ds_resource r
               	LEFT JOIN ds_attributename an ON (an.id_attributename = r.id_attributename)
                WHERE 
                	r.trade_resources = 't' ";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	static public function GetAllUnits()
	{
		assert(self::$common instanceof DS_Common);

		//Получаем id  всех боевых юнитов
		$sql = "
				SELECT
					u.id_unit
				FROM 
					ds_unit u 
               	WHERE
               		u.id_unitcontainertype = 1";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	static public function GetAllArts()
	{
		assert(self::$common instanceof DS_Common);

		//Получаем id  всех боевых юнитов
		$sql = "
				SELECT
					a.id_artefact
				FROM 
					ds_artefact a";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	static public function CurrentCourseForResource($resourceID, $worldID, $silver = false)
	{
		assert(is_int($resourceID) && $resourceID >0);
		assert(is_int($worldID) && $worldID > 0);

		assert(self::$common instanceof DS_Common);

		if ($silver == true) {
			$id_tradecourse = 6;
		} else {
			$id_tradecourse = 2;
		}

		//Получаем курс ресурса за нану
		$sql = "
				SELECT 
                    trcd.value
                FROM
				    ds_trade_resorce_course_data AS trcd
				WHERE
					trcd.id_resource = ".$resourceID."
					 AND
					trcd.is_current = true
					 AND
					trcd.id_world = ".$worldID."
					 AND
					trcd.id_tradecourse = ".$id_tradecourse;

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);

		return (float)$res[0]['value'];
	}

	static public function CurrentCourseForUnit($unitID, $worldID, $silver = false)
	{
		assert(is_int($unitID) && $unitID >0);
		assert(is_int($worldID) && $worldID > 0);

		assert(self::$common instanceof DS_Common);

		if ($silver == true) {
			$id_tradecourse = 6;
		} else {
			$id_tradecourse = 2;
		}


		//Получаем курс ресурса за нану
		$sql = "
				SELECT 
                	tucd.value
                FROM
                	ds_trade_units_cours_data AS tucd
                WHERE
                	 tucd.id_unit = ".$unitID."
	                AND
	                tucd.is_current = true
	                 AND
	                tucd.id_world = ".$worldID."
	                  AND
					tucd.id_tradecourse = ".$id_tradecourse;

		$res = self::$common->getResultArray("", $sql, "", "", "");
		if (!empty(self::$common->errormsg)) {
			echo self::$common->errormsg."<br><pre>";
			echo $sql;
		}
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);

		return (float)$res[0]['value'];
	}

	static public function CurrentCourseForArt($id_art, $worldID, $silver = false)
	{
		assert(is_int($id_art) && $id_art >0);
		assert(is_int($worldID) && $worldID > 0);

		assert(self::$common instanceof DS_Common);

		if ($silver == true) {
			$id_tradecourse = 6;
		} else {
			$id_tradecourse = 2;
		}

		//Получаем курс ресурса за нану
		$sql = "
				SELECT 
                	tacd.value
                FROM
                	ds_trade_artefact_course_data AS tacd
                WHERE
                	  tacd.id_artefact = ".$id_art."
	                AND
	                tacd.is_current = true
	                 AND
	                tacd.id_world = ".$worldID."
	                 AND
					tacd.id_tradecourse = ".$id_tradecourse;

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		//assert(count($res) == 1);

		if (count($res) > 0) {
			assert(count($res) == 1);
			return (float)$res[0]['value'];
		} else {
			return false;
		}

	}
	
	static public function GetPrimaryCourceForSC($worldID, $idCategory, $idArticle)
	{
		assert(is_int($idCategory) && $idCategory >0);
		assert(is_int($idArticle) && $idArticle >0);
		assert(is_int($worldID) && $worldID > 0);

		assert(self::$common instanceof DS_Common);

		//Получаем средний крс за серу
		$sql = "
				SELECT 
					value 
				FROM 
					ds_trade_start_cources
				WHERE 
					id_trade_category = ".$idCategory."
					 AND 
					id_trade_article = ".$idArticle."
					AND
            		id_world = ".$worldID ;

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		//assert(count($res) == 1);

		if (count($res) > 0) {
			assert(count($res) == 1);
			return (float)$res[0]['value'];
		} else {
			return false;
		}

	}

	static public function GetCorForDenic($idFromCategory, $idFromArticle, $idToArticle, $worldID)
	{
		assert(self::$common instanceof DS_Common);


		switch ($idFromCategory)
		{
			case 1:
				//Получаем курс ресурса за нану
				$sql = "
				SELECT 
                                       trcd.value/(
                                       				SELECT 
						                                       trcd.value
						                                FROM
						                                    ds_trade_resorce_course_data AS trcd
						                                WHERE
						                                trcd.id_resource = $idFromArticle
											                AND
											                trcd.is_current = true
											                AND
											                trcd.id_world = ".$worldID."
											                AND
											                trcd.id_tradecourse = 2
											               ) as val
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                                trcd.id_resource = $idToArticle
					                AND
					                trcd.is_current = true
					                AND
					                trcd.id_world = ".$worldID."
					                AND
					                trcd.id_tradecourse = 2";

				break;

			case 2:
				//Получаем курс ресурса за нану
				$sql = "
				SELECT 
                                       trcd.value/(
                                       				SELECT 
						                                       trcd.value
						                                FROM
						                                    ds_trade_units_cours_data AS trcd
						                                WHERE
						                                trcd.id_unit = $idFromArticle
											                AND
											                trcd.is_current = true
											                AND
											                trcd.id_world = ".$worldID."
											                AND
											                trcd.id_tradecourse = 2
											               ) as val
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                                trcd.id_resource = $idToArticle
					                AND
					                trcd.is_current = true
					                AND
					                trcd.id_world = ".$worldID."
					                AND
					                trcd.id_tradecourse = 2";
				break;

			case 3:
				//Получаем курс ресурса за нану
				$sql = "
				SELECT 
                                       trcd.value/(
                                       				SELECT 
						                                       trcd.value
						                                FROM
						                                    ds_trade_artefact_course_data AS trcd
						                                WHERE
						                                trcd.id_artefact = $idFromArticle
											                AND
											                trcd.is_current = true
											                AND
											                trcd.id_world = ".$worldID."
											                AND
											                trcd.id_tradecourse = 2
											               ) as val
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                                trcd.id_resource = $idToArticle
					                AND
					                trcd.is_current = true
					                AND
					                trcd.id_world = ".$worldID."
					                AND
					                trcd.id_tradecourse = 2";
				break;
		}


		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		//assert(count($res) == 1);

		if (count($res) > 0) {
			assert(count($res) == 1);
			return (float)$res[0]['val'];
		} else {
			return false;
		}

	}
}

?>