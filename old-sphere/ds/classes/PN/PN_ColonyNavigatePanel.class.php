<?php $CVS_Version = '$Revision$ ; $Date$';

// меню навигации по колони€м

class PN_ColonyNavigatePanel extends UA_GetBuildMenu {

    function __construct() {
        UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    function getPanel () {

        global $params;
        global $common;

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><colonynavigate />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();

        // список колоний
        $res = DS_Comb::GetCombsByUserInstance($params['uiid']);

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            $ch = $root->new_child ( "colony" );
            $ch->set_attribute ( "combname", urlencode ( $res[$i]['combname'] ) );
            $ch->set_attribute ( "coords", urlencode ( $res[$i]['coords'] ) );
            $ch->set_attribute ( "id_comb", $res[$i]['id_comb'] );
            $ch->set_attribute ( "allow",  ($res[$i]['id_comb'] != $this->comb)?1:0);
            $ch->set_attribute ( "capture",  ($res[$i]['is_capture'] == 't')?1:0);

            $sql = "select 1 from ds_attachedbuilding where id_building=26 and active and id_comb=".$res[$i]['id_comb'];
            $res2 = $this->common->GetResultArray("", $sql);
            $ch->set_attribute ( "base", count($res2));

            // ≈сли колони€ временна€
            if ($res[$i]['is_capture'] == 't') {
                // —мотрим процент оставшегос€ пол€ на колонии
                $protectedField = DE_CombProtectiveField::CreateFromCombID((int)$res[$i]['id_comb'])->getProtectiveField();
                // ћинимальное значение защитного пол€, при котором можно отказатьс€ от колонии
                $minField = (int)DE_WorldSettings::CreateForCurentWorld()->get('MIN_PROTECTIVE_FIELD_TO_LEAVE_COLONY');
                if ($protectedField < $minField) {
                    $ch->set_attribute ( "disable",  1);
                } else {
                    $ch->set_attribute ( "disable",  0);
                }
            }

        }

        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

        return true;
    }
}

?>