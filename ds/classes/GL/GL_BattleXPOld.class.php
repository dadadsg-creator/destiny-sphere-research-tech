<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_BattleXP
{	
	//Рейтинрг перед началом боя
	static private $attackerBattleXPStart = 0;
	static private $defenderBattleXPStart = 0;
	
	//Сведени япо рандуам для обороняющихся	
	static private $defendersRoundsInfo;
	
	//Элеиент описани дефендара
	static private $defenderRoundInfoElement;	
	
	//Сыла армии в стаизисе перед боем
	static private $attackerStartArmyPower = 0;	
	static private $defenderStartArmyPower = 0;
	
	//Звездность боя (звездность меньшей армии в бое)
	static private $battleStars;
	
	//Потери армиий в течение боя
	static private $attackerArmyLosesPower = 0;
	static private $defenderArmyLosesPower = 0;	
	
	//Проценты от сумарных потерь потерь каждой
	static private $attackerProcentLoses = 0;
	static private $defenderProcentLoses = 0;
		
	//Базовый рейтинг за бой
	static private $battleBaseRating = 0;
	
	//Разница между рейтингов атакующего и нападающего
	static private $batlleXPDelta = 0;
	
	//Вероятности выигрыша соторн
	static private $attackerWinProbability = 0;
	static private $defenderWinProbability = 0;
	
	//Множетили для расчет финального рейтинга завит от того кто побеьил
	static private $attackerXPMultiplaer = 0;
	static private $defenderXPMultiplaer = 0;
	
	//Какая часть от суммарного рейтинга идет игроку
	static private $attackerFinalXPPart = 0;
	static private $defenderFinalXPPart = 0;
	
	//Предварительное знавчение XP
	static private $attackerPreXP = 0;
	static private $defenderPreXP = 0;	
	
	//Модифаеры
	static private $BATTLE_XP_ARMY_POWER = 2;
	static private $BATTLE_XP_SCALE_DIVISOR = 100000;
	static private $BATTLE_XP_PROBABILITY_POWER = 100;
	static private $BATTLE_XP_WINNER_MULTIPLAER = 0.8;
	
	static public function Count(DE_Battle $battle)
	{		
$str = "";
		//Массив с кокнчательными значениями результата
		$result = array(		
			'ATTACKER' => (float)0,
			'DEFENDER' => array()
		);
		
		//Берем значения рейтинга сторон до боя
		self::$attackerBattleXPStart = $battle->getAttacker()->getUserInstance()->getBattleXP()->getXP();
		
		$defendersCollection = $battle->getDefendersCollection();
		$defendersCollection->resetNext();
		while($defender = $defendersCollection->next())
		{
			self::$defenderBattleXPStart += $defender->getUserInstance()->getBattleXP()->getXP();			
			self::$defenderRoundInfoElement[$defender->getID()] = 0;
			$result['DEFENDER'][$defender->getID()] = 0;
		}
			
		//РАсчитываем потери и решаеи надо ли ситать вобще опыт
		if(!self::_countLosesAndStartPower($battle))
		{
			return $result;
		};
			

		//Определяем звездность боя
		$stars = array(
			GL_ArmyPowerToStars::Count(self::$attackerStartArmyPower),
			GL_ArmyPowerToStars::Count(self::$defenderStartArmyPower)
		);		
		self::$battleStars = min($stars);

		//Вычилили проценты от общиъх потреь за бой		
		self::$attackerProcentLoses = self::$attackerArmyLosesPower / self::$attackerStartArmyPower;
		self::$defenderProcentLoses = self::$defenderArmyLosesPower / self::$defenderStartArmyPower;
		
		//Расчитываем базорвый рейтинг з абой
		self::$battleBaseRating = pow(self::$BATTLE_XP_ARMY_POWER, self::$battleStars) - self::$battleStars;
	
		//ВЫчиляем  разницу
		self::$batlleXPDelta = self::$attackerBattleXPStart - self::$defenderBattleXPStart;

		//Степень для вероятности победы 
		$winDegree = self::$batlleXPDelta / self::$BATTLE_XP_SCALE_DIVISOR;
		
		//Степень для рейт., получ. игроками 
		$xpDegree = pow(self::$BATTLE_XP_PROBABILITY_POWER, $winDegree);

		//Вероятности выигрыша игроков
		self::$attackerWinProbability = 1 - (1 / ($xpDegree + 1));
		self::$defenderWinProbability = 1 - self::$attackerWinProbability;

		//Вычисляем ножетль для расчета базовго опыта
		switch ($battle->getWinnerCode()->value)
		{
			case 'A':
				self::$attackerXPMultiplaer = self::$BATTLE_XP_WINNER_MULTIPLAER;
				break;
			case 'D':
				self::$attackerXPMultiplaer = 1 - self::$BATTLE_XP_WINNER_MULTIPLAER;
				break;
			default:
				ds_error('Uncorrect winner code = '.$this->battle->getWinnerCode());
		} 		
		self::$defenderXPMultiplaer = 1 - self::$attackerXPMultiplaer;
		
		//Вычиляем часть опыта которую получает какждый из участников
		self::$attackerFinalXPPart = self::$attackerXPMultiplaer + self::$attackerXPMultiplaer*(0.7 - self::$attackerWinProbability);

		self::$defenderFinalXPPart = abs(1 - self::$attackerFinalXPPart);
		
		//Предварительное занвие опыта на учатнка
		self::$attackerPreXP = self::$battleBaseRating * self::$attackerFinalXPPart;
		self::$defenderPreXP = abs(self::$battleBaseRating - self::$attackerPreXP);
				
		//Окончательное знавчение опыта
		$attackerFinalXP = (float)(self::$attackerPreXP * self::$defenderProcentLoses);
		$defenderFinalXP = (float)(self::$defenderPreXP * self::$attackerProcentLoses);
		
$str .= "
                                   АТАКУЮЩАЯ СТОРОНА / ОБОРОНЯЮЩАЯСЯ СТОРОНА
Начальное значение опыта игроков = ".self::$attackerBattleXPStart." / ".self::$defenderBattleXPStart."
Сила сторон до боя стазис = ".self::$attackerStartArmyPower." / ".self::$defenderStartArmyPower."
Начальное значение звезд армий = ".$stars[0]." / ".$stars[1]."
Звездность боя = ".self::$battleStars."
Потери сторон стазис = ".self::$attackerArmyLosesPower." / ".self::$defenderArmyLosesPower."
Потери сторон в процентах от общх потерь в бое = ".self::$attackerProcentLoses." / ".self::$defenderProcentLoses."
--------------------------------------
Базовый рейтинг боя = ".self::$battleBaseRating."
Степень для вероятности победы  = ".$winDegree."
Степень для рейт., получ. игроками  = ".$xpDegree."
---------------------------------------
Вероятность победы Игрока = ".self::$attackerWinProbability." / ".self::$defenderWinProbability."
Коофициент от победы = ".self::$attackerXPMultiplaer." / ".self::$defenderXPMultiplaer."
Множители для призового рейтинга = ".self::$attackerFinalXPPart." / ".self::$defenderFinalXPPart."
Призовой рейтинг Игрока (Ри1) = ".self::$attackerPreXP." / ".self::$defenderPreXP."
----------------------------------------
Окончательное значение опыта = ".$attackerFinalXP." / ".$defenderFinalXP."

";

		//Начислztv опыт атакующему
		$result['ATTACKER'] = $attackerFinalXP;
		

		//Туперь делим опыт между обороняющимимся
		$xpPartInRound = $defenderFinalXP / count(self::$defendersRoundsInfo);
		foreach(self::$defendersRoundsInfo as $roundNumber=>$defendersInfo)
		{
			$summArmyPowerInRound = array_sum($defendersInfo);			
			//assert($summArmyPowerInRound > 0);
			
			foreach($defendersInfo as $defenderID => $armyPowerInRound)
			{		
				
				if($summArmyPowerInRound == 0)
				{
					$deltaXP = 0;
				}else{
					$deltaXP = $xpPartInRound * ($armyPowerInRound / $summArmyPowerInRound);	
				}
							
				//Вобщем туту заткунто проверкой непончятное такое метос. Вобщем кто-то из дефендероов почему-то 
				// не попадал в это список при инициализаии массива обороняющихся
				//Пока заткнул проверкой на существоание, если будут глюки с начислением опыта обороняющимсясомтреть надо отсюда.								
				if(!key_exists($defenderID, $result['DEFENDER']))
				{
					$result['DEFENDER'][$defenderID] = 0;
				}
				
				$result['DEFENDER'][$defenderID] +=  $deltaXP;		
$str .= "
roundNumber = $roundNumber
defenderID = $defenderID
xpPartInRound = $xpPartInRound
armyPowerInRound = $armyPowerInRound
summArmyPowerInRound = $summArmyPowerInRound
deltaXP = $deltaXP
"; 	
//DebugTools::LogUser('Bursak', $str, __CLASS__);				
			}
		}
						
		//Возвращаем значения
		return $result;		
	}
	
	static private function _countLosesAndStartPower(DE_Battle $battle)
	{
		$roundsCount = 0;
		
		//Собераем сведения о потерях в течение боя и начальном состояние
		$roundsCollection = $battle->getBattleRoundCollection();		
		$roundsCollection->resetNext();
		while($round = $roundsCollection->next())
		{
			$roundsCount ++;
			
			$roundResultCollection = $round->getBattleResultCollection();
			$roundResultCollection->resetNext();
			
			$tmpDefendersRoundInfo = self::$defenderRoundInfoElement;
			
			while($result = $roundResultCollection->next())
			{

				$participant = $result->getPattleParticipant();
				$unit = $result->getUnit();
				
				if($round->getRoundNumber() == 0)
				{//Здесь начальное значвение армии выяисляем
					switch($participant->getParticipantFlag())
					{
						case 'ATTACKER':
							self::$attackerStartArmyPower += self::_countPower($unit, $result->getAmount());
							break;
						case 'DEFENDER':
							self::$defenderStartArmyPower += self::_countPower($unit, $result->getAmount());
							break;
						default:
							ds_error("Unknown participant code = ".$participant->getParticipantFlag());
					}
				}else{//Здесь значение потерь сумарное	
					switch($participant->getParticipantFlag())
					{
						case 'ATTACKER':
							self::$attackerArmyLosesPower += self::_countPower($unit, $result->getLoses());
							break;
						case 'DEFENDER':
							$amount = $result->getAmount() + $result->getLoses();
							$tmpDefendersRoundInfo[$participant->getID()] += self::_countPower($unit, $amount);
							self::$defenderArmyLosesPower += self::_countPower($unit, $result->getLoses());
							break;
						default:
							ds_error("Unknown participant code = ".$participant->getParticipantFlag());
					}
				}//END OF IF					
			}//END OF WHILE $result
					
			if($round->getRoundNumber() != 0)
			{
				self::$defendersRoundsInfo[$round->getRoundNumber()] = $tmpDefendersRoundInfo;
			}	
		}//END OF WHILE $round

		//Теперь принимаем решенеи надо ли начилять опыт или нет
		//Елси победили без боя то ненадо начиляться
		if($roundsCount == 1)
		{
			return false;
		}

		//Если атакующий слддох весьпосле первго раунда то тоже ненадо. Это спам армия
		$attackerEndBattlePower = self::$attackerStartArmyPower - self::$attackerArmyLosesPower;
		if($roundsCount == 2 && !$battle->getAttacker()->getIsWinner() && $attackerEndBattlePower == 0 )
		{
			return false;
		}
		
		
		//Это на тот случай если пока шел первый рануд армию обороняюругой дядька в паралльном бое.
		if($roundsCount == 2)
		{
			if(array_sum(self::$defendersRoundsInfo[1]) == 0)
			{
				return false;
			}
		}
		
		return true;
	}
	
	static private function _countPower(DE_Unit $unit, $amount)
	{

		assert($amount>= 0);		
		return $unit->getUnitPower() * $amount;
	}
}


?>