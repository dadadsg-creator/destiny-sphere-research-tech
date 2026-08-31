<?php $CVS_Version = '$Revision$ ; $Date$';

// закладки военной базы - обзор всех армий на соте

class PN_ArmyOverview extends UA_GetBuildMenu
{

    private $combON;

    private $docXML;

    private $rootXML;

    private $armyWithArtsInAction = FALSE;

    private $combList = array();

    /** Армии куда трансферить мона. ТОлько те что принадлежат комбу. */
    private $trasfers = array();

    function __construct()
    {
        UA_GetBuildMenu::UA_GetBuildMenu ();

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><armyoverview />";

        $this->docXML = domxml_open_mem ($xml);

        $this->rootXML = $this->docXML->document_element();

        $this->combON = DE_Comb::CreateFromID($this->comb);
    }

    function getPanel ()
    {

        $res = $this->common->getResultArray ("", "select * from ds_unitcontainer
            where id_basecomb=".$this->comb." and use_artefacts and inaction");
        if (count($res)) $this->armyWithArtsInAction = TRUE;

        $this->rootXML->set_attribute ( "turn", $this->worldSettings->get('TURN_TIME')*60 );

        // минимальное ускорение
        $this->rootXML->set_attribute ( "minspeed", floor($this->worldSettings->get('MIN_ACCELERATION')*100) );
        
        // максимальное ускорение
        $this->rootXML->set_attribute ( "maxspeed", floor($this->worldSettings->get('MAX_ACCELERATION')*100) );

        $userInstance = DE_UserInstance::CreateFromID($this->uiid);
        $combCollection = $userInstance->getCombsCollection();
        $combCollection->resetNext();
        while ($comb = $combCollection->Next()) {
            if ($this->combON->getID() != $comb->getID())
                $this->combList[] = $comb->getFormatedAddress().'_'.$comb->getName();
        }
        $this->rootXML->set_attribute ("combs", implode('||', $this->combList));

        $this->rootXML->set_attribute ("rotatedRings", $this->worldSettings->get('RING_ROTATION')?'true':'false');
        
        $armysCollection = $this->combON->GetCombArmysCollection();
        
        //Собираем список армий, в какие можно делать трансфер юнитов. (все, кроме собственной)
        $armysCollection->resetNext();
        while ($army = $armysCollection->Next())
        {
            if (!$army->getInAction())
            {
                $this->transfers[$army->getID()] = trim(urlencode($army->getName()));
            }
        }

        $armysCollection->resetNext();
        while ($army = $armysCollection->Next())
        {
            $this->_addSelfArmy($army);
        }

        $armysOnDefendCollection = $this->combON->GetArmysOnCombDefendCollection();
        if($armysOnDefendCollection->countCollection() > 0)
        {
            $armysOnDefendCollection->resetNext();
            while ($army = $armysOnDefendCollection->Next())
            {
                $this->_addDefendArmy($army);
            }
        }
        
        // закладки
        $this->getBuildingTabs ( $this->rootXML, $this->bid, $this->params["lang"], $this->tab );


        SU_OutputHandler::Singleton()->addString( $this->docXML->dump_mem() , 'xmldata');
        return true;
    }


    private function _addActionsForInActionArmy(DE_Army $army, &$ch, &$ch2, &$currentaction)
    {
        // если армия в действии
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
                
        $armyAction = $army->getArmyAction();

        // смотрим что за действие
        $combnameto = $armyAction->getCombTo()->getName(); // куда
        $combnamefrom = $armyAction->getCombFrom()->getName(); // откуда



        $endtime = $armyAction->getEndDate()->getFormatedTime($delta);
        $enddate = $armyAction->getEndDate()->getFormatedDate($delta);

        $armyActionType = $armyAction->getActionType();

        $now = GB_TypeFactory::CreateBase('GB_Date', time());


        //Меняем тип екшена если время боя уже настпило.
        if (($armyAction->getEndDate()->value < $now->value) && ($armyActionType == 'MOVE_TO_ATTACK'))
        {
            $armyActionType = 'IN_BATTLE';
        }

        if (($armyAction->getEndDate()->value < $now->value) && ($armyActionType == 'MOVE_FROM'))
        {
            $armyActionType = 'ARMY_RETURN_ON_COMB';
        }

        switch($armyActionType)
        {
            case 'MOVE_TO_ATTACK':
                $ch->set_attribute ( "inaction", "1" );

                $messageparams = array(
                    $combnameto,
                    $armyAction->getCombTo()->getUserInstance()->getUser()->getName(),
                    $enddate,
                    $endtime
                );

                $currentaction = $this->_getMessage('ARMY_ATTACK', $messageparams);

                $res = $this->_getPossibleAction('CANCEL_ATTACK');

                $ch3 = $ch2->new_child ( "possible" );
                $ch3->set_attribute ( "id_action", $res['actiontypename'] );
                $ch3->set_attribute ( "actionname", urlencode($res['localizedtypename']));

                break;
                
            case 'IN_BATTLE':
                $ch->set_attribute ( "inaction", "2" );

                $messageparams[0] = $combnameto;
                $currentaction = $this->_getMessage('ARMY_IN_BATTLE', $messageparams);

                break;
                
            case 'MOVE_FROM':
                $ch->set_attribute ( "inaction", "2" );

                $messageparams[0] = $combnameto;
                $messageparams[1] = $enddate;
                $messageparams[2] = $endtime;

                $currentaction = $this->_getMessage('ARMY_RETURN', $messageparams);
                break;
                
            case 'MOVE_TO_DEFEND':
                if(GL_CanCancelDefend::Count($army))
                {
                    $ch->set_attribute ( "inaction", "1" );
                    $messageparams[0] = $combnameto;
                    $messageparams[1] = $enddate;
                    $messageparams[2] = $endtime;

                    $currentaction = $this->_getMessage('ARMY_DEFEND', $messageparams);

                    $res = $this->_getPossibleAction('CANCEL_DEFEND');

                    $ch3 = $ch2->new_child ( "possible" );
                    $ch3->set_attribute ( "id_action", $res['actiontypename'] );
                    $ch3->set_attribute ( "actionname", urlencode($res['localizedtypename']));
                }else{
                    $ch->set_attribute ( "inaction", "2" );
                    $messageparams[0] = $combnameto;
                    $messageparams[1] = $enddate;
                    $messageparams[2] = $endtime;

                    $currentaction = $this->_getMessage('ARMY_DEFEND', $messageparams);
                }
                break;
                
            case 'IN_DEFEND':
                if(GL_CanCancelDefend::Count($army))
                {
                    $ch->set_attribute ( "inaction", "1" );

                    $combFullName = $messageparams[0] = $armyAction->getCombTo()->getFullName();
                    $messageparams[1] = $armyAction->getCombTo()->getUserInstance()->getUser()->getName();

                    $currentaction = $this->_getMessage('ARMY_DEFEND2', $messageparams);

                    $res = $this->_getPossibleAction('CANCEL_DEFEND');

                    $ch3 = $ch2->new_child ( "possible" );
                    $ch3->set_attribute ( "id_action", $res['actiontypename'] );
                    $ch3->set_attribute ( "actionname", urlencode($res['localizedtypename']));
                }else{
                    $ch->set_attribute ( "inaction", "2" );

                    $messageparams[0] = $combnameto;
                    $messageparams[1] = $armyAction->getCombTo()->getUserInstance()->getUser()->getName();

                    $currentaction = $this->_getMessage('ARMY_DEFEND2', $messageparams);
                }

                break;

            case 'ARMY_RETURN_ON_COMB':
                $ch->set_attribute ( "inaction", "2" );

                $messageparams[0] = $combnameto;

                $currentaction = $this->_getMessage('ARMY_RETURN_ON_COMB', $messageparams);
                break;
                
            case 'MOVE_TO_COMB':
                $ch->set_attribute ( "inaction", "2" );
                
                $messageparams[0] = $combnameto;
                $messageparams[1] = $enddate;
                $messageparams[2] = $endtime;
                
                $currentaction = $this->_getMessage('ARMY_MOVE_TO_COMB', $messageparams);
                break;
            
            case 'HIDE_ARMY':
                $ch->set_attribute ( "inaction", "2" );
                                
                $messageparams[0] = $enddate;
                $messageparams[1] = $endtime;
                
                $currentaction = $this->_getMessage('HIDE_ARMY_PN', $messageparams);
                break;
                                
            default:
                ds_error("Unknown army action type = '".$armyAction->getActionType()."'.");
        }//END OF SWITCH

        if(isset($ch3))
        {
            return $ch3;
        }else{
            return true;
        }

    }

    private function _addActionsForNonActionArmy(DE_Army $army, &$ch, &$ch2, &$currentaction)
    {
        // идет на соте битва или нет
        $sql = sprintf ( "select id_battle
                        from ds_battle
                        where id_battlecomb = %d
                        and battleenddate is null",
        $army->getBaseComb()->getID() );

        $res2 = $this->common->getResultArray ( "", $sql, "", $this->params["uiid"], "" );
        if ( $res2 === false )
        {
            $this->errormsg .= " SYSTEM ERROR ArmyOverview::getPanel24";
            return false;
        }

        if ( sizeof ( $res2 ) > 0 )
        {
            $ch->set_attribute ( "inaction", "2" );
        } else {
            if ( $army->getIsDefault() || !$army->getIsMoveble() )
            {
                $ch->set_attribute ( "inaction", "3" );
            }
        }


        $res = $this->_getPossibleAction('ATTACK');
        $ch3 = $ch2->new_child ( "possible" );
        $ch3->set_attribute ( "id_action", $res['actiontypename'] );
        $ch3->set_attribute ( "actionname", urlencode($res['localizedtypename']));

        $res = $this->_getPossibleAction('DEFEND');
        $ch3 = $ch2->new_child ( "possible" );
        $ch3->set_attribute ( "id_action", $res['actiontypename'] );
        $ch3->set_attribute ( "actionname", urlencode($res['localizedtypename']));

        if(count($this->combList))
        {
            $res = $this->_getPossibleAction('MOVE_TO_COMB');
            $ch3 = $ch2->new_child ( "possible" );
            $ch3->set_attribute ( "id_action", $res['actiontypename'] );
            $ch3->set_attribute ( "actionname", urlencode($res['localizedtypename']));
        }
        
        if(GL_CanHideArmy::Test($army))
        {
            $res = $this->_getPossibleAction('HIDE_ARMY'); 
            $ch3 = $ch2->new_child ( "possible" );
            $ch3->set_attribute ( "id_action", $res['actiontypename'] );
            $ch3->set_attribute ( "actionname", urlencode($res['localizedtypename']));  
        }
        
        
        // текст - "армия стоит на соте"
        $messageparams[0] = $army->getBaseComb()->getName();
        $currentaction = $this->_getMessage('ARMY_STAY', $messageparams);

        return $ch3;
    }

    private function _addSelfArmy(DE_Army $army)
    {
        $basecombname = $army->getBaseComb()->getName();

        $ch = $this->rootXML->new_child ( "army" );
        $ch->set_attribute ( "id_army", $army->getID() );
        $ch->set_attribute ( "armyname", urlencode ( $army->getName() ) );
        $ch->set_attribute ( "basecomb", $army->getBaseComb()->getFormatedAddress() );
        $ch->set_attribute ( "PW", round($army->getArmyPower()/10) );
        if ($army->lastCoord)
            $ch->set_attribute ( "lastcoord", $army->lastCoord);
        if ($army->speed)
            $ch->set_attribute ( "lastspeed", $army->speed);
        else
            $ch->set_attribute ( "lastspeed", floor($this->worldSettings->get('MAX_ACCELERATION')*100));

        $armyStars = GL_ArmyPowerToStars::CountForMenu($army->getArmyPower());

        $ch->set_attribute ( "armypower",  $armyStars);

        if(GL_CanHideArmy::Test($army))
        {

            $tmp = DE_WorldSettings::CreateForCurentWorld()->get('ARMYS_HIDDING_MAX_PERIOD');
            $ch->set_attribute("mst",  $tmp);
        }

        $armyAttributes = DE_ArmyAttributes::CreateFromArmyID($army->getID());

        if($armyAttributes->haveAttribute('CURRENT_AMOUNT', 'SPEED'))
        {
            $ch->set_attribute ( "speed",  $armyAttributes->getAttribute('CURRENT_AMOUNT', 'SPEED') * 100);
        }else{
            $ch->set_attribute ( "speed",  floor($this->worldSettings->get('MAX_ACCELERATION')*100));
        }

        if($armyAttributes->haveAttribute('CURRENT_AMOUNT', 'RESOURCE_FOR_STEALING'))
        {
            $ch->set_attribute ( "res",  $armyAttributes->getAttribute('CURRENT_AMOUNT', 'RESOURCE_FOR_STEALING'));
        }else{
            $ch->set_attribute ( "res",  'ALL_RES');
        }

        $ch2 = $ch->new_child ( "actions" );

        assert(($army->getInAction() && !$army->getArmyAction()->isNull())||(!$army->getInAction() && $army->getArmyAction()->isNull()));

        $currentaction = 'EMPTY';

        if ($army->getInAction())
        {
            $ch3 = $this->_addActionsForInActionArmy($army, $ch, $ch2, $currentaction);
            if ($army->useArtefacts==1 && DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_OFFENSIVE_ARTEFACTS') > 0)
                $ch->set_attribute ("useArtefacts", $army->useArtefacts);
        }else{
            if (!$this->armyWithArtsInAction && !$army->getIsDefault() && $army->getIsMoveble() && DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_OFFENSIVE_ARTEFACTS') > 0)
                $ch->set_attribute ("useArtefacts", $army->useArtefacts);
            $ch3 = $this->_addActionsForNonActionArmy($army, $ch, $ch2, $currentaction);
        }

        $elem = $this->docXML->create_text_node(urlencode($currentaction));
        $ch2->append_child ( $elem );
        $ch2 = $ch->new_child ( "transfers" );

        // по каждой армии - инфа куда можно трансферить из нее юниты
        if(sizeof($this->transfers) > 0)
        {
            foreach ( $this->transfers as $id_army=>$armyname )
            {
                if ( $id_army == $army->getID() )
                {
                    continue;
                }

                $ch3 = $ch2->new_child ( "transer_to" );
                $ch3->set_attribute ( "id_army", $id_army );
                $ch3->set_attribute ( "armyname", $armyname );

            }
        }

        $unitsGroupCollection = $army->getUnitsGroupCollection();
        $unitsGroupCollection->resetNext();
        while($unitsGroup = $unitsGroupCollection->next())
        {
            $this->_addUnitsGroups($unitsGroup, $ch);
        }
    }


    private function _addDefendArmy(DE_Army $army)
    {
        $basecombname = $army->getBaseComb()->getName();

        $ch = $this->rootXML->new_child ( "army" );
        $ch->set_attribute ( "id_army", $army->getID() );
        $ch->set_attribute ( "armyname", urlencode ( $army->getName() ) );
        $ch->set_attribute ( "basecomb", $army->getBaseComb()->getFormatedAddress() );
        $ch->set_attribute ( "PW", round($army->getArmyPower()/10) );

        $armyStars = ceil(GL_ArmyPowerToStars::Count($army->getArmyPower()));
        $ch->set_attribute ( "armypower", sprintf ('%d', $armyStars));

        $ch2 = $ch->new_child ( "actions" );

        $ch->set_attribute ( "inaction", "2" );

        // текст - "армия стоит на соте"
        $messageparams[0] = $army->getUserInstance()->getUser()->getName();
        $messageparams[1] = $army->getBaseComb()->getFormatedAddress();

        $currentaction = $this->_getMessage('ARMY_IN_DEFEND_MESS', $messageparams);

        $elem = $this->docXML->create_text_node(urlencode($currentaction));
        $ch2->append_child($elem);

        $unitsGroupCollection = $army->getUnitsGroupCollection();
        $unitsGroupCollection->resetNext();
        while($unitsGroup = $unitsGroupCollection->next())
        {
            $this->_addUnitsGroups($unitsGroup, $ch);
        }
    }

    private function _addUnitsGroups(DE_UnitsGroup $unitsGroup, &$ch)
    {
        $ch2 = $ch->new_child ( "unit" );
        $ch2->set_attribute ( "unitname", $unitsGroup->getUnit()->getUnitName() );
        $ch2->set_attribute ( "id_unit", $unitsGroup->getUnit()->getID() );
        $ch2->set_attribute ( "amount", GB_Integer::Format(ceil($unitsGroup->getAmount())) );   
    }

    private function _getMessage($messageName, $msgparams)
    {
        assert(is_string($messageName) && !empty($messageName));
        assert(is_array($msgparams) && count($msgparams) > 0);
            
        return DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($messageName, $msgparams, null, null, false);
    }

    private function _getPossibleAction($actionName)
    {
        return DS_Army::GetActionByName($actionName, $this->langId);
    }       
}
?>