<?php $CVS_Version = '$Revision$ ; $Date$';

// методы общие для всех классов работающих со зданиями
class UA_Building extends UA_Action
{

    protected $buildingname;

    protected $bid;

    protected $attachedbid;

    protected $id_cell;

    public $groundid;

    function UA_Building()
    {
        UA_Action::UA_Action ();
    }

    // определяет параметры эвента
    function createEventParams ()
    {
        global $params;

        $this->eventparams["ID_Building"] = $this->bid;
        $this->eventparams["ID_Cell"] = $this->id_cell;
        $this->eventparams["ID_UserInstance"] = $params["uiid"];
        $this->eventparams["ID_Language"] = $params["lang"];
        $this->eventparams["ID_Comb"] = $this->comb;
        $this->eventparams["ID_AttachedBuilding"] = $this->attachedbid;

        return true;
    }

    // можно ли строить здание на ячейке
    function isAllowBuildOn ($bid, $id_cell, $groundtype )
    {

        $sql = sprintf ( "select bcbo.id_building
                from DS_Building b,
                DS_BuildingCanBeBuildOn bcbo,
                ds_cell ce
                where b.ID_Building = bcbo.ID_Building
                and b.ID_Building = %d
                and bcbo.ID_CellType = ce.id_celltype
                and bcbo.id_groundtype = ce.id_groundtype
                and ce.id_cell = %d",
        $bid, $id_cell);

        $res = $this->common->getResultArray ( "id_building_id_building_id_cell_".$bid."_".$id_cell, $sql, "", "", "" );

        assert( empty( $this->common->errormsg ) );

        if ( count( $res ) < 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isAllowBuildOn1";
            return false;
        }

        $res = "";

        return true;
    }

    // доступно ли здание расе
    function isBuildingAllowRace ( $bid, $id_race ) {

        $sql = sprintf ( "select id_race
                from DS_BuildingCanBuildByRace
                where id_building = %d
                and id_race = %d",
        $bid, $id_race);

        $res = $this->common->getResultArray ( "id_race_id_building_id_race_".$bid."_".$id_race, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        assert(empty($this->common->errormsg));

        if(count($res) < 1)
        {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isBuildingAllowRace1";
            return false;
        }

        return true;
    }

    // доступно ли здание
    function isDisabledBuilding ( $id_world, $bid )
    {
        //Проверяем не закры то ли строительство этого здания
        $sql = sprintf ( "
                select
                    id_building
                from
                    DS_DisabledBuilding
                where
                    id_world = %d
                    AND id_building = %d
                ",
        $id_world,
        $bid );
        $res = $this->common->getResultArray ( "id_world_disabled_id_building_".$id_world."_".$bid, $sql, "", "", "GLOBAL_CACHE" );
        assert( empty( $this->common->errormsg ) );

        if(count($res) < 1)
        {
            return false;
        }

        //Проверяем не закры то ли строительство этого здания на захватываемой колонии
        if (DE_Comb::CreateFromID((int)$this->comb)->getIsCapture()) {
            $sql = sprintf ( "
                select
                    id_building
                from
                    ds_disabledbuilding_colony
                where
                    id_world = %d
                    AND id_building = %d
                ",
            $id_world,
            $bid );

            $res = $this->common->getResultArray ( "id_world_disabled_colony_id_building_".$id_world."_".$bid, $sql, "", "", "GLOBAL_CACHE" );
            assert( empty( $this->common->errormsg ) );

            if(count($res) < 1)
            {
                return false;
            }
        }

        //Проверяем здание возможно копостройке или нет,
        $sql = "
            SELECT
            (
                SELECT 1
                FROM ds_building
                WHERE 
                    id_building = ".$bid."
                    AND
                    buildable = false
            ) AS unbuildable,
            (
                SELECT 1
                FROM ds_buildingsalowedtouserinstance
                WHERE
                    id_building = ".$bid."
                    AND
                    id_userinstance = ".$this->uiid."
            ) AS allowed";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        $unbuildable = (int)$res[0]['unbuildable'];
        $allowed = (int)$res[0]['allowed'];

        if($unbuildable == 0)
        {
            return true;
        }else{
            if($allowed == 1)
            {
                return true;
            }else{
                return false;
            }
        }
    }

    // корректный ли номер ячейки
    function isCellNumCorrect ($cellnum, $celltype) {

        if ( $cellnum < 1 || $cellnum > 217 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isCellNumCorrect1";
            return false;
        }

        return true;
    }

    // соблюдена ли уникальность здания
    function isUniqueTrue ( $combid, $buildid ) {

        $sql = sprintf ( "select isUniqueTrue_2 ( %d, %d ) as ut", $combid, $buildid );

        $res = $this->common->getResultArray ( "is_unique_true_id_comb_id_building_".$combid."_".$buildid, $sql, "", $this->uiid, "LOCAL_CACHE" );

        assert( empty( $this->common->errormsg ) );

        if ( count( $res ) < 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isUniqueTrue1";
            return false;
        }

        if ( $res[0]['ut'] != 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isUniqueTrue2";

            return false;
        }

        $res = "";

        return true;
    }

    // присутствуют ли необходимые здания
    function isNeedsBuildingPresent ( $combid, $bid ) {

        $sql = sprintf ( "select isNeedsBuildingPresent_2 (%d, %d) as bnb", $combid, $bid );

        $res = $this->common->getResultArray ( "isneedsbuildingpresent_id_comb_id_building_".$combid."_".$bid, $sql, "", "", "" );

        assert( empty( $this->common->errormsg ) );

        if ( count( $res ) < 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isNeedsBuildingPresent1";
            return false;
        }

        if ( $res[0]['bnb'] != 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isNeedsBuildingPresent2 ".$res[0]['bnb'];
            return false;
        }

        $res = "";

        return true;
    }

    // хватает ли ресурсов для строительства
    function isResourcesEnough($combid, $bid, &$lastTestResInfo)
    {
        // хватает ли на юниты ресурсов
        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$combid);
        $buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$bid);

        foreach(GB_ResourcesList::$allBuildResources as $k=>$resourceName)
        {
            if ($resourceName == 'SILVER_CREDIT' OR $resourceName == 'GOLD_CREDIT')
            {
                //Смотрим модифаер
                $worldSettings = DE_WorldSettings::CreateForCurentWorld();
                $modifier = $worldSettings->get('BUILDING_CAN_BUIL_BY_MONEY');

                //Проверяем модифаер
                assert($modifier == 1 || $modifier == 0);

                //Если в настройках мира у нас стоит запрет на постройку зданий за деньги
                if ($modifier == 0)
                {
                    continue;
                }

                //Получаем сведения об акаунте данного инстанса
                $userInstance = DE_UserInstance::CreateFromID($this->uiid);
                $member = MU_EntityOperationMember::CreateFromUserInstance($userInstance);
                $money = MU_SearchOperations::GetAccountData($member);

                //Проверяем, чтобы у юзверя был денежный аккаунт
                assert(!empty($money));

                //Если аккаунт неактивный, то здание строить незьзя
                if (!$money['IS_ACTIVE'])
                {
                    $disabledBuilding = true;
                    continue;
                }

                if ($resourceName == 'SILVER_CREDIT')
                {
                    $currentMoneyAccount = $money['sc_amount'];
                } elseif ($resourceName == 'GOLD_CREDIT')
                {
                    $currentMoneyAccount = $money['gc_amount'];
                }

                //Если количество денет, тредбуемое для постройки здания больше текущего количества денег
                if($buildingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName) > $currentMoneyAccount)
                {
                    $disabledBuilding = true;
                    continue;
                }
            }


            if(!$buildingAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
            {
                continue;
            }

            $prodValue = $buildingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);
            $combValue = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);

            if($combValue < $prodValue)
            {
                $building = DE_Building::CreateFromID((int)$bid);
                $comb = DE_Comb::CreateFromID((int)$combid);

                $lastTestResInfo = "FOR ".$comb->getName()." => ".$building->getName()." RESOURCE: $resourceName NEED: $prodValue HAVE: $combValue";
                return false;
            }
        }

        return true;
    }

    // хватает ли ресурсов для апгрейда
    function isResourcesEnoughToUpgrade ( $worldid, $combid, $bid )
    {
        $sql = sprintf ( "select isResourcesEnoughToUpgrade (%d, %d, %d) as ire", $worldid, $combid, $bid );

        $res = $this->common->getResultArray ( "isresourceenougn_id_world_id_comb_id_building_".$worldid."_".$combid."_".$bid, $sql, "", $this->uiid, "LOCAL_CACHE" );

        assert( empty( $this->common->errormsg ) );

        if ( count( $res ) < 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isResourcesEnoughToUpgrade1";
            return false;
        }

        if ( $res[0]['ire'] != 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isResourcesEnoughToUpgrade2";

            return false;
        }

        $res = "";

        return true;
    }

    // хватает ли науки для строительства
    function isScienceEnough($uiid, $bid)
    {

        $sql = sprintf ( "select isScienceEnough(%d, %d) as ise", $uiid, $bid );

        $res = $this->common->getResultArray ( "isscienceenougn_id_userinstance_id_building_".$uiid."_".$bid, $sql, "", $this->uiid, "LOCAL_CACHE" );

        assert( empty( $this->common->errormsg ) );

        if(count( $res) < 1)
        {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isScienceEnough1";
            return false;
        }

        if ( $res[0]['ise'] != 1 ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isScienceEnough2";

            return false;
        }

        $res = "";

        return true;
    }

    // добавить к закладке дочерние закладки
    function addChildTabs ( $id_tab, &$tab, $lang, $current )
    {
        global $common;

        if(key_exists('profi_game', $_SESSION) && $_SESSION['profi_game'])
        {
            $profiInjection = "";
        }else{
            $profiInjection = "AND sf.onlyforpremium = 'f'";
        }

        $sql = "SELECT
                    sf.id_specialfunction,
                    sf.specialfunctionname,
                    (
                        SELECT text
                        FROM
                            ds_localizedtext l,
                            ds_localizedtextlanguage ltl
                        WHERE
                                l.id_localizedtext = ltl.id_localizedtext
                            AND l.id_localizedtext = sf.id_localizedname
                            AND ltl.id_language = ".$lang."
                    ) AS localizedname,
                    sf.affected_by_modifier
                FROM
                    ds_specialfunction sf,
                    (
                        SELECT sfr.id_child
                        FROM
                            ds_specialfunction sf,
                            ds_specialfunctionrelation sfr
                        WHERE
                                sfr.id_parent = sf.id_specialfunction
                            AND sf.id_specialfunction = ".$id_tab."
                    ) AS t
                WHERE
                    sf.id_specialfunction = t.id_child
                    ".$profiInjection."
                ORDER BY
                    sf.id_specialfunction";
        $res = $common->getResultArray ( "id_specialfunction_specialfunctionname_id_language_id_tab".$lang."_".$id_tab."_".$_SESSION['profi_game'], $sql, "", "", "GLOBAL_CACHE" );
        assert(empty($common->errormsg));
        //SU_DebugTools::LogAll("sql=$sql\n res=".var_export($res, true),'peace.log');

        if ( $res === false)
        {
            //$this->errormsg = " SYSTEM ERROR - UA_Building::addChildTabs1";
            return true;
        }

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            if(!empty($res[$i]['affected_by_modifier']) && !DE_WorldSettings::CreateForCurentWorld()->get($res[$i]['affected_by_modifier']))
            {
                continue;
            }
            
            $ch = $tab->new_child ( "tab" );
            $ch->set_attribute ( "id", trim ( $res[$i]['specialfunctionname'] ) );
            $ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
            if ( $res[$i]['specialfunctionname'] == $current ) {
                $ch->set_attribute ( "cur", "1" );
            }
            $this->addChildTabs ( $res[$i]['id_specialfunction'], $ch, $lang, $current );
        }

        $res = "";

        return true;
    }

    // получить все закладки здания
    function getBuildingTabs(&$root, $bid, $lang, $current, $allIsCurrent = false)
    {
        global $common;

        if(key_exists('profi_game', $_SESSION) && $_SESSION['profi_game'])
        {
            $profiInjection = "";
        }else{
            $profiInjection = "AND sf.onlyforpremium = 'f'";
        }
        // Убираем вторую закладку с артами из военной базы по модифаеру
        if ($bid==26) {
            if (DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_OFFENSIVE_ARTEFACTS')==0)
                $profiInjection .= " AND sf.id_specialfunction!=41";
        }

        $sql = sprintf ( "
                SELECT
                    sf.id_specialfunction,
                    sf.specialfunctionname,
                    sf.tab_image,
                    (
                        SELECT text
                        FROM
                            ds_localizedtext l,
                            ds_localizedtextlanguage ltl
                        WHERE
                                l.id_localizedtext = ltl.id_localizedtext
                            AND l.id_localizedtext = sf.id_localizedname
                            AND ltl.id_language = %d
                    ) AS localizedname,
                    sf.url,
                    sf.affected_by_modifier
                FROM
                    ds_building b,
                    ds_buildinghasspecialfunction bhsf,
                    ds_specialfunction sf
                WHERE
                        b.id_building = bhsf.id_building
                    AND bhsf.id_specialfunction = sf.id_specialfunction
                    AND b.id_building = %d
                    AND sf.level = 1
                    ".$profiInjection."
                ORDER BY sf.id_specialfunction!=41, sf.id_specialfunction",
        $lang, $bid );
        $res = $common->getResultArray ( "id_specialfunction_specialfunctionname_id_language_id_building".$lang."_".$bid."_".$_SESSION['profi_game'], $sql, "", "FULL_PREFIX", "GLOBAL_CACHE" );
   // SU_DebugTools::LogAll($sql."\n res=".var_export($res, true),'tabs.log');
        if ( $res === false) {
            $this->errormsg = " SYSTEM ERROR - UA_Building::getBuildingTabs1";

            return false;
        }

        $ch = $root->new_child ( "alltabs" );
        //SU_DebugTools::LogAll("getBuildingTabs res=".var_export($res, true), 'peace.log');

        for ( $i=0; $i<sizeof ( $res ); $i++ )
        {

            if($allIsCurrent && ($res[$i]['specialfunctionname'] != $current))
            {
                continue;
            }

            if(!empty($res[$i]['affected_by_modifier']) && !DE_WorldSettings::CreateForCurentWorld()->get($res[$i]['affected_by_modifier']))
            {
                continue;
            }

            $ch2 = $ch->new_child ( "tab" );
            $ch2->set_attribute ( "id", trim ( $res[$i]['specialfunctionname'] ) );
            $ch2->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
            $ch2->set_attribute ( "icon", urlencode ( trim ( $res[$i]['tab_image'] ) ) );

            if(!empty($res[$i]['url']))
            {
                $ch2->set_attribute ( "nc", urlencode ( trim ( $res[$i]['url'] ) ) );
            }

            if ( $res[$i]['specialfunctionname'] == $current )
            {
                $ch2->set_attribute ( "cur", "1" );
            }

            $this->addChildTabs ( $res[$i]['id_specialfunction'], $ch2, $lang, $current );
            // SU_DebugTools::LogAll("getBuildingTabs ch2=".$ch2, 'peace.log');
        }
        $res = "";

        return true;
    }

    // получить дочерние закладки
    function getChildTabs ( &$root, $lang, $current ) {

        global $common;

        if(key_exists('profi_game', $_SESSION) && $_SESSION['profi_game'])
        {
            $profiInjection = "";
        }else{
            $profiInjection = "AND sf.onlyforpremium = 'f'";
        }

        $sql = sprintf ( "
                select
                    sf.id_specialfunction,
                    sf.specialfunctionname,
                    sf.tab_image,
                    (select text
                    from ds_localizedtextlanguage ltl
                    where ltl.id_localizedtext = sf.id_localizedname
                    and ltl.id_language = %d) as localizedname
                from
                    ds_specialfunctionrelation sfr1,
                    ds_specialfunction sf
                where
                        sfr1.id_child = sf.id_specialfunction
                    ".$profiInjection."
                    and sfr1.id_parent =
                    (select sfr.id_parent
                from ds_specialfunctionrelation sfr
                where sfr.id_child =
                (select id_specialfunction
                from ds_specialfunction
                where specialfunctionname = '%s'))
                order by sf.id_specialfunction",
        $lang, $current );

        //SU_DebugTools::LogAll("getChildTabs sql=$sql", 'peace.log');
        $res = $common->getResultArray ( "id_specialfunction_specialfunctionname_id_language_current".$lang."_".$current."_".$_SESSION['profi_game'], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

        if ( $res === false ) {
            $this->errormsg = " SYSTEM ERROR - UA_Building::getChildTabs1";

            return false;
        }

        $ch = $root->new_child ( "alltabs" );

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {

            $ch2 = $ch->new_child ( "tab" );
            $ch2->set_attribute ( "id", trim ( $res[$i]['specialfunctionname'] ) );
            $ch2->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
            $ch2->set_attribute ( "icon", urlencode ( trim ( $res[$i]['tab_image'] ) ) );

            if ( $res[$i]['specialfunctionname'] == $current ) {
                $ch2->set_attribute ( "cur", "1" );
            }
        }

        $res = "";

        return true;
    }

    // хватает ли одновременных строек
    function isSimiltaneousBuildsEnough ( $id_comb )
    {
        global $common;
        global $params;

        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$id_comb);

        $current = $combAttributes->getAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS');
        $max = $combAttributes->getAttribute('MAX_AMOUNT', 'SIMILTANEOUS_BUILDS');
        $addon = $this->worldSettings->get('SIMILTANEOUS_BUILDS_ADDON');

        $max += $addon;

        if ( $current >= $max ) {
            $this->errormsg .= " SYSTEM ERROR - UA_Building::isSimiltaneousBuildsEnough3";
            return false;
        }

        $res = "";

        return true;
    }

    // id здания в которое здание может быть апгейжено
    function getBuildingTo ( $id_building ) {

        global $common;

        $sql = sprintf ( "select id_to_building
                from ds_buildingcanbeupgraded
                where id_from_building = %d",
        $id_building );

        $res = $common->getResultArray ( "id_building_to_id_from_building_".$id_building, $sql, "", "", "" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR UA_Building::getBuildingTo1";

            return false;
        }

        if ( sizeof ( $res ) > 0 ) {
            return $res[0]['id_to_building'];
        } else {
            return false;
        }
    }

    // проверить уникальность
    function checkUnique ( $id_building, $id_comb ) {

        //$b = 1;
        $buildings[] = $id_building;
        $b = $id_building;

        while ( $b ) {
            $b = $this->getBuildingTo ( $b );
            if ( $b ) $buildings[] = $b;
        }

        $buildings = implode ( $buildings, "," );

        $sql = sprintf ( "select id_building
                from ds_attachedbuilding
                where id_comb = %d
                and id_building in (%s)",
        $id_comb,
        $buildings );

        $res = $this->common->getResultArray ( "is_unique", $sql, "", "", "" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR UA_Building::checkUnique1";

            return false;
        }

        if ( sizeof ( $res ) == 0 ) {
            return true;
        } else {
            return false;
        }
    }
}

?>