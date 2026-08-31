<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Клас для расчета боевого опыта. Новая версия. от 21.02.207
 * Cпасибо Ольге за замечательно написанную документация. Респект ей и уважуха. :-)
 * 
 * Для упращения преложения алгаритма из оки посраюсь максимально сохранит ьобозначения которые есть
 * в алгаритме. Так что будут имена выглядеть странно.
 * 
 * Пусть 1 – армия нападения, а 2 – армия защиты.
 *
 */

class GL_BattleXP
{	
	/**
	 * Собственно сам класс боя
	 *
	 * @var DE_Battle
	 */
	static private $battle;
	
	/**
	 * Вес аткаующей армии перед началом боя.
	 *
	 * @var float
	 */
	static private $armyP1;
	
	/**
	 * Стартовое значение демеджа армии атакующего
	 *
	 * @var float
	 */
	static private $startDamage1 = 0;
	
	/**
	 * Стартовое значение демеджа армии обороняющегося
	 *
	 * @var float
	 */
	static private $startDamage2 = 0;
	
	/**
	 * Вес обороняющейся армии армии перед началом боя.
	 *
	 * @var float
	 */
	static private $armyP2;		
	
	/**
	 * Вес потоерь атакующей армии
	 *
	 * @var float
	 */
	static private $armyL1;
	
	/**
	 * Вес потерь обороняющейся армии в проде
	 *
	 * @var float
	 */
	static private $armyL2;
	
	/**
	 * Военный рейтинг игрока атакующего на начало боя
	 *
	 * @var float
	 */
	static private $battleR1;
	
	/**
	 * Военный рейтинг игрока обороняющегося на начало боя
	 *
	 * @var float
	 */
	static private $battleR2;
	
	/**
	 * Массив с защитниками присуствовашими на соте в начале раунда
	 * Слудющий формат:
	 * UIID => array
	 * (	 
	 *   'PARTICIPANT' => ,
	 *   'XP' => ,
	 *   'START_POWER' => 
	 * )
	 * 
	 * @var array
	 */
	static private $defendersList = array();	
	
	/**
	 * Настроечные коэф-т. Берется из игрового модификатора.
	 * Регулирует максимальный опыт за бой при бое противоюнитов или динаме (при победе с очень малыми потерями),
	 *
	 * @var float
	 */
	static private $xpModK1;
	
	/**
	 * Настроечные коэф-т. Берется из игрового модификатора.
	 * Регулирует максимальный опыт при атаке меньшей по весу армией
	 *
	 * @var float
	 */
	static private $xpModK2;
	
	/**
	 * Настроечные коэф-т. Берется из игрового модификатора.
	 * Устанавливает «порог» (в средних армиях по миру), после которого опыт не зависит от размеров воюющих армий.
	 * 
	 * @var float
	 */
	static private $xpModK3;
	
	/**
	 * Базовый коофициент масштабирования  опыта.
	 *
	 * @var float
	 */
	static private $xpMultiplaer;	
	
	/**
	 * Массив с результатми 
	 *
	 * @var array
	 */
	static private $result = array();	
	
	/**
	 * Код победителя в данном бое
	 *
	 * @var string
	 */
	static private $winnerCode;
	
	/**
	 * Какую минимальнуй часть войск победителя должен звалить проигравший что 
	 * бы получит ьопыт за бой
	 *
	 * @var float
	 */
	static private $minLosserKillDeltaForXP;
	
	/**
	 * Нужно ли начислять опыт атакующему за это бой
	 *
	 * @var boolean
	 */
	static private $attackerNeedXP;
	
	/**
	 * Нужно ли  начислять опыт обороняющемуся за это бой
	 *
	 * @var boolean
	 */
	static private $defenderNeedXP;
	
	/**
	 * ОБщее колличество райндов в бое
	 *
	 * @var integer
	 */
	static private $totlaRoundsCount;
	
	/**
	 * Строка с тестовыми выводами
	 *
	 * @var string
	 */	
	static public $debugInfo;
	
	static private $M1;
	 
	static private $M21;
	
	static private $M22;
	
	static private $LR1;
	
	static private $LR2;
	
	static private $M31;
	
	static private $M32;
	
	static private $M41;
	
	static private $M42;
		
	//----------------------------------------------------------------------------------------------------------------//
	
	/**
	 * Сосбвтенно основной метод.
	 *
	 * @param DE_Battle $battle
	 */
	static public function Count(DE_Battle $battle)
	{			
		self::_debugMsg("");
		self::_debugMsg("-----===== START count XP for battle = ".$battle->getID()." =====-----");		
		
		//Устанавливаем стартовые занчения
		self::_init($battle);	

//Временно отключили боевой опыт
return self::$result;
		
		//ОПределяем надо или нет считать опыт вобще.	
		if(!self::_needCountXP())
		{
			self::_debugMsg("");
			self::_debugMsg("=== NOT NEED COUNT XP. RETURN RESULT.");
			self::_debugMsg("");
			return self::$result;
		}
						
		self::$M1 = self::_countM1();
		self::_debugMsg("M1 = ".self::$M1);
		
		self::_debugMsg("");
		
		self::$M21 = self::_countM21();
		self::_debugMsg("M21 = ".self::$M21);
		self::$M22 = self::_countM22();
		self::_debugMsg("M22 = ".self::$M22);
		
		self::_debugMsg("");
		
		self::$LR1 = self::_countLR1();
		self::_debugMsg("LR1 = ".self::$LR1);
		self::$LR2 = self::_countLR2();
		self::_debugMsg("LR2 = ".self::$LR2);
		
		self::_debugMsg("");
		
		self::$M31 = self::_countM31();
		self::_debugMsg("M31 = ".self::$M31);
		self::$M32 = self::_countM32();
		self::_debugMsg("M32 = ".self::$M32);
		
		self::_debugMsg("");
		
		self::$M41 = self::_countM41();
		self::_debugMsg("M41 = ".self::$M41);
		self::$M42 = self::_countM42();
		self::_debugMsg("M42 = ".self::$M42);
		
		self::_debugMsg("");
		
		$xp1 = self::_countXP1();
		self::_debugMsg("XP1 = ".$xp1);
		$xp2 = self::_countXP2();
		self::_debugMsg("XP2 = ".$xp2);
						
		self::_seporateXP($xp1, $xp2);
				
		
		self::_debugMsg("");
		self::_debugMsg("=== COUNT ENDED. RETURN RESULT.");
		self::_debugMsg("");
		
		self::_debugMsg(print_r(self::$result, 1));
		
		DE_BattleSystemLog::Insert(self::$debugInfo);
		
		return self::$result;
	}
	
	static private function _seporateXP($xp1, $xp2)
	{
		self::_debugMsg("=== Separate XP");
		
		assert($xp1 >= 0);
		assert($xp2 >= 0);
		
		self::_debugMsg("xp1= $xp1");
		self::_debugMsg("xp2= $xp2");
		
		self::$result['ATTACKER'] = (double)$xp1;
		
		if($xp2 == 0)
		{
			return;
		}
		
		foreach(self::$defendersList as $UIID => $data)
		{
			self::$result['DEFENDER'][$data['PARTICIPANT']->getID()] = (double)($xp2 * ( $data['START_POWER'] / self::$armyP2));
		}
	}
	
	/**
	 * Рассчет коофициента M1
	 *
	 */
	static private function _countM1()
	{
		self::_debugMsg("=== Count M1");
		
		$averArmyPower = DS_Battle::GetAwerArmyPowerInWorld(self::$battle->getAttacker()->getUserInstance()->getWorld());		
		
		if($averArmyPower <= 0)
		{
			$averArmyPower = 1000;
		}
		
		return min(min(self::$armyP1, self::$armyP2) / (self::$xpModK3 * $averArmyPower) , 1);		
	}
	
	static private function _countM21()
	{
		self::_debugMsg("=== Count M21");
		
		$relation = self::$battleR2 / self::$battleR1;
		
		if($relation >= 1)
		{
			return log10($relation + 1) / log10(100) + 1 - log10(2) /log10(100);
		}else{
			return log10($relation + 1) / log10(2);
		}
	}
	
	static private function _countM22()
	{
		self::_debugMsg("=== Count M22");
		
		$relation = self::$battleR1 / self::$battleR2;	
		
		if($relation >= 1)
		{
			return log10($relation + 1) / log10(100) + 1 - log10(2) / log10(100);
		}else{
			return log10($relation + 1) / log10(2);
		}
	}
	
	static private function _countLR1()
	{
		self::_debugMsg("=== Count LR1");
		
		$out = self::$armyL1 * self::$armyP2 / (self::$armyL2 * self::$armyP1);
		
		if($out == 0)
		{
			$out = 0.000001;
		}
		
		return $out;
	} 
	
	static private function _countLR2()
	{
		self::_debugMsg("=== Count LR2");
		
		/*assert(self::$armyL1 > 0);
		assert(self::$armyP2 > 0);*/
		
		$out = self::$armyL2 * self::$armyP1 / (self::$armyL1 * self::$armyP2);
		
		if($out == 0)
		{
			$out = 0.000001;
		}
		
		return $out;
	} 
	
	static private function _countM31()
	{
		self::_debugMsg("=== Count M31");
		
		assert(self::$LR1);
		
		if(self::$LR1 <= 1)
		{
			return self::$xpModK1 * exp(-pow(self::$LR1, 2)) + 1 - self::$xpModK1 / exp(1);
		}else{
			return 1 / self::$LR1; 
		}
	}
	
	static private function _countM32()
	{
		self::_debugMsg("=== Count M32");
		
		assert(self::$LR2);
		
		if(self::$LR2 <= 1)
		{
			return self::$xpModK1 * exp(-pow(self::$LR2, 2)) + 1 - self::$xpModK1 / exp(1); 
		}else{
			return 1 / self::$LR2; 
		}
	}
	
	static private function _countM41()
	{
		self::_debugMsg("=== Count M41");
		
		if(self::$armyP1 <= self::$armyP2)
		{
			return self::$xpModK2 * exp(-pow(self::$armyP1 / self::$armyP2, 2)) + 1 - self::$xpModK2 / exp(1);
		}else{
			return pow(self::$armyP2 / self::$armyP1, 2);
		}
	}
	
	static private function _countM42()
	{
		self::_debugMsg("=== Count M42");
		
		if(self::$armyP2 <= self::$armyP1)
		{
			return self::$xpModK2 * exp(-pow(self::$armyP2 / self::$armyP1, 2)) + 1 - self::$xpModK2 / exp(1);
		}else{
			return pow(self::$armyP1 / self::$armyP2, 2);
		}
	}				
	
	static private function _countXP1()
	{
		self::_debugMsg("=== Count XP1");
		
		if(self::$attackerNeedXP)
		{
			return 2 * self::$M1 * self::$M21 * self::$M31 * self::$M41;	
		}else{
			return 0;
		}				
	}				
	
	static private function _countXP2()
	{
		self::_debugMsg("=== Count XP2");
		
		if(self::$defenderNeedXP)
		{
			return self::$M1 * self::$M22 * self::$M32 * self::$M42;	
		}else{
			return 0;
		}		
	}				
	
	/**
	 * Проверка необходимости рассчета рейтинга/ 
	 * И попутно определям может какой-то стороне за бесславный бой вобще опыт не положен.
	 *
	 */
	static private function _needCountXP()
	{
		self::_debugMsg("");
		self::_debugMsg("=== TEST NEED COUNT");
				
		if(self::$totlaRoundsCount < 2)
		{
			self::_debugMsg("There is no fight");
			self::_debugMsg("");
			return false;
		}
		
		//Если в одной из армий были только чемоданы, то рейтинг не даётся никому
		if(self::$startDamage1 == 0 || self::$startDamage2 == 0)
		{
			self::_debugMsg("One army have null damage in degin");
			self::_debugMsg("");
			return false;
		}
			
		assert(self::$winnerCode == 'A' || self::$winnerCode == 'D');
		if(self::$winnerCode == 'A')
		{
			if(self::$armyL1 / self::$armyP1 < self::$minLosserKillDeltaForXP)
			{
				self::_debugMsg("Defender army dont need XP. Too small kill.");
				self::_debugMsg("");
				self::$defenderNeedXP = false;
			}
		}else{
			if(self::$armyL2 / self::$armyP2 < self::$minLosserKillDeltaForXP)
			{
				self::_debugMsg("Attacker army dont need XP. Too small kill.");
				self::_debugMsg("");
				self::$attackerNeedXP = false;
			}
		}		
				
		return true;
	}
	
	/**
	 * Инициализация стартовых значений  атакующего
	 *
	 */
	static private function _initAttacker()
	{
		self::_debugMsg("");
		self::_debugMsg("=== INIT Attacker");
		
		//Опредлеям состояние опыта атакующего на начало боя.
		self::$battleR1 = self::$battle->getAttacker()->getUserInstance()->getBattleXP()->getXP();		
		
		//Математический ноль заменяем физическим
		if(self::$battleR1 <= 0)
		{
			self::$battleR1 = 1;
		}
		
		//Рассчитываем потери и начальное значение веса армии для атакующего
		$roundsCollection =  self::$battle->getBattleRoundCollection();
		$roundsCollection->resetNext();		
		while($round = $roundsCollection->next())
		{
			
			self::$totlaRoundsCount++;
			
			$roundResultCollection = $round->getBattleResultCollection();
			$roundResultCollection->resetNext();			
			while($result = $roundResultCollection->next())
			{
				$participant = $result->getPattleParticipant();
								
				if($participant->getParticipantFlag() != 'ATTACKER')
				{
					continue;
				}
				
				$unit = $result->getUnit();
				
				if($round->getRoundNumber() == 0)
				{					
					//Стартовое значение веса армии
					self::$armyP1 += $unit->getUnitPower() * $result->getAmount();
					self::$startDamage1 += $unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX');
				}else{
					//Вес потерь за бой
					self::$armyL1 += $unit->getUnitPower() * $result->getLoses();
				}						
			}
		}
		
		if(self::$armyL1 <= 0)
		{
			self::$armyL1 = 1;	
		}
		
		
		self::_debugMsg("battleR1 = ".self::$battleR1);
		self::_debugMsg("armyP1 = ".self::$armyP1);
		self::_debugMsg("armyL1 = ".self::$armyL1);
		self::_debugMsg("startDamage1 = ".self::$startDamage1);		
		
		//Инициализируем массив с результатом.
		self::$result['ATTACKER'] = (float)0;
		
	}//END OF METHOD _initAttacker()
	
	/**
	 *инициализация стратвых значений обороняющейся стороны
	 *
	 */
	static private function _initDefender()
	{
		self::_debugMsg("");	
		self::_debugMsg("=== INIT Defender");
		
		//Собераем сведения по обороняющимся.
		$roundsCollection =  self::$battle->getBattleRoundCollection();
		$roundsCollection->resetNext();		
		
		while($round = $roundsCollection->next())
		{
			$roundResultCollection = $round->getBattleResultCollection();
			$roundResultCollection->resetNext();	

			while($result = $roundResultCollection->next())
			{											
				$participant = $result->getPattleParticipant();
								
				if($participant->getParticipantFlag() == 'ATTACKER')
				{
					continue;
				}
				
				self::$result['DEFENDER'][$participant->getID()] = 0;				
				
				$unit = $result->getUnit();
				
				if($round->getRoundNumber() == 0)
				{					
					//Стартовое значение веса армии и опыта расписанное на участников боя присуствовавших на моент начала боя
					if(key_exists($participant->getUIID(), self::$defendersList))
					{
						self::$defendersList[$participant->getUIID()]['START_POWER'] += $unit->getUnitPower() * $result->getAmount();
					}else{
						
						$xp = $participant->getUserInstance()->getBattleXP()->getXP();
						
						//Математический ноль заменяем физическим
						if($xp <= 0)
						{
							$xp = 0.000001;
						}
						
						self::$defendersList[$participant->getUIID()] = array
						(							
							'PARTICIPANT' => $participant,
							'XP' => $xp,
							'START_POWER' => $unit->getUnitPower() * $result->getAmount()							
						);
						
					}			
					
					//Суммарный вес армии обороняющегося в началае
					self::$armyP2 += $unit->getUnitPower() * $result->getAmount();	
					
					//Суммарный демедж сторон 
					self::$startDamage2 += $unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX');
					
//echo "".$unit->getName()." = ".$unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX')." X ".$result->getAmount()."\n";

				}else{					
					//Вес потерь за бой. Расматриваем только участников бывших на моент начала боя.
					if(key_exists($participant->getUIID(), self::$defendersList))
					{
						self::$armyL2 += $unit->getUnitPower() * $result->getLoses();
					}					
				}				
			}//END OF WILE BY RESULT
		}//END OF WILE BY ROUNDS		
		
		if(self::$armyL2 <= 0)
		{
			self::$armyL2 = 1;	
		}
		
		//ВЫсчитываем как бы суммарный опыт обороняющейся стороны
		foreach (self::$defendersList as $UIID=>$data)
		{
			if(self::$armyP2 <= 0)
			{
				$armyPower = 1;
			}else{
				$armyPower = self::$armyP2;
			}
			
			self::$battleR2 += $data['XP'] * ($data['START_POWER'] / $armyPower);
		}
		
		if(self::$battleR2 <= 0)
		{
			self::$battleR2 = 1;
		}
		
		self::_debugMsg("battleR2 = ".self::$battleR2);		
		self::_debugMsg("armyP2 = ".self::$armyP2);
		self::_debugMsg("armyL2 = ".self::$armyL2);		
		self::_debugMsg("startDamage2 = ".self::$startDamage2);		
		
		self::_debugMsg("Defenders list:");		
		foreach(self::$defendersList as $k=>$v)
		{
			self::_debugMsg($v['PARTICIPANT']->getID().": XP = ".$v['XP'].", START_POWER = ".$v['START_POWER']);		
		}
		
		
	}//END OF METHOD _initDefender()
	
	/**
	 * Инициализация переменных стартовая
	 *
	 * @param DE_Battle $battle
	 */
	static private function _init(DE_Battle $battle)
	{		
		self::_debugMsg("");
		self::_debugMsg('=== INIT attributes total');
		
		self::$battle = $battle;
		
		$settings = DE_WorldSettings::CreateForCurentWorld();
		
		self::$xpModK1 = $settings->get('BATTLEXP_NEW_MODK1');
		self::_debugMsg("xpModK1 = ".self::$xpModK1);
		
		self::$xpModK2 = $settings->get('BATTLEXP_NEW_MODK2');
		self::_debugMsg("xpModK2 = ".self::$xpModK2);
		
		self::$xpModK3 = $settings->get('BATTLEXP_NEW_MODK3');
		self::_debugMsg("xpModK3 = ".self::$xpModK3);
		
		self::$xpMultiplaer = $settings->get('BATTLEXP_NEW_XP_MULTIPLAYER');
		self::_debugMsg("xpMultiplaer = ".self::$xpMultiplaer);
		
		self::$minLosserKillDeltaForXP = $settings->get('BATTLEXP_NEW_MIN_LOSER_KILL_FOR_XP');
		self::_debugMsg("minLosserKillDeltaForXP = ".self::$minLosserKillDeltaForXP);
		
		self::$winnerCode = self::$battle->getWinnerCode()->value;
		self::_debugMsg("winnerCode = ".self::$winnerCode);
		
		self::$startDamage1 = 0;
		
		self::$startDamage2 = 0;
		
		self::$armyL1 = 0;
		
		self::$armyL2 = 0;
		
		self::$armyP1 = 0;
		
		self::$armyP2 = 0;
		
		self::$totlaRoundsCount = 0;
		
		self::$debugInfo = '';
		
		self::$attackerNeedXP = true;
		
		self::$defenderNeedXP = true;
		
		self::$result = array();
		
		self::$M1 = null;
	 	
		self::$M21 = null;
		
		self::$M22 = null;
		
		self::$LR1 = null;
		
		self::$LR2 = null;
		
		self::$M31 = null;
		
		self::$M32 = null;
		
		self::$M41 = null;
		
		self::$M42 = null;		
		
		self::_initAttacker();
		
		self::_initDefender();		

		self::_debugMsg("");	
		self::_debugMsg("Inited result: \n".print_r(self::$result, 1));	
	
	}
	
	static private function _debugMsg($msg)
	{				
		self::$debugInfo .= $msg."\n";
	}
}


?>