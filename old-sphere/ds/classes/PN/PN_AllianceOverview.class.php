<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка здания ALLIANCE_CONNECTOR - обзор членов альянса с постраничным выводом

class PN_AllianceOverview extends UA_GetBuildMenu {

    function __construct() {
        UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    function getPanel () {

        global $params;
        global $common;

        $limit = 25;

        if ( $this->page == "" || $this->page <= 0 ) $this->page = 1;

        $offset = $limit * ( $this->page - 1 );

        if ( $offset < 0 ) $offset = 0;

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><allianceoverview />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();
        //$root->set_attribute ( "c", $this->cellnum );

        
        // id альянса в котором состоит игрок и количество членов альянса
        $sql = sprintf ( "select a.id_alliance,
                a.members_amount
                from ds_userhasalliance uha,
                ds_alliance a
                where uha.id_alliance = a.id_alliance
                and uha.id_userinstance = %d",
                $params["uiid"] );

        $res = $common->getResultArray ( "id_alliance_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR - AllianceOverview::getPanel1";
            
            return false;
        }
        if ( sizeof ( $res ) > 0 ) { // если в альянсе

            $id_alliance = $res[0]['id_alliance'];
            $members_amount = $res[0]['members_amount'];

            $pages = ceil ( $members_amount/$limit );

            $root->set_attribute ( "pages", $pages );
            $root->set_attribute ( "b", $this->bid );
            $root->set_attribute ( "curpage", $this->page );
            
            // определяем лидера альянса игрок или нет

            $sql = sprintf ( "select uha.id_alliance
                    from ds_userhasalliance uha,
                    ds_userhasalliancestatus uhas,
                    ds_useralliancestatus uas
                    where uha.id_alliance = uhas.id_alliance
                    and uhas.id_status = uas.id_status
                    and uas.statusname = 'LEADER'
                    and uha.id_alliance = %d
                    and uhas.id_userinstance = %d",
                    $id_alliance,
                    $params["uiid"] );

            $res = $common->getResultArray ( "userhasalliancestatus_id_alliance_id_userinstance_".$id_alliance."_".$params["uiid"], $sql, "", "", "GLOBAL_CACHE" );
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR - AllianceOverview::getPanel2";
                
                return false;
            }

            if ( sizeof ( $res ) > 0 ) $root->set_attribute ( "leader", "1" );

            $root->set_attribute ( "id_alliance", $id_alliance );

            // выбираем информацию о всех членах альянса
            $sql = sprintf ( "select u.username,
                    ui.id_userinstance,
                     (
                        SELECT
                              SUM( c.combscore ) as score
                        FROM
                            ds_comb c
                        WHERE
                             c.id_userinstance = ui.id_userinstance
                        GROUP BY
                              c.id_userinstance 
                     ) as userscore,
                    (select id_status
                    from ds_userhasalliancestatus
                    where id_userinstance = ui.id_userinstance) as id_status,
                    (select ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber
                    from ds_ring ri,
                    ds_combcomplex cc,
                    ds_comb co,
                    ds_combcomplextype cct
                    where ri.id_ring = cc.id_ring
                    and cc.id_combcomplex = co.id_combcomplex
                    and co.id_userinstance = ui.id_userinstance
                    and cc.id_complextype = cct.id_complextype
                    and cct.complextype = 'HABITABLE') as coord,
                    (select count(aaa.id_artefact)
                    from ds_artefactattachedalliance aaa,
                    ds_attachedbuilding ab,
                    ds_comb co
                    where aaa.id_attachedbuilding = ab.id_attachedbuilding
                    and ab.id_comb = co.id_comb 
                    and co.id_userinstance = ui.id_userinstance) as artefact
                    from ds_userhasalliance uha,
                    ds_user u,
                    ds_userinstance ui                  
                    where uha.id_userinstance = ui.id_userinstance
                    and ui.id_user = u.id_user
                    and uha.id_alliance = %d
                    order by uha.joindate
                    limit %d offset %d",
                    $id_alliance,
                    $limit,
                    $offset );

            $res = $common->getResultArray ( "alliance_members2_id_alliance_".$id_alliance."_".$offset, $sql, "", "", "" ); //GLOBAL_CACHE
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR - AllianceOverview::getPanel3";
                
                return false;
            }
            
            for ( $i=0; $i<sizeof ( $res ); $i++ ) {
                
                $ch = $root->new_child ( "member" );
                $ch->set_attribute ( "name", $res[$i]['username'] );
                $ch->set_attribute ( "id_userinstance", $res[$i]['id_userinstance'] );
                $ch->set_attribute ( "ranking", $res[$i]['userscore'] );
                $ch->set_attribute ( "coord", $res[$i]['coord'] );
                $ch->set_attribute ( "artefact", $res[$i]['artefact'] );
                // $ch->set_attribute ( "delete_button_disabled", DE_WorldSettings::CreateForCurentWorld()->get('DISABLE_EXPEL_ALLIANCE') );

                if ( $res[$i]['id_status'] ) { // если есть статус в альянсе - берем его лок. название
                
                    $sql = sprintf ( "select ltl.text as localizedstatus,
                            uas.statusname as status
                            from ds_localizedtextlanguage ltl,
                            ds_useralliancestatus uas
                            where ltl.id_localizedtext = uas.id_localizedstatus
                            and ltl.id_language = %d
                            and uas.id_status = %d",
                            $params['lang'],
                            $res[$i]['id_status'] );
                            
                    $res2 = $common->getResultArray ( "alliance_status_name_id_language_id_status_".$params['lang']."_".$res[$i]['id_status'], $sql, "", "", "GLOBAL_CACHE" );
                    if ( !$res2 ) {
                        $this->errormsg .= " SYSTEM ERROR - AllianceOverview::getPanel6";
                        
                        return false;
                    }
                
                    $ch->set_attribute ( "lstatus", $res2[0]['localizedstatus'] );
                    $ch->set_attribute ( "status", $res2[0]['status'] );
                }               
            }

        } else { // не в альянсе

            $sql = sprintf ( "select ltl.text as localizedmessage
                    from ds_localizedtextlanguage ltl,
                    ds_systemmessage sm
                    where ltl.id_localizedtext = sm.id_localizedtext
                    and ltl.id_language = %d
                    and sm.messagename = 'NOT_IN_ALLIANCE'",
                    $params["lang"] );
                
            $res = $common->getResultArray ( "not_in_alliance_message_id_language_".$params["lang"], $sql, "", "", "GLOBAL_CACHE" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR - AllianceOverview::getPanel3";
                
                return false;
            }

            $elem = $doc->create_text_node ( urlencode ( trim ( $res[0]['localizedmessage'] ) ) );
            $root->append_child ( $elem );
        }

        $this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );
        
        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );
    
        return true;
    }
}

?>
