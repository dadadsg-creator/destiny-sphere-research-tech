<?php
//==============================================================================
class DE_RingSpeed
{
	private $ringNumber;
	private $ringRotationSpeed;

	function __construct($ringNumber)
	{
		assert(is_int($ringNumber) && $ringNumber > 0 && $ringNumber <=4);

		switch($ringNumber)
		{
			// здесь задается скорость вращения колец
            // в турнах, за которые совершается полный оборот
			// значения можно изменять
			case 1:
				$this->ringRotationSpeed = 500;
				break;
			case 2:
				$this->ringRotationSpeed = 500;
				break;
			case 3:
				$this->ringRotationSpeed = 500;
				break;
			case 4:
				$this->ringRotationSpeed =1100;
				break;
			default:
				assert(false);
		}
	}

	public function getRotationSpeed()
	{
		return $this->ringRotationSpeed;
	}
}


//===============================================

class DE_Ring
{
	private $ringNumber;

	private $complexOnRing;

	function __construct($ringNumber)
	{
		assert(is_int($ringNumber) && $ringNumber > 0 && $ringNumber <=4);

		switch($ringNumber)
		{
			// здесь задается количество комплексов на кольцах
			// значения можно изменять
			case 1:
				$this->complexOnRing = 500;
				break;
			case 2:
				$this->complexOnRing = 200;
				break;
			case 3:
				$this->complexOnRing = 100;
				break;
			case 4:
				$this->complexOnRing = 1100;
				break;
			default:
				assert(false);
		}
	}

	public function getComplexCount()
	{
		return $this->complexOnRing;
	}
}

/**
 * Объект даты. принимает время в виде таймстемпа
 *
 */
class GB_Date
{
	public $value;

	function __construct($time)
	{
		assert(is_int($time) && $time > 0);

		$this->value = $time;
	}
}

class DE_Comb
{
	private $combNumber;

	private $complexNumber;

	private $ringNumber;

	function __construct($ringNumber, $complexNumber, $combNumber)
	{
		assert(is_int($ringNumber) && $ringNumber > 0 && $ringNumber <=4);
		assert(is_int($complexNumber) && $complexNumber > 0);
		assert(is_int($combNumber) || $combNumber > 0 || $combNumber <= 6);

		$this->combNumber = $combNumber;

		$this->complexNumber = $complexNumber;

		$this->ringNumber = $ringNumber;
	}

	public function getRingNumber()
	{
		return $this->ringNumber;
	}

	public function getComplexNumber()
	{
		return $this->complexNumber;
	}

	public function getCombRing()
	{
		$ring = new DE_Ring($this->ringNumber);
		return $ring;
	}
}

class GL_TravelTime
{

	/*  Время игрового турна в секундах. */
	static public $turnTime;

	/* Минимальное время внутрикольцевого перелёта в турнах	*/
   	static private $minLocalTravelTime = 1;

	/* Коэффициент скорости внутрикольцевого перелёта */
 	static private $TravelTimePower = 0.4;

	/* Коэффициент, указывающий размерность максимального времени полёта на Мельсион
      (относительно обычного межкольцевого перелёта)
	*/
	static private $coeffForMelsion = 1;

	/* Начало вращения колец */
	static private $timeStartRotation =  1127419200; // (timestamp) 23-09-2005 00:00:00

    /* Минимальное время межкольцевого перелёта (в турнах) */
    static private $minRingToRingTime=2;

    /* Максимальное время межкольцевого перелёта (в турнах) */
    static private $maxRingToRingTime=9;

    static public  $timeLanding = 1;

	/* Функция возвращает время перелёта между заданными комплексами (в секундах) */
	static public function _count(DE_Comb $combFrom, DE_Comb $combTo, GB_Date $startDate)
	{
   		$SrcRing = 	$combFrom->getRingNumber();                          //Номер кольца старта
		$DstRing = 	$combTo->getRingNumber();                            //Номер кольца назначения
		$SrcComplexPerRing = $combFrom->getCombRing()->getComplexCount();//Количество комплексов на кольце старта
		$DstComplexPerRing = $combTo->getCombRing()->getComplexCount();  //Количество комплексов на кольце назначения
		$SrcComplex = $combFrom->getComplexNumber();                     // Номер комплекса старта
		$DstComplex = $combTo->getComplexNumber();                       // Номер комплекса назначения

				// Если перемещение внутри кольца, то больше считать нечего
		if($SrcRing == $DstRing)
		{
            $way = min(abs($SrcComplex - $DstComplex), $SrcComplexPerRing - abs($SrcComplex - $DstComplex));
			return floor( self::getInterRingTravelTime($way) );
		}
				// Если внутрикольцевого перелёта не произошло, то рассчитываем межкольцевой перелёт
				// Время начала перелёта в секундах. Если время старта не указано - берётся текущее время системы
		$timeStartFly = $startDate->value;
		if(empty($timeStartFly)) $timeStartFly = mktime();
				// Ошибка, если время старта 0 или ранее запуска вращения
		assert(($timeStartFly != 0) && ($timeStartFly >= self::$timeStartRotation));
            	// Полное время вращения колец с начала вращения
		$timeRotationTotal = $timeStartFly - self::$timeStartRotation ;

                // вычисляем "отражение" на зеркале исходного кольца
        $MirrorPoint = self::getMirrorPoint( $combFrom, $timeRotationTotal );
                // вычисляем комплекс перехода на кольце назначения
        $DstStartComplex = self::getDstRingComplex( $combTo, $timeRotationTotal, $MirrorPoint ) ;

                // вычисляем путь по кольцу назначения до комплекса назначения
        $way = min(abs($DstStartComplex - $DstComplex), $DstComplexPerRing - abs($DstStartComplex - $DstComplex));

            // Учитываем коэффициент для Мельсиона, если он учавствует в перелёте
        if ( $SrcRing == 4 || $DstRing == 4 ) $coeffMelsion = self::$coeffForMelsion;
        else  $coeffMelsion = 1 ;
                // вычисляем время полёта
        $timeTotalFly = ( self::$minRingToRingTime * self::$turnTime * $coeffMelsion  + self::getInterRingTravelTime($way) );

        return floor($timeTotalFly);

	}
         // функция возвращает время внутрикольцевого перелёта
	static private function getInterRingTravelTime($way)
	{
		return (( pow( $way , self::$TravelTimePower) + self::$minLocalTravelTime) * self::$turnTime );
	}

        // функция возвращает угол точки на "зеркале" для указанного комплекса.
    static private function getMirrorPoint( DE_Comb $combFrom, $timeRotationTotal )
    {
		$SrcRing = 	$combFrom->getRingNumber();                         //Номер кольца старта
		$SrcComplex = $combFrom->getComplexNumber();                     // Номер комплекса старта
        $SrcRingSpeed = new DE_RingSpeed($SrcRing);
        $SrcRotationSpeed = $SrcRingSpeed->getRotationSpeed();            // Скорость вращения кольца старта
		$SrcComplexPerRing = $combFrom->getCombRing()->getComplexCount(); //Количество комплексов на кольце старта
        		// Время, за которое кольцо совершает оборот
		$timeSrcRotationCicle = $SrcRotationSpeed * self::$turnTime;
    			// время, прошедшее с начала текущего цикла вращения для кольца
    	$SrcRotationDelta = $timeRotationTotal - $timeSrcRotationCicle * floor( $timeRotationTotal/$timeSrcRotationCicle);

                // учитываем размер "площадок"
        $SrcRotationDelta = self::$timeLanding * floor ($SrcRotationDelta/self::$timeLanding);

                // вычисляем угол поворота кольца от начала вращения
        $RotationAngle = $SrcRotationDelta / $timeSrcRotationCicle * 360;
                // вычисляем угол для комплекса старта
        $ComplexAngle = ($SrcComplex - 1)* 360 / $SrcComplexPerRing;
        $RotationAngle = $RotationAngle + $ComplexAngle;
        if ( $RotationAngle > 360 ) $RotationAngle = $RotationAngle - 360; // ???
                // Вычисляем зеркальную точку и возвращаем её. (в градусах от начала кольца)
                // подразумевается, что скорости вращения кольца и зеркала одинаковы и противоположно направлены.
        $MirrorPoint = 360 - 2 * $RotationAngle;
        if ($MirrorPoint < 0 ) $MirrorPoint = 360 + $MirrorPoint;
        if ($MirrorPoint == 360) $MirrorPoint = 0;
        return $MirrorPoint;
    }

        // функция возвращает угол точки на реальном кольце для указанной точки на "зеркале".
    static private function getDstRingComplex( DE_Comb $combTo, $timeRotationTotal, $MirrorPoint )
    {
		$Ring =  $combTo->getRingNumber();                         //Номер кольца старта
        $RingSpeed = new DE_RingSpeed($Ring);
        $RotationSpeed = $RingSpeed->getRotationSpeed();            // Скорость вращения кольца старта
		$ComplexPerRing = $combTo->getCombRing()->getComplexCount(); //Количество комплексов на кольце старта
          		// Время, за которое кольцо совершает оборот
		$timeRotationCicle = $RotationSpeed * self::$turnTime;
    			// время, прошедшее с начала текущего цикла вращения для кольца
    	$RotationDelta = $timeRotationTotal - $timeRotationCicle * floor( $timeRotationTotal/$timeRotationCicle);

                // учитываем размер "площадок"
        $RotationDelta = self::$timeLanding * floor ($RotationDelta/self::$timeLanding);


                // вычисляем угол поворота кольца от начала вращения
        $RotationAngle = $RotationDelta / $timeRotationCicle * 360;
        $RingPoint =   $RotationAngle - $MirrorPoint;
        if ($RingPoint <= 0 ) $RingPoint = 360 + $RingPoint;
        if ($RingPoint >= 360 )  $RingPoint = $RingPoint - 360;
        $DstComplex = 1 + floor(  $RingPoint / ( 360 / $ComplexPerRing ) );
        return $DstComplex;
    }
}
?>
