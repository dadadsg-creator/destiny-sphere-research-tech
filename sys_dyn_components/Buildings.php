<?
function print_rr ($array) {
    echo "<xmp>";
    print_r ( $array ) ;
    echo "</xmp>";
}

require_once $_SERVER['DOCUMENT_ROOT']."/sys/define.php";

if( !class_exists( 'DS_Common' ) ){
    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/DS_Common.php";
}

require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/Object.class.php";
    
require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/FastTemplate.class.php";

class Buildings 
{   
    var $tpl;
    var $common;
    
    var $pageId;
    var $lang;
    //  id_race
    var $race;
    //
    var $racename;
    
    /*
    Array Format:
        $Array
            [buildingname]
                    [ParamName] = Value;
                    [ParamName] = Value;
    */
    var $resources = array(
        'TURN_PRODUCTION'   => array ( 'KADERIUM' => array(), 'NANO_CRYSTAL' => array(), 'STASIS_ENERGY' => array(), 'FOOD' => array(), 'PRODIUM' => array() ),
        'TURN_UPKEEP'       => array ( 'STASIS_ENERGY' => array() ),
        'PRODUCTION_COSTS'  => array ( 'KADERIUM' => array(), 'NANO_CRYSTAL' => array(), 'STASIS_ENERGY' => array(), 'FOOD' => array(), 'PRODIUM' => array(), 'WORKER' => array(), 'CONSTRUCTION_TIME' => array() )
    );
    var $science = array( 'KADERIUM' => array(), 'NANO_CRYSTAL' => array(), 'STASIS_ENERGY' => array(), 'FOOD' => array(), 'PRODIUM' => array(), 'TRADE' => array(), 'SPYING' => array() );
    var $buildings;
    var $grounds;
    
    var $localize = array();

    var $points;
    
    var $type;
    var $typesLocalize = 
            array(
                    'res'       => 'PORTAL_BUILDINGS_RES',
                    'military'  => 'PORTAL_BUILDINGS_MILITARY',
                    'spec'      => 'PORTAL_BUILDINGS_SPEC',
                    'spy'       => 'PORTAL_BUILDINGS_SPY',
                    'live'      => 'PORTAL_BUILDINGS_LIVE',
                    'trade'     => 'PORTAL_BUILDINGS_TRADE',
                    'depot'     => 'PORTAL_BUILDINGS_DEPOT ',
                    'tech'      => 'PORTAL_BUILDINGS_TECH'
            );
            
    var $types = array( 
        'res'       => "'KADERIUM_FORGE', 'LARGE_KADERIUM_FORGE','PRODIUM_COLLECTOR', 'ENERGY_COLLECTOR', 'SAIL_FOREST', 'SAIL_TRAP', 'HUGE_KADERIUM_FORGE', 'HUGE_NANO_CRYSTALLIZER', 'NANO_CRYSTALLIZER', 'CLONE_FARM','FARM', 'PLANTATION', 'HYDRO_FARM', 'LARGE_NANO_CRYSTALLIZER', 'LARGE_PRODIUM_COLLECTOR', 'WELL_LADLER', 'SOURCE'",
        'military'  => "'HEAVY_WEAPON_MANUFACTURE', 'LIGHT_WEAPON_MANUFACTURE', 'MILITARY_BASE', 'TRAINING_CAMP'",
        'spec'      => "'ALLIANCE_CONNECTOR', 'CENTRAL_GATE', 'CLOUD_ENDERS_TEMPLE', 'COLONIZATION_CENTER', 'CRYSTAL_WEAVER', 'GONDOLA_TRANSPORTER', 'KADERIUM_PRESS', 'CONSTRUCTION_FACTORY', 'MESH_BOILER', 'MIND_FACTORY', 'RING_GATE', 'RUSHERS_TEMPLE', 'SHELTER'",
        'spy'       => "'SPYING_CENTER', 'TUNNEL_RIVER_SCANNER', 'SECRET_SERVICE_CENTER'",
        'live'      => "'CITY', 'VILLAGE', 'RESIDENTIAL_BUILDING'",
        'trade'     => "'INTERRING_TRADE_CONNECTOR', 'LOCAL_TRADE_CONNECTOR', 'RING_TRADE_CONNECTOR'",
        'depot'     => "'MEDIUM_FOOD_SILO', 'MEDIUM_KADERIUM_SILO', 'MEDIUM_NANO_SILO', 'MEDIUM_PRODIUM_SILO', 'MEDIUM_STASIS_SILO', 'SMALL_FOOD_SILO', 'SMALL_KADERIUM_SILO', 'SMALL_NANO_SILO', 'SMALL_PRODIUM_SILO', 'SMALL_STASIS_SILO'",
        'tech'      => "'ARCHEOLOGICAL_INSTITUTE', 'ARCHEOLOGICAL_OUTPOST', 'ARTEFACT_TOWER', 'RESEARCH_CENTER'"
    );
    
    //var $buildingTypes = array("res", "military", "spec", "spy", "live", "trade", "depot", "tech");
    var $onlyBuild;
    
    
    function Buildings( &$parent, $lang, $race, $type, $buildName = '')
    {   
        $this->tpl =& new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates/');
        
        $this->common = new DS_Common;
        $this->common->setDbId(0);
        
        $this->pageId = $parent->Page['id'];
        
        $this->lang = $this->getLangIdByName ( $lang );
        $this->race = ($race > 0) ? $race : 1;
        $this->type = ($type != '' ) ? $type : 'spec';
        
        $this->onlyBuild = $buildName ? pg_escape_string(trim($buildName)) : '';
        
        assert( $this->_initType() );
        assert( $this->getBuildingTypes() );
        assert( $this->getRaceName() );
        assert( $this->getResource() );
        assert( $this->getScience() );
        assert( $this->getDementions() );
        assert( $this->getGrounds() );
        assert( $this->getLocalize() );
        assert( $this->getPoints() );
        assert( $this->showBuildings() );
    }
    
    private function _initType()
    {
        if($this->onlyBuild)
        {
            $this->type = strtolower(DE_Building::CreateFromName($this->onlyBuild)->getType());
        }
        
        return true;
    }
    
    
    private function showBuildings()
    {
        $this->tpl->define( array( 'buildings' => '/Buildings/Buildings.tpl.html' ) );
        $this->tpl->defineDynamic('buildingItem', 'buildings');

        $assign['PAGE_TITLE'] = local($this->typesLocalize[$this->type]);
        $assign['PAGE_HEADER'] = $this->getHeader();
        $assign['PORTAL_BUILDINGS_BUILD_POINTS'] = local( 'PORTAL_BUILDINGS_BUILD_POINTS' );
        $assign['PORTAL_BUILDINGS_NEED_BUILDINGS'] = local( 'PORTAL_BUILDINGS_NEED_BUILDINGS' );
        
        foreach($this->localize as $buildingName => $text )
        {
            $assign['LOCAL_BUILDING_NAME'] = $text['name']."<br>".$buildingName;
        }
        
        foreach($this->localize as $buildingName => $text )
        {
            $assign['BUILDING_IMAGE'] = strtolower( $this->racename."_".$buildingName );
            $assign['LOCAL_BUILDING_NAME'] = $text['name'];
            
            $assign['GROUND_SOTE'] = '';
            foreach ( $this->grounds[$buildingName] as $cellType => $grounds)
            {
                foreach ( $grounds as $groundType )
                {   
                    $assign['GROUND_SOTE'] .= '<td><img src="/img/support/sotes/ground_'.strtolower( $cellType."_".$groundType ).'.gif" border="0"></td>';
                }
            }
            

            foreach ( $this->resources['PRODUCTION_COSTS'] as $attributeName => $buildingResouces )
            {
                $assign['RES_CONSTRUCT_'.$attributeName] = isset($buildingResouces[$buildingName]) ? $buildingResouces[$buildingName] : 0 ;
            }
            
            foreach ( $this->science as $branchName => $scienceLevel )
            {
                $assign['SCI_'.$branchName] = isset($scienceLevel[$buildingName]) ? $scienceLevel[$buildingName] : 1 ;
            }
            
            $assign['NEED_BUILDINGS'] = '';
            if( isset( $this->buildings[$buildingName] ) )
            {
                foreach ( $this->buildings[$buildingName] as $needBuilding => $text )
                {
                    $assign['NEED_BUILDINGS'] .= '<img src="/img/support/builds/small/'.strtolower( $this->racename."_".$needBuilding ).'.gif" alt="'.$text.'" border="0">';
                }
            }

            
            if( isset($this->resources['TURN_UPKEEP']['STASIS_ENERGY'][$buildingName]) )
            {   
                $assign['TURN_UPKEEP'] = $this->resources['TURN_UPKEEP']['STASIS_ENERGY'][$buildingName];   
            }else{
                $assign['TURN_UPKEEP'] = '-';
            }
            

            $TURN_PRODUCTION = false;
            
            foreach ( $this->resources['TURN_PRODUCTION'] as $attributeName => $buildings )
            {
                if( isset( $buildings[$buildingName] ) && ( $buildings[$buildingName] > 0 ) ) 
                {
                    $TURN_PRODUCTION = true;
                    break;
                }
            }
            
            if( $TURN_PRODUCTION )
            {
                $assign['TURN_PRODUCTION_AMOUNT']  = $this->resources['TURN_PRODUCTION'][$attributeName][$buildingName];
                $assign['TURN_PRODUCTION_PICTURE'] = strtolower($attributeName);
            }else{
                $assign['TURN_PRODUCTION_AMOUNT']  = '-';
                $assign['TURN_PRODUCTION_PICTURE'] = strtolower('STASIS_ENERGY');
            }

            $assign['BUILDING_DESCRIPTION'] = stripslashes(nl2br( $this->localize[$buildingName]['desc'] ));
            $assign['POINTS'] = isset($this->points[$buildingName]) ? $this->points[$buildingName] : 0;
        
            $this->tpl->assign( $assign );
            $this->tpl->parse('BUILD', '.buildingItem');
        }
        
        $this->tpl->parse('MAIN', 'buildings');
                
        echo $this->tpl->fetch('MAIN');
        
        return true;
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
    
    
    function getHeader()
    {
        $out = array();
        foreach($this->typesLocalize as $typeName => $shortLocalize)
        {   
            if( $typeName == $this->type )
            {
                continue;
            }
            
            $out[] = '<b><a href="/?p='.$this->pageId.'&type='.$typeName.'" class="wnd_header_txt">'.local( $shortLocalize ).'</a></b>';
        }
        
        $out = implode( "<span class=wnd_gen_txt_large>&nbsp;|</span>\n", $out );
        
        return $out;
    }
    
    function getRaceName()
    {   
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
    
    
    function getResource()
    {   
        $sql = sprintf ( 
        "
        SELECT
            b.buildingname,
            bha.value,
            an.attributename,
            at.typename,
            bha.id_attribute,
            at.id_attributetype,
            an.id_attributename,
            b.id_building,
            a.id_attributetype

        FROM
            ds_building b
            INNER JOIN ds_buildinghasattribute bha ON ( b.id_building = bha.id_building )
            INNER JOIN ds_attribute a ON ( bha.id_attribute = a.id_attribute )
            INNER JOIN ds_attributename an ON ( a.id_attributename = an.id_attributename )
            INNER JOIN ds_attributetype at ON ( a.id_attributetype = at.id_attributetype )
            INNER JOIN ds_buildingcanbuildbyrace bcb ON b.id_building = bcb.id_building

        WHERE
             b.id_building > 0
             AND a.id_attributetype IN (
                SELECT
                    id_attributetype
                FROM
                    ds_attributetype
                WHERE
                    typename IN ( 'TURN_UPKEEP', 'TURN_PRODUCTION', 'PRODUCTION_COSTS')
             )
             AND bcb.id_race= %d            
             AND b.buildingname IN (%s)
        
        ORDER BY
--          a.id_attributetype,
            b.buildingname,
            b.id_building

        ;",
        $this->race,
        $this->types[ $this->type ]
        );
        $res = $this->common->getResultArray ( "getResource", $sql,"", "", "" );

//      die( $this->common->errormsg."\n<br>\n".$sql );
        assert( empty( $this->common->errormsg ) );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getResource::getResource";
            return false;
        }
        
        foreach ( $res as $row ){
            
            $this->resources[ $row['typename'] ][ $row['attributename'] ][ $row['buildingname'] ] = $row['value'];
        }
        
        return true;
    }
    

    function getScience()
    {
        /// get needed Sciences
        $sql = sprintf ( 
        "
        SELECT
            b.id_building,
            s.level,
            sb.branchname,
            ltl.text,
            ltl.id_language,
            b.buildingname,
            bcb.id_race
        FROM
            ds_building b
            INNER JOIN ds_buildingneedsscience bns ON b.id_building = bns.id_building
            INNER JOIN ds_science s ON bns.id_science = s.id_science
            INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
            INNER JOIN ds_localizedtextlanguage  ltl ON sb.id_localizedname = ltl.id_localizedtext
            INNER JOIN ds_buildingcanbuildbyrace bcb ON b.id_building = bcb.id_building
        WHERE
             ltl.id_language = %d
             AND bcb.id_race = %d           
             AND b.buildingname IN (%s)
        ORDER BY
              b.buildingname,
              b.id_building;
        ",
        $this->lang,
        $this->race,
        $this->types[ $this->type ]
        );
        
        $res = $this->common->getResultArray ( "getScience", $sql,"", "", "" );
        assert( empty( $this->common->errormsg ) );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getScience::getScience";
            return false;
        }
        
        foreach ( $res as $row ){
            
            $this->science[ $row['branchname'] ]['text'] = $row['text'];
            $this->science[ $row['branchname'] ][ $row['buildingname'] ] = $row['level'];
        }
        
        return true;
    }
    
    function getDementions()
    {
        /// get needed Buildings
        $sql = sprintf (
        "
        SELECT
            b.id_building as idb,
            b.buildingname,
            b1.id_building,
            b1.buildingname as needbuilding,
            ltl.text,
            ltl.id_language,
            bcb.id_race
        FROM
            ds_buildingcanbuildbyrace bcb
            INNER JOIN
            (
             ds_building b
             INNER JOIN ds_buildingneedsbuilding bnb ON (b.id_building = bnb.id_building_1)
             INNER JOIN ds_building b1 ON (bnb.id_building_2 = b1.id_building)
            ) ON (bcb.id_building = b.id_building)
            INNER JOIN ds_buildingcanbuildbyrace bcb1 ON (bcb.id_race = bcb1.id_race AND b1.id_building = bcb1.id_building)
            INNER JOIN ds_localizedtextlanguage ltl ON (bcb1.id_localizedname = ltl.id_localizedtext)
        WHERE
             ltl.id_language = %d
             AND bcb.id_race = %d
             AND b.buildingname IN (%s)
        ORDER BY
              b.buildingname,
              b.id_building;
        ",
        $this->lang,
        $this->race,
        $this->types[ $this->type ]
        );
        
        $res = $this->common->getResultArray ( "getDementions", $sql,"", "", "" );
        assert( empty( $this->common->errormsg ) );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getDementions::getDementions";
            return false;
        }

        foreach ( $res as $row ){
            
            $this->buildings[ $row['buildingname'] ][ $row['needbuilding'] ] = $row['text'];
        }
        
        return true;
    }
    
    function getGrounds()
    {   
        /// get needed Groundtypes
        $sql = sprintf ( 
        "
        SELECT
            b.id_building,
            b.buildingname,
            ct.celltypename,
            gt.groundname,
            bcb.id_race
        FROM
            ds_building b
            INNER JOIN ds_buildingcanbebuildon bcbon ON (b.id_building = bcbon.id_building)
            INNER JOIN ds_celltype ct ON (bcbon.id_celltype = ct.id_celltype)
            INNER JOIN ds_groundtype gt ON (bcbon.id_groundtype = gt.id_groundtype)
            INNER JOIN ds_buildingcanbuildbyrace bcb ON (b.id_building = bcb.id_building)
        WHERE
            bcb.id_race = %d
            AND b.buildingname IN (%s)
        ORDER BY
            b.buildingname,
            b.id_building;
        ",
        $this->race,
        $this->types[ $this->type ]
        );
        
        $res = $this->common->getResultArray ( "getGrounds", $sql,"", "", "" );
        assert( empty( $this->common->errormsg ) );
        if($res === false)
        {
            $this->errormsg = "SYSTEM ERROR getGrounds::getGrounds";
            return false;
        }
        
        foreach ( $res as $row )
        {
            $this->grounds[ $row['buildingname'] ][ $row['celltypename'] ][] = $row['groundname'];
        }
        
        return true;
    }
    
    function getLocalize()
    {   
        /// get localizedname & Description
        $sql = sprintf ( 
        "
        SELECT
            b.id_building,
            b.buildingname,
            bcb.id_race,
            ltl.text AS buildname,
            ltl1.text AS builddescription,
            ltl.id_language
        FROM
            ds_building b
            INNER JOIN ds_buildingcanbuildbyrace bcb ON ( b.id_building = bcb.id_building)
            INNER JOIN ds_localizedtextlanguage ltl ON (bcb.id_localizedname = ltl.id_localizedtext)
            INNER JOIN ds_localizedtextlanguage ltl1 ON (ltl.id_language = ltl1.id_language AND bcb.id_localizeddescription = ltl1.id_localizedtext)
        WHERE
            bcb.id_race = %d
            AND ltl.id_language = %d
            AND b.buildingname IN (%s)
        ORDER BY
            ltl.text;
        ",
        $this->race,
        $this->lang,
        $this->types[ $this->type ]
        );
        
        $res = $this->common->getResultArray ( "getLocalize", $sql,"", "", "" );
        assert( empty( $this->common->errormsg ) );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR getLocalize::getLocalize";
            return false;
        }
        
        foreach ( $res as $row ){
            
            $this->localize[ $row['buildingname'] ][ 'name' ] = $row['buildname'];
            $this->localize[ $row['buildingname'] ][ 'desc' ] = $row['builddescription'];
        }
        
        return true;
    }
    
    function getPoints( ){

        $sql = "select buildingname, building_score*10 as brank from ds_building";

        /*        
        $sql = "
        SELECT
            buildingname,
            COALESCE(sum(combhas),0) as brank
        FROM
            (
            SELECT
                  b.buildingname as buildingname,
                  bha.value*
                  (
                   SELECT
                         exchangerate
                   FROM
                       ds_resource
                   WHERE
                        id_attributename = atn.id_attributename
                   ) as combhas
            FROM
                ds_building b,
                ds_buildinghasattribute bha,
                ds_attribute a,
                ds_attributename atn,
                ds_attributetype att
            WHERE
                bha.id_building = b.id_building
                AND bha.id_attribute = a.id_attribute
                AND a.id_attributename = atn.id_attributename
                AND a.id_attributetype = att.id_attributetype
                AND att.typename = 'PRODUCTION_COSTS'
                AND atn.attributename != 'CONSTRUCTION_TIME'
            ) as t
        GROUP BY
              buildingname
        ";
        */

        $res = $this->common->getResultArray ( "", $sql,"", "", "" );


        assert ( empty ( $this->common->errormsg ) );

        if ( !$res ) {
            $this->errormsg = "SYSTEM ERROR - getPoints::getPoints";
            return false;
        }
        
        foreach ( $res as $row ){
            
            $this->points[ $row['buildingname'] ] = $row['brank']/10;
        }
        
        return true;
    }
    
    /*
    *  Ќедольша€ така€ затыка, дл€ выбора названий зданий из базы, а не из массива 
    */
    function getBuildingTypes()
    {
        if($this->onlyBuild)
        {
            $this->types[$this->type] = "'$this->onlyBuild'";
            return true;
        } 
        
        $sql = "
            SELECT
                buildingname
            FROM
                ds_building AS b
                INNER JOIN ds_buildingtype AS bt ON (bt.id_buildingtype = b.id_buildingtype)
            WHERE
                bt.type = upper('".$this->type."')
            ";
        $res = $this->common->getResultArray("", $sql,"", "", "");
        assert(empty($this->common->errormsg));
        
        $buildings = "";
        
        foreach($res as $k => $building)
        {
            $buildings .= "'".$building['buildingname']."',"; 
        }
        $buildings = substr($buildings, 0, strlen($buildings) - 1);
        $this->types[$this->type] = $buildings;

        return true;
    }

}

//ѕровер€м не закрыта ли игра
require_once($_SERVER['DOCUMENT_ROOT'].'/ds/include/GameDown.inc.php');
if (GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

$test = new Buildings( $this, constant('DEFAULT_LONG_LANG'), $buildingRaceID, @$_GET['type'], $buildName );

?>