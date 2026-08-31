<?php $CVS_Version = '$Revision$ ; $Date$';

// обработчик строительства зданий
class EH_ConstructBuilding extends EH_Event
{

    public $checktime;

    function __construct()
    {
        parent::EH_Event();
    }

    function handleEvent ( $eqid )
    {
        $this->eqid = $eqid;

        if(!$this->checktime)
        {
            $this->checktime = 'now()';
        }

        // берем параметры эвента
        if(!$this->getEventParams())
        {
            return false;
        }

        // название соты
        $messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"])->getName()."{/b}";

        $raceID = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"])->getRaceID();
        $raceBuilding = DE_Building::CreateFromID((int)$this->eventparams["ID_Building"])->getRaceBuilding((int)$raceID);

        // название здани€
        $messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

        // если закончено
        if ($this->_isTimeToHandle())
        { //finished

            $buildingName = DE_Building::CreateFromID((int)$this->eventparams["ID_Building"])->getName();
            $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->eventparams["ID_Comb"]);
            
            // если археологический институт
            if ( $buildingName == 'ARCHEOLOGICAL_INSTITUTE' )
            {
                $addArcheologists = $this->worldSettings->get('COUNT_ADD_ARCHEOLOGISTS_CONSTRUCT_INSTITUT');
                assert($addArcheologists >= 0);
                
                if ($addArcheologists != 0) {
                    // —мотрим, нет ли на соте хот€ бы одиного археологического института или лагерь археологов
                    if (count(DS_Building::GetAttachedBuilddingNotUnderConstruction((int)$this->eventparams["ID_Comb"], 12)) == 0
                    && count(DS_Building::GetAttachedBuilddingNotUnderConstruction((int)$this->eventparams["ID_Comb"], 47)) == 0 ) {
                        // ƒобавл€ем архов на соту
                        $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', (float)$addArcheologists);
                    }
                }
            }

            // если научный центр
            if ( $buildingName == 'RESEARCH_CENTER' )
            {
                $addScientists = $this->worldSettings->get('COUNT_ADD_CSIENTISTS_CONSTRUCT_CENTER');
                assert($addScientists >= 0);

                if ($addScientists != 0) {
                    // —мотрим, нет ли на соте хот€ бы одиного научного центра
                    if (count(DS_Building::GetAttachedBuilddingNotUnderConstruction((int)$this->eventparams["ID_Comb"], 29)) == 0 ) {
                        // ƒобавл€ем ученых на соту
                        $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SCIENTIST', (float)$addScientists);
                    }
                }
            }

            $ehsql = "
                    UPDATE ONLY
                        DS_AttachedBuilding
                    SET
                        UnderConstruction = false,
                        Active = true
                    WHERE
                        ID_AttachedBuilding = ".(int)$this->eventparams["ID_AttachedBuilding"];
            $ehres = $this->common->executeQuery($ehsql);
            assert(empty($this->common->errormsg));

            $buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->eventparams["ID_Building"]);

            //–естартим кэш вида соты
            $buidlingData = ME_CombViewGetAtachedBuildingData::Get((int)$this->eventparams["ID_AttachedBuilding"]);
            CR_CombView::CreateFromCombID($buidlingData['COMB_ID'])->resetBuildings($buidlingData['CELL_TYPE_ID']);

            $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS', -(float)1);

            // добавл€ем производстов/потребление к соте
            ME_AddBuildingAttributesToComb::AddAttributes($buildingAttributes, $combAttributes, 'plus');


            // аттрибут destroyable
            $ehsql = sprintf ( "select 1 as destr
                    from ds_buildinghasattribute bha,
                    ds_attribute a,
                    ds_attributename atn,
                    ds_attributetype att
                    where bha.id_attribute = a.id_attribute
                    and a.id_attributename = atn.id_attributename
                    and a.id_attributetype = att.id_attributetype
                    and att.typename = 'PERMANENT_FLAG'
                    and atn.attributename = 'DESTROYABLE'
                    and bha.id_building = %d
                    and bha.value = 0",
            $this->eventparams["ID_Building"] );

            $ehres = $this->common->getResultArray ( "destroyable_permanent_flag_id_building_".$this->eventparams["ID_Building"], $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
            if ( $ehres === false ) {
                $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent7";

                return false;
            }

            if ( count($ehres) == 0 ) $ehres[0]['destr'] = 0;

            if ( $ehres[0]['destr'] != 1 )
            {

                $ehsql = sprintf ( "select ID_Attribute
                        from DS_Attribute a,
                        DS_AttributeName atn,
                        DS_AttributeType att
                        where a.ID_AttributeName = atn.ID_AttributeName
                        and a.ID_AttributeType = att.ID_AttributeType
                        and att.TypeName = 'CURRENT_FLAG'
                        and atn.AttributeName = 'DESTROYABLE'" );

                $ehres = $this->common->getResultArray ( "id_attribute_current_flag_destroyable", $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
                if ( !$ehres ) {
                    $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent8";

                    return false;
                }

                $ehsql = sprintf ( "insert into DS_AttachedBuildingHasAttribute (ID_AttachedBuilding, ID_Attribute)
                        values (%d, %d)",
                $this->eventparams["ID_AttachedBuilding"],
                $ehres[0]['id_attribute'] );

                $ehres = $this->common->executeQuery ( $ehsql );
                if ( $ehres === false ) {
                    $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent9";

                    return false;
                }
            }

            // аттрибут deactiveable
            $ehsql = sprintf ( "select 1 as deact
                    from ds_buildinghasattribute bha,
                    ds_attribute a,
                    ds_attributename atn,
                    ds_attributetype att
                    where bha.id_attribute = a.id_attribute
                    and a.id_attributename = atn.id_attributename
                    and a.id_attributetype = att.id_attributetype
                    and att.typename = 'PERMANENT_FLAG'
                    and atn.attributename = 'DEACTIVEABLE'
                    and bha.id_building = %d
                    and bha.value = 0",
            $this->eventparams["ID_Building"] );

            $ehres = $this->common->getResultArray ( "deactiveable_permanent_flag_id_building_".$this->eventparams["ID_Building"], $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
            if ( $ehres === false ) {
                $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent10";

                return false;
            }

            if ( count($ehres) == 0 ) $ehres[0]['deact'] = 0;

            if ( $ehres[0]['deact'] != 1 )
            {

                $ehsql = sprintf ( "select ID_Attribute
                        from DS_Attribute a,
                        DS_AttributeName atn,
                        DS_AttributeType att
                        where a.ID_AttributeName = atn.ID_AttributeName
                        and a.ID_AttributeType = att.ID_AttributeType
                        and att.TypeName = 'CURRENT_FLAG'
                        and atn.AttributeName = 'DEACTIVEABLE'" );

                $ehres = $this->common->getResultArray ( "id_attribute_current_flag_deactiveable", $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
                if ( !$ehres ) {
                    $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent11";

                    return false;
                }

                $ehsql = sprintf ( "insert into DS_AttachedBuildingHasAttribute
                        (ID_AttachedBuilding, ID_Attribute)
                        values (%d, %d)",
                $this->eventparams["ID_AttachedBuilding"],
                $ehres[0]['id_attribute'] );

                $ehres = $this->common->executeQuery ( $ehsql );
                if ( $ehres === false ) {
                    $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent12";

                    return false;
                }
            }

            //«атычка на тот случай когда у нас одини тотже трейдскоп вситавл€етьс€ дважды.
            $ehsql = "SELECT
                        id_tradescope
                      FROM
                        ds_buildinghastradescope
                      WHERE
                        id_building = ".$this->eventparams["ID_Building"];
            $tradeScopes = $this->common->getResultArray ( "GET_BUILDING_TRADESCOPE_".$this->eventparams["ID_Building"], $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

            if ( is_array ($tradeScopes) ) {
                foreach ($tradeScopes as $k=>$v) {
                    $sql = "SELECT *
                            FROM ds_comballowedtradescope
                            WHERE
                                id_comb = ".$this->eventparams["ID_Comb"]." AND
                                id_tradescope = ".$v['id_tradescope'];
                    $res = $this->common->getResultArray ( "ereee", $sql, "", "", "" );
                    if ( !empty ($res) ) {
                        continue;
                    }

                    // если надо добавл€ем соте tradescope
                    $ehsql = "INSERT INTO
                                ds_comballowedtradescope
                            (id_comb, id_tradescope )
                            VALUES
                            (".$this->eventparams["ID_Comb"].", ".$v['id_tradescope']." )";
                    $ehres = $this->common->executeQuery ( $ehsql );
                    if ( $ehres === false ) {
                        $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding: Cannot add tradescope for ".$this->eventparams["ID_Building"]." building in comb ".$this->eventparams["ID_Comb"]; // EH_ConstructBuilding::handlEvent13
                        return false;
                    }
                }//END OF foreach()
            }//END OF if

            
            //          $raceID = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"])->getRaceID();

            // название здани€ дл€ системного сообщени€
            //          $raceBuilding = DE_Building::CreateFromID((int)$this->eventparams["ID_Building"])->getRaceBuilding((int)$raceID);

            // если колонизационный центр - создаем модуль
            if ( $buildingName == 'COLONIZATION_CENTER' ) {

                $ehsql = sprintf ( "select id_unitcontainertype
                        from ds_unitorcontainertype
                        where containertypename = 'COLONIZATION'");

                $ehres = $this->common->getResultArray ( "id_unitcontainertype_colonization", $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
                if ( $ehres === false ) {
                    $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent17";

                    return false;
                }

                $ehsql = sprintf ( "insert into ds_unitcontainer ( id_unitcontainertype, id_basecomb, id_userinstance, moveable, inaction )
                        values ( %d, %d, %d, true, false )",
                $ehres[0]['id_unitcontainertype'],
                $this->eventparams["ID_Comb"],
                $this->eventparams["ID_UserInstance"] );

                $ehres = $this->common->executeQuery ( $ehsql );
                if ( $ehres === false ) {
                    $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent18";

                    return false;
                }
            }

            // если посольство
            if ( $buildingName == 'ALLIANCE_CONNECTOR' )
            {
                $userInstance = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"]);
                $user = $userInstance->getUser();

                $globalAllianceId = DS_GlobalAlliance::GetUserGlobalAlliance($user);

                //SU_DebugTools::LogAll("globalAllianceId = $globalAllianceId", "SessionErrors.log");

                //—мотрим, состоит ли игрок в глобальном аль€нсе
                if (!empty($globalAllianceId))
                {
                    if ( (int)$globalAllianceId <= 0 ) {
                        $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent19";

                        return false;
                    }

                    //¬ыбираем локальный альнс
                    $ehsql = sprintf ( "SELECT
                                            id_alliance
                                        FROM
                                            ds_alliance     
                                        WHERE
                                             id_global_alliance = ".(int)$globalAllianceId."
                                            AND
                                             id_world = ".$userInstance->getWorldID());

                    $ehres = $this->common->getResultArray ( "", $ehsql, "", "", "" );

                    if ( empty($ehres) ) {
                        $this->errormsg = "SYSTEM ERROR - EH_ConstructBuilding::handlEvent20";

                        return false;
                    }

                    $localAlliance = DE_Alliance::CreateFromID((int)$ehres[0]['id_alliance']);

                    //ƒобавл€ем его в локальный аль€нс
                    ME_AddUserToAlliance::Add($userInstance, $localAlliance);

                }
            }

            // если √енератор защитного пол€
            if ( $buildingName == 'PROTECTIVE_FIELD_GENERATOR' )
            {
                // добавл€ем соте защитное поле
                $protectedField = DE_CombProtectiveField::CreateFromCombID((int)$this->eventparams["ID_Comb"]);
                $protectedField->setProtectiveField(100);
            }

            // ¬ыбираем очки за здание
            $buildinScore = DS_Building::GetBuildingScore((int)$this->eventparams["ID_Building"]);

            // увеличиваем рейтинг соты
            DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"])->deltaCombScore($buildinScore);

            // ”величиваем строительный рейтинг игрока
            $buildinScore2 = DS_Building::GetBuildingScoreNew((int)$this->eventparams["ID_Building"]);
            DE_Ranking::CreateFromID((int)$this->eventparams["ID_UserInstance"])->deltaBuildingRanking($buildinScore2);

            // системное сообщение
            DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_BUILDING_MESS", $messparams );

            // прибиваем эвент
            $this->setTrash ();

        }else{  // не закончено

            // процент завершенности и дата окончани€
            $prog = $this->_getComplitPart() * 100;

            if ( $prog > 100 ) $prog = 100;
            if ( $prog < 0 ) $prog = 0;

            $messparams[2] = number_format ( $prog, 1, ".", "" );

            $event = DE_EventQueue::CreateFromID((int)$this->eqid);
            $messparams[3] = $event->getEndDate()->getSystemMessageFormat();

            // системное сообщение
            DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_BUILDING_MESS", $messparams, null, null, 'NOACCUMULATE');

        }

        return true;
    }

}

?>