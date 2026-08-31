<?php

// Класс для обсчета врмени необходимого для пуремещения (TM) A.Fedchin

// Но если эта машинка не сработает, тогда уж вы с нами пуреместитесь, куда мы вас пуреместим (R) MC

class GL_TravelTime { 

/*

Traveling Engine v2.17 (C) Кодышь, уси права сохранены и магически запечатаны.
Попытка модификации и выдавания за своё будет караться проклятьем вплодь до 7ого колена.

*/

// Блок модифаеров (все что связано с временем указывается в секундах)
static private $WorldTravelType;         // Тип системы перемещения для мира: 0 -  без вращения, 2 - вращение без сближения, 1 вращение со сближением
static private $GameTurnTime;            // Время "одного хода" игры
static private $LocalTravelMinTime;      // Время "предполетной подготовки"  (минимально допустимое время для любого полета)
static private $InterRingMinTime;        // Минимальное время на перелет между обычными кольцами
static private $InterRingMaxTime;        // Максимальное время на перелет между обычными кольцами
static private $InterRingTimeStep;       // Градационный шаг от минимального до максимального времени перелета
static private $InterRingFirstStep;      //  но при этом первый шаг должен быть таким. (если меньше предыдущего параметра то не используется)
static private $FourRingMinTime;         // Время межкольцевого перелета для мельсиона в точке касания
static private $FourRingAddonTime;       // Добавочное время перелета для мельсиона, используется если соты не в точке касания
static private $TravelTimePower;         // Коэфициент разгона
static private $TravelSpeedupLimit;      // Ускорение действует только после этого отрезка времени
static private $TravelSpeedupLogic;      // Логика ускорения для миров без сближения: 0 - затрагивает межкольцевой перелет, 1 - только кольцевой отрезок пути
static private $RingRotationStep;        // Время "шага" вращения колец
static private $StartRotationPoint = 1127419200; // И сказал бог - вертитесь. И все закрутилось (timestamp) 23-09-2005 00:00:00

/// Долбоебные модифаеры придуманные Дадой для скорейшего убийства игры и которые нахуй здесь не нужны
/// Здесь и в дальнейшем ненужные блоки будут комментированы тремя чертами ///, чтобы легче было найти и стереть

static private $TravelMinTimeLimit;        ///  Жесткий лимитер минимального времени полета
static private $TravelMaxTimeLimit;        ///  Жесткий лимитер максимального времени полета
static private $FixedLocalComplexTime;     ///  Дублирующая константа указывающая время полета внутри комплекса


// Переменные

static private $worldSettings;       // Убить апстену того [censored] кто решил использовать ООП в PHP, да еще к тому же на таком тяжеленном проекте
static private $isInitialised;       //  два раза, для верности

static private function Init() {
 self::$worldSettings = DE_WorldSettings::CreateForCurentWorld(); // Сжираем кусок памяти под копию класса и томозим систему
 //
 // Настройка модифаеров движка 
 //
 // Добавить в базу следущие модифаеры
 //
 //   Название                   значение по умолчанию  тип
 // MIN_COLONY_RING_TRAVEL_TIME         0               float
 // SPEED_ACCELERATION_LOGIC            1               integer
 // RING_ROTATION_STEP_IN_MINUTES       0               float
 // MAX_RING_TRAVEL_TIME                0               float
 // RING_TRAVEL_FIRST_INCREMENT_TIME    1               float
 // COLONIE_RING_TRAVELTIME_DELTA       1               float
 // RING_TRAVEL_TIME_STEP               0.5             float
 // 

 /// И персонально для Дады. Желательно чтобы их вообще не было в базе или в них всегда были нулевые значения
 ///
 /// MIN_TRAVEL_TIME                        0           float
 /// MAX_TRAVEL_TIME                        0           float
 /// MIN_COMPLEX_TRAVEL_TIME                0           float
 
 
 //
 // Если будет свербить желание "покапаться" то нужно его удовлетворять именно в этом блоке, на лазия в основную функцию

 while (true) {                 //Поехали
  // Считываем тип системы перемещения. Если модифаер в базе провафлили - устанавливаем 0 тип (без вращения)
  self::$WorldTravelType = intval(self::$worldSettings->get('RING_ROTATION')); 
  assert(!((self::$WorldTravelType < 0) || (self::$WorldTravelType > 2))); // Орем, если модифаер вне диапазона

  // Считываем время "хода игры" и преобразуем в секунды. Недопустимы состояния: "провафлили", 0, больше 10 часов
  self::$GameTurnTime = round(floatval(self::$worldSettings->get('TURN_TIME')) * 60);
  assert(!((self::$GameTurnTime < 1) || (self::$GameTurnTime > 36000)));  // Орем, если модифаер вне диапазона

  // Считываем время "предполетной подготовки" и преобразуем в секунды. Недопустимы состояния: "провафлили", 0, больше 10 часов
  self::$LocalTravelMinTime = round(floatval(self::$worldSettings->get('MIN_LOCAL_TRAVEL_TIME')) * self::$GameTurnTime);
  assert(!((self::$LocalTravelMinTime < 1) || (self::$LocalTravelMinTime > 36000))); // Орем, если модифаер вне диапазона

  // Считываем минимальное время межкольцевого перелета и преобразуем в секунды. Недопустимы состояния: "провафлили", 0, больше 48 часов
  self::$InterRingMinTime = round(floatval(self::$worldSettings->get('MIN_RING_TRAVEL_TIME')) * self::$GameTurnTime);
  assert(!((self::$InterRingMinTime < 1) || (self::$InterRingMinTime > 172800))); // Орем, если модифаер вне диапазона

  // Считываем минимальное время перелета для мельсиона и преобразуем в секунды. Недопустимы состояния: больше 48 часов
  self::$FourRingMinTime = round(floatval(self::$worldSettings->get('MIN_COLONY_RING_TRAVEL_TIME')) * self::$GameTurnTime);
  // В случае если модифаер провафлили  делаем его равным MIN_RING_TRAVEL_TIME
  if (self::$FourRingMinTime == 0) self::$FourRingMinTime = self::$InterRingMinTime;
  assert(!((self::$FourRingMinTime < 1) || (self::$FourRingMinTime > 172800))); // Орем, если модифаер вне диапазона

  // Считываем "коэфициент разгона". Допустимый диапазон 0.05-10 
  self::$TravelTimePower = floatval(self::$worldSettings->get('TRAVELLING_TIME_POWER')); 
  assert(!((self::$TravelTimePower < 0.05) || (self::$TravelTimePower > 10))); // Орем, если модифаер вне диапазона

  // Считываем лимитер ускорения. Состояние "провафлили" расценивается как 0. Диапазон 0-48 часов
  self::$TravelSpeedupLimit = round(floatval(self::$worldSettings->get('MIN_SPEED_ACCELERATION_TIME_TURNS')) * self::$GameTurnTime);
  assert(!((self::$TravelSpeedupLimit < 0) || (self::$TravelSpeedupLimit > 172800))); // Орем, если модифаер вне диапазона

  // Считываем тип логики ускорения. Допустимые значения: 0,1. "Провафлили" - расценивается как 0
  // рекомендуется вбить 1
  self::$TravelSpeedupLogic = intval(self::$worldSettings->get('SPEED_ACCELERATION_LOGIC')); 
  assert(!((self::$TravelSpeedupLogic < 0) || (self::$TravelSpeedupLogic > 1))); // Орем, если модифаер вне диапазона


 /// Считывание персональных Дадиных модифаеров. В идеале, чтобы их небыло нахуй.

 /// Считываем Дадин модифаер минимального лимитера. Состояние "провафлили" расценивается как "ну и слава богу". Диапазон: Ну_и_слава_богу-Похую_сколько_часов
  self::$TravelMinTimeLimit = round(floatval(self::$worldSettings->get('MIN_TRAVEL_TIME')) * self::$GameTurnTime); ///
  assert(!(self::$TravelMinTimeLimit < 0));     /// Орем, если модифаер вне диапазона

 /// Считываем Дадин модифаер максимального лимитера. Состояние "провафлили" расценивается как "ну и слава богу". Диапазон: Ну_и_слава_богу-Похую_сколько_часов
  self::$TravelMaxTimeLimit = round(floatval(self::$worldSettings->get('MAX_TRAVEL_TIME')) * self::$GameTurnTime); ///
  assert(!(self::$TravelMaxTimeLimit < 0));     /// Орем, если модифаер вне диапазона

 /// Считываем Дадин модифаер дублирующий время внутрикомплексного полета. Состояние "провафлили" расценивается как "ну и слава богу". Диапазон: Ну_и_слава_богу-Похую_сколько_часов
  self::$FixedLocalComplexTime = round(floatval(self::$worldSettings->get('MIN_COMPLEX_TRAVEL_TIME')) * self::$GameTurnTime); ///
  assert(!(self::$FixedLocalComplexTime < 0));  /// Орем, если модифаер вне диапазона

/// конец блока 


  if (self::$WorldTravelType == 0) break; // Если статический мир то сваливаем т.к. остальные модифаеры шерифа не волнуют

  // Считываем время шага вращения колец. !!! Модифаер указывается в минутах, независимо от TURN_TIME. 
  // Диапазон 1 - 300. Если 0 то расчитывается автоматически.
  //
  // **** Рекомендуется установить в 0 и не допускать к корректировке Райлу с Дадой пока не сдадут экзамен по
  // вождению, а лучше совсем не допускать :)
  // 
  self::$RingRotationStep = round(floatval(self::$worldSettings->get('RING_ROTATION_STEP_IN_MINUTES')) * 60);
  if (self::$RingRotationStep == 0) { // Какое счастье, что никто не сломал!!! Включаем автоматику.
   self::$RingRotationStep = 60;
   foreach (array(300=>180,900=>300,1800=>600,2700=>900,3600=>1200,5400=>1800,7200=>2400,14400=>3600) as  $i => $j) if (self::$GameTurnTime >= $i) self::$RingRotationStep = $j;
  } 
  assert(!((self::$RingRotationStep < 60) || (self::$RingRotationStep > 18000))); // Орем, если модифаер вне диапазона

  if (self::$WorldTravelType == 2) break; // Если мир без сближения то сваливаем т.к. остальные модифаеры шерифа не волнуют

  // Считываем максимальное время межкольцевого перелета и преобразуем в секунды. Недопустимы состояния: больше 48 часов, меньше MIN_RING_TRAVEL_TIME+3
  // Если "провафлили" или 0, устанавливается как MIN_RING_TRAVEL_TIME + 7
  self::$InterRingMaxTime = round(floatval(self::$worldSettings->get('MAX_RING_TRAVEL_TIME')) * self::$GameTurnTime);
  if (self::$InterRingMaxTime == 0) self::$InterRingMaxTime = self::$InterRingMinTime + 7 * self::$GameTurnTime;
  assert(!((self::$InterRingMaxTime < (self::$InterRingMinTime + 3 * self::$GameTurnTime)) || (self::$InterRingMaxTime > 172800))); // Орем, если модифаер вне диапазона

  // Считываем время удаления на первом шаге расхождения колец Недопустимы состояния: меньше 0, больше половины дельты между MIN и MAX_RING_TRAVEL_TIME
  // Если меньше RING_TRAVEL_TIME_STEP то игнорируется, "провафлили" - считаем за 0
  // Рекомендуется вбить 1
  self::$InterRingFirstStep = round(floatval(self::$worldSettings->get('RING_TRAVEL_FIRST_INCREMENT_TIME')) * self::$GameTurnTime);
  assert(!((self::$InterRingFirstStep < 0) || (self::$InterRingFirstStep > ((self::$InterRingMaxTime-self::$InterRingMinTime)/2)))); // Орем, если модифаер вне диапазона


  // Считываем время удаления на первом шаге расхождения мельсиона Недопустимы состояния: меньше 0, больше дельты между MIN и MAX_RING_TRAVEL_TIME
  // Рекомендуется вбить 1, "провафлили" - считаем за 0
  self::$FourRingAddonTime = round(floatval(self::$worldSettings->get('COLONIE_RING_TRAVELTIME_DELTA')) * self::$GameTurnTime);
  assert(!((self::$FourRingAddonTime < 0) || (self::$FourRingAddonTime > (self::$InterRingMaxTime-self::$InterRingMinTime)))); // Орем, если модифаер вне диапазона

  // Считываем время градационного шага  приращения удаления. Недопустимы состояния: меньше 0, больше дельты между MIN и MAX_RING_TRAVEL_TIME деленной на 5
  // Рекомендуется вбить 0.5  При "провафлили" и значении менее минимально эффективного - устанавливается минимально эффективное
  self::$InterRingTimeStep = round(floatval(self::$worldSettings->get('RING_TRAVEL_TIME_STEP')) * self::$GameTurnTime);
  if (self::$InterRingTimeStep == 0) self::$InterRingTimeStep =  round(self::$GameTurnTime / 2);
  if (self::$InterRingTimeStep < ((self::$InterRingMaxTime-self::$InterRingMinTime)/125)) self::$InterRingTimeStep = round((self::$InterRingMaxTime-self::$InterRingMinTime)/125);
  assert(self::$InterRingTimeStep <= ((self::$InterRingMaxTime-self::$InterRingMinTime)/5)); // Орем, если модифаер вне диапазона
  break; //Абзац
 }
 self::$isInitialised = 1;
}

public static function Count (DE_Comb $combFrom, DE_Comb $combTo, GB_MoveSpeedInterface $speed, $startDate = null){ 
 assert(is_null($startDate) || $startDate instanceof GB_Date); // Ахуеть дайте две

 if(is_null($startDate)) $startDate = GB_TypeFactory::CreateBase('GB_Date', time());
 if(is_null(self::$isInitialised))self::Init();
 // if($combTo->getID() == $combFrom->getID()) ds_error('Cant count travel with zero path. ('.$combFrom->getID().', '.$combTo->getID().')');

 // Глобальное онанирование закончено, дрочим локально

 $SrcRing = $combFrom->getRingNumber();                         // ООП самый лучший тормоз сервера
 $SrcComplex =  $combFrom->getComplexNumber();                  //  и самый оптимальный стиль для интерпретатора
 $SrcComplexPerRing =  $combFrom->getCombRing()->getComplexCount();
 $DstRing = $combTo->getRingNumber();                           // Оптимизацию кода и памяти придумали лохи
 $DstComplex = $combTo->getComplexNumber();                     //  потому что у них нет денег на такой крутой сервак
 $DstComplexPerRing = $combTo->getCombRing()->getComplexCount();//  как у нас
 $TravelTimeSpeedup = max(min($speed->value,2),0.01);           // До кучи...
 $StartTime = $startDate->value;                                // I like move it, move it... 
 
 //--------------------------------------------------------------------------------------------------
 //   ******** Начиная с этого места не рекомендуется "УЛУЧШАТЬ" код !!! *******
 
 // Параметры физической модели (трогать не рекомендуеться, особенно не смоделировав предварительно)
 // Типа константа. Вынесена сюда из общего блока "ибо нефиг". Бездумная правка разрушит модель!!!
 $SectorPerRing = array( 1 => 251, 2 => 262, 3 => 273, 4 => 280 * 3 );  // Количество секторов для колец

// Рабочие переменные 
 $InterRingTravelTime = 0;                                      // Время межкольцевого перелета
 $WayOneDistance = 0; $WayTwoDistance = 0;                      // Два варианта пути (локальный отрезок полета в комплексах)   

 while (true) {                                                // Engine start
  $WayOneDistance = abs($SrcComplex-$DstComplex);             
  $WayTwoDistance = max($SrcComplexPerRing,$DstComplexPerRing)-$WayOneDistance;
  if ($SrcRing == $DstRing) break;                 // Перемещение внутри кольца
  $InterRingTravelTime = (max($SrcRing,$DstRing)==4)? self::$FourRingMinTime: self::$InterRingMinTime;
  if (self::$WorldTravelType == 0) break;          // Межкольцеыое перемещение в статическом мире
  // Межкольцевое перемещение в динамическом мире
  $CurrentStep = floor(($StartTime-self::$StartRotationPoint)/self::$RingRotationStep); // Подготовим временную системум
  $SrcComplex--; $DstComplex--;                    // Нормализуем комплексы, чтобы считались от нуля
  if ($SrcRing == 4) {                             // Перемещение с Мельсиона ?
      // Обменяем точку старта и финиша, сведя к перемещению на Мельсион  
   $tmp = $SrcRing; $SrcRing = $DstRing; $DstRing = $tmp;
   $tmp = $SrcComplex; $SrcComplex = $DstComplex; $DstComplex = $tmp;
   $tmp = $SrcComplexPerRing; $SrcComplexPerRing = $DstComplexPerRing; $DstComplexPerRing = $tmp;
  }
  // Фаза 1 - Выясняем соприкасающиеся сектора колец
  if ($DstRing == 4) {                             // Перемещение на Mельсион?
   // Две точки соприкосновения Мельсиона
   $DstRingCommonOne = ($SectorPerRing[$DstRing]*($SrcRing-1)/6+$CurrentStep)%$SectorPerRing[$DstRing];
   $DstRingCommonTwo = ($SectorPerRing[$DstRing]*($SrcRing+2)/6+$CurrentStep)%$SectorPerRing[$DstRing];
   // и исходного кольца
   $SrcRingCommonOne = (($SectorPerRing[$SrcRing]*1)/4+$CurrentStep)%$SectorPerRing[$SrcRing];
   $SrcRingCommonTwo = (($SectorPerRing[$SrcRing]*3)/4+$CurrentStep)%$SectorPerRing[$SrcRing];
  } else {                                        // перемещение между обычными кольцами    
   // Две точки соприкосновения кольца назначения
   $DstRingCommonOne = $CurrentStep%$SectorPerRing[$DstRing];
   $DstRingCommonTwo = ($CurrentStep+$SectorPerRing[$DstRing]/2)%$SectorPerRing[$DstRing];
   // и исходного кольца      
   $SrcRingCommonOne = $CurrentStep%$SectorPerRing[$SrcRing];
   $SrcRingCommonTwo = ($CurrentStep+$SectorPerRing[$SrcRing]/2)%$SectorPerRing[$SrcRing];
  }   
  // Выясняем соприкасающеся комплексы и нормируем их за компанию xxxRingCommonOneFirst=0
  // Кольца назначения 
  $DstRingCommonOneFirst = floor(($DstComplexPerRing*($DstRingCommonOne+($SectorPerRing[$DstRing]>=$DstComplexPerRing?($CurrentStep/$SectorPerRing[$DstRing])%2:0))+($SectorPerRing[$DstRing]>=$DstComplexPerRing?0-($CurrentStep/$SectorPerRing[$DstRing])%2:$SectorPerRing[$DstRing]/2))/$SectorPerRing[$DstRing]);
  $DstRingCommonOneLast = (($SectorPerRing[$DstRing]>=$DstComplexPerRing?$DstRingCommonOneFirst:($DstComplexPerRing*($DstRingCommonOne+1)+$SectorPerRing[$DstRing]/2)/$SectorPerRing[$DstRing]-1)+($DstComplexPerRing-$DstRingCommonOneFirst))%$DstComplexPerRing;
  $DstRingCommonTwoFirst = (($DstComplexPerRing*($DstRingCommonTwo+($SectorPerRing[$DstRing]>=$DstComplexPerRing?($CurrentStep/$SectorPerRing[$DstRing])%2:0))+($SectorPerRing[$DstRing]>=$DstComplexPerRing?0-($CurrentStep/$SectorPerRing[$DstRing])%2:$SectorPerRing[$DstRing]/2))/$SectorPerRing[$DstRing]+($DstComplexPerRing-$DstRingCommonOneFirst))%$DstComplexPerRing;
  $DstRingCommonTwoLast = $SectorPerRing[$DstRing]>=$DstComplexPerRing?$DstRingCommonTwoFirst:((($DstComplexPerRing*($DstRingCommonTwo+1)+$SectorPerRing[$DstRing]/2)/$SectorPerRing[$DstRing]-1)+($DstComplexPerRing-$DstRingCommonOneFirst))%$DstComplexPerRing;
  // Исходного кольца
  $SrcRingCommonOneFirst = floor(($SrcComplexPerRing*($SrcRingCommonOne+($SectorPerRing[$SrcRing]>=$SrcComplexPerRing?($CurrentStep/$SectorPerRing[$SrcRing])%2:0))+($SectorPerRing[$SrcRing]>=$SrcComplexPerRing?0-($CurrentStep/$SectorPerRing[$SrcRing])%2:$SectorPerRing[$SrcRing]/2))/$SectorPerRing[$SrcRing]);
  $SrcRingCommonOneLast = (($SectorPerRing[$SrcRing]>=$SrcComplexPerRing?$SrcRingCommonOneFirst:($SrcComplexPerRing*($SrcRingCommonOne+1)+$SectorPerRing[$SrcRing]/2)/$SectorPerRing[$SrcRing]-1)+($SrcComplexPerRing-$SrcRingCommonOneFirst))%$SrcComplexPerRing;
  $SrcRingCommonTwoFirst = (($SrcComplexPerRing*($SrcRingCommonTwo+($SectorPerRing[$SrcRing]>=$SrcComplexPerRing?($CurrentStep/$SectorPerRing[$SrcRing])%2:0))+($SectorPerRing[$SrcRing]>=$SrcComplexPerRing?0-($CurrentStep/$SectorPerRing[$SrcRing])%2:$SectorPerRing[$SrcRing]/2))/$SectorPerRing[$SrcRing]+($SrcComplexPerRing-$SrcRingCommonOneFirst))%$SrcComplexPerRing;
  $SrcRingCommonTwoLast = $SectorPerRing[$SrcRing]>=$SrcComplexPerRing?$SrcRingCommonTwoFirst:((($SrcComplexPerRing*($SrcRingCommonTwo+1)+$SectorPerRing[$SrcRing]/2)/$SectorPerRing[$SrcRing]-1)+($SrcComplexPerRing-$SrcRingCommonOneFirst))%$SrcComplexPerRing;
  // Нормируем координаты исходного и целевого комплекса
  $DstComplex = ($DstComplex+($DstComplexPerRing-$DstRingCommonOneFirst))%$DstComplexPerRing;
  $SrcComplex = ($SrcComplex+($SrcComplexPerRing-$SrcRingCommonOneFirst))%$SrcComplexPerRing;
  // Фаза 2 - Обрабатываем варианты перемещения если какой-либо комплекс находится в точке соприкосновения
  $WayOneDistance = $WayTwoDistance = 0;     
  if ($DstRing == 4) {                             // Перемещение на Mельсион?
   // вычисляем расстояние от старта до точки пересечения  
   if ($SrcComplex >= $SrcRingCommonTwoFirst) {    // Исходный комплекс в старшем полукруге ?
    $WayOneDistance = $SrcComplexPerRing-$SrcComplex;
    if ($SrcComplex > $SrcRingCommonTwoLast) $WayTwoDistance = $SrcComplex-$SrcRingCommonTwoLast;
   } else {                                        // исходный комплекс в младшем полукруге
    if ($SrcComplex > $SrcRingCommonOneLast) $WayOneDistance = $SrcComplex-$SrcRingCommonOneLast;
    $WayTwoDistance = $SrcRingCommonTwoFirst-$SrcComplex;
   }  
   // добавляем расстояние от точки пересечения до финиша
   if ($DstComplex >= $DstRingCommonTwoFirst) {    // Целевой комплекс в старшем полукруге ?
    $WayOneDistance += $DstComplexPerRing-$DstComplex;
    if ($DstComplex > $DstRingCommonTwoLast) $WayTwoDistance += $DstComplex-$DstRingCommonTwoLast;
   } else {                                        // целевой комплекс в младшем полукруге
    if ($DstComplex > $DstRingCommonOneLast) $WayOneDistance += $DstComplex-$DstRingCommonOneLast;
    $WayTwoDistance += $DstRingCommonTwoFirst-$DstComplex;
   }  
   // Прибавляем добавочное время, если требуется
   if (min($WayOneDistance,$WayTwoDistance) && (self::$WorldTravelType == 1)) $InterRingTravelTime += self::$FourRingAddonTime;
   break; // Обработка Мельсиона закончена  
  } 
  // Обработка перемещения между обычными кольцами, Проверка стартовой точки
  if ($SrcComplex <= $SrcRingCommonOneLast) { // Исходный комплекс находится в первом пересечении колец
   if ($DstComplex > $DstRingCommonOneLast) { // Комплекс назначения вне зоны перехода
    $WayOneDistance = $DstComplexPerRing-$DstComplex;
    $WayTwoDistance = $DstComplex-$DstRingCommonOneLast;
   }
   break;
  } 
  if (($SrcComplex >= $SrcRingCommonTwoFirst) && ($SrcComplex <= $SrcRingCommonTwoLast)) { // Исходный комплекс находится во втором пересечении колец
   if ($DstComplex > $DstRingCommonTwoLast) $WayOneDistance = $DstComplex-$DstRingCommonTwoLast;   // Комплекс назначения после зоны перехода
   if ($DstComplex < $DstRingCommonTwoFirst) $WayOneDistance = $DstRingCommonTwoFirst-$DstComplex; // Комплекс назначения до зоны перехода
   $WayTwoDistance = $WayOneDistance;         // Возможен только  один путь
   break; 
  }
  // Проверка финишной точки
  if ($DstComplex <= $DstRingCommonOneLast) { // Комплекс назначения в первом пересечении колец, исходный комплекс вне зоны перехода
   $WayOneDistance = $SrcComplexPerRing-$SrcComplex;
   $WayTwoDistance = $SrcComplex-$SrcRingCommonOneLast;
   break; 
  }
  if (($DstComplex >= $DstRingCommonTwoFirst) && ($DstComplex <= $DstRingCommonTwoLast)) { // Комплекс назначения находится во втором пересечении колец
   if ($SrcComplex > $SrcRingCommonTwoLast) $WayOneDistance = $SrcComplex-$SrcRingCommonTwoLast;  // Исходный комплекс после зоны перехода
   if ($SrcComplex < $SrcRingCommonTwoFirst) $WayOneDistance = $SrcRingCommonTwoFirst-$SrcComplex; // Исходный комплекс до зоны перехода
   $WayTwoDistance = $WayOneDistance;         // Возможен только  один путь
   break; 
  }
  // Побыстрому не отделались - нужен перелет
  if (self::$WorldTravelType == 1) {           // Мир с сближением колец ?
   // Вычисляем расстояние между кольцами в точке перемещения
   $tmp = floor($SrcComplexPerRing/2);         // поправка на округление при нечетных длинах колец
   if ($SrcComplex >= $tmp) {                  // позиция удаленности точки перемещения от ближайшей точки касания
    $RingPosition = $SrcComplex-$tmp;
    $RingLength = $SrcComplexPerRing-$tmp;
   } else {
    $RingPosition = $SrcComplex;
    $RingLength = $tmp;
   }
   // Расчет и округления времени полета между кольцами
   $tmp = self::$InterRingMinTime;             // учтем задержку первого шага, если она есть
   if (self::$InterRingTimeStep < self::$InterRingFirstStep) $tmp += (self::$InterRingFirstStep-self::$InterRingTimeStep);
   // Собственно  расчет времени 
   $InterRingTravelTime = ceil(abs((self::$InterRingMaxTime-$tmp)*sin(pi()*$RingPosition/$RingLength)));
   // Подгонка результата  под градационную шкалу
   if ($InterRingTravelTime%self::$InterRingTimeStep) $InterRingTravelTime = $InterRingTravelTime-$InterRingTravelTime%self::$InterRingTimeStep+self::$InterRingTimeStep;
   $InterRingTravelTime += $tmp;
  }
  // Вычисляем длину локального участка
  $SrcComplex = floor(($SrcComplex*$DstComplexPerRing)/$SrcComplexPerRing); // Правим источник
  if ($DstComplex > $SrcComplex) {             // и вычисляем два возможных пути
   $WayOneDistance = $DstComplex-$SrcComplex;
   $WayTwoDistance = $DstComplexPerRing-$DstComplex+$SrcComplex;
  } else { 
   $WayOneDistance  = $SrcComplex-$DstComplex;
   $WayTwoDistance  = $DstComplexPerRing-$SrcComplex+$DstComplex;
  }
  break; // Абзац
 } 

 if (self::$worldSettings->get('NEW_TRAVEL_FORMULA')>0) {
    $b = (float)self::$worldSettings->get('TRAVEL_COEFF_B');
    $c = (float)self::$worldSettings->get('TRAVEL_COEFF_C');
    $x = min($WayOneDistance,$WayTwoDistance);
    $z = $speed->value;
    if ($x>0) $a = $b / pow($x, $c);
    else $a = 0;
    $y1 = ($a * $x*$x) / 60;
    if ($x>0) $y = max(0, $y1 * log($x, $z*($x+10)) + (1 - $z) / pow($x, 0.2));
    else $y=0;
    if ($z < 1) $y = max($y, y1);
    if ($InterRingTravelTime==0) $InterRingTravelTime = self::$LocalTravelMinTime;
    $TotalTravelTime = (int) round ($y * 60 + $InterRingTravelTime);
 }
 else {
    // Накладываем ускорение.
    //  Логика этого процесса немного хромает и немешало бы пересмотреть всю схему движка
    //  и легенды, но попробуем наиболее логично выкрутится из той ситуации что есть.
    $StablePart = 0;
    $SpeedupPart = (float)pow(min($WayOneDistance,$WayTwoDistance),self::$TravelTimePower) * self::$GameTurnTime;
    while (true) {                                              // SpeedUp Logic start
     if (self::$WorldTravelType == 1) {                         // Динамический мир со сближением
      $StablePart += $InterRingTravelTime;                      // мехкольцевое время не трогаем полюбому
      if ($TravelTimeSpeedup > 1) {                             // Ускорение ?
       $StablePart += self::$LocalTravelMinTime;                // тогда не трогаем и время подотовки
       if ($StablePart >=  self::$TravelSpeedupLimit) break;          // если "то что нетрогаем" больше лимитера идем накладывать ускорение на то что остаось
       if (($StablePart+$SpeedupPart) <= self::$TravelSpeedupLimit) { // Общее время меньше лимитера ?
        $StablePart += $SpeedupPart;                            // тогда нифига не ускоряем
        $SpeedupPart = 0;
        break;
       }
       $tmp = self::$TravelSpeedupLimit-$StablePart;                  // иначе ускоряем все что больше лимита
       $StablePart += $tmp;
       $SpeedupPart -= $tmp;
       break;
      } 
      $SpeedupPart += self::$LocalTravelMinTime;                // А при замедлении давим и время подготовки
      break;
     }
     // Статический или динамический мир без сближения
     // в зависимости от логики  затрагиваем или нет межкольцевое время
     if (self::$TravelSpeedupLogic) $StablePart += $InterRingTravelTime; else $SpeedupPart += $InterRingTravelTime; 
     $SpeedupPart += self::$LocalTravelMinTime;                // "Время подготовки" ускоряем "в силу традиций" :)
     if ($TravelTimeSpeedup <= 1) break;                       // Если замедление то делать больше нехер
     if ($StablePart >=  self::$TravelSpeedupLimit) break;           // если "то что нетрогаем" больше лимитера то делать больше нехер
     if (($StablePart+$SpeedupPart) <= self::$TravelSpeedupLimit) {  // Общее время меньше лимитера ?
      $StablePart += $SpeedupPart;                             // тогда нифига не ускоряем
      $SpeedupPart = 0;
       break;
     }
     $tmp = self::$TravelSpeedupLimit-$StablePart;                     // иначе ускоряем все что больше лимита
     $StablePart += $tmp;
     $SpeedupPart -= $tmp;
     break;
    }  // Абзац
    
    
    // Накладываем ускорение на нужный отрезок
    if ($TravelTimeSpeedup > 1) $SpeedupPart /= $TravelTimeSpeedup; else $SpeedupPart += $SpeedupPart * (1 - $TravelTimeSpeedup);
    
    $TotalTravelTime = round($StablePart+$SpeedupPart);        // Суммируем все отрезки и возращаем результат
 }
 
 /// Блок реализации Дадиного долбоебизма
 
 if (self::$TravelMinTimeLimit != 0) $TotalTravelTime = max($TotalTravelTime,self::$TravelMinTimeLimit); /// Активация минимального лимитера
 if (self::$TravelMaxTimeLimit != 0) $TotalTravelTime = min($TotalTravelTime,self::$TravelMaxTimeLimit); /// Активация максимального лимитера
 if ((self::$FixedLocalComplexTime !=0) && ($SrcRing == $DstRing) && ($SrcComplex == $DstComplex)) $TotalTravelTime = self::$FixedLocalComplexTime;  /// активация дублирующего модифаера внутрикомплексного полета
 
/// конец блока  


 assert($TotalTravelTime > 0); // До такого состояния довести функцию невозможно, но оставим для "исправляльщиков кода"
 return GB_TypeFactory::CreateConstant('GB_TimePeriod', $TotalTravelTime);
} 

}//END OF CLASS
?>