<?php

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

//define('DEBUG_MODE', 0);

if (VERSION_NAME=='RU_UNLIM') {
    $_SESSION["dbId"] = 0;
    $_SESSION["lang"] = 3;
    $_SESSION["world"] = 9;
} else if (VERSION_NAME=='LIMITED') {
    $_SESSION["dbId"] = 0;
    $_SESSION["lang"] = 3;
    $_SESSION["world"] = 6;
} else {
    $_SESSION["dbId"] = 3;
    $_SESSION["lang"] = 3;
    $_SESSION["world"] = 2;
}


require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/unit_efficiency.lang");

//SU_DebugTools::TimerStart('TEST');
//echo "<xmp>";
/*********** BEGIN WORK AREA **************/

class UnitEfficiency
{
    
    /**
     * ID юнита для которого рассчитываем эфективность
     *
     * @var int
     */
    private $id_unit;
    
    /**
     * ID рассы с юнитами которой сравниваем
     *
     * @var int
     */
    private $id_raceto;
    
    private $common = false;
    
    /**
     * Язык локализации
     *
     * @var int
     */
    private $lang;
    
    /**
     * Массив атрибутов юнитов. Для уменьшения количества обращений к базе.
     *
     * @var array
     */
    private $unitAttributes = array();
    
    
    /**
     * Список локализованных названий рас
     *
     * @var array
     */
    private $races = false;
    
    /**
     * Список локализованных названий религий
     *
     * @var array
     */
    private $religions = array();
    
    
    public $userReligion = 0;
    public $oponentReligion = 0;
    public $userRace = 0;
    public $oponentRace = 0;
    
    private $dbId = 0;

    private $unitEfficiencyRelation = array();
    
    /**
    * Бонусы
    *
    * @var array
    */
    private $bonus = array(
                "race"      => array("user"    => array("ATTACK" => 0, "DAMAGE" => 0, "DEFENCE" => 0, "HITPOINTS" => 0),
                                     "oponent" => array("ATTACK" => 0, "DAMAGE" => 0, "DEFENCE" => 0, "HITPOINTS" => 0)),
                "religion"  => array("user"    => array("ATTACK" => 0, "DAMAGE" => 0, "DEFENCE" => 0, "HITPOINTS" => 0),
                                     "oponent" => array("ATTACK" => 0, "DAMAGE" => 0, "DEFENCE" => 0, "HITPOINTS" => 0)),
            );
    
    
    function UnitEfficiency($lang = 3)
    {
        $this->lang = (int)$lang;
        $this->dbId = $_SESSION['dbId'];        
        $this->common = DS_Common::CreateForCurentDB($this->dbId);

        assert($this->common);      
    }
    
    function SetUnitId($id_unit)
    {
        settype($id_unit, "integer");
        
        if($id_unit < 1)
        {
            $id_unit = 3;
            //return false;
        }
    
        $forbiddenUnits =array(1, 2, 8, 83, 88);
        if(in_array($id_unit, $forbiddenUnits))
        {       
            $this->id_unit = 3;
            return false;
        }
        
    
        $sql = "
            SELECT
                ucbr.id_unit
            FROM
                ds_unitcanbebuildbyrace AS ucbr
            WHERE
                ucbr.id_unit = $id_unit
            ";      
        $res = $this->common->getResultArray("unitefficiency.unitcanbebuildbyrace.".$id_unit, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($this->common->errormsg));
        
        if(count($res) != 1)
        {
            $this->id_unit = 3;
            return false;
        }
        
        $this->id_unit = $id_unit;
        $this->userRace = $this->getUnitRaceByUnitId($this->id_unit);
        if(!$this->userRace)
        {
            return false;
        }
        
        return true;
    }
    
    function SetRace($race)
    {
        $this->id_raceto = (int)$race;
        
        if(!$this->id_raceto || ($this->id_raceto > count($this->getRaceList())))
        {
            $this->id_raceto = 1;
            //return false;
        }
        
        $this->oponentRace = $this->id_raceto;

        return true;
    }
    
    function setUserReligion($id_religion)
    {
        $id_religion = (int) $id_religion;
        if(($id_religion < 0) || ($id_religion >= count($this->getReligionList())))
        {
            $this->userReligion = 0;
            return false;
        }
        $this->userReligion = $id_religion;
        
        return $this->getBonus('user', 'religion');
    }
    
    function setOponentReligion($id_religion)
    {
        $id_religion = (int) $id_religion;
        if(($id_religion < 0) || ($id_religion >= count($this->getReligionList())))
        {
            $this->userReligion = 0;
            return false;
        }
        $this->oponentReligion = $id_religion;
        
        return $this->getBonus('oponent', 'religion');
    }
    
    function setUserRace($id_race)
    {
        $id_race = (int) $id_race;
        if(($id_race < 0) || ($id_race > count($this->getRaceList())))
        {
            $this->userRace = $this->getUnitRaceByUnitId($this->id_unit);
            return false;
        }
        $this->userRace = $id_race;
        
        return $this->getBonus('user', 'race');
    }
    
    function setOponentRace($id_race)
    {
        $id_race = (int) $id_race;
        if(($id_race < 0) || ($id_race > count($this->getRaceList())))
        {
            $this->userRace = $this->id_raceto;
            return false;
        }
        $this->oponentRace = $id_race;
        
        return $this->getBonus('oponent', 'race');
    }
    
    /**
     * Получение расы юнита по его ID
     *
     * @param int $id_unit
     * @return int
     */
    function getUnitRaceByUnitId($id_unit)
    {
        if(!$this->common)
        {
            return false;
        }
        
        $sql = "
            SELECT
                r.id_race
            FROM
                ds_race AS r
                INNER JOIN ds_unitcanbebuildbyrace AS ucbb ON ucbb.id_race = r.id_race
            WHERE
                ucbb.id_unit = $id_unit
            ";
        
        $k = $this->common->getResultArray("", $sql, "", "", "");
        
        if($k)
        {
            return $k[0]['id_race'];
        } else {
            return 0;
        }
    }
    
    function getCacheMark()
    {
        return "unit_efficiency_".$this->id_unit."_vsrace".$this->id_raceto."_religion".$this->userReligion.$this->oponentReligion."_race".$this->userRace.$this->oponentRace;
    }
    
    function checkCache()
    {
        $cache = new DS_Cache();
        
        $this->unitEfficiencyRelation = $cache->get($this->getCacheMark());
        
        if($this->unitEfficiencyRelation !== false)
        {
            return true;
        }
        
        return false;
    }
    
    function setCache()
    {
        $cache = new DS_Cache();
        
        // кешируем на 5 часов
        $cache->set($this->getCacheMark(), 18000, $this->unitEfficiencyRelation);
    }
    
    /**
     * Получение массива со значениями эффективности юнита
     *
     * @return array
     */
    function getUnitEfficiencyList()
    {
        if($this->checkCache())
        {
            return $this->unitEfficiencyRelation;
        }
        
        $this->common->setDbId($this->dbId);
        
        $vsRaceUnits = $this->getRaceUnits($this->id_raceto);
        
        $this->unitEfficiencyRelation = array();        
        
        foreach($vsRaceUnits as $k => $unit)
        {
            $eff = $this->getUnitRelation($unit['id_unit']);
            $this->unitEfficiencyRelation[$unit['text']]['eff'] = $this->getColorByEfficiency($eff);
            $this->unitEfficiencyRelation[$unit['text']]['num'] = $eff;
            $this->unitEfficiencyRelation[$unit['text']]['file'] = $unit['unitname'];
            $this->unitEfficiencyRelation[$unit['text']]['k_attack'] = $this->getUnitK($this->id_unit, $unit['id_unit']);
            $this->unitEfficiencyRelation[$unit['text']]['k_defence'] = $this->getUnitK($unit['id_unit'], $this->id_unit);
            
            $fate = $this->getUnitFate($this->id_unit, $unit['id_unit']);
        
            $this->unitEfficiencyRelation[$unit['text']]['fate_attack'] = round(100*$fate['attack']);
            $this->unitEfficiencyRelation[$unit['text']]['fate_defence'] = round(100*$fate['defence']);
        }
        

        $this->setCache();
    
        return $this->unitEfficiencyRelation;
    }
    
    
    /**
     * Получение списка локализованных названий рас
     *
     * @return bool
     */
    function getRaceList()
    {
        if($this->races)
        {
            return $this->races;
        }
        
        if(!$this->common)
        {
            return false;
        }
        
        $sql = "
            SELECT
                l.text, r.id_race
            FROM
                ds_race AS r
                INNER JOIN ds_localizedtextlanguage AS l ON l.id_localizedtext = r.id_localizedname
            WHERE 
                l.id_language = {$this->lang} 
            ";
        $k = $this->common->getResultArray("unitefficiency.racelist".$this->lang, $sql, "", "", "GLOBAL_CACHE");
        if($k)
        {
            foreach($k as $key => $val)
            {
                $res[$val['id_race']] = $val['text'];
            }
            
            $res[0] = '-';
            
            ksort($res);
            
            //$this->userRace = $this->oponentRace = 0;
            
            $this->races = $res;
            
            return($res);
        }
        return false;
    }
    
    /**
     * Получение списка религий
     *
     * @return bool
     */
    function getReligionList()
    {
        if($this->religions)
        {
            return $this->religions;
        }
        
        if(!$this->common)
        {
            return false;
        }
        
        $sql = "
            SELECT
                l.text, r.id_religion
            FROM
                ds_religion AS r
                INNER JOIN ds_localizedtextlanguage AS l ON l.id_localizedtext = r.id_localizedname
            WHERE 
                l.id_language = {$this->lang} 
            ";
        
        $k = $this->common->getResultArray("unitefficiency.religionlist".$this->lang, $sql, "", "", "GLOBAL_CACHE");
        
        if($k)
        {
            foreach($k as $key => $val)
            {
                $res[$val['id_religion']] = $val['text'];
            }
            
            $res[0] = '-';
            ksort($res);
            $this->religions = $res;
            
            $this->userReligion = $this->oponentReligion = 0;
            
            return($res);
        }
        
        return false;
    }
    
    /**
     * Получение значений бонусов из БД
     *
     * @param string $side
     * @param string $type
     * @return bool
     */
    function getBonus($side, $type)
    {
        if(($side != 'user') && ($side != 'oponent'))
        {
            return false;
        }
        
        if(($type != 'race') && ($type != 'religion'))
        {
            return false;
        }
        
        $attr = false;
        
        if($type == 'religion')
        {
            $param = $side."Religion";
            $bonus = $side."ReligionBonus"; 
            
            if($this->$param)
            {
                $attr = new DE_ReligionAttributes($this->$param);
            } else {
                return false;
            }
        } else {
            $param = $side."Race";
            $bonus = $side."RaceBonus"; 
            
            if($this->$param)
            {
                $attr = new DE_RaceAttributes($this->$param);
            } else {
                return false;
            }
        }
                
        if(!$attr)
        {   
            return false;
        }
        
        if($attr->haveAttribute('BONUS_RELATIVE', 'ATTACK'))
        {
            $this->bonus[$type][$side]['ATTACK'] = $attr->getAttribute('BONUS_RELATIVE', 'ATTACK');
        }
        if($attr->haveAttribute('BONUS_RELATIVE', 'DAMAGE'))
        {
            $this->bonus[$type][$side]['DAMAGE'] = $attr->getAttribute('BONUS_RELATIVE', 'DAMAGE');
        }
        if($attr->haveAttribute('BONUS_RELATIVE', 'DEFENCE'))
        {
            $this->bonus[$type][$side]['DEFENCE'] = $attr->getAttribute('BONUS_RELATIVE', 'DEFENCE');
        }
        if($attr->haveAttribute('BONUS_RELATIVE', 'HITPOINTS'))
        {
            $this->bonus[$type][$side]['HITPOINTS'] = $attr->getAttribute('BONUS_RELATIVE', 'HITPOINTS');
        }   
        
        return true;
    }
    
    /**
     * Рассчет эффективности юнита
     *
     * @param int $id_unitfrom
     * @param int $id_unitto
     * @return float
     */
    function getUnitEfficiency($id_unitfrom, $id_unitto)
    {
        $damage = $this->getUnitAttribute($id_unitfrom, 'DAMAGE');
        
        if(!$damage)
        {
            $damage = 0.5 * ($this->getUnitAttribute($id_unitfrom, 'DAMAGE_MIN') + $this->getUnitAttribute($id_unitfrom, 'DAMAGE_MAX'));
        }

        $defence = $this->getUnitAttribute($id_unitfrom, 'DEFENCE');
        
        if($id_unitfrom == $this->id_unit)
        {
            $raceBonus = $this->bonus['race']['user'];
            $religionBonus = $this->bonus['religion']['user'];
        } else {
            $raceBonus = $this->bonus['race']['oponent'];
            $religionBonus = $this->bonus['religion']['oponent'];
        }
        
        // Учитываем бонусы расы и религии
        $attack = $this->getUnitAttribute($id_unitfrom, 'ATTACK')*(1 + $raceBonus['ATTACK'] + $religionBonus['ATTACK']);
        $hitPoints = $this->getUnitAttribute($id_unitfrom, 'HITPOINTS')*(1 + $raceBonus['HITPOINTS'] + $religionBonus['HITPOINTS']);
        $damage = $damage*(1 + $raceBonus['DAMAGE'] + $religionBonus['DAMAGE']);
        // защиту считаем с регрессией
        $defence += (1 - $defence)*$raceBonus['DEFENCE'];
        $defence += (1 - $defence)*$religionBonus['DEFENCE'];

        // еффективная броня
        $effectiveDefence = $hitPoints / (1 - $defence);    
        // еффективная атака
        $effectiveAttack = $attack * $damage;
        
        // коэффициенты юнит против юнита
        $k1 = $this->getUnitK($id_unitfrom, $id_unitto);
        $k2 = $this->getUnitK($id_unitto, $id_unitfrom);
    
        if($k1 == 0)
        {
            return 0;
        }
        
        if($k2 == 0)
        {
            return 10;
        }
        
        // боеспособность
        $b = sqrt($effectiveDefence * $effectiveAttack * $k1 /*/ $k2*/) * 100;
        
        // требуемые на постройку ресурсы
        /*$kad    = (int) $this->getUnitAttribute($id_unitfrom, 'KADERIUM');
        $nano   = (int) $this->getUnitAttribute($id_unitfrom, 'NANO_CRYSTAL');
        $stasis = (int) $this->getUnitAttribute($id_unitfrom, 'STASIS_ENERGY');
        $food   = (int) $this->getUnitAttribute($id_unitfrom, 'FOOD');
        $prod   = (int) $this->getUnitAttribute($id_unitfrom, 'PRODIUM');*/
        
        // вес юнита в продиумном эквиваленте   
        //$unitWeight = $prod + 0.4*$nano + 0.2*$kad + 0.1*$stasis + 0.1*$food;
        $unitWeight = DE_Unit::CreateFromID((int)$id_unitfrom)->getUnitPower();

        // эффективность юнита
        return $b/$unitWeight;
    }
    
    
    /**
     * Расчет отношения эффективности двух юнитов
     *
     * @param int $id_unitto
     * @return float
     */
    function getUnitRelation($id_unitto)
    {   
        $unitEfficiency1 = $this->getUnitEfficiency($this->id_unit, $id_unitto);        
        $unitEfficiency2 = $this->getUnitEfficiency($id_unitto, $this->id_unit);
        if($unitEfficiency1 == 0 && $unitEfficiency2 == 0)
        {
            return 1;
        }
        
        if($unitEfficiency1 == 0)
        {
            return 0;
        }       
        if($unitEfficiency2 == 0)
        {
            return 10;
        }
        
        return $unitEfficiency1/$unitEfficiency2;
    }
    
    
    /**
     * Получение списка всех атрибутов юнита
     *
     * @param int $id_unit
     * @return bool
     */
    function getAllUnitAttributes($id_unit)
    {
        // если атрибуты этого юнита уже есть, то ничего не делаем
        if(key_exists($id_unit, $this->unitAttributes))
        {
            return true;
        }
        
        if(!$this->common)
        {
            return false;
        }
        
        // а если их еще нет то запрашиваем из БД
        $sql = "
                SELECT
                    an.attributename, ua.value
                FROM
                    ds_unithasattribute AS ua
                    INNER JOIN ds_attribute AS a ON ua.id_attribute = a.id_attribute
                    INNER JOIN ds_attributename AS an ON an.id_attributename = a.id_attributename
                    INNER JOIN ds_unit AS u ON ua.id_unit = u.id_unit
                WHERE
                    u.id_unit = $id_unit
            ";
        
        $k = $this->common->getResultArray("unitefficiency.allunitattributes.".$id_unit, $sql, "", "", "GLOBAL_CACHE");
        
        if(!$k)
        {
            return false;
        }
        
        foreach($k as $attr)
        {
            $this->unitAttributes[$id_unit][$attr['attributename']] = $attr['value'];
        }
        
        return true;
    }
    
    /**
     * Получение атрибута юнита
     *
     * @param int $id_unit
     * @param string $attr
     * @return unknown
     */
    function getUnitAttribute($id_unit, $attr)
    {           
        if(!$this->getAllUnitAttributes($id_unit))
        {
            return 0;
        }
        $attributes = $this->unitAttributes[$id_unit];
        
        if(key_exists($attr, $attributes))
        {
            return $attributes[$attr];
        } else {
            return 0;
        }
    }
    
    
    /**
     * Получение коэффициента атаки юнита против юнита
     *
     * @param int $id_unitfrom
     * @param int $id_unitto
     * @return float
     */
    function getUnitK($id_unitfrom, $id_unitto)
    {
        if(!$this->common)
        {
            return false;
        }
        
        $sql = "SELECT
                    vs.value as k
                FROM
                    ds_unitvsunit AS vs
                WHERE
                    vs.id_unitto = $id_unitto AND
                    vs.id_unitfrom = $id_unitfrom";
    
        $k = $this->common->getResultArray("unitefficiency.unitk.".$id_unitfrom.".".$id_unitto, $sql, "", "", "GLOBAL_CACHE");
    
        if(!count($k))
        {
            return 'no';
        } else {
            return $k[0]['k'];
        }
    }
    
    function getUnitWeight($id_unit)
    {
        // требуемые на постройку ресурсы
        /*$kad    = (int) $this->getUnitAttribute($id_unit, 'KADERIUM');
        $nano   = (int) $this->getUnitAttribute($id_unit, 'NANO_CRYSTAL');
        $stasis = (int) $this->getUnitAttribute($id_unit, 'STASIS_ENERGY');
        $food   = (int) $this->getUnitAttribute($id_unit, 'FOOD');
        $prod   = (int) $this->getUnitAttribute($id_unit, 'PRODIUM');
        
        // вес юнита в продиумном эквиваленте   
        $unitWeight = $prod + 0.4*$nano + 0.2*$kad + 0.1*$stasis + 0.1*$food;*/


        // вес юнита в продиумном эквиваленте   
        $unitWeight = DE_Unit::CreateFromID((int)$id_unit)->getUnitPower();
        
        // возвращаем вес в стазисе
        return 10*$unitWeight;
    }
    
    function getUnitFate($id_attackunit, $id_defenceunit)
    {
        $stasis = 10000000;
        
        $attackGroupCount = floor($stasis / $this->getUnitWeight($id_attackunit));
        $defenceGroupCount = floor($stasis / $this->getUnitWeight($id_defenceunit));
        
        $fate = $this->calcGroupDamage($id_attackunit, $attackGroupCount, $id_defenceunit, $defenceGroupCount);
        
        return $fate;
    }
    
    function getBattleBonus($side, $combId = 1)
    {
        assert($side == 'user' || $side == 'oponent');
        
        $raceBonus = $this->bonus['race'][$side];
        $religionBonus = $this->bonus['religion'][$side];
        $attackBonus = new DE_Bonus;
        $attackBonus->deltaRelativeBonus((float)$raceBonus['ATTACK']);
        $attackBonus->deltaRelativeBonus((float)$religionBonus['ATTACK']);
        $defenceBonus = new DE_Bonus;
        $defenceBonus->deltaRelativeBonus((float)$raceBonus['DEFENCE']);
        $defenceBonus->deltaRelativeBonus((float)$religionBonus['DEFENCE']);
        $damageBonus = new DE_Bonus;
        $damageBonus->deltaRelativeBonus((float)$raceBonus['DAMAGE']);
        $damageBonus->deltaRelativeBonus((float)$religionBonus['DAMAGE']);
        $hitpointsBonus = new DE_Bonus;
        $hitpointsBonus->deltaRelativeBonus((float)$raceBonus['HITPOINTS']);
        $hitpointsBonus->deltaRelativeBonus((float)$religionBonus['HITPOINTS']);
        
        if($side == 'user')
        {
            $id = '1_1_0';
        } else {
            $id = $combId.'_2_0';
        }
        $battleBonus = new DE_BattleBonuses($id);
        $battleBonus->setAttackBonus($attackBonus);
        $battleBonus->setDefendBonus($defenceBonus);
        $battleBonus->setDemageBonus($damageBonus);
        $battleBonus->setHitpointBonus($hitpointsBonus);
        
        return $battleBonus;
    }
    
    
    function calcGroupDamage($id_attackunit, $attackGroupCount, $id_defenceunit, $defenceGroupCount)
    {
        // ATTACK GROUP
        $data = array ( 'id_basecomb' => 1, 'id_userinstance' => 1, 'moveable' => 't', 
                        'inaction' => 't', 'isdefault' => 't', 'unitcontainername' => 'attack', 
                        'id_currentcomb' => 1, 'id_eventqueue'  => 1 );
        $attackArmy = GB_SingletonObjectStorage::GetSingleton('DE_Army', 1, $data);
        
        $attackBattleBonus = $this->getBattleBonus('user');
        $attackArmy->setBonus($attackBattleBonus);

        $data = array('id_unit' => $id_attackunit, 'id_army' => 1, 'amount' => $attackGroupCount);
        $attackGroup = GB_SingletonObjectStorage::GetSingleton('DE_UnitsGroup', 1, $data);
        
        $attackGroupWarPower = new DE_UnitsGroupWarPower($attackGroup, $attackBattleBonus);
        
        // DEFENCE GROUP
        $data = array ( 'id_basecomb' => 100 + (int)$id_defenceunit, 'id_userinstance' => 2, 'moveable' => 't', 
                        'inaction' => 't', 'isdefault' => 't', 'unitcontainername' => 'defence_'.(int)$id_defenceunit, 
                        'id_currentcomb' => 100 + (int)$id_defenceunit, 'id_eventqueue'     => 100 + (int)$id_defenceunit );
                        
        $defenceArmy = GB_SingletonObjectStorage::GetSingleton('DE_Army', 100 + (int)$id_defenceunit, $data);
        
        $defenceBattleBonus = $this->getBattleBonus('oponent', 100 + (int)$id_defenceunit);
        $defenceArmy->setBonus($defenceBattleBonus);        
        
        $data = array('id_unit' => $id_defenceunit, 'id_army' => 100 + (int)$id_defenceunit, 'amount' => $defenceGroupCount);
        $defenceGroup = GB_SingletonObjectStorage::GetSingleton('DE_UnitsGroup', 100 + (int)$id_defenceunit, $data);

        $defenceGroupWarPower = new DE_UnitsGroupWarPower($defenceGroup, $defenceBattleBonus);

        // damage
        $damageDefence = $this->CountDamage($attackGroupWarPower,  $defenceGroup);
        $damageAttack  = $this->CountDamage($defenceGroupWarPower, $attackGroup);
        
        $attackLost  = GL_CountUnitGroupLoses::Count($damageAttack,  $attackGroup);
        $defenceLost = GL_CountUnitGroupLoses::Count($damageDefence, $defenceGroup);
        
        $attackLeft = $attackGroupCount - $attackLost;
        $defenceLeft = $defenceGroupCount - $defenceLost;
        
        $attackFate = round($attackLeft / $attackGroupCount, 2);
        if ($defenceGroupCount)
            $defenceFate = round($defenceLeft / $defenceGroupCount, 2);
        else
            $defenceFate = 0;
        $strD = floor($defenceLost)." | ".$defenceGroupCount;
        $strA = floor($attackLost)." | ".$attackGroupCount;
        
        $fate = array('attack' =>  $attackFate, 'defence' => $defenceFate);
        return $fate;
    }
    
    function CountDamage(DE_UnitsGroupWarPower $hitedWarPower, DE_UnitsGroupInterface $unitsGroup)
    {
        $hitAttack = $hitedWarPower->getAttack();
        $hitDamage = $hitedWarPower->getDemage();
        $unitVsUnitBonus = $hitedWarPower->getUnitsGroup()->getUnit()->getUnitVsUnitBonus($unitsGroup->getUnit());
        $reciveDefend = $unitsGroup->getGroupWarPower()->getDefend();
        
        // Это затыка
        $recivedSeporator = 1;

        return ($hitAttack - ($hitAttack * $reciveDefend)) * ($hitDamage * $recivedSeporator) * $unitVsUnitBonus;
    }
    
    /**
     * Получение локализованного имени юнита
     *
     * @param int $id_unit
     * @return string
     */
    function getUnitLocalizedName($id_unit)
    {
        if(!$this->common)
        {
            return false;
        }
        
        $sql = "
            SELECT
                un.text AS name,
                u.unitname AS img
            FROM
                ds_unit as u
                INNER JOIN ds_localizedtextlanguage AS un ON un.id_localizedtext = u.id_localizedname
            WHERE
                u.id_unit = ".$id_unit." AND
                un.id_language = ".$this->lang."
            ";
        
        $k = $this->common->getResultArray("unitefficiency.unitlocalizedname.id_unit.id_lang".$id_unit.$this->lang, $sql, "", "", "GLOBAL_CACHE");
    
        if(!$k)
        {
            return false;
        } else {
            return $k[0];
        }
    }
    
    /**
     * Получение списка юнитов рассы
     *
     * @return array
     */
    function getRaceUnits($race)
    {
        if(!$this->common)
        {
            return false;
        }
        
        $sql = "
                SELECT
                    u1.id_unit, 
                    un.text, 
                    u2.unitname
                FROM
                    ds_unitcanbebuildbyrace as u1
                    INNER JOIN ds_unit AS u2 ON u1.id_unit = u2.id_unit
                    INNER JOIN ds_localizedtextlanguage AS un ON un.id_localizedtext = u2.id_localizedname
                WHERE
                    u1.id_race = ".$race." AND
                    u1.id_unit NOT IN (1,2,8,83, 88, 90, 91, 92, 93, 94, 95, 96, 97, 98) AND
                    un.id_language = ".$this->lang."
                ORDER BY
                    u1.id_unit";
    
        return $this->common->getResultArray("unitefficiency.raceunits.race.lang.".$race.$this->lang, $sql, "", "", "GLOBAL_CACHE");
    }
    
    function getAllUnits()
    {
        if(!$this->common)
        {
            return false;
        }
        
        $sql = "
                SELECT
                    u1.id_unit, 
                    un.text, 
                    u2.unitname
                FROM
                    ds_unitcanbebuildbyrace as u1
                    INNER JOIN ds_unit AS u2 ON u1.id_unit = u2.id_unit
                    INNER JOIN ds_localizedtextlanguage AS un ON un.id_localizedtext = u2.id_localizedname
                WHERE
                    u1.id_unit NOT IN (1,2,8, 83, 88, 90, 91, 92, 93, 94, 95, 96, 97, 98) AND
                    un.id_language = ".$this->lang."
                ORDER BY
                    u1.id_race, u1.id_unit";
    
        return $this->common->getResultArray("unitefficiency.units.lang".$this->lang, $sql, "", "", "GLOBAL_CACHE");
    }
    
    
    /**
     * Получение локализованного названия рассы
     *
     * @param int $race
     * @return string
     */
    function getRaceLocalizedName($race)
    {
        if(!$this->common)
        {
            return false;
        }
        
        $sql = sprintf(
                "SELECT
                    un.text
                FROM
                    ds_race AS r
                    INNER JOIN ds_localizedtextlanguage AS un ON un.id_localizedtext = r.id_localizedname
                WHERE
                    r.id_race = %d AND
                    un.id_language = %d", $race, $this->lang);
    
        $k = $this->common->getResultArray("", $sql, "", "", "");
        if($k)
        {
            return $k[0]['text'];
        } else {
            return false;
        }
    }
    
    function getColorByEfficiency($efficiency)
    {
        $baseColors = array(
                0  => array('r'=>110,   'g'=>0,     'b'=>0),
                1  => array('r'=>255,   'g'=>0,     'b'=>0),
                2  => array('r'=>220,   'g'=>220,   'b'=>0),
                3  => array('r'=>0,     'g'=>255,   'b'=>0),
                4  => array('r'=>100,   'g'=>100,   'b'=>255),
            );
        $baseEfficiency = array(
                0 => 0.1,
                1 => 0.5,
                2 => 0.8,
                3 => 1.25,
                4 => 2.5
            );
            
        $fromColor = 0;
        $toColor = 0;       
        
        // get nearest colors
        for($i=0; $i<count($baseEfficiency); $i++)
        {
            if($efficiency <= $baseEfficiency[$i])
            {
                $toColor = $i;
                break;
            }
            $toColor = $i;
            $fromColor = $i;
        }

        if($fromColor == $toColor)
        {
            $weight = 1;
        } else {
            $weight = ($efficiency - $baseEfficiency[$fromColor]) / ($baseEfficiency[$toColor] - $baseEfficiency[$fromColor]);
        }
    
        $res = $this->blendColors($baseColors[$fromColor], $baseColors[$toColor], $weight);
        
        return "rgb(".$res['r'].",".$res['g'].",".$res['b'].")";
    }
    
    function blendColors($col1, $col2, $weight)
    {
        $res['r'] = round(($col2['r'] - $col1['r']) * $weight + $col1['r']);
        $res['g'] = round(($col2['g'] - $col1['g']) * $weight + $col1['g']);
        $res['b'] = round(($col2['b'] - $col1['b']) * $weight + $col1['b']);
        
        return $res;
    }
}

$tpl = new Smarty;
$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';   
$tpl->compile_check = true;
$tpl->debugging = false;

$unit = 0;

$unitEfficiency = new UnitEfficiency(constant("DEFAULT_LANGUAGE"));

if(isset($_GET['unit']))
{
    $unit = (int) $_GET['unit'];    
} else {
    $unit = 3;  
}

if(!$unitEfficiency->SetUnitId($unit))
{
    $unit = 3;
}

if(isset($_GET['race']))
{
    $race = (int) $_GET['race'];    
} else {
    $race = 1;
}

if(!$unitEfficiency->SetRace($race))
{
    $race = 0;
}
    

if($unit && $race > 0 && $race < 4)
{
    $tpl->assign("UNIT_ID", $unit);
    $tpl->assign("RACE", $race);
    
    if(isset($_REQUEST['user_race']))
    {
        $unitEfficiency->setUserRace($_REQUEST['user_race']);
    }
    
    if(isset($_REQUEST['oponent_race']))
    {
        $unitEfficiency->setOponentRace($_REQUEST['oponent_race']);
    }
    
    if(isset($_REQUEST['user_religion']))
    {
        $unitEfficiency->setUserReligion($_REQUEST['user_religion']);
    }
    
    if(isset($_REQUEST['oponent_religion']))
    {
        $unitEfficiency->setOponentReligion($_REQUEST['oponent_religion']);
    }
    
    
    $unit = $unitEfficiency->getUnitLocalizedName($unit);
    $raceName = $unitEfficiency->getRaceLocalizedName($race);
    if($unit && $raceName)
    {
        $tpl->assign('UNIT', $unit);
        $tpl->assign('RACE_NAME', $raceName);
    
        $result = $unitEfficiency->getUnitEfficiencyList();
        $races = $unitEfficiency->getRaceList();
        $religions = $unitEfficiency->getReligionList();    
        
        $tpl->assign('RACES', $races);
        $tpl->assign('USER_RACE', $unitEfficiency->userRace);
        $tpl->assign('USER_RACE_UNITS', $unitEfficiency->getAllUnits());
        $tpl->assign('OPONENT_RACE', $unitEfficiency->oponentRace);
        $tpl->assign('RELIGIONS', $religions);
        $tpl->assign('USER_RELIGION', $unitEfficiency->userReligion);
        $tpl->assign('OPONENT_RELIGION', $unitEfficiency->oponentReligion);
        
//$fp = fopen('./csv/'.$unitName.'_vs_'.$raceName.'.csv', "w+");
//$str = '';
        
        $units = array();
        $i = 0; 
                
        foreach($result as $unitName => $unit)
        {
            $units[$i]['name'] = $unitName;
            $units[$i]['eff'] = $unit['eff'];
            $units[$i]['img'] = $unit['file'];
            $units[$i]['num'] = round($unit['num'], 2);
            $units[$i]['k_attack'] = round($unit['k_attack'], 2);
            $units[$i]['k_defence'] = round($unit['k_defence'], 2);
            $units[$i]['fate_attack'] = $unit['fate_attack'];
            $units[$i]['fate_defence'] = $unit['fate_defence'];
//$str .= $unit['num'].";";
            $i++;
        }
        
        $tpl->assign('UNITS', $units);
        
//fwrite($fp, $str);
//fclose($fp);
        
        $tpl->assign("lang",$lang);
        
        $tpl->display('unitEfficiency.tpl');

    }
} else {
    echo 'no unit or race selected';
}

/*********** END WORK AREA **************/
//echo "</xmp>";
//echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

//GB_SingletonObjectStorage::CommitAllCommitable();

?>