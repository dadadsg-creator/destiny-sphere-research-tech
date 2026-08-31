<?php $CVS_Version = '$Revision$ ; $Date$';

// меню религии

class PN_ReligyPanel extends UA_GetBuildMenu {

    // Time format - UnixTimestamp
    public $eventStartTime = 0;
    public $eventCurTime = 0;
    public $eventEndTime = 0;

    public $eventPercent = 0;

    public $eventName = '';
    public $eventNameArr = array ( 'RELIGION_CONNECT', 'RELIGION_CANCEL' );


    function __construct() {

        UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    function getPanel () {

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><religion />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();

        // Список религий с параметрами
        $sql = sprintf ("
                SELECT
                      r.id_religion,
                      r.religionname,
                      ltl.text,
                      an.attributename,
                      rha.value,
                      (
                        SELECT
                            rs.statusname
                        FROM
                            ds_userhasreligion uhr,
                            ds_religionstatus rs
                        WHERE
                            rs.id_status = uhr.id_status
                            AND uhr.id_religion = r.id_religion
                            AND uhr.id_userinstance = %d
                      ) as status,
                      
                      (
                        SELECT
                            rs.statusname
                        FROM
                            ds_userhasreligion uhr,
                            ds_religionstatus rs
                        WHERE
                            rs.id_status = uhr.id_status
                            AND NOT uhr.id_religion = r.id_religion
                            AND uhr.id_userinstance = %d
                      ) as status_other

                FROM
                    ds_religion r,
                    ds_religionhasattribute rha,
                    ds_attribute a,
                    ds_attributename an,
                    ds_localizedtextlanguage ltl,
                    (
                        SELECT
                            rnb.id_religion
                        FROM
                            ds_religionneedsbuilding rnb,
                            ds_attachedbuilding ab,
                            ds_comb c
                        WHERE
                            ab.id_building = rnb.id_building
                            AND ab.active = 't'
                            AND ab.id_comb = c.id_comb
                            AND c.id_userinstance = %d
                            AND
                            rnb.id_building = ".$this->bid."
                        GROUP BY
                            rnb.id_religion
                    ) as rel

                WHERE

                     r.id_religion = rel.id_religion
                     AND rha.id_religion = r.id_religion
                     AND a.id_attribute = rha.id_attribute
                     AND an.id_attributename = a.id_attributename
                     AND ltl.id_localizedtext = r.id_religiondesc
                     AND ltl.id_language = %d

                ORDER BY
                      status, id_religion;",
                $this->uiid,
                $this->uiid,
                $this->uiid,
                $this->langId
            );

        $res = $this->common->getResultArray ( "ds_userhasreligion_id_userinstance_".$this->uiid, $sql, "", $this->uiid, "LOCAL_CACHE" );

        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR - ReligyPanel::Can't get Religions and Status";
            return false;
        }

        $last_religion_id = 0;
        $has_active_religion = false;

        foreach( $res as $key => $row ){

            if( $last_religion_id != $row['id_religion'] ){

                $ch = $root->new_child ( "religy" );
                $ch->set_attribute ( "name",    urlencode ( $row['religionname'] ) );
                $ch->set_attribute ( "ID",  $row['id_religion'] );
                //$ch->set_attribute ( "desc",  urlencode ( $row['text'] ) );
                $desc = $ch->new_child ( "desc" );
                $text = $doc->create_text_node( urlencode ( $row['text'] ) );
                $desc->append_child( $text );

                switch( $row['status'] )
                {
                    case 'RELIGI_STATUS_CONNECT':
                        $religion_status = 'RELIGI_CONNECT_ACTION';
                        $has_active_religion = true;

                        assert($this->getEventTime('RELIGION_CONNECT'));

                        $ch->set_attribute ( "percent", $this->eventPercent );
                        $ch->set_attribute ( "date", date( 'j.m h:i', $this->eventEndTime ) );
                        break;

                    case 'RELIGI_STATUS_ACTIVE':
                        $religion_status = 'RELIGI_KICK';
                        $has_active_religion = true;
                        break;

                    case 'RELIGI_STATUS_KICK':
                        $religion_status = 'RELIGI_KICK_ACTION';
                        $has_active_religion = true;
                        assert( $this->getEventTime( 'RELIGION_CANCEL' ) );
                        $ch->set_attribute ( "percent", $this->eventPercent );
                        $ch->set_attribute ( "date", date( 'd.m H:i', $this->eventEndTime ) );
                        break;

                    default:
                        if( !$has_active_religion && $row['status_other'] == "")
                        {
                            // Если Статус Null и нет принятой религии, то разрешаем принять
                            $religion_status = 'RELIGI_CONNECT';
                        }else{
                            // Если Статус Null и есть принятая религия, то запрещаем ее принять
                            $religion_status = 'RELIGI_NOTACCESS';
                        }
                }
                $ch->set_attribute ( "status", urlencode ( $religion_status ) );

                $last_religion_id = $row['id_religion'];
            }
            $ch_bonus = $ch->new_child ( "bonus" );

            $ch_bonus->set_attribute ( "name", urlencode ( $row['attributename'] ) );
            $ch_bonus->set_attribute ( "value", ($row['value']*100) );
        }
        $this->getBuildingTabs ( $root, $this->bid, $this->langId, $this->tab );
        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );
        return true;
    }


    function getEventTime($eventName)
    {

        $sql = sprintf( "
                SELECT
                      EXTRACT(EPOCH FROM eq.eventstarttime)::bigint as eventstarttime,
                      EXTRACT(EPOCH FROM now())::bigint as eventcurtime,
                      EXTRACT(EPOCH FROM eq.eventendtime)::bigint as eventendtime,
                      e.eventname

                FROM
                    ds_event e,
                    ds_eventqueue eq,
                    ds_eventparticipant ep

                WHERE
                     e.id_event = eq.id_event
                     AND eq.id_eventqueue = ep.id_eventqueue
                     AND eq.istrash = false
                     AND e.eventname = '%s'
                     AND ep.id_userinstance = %d
                ",
                $eventName,
                $this->uiid
            );

        $res = $this->common->getQueryArray($sql);

        assert ( empty($this->common->errormsg) );

        if( count($res) == 0 ){
            $this->errormsg = "SYSTEM ERROR - ReligyPanel:: Can't get EventEndTime - no such event";
            return false;
        }

        $this->setEventName( $res[0]['eventname'] );

        $this->setEventStartTime( $res[0]['eventstarttime'] );
        $this->setEventCurTime( $res[0]['eventcurtime'] );
        $this->setEventEndTime(   $res[0]['eventendtime'] );

        $eventPerc = ( ( $this->eventCurTime - $this->eventStartTime ) / ( $this->eventEndTime - $this->eventStartTime ) ) * 100;

        $this->setEventPercent( $eventPerc );

        return true;
    }

    /**
     * Устанавливаем значение
     * Параметры:
     * $time - целочисленное кол-ва более 0
     */
    function setEventStartTime( $time ){
        settype ( $time, 'integer' );
        $this->eventStartTime = $time;
        assert( $this->eventStartTime >= 0 );
        return true;
    }//END OF METHOD setEventStartTime( $time )

    /**
     * Устанавливаем значение
     * Параметры:
     * $time - целочисленное кол-ва более 0
     */
    function setEventCurTime( $time ){
        settype ( $time, 'integer' );
        $this->eventCurTime = $time;
        assert( $this->eventCurTime >= 0 );
        return true;
    }//END OF METHOD setEventCurTime( $time )

    /**
     * Устанавливаем значение
     * Параметры:
     * $time - целочисленное кол-ва более 0
     */
    function setEventEndTime( $time ){
        settype ( $time, 'integer' );
        $this->eventEndTime = $time;
        assert( $this->eventEndTime >= 0 );
        return true;
    }//END OF METHOD setEventEndTime( $time )

    /**
     * Устанавливаем значение
     * Параметры:
     * $name - строка  ( 'RELIGION_CONNECT', 'RELIGION_CANCEL' )
     */
    function setEventName( $name ){
        $this->eventName = ereg_replace( '[^A-Z\_]', '', $name );
        assert( in_array( $this->eventName, $this->eventNameArr ) );
        return true;
    }//END OF METHOD setEventName( $name )

    /**
     * Устанавливаем значение
     * Параметры:
     * $perc - целочисленное кол-ва более 0
     */
    function setEventPercent( $perc ){
        settype ( $perc, 'integer' );
        $this->eventPercent = round ( $perc );
        if ($this->eventPercent < 0) $this->eventPercent = 0;
        return true;
    }//END OF METHOD setEventPercent( $perc )
}
?>