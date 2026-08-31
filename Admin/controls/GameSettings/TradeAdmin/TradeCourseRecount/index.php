<?php
ini_set('memory_limit', '16M');
set_time_limit(600);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

//require_once "../classes/TradeCourseRecountSearch.class.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TradeCousreRecount><font size=3><b>HELP</b></font></a></p>';

echo "<font color='White'>";
$action = '';
if(key_exists('action', $_REQUEST))
{
	$action = $_REQUEST['action'];
}

if(key_exists('world', $_REQUEST))
{
	$world = $_REQUEST['world'];
}

$worlds = SU_RecountCoursesSearch::GetAllWorldsList();

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
	//echo "<xmp>";
	SU_RecountCourses::Exec($world);
	//echo "</xmp>";
}
echo "</font>";

/*CourseRecount::Exec($_REQUEST);

class CourseRecount
{
	static private $common;
	
	static private $worldData;
	
	static private $action = 'index';
	
	// Время хранения торгов в базе
	static private $perionLength = 60;
	
	// Коэффициенты
	static private $k = 0.368; // 1/e
	static private  $t = 0.01;	
		
	static public function Exec($data)
	{
		self::_getParams($data);
		
		switch (self::$action) {
			case 'index':
				self::_showIndex();
				break;

			case 'recount':
				self::_recount();
				break;
			default:
				break;
		}
	}
	
	static private function _showIndex()
	{
		$worlds = TradeCourseRecountSearch::GetAllWorldsList();
		
		echo "<h1>Trade Course Recount</h1>";
		echo "<b>Select world for recount:</b><br>";
		
		echo "<form action='".$_SERVER['PHP_SELF']."' method='GET'>";
		foreach($worlds AS $k=>$worldData)
		{
			// Затыка для теста
			if (constant('GAME_HOST') == 'http://test.destinysphere.ru' && $worldData['worldname'] != 'Сфера Носорога') {
				continue;
			}
			echo "<input type='checkbox' name='world[]' value=".$worldData['dbid']."_".$worldData['id_world'].">".$worldData['worldname']."<br>";
		}
		echo '<input type="hidden" name="action" value="recount">';
		echo "<br>";
		echo '<input type="submit" class="Submit" value="Recount">';
		echo "</form>";
	}
		
	static private function _recount()
	{
			echo "<font color=#F8F8FF>";
			//echo SU_DebugTools::GetInternalDate()." - START RECOUNT COURSES<br>";
            //flush();    

            $_SESSION['lang'] = 3;         
			
		    foreach (self::$worldData as $worldKey => $data) 
		    {
				$temp = explode('_', $data);
		    	$dbID = (int)$temp[0];
		    	$worldID = (int)$temp[1];
		    	
		    	assert(is_int($dbID) && $dbID >= 0);
				assert(is_int($worldID) && $worldID > 0);
		
		    	$_SESSION['dbId'] = $dbID;
		    	TradeCourseRecountSearch::$common = DS_Common::CreateForCurrentDB($dbID);
		    	
		    					
				$worldName  = TradeCourseRecountSearch::GetWorldNameById($worldID);
		    	
		    	// Начинаем транзакцию
		    	TradeCourseRecountSearch::$common->startTrans();
		    	
		    	TradeCourseRecountSearch::$common->executeQuery('SET STATEMENT_TIMEOUT TO 120000');
			    
				echo "START RECOUNT COURCES<br><br>";
                //echo SU_DebugTools::GetInternalDate()." - Start recount courses for world [".$worldName."]<br><br>";
                flush(); 
                
                // Получаем дату последнего пересчета курсов 
                $startDate = TradeCourseRecountSearch::GetLastRecountDate($worldID);
               
                $nano = DE_Resource::CreateFromName('NANO_CRYSTAL');
				$weightNanoInNAno = $nano->getStazis();
				
				// Пересчитываем курс ресурсов
                echo "Start recount courses for [RESOURCES]<br><br>";
				flush();
				// Выбираем все ресы
				$resourses = TradeCourseRecountSearch::GetAllResourses();
				// Проходимся по каждому ресу
				foreach ($resourses as $key => $value) {
					// Если ресурс - нана, пропускаем его
					if ($value['id_attributename'] == 2) {
						continue;
					}
					// Получаем все торги по этому ресурсу
					$tradesData = TradeCourseRecountSearch::GetResourceTrades($worldID, $startDate, (int)$value['id_attributename']);
					// Вес ресурса в нане
					$resource = DE_Resource::CreateFromID((int)$value['id_attributename']);
					$weight = $resource->getStazis();
					$weight_nano = $weight/$weightNanoInNAno;
					// Название ресурса
					$resName = $resource->getLocolizedName();
					// Получам текущий торговый курс
					$currentCourse = (float)TradeCourseRecountSearch::CurrentCourseForResource((int)$value['id_attributename'], $worldID);

					// Пересчитываем курс
					self::_recountCourse($worldID, $tradesData, 1, (int)$value['id_attributename'], $resName, $currentCourse, $weight_nano);
				}
				echo "<br>End recount courses for [RESOURCES]<br>
				________________________________<br><br>";
				flush();

				// Пересчитываем курс юнитов
				echo "Start recount courses for [UNITS]<br><br>";
				flush();
				// Выбираем все юниты
				$units = TradeCourseRecountSearch::GetAllUnits();
				// Проходимся по каждому ресу
				foreach ($units as $key => $value) {
					$id_unit = (int)$value['id_unit'];
					assert(is_int($id_unit) && $id_unit > 0);
					// Получаем все торги по этому ресурсу
					$tradesData = TradeCourseRecountSearch::GetUnitTrades($worldID, $startDate, $id_unit);
					// Вес ресурса в нане
					$unit = DE_Unit::CreateFromID($id_unit);
					$weight = $unit->getStazis();
					$weight_nano = $weight/$weightNanoInNAno;
					// Название юнита
					$unitName = $unit->getName();
					// Получам текущий торговый курс
					$currentCourse = (float)TradeCourseRecountSearch::CurrentCourseForUnit($id_unit, $worldID);

					// Пересчитываем курс
					self::_recountCourse($worldID, $tradesData, 2, $id_unit, $unitName, $currentCourse, $weight_nano);
				}
				echo "<br>End recount courses for [UNITS]<br>
				________________________________<br><br>";
				flush();
				
				
				
                // Пересчитываем курс артов
                echo "Start recount courses for [ARTEFACTS]<br><br>";
				flush();
				// Выбираем все арты
				$arts = TradeCourseRecountSearch::GetAllArts();
				// Проходимся по каждому ресу
				foreach ($arts as $key => $value) {
					$id_art = (int)$value['id_artefact'];
					assert(is_int($id_art) && $id_art > 0);
					// Получаем все торги по этому ресурсу
					$tradesData = TradeCourseRecountSearch::GetUnitTrades($worldID, $startDate, $id_art);
					// Вес ресурса в нане
					$art = DE_Artefact::CreateFromArtefactID($id_art);
					$weight = $art->getStazis();
					$weight_nano = $weight/$weightNanoInNAno;
					// Название юнита
					$artName = $art->getLocalizedName();
					// Получам текущий торговый курс
					$currentCourse = (float)TradeCourseRecountSearch::CurrentCourseForArt($id_art, $worldID);
					if (empty($currentCourse)) {
						echo "<font color='red'>На артефакт <b>$artName</b> не установлен курс <<<<<<<<<< ERROR</font><br>";
					} else {
						// Пересчитываем курс
						self::_recountCourse($worldID, $tradesData, 3, $id_art, $artName, $currentCourse, $weight_nano);
					}

				}
				echo "<br>End recount courses for [ARTEFACTS]<br>
				________________________________<br><br>";
				flush();
			
                // Обновляем дату последнего пересчета курсов
                //TradeCourseRecountSearch::InsertRecountDate($worldID);

                // Заканчиваем транзакцию
                TradeCourseRecountSearch::$common->closeTrans('COMMIT');
                //TradeCourseRecountSearch::$common->closeTrans('ROLLBACK');
                
                // Логируем
				actionLog( "Recount course in World '".$worldName);
				
				echo "<br><br>";
		    }

		    echo "<br>";
            flush();
			echo "END RECOUNT COURCES<br>";
		    
		    // echo SU_DebugTools::GetInternalDate()." - RECOUNT COURSES ENEDED<br>";
		   	// echo "</font>";
           	flush();
	}
	
	//static private function _recountCourse($worldID, $tradesData, GB_Date $startDate, $insertMetodName, $outName, $idFromCategory, $idFromArticle, $reaName)
	static private function _recountCourse($worldID, $tradesData, $idFromCategory, $idFromArticle, $reaName, $currentCourse, $weight_nano)
	{
/*echo "<pre>";
print_r($tradesData);
exit;*/
		/*assert(is_int($worldID) && $worldID > 0);
		assert(is_array($tradesData));
		assert(is_int($idFromCategory) && $idFromCategory > 0);
		assert(is_int($idFromArticle) && $idFromArticle > 0);
		assert(is_string($reaName) && !empty($reaName));
		assert(is_float($currentCourse) && $currentCourse > 0);
		assert(is_float($weight_nano) || is_int($weight_nano));
		assert($weight_nano > 0);

		echo "$reaName ";
		flush();
		
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
echo "--------------------------------------------<br>";*/

		// Проходимся по каждому торгу
		/*foreach ($tradesData as $key => $data)
		{
			if ($data['delta'] == 'h') {
				// Увеличиваем вес продаж выше среднего курса
				$totalWeightH += $data['from_ammount'];
			} elseif ($data['delta'] == 'l') {
				$totalWeightL += $data['from_ammount'];
			}

			$countTrades ++;
		}

/*echo "<br><br>Количество продаж: ".$countTrades."<br>";

echo "Вес продаж выше среднего курса ".$totalWeightH."<br>";
echo "Вес продаж ниже среднего курса ".$totalWeightL."<br>";

$pl = '(';
$pl2 = '(';
$pStr = '';
$rStr = '';*/

		// Проходимся по каждому торгу
		/*foreach ($tradesData as $key => $data)
		{
			if ($data['delta'] == 'h') {
				// Считаем P
				$P += $data['from_ammount']*$data['kv'];
//$pStr .= $pl.$data['from_ammount']."*".$data['kv'];
//$pl = '+';				
			} elseif ($data['delta'] == 'l') {
				$R += $data['from_ammount']*$data['kn'];
//$rStr .= $pl2.$data['from_ammount']."*".$data['kn'];
//$pl2 = '+';
			}
		}
//$pStr = $pStr.")/$totalWeightH";
//$rStr = $rStr.")/$totalWeightL";



		if ($P != 0) {
			$P = $P/$totalWeightH;
		}

		if ($R != 0) {
			$R = $R/$totalWeightL;
		}

/*echo "P = ".$pStr." = ".$P."<br>";
echo "R = ".$rStr." = ".$R."<br>";

echo "Текущий курс: ".$currentCourse."<br>";
echo "Вес в нане: ".$weight_nano."<br>";*/

		/*// Высчитаваем стартовый и начальный курсы
		// Если это нана
		if ($idFromCategory == 1 && $idFromArticle == 2) {
			$sratCourse = 1;
			$primaryCourse = 1;
		} else {
			// Если это ресурс
			if ($idFromCategory == 1) {
				$primaryCourse = $weight_nano;
				//$sratCourse = $primaryCourse*20;
			} else {
				$primaryCourse = $weight_nano;
				//$sratCourse = ($weight_nano + $weight_nano*0.2)*20;
			}
		}

		$recountMethod = 99;
//echo "primaryCourse: ".$primaryCourse."<br>";

		// Если с момента последнего изменения курса этот товар ни разу не купили или купили по курсу
		if ($P == $R) {
			// Если продажи присутствуют, то считаем по формуле $P = $R
			if ($countTrades > 0) {
				$recountMethod = 1; // $P = $R
//echo "recountMethod = 1<br>";
			} else {
				// Если продаж нет
				// Сравниваем текущий курс с начальным
				if ($currentCourse > $primaryCourse) {
					// И курс больше начального курса, считаем по формуле $R == 0
					$recountMethod = 2; // R = 0
//echo "recountMethod = 2<br>";
				} elseif ($currentCourse < $primaryCourse) {
					// И курс меньше начального курса, считаем по формуле $P == 0
					$recountMethod = 3; // P = 0
//echo "recountMethod = 3<br>";
				} else {
					// Если стартовый курс равен текущему
					$recountMethod = 1; // $P = $R
//echo "recountMethod = 1<br>";
				}
			}
		}

/*if ($P == $R || $recountMethod == 1) {
echo "1<br>";
} elseif ($P == 0 || $recountMethod == 3) {
echo "2<br>";
		}  elseif ($R == 0 || $recountMethod == 2) {
echo "3<br>";
	} elseif ($P > $R) {
echo "4<br>";
		} elseif ($R > $P) {
echo "5<br>";
		} else {
echo "6<br>";
		}*/
		
		
		
		/*if ($recountMethod == 1) {
			$course = $currentCourse;
			$C = 0;
		} elseif ($P == 0 || $recountMethod == 3) {
			// Если с момента последнего изменения курса товар за цену выше среднего курса ни разу не купили
			//$comment = 'C момента последнего изменения курса товар за цену выше среднего курса ни разу не купили';
			$C = self::$k*sqrt(2)*exp((2*self::$t*$countTrades)/self::$perionLength);
		}  elseif ($R == 0 || $recountMethod == 2) {
			// Если с момента последнего изменения курса товар за цену ниже среднего курса ни разу не купили
			//$comment = 'C момента последнего изменения курса товар за цену ниже среднего курса ни разу не купили';
			$C = -self::$k*sqrt(2)*exp((2*self::$t*$countTrades)/self::$perionLength);
		} elseif ($P > $R) {
			//$comment = 'Покупок по курсу больше текущего было больше покупок по курсу меньше текущего';
			$C = self::$k*($P/$R)*exp((2*self::$t*$countTrades)/self::$perionLength);
//echo "$C = ".self::$k."*(".$P."/".$R."*exp(2*".self::$t."*".$countTrades.")/".self::$perionLength.")<br>";

		} elseif ($R > $P) {
			//$comment = 'Покупок по курсу меньше текущего было больше покупок по курсу больше текущего';
			$C = -self::$k*($P/$R)*exp((2*self::$t*$countTrades)/self::$perionLength);
//echo "C = -".self::$k."*(".$P."/".$R."*exp(2*".self::$t."*".$countTrades.")/".self::$perionLength.") = $C<br>";
		} else {
			echo "Error!";
		}
//echo "Дельта курса ".$C."<br>";

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
		/*$course = $currentCourse + $C;
		assert($course > 0);
		
		
		//$goods = self::_getGoodsName($idFromCategory, $idFromArticle);

		// Курс на нанку
		$id_tradecategory = 1;
		$id_article = 2;
		
		// Меняем курс
		self::_ChangeCourse($idFromCategory, $idFromArticle, $id_tradecategory, $id_article, (float)$course, $worldID);
		
		echo ": ".$course." [".$currentCourse."]<br> ";
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
				TradeCourseRecountSearch::InsertResourceCourse($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource);
				break;

			// Юниты
			case 2:
				TradeCourseRecountSearch::InsertUnitsCourse($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource);
				break;

			// Арты
			case 3:
				TradeCourseRecountSearch::InsertArtefactCourse($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource);
				break;

			default:
				echo "Wrong Data!<br>";
				exit;
		}
	}
	
	static private function _getGoodsName($categoryID, $articleID)
	{
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		
		switch ($categoryID)
		{
			// Ресы
			case 1:
				$name = TradeCourseRecountSearch::GetResuorceName($articleID);
				break;

			// Юниты
			case 2:
				$name = TradeCourseRecountSearch::GetUnitName($articleID);
				break;

			// Арты
			case 3:
				$name = TradeCourseRecountSearch::GetArtefactName($articleID);
				break;
		}
		
		return $name;
	}
	
	
	static private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}

		if(key_exists('world', $data))
		{
			foreach ($data['world'] as $key => $woldData)
			{
				self::$worldData[] = $woldData;
				assert(!empty(self::$worldData));
			}
		}
	}
}
*/
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>