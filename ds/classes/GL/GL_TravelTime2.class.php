<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Классдля обсчета врмени необходимого для пуремещения из одной соты в другую
 */
class GL_TravelTime
{
	/**
	 * Enter description here...
	 *
	 * @var DE_WorldSettings
	 */
	static private $worldSettings;
	
	/**
	 * Время ходжа в секундах 
	 *
	 * @var integer
	 */
	static private $turnTime;
	
	/**
	 * Минимальное время пути между кольцами в секундах
	 *
	 * @var float
	 */
	static private $minRingTravelTime;
	
	/**
	 * Минимальное время пути в турнах
	 *
	 * @var float
	 */
	static private $minLocalTravelTime;
	
	/**
	 * Минимальное время полета в комплексе
	 *
	 * @var float
	 */
	static private $minComplexTravelTime;
	
	/**
	 * Максимальное время пути в секунлдах
	 *
	 * @var float
	 */
	static private $maxTravelTime;
	
	/**
	 * Минимальное время пути в секунлдах
	 *
	 * @var float
	 */
	static private $minTravelTime;
	
	/**
	 * Коофициент расчета времени полета по кольцу
	 *
	 * @var float
	 */	
	static private $trevelTimePower;	
	
	/**
	 * На сколько секунд время движение с мелсионом больше
	 *
	 * @var float
	 */ 
	static private $colonieRingTravelTimeDelta;

	/**
	 * Время начала вращения колец:	
	 * (timestamp) 23-09-2005 00:00:00
	 * 
	 * @var integer
	 */
	static public $timeStartRotation = 1127419200; // 
	
	/**
	 * Размер площадки в секундах при вращение (время в течение которого время движени между кольцами не меняеться)
	 *
	 * @var integer
	 */
    static public $timeLanding;
		
    /**
     * Флаг включения вращения
     *
     * @var boolean
     */
	static private $ringRotation;
	
	/**
	 * Время в секундах после которго начинает действовать ускорение 
	 *
	 * @var float
	 */
	static private $minAccelerationTime;
	
	/**
	 * здесь задается скорость вращения колец
	 * в секундах, за которые совершается полный оборот
	 * 
	 * Это массив с номерами кольц в качесвтве лючей массива.
	 *
	 * @var array
	 */
	//static private $ringsRotattionSpeed = array();


	// Параметры физической модели (трогать не рекомендуеться, особенно не смоделировав предварительно)
	// Количество секторов для колец
	static private $SectorPerRing = array(1 => 251, 2 => 262, 3 => 273, 4 => 840 /* = 280 * 3 */);
	
	static private $InterRingMaxTime;        // Максимальное время на перелет между обычными кольцами
	static private $InterRingTimeStep;       // Градационный шаг от минимального до максимального времени перелета
	static private $InterRingFirstStep;      //  но при этом первый шаг должен быть таким. (если меньше предыдущего параметра то не используется)
	// не нужна нам эта логика, особенно если ускоряется все по-старому
	static private $TravelSpeedupLogic = 1;  // Логика ускорения для миров без сближения: 0 - затрагивает межкольцевой перелет, 1 - только кольцевой отрезок пути	
	static private $RingRotationStep;        // Время "шага" вращения колец
	
	static private $combFrom;
	static private $combTo;
	static private $speed;
	static private $startDate;
	
	static private $WayOneDistance;
	static private $WayTwoDistance;
	
	static private $InterRingTravelTime;
	
	//static private $InterRingTravelTime;
	
	static private $SpeedupPart;
	static private $StablePart;
	
	static private function _init()
	{
		self::$worldSettings = DE_WorldSettings::CreateForCurentWorld();
			
		self::$turnTime = (int)ceil(self::$worldSettings->get('TURN_TIME') * 60);
		assert(self::$turnTime > 0);	
			
		self::$minRingTravelTime = (int)ceil(self::$worldSettings->get('MIN_RING_TRAVEL_TIME') * self::$turnTime);
		assert(self::$minRingTravelTime >= 0);
		
		self::$minLocalTravelTime = (float)self::$worldSettings->get('MIN_LOCAL_TRAVEL_TIME');
		assert(self::$minLocalTravelTime > 0);
		
		self::$minComplexTravelTime = (float)self::$worldSettings->get('MIN_COMPLEX_TRAVEL_TIME');
		assert(self::$minComplexTravelTime > 0);
		
		self::$maxTravelTime = (float)ceil(self::$worldSettings->get('MAX_TRAVEL_TIME') * self::$turnTime);
		assert(self::$maxTravelTime > 0);
		
		self::$minTravelTime = (float)ceil(self::$worldSettings->get('MIN_TRAVEL_TIME') * self::$turnTime);
		assert(self::$maxTravelTime >= 0);
		
		self::$trevelTimePower = (float)self::$worldSettings->get('TRAVELLING_TIME_POWER');
		assert(self::$trevelTimePower > 0 && self::$trevelTimePower < 1);
		
		self::$minAccelerationTime = (int)ceil(self::$worldSettings->get('MIN_SPEED_ACCELERATION_TIME_TURNS')  *  self::$turnTime);
		assert(self::$minAccelerationTime > 0);
		
		self::$ringRotation = (int)self::$worldSettings->get('RING_ROTATION');		
		
		self::$colonieRingTravelTimeDelta = (int)ceil(self::$worldSettings->get('COLONIE_RING_TRAVELTIME_DELTA')  *  self::$turnTime);
		assert(self::$colonieRingTravelTimeDelta >= 0);
		/*
		self::$ringsRotattionSpeed[1] = (int)ceil(self::$worldSettings->get('RING_ROTATION_SPEED_VORANIA')  *  self::$turnTime);	
		assert(self::$ringsRotattionSpeed[1] > 0);
		
		self::$ringsRotattionSpeed[2] = (int)ceil(self::$worldSettings->get('RING_ROTATION_SPEED_LIENSORD')  *  self::$turnTime);		
		assert(self::$ringsRotattionSpeed[2] > 0);
		
		self::$ringsRotattionSpeed[3] = (int)ceil(self::$worldSettings->get('RING_ROTATION_SPEED_PSALION')  *  self::$turnTime);		
		assert(self::$ringsRotattionSpeed[3] > 0);
		
		self::$ringsRotattionSpeed[4] = (int)ceil(self::$worldSettings->get('RING_ROTATION_SPEED_MELSION')  *  self::$turnTime);
		assert(self::$ringsRotattionSpeed[4] > 0);
		*/
		self::$timeLanding = (int)ceil(self::$worldSettings->get('RING_ROTATION_LANDING_TIME') * self::$turnTime);
		assert(self::$timeLanding > 0);		

		// Время шага вращения колец.
		self::$RingRotationStep = self::$timeLanding;
		assert(self::$RingRotationStep > 0);
		
		// Какие-то левые параметры, пока без модифаеров
		self::$InterRingMaxTime = self::$minRingTravelTime + 7 * self::$turnTime;
		self::$InterRingTimeStep = 0.5 * self::$turnTime;
		self::$InterRingFirstStep = self::$turnTime;
	}
    
	public static function Count (DE_Comb $combFrom, DE_Comb $combTo, GB_MoveSpeed $speed, $startDate = null)
	{		
		assert(is_null($startDate) || $startDate instanceof GB_Date);
		
		if(is_null($startDate))
		{
			$startDate = GB_TypeFactory::CreateBase('GB_Date', time());
		}
		
		self::_init();		

		if($combTo->getID() == $combFrom->getID())
		{
			ds_error('Cant count travel with zero path. ('.$combFrom->getID().', '.$combTo->getID().')');
		}

		//Путиществие межкольцевое или нет
		if($combFrom->getRingNumber() !=  $combTo->getRingNumber())
		{
			$ringtravel = self::$minRingTravelTime;
		} else {
			$ringtravel = 0;
		}

		if($ringtravel != 0 && self::$ringRotation)
		{
			$traveltime = self::_countRotation($combFrom, $combTo, $startDate);
		} else {
			$traveltime = self::_countStatic($combFrom, $combTo);
		}
		assert($traveltime > 0);
		
		
		if($traveltime > self::$maxTravelTime)
		{
			$traveltime = self::$maxTravelTime;
		}
		
		if($traveltime < self::$minTravelTime && ($combTo->getComplexNumber() != $combFrom->getComplexNumber() || $ringtravel != 0))
		{
			$traveltime = self::$minTravelTime;
		}
		
		if($speed->value <= 1)
		{
			$traveltime = $traveltime + $traveltime * (1 - $speed->value);			
		} else {
			if($traveltime > self::$minAccelerationTime)
			{
				$traveltime = self::$minAccelerationTime  + ($traveltime - self::$minAccelerationTime) / $speed->value;				
			}
		}
		
		$traveltime = ceil($traveltime);
		assert($traveltime > 0);

		return GB_TypeFactory::CreateConstant('GB_TimePeriod', $traveltime);
	}
	
	
	/**
	 * Расчет пути для статичекого мира
	 *
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @return unknown
	 */
	static private function _countStatic(DE_Comb $combFrom, DE_Comb $combTo)
	{
		//Путиществие межкольцевое или нет
		if($combFrom->getRingNumber() !=  $combTo->getRingNumber())
		{
			$ringtravel = self::$minRingTravelTime;
		}else{
			$ringtravel = 0;
		}
		
		//Определяем количество комплексов на целевон кольце
		$complexCount = max (
			$combTo->getCombRing()->getComplexCount(),
			$combFrom->getCombRing()->getComplexCount()
		);

		assert($complexCount >= $combTo->getComplexNumber());
		assert($complexCount >= $combFrom->getComplexNumber());

		//Вsичляем варинты прямого и инверснго маршрута
		if($combTo->getComplexNumber() > $combFrom->getComplexNumber())
		{
			$directWay = intval($combTo->getComplexNumber() - $combFrom->getComplexNumber());
			$inversWay = intval(($complexCount - $combTo->getComplexNumber()) + $combFrom->getComplexNumber());
		} else {
			$directWay = intval($combFrom->getComplexNumber() - $combTo->getComplexNumber());
			$inversWay = intval(($complexCount - $combFrom->getComplexNumber()) + $combTo->getComplexNumber());
		}

		assert($directWay >= 0);
		assert($inversWay >= 0);

		//Выбираем оптимальны маршрут
		if($directWay > $inversWay)
		{
			$way = $inversWay;
		} elseif ($directWay < $inversWay) {
			$way = $directWay;
		} else {
			$way = $directWay;
		}

		
		if($way == 0 && $combFrom->getRingNumber() ==  $combTo->getRingNumber())
		{
			$minTime = self::$minComplexTravelTime * self::$turnTime;
		} else {
			$minTime = self::$minLocalTravelTime * self::$turnTime;
		}
		
		//Учитываем время пролета одной соты
		$traveltime = self::getRingTravelTime($way) + $ringtravel + $minTime;
		assert($traveltime > 0);
		
		return $traveltime;
	}
		
	/**
	 * функция возвращает время внутрикольцевого перелёта
	 *
	 * @param unknown_type $way
	 * @return unknown
	 */
	static private function getRingTravelTime($way)
	{
		$travelTime = (int)ceil((pow($way , self::$trevelTimePower) * self::$turnTime));
		assert($travelTime >= 0);

		return $travelTime;
	}
	
	
	static public function _countRotation(DE_Comb $combFrom, DE_Comb $combTo, GB_Date $startDate)
	{
		self::$combFrom = $combFrom;
		self::$combTo = $combTo;
		self::$speed = $speed;
		self::$startDate = $startDate;
		self::$WayOneDistance = 0;
		self::$WayTwoDistance = 0;
		self::$InterRingTravelTime = 0;
		self::$SpeedupPart = 0;
		self::$StablePart = 0;
		
		/**
		 *   !!!  Отсюда начинается расчет  !!!
		 */
		
		// расчет времени межкольцевого прелета
		self::_countWayOneAndTwo();

		// расчет ускорения
		self::_countTravelTimeParts();
		
		// итоговое время полета
		self::$SpeedupPart *= (2 - $speed->value);                    // Накладываем ускорение на нужный отрезок
		$TotalTravelTime = round(self::$StablePart + self::$SpeedupPart);        // Суммируем все отрезки и возращаем результат

		// возвращаем результат
		assert($TotalTravelTime > 0);
		//return GB_TypeFactory::CreateConstant('GB_TimePeriod', $TotalTravelTime);
		return $TotalTravelTime;
	}
	
	
	static public function _countWayOneAndTwo()
	{
		// Номер стартового кольца
		$SrcRing = self::$combFrom->getRingNumber();
		// Номер стартового коплекса
		$SrcComplex =  self::$combFrom->getComplexNumber();
		// Кол-во компдексов на стартовом кольце
		$SrcComplexPerRing =  self::$combFrom->getCombRing()->getComplexCount();
		
		// Кольцо назначения
		$DstRing = self::$combTo->getRingNumber();
		$DstComplex = self::$combTo->getComplexNumber();
		$DstComplexPerRing = self::$combTo->getCombRing()->getComplexCount();
		
		// Скорость полета 0.1 - 1.5
		$TravelTimeSpeedup = self::$speed->value;
		// Время вылета
		$StartTime = self::$startDate->value;

		
		self::$WayOneDistance = abs($SrcComplex - $DstComplex);
		self::$WayTwoDistance = max($SrcComplexPerRing, $DstComplexPerRing) - $WayOneDistance;

		// Перемещение внутри кольца не интересуют, выходим
		if ($SrcRing == $DstRing)
		{
			return;                 
		}

		/*
		// Для 4 кольца время мин. полета различается
		if($SrcRing == 4 || $DstRing == 4)
		{
			self::$InterRingTravelTime = self::$minRingTravelTime;
		} else {
			self::$InterRingTravelTime = self::$minRingTravelTime;
		}
		*/
		
		 // Вращения нет (межкольцеыое перемещение в статическом мире), выходим
		if(self::$ringRotation == 0)
		{
			return;
		}

		// Нормализуем комплексы, чтобы считались от нуля
		$SrcComplex--;
		$DstComplex--;                    

		// Перемещение с Мельсиона
		if ($SrcRing == 4)
		{
			// Обменяем точку старта и финиша, сведя к перемещению на Мельсион
			$tmp = $SrcRing;
			$SrcRing = $DstRing;
			$DstRing = $tmp;

			$tmp = $SrcComplex;
			$SrcComplex = $DstComplex;
			$DstComplex = $tmp;

			$tmp = $SrcComplexPerRing;
			$SrcComplexPerRing = $DstComplexPerRing;
			$DstComplexPerRing = $tmp;
		}

		// Фаза 1 - Выясняем соприкасающиеся сектора колец
		
		// Текущий временной "шаг"
		$CurrentStep = floor((self::$startDate->value - self::$timeStartRotation) / self::$RingRotationStep); 
		
		// Перемещение на Mельсион
		if ($DstRing == 4) 
		{
			// Две точки соприкосновения Мельсиона
				// $var1 % $var2 = остаток от деления $var1 на $var2
				// self::$SectorPerRing[$DstRing] - кол-во секторов на кольце
			$DstRingCommonOne = (self::$SectorPerRing[$DstRing] * ($SrcRing - 1) / 6 + $CurrentStep) % self::$SectorPerRing[$DstRing];
			$DstRingCommonTwo = (self::$SectorPerRing[$DstRing] * ($SrcRing + 2) / 6 + $CurrentStep) % self::$SectorPerRing[$DstRing];
			// и исходного кольца
			$SrcRingCommonOne = ((self::$SectorPerRing[$SrcRing] * 1)/ 4 + $CurrentStep) % self::$SectorPerRing[$SrcRing];
			$SrcRingCommonTwo = ((self::$SectorPerRing[$SrcRing] * 3)/ 4 + $CurrentStep) % self::$SectorPerRing[$SrcRing];
		} else {// перемещение между обычными кольцами
			// Две точки соприкосновения кольца назначения
			$DstRingCommonOne = $CurrentStep % self::$SectorPerRing[$DstRing];
			$DstRingCommonTwo = ($CurrentStep + self::$SectorPerRing[$DstRing]/2) % self::$SectorPerRing[$DstRing];
			// и исходного кольца
			$SrcRingCommonOne = $CurrentStep % self::$SectorPerRing[$SrcRing];
			$SrcRingCommonTwo = ($CurrentStep + self::$SectorPerRing[$SrcRing]/2) % self::$SectorPerRing[$SrcRing];
		}
		
		// Выясняем соприкасающеся комплексы
		// кольца назначения
		// расчет идет во вспомогательной функции _calcCommon(), которая возвращает массив с нужными значениями
		$commons = self::_calcCommon($DstRing, $DstComplexPerRing, $DstRingCommonOne, $DstRingCommonTwo);
		$DstRingCommonOneFirst = $commons['RingCommonOneFirst'];
		$DstRingCommonOneLast = $commons['RingCommonOneLast'];
		$DstRingCommonTwoFirst = $commons['RingCommonTwoFirst'];
		$DstRingCommonTwoLast = $commons['RingCommonTwoLast'];
		
		// исходного кольца	
		$commons = self::_calcCommon($SrcRing, $SrcComplexPerRing, $SrcRingCommonOne, $SrcRingCommonTwo);
		$SrcRingCommonOneFirst = $commons['RingCommonOneFirst'];
		$SrcRingCommonOneLast = $commons['RingCommonOneLast'];
		$SrcRingCommonTwoFirst = $commons['RingCommonTwoFirst'];
		$SrcRingCommonTwoLast = $commons['RingCommonTwoLast'];
		
		// Нормируем координаты исходного и целевого комплекса
		$DstComplex = ($DstComplex + ($DstComplexPerRing - $DstRingCommonOneFirst)) % $DstComplexPerRing;
		$SrcComplex = ($SrcComplex + ($SrcComplexPerRing - $SrcRingCommonOneFirst)) % $SrcComplexPerRing;

		// Фаза 2 - Обрабатываем варианты перемещения если какой-либо комплекс находится в точке соприкосновения
		self::$WayOneDistance = self::$WayTwoDistance = 0;
		
		// Перемещение на Mельсион
		if($DstRing == 4)
		{ 
			// вычисляем расстояние от старта до точки пересечения
			if ($SrcComplex >= $SrcRingCommonTwoFirst) {    // Исходный комплекс в старшем полукруге
				self::$WayOneDistance = $SrcComplexPerRing - $SrcComplex;
				if($SrcComplex > $SrcRingCommonTwoLast)
				{
					self::$WayTwoDistance = $SrcComplex - $SrcRingCommonTwoLast;
				}
			} else {                                        // исходный комплекс в младшем полукруге
				if($SrcComplex > $SrcRingCommonOneLast)
				{
					self::$WayOneDistance = $SrcComplex - $SrcRingCommonOneLast;
				}
				self::$WayTwoDistance = $SrcRingCommonTwoFirst - $SrcComplex;
			}
			
			// добавляем расстояние от точки пересечения до финиша
			if ($DstComplex >= $DstRingCommonTwoFirst) 
			{   // Целевой комплекс в старшем полукруге
				self::$WayOneDistance += $DstComplexPerRing - $DstComplex;
				if ($DstComplex > $DstRingCommonTwoLast) 
				{
					self::$WayTwoDistance += $DstComplex-$DstRingCommonTwoLast;
				}
			} else { 
				// целевой комплекс в младшем полукруге
				if ($DstComplex > $DstRingCommonOneLast)
				{
					self::$WayOneDistance += $DstComplex-$DstRingCommonOneLast;
				}
				self::$WayTwoDistance += $DstRingCommonTwoFirst-$DstComplex;
			}
			
			// Прибавляем добавочное время, если требуется
			if(min(self::$WayOneDistance, self::$WayTwoDistance) && (self::$ringRotation == 1))
			{
				self::$minRingTravelTime += self::$colonieRingTravelTimeDelta;
			}
			
			return; // Обработка Мельсиона закончена
		}
		
		// Обработка перемещения между обычными кольцами, Проверка стартовой точки
		if ($SrcComplex <= $SrcRingCommonOneLast) 
		{ // Исходный комплекс находится в первом пересечении колец
			if ($DstComplex > $DstRingCommonOneLast) 
			{ // Комплекс назначения вне зоны перехода
				self::$WayOneDistance = $DstComplexPerRing - $DstComplex;
				self::$WayTwoDistance = $DstComplex - $DstRingCommonOneLast;
			}
			
			return;
		}
		
		if(($SrcComplex >= $SrcRingCommonTwoFirst) && ($SrcComplex <= $SrcRingCommonTwoLast)) 
		{ // Исходный комплекс находится во втором пересечении колец
			if ($DstComplex > $DstRingCommonTwoLast)
			{
				self::$WayOneDistance = $DstComplex - $DstRingCommonTwoLast;   // Комплекс назначения после зоны перехода
			}
			if ($DstComplex < $DstRingCommonTwoFirst)
			{
				self::$WayOneDistance = $DstRingCommonTwoFirst - $DstComplex; // Комплекс назначения до зоны перехода
			}
			
			self::$WayTwoDistance = self::$WayOneDistance;         // Возможен только  один путь
			
			return;
		}
		
		// Проверка финишной точки
		if ($DstComplex <= $DstRingCommonOneLast) 
		{ // Комплекс назначения в первом пересечении колец, исходный комплекс вне зоны перехода
			self::$WayOneDistance = $SrcComplexPerRing - $SrcComplex;
			self::$WayTwoDistance = $SrcComplex - $SrcRingCommonOneLast;
			
			return;
		}

		// Комплекс назначения находится во втором пересечении колец
		if (($DstComplex >= $DstRingCommonTwoFirst) && ($DstComplex <= $DstRingCommonTwoLast))
		{
			if($SrcComplex > $SrcRingCommonTwoLast)
			{
				// Исходный комплекс после зоны перехода
				self::$WayOneDistance = $SrcComplex - $SrcRingCommonTwoLast;
			}

			if($SrcComplex < $SrcRingCommonTwoFirst)
			{
				// Исходный комплекс до зоны перехода
				self::$WayOneDistance = $SrcRingCommonTwoFirst - $SrcComplex;
			}

			// Возможен только  один путь
			self::$WayTwoDistance = self::$WayOneDistance;
			return;
		}

		// Комплексы не попали в точки пересечения - нужен перелет
		if(self::$ringRotation == 1)  // Мир со сближением колец
		{
			// Вычисляем расстояние между кольцами в точке перемещения
			$tmp = floor($SrcComplexPerRing / 2);         // поправка на округление при нечетных длинах колец

			// позиция удаленности точки перемещения от ближайшей точки касания
			if ($SrcComplex >= $tmp)
			{
				$RingPosition = $SrcComplex - $tmp;
				$RingLength = $SrcComplexPerRing - $tmp;
			} else {
				$RingPosition = $SrcComplex;
				$RingLength = $tmp;
			}

			// Расчет и округления времени полета между кольцами
			$tmp = self::$minRingTravelTime;
			// учтем задержку первого шага, если она есть
			if(self::$InterRingTimeStep < self::$InterRingFirstStep)
			{
				$tmp += (self::$InterRingFirstStep - self::$InterRingTimeStep);
			}

			// Собственно  расчет времени
			self::$minRingTravelTime = ceil(abs((self::$InterRingMaxTime - $tmp) * sin(pi() * $RingPosition / $RingLength)));

			$countSteps = self::$minRingTravelTime % self::$InterRingTimeStep;
			// Подгонка результата  под градационную шкалу
			if($countSteps)
			{
				self::$minRingTravelTime = self::$minRingTravelTime - $countSteps + self::$InterRingTimeStep;
			}

			self::$minRingTravelTime += $tmp;
		}
		
		// Вычисляем длину локального участка
		$SrcComplex = floor(($SrcComplex * $DstComplexPerRing) / $SrcComplexPerRing); // Правим источник

		// и вычисляем два возможных пути
		self::$WayOneDistance = abs($DstComplex - $SrcComplex);
		self::$WayTwoDistance = $DstComplexPerRing - self::$WayOneDistance;
	}

	
	/**
	 * Расчет соприкасающихся комплексов
	 */
	static private function _calcCommon($Ring, $ComplexPerRing, $RingCommonOne, $RingCommonTwo)
	{	
		assert(is_int($Ring) && $Ring > 0);
		assert(is_int($ComplexPerRing) && $ComplexPerRing > 0);
		assert(is_numeric($RingCommonOne));
		assert(is_numeric($RingCommonTwo));
		
		$CurrentStep = floor((self::$startDate->value - self::$timeStartRotation) / self::$RingRotationStep); // Подготовим временную систему

		if(self::$SectorPerRing[$Ring] >= $ComplexPerRing)
		{
			// var - промежуточные переменные для более компактной записи
			$var1 = ($ComplexPerRing - 1) * ($CurrentStep / self::$SectorPerRing[$Ring]) % 2;
		} else {
			$var1 = - self::$SectorPerRing[$Ring] / 2;
		}
		
		$RingCommonOneFirst = ($ComplexPerRing * $RingCommonOne + $var1) / self::$SectorPerRing[$Ring];

		$RingCommonTwoFirst = $RingCommonOneFirst + ($ComplexPerRing - floor($RingCommonOneFirst)) % $ComplexPerRing;

		// итоговое значение $RingCommonOneFirst - ближайшее целое число, меньшее $RingCommonOneFirst
		$RingCommonOneFirst = floor($RingCommonOneFirst);

		$var3 = self::$SectorPerRing[$Ring] - 1 + $ComplexPerRing - $RingCommonOneFirst;
	
		if(self::$SectorPerRing[$Ring] >= $ComplexPerRing)
		{
			$var2 = $RingCommonOneFirst;
			$var4 = $RingCommonTwoFirst;
		} else {
			$var2 = ($ComplexPerRing * ($RingCommonOne + 1) + self::$SectorPerRing[$Ring] / 2);
			$var4 = ($ComplexPerRing * ($RingCommonTwo + 1) + self::$SectorPerRing[$Ring] / 2) / $var3;
		}

		$RingCommonOneLast = ($var2 / $var3) % $ComplexPerRing;

		$RingCommonTwoLast = ($var4) % $ComplexPerRing;
		
		$res = array(
			'RingCommonOneFirst' => $RingCommonOneFirst,
			'RingCommonOneLast' => $RingCommonOneLast,
			'RingCommonTwoFirst' => $RingCommonTwoFirst,
			'RingCommonTwoLast' => $RingCommonTwoLast,
		);
		
		return $res;
	}


	/**
	 * Расчет ускорения
	 *
	 */
	static public function _countTravelTimeParts()
	{
		// Начальные параметры
		$SrcRing = self::$combFrom->getRingNumber();
		$SrcComplex =  self::$combFrom->getComplexNumber();
		$SrcComplexPerRing =  self::$combFrom->getCombRing()->getComplexCount();
		
		$DstRing = self::$combTo->getRingNumber();
		$DstComplex = self::$combTo->getComplexNumber();
		$DstComplexPerRing = self::$combTo->getCombRing()->getComplexCount();

		// часть пути, на которую не влияет ускорение
		self::$StablePart = 0;
		// часть с ускорением
		self::$SpeedupPart = (float)pow(min(self::$WayOneDistance, self::$WayTwoDistance), self::$trevelTimePower) * self::$turnTime;

		// Динамический мир со сближением
		if (self::$ringRotation == 1) 
		{                         
			self::$StablePart += self::$minRingTravelTime;               // мехкольцевое время не трогаем полюбому
			if (self::$speed->value > 1) 
			{// Ускорение ?
				self::$StablePart += self::$minLocalTravelType;            // тогда не трогаем и время подотовки
				if (self::$StablePart >= self::$minAccelerationTime)
				{
					return;          // если "то что нетрогаем" больше лимитера идем накладывать ускорение на то что остаось
				}
				
				if ((self::$StablePart + self::$SpeedupPart) <= self::$minAccelerationTime) 
				{ // Общее время меньше лимитера ?
					self::$StablePart += self::$SpeedupPart;               // тогда нифига не ускоряем
					self::$SpeedupPart = 0;
					return;
				}
				
				$tmp = self::$minAccelerationTime - self::$StablePart;      // иначе ускоряем все что больше лимита
				
				self::$StablePart += $tmp;
				self::$SpeedupPart -= $tmp;
				
				return;
			}
			
			self::$SpeedupPart += self::$minLocalTravelTime;  // А при замедлении давим и время подготовки
			return;
		}
		
		// Статический или динамический мир без сближения
		// в зависимости от логики  затрагиваем или нет межкольцевое время
		if(self::$TravelSpeedupLogic)
		{
			self::$StablePart += self::$minRingTravelTime;
		} else {
			self::$SpeedupPart += self::$minRingTravelTime;
		}
		
		self::$SpeedupPart += self::$minLocalTravelType;     // "Время подготовки" ускоряем "в силу традиций" :)
		
		if(self::$speed->value <= 1)
		{
			// Если замедление то делать больше нечего
			return; 
		}
		
		// если "то что нетрогаем" больше лимитера то делать больше нечего
		if(self::$StablePart >= self::$minAccelerationTime)
		{
			return;           
		}
		
		if((self::$StablePart + self::$SpeedupPart) <= self::$minAccelerationTime) 
		{  // Общее время меньше лимита ?
			self::$StablePart += self::$SpeedupPart;  // тогда ничего не ускоряем
			self::$SpeedupPart = 0;
			return;
		}
		
		$tmp = self::$minAccelerationTime - self::$StablePart;   // иначе ускоряем все что больше лимита
		
		self::$StablePart += $tmp;
		self::$SpeedupPart -= $tmp;
		
		return;
	}
	
}//END OF CLASS
?>