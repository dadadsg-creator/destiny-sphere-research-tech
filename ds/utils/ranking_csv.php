<?PHP $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/09/17 14:34:50 $';
ini_set('memory_limit', '512M');
set_time_limit(0);

require_once '../Main.php';

require_once(constant('INCLUDE_PATH').'/GameDown.inc.php');
if(GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

define ("FILENAME", $_SERVER['DOCUMENT_ROOT'].'/ds/csv/ranking');

if(!isset($GLOBALS["common"])) 
{
    $common = ClassFactory::getInstance ( "DS_Common" );
    if(!$common->connect()) 
    {
        echo "DB-connection failed";
        exit;
    }
}

class RankingCSV {

    var $errormsg;
    var $db;
    var $limit;
    var $trans;

    function RankingCSV()
    {
        if(!$this->generateRanking())
        {
            die($this->errormsg);
        }
    }

    function generateRanking()
    {
        global $common;

        $lang = constant('DEFAULT_LANGUAGE');           

        $uiid = 0;                   

        foreach($GLOBALS['dbWorldAccess'] as $k => $v)
        {
            if(!$common->setDbId($k))
            { 
                $this->errormsg = "SYSTEM ERROR: Ranking_csv::SetDB1: Bad Set db Id $k";
                return false; 
            }

            $sql = "
                SELECT 
                    w.id_world, 
                    worldname, 
                    $k as dbid
                FROM 
                    ds_worldhaslanguage whl, 
                    ds_world w
                WHERE 
                    whl.id_world = w.id_world 
                    AND
                    id_language = $lang 
                    AND 
                    w.id_world > 0";                
                
            $res[] = $common->getResultArray("", $sql, "", "", "");
            if($res === false) 
            {
                $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking1";
                return false;
            }
        }
        
        for($i=0; $i<sizeof($res); $i++)
        {
            if(sizeof($res[$i]) == 0)
            {
                continue;
            }

            if(!$common->setDbId($res[$i][0]['dbid']))
            { 
                $this->errormsg = "SYSTEM ERROR: Ranking_csv::SetDB2: Bad Set db Id".$res[$i][$k]['dbid'];
                return false; 
            }
            for($k=0; $k<sizeof($res[$i]); $k++)
            {
                if(sizeof($res) == 1 && sizeof($res[$i]) == 1)
                {
                    $csv = fopen(FILENAME . ".csv", 'w');
                } else {
                    $csv = fopen(FILENAME . "_" . $res[$i][$k]['worldname'] . ".csv", 'w');
                }
//              and id_language = %d
                $sql = "
                    SELECT 
                        *
                    FROM 
                        ds_finalranking AS ra
                        LEFT JOIN ds_battlexp AS bx USING (id_userinstance)
                    WHERE
                        ra.id_world = ".$res[$i][$k]['id_world']."
                    ORDER BY thefinal DESC
                    ";
                
                $res2 = $common->getResultArray("", $sql, "", "", "");
                if($res2 === false) 
                {
                    $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking3";
                    return false;
                }
                                    
                for($j=0; $j<sizeof($res2); $j++) 
                {
                    $id_userinstance = $res2[$j]['id_userinstance'];

                    $sql = "
                        SELECT
                            co.combnumber,
                            cc.complexnumber,
                            ri.ringnumber,
                            cn.combname
                        FROM
                            ds_comb AS co
                            INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
                            INNER JOIN ds_ring AS ri ON (cc.id_ring = ri.id_ring)
                            INNER JOIN ds_combname AS cn ON (cn.id_comb = co.id_comb)
                        WHERE
                            co.id_userinstance = $id_userinstance
                            AND 
                            ri.ringnumber = 4
                        ";
                    $res3 = $common->getResultArray("", $sql, "", "", "");

                    if($res3 === false)
                    {
                        $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking4";
                        return false;
                    }

                    $colonyCoords = '';
                    $colonyName = '';
    
                    for($l=0; $l < sizeof($res3); $l++)
                    {
                        $colonyCoords .= ",".$res3[$l]['ringnumber'].".".$res3[$l]['complexnumber'].".".$res3[$l]['combnumber'];
                        $colonyName .= ",".$res3[$l]['combname'];
                    }   
                    
                    // Затыка по просьбе игроков
                    if ($res2[$j]['id_race'] == '2') {
                        $res2[$j]['racename'] = 'Лиенсу';
                        $res2[$j]['racename'] = 'Liensu';
                    }
                    if ($res2[$j]['id_race'] == '3') {
                        $res2[$j]['racename'] = 'Псолао';
                        $res2[$j]['racename'] = 'Psolao';
                    }
                    if ($res2[$j]['id_race'] == '1') {
                        $res2[$j]['racename'] = 'Воранер';
                        $res2[$j]['racename'] = 'Voraner';
                    }
                    
                    
                    // Есть ли открытые ворота на соте
                    $sql = "
                        SELECT 
                            ab.id_building 
                        FROM 
                            ds_userinstance ui
                        LEFT JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
                        LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
                        LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                        LEFT JOIN ds_attachedbuilding ab ON (c.id_comb = ab.id_comb)            
                        WHERE 
                             ab.id_building = 6
                             AND 
                             ri.ringnumber != 4
                             AND 
                             ui.id_userinstance = ".$id_userinstance."
                             AND 
                             ab.active = 't'";
                    $res4 = $common->getResultArray("", $sql, "", "", "");
                    if($res4 === false)
                    {
                        $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking4";
                        return false;
                    }

                    if (count($res4) > 0) {
                        $openGate = '1';
                    } else {
                        $openGate = '0';
                    }
                    
                    $rankingLine = ($j+1) . ";";
                    $rankingLine .= $res2[$j]['username'] . ";";
                    $rankingLine .= str_replace(";", "", $res2[$j]['combname'].$colonyName) . ";";
                    $rankingLine .= $res2[$j]['coords'] . $colonyCoords . ";";              
                    $rankingLine .= $res2[$j]['alliancetag'] .";";
                    $rankingLine .= $res2[$j]['racename'] . ";";
                    $rankingLine .= $res2[$j]['thefinal'] . ";";            
                    $rankingLine .= $res2[$j]['value'] . ";";   
                    $rankingLine .= 'Gate '.$openGate . ";";        
                    fwrite ($csv, $rankingLine . "\n");
                }
    
                $res2 = "";
                fclose ($csv);
            }
        }
        
        return true;
    }
}

class RankingCSVNew {

    var $errormsg;
    var $db;
    var $limit;
    var $trans;

    function RankingCSVNew()
    {
        if(!$this->generateRanking())
        {
            die($this->errormsg);
        }
    }

    function generateRanking()
    {
        global $common;

        $lang = constant('DEFAULT_LANGUAGE');           
        $file =  $_SERVER['DOCUMENT_ROOT'].'/ds/csv/ranking';   

        $uiid = 0;                   

        foreach($GLOBALS['dbWorldAccess'] as $k => $v)
        {
            if(!$common->setDbId($k))
            { 
                $this->errormsg = "SYSTEM ERROR: Ranking_csv::SetDB1: Bad Set db Id $k";
                return false; 
            }

            $sql = "
                SELECT 
                    w.id_world, 
                    worldname, 
                    $k as dbid
                FROM 
                    ds_worldhaslanguage whl, 
                    ds_world w
                WHERE 
                    whl.id_world = w.id_world 
                    AND
                    id_language = $lang 
                    AND 
                    w.id_world > 0";                
                
            $res[] = $common->getResultArray("", $sql, "", "", "");
            if($res === false) 
            {
                $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking1";
                return false;
            }
        }
        
        for($i=0; $i<sizeof($res); $i++)
        {
            if(sizeof($res[$i]) == 0)
            {
                continue;
            }

            if(!$common->setDbId($res[$i][0]['dbid']))
            { 
                $this->errormsg = "SYSTEM ERROR: Ranking_csv::SetDB2: Bad Set db Id".$res[$i][$k]['dbid'];
                return false; 
            }
            for($k=0; $k<sizeof($res[$i]); $k++)
            {
                if(sizeof($res) == 1 && sizeof($res[$i]) == 1)
                {
                    $csv = fopen($file . ".csv", 'w');
                } else {
                    $csv = fopen($file."_".$res[$i][$k]['worldname']."_new".".csv", 'w');
                }
                $sql = "
                    SELECT 
                        COALESCE(
                            (
                            COALESCE(r.science_ranking, 0)
                            + 
                            COALESCE(r.artefact_ranking, 0)
                            + 
                            COALESCE(r.building_ranking, 0)
                            + 
                            COALESCE(b.absolute_value, 0)*100
                            ), 0)::BIGINT as thefinal,
                            u.username,
                            
                           -- r.science_ranking as scienceranking,
                          --  r.artefact_ranking as artefactranking,
                          --  r.building_ranking as buildingranking,
                            COALESCE(b.absolute_value, 0) as bo,
                            a.alliancetag,
                            ui.id_userinstance,
                         --   r.id_level,
                            COALESCE(value, 0) as battlexp,
                            COALESCE(absolute_value, 0) as battlexpabs,
                            l.text as racename
                    FROM 
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (ui.id_user = u.id_user)
                    LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_battlexp b ON (b.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_userhasalliance uha ON (uha.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
                    LEFT JOIN ds_race rac ON (rac.id_race = ui.id_race)
                    LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = rac.id_localizedname AND l.id_language = 1)
                    WHERE
                        ui.id_world = ".$res[$i][$k]['id_world']."
                        ORDER BY thefinal desc";

                $res2 = $common->getResultArray("", $sql, "", "", "");
                if($res2 === false) 
                {
                    $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking3";
                    return false;
                }
                                    
                for($j=0; $j<sizeof($res2); $j++) 
                {
                    $id_userinstance = $res2[$j]['id_userinstance'];

                    $sql = "
                        SELECT
                            co.combnumber,
                            cc.complexnumber,
                            ri.ringnumber,
                            cn.combname
                        FROM
                            ds_comb AS co
                            INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
                            INNER JOIN ds_ring AS ri ON (cc.id_ring = ri.id_ring)
                            INNER JOIN ds_combname AS cn ON (cn.id_comb = co.id_comb)
                        WHERE
                            co.id_userinstance = $id_userinstance
                        ORDER BY ri.ringnumber
                        ";
                    $res3 = $common->getResultArray("", $sql, "", "", "");

                    if($res3 === false)
                    {
                        $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking4";
                        return false;
                    }

                    $combCoords = '';
                    $combName = '';
                    $comma = '';
    
                    for($l=0; $l < sizeof($res3); $l++)
                    {
                        $combCoords .= $comma.$res3[$l]['ringnumber'].".".$res3[$l]['complexnumber'].".".$res3[$l]['combnumber'];
                        $combName .= $comma.$res3[$l]['combname'];
                        $comma = ',';
                    }   
                    
                    // Затыка по просьбе игроков
                    if ($res2[$j]['id_race'] == '2') {
                        $res2[$j]['racename'] = 'Лиенсу';
                    }
                    if ($res2[$j]['id_race'] == '3') {
                        $res2[$j]['racename'] = 'Псолао';
                    }
                    if ($res2[$j]['id_race'] == '1') {
                        $res2[$j]['racename'] = 'Воранер';
                    }
                    
                    
                    // Есть ли открытые ворота на соте
                    $sql = "
                        SELECT 
                            ab.id_building 
                        FROM 
                            ds_userinstance ui
                        LEFT JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
                        LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
                        LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                        LEFT JOIN ds_attachedbuilding ab ON (c.id_comb = ab.id_comb)            
                        WHERE 
                             ab.id_building = 6
                             AND 
                             ri.ringnumber != 4
                             AND 
                             ui.id_userinstance = ".$id_userinstance."
                             AND 
                             ab.active = 't'";
                    $res4 = $common->getResultArray("", $sql, "", "", "");
                    if($res4 === false)
                    {
                        $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking4";
                        return false;
                    }

                    if (count($res4) > 0) {
                        $openGate = '1';
                    } else {
                        $openGate = '0';
                    }
                    
                    $rankingLine = ($j+1) . ";";
                    $rankingLine .= $res2[$j]['username'] . ";";
                    $rankingLine .= $combName . ";";
                    $rankingLine .= $combCoords . ";";              
                    $rankingLine .= $res2[$j]['alliancetag'] .";";
                    $rankingLine .= $res2[$j]['racename'] . ";";
                    $rankingLine .= $res2[$j]['thefinal'] . ";";            
                    $rankingLine .= $res2[$j]['battlexpabs'] . ";"; 
                    $rankingLine .= 'Gate '.$openGate . ";";        
                    fwrite ($csv, $rankingLine . "\n");
                }
    
                $res2 = "";
                fclose ($csv);
            }
        }
        
        return true;
    }
}

$r = new RankingCSV;

$r = new RankingCSVNew;

?>
