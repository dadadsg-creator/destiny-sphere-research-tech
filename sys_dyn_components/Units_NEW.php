<?
function print_rr ($array) {
    echo "<xmp>";
    print_r ( $array ) ;
    echo "</xmp>";
}

if( !class_exists( 'DS_Common' ) ){
    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/DS_Common.php";
}

//if( !class_exists( 'Object' ) ){

    require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/Object.class.php";
//}
//if( !class_exists( 'FastTemplate' ) ){

    require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/FastTemplate.class.php";
//}

class Units {

    var $tpl;
    var $common;

    var $lang;
    //  id_race
    var $race;
    //
    var $racename;

    var $pageId;

    var $attacktypes = array();
    var $attacktypesdescriptuon = array();
    var $attacktypesname = array();
    var $attacktypesvalues = array();

    /*
    Array Format:
    $Array
    [buildingname]
    [ParamName] = Value;
    [ParamName] = Value;
    */
    var $resources = array( 'PRODUCTION_COSTS'  => array ( 'KADERIUM' => array(), 'NANO_CRYSTAL' => array(), 'STASIS_ENERGY' => array(), 'FOOD' => array(), 'PRODIUM' => array(), 'WORKER' => array(), 'CONSTRUCTION_TIME' => array() ) );
    var $science = array( 'KADERIUM' => array(), 'NANO_CRYSTAL' => array(), 'STASIS_ENERGY' => array(), 'FOOD' => array(), 'PRODIUM' => array(), 'TRADE' => array(), 'SPYING' => array() );
    var $localize;

    var $raceLocalize = array(
    'TECH'  => 'PORTAL_UNITS_TECH',
    'BIO'   => 'PORTAL_UNITS_BIO',
    'PSI'   => 'PORTAL_UNITS_PSI'
    );

    var $pageparent = array (
    'TECH'  =>  'warunits_tech_new',
    'BIO'   =>  'warunits_bio_new',
    'PSI'   =>  'warunits_psi_new'
    );
    var $parenttype;
    var $types;

    var $vulnerability;
    var $vulnerabilitytypes;
    var $vulnerabilityvalues;


    function Units( &$parent, $lang, $race, $p, $type ){

        $this->tpl =& new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates/');

        $this->common =& new DS_Common;
        $this->common->setDbId(0);

        $this->lang = $this->getLangIdByName( $lang );
        $this->race = ($race > 0) ? $race : 1;
        $this->types = array_flip($this->pageparent);
        if ($type != '') {
            $this->type = $type;
        } else {
            switch ($race)
            {
                case 1:
                    $this->type = 'TECH';
                    break;
                case 2:
                    $this->type = 'BIO';
                    break;
                case 3:
                    $this->type = 'PSI';
                    break;
                default:
                    $this->type = 'TECH';
                    break;
            }
        } 

        assert( $this->getRaceName() );
        assert( $this->getResource() );
        assert( $this->getScience() );
        assert( $this->getLocalize() );
        assert( $this->getAttackType() );
        assert( $this->getVulnerabilityType() );

        assert( $this->showUnits() );
    }

    function showUnits( ){
        $this->tpl->define( array( 'units' => '/Units/Units_NEW.tpl.html' ) );
        $this->tpl->defineDynamic('unitItem', 'units');

        $assign['PAGE_TITLE'] = local( $this->raceLocalize[ $this->racename ] );
        $assign['PAGE_HEADER'] = $this->getHeader();
        
        $races = $this->getRacesList();
        $assign['RACE1'] = $races[1]; 
        $assign['RACE2'] = $races[2];
        $assign['RACE3'] = $races[3];

        $assign['SCRIPT'] = "";
        foreach ($this->attacktypesdescriptuon as $key => $value)
        {
            $assign['SCRIPT'] .= "a['".$key."'] = '".str_replace("\r","<br>",str_replace("\n","",$value))."';"."\n";
        }

        foreach ($this->attacktypesname as $key => $value)
        {
            $assign['SCRIPT'] .= "b['".$key."'] = '".$value."';"."\n";
        }
        $valnerability = array("1"=>"—лаба€ у€звимость","2"=>"—редн€€ у€звимость","3"=>"—ильна€ у€звимость","4"=>"Ёкстремальна€ у€звимость");
        $valnerability = array("1"=>"—лаба€","2"=>"—редн€€","3"=>"—ильна€","4"=>"Ёкстремальна€");
//$valnerability = array("1"=>"Niedrige verwundbarkeit","2"=>"Mittlere verwundbarkeit","3"=>"Hohe verwundbarkeit");
        foreach ($valnerability as $key => $value)
        {
            $assign['SCRIPT'] .= "c['".$key."'] = '".$value."';"."\n";
        }
/*
        $attack = array("1"=>"—лаба€","2"=>"—редн€€","3"=>"—ильна€","4"=>"Ёкстремальна€");
        foreach ($attack as $key => $value)
        {
            $assign['SCRIPT'] .= "d['".$key."'] = '".$value."';"."\n";
        }
*/
        foreach($this->localize as $unitName => $text ){

            $assign['UNIT_IMAGE'] = $unitName;
            $assign['LOCAL_UNIT_NAME'] = $text['name']; //.$unitName
            
            $assign['UNIT_ID'] = $text['id'];

            foreach ( $this->resources['FIGHT_ATTRIBUTE'] as $attributeName => $unitResouces )
            {
                $assign['ATTRIBUTES_'.$attributeName] = isset($unitResouces[$unitName]) ? $unitResouces[$unitName] : 0 ;
                $assign['ATTRIBUTES_'.$attributeName] = ( $assign['ATTRIBUTES_'.$attributeName] < 1 ) ? $assign['ATTRIBUTES_'.$attributeName]*100 : $assign['ATTRIBUTES_'.$attributeName] ;
                
                /**
                 *  «атыка дл€ пересчета защиты в защитное поле
                 */
                if($attributeName == 'DEFENCE' && $this->_getDefenceFieldSetting())
                {
                    $defenceField = round((1/(1 - $unitResouces[$unitName]) - 1) * 100);
                    $assign['ATTRIBUTES_'.$attributeName] = $defenceField;
                }
            }

            foreach ( $this->resources['PRODUCTION_COSTS'] as $attributeName => $unitResouces ){
                $assign['RES_CONSTRUCT_'.$attributeName] = isset($unitResouces[$unitName]) ? $unitResouces[$unitName] : 0 ;
            }

            foreach ( $this->science as $branchName => $scienceLevel ){
                $assign['SCI_'.$branchName] = isset($scienceLevel[$unitName]) ? $scienceLevel[$unitName] : 1 ;
            }

            $assign['UNIT_DESCRIPTION'] = nl2br( $this->localize[$unitName]['desc'] );

            $assign['UNIT_ATTACK_IMAGES'] = "";
            if (count($this->attacktypes[ $unitName ])>0)
            {
                $prefix = array("1"=>"MIN_","2"=>"MED_","3"=>"MAX_","4"=>"EXT_");
                foreach ( $this->attacktypes[ $unitName ] as $value)
                {
                    $prefix = array("1"=>"MIN_","2"=>"MED_","3"=>"MAX_","4"=>"EXT_");

                    if ($value == "EMPTY")
                    {
                        $assign['UNIT_ATTACK_IMAGES'] .= '<img width=23 height=23 src="img/attack_type/'.$value.'.gif" border=0>&nbsp;';
                    }
                    else
                    {
                        $str = '<a href="#" onMouseOver="ShowHint(\''.$value.'\',\''.$this->attacktypesvalues[ $unitName ][ $value ].'\' );" onMouseOut="nd();"><img width=23 height=23 src="img/attack_type/'.$prefix[ $this->attacktypesvalues[ $unitName ][ $value ] ].$value.'.gif" border=0></a>&nbsp;';
//echo "<xmp>".$str."</xmp>";
                        $assign['UNIT_ATTACK_IMAGES'] .= $str;
                    }
                }
            } else {
                $assign['UNIT_ATTACK_IMAGES'] = '<img width=23 height=23 src="img/attack_type/EMPTY.gif" border=0>&nbsp;';
            }

            $assign['UNIT_DAMAGE_IMAGES'] = "";
            if (count($this->vulnerabilitytypes[ $unitName ])>0)
            {
                foreach ( $this->vulnerabilitytypes[ $unitName ] as $value)
                {
                    $prefix = array("1"=>"MIN_","2"=>"MED_","3"=>"MAX_","4"=>"EXT_");

                    if ($value == "EMPTY")
                    {
                        $assign['UNIT_DAMAGE_IMAGES'] .= '<img width=25 height=25 src="img/damage_type/'.$prefix[ $this->vulnerabilityvalues[ $unitName ][ $value ] ].$value.'.gif" border=0>&nbsp;';
                    }
                    else
                    {
                        $assign['UNIT_DAMAGE_IMAGES'] .= '<a href="#" onMouseOver="ShowHint(\''.$value.'\',\''.$this->vulnerabilityvalues[ $unitName ][ $value ].'\' );" onMouseOut="nd();"><img width=25 height=25 src="img/damage_type/'.$prefix[ $this->vulnerabilityvalues[ $unitName ][ $value ] ].$value.'.gif" border=0></a>&nbsp;';
                    }

                }

            }

            $this->tpl->assign( $assign );
            $this->tpl->parse('UNIT', '.unitItem');
        }

        $this->tpl->parse('MAIN', 'units');

        echo $this->tpl->fetch('MAIN');

        return true;
    }
    
    function _getDefenceFieldSetting()
    {
        $sql = "
            SELECT
                value
            FROM
                ds_global_settings
            WHERE
                settings_name = 'BATTLE_CALC_USE_DEFENCE_FIELD'
            ";
        
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        
        if(count($res) == 0)
        {
            return false;
        }
        
        if($res[0]['value'] == 1)
        {
            return true;
        }
        
        return false;
    }

    function getAttackType()
    {
        $sql = "

            SELECT
                u.unitname,
                uat.attackname,
                (SELECT ds_localizedtextlanguage.text FROM ds_localizedtext INNER JOIN ds_localizedtextlanguage ON(ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext) WHERE ds_localizedtextlanguage.id_language = ".$this->lang." AND ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext AND uat.localizedtext = ds_localizedtext.id_localizedtext) AS localizedtext,
                (SELECT ds_localizedtextlanguage.text FROM ds_localizedtext INNER JOIN ds_localizedtextlanguage ON(ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext) WHERE ds_localizedtextlanguage.id_language = ".$this->lang." AND ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext AND uat.description = ds_localizedtext.id_localizedtext) AS description,
                uhat.value
            FROM
                ds_unit u
                INNER JOIN ds_unithasattacktype uhat ON (u.id_unit = uhat.id_unit)
                INNER JOIN public.ds_unitattacktype uat ON (uhat.id_unitattacktype = uat.id_attacktype)
                INNER JOIN ds_unitcanbebuildbyrace ucbbr ON (u.id_unit = ucbbr.id_unit)
            WHERE
                ucbbr.id_race = ".$this->race."
            ORDER BY
                uat.id_attacktype
            ";

        $res = $this->common->getResultArray ( "getAttackType_by_race_id_".$this->race, $sql,"", "", "" );

        assert( empty( $this->common->errormsg ) );

        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getResource::getResource";
            return false;
        }

        foreach ( $res as $row )
        {
/*
            if ($row['unitname'] == 'TRANSPORTER')
            {
                print_rr($row);
            }
*/
            $this->attacktypesdescriptuon[ $row['attackname'] ] = $row['description'];
            $this->attacktypesname[ $row['attackname'] ] = $row['localizedtext'];
            $this->attacktypes[ $row['unitname'] ][] = $row['attackname'];
            $this->attacktypesvalues[ $row['unitname'] ][ $row['attackname'] ] = $row['value'];
        }
        return true;

    }

    function getVulnerabilityType()
    {
        $sql = "
                SELECT
                    u.unitname,
                    uat.attackname,
                    (SELECT ds_localizedtextlanguage.text FROM ds_localizedtext INNER JOIN ds_localizedtextlanguage ON(ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext) WHERE ds_localizedtextlanguage.id_language = ".$this->lang." AND ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext AND uat.localizedtext = ds_localizedtext.id_localizedtext) AS localizedtext,
                    (SELECT ds_localizedtextlanguage.text FROM ds_localizedtext INNER JOIN ds_localizedtextlanguage ON(ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext) WHERE ds_localizedtextlanguage.id_language = ".$this->lang." AND ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext AND uat.description = ds_localizedtext.id_localizedtext) AS description,
                    uhat.value
                FROM
                    ds_unit u
                    INNER JOIN ds_unithasvulnerability uhat ON (u.id_unit = uhat.id_unit)
                    INNER JOIN public.ds_unitattacktype uat ON (uhat.id_unitattacktype = uat.id_attacktype)
                    INNER JOIN ds_unitcanbebuildbyrace ucbbr ON (u.id_unit = ucbbr.id_unit)
                WHERE
                    ucbbr.id_race = ".$this->race."
                ORDER BY
                    uat.id_attacktype

            ";

        $res = $this->common->getResultArray ( "getVulnerabilityType_by_race_id_".$this->race, $sql,"", "", "" );

        assert( empty( $this->common->errormsg ) );

        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getResource::getResource";
            return false;
        }
//var_dump($res);

        foreach ( $res as $row )
        {
//echo $row['unitname']." -- ".$row['localizedtext']."<br>";
            $this->attacktypesdescriptuon[ $row['attackname'] ] = $row['description'];
            $this->attacktypesname[ $row['attackname'] ] = $row['localizedtext'];
            $this->vulnerabilitytypes[ $row['unitname'] ][] = $row['attackname'];
            $this->vulnerabilityvalues[ $row['unitname'] ][ $row['attackname'] ] = $row['value'];
        }
        return true;

    }
    
    function getRacesList()
    {
        $sql = "
            SELECT
                l.text, r.id_race
            FROM
                ds_race AS r
                INNER JOIN ds_localizedtextlanguage AS l ON r.id_localizedname = l.id_localizedtext
            WHERE 
                l.id_language = {$this->lang} 
            ";
        
        $k = $this->common->getResultArray("", $sql, "", "", "");
        
        if($k)
        {
            foreach($k as $key => $val)
            {
                $res[$val['id_race']] = $val['text'];
            }
            
            return($res);
        }
        
        return false;
    }


    function getLangIdByName( $langName ){

        $sql = sprintf(
        "
        SELECT
            l.id_language
        FROM
            ds_language l
        WHERE
            l.language = '%s'
            OR l.shortname = '%s'
        ;",
        pg_escape_string( $langName ),
        pg_escape_string( $langName )
        );

        $res = $this->common->getQueryArray( $sql );
        assert( empty($this->common->errormsg) );

        return ( isset($res[0]['id_language']) ? $res[0]['id_language'] : 3);
    }

    function getHeader( ){

        $out = array();
        foreach ( $this->raceLocalize as $typeName => $shortLocalize ){

            if( $typeName == $this->type ){
                continue;
            }
            $out[] = '<b><a href="?p='.$this->pageparent[$typeName].'&type='.$typeName.'" class=wnd_header_txt>'.local( $shortLocalize ).'</a></b>';
        }

        $out = implode( "&nbsp;|\n", $out );

        return $out;
    }

    function getRaceName( ){

        $sql = sprintf (
        "
        SELECT
            racename
        FROM
            ds_race
        WHERE
            id_race= %d
        ;",
        $this->race
        );

        $res = $this->common->getResultArray ( "getRaceName", $sql,"", "", "" );

        assert( empty( $this->common->errormsg ) );

        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getRaceName::getRaceName";
            return false;
        }

        $this->racename = $res[0]['racename'];

        return true;
    }


    function getResource( ){

        $sql = sprintf (
        "
        SELECT
            u.unitname,
            uct.containertypename,
            uha.value,
            an.attributename,
            at.typename

        FROM
            ds_unit u
            INNER JOIN ds_unitorcontainertype uct ON ( u.id_unitcontainertype = uct.id_unitcontainertype )
            INNER JOIN ds_unithasattribute uha ON ( u.id_unit = uha.id_unit )
            INNER JOIN ds_attribute a ON ( uha.id_attribute = a.id_attribute )
            INNER JOIN ds_attributename an ON ( a.id_attributename = an.id_attributename )
            INNER JOIN ds_attributetype at ON ( a.id_attributetype = at.id_attributetype )
            INNER JOIN ds_unitcanbebuildbyrace ucb ON u.id_unit = ucb.id_unit

        WHERE
             u.id_unit > 0
             AND a.id_attributetype IN (
                SELECT
                    id_attributetype
                FROM
                    ds_attributetype
                WHERE
                    typename IN ('PRODUCTION_COSTS', 'FIGHT_ATTRIBUTE')
             )
             AND uct.containertypename = 'ARMY'
             AND ucb.id_race= %d

        ORDER BY
              u.id_unit;
        ",
        $this->race
        );
        $res = $this->common->getResultArray ( "getResource", $sql,"", "", "" );
        //      die( $this->common->errormsg."\n<br>\n".$sql );
        assert( empty( $this->common->errormsg ) );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getResource::getResource";
            return false;
        }

        foreach($res as $row)
        {
            $this->resources[ $row['typename'] ][ $row['attributename'] ][ $row['unitname'] ] = $row['value'];
        }

        return true;
    }


    function getScience( ){
        /// get needed Sciences
        $sql = sprintf (
        "
        SELECT
            u.unitname,
            sb.branchname,
            s.level,
            ltl.text
        FROM
            ds_unit u
            INNER JOIN ds_unitorcontainertype uct ON ( u.id_unitcontainertype = uct.id_unitcontainertype )
            INNER JOIN ds_unitneedsscience uns ON u.id_unit = uns.id_unit
            INNER JOIN ds_science s ON uns.id_science = s.id_science
            INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
            INNER JOIN ds_localizedtext  lt ON sb.id_localizedname = lt.id_localizedtext
            INNER JOIN ds_localizedtextlanguage  ltl ON lt.id_localizedtext = ltl.id_localizedtext
            INNER JOIN ds_unitcanbebuildbyrace ucb ON u.id_unit = ucb.id_unit
        WHERE
             ltl.id_language = %d
             AND ucb.id_race = %d
             AND uct.containertypename = 'ARMY'
        ORDER BY
              u.id_unit;
        ",
        $this->lang,
        $this->race
        );

        $res = $this->common->getResultArray ( "getScience", $sql,"", "", "" );
        assert( empty( $this->common->errormsg ) );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getScience::getScience";
            return false;
        }

        foreach ( $res as $row ){

            $this->science[ $row['branchname'] ]['text'] = $row['text'];
            $this->science[ $row['branchname'] ][ $row['unitname'] ] = $row['level'];
        }

        return true;
    }



    function getLocalize( )
    {
        /// get localized name & description
        $sql = "
                SELECT
                    u.unitname, u.id_unit,
                    ltl.text AS localunitname,
                    ltl2.text AS localunitdescription,
                    (SELECT ltl3.text from ds_localizedtext as lt3, ds_localizedtextlanguage as ltl3 WHERE lt3.id_localizedtext = ltl3.id_localizedtext AND ltl3.id_language = ltl.id_language AND lt3.id_localizedtext = u.id_extendeddescription) AS localunitextendeddescription
                FROM
                    ds_unit u
                    INNER JOIN ds_unitorcontainertype uct ON (u.id_unitcontainertype = uct.id_unitcontainertype)
                    INNER JOIN ds_unitcanbebuildbyrace ucb ON (u.id_unit = ucb.id_unit)
                    INNER JOIN ds_localizedtext lt ON (u.id_localizedname = lt.id_localizedtext)
                    INNER JOIN ds_localizedtextlanguage ltl ON (lt.id_localizedtext = ltl.id_localizedtext)
                    INNER JOIN ds_localizedtextlanguage ltl2 ON (ltl.id_language = ltl2.id_language)
                    INNER JOIN ds_localizedtext lt2 ON (u.id_localizeddescription = lt2.id_localizedtext)
                    INNER JOIN ds_unithasclass uhc ON (u.id_unit = uhc.id_unit)
                    INNER JOIN ds_unitclass uc ON (uhc.id_unitclass = uc.id_unitclass) AND
                    (lt2.id_localizedtext = ltl2.id_localizedtext)
                WHERE
                    (ucb.id_race = '".$this->race."') AND
                    (ltl.id_language = '".$this->lang."') AND
                    (uct.containertypename = 'ARMY')
                ORDER BY
                    uc.classnumber
            ";

        $res = $this->common->getResultArray( "getLocalize", $sql,"", "", "" );
        assert( empty( $this->common->errormsg ) );
        if($res === false) 
        {
            $this->errormsg = "SYSTEM ERROR getLocalize::getLocalize";
            return false;
        }

        foreach($res as $row)
        {
            $this->localize[ $row['unitname'] ][ 'name' ] = $row['localunitname'];
            $this->localize[ $row['unitname'] ][ 'id' ] = $row['id_unit'];
            if ( !empty($row['localunitextendeddescription']) )
            {
                $this->localize[ $row['unitname'] ][ 'desc' ] = stripslashes($row['localunitextendeddescription']);
            }
            else
            {
                $this->localize[ $row['unitname'] ][ 'desc' ] = stripslashes($row['localunitdescription']);
            }
        }
        return true;
    }
}

//ѕровер€м не закрыта ли игра
require_once('ds/include/GameDown.inc.php');
if (GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();

}

$test = new Units($this, constant('DEFAULT_LONG_LANG'), $unitRaceID, $_GET[p], $_GET['type']);
?>