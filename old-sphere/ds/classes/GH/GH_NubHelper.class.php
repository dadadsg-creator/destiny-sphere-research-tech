<?php
/**
 * Класс, реализующий помощь новичкам
 *
 * @author vasmik - РРР.. Автор индийских кодов. Переписано все нафиг.
 */
class GH_NubHelper {

    /**
     * Контролллер доступа к базе данных
     * @var DS_Common
     */
    public static $common = null;

    /**
     * Обьек инстанса игрока
     * @var DE_UserInstance
     */
    public static $userInstance;


    /**
     * Список соответствий зданий, проверяемых на отсутствие для каждого шага помощи
     * @var array
     */
    private static $buildings = array();

    private static $helpLevelBuildings = array(
    2  => array("SAIL_FOREST" => 32, "SAIL_TRAP" => 11, "WELL_LADLER" => 39, "SOURCE" => 34),
    3  => array("SAIL_FOREST" => 32, "VILLAGE" => 38, "CITY" => 15, "RESIDENTIAL_BUILDING" => 9),
    4  => array("FARM" => 17, "PLANTATION" => 10, /*"SHELTER" => 8,*/ "CLONE_FARM" => 49),
    5  => array("KADERUM_FORGE" => 1,  "HUGE_KADERIUM_FORGE" => 40),
    6  => array("NANO_CRYSTALLIZER" => 2, "LARGE_NANO_CRYSTALLIZER" => 43),
    7  => array("RESEARCH_CENTER" => 29),
    8  => array("KADERIUM_PRESS" => 21),
    9  => array("ARCHEOLOGICAL_INSTITUTE" => 12),
    10 => array("MILITARY_BASE" => 26),
    11 => array("TRAINING_CAMP" => 36),
    12 => array("ARTEFACT_TOWER" => 13),
    13 => array("CENTRAL_GATE" => 6),
    15 => array("CRYSTAL_WEAVER" => 16),
    16 => array("PRODIUM_COLLECTOR" => 28),

    17 => array("HUGE_KADERIUM_FORGE_LIENSU" => 86, "LARGE_KADERIUM_FORGE" => 41, "HUGE_KADERIUM_FORGE" => 40,
    "HUGE_NANO_CRYSTALLIZER" => 44,  "LARGE_NANO_CRYSTALLIZER" => 43, "HUGE_NANO_CRYSTALLIZER_LIENSU" => 87,
    "WELL_LADLER" => 39, "SOURCE" => 34, "HYDRO_FARM" => 50,
    "TRAINING_CAMP2L" => 70, "TRAINING_CAMP2P" => 73, "TRAINING_CAMP2V" => 67, "HEAVY_WEAPON_MANUFACTURE" => 19,
    "LIGHT_WEAPON_MANUFACTURE"   => 22, "LIGHT_WEAPON_MANUFACTURE2L" => 71,
    "LIGHT_WEAPON_MANUFACTURE2P" => 74, "LIGHT_WEAPON_MANUFACTURE2V" => 68),

    18 => array("ALLIANCE_CONNECTOR" => 14),
    19 => array("SMALL_STASIS_STILO"=>51,"MEDIUM_STASIS_STILO"=>52,"SMALL_FOOD_STILO"=>53,
    "MEDIUM_FOOD_SILO" => 54, "SMALL_KADERIUM_SILO" => 55, "MEDIUM_KADERIUM_SILO" => 56,
    "SMALL_NANO_SILO"=>57,"MEDIUM_NANO_SILO"=>58,"SMALL_PRODIUM_SILO"=>59,
    "MEDIUM_PRODIUM_SILO"=>60,
    "SMALL_PRODIUM_DUMP"=>76, "SMALL_NANO_DUMP"=>77, "SMALL_KADERIUM_DUMP"=>78,
    "SMALL_FOOD_DUMP" => 79, "SMALL_STASIS_DUMP"=>80, "MEDIUM_PRODIUM_DUMP"=>81,
    "MEDIUM_NANO_DUMP"=>82, "MEDIUM_KADERIUM_DUMP"=>83, "MEDIUM_FOOD_DUMP"=>84,
    "MEDIUM_STASIS_DUMP"=>85),

    20 => array("MESH_BOILER" => 24),
    21 => array("RING_GATE" => 25),
    22 => array("MIND_FACTORY" => 27),
    23 => array("INTERRING_TRADE_CONNECTOR" => 20),
    24 => array("GONDOLA_TRANSPORTER" => 18),
    25 => array("HEAVY_WEAPON_MANUFACTURE" => 19, "HEAVY_WEAPON_MANUFACTURE2L" => 19, "HEAVY_WEAPON_MANUFACTURE2L" => 72, "HEAVY_WEAPON_MANUFACTURE2P" => 75, "HEAVY_WEAPON_MANUFACTURE2V" => 69),
    26 => array("COLONIZATION_CENTER" => 61),
    );

    public static  $iconsInfo = array
    ( 1=>array('building' => '', 'surface'=> 0, 'to_build'=>''),
    2 => array('building' => 'SAIL_TRAP', 'surface'=> 1, 'to_build'=>''),
    3 => array('building' => 'RESIDENTIAL_BUILDING', 'surface'=> 2, 'to_build'=>''),
    4 => array('building' => 'PLANTATION', 'surface'=> 2, 'to_build'=>''),
    5 => array('building' => 'KADERIUM_FORGE', 'surface'=> 3, 'to_build'=>''),
    6 => array('building' => 'NANO_CRYSTALLIZER', 'surface'=> 3, 'to_build'=>''),
    7 => array('building' => 'RESEARCH_CENTER', 'surface'=> 3, 'to_build'=>''),
    8 => array('building' => 'KADERIUM_PRESS', 'surface'=> 3, 'to_build'=>''),
    9 => array('building' => 'ARCHEOLOGICAL_INSTITUTE', 'surface'=> 2, 'to_build'=>''),
    10 => array('building' => 'MILITARY_BASE', 'surface'=> 3, 'to_build'=>''),
    11 => array('building' => 'TRAINING_CAMP', 'surface'=> 3, 'to_build'=>''),
    12 => array('building' => 'ARTEFACT_TOWER', 'surface'=> 3, 'to_build'=>''),
    13 => array('building' => 'CENTRAL_GATE_WRECK', 'surface'=> 3, 'to_build'=>'CENTRAL_GATE'),
    14 => array('building' => 'CENTRAL_GATE', 'surface'=> 3, 'to_build'=>''),
    15 => array('building' => 'CRYSTAL_WEAVER', 'surface'=> 3, 'to_build'=>''),
    16 => array('building' => 'PRODIUM_COLLECTOR', 'surface'=> 3, 'to_build'=>''),
    17 => array('building' => 'KADERIUM_FORGE', 'surface'=> 3, 'to_build'=>'LARGE_KADERIUM_FORGE'),
    18 => array('building' => 'ALLIANCE_CONNECTOR', 'surface'=> 1, 'to_build'=>''),
    19 => array('building' => 'SMALL_KADERIUM_SILO', 'surface'=> 1, 'to_build'=>''),
    20 => array('building' => 'MESH_BOILER', 'surface'=> 3, 'to_build'=>''),
    21 => array('building' => 'RING_GATE', 'surface'=> 3, 'to_build'=>''),
    22 => array('building' => 'MIND_FACTORY', 'surface'=> 5, 'to_build'=>''),
    23 => array('building' => 'INTERRING_TRADE_CONNECTOR', 'surface'=> 5, 'to_build'=>''),
    24 => array('building' => 'GONDOLA_TRANSPORTER', 'surface'=> 5, 'to_build'=>''),
    25 => array('building' => 'HEAVY_WEAPON_MANUFACTURE', 'surface'=> 5, 'to_build'=>''),
    26 => array('building' => 'COLONIZATION_CENTER', 'surface'=> 5, 'to_build'=>''),
    27 => array('building' => '', 'surface'=> 0, 'to_build'=>''),

    );

    public static function getIconInfo($level)
    {
        assert(array_key_exists($level, self::$iconsInfo));
        return self::$iconsInfo[$level];
    }

    /*********************************************************************************************/
    public static function getHelpCount()
    {
        if (self::$common == null) self::$common = DS_Common::CreateForCurentDB();

        $sql = "SELECT
                    count(*) as cnt 
                FROM 
                    ds_systemmessage 
                WHERE 
                    messagename LIKE '%NUB_HELP_LEVEL%'";
        $res = self::$common->getResultArray('NUB_HELP_LEVEL_COUNT', $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($res->errormsg));
        return $res[0]['cnt'];

    }

    public static function getHelpList($lang)
    {
        if (self::$common == null) self::$common = DS_Common::CreateForCurentDB();

        $sql = "SELECT
                    l.text 
                FROM 
                    ds_systemmessage AS m
                LEFT JOIN ds_localizedtextlanguage AS l ON l.id_localizedtext = m.id_sysmsg_localizedtext
                WHERE 
                    m.messagename LIKE '%NUB_HELP_LEVEL%'
                    AND 
                    l.id_language={$lang}
                ORDER BY CAST(substr(messagename, 16, 2) AS int)";
        $res = self::$common->getResultArray('NUB_HELP_LEVEL_LIST_'.$lang, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($res->errormsg));
        return $res;
    }



    public static function GetHelpTitle($help_level, $lang)
    {
        $sql = "
                SELECT 
                    l.text 
                FROM 
                    ds_systemmessage AS m
               LEFT JOIN ds_localizedtextlanguage AS l 
               ON l.id_localizedtext = m.id_sysmsg_localizedtext
               WHERE 
                    m.messagename = 'NUB_HELP_LEVEL_{$help_level}' 
                    AND 
                    l.id_language={$lang}";

        $res = self::$common->getResultArray('NUB_HELP_LEVEL_TITLE_'.$help_level.'_'.$lang, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($res->errormsg));
        return $res[0]['text'];
    }

    /**
     * Возвращает локализованное текстовое сообщение помощи новичку
     *
     * @param $userInstanceId
     * @param $worldId
     * @param $languageId
     * @param $force
     * @return string
     */
    public static function getHelpMessage($userInstanceId, $worldId, $force = false)
    {
        if (self::$common == null) self::$common = DS_Common::CreateForCurentDB();

        self::$userInstance = DE_UserInstance::CreateFromID($userInstanceId);

        // Последняя показаннаы подсказка
        $last_step = self::$userInstance->getNubhelplevel();

        // Если мы еще ни разу не показывали подсказку, то выводим приветствие
        if ($last_step == 0) {
            $current_step = 1;
        } else {
            // Получаем здания на соте игрока
            self::getCombBuildings($userInstanceId, $worldId);
            // Шаг, который дожны вывести
            $current_step = self::getNextStep($userInstanceId, $worldId, $last_step, self::$userInstance->getHelp_var(), $force);   
        }
        
        // Если мы достигли новой подсказки или у нас одна из рандомных непоказнных подсказок
        if ($current_step > 0 && ($current_step > $last_step || $current_step==12 && $last_step==23 || in_array($current_step, array(17, 18, 19, 24, 25, 26))))
        {
            // Обновляем последнюю показанную подсказку
            self::$userInstance->setNubhelplevel($current_step);

            $params = array();

            // Если у нас первый шаг
            if ($current_step == 1) {
                // Устанавливаем параметры
                $params = array(0 => $_SESSION['s_login'], 1 => DE_World::CreateForCurrentWorld()->getName());
            }
            
            // Возвращаем текст, который надо показать
            return self::getHelpText($current_step, $params);
        }

        // Ничего не выводим
        return "";
    }


    /*********************************************************************************************/

    private static function checkGateIsOpen($userInstanceId, $worldId)
    {
        $sql = "SELECT
                        active
                    FROM
                        ds_user u
                        INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
                        INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
                        INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
                        INNER JOIN ds_building b ON (attb.id_building = b.id_building)
                    WHERE
                        ui.id_userinstance = '".(int)$userInstanceId."'
                        AND
                        b.id_building = 6 AND ui.id_world = ".(int)$worldId;

        $result = self::$common->getResultArray('', $sql);
        if (!$result)
        return false;

        if ($result[0]['active'] == 'f')
        return false;
        return true;
    }
    /*********************************************************************************************/

    /**
     * Получаем следующий шаг помощи
     *
     * @param $userInstanceId
     * @param $worldId
     * @param $current
     * @return int
     */
    private static function getNextStep($userInstanceId, $worldId, $current, $help_var, $force = false)
    {   
        $startSteps = array (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 23, 12, 13);
        if (in_array($current, $startSteps))
        {
            $ret = 14;
            foreach($startSteps as $i)
            {
                if($i > 1 && !self::checkBuildingsInScope(self::$helpLevelBuildings[$i]))
                {
                    $ret = $i;
                    break;
                }
            }

            return $ret;
        }
        else
        {
            $ret = 0;
            if ($current==14)
            $current = 15;

            if (($current >= 15) && ($current <= 19))
            {
                //проверим на 15 подсказку
                if (!self::checkBuildingsInScope(self::$helpLevelBuildings[15]))
                {
                    return   15;
                }


                //проверим на 16 подсказку
                if (self::checkGateIsOpen($userInstanceId, $worldId))
                {
                    if (!self::checkBuildingsInScope(self::$helpLevelBuildings[16]))
                    {
                        return 16;
                    }
                }
                else
                {
                }

                //подсказки с 17 по 19 проверяются особо
                $ret = self::getSpecialHelp($help_var, array(17, 18, 19), $force);
                if ($ret > 0)
                return $ret;

                if ($ret==-1)
                return 20;
            }
            else if (($current>=20)  && ($current <=27))
            {
                //подсказки с 20 по 22 - обычным образом
                for($i = 20; $i <= 22; $i++)
                {
                    if (!self::checkBuildingsInScope(self::$helpLevelBuildings[$i]))
                    {
                        return $i;
                    }
                }

                //Если уже есть КЦ, то показываем последнюю подсказку
                if (self::checkBuildingsInScope(array("COLONIZATION_CENTER" => 61)))
                return 27;

                //подсказки с 24 по 26 - особо
                $ret = self::getSpecialHelp($help_var, array(24, 25, 26), $force);
                if ($ret > 0)
                return $ret;

                if ($ret == -1)
                return 27;
            }
        }
        
        return 0;
    }

    private static function getSpecialHelp($help_var, $helparr, $force = false)
    {
        $actualhelp = array();
        $ret = 0;

        foreach ($helparr as $h)
        {
            if (!self::checkBuildingsInScope(self::$helpLevelBuildings[$h]))
            array_push($actualhelp, $h);
        }

        if (!$actualhelp && $help_var) //все подсказки уже неактуальны, обнулим help_var - но только в
        //том случае, если она ещё не обнулена
        {
            self::$userInstance->setHelp_var('');
        } else if ($actualhelp) {
            //получим список уже показанных подсказок
            $showed_help = explode(',', $help_var); 

            //получим список тех подсказок, которых нет в $showed_help
            $not_showed = array_diff($actualhelp, $showed_help);

            //случайно выберем подсказку, которую будем показывать
            if ($not_showed)
            {
                $ret = $not_showed[array_rand($not_showed, 1)];
    
                $help_var .= $ret.",";

                /*
                //Показываем подсказки поочерёдно, пока их не останется одна
                //Как только одна - она показывается 1 раз, а в help_var пишем, что все
                //показали
                if (count($actualhelp)==1)
                $help_var = implode(',',$helparr);
                else
                $help_var = $ret; //Иначе записываем только текущую подсказку
                //Это гарантирует нам, что мы не покажем её 2 раза подряд
                */

                self::$userInstance->setHelp_var($help_var);
            }
        }

        if (!$actualhelp)
        $ret = -1;

        if ($force && ($ret==0) && $showed_help)
        {
            if ($showed_help[count($showed_help)-1])
            {
                $ret = array_pop($showed_help);
            }
            else
            {
                $ret = array_pop($showed_help);
                $ret = array_pop($showed_help);
            }
        }

        return $ret;
    }

    private static function getCombBuildings($userInstanceId, $worldId)
    {
        $sql = "SELECT
                    distinct b.id_building
                FROM
                    ds_user u
                    INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
                    INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
                    INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
                    INNER JOIN ds_building b ON (attb.id_building = b.id_building)". // AND attb.underconstruction = FALSE)
                    "WHERE
                    ui.id_userinstance = '".(int)$userInstanceId."'
                     AND
                    ui.id_world = ".(int)$worldId;

                    $result = self::$common->getResultArray('', $sql);
                    assert(empty(self::$common->errormsg));
                    if ($result)
                    {
                        foreach ($result as $r)
                        {
                            array_push(self::$buildings, $r['id_building']);
                        }
                    }
    }

    /**
     * Проверка на наличие зданий у юзеринстанса в мире
     *
     * @param int $userInstanceId
     * @param int $worldId
     * @param array $buildings
     * @return boolean
     */
    private static function checkBuildingsInScope($bld)
    {
        foreach ($bld as $v)
        {
            if (in_array($v, self::$buildings))
            return true;
        }
        return false;
    }

    /**
     * Возвращяет локализованный текст помощи для конкретного шага помощи
     *
     * @param string $label
     * @param int $languageId
     * @param array $params
     * @return string
     */
    private static function getHelpText($level, $params = array())
    {
        if ($level == 27)
        $flashname =  "NUB_HELP_LEVEL_14";
        else
        $flashname =  "NUB_HELP_LEVEL_".$level;

        $helptext = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage("NUB_HELP_LEVEL_".$level, $params, null, null, false);
        $title = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage("TIP_OF_THE_DAY", $params, null, null, false);
        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<swfloader url=\"flash/".$flashname.".swf\" title=\"".$title."\"><txt><![CDATA[".urlencode($helptext)."]]></txt></swloader>";
    }
}


?>