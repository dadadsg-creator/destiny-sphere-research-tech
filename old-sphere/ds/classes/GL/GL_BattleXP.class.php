<?php

class GL_BattleXP
{

    /**
     * Объект с инвормацией по бою
     *
     * @var DE_Battle
     */
    static private $battle;
    
    /**
     * Текущий обрабатываемый раунд
     *
     * @var DE_BattleRound
     */
    static private $currentRound;
    
    static private $Ka;
    
    static private $K;
    
    static private $init = false;
    
    /**
     * Массив с результатми раундов
     *
     * @var array
     */
    static private $result = array();   
        
    /**
     * Строка с тестовыми выводами
     *
     * @var string
     */ 
    static public $debugInfo;
    
    static private $roundsInfo = array();
    
    static public function Count(DE_Battle $battle)
    {
        self::_init();
        
        self::$battle = $battle;
        
        self::_prepareArmysWeights();
        
        self::_countRoundsXP();
        
        $finalXP = self::_getFinalXP();
        
        self::_debugMsg('======== END BATTLE XP');
        
        DE_BattleSystemLog::Insert(self::$debugInfo);
        
        return $finalXP;
    }
    
    static private function _init()
    {
        if(!self::$init)
        {
            self::$Ka = (float)DE_WorldSettings::CreateForCurentWorld()->get('BATTLEXP_PROD_MULTIPLIER');
            assert(self::$Ka > 0);
        
            self::$K = (float)DE_WorldSettings::CreateForCurentWorld()->get('BATTLEXP_PROD_DIVISOR');
            assert(self::$K > 1000);
        
            self::$init = true;
        }
        
        self::$battle = null;
        
        self::$currentRound = null;
        
        self::$debugInfo = '======== START BATTLE XP';
            
        self::$result = array();
        
        self::$roundsInfo = array();
    }
    
    static private function _prepareArmysWeights()
    {
        $roundsCollection =  self::$battle->getBattleRoundCollection();
        $roundsCollection->resetNext(); 
        
        while(self::$currentRound = $roundsCollection->next())
        {
            self::$roundsInfo[self::$currentRound->getRoundNumber()] = self::_getRoundArmiesWeights();
        }
    }
    
    /**
     * Расчет боевого опыта во всех раундах
     *
     */
    static private function _countRoundsXP()
    {
        $roundsCollection =  self::$battle->getBattleRoundCollection();
        $roundsCollection->resetNext(); 
        
        while(self::$currentRound = $roundsCollection->next())
        {
            self::_debugMsg('');
            self::_debugMsg('START CALC ROUND '.self::$currentRound->getRoundNumber());
            
            if(self::$currentRound->getRoundNumber() == 0)
            {
                continue;
            }
            
            self::_getRoundXP();
        }
    }
    
    /**
     * Боевой опыт за раунд
     *
     */
    static private function _getRoundXP()
    {
        // веса обороняющихся берем из предыдущего раунда
        $defendersWeights = self::$roundsInfo[self::$currentRound->getRoundNumber() - 1]['DEFENDER_WEIGHT'];
        self::_debugMsg("defendersWeights = ".print_r($defendersWeights, 1));
        
        $defenderLoses = self::$roundsInfo[self::$currentRound->getRoundNumber()]['DEFENDER_LOSES'];
        self::_debugMsg("defenderLoses = $defenderLoses");
        
        $attackerLoses = self::$roundsInfo[self::$currentRound->getRoundNumber()]['ATTACKER_LOSES'];
        self::_debugMsg("attackerLoses = $attackerLoses");
            
        $sumDefenderWeight = 0;
        
        foreach($defendersWeights as $k => $weight)
        {
            $sumDefenderWeight += $weight;
        }
        
        if($sumDefenderWeight == 0)
        {
            $sumDefenderWeight = 0.001;
        }

        self::_debugMsg("sumDefenderWeight = $sumDefenderWeight ");
        
        $defenderRoundXP = array();
        foreach ($defendersWeights as $id => $weight)
        {
            $defenderRoundXP[$id] = ($weight / $sumDefenderWeight) * self::$Ka * $attackerLoses / self::$K;
        }
        
        $attackerRoundXP = self::$Ka * $defenderLoses / self::$K;
                
        self::$result[self::$currentRound->getRoundNumber()] = array(
                
                'ATTACKER' => $attackerRoundXP,
                'DEFENDER' => $defenderRoundXP
            );
        
        self::_debugMsg('');
        self::_debugMsg('ROUND XP: '.print_r(self::$result[self::$currentRound->getRoundNumber()], 1));
    }
    
    /**
     * Суммируем итоговый опыт за бой по всем раундам
     *
     * @return array
     */
    static private function _getFinalXP()
    {
        $result = array(
                'ATTACKER' => 0,
                'DEFENDER' => array(),
            );
        
        foreach(self::$result as $roundNumber => $roundXP)
        {
            $result['ATTACKER'] += $roundXP['ATTACKER'];
            
            foreach ($roundXP['DEFENDER'] as $id => $xp)
            {
                if(key_exists($id, $result['DEFENDER']))
                {
                    $result['DEFENDER'][$id] += $xp;
                } else {
                    $result['DEFENDER'][$id] = $xp;
                }
            }
        }

        // Бой на радужной соте
        if (self::$battle->getComb()->getUserInstance()->getUser()->getIsUserNPC() == 't' ||
            self::$battle->getFromComb()->getUserInstance()->getUser()->getIsUserNPC() == 't')
            $mod = (float) DE_WorldSettings::CreateForCurentWorld()->get('RAINBOW_COMBS_BATTLEXP_MOD');
        else
            $mod = 1;

        $result['ATTACKER'] = round($result['ATTACKER'] * $mod, 4);
        foreach ($result['DEFENDER'] as $id => $xp)
        {
            $result['DEFENDER'][$id] = round($xp * $mod, 4);
        }
        
        self::_debugMsg('');
        self::_debugMsg('FINAL XP: '.print_r($result, 1));
        
        return $result;
    }
    
    /**
     * Расчет весов и потерь армий в текущем раунде
     *
     * @return array
     */
    static private function _getRoundArmiesWeights()
    {
        $resultWeight = array(
                'ATTACKER_WEIGHT' => 0,
                'DEFENDER_WEIGHT' => array(),
                'ATTACKER_LOSES' => 0,
                'DEFENDER_LOSES' => 0,
            );
        
        $roundResultCollection = self::$currentRound->getBattleResultCollection();
        $roundResultCollection->resetNext();    

        while($result = $roundResultCollection->next())
        {
            //$key = $roundResultCollection->getCurrentPosition();
            
            $unit = $result->getUnit();
            
            /*
            // Не учитывать юниты, которые не наносфт повреждений (транспорты)
            if($unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX') == 0)
            {
                continue;
            }
            */
            
            $participant = $result->getPattleParticipant();
            
            if($participant->getParticipantFlag() == 'ATTACKER')
            {
                $resultWeight['ATTACKER_LOSES'] += $unit->getUnitPower() * floor($result->getLoses());
                
                $resultWeight['ATTACKER_WEIGHT'] += $unit->getUnitPower() * $result->getAmount();
            } else {
                if(key_exists($participant->getID(), $resultWeight['DEFENDER_WEIGHT']))
                {
                    $resultWeight['DEFENDER_WEIGHT'][$participant->getID()] += $unit->getUnitPower() * $result->getAmount();
                } else {
                    $resultWeight['DEFENDER_WEIGHT'][$participant->getID()] = $unit->getUnitPower() * $result->getAmount();
                }
                
                $resultWeight['DEFENDER_LOSES'] += $unit->getUnitPower() * floor($result->getLoses());
            }
            
            //$roundResultCollection->setPointerToPosition($key);
        }
        
        return $resultWeight;
    }
    
    static private function _debugMsg($msg)
    {               
        self::$debugInfo .= $msg."\n";
    }
    
}

?>