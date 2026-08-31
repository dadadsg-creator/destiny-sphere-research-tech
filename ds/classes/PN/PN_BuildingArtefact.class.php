<?php $CVS_Version = '$Revision: 1.11 $ ; $Date: 2009/03/16 14:26:12 $';

// список артефактов, приделанных к зданию

class PN_BuildingArtefact extends UA_GetBuildMenu {

    function __construct() {
        UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    function getPanel () {

        global $params;
        global $common;

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><buildingartefact />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();
        $root->set_attribute ( "c", $this->id_cell );
        $root->set_attribute ( "n", 1);

        // колличество слотов в здании
        $sql = sprintf ( "
                SELECT bha.value
                FROM ds_buildinghasattribute bha,
                ds_attribute a,
                ds_attributename atn,
                ds_attributetype att
                WHERE bha.id_attribute = a.id_attribute
                AND a.id_attributename = atn.id_attributename
                AND a.id_attributetype = att.id_attributetype
                AND att.typename = 'MAX_AMOUNT'
                AND atn.attributename = 'ARTEFACT_SLOTS'
                AND bha.id_building = %d",
        $this->bid );

        $res = $common->getResultArray ( "building_max_amount_artefact_slots_id_building_".$this->bid, $sql,  "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR BuildingArtefact::getPanel1";

            return false;
        }

        if ( sizeof ( $res ) > 0 ) $slots = $res[0]['value']; else $slots = 0;

        $root->set_attribute ( "slots", $slots );
        $sql = sprintf ( "select ltl.text
                        from ds_localizedtextlanguage ltl,
                        ds_systemmessage sm
                        where ltl.id_localizedtext = sm.id_localizedtext
                        and ltl.id_language = %d
                        and sm.messagename = 'ARTEFACT_DEATH_TIME'", $params['lang'] );

        $loc_death_text_res = $common->getResultArray ( "system_message_art_finished_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        $loc_death_text = $loc_death_text_res[0]['text'];


        // какие арты приделаны
        $sql = sprintf ( "
                SELECT
                    ai.id_artefactinstance,
                    ai.immortal,
                    ap.prefix,
                    ars.scopename,
                    to_char(ai.death_time, 'DD.MM.YYYY') AS death_time,
                    to_char(ai.birth_time, 'DD.MM.YYYY') AS birth_time,
                    at.typename,
                    (
                        SELECT text
                        FROM ds_localizedtextlanguage
                        WHERE
                                id_localizedtext = a.id_localizedname
                            AND id_language = %d
                    ) AS localizedname,
                    (
                        SELECT text
                        FROM ds_localizedtextlanguage
                        WHERE
                                id_localizedtext = ars.id_localizedscope
                            AND id_language = %d
                    ) AS localizedscope,
                    (
                        SELECT text
                        FROM ds_localizedtextlanguage
                        WHERE id_localizedtext = ap.id_localizedprefix
                        AND id_language = %d
                    ) AS localizedprefix,
                    (
                        SELECT substr(text,strpos(text, ' ')+1)
                        FROM ds_localizedtextlanguage
                        WHERE id_localizedtext = at.id_localizedtypename
                        AND id_language = %d
                    ) AS localizedtypename
                    FROM ds_artefact a,
                        ds_artefactprobability ap,
                        ds_artefactscope ars,
                        ds_artefacttype at,
                        ds_artefactinstance ai,
                        ds_artefactattachedbuilding aab
                    WHERE a.id_artefact = ai.id_artefact
                    AND ai.id_artefactinstance = aab.id_artefact
                    AND a.id_artefactprobability = ap.id_artefactprobability
                    AND a.id_artefactscope = ars.id_artefactscope
                    AND a.id_artefacttype = at.id_artefacttype
                    AND aab.collection = 1
                    AND aab.id_attachedbuilding = %d",
        $params["lang"], $params["lang"],
        $params["lang"], $params["lang"],
        $this->attachedbid );

        $res = $common->getResultArray ( "attached_building_artefacts_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR BuildingArtefact::getPanel2";

            return false;
        }

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            if(preg_match("/^IGROMIR_/", $res[$i]['typename'])) {
                $res[$i]['typename'] = preg_replace(array('/COMB_/', '/COMBCOMPLEX_/', '/ALLIANCE_/', '/WAR_/'), array('', '', '', ''), $res[$i]['typename']);
            }
            $ch2 = $root->new_child ( "artefact" );
            $ch2->set_attribute ( "id", $res[$i]['id_artefactinstance'] );
            $ch2->set_attribute ( "prefix", $res[$i]['prefix'] );
            $ch2->set_attribute ( "scope", $res[$i]['scopename'] );
            if($res[$i]['immortal'] == 'f') {
                list($d,$m,$y) = explode('.', $res[$i]['birth_time']);
                $lt = mktime(0, 0, 0, $m, $d, $y);
                list($d,$m,$y) = explode('.', $res[$i]['death_time']);
                $lt .= '|'.mktime(0, 0, 0, $m, $d, $y);
                $res[$i]['typename'] .= "_".$lt;
                $ch2->set_attribute("death_message", sprintf(str_replace('%', '%s', $loc_death_text), $res[$i]['death_time']));
                $ch2->set_attribute("lifetime", $lt);
            }
            $ch2->set_attribute("postfix", $res[$i]['typename']);

            // какие бонусы они дают
            $sql = sprintf ( "select atn.attributename,
                    att.typename,
                    ahb.value
                    from ds_artefacthasbonus ahb,
                    ds_artefact a,
                    ds_artefactinstance ai,
                    ds_attribute at,
                    ds_attributename atn,
                    ds_attributetype att
                    where ahb.id_attribute = at.id_attribute
                    and ahb.id_artefact = a.id_artefact
                    and a.id_artefact = ai.id_artefact
                    and at.id_attributename = atn.id_attributename
                    and at.id_attributetype = att.id_attributetype
                    and ai.id_artefactinstance = %d",
            $res[$i]['id_artefactinstance'] );

            $res2 = $common->getResultArray ( "artefactbonus_id_artefactinstance_".$res[$i]['id_artefactinstance'], $sql, "", "", "GLOBAL_CACHE" );

            if ( $res2 === false ) {
                $this->errormsg .= " SYSTEM ERROR BuildingArtefact::getPanel3";

            }

            for ( $e=0; $e<sizeof ( $res2 ); $e++ ) {

                $ch3 = $ch2->new_child ( "bonus" );
                $bonus = floatval ( $res2[$e]['value'] );

                if ( $res2[$e]['typename'] == 'BONUS_RELATIVE' ) {
                    $bonus = $bonus*100;
                    $bonus .= "%25";
                }

                $bonus = "%2B".$bonus;
                $ch3->set_attribute ( "name", trim ( $res2[$e]['attributename'] ) );
                $ch3->set_attribute ( "value", urlencode ( $bonus ) );
            }
        }



        // список артефактов, которые еще можно приделать к зданию
        $sql = sprintf ( "
                SELECT
                    ai.id_artefactinstance,
                    ai.immortal,
                    ap.prefix,
                    ars.scopename,
                    to_char(ai.death_time, 'DD.MM.YYYY') AS death_time,
                    to_char(ai.birth_time, 'DD.MM.YYYY') AS birth_time,
                    at.typename
                FROM
                    ds_artefact a,
                    ds_artefactprobability ap,
                    ds_artefactscope ars,
                    ds_artefacttype at,
                    ds_artefactinstance ai,
                    ds_artefacttypeforbuilding atfb,
                    ds_combhasartefact cha
                WHERE
                        a.id_artefact = ai.id_artefact
                    AND a.id_artefactprobability = ap.id_artefactprobability
                    AND a.id_artefactscope = ars.id_artefactscope
                    AND a.id_artefacttype = at.id_artefacttype
                    AND atfb.id_artefactscope = ars.id_artefactscope
                    AND atfb.id_artefacttype = at.id_artefacttype
                    AND cha.id_artefact = ai.id_artefactinstance
                    AND atfb.id_building = %d
                    AND cha.id_comb = %d
                ORDER BY ap.id_artefactprobability DESC, ars.id_artefactscope DESC, at.id_artefacttype DESC",
        $this->bid,
        $this->comb );
        $res = $common->getResultArray ( "artefacts_for_building_id_building_id_comb_".$this->bid."_".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR BuildingArtefact::getPanel4";

            return false;
        }

        for ( $i=0; $i<sizeof ( $res ); $i++ )
        {
            $ch = $root->new_child ( "artefact_attach" );
            $ch->set_attribute ( "id", $res[$i]['id_artefactinstance'] );
            $ch->set_attribute ( "prefix", $res[$i]['prefix'] );
            $ch->set_attribute ( "scope", $res[$i]['scopename'] );
            $ch->set_attribute ( "postfix", $res[$i]['typename'] );
            if(preg_match("/^IGROMIR_/", $res[$i]['typename'])) {
                $res[$i]['typename'] = preg_replace(array('/COMB_/', '/COMBCOMPLEX_/', '/ALLIANCE_/', '/WAR_/'), array('', '', '', ''), $res[$i]['typename']);
            }
            if($res[$i]['immortal'] == 'f') {
                list($d,$m,$y) = explode('.', $res[$i]['birth_time']);
                $lt = mktime(0, 0, 0, $m, $d, $y);
                list($d,$m,$y) = explode('.', $res[$i]['death_time']);
                $lt .= '|'.mktime(0, 0, 0, $m, $d, $y);
                $res[$i]['typename'] .= "_".$lt;
                $ch->set_attribute("death_message", sprintf(str_replace('%', '%s', $loc_death_text), $res[$i]['death_time']));
                $ch->set_attribute("lifetime", $lt);
            }
            $ch->set_attribute("postfix", $res[$i]['typename']);

            // их бонусы
            $sql = sprintf ( "select atn.attributename,
                    att.typename,
                    ahb.value
                    from ds_artefacthasbonus ahb,
                    ds_artefact a,
                    ds_artefactinstance ai,
                    ds_attribute at,
                    ds_attributename atn,
                    ds_attributetype att
                    where ahb.id_attribute = at.id_attribute
                    and ahb.id_artefact = a.id_artefact
                    and a.id_artefact = ai.id_artefact
                    and at.id_attributename = atn.id_attributename
                    and at.id_attributetype = att.id_attributetype
                    and ai.id_artefactinstance = %d",
            $res[$i]['id_artefactinstance'] );

            $res2 = $common->getResultArray ( "artefactbonus_id_artefactinstance_".$res[$i]['id_artefactinstance'], $sql, "", "", "GLOBAL_CACHE" );

            if ( $res2 === false ) {
                $this->errormsg .= " SYSTEM ERROR BuildingArtefact::getPanel5";

            }

            for ( $e=0; $e<sizeof ( $res2 ); $e++ ) {

                $ch2 = $ch->new_child ( "bonus" );
                $bonus = (float)$res2[$e]['value'];

                if ( $res2[$e]['typename'] == 'BONUS_RELATIVE' ) {
                    $bonus = $bonus*100;
                    $bonus .= "%25";
                }
                $bonus = "%2B".$bonus;
                $ch2->set_attribute ( "name", trim ( $res2[$e]['attributename'] ) );
                $ch2->set_attribute ( "value", urlencode ( $bonus ) );
            }
        }

        // закладки
        $this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );

        //$panel = "xmldata=";
        //$panel .= $doc->dump_mem();

        //$this->logResponse ( $panel );

        // print $panel;

        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

        $res = "";
        $res2 = "";
        $doc = "";
        $root = "";

        return true;
    }
}

?>