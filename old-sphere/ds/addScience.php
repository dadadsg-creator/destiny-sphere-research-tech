<?
if ( !session_start())
{
    exit;
}
$_SESSION['dbId'] = 0;

require_once ('Main.php');
if ( !isset ( $GLOBALS["common"] ) ) {
    $common = & ClassFactory::getInstance ( "DS_Common" );
    $common->connect();
}

echo "<HTML>
        <BODY>
        <TABLE border='0'>
        <TR>
            <TD>
            <FORM action=\"addScience.php\" method=\"POST\">
            <TABLE border='0'>
            <TR>
                <TD>
                    Пипл-нейм:
                    <INPUT type=\"text\" size=\"20\"  name=\"username\" value=\"";
if(isset($_POST['username']))  echo $_POST['username'];
echo        "\"></TD>";         
echo            "<TD>
                    <INPUT type=\"submit\" name=\"search\">
                </TD>
            </TR>
            </TABLE>
            </FORM>
            </TD>";
    
    if(isset($_POST['search']))
    {
        if( (preg_match("/[^0-9_A-Za-z]+/",$_POST['username']) != 0) || (sizeof($_POST['username']) == 0))
        {
            die('некорректное имя пользователя');
        }               
    }
    
    if(isset($_POST['username'])) 
    {   
        showWorlds($_POST['username']);
    }
    
    echo "</TR></TABLE>";

    if(isset($_POST['search2']) && isset($_POST['username']))
    {
        list ($dbid,$id_userinstance) = explode('-',$_POST['world']);       
        showScienceTable($dbid,$id_userinstance,$_POST['username']);        
    }
    
    if(isset($_POST['add']))
    {
        assert(isset($_POST['id_branch']));
        assert(isset($_POST['level']));
        assert(isset($_POST['id_userinstance']));
        assert(isset($_POST['dbid']));
        assert(isset($_POST['username']));
        
        addScience($_POST['id_branch'],
                    $_POST['level'],
                    $_POST['id_userinstance'],
                    $_POST['dbid'],
                    $_POST['username']
                    );
    }
    
    function showScienceTable($dbid,$id_userinstance,$username)
    {
        global $common;
        
        assert(isset($dbid));
        assert(isset($id_userinstance));
        
        $common->setDbId($dbid);
                
        $sql = 'SELECT
                        max(level),id_branch
                    FROM
                        ds_science
                GROUP BY id_branch';
        
        $branches_max = $common->getResultArray ( "", $sql, "", "", "" );
        
        if ( $branches_max === false || sizeof($branches_max) == 0 )
        {
            die("error0");
        }
                
        $sql = sprintf ("SELECT
                                id_science,
                                id_branch,
                                level
                            FROM
                                ds_science                              
                            WHERE                           
                            id_science not in
                            (
                            SELECT
                                    uhs.id_science
                                FROM
                                    ds_userhasscience uhs                       
                                WHERE                                                                       
                                    uhs.id_userinstance = %d
                                    and uhs.inresearch = 'f' 
                            )",
                            $id_userinstance);

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        
        if ( $res === false || sizeof($res) === 0)
        {
            die("error1  или, о ужас!, юзер изучил все науки"); 
        }
        
        $sql2 = sprintf ("SELECT 
                                ep.id_eventqueue,
                                eho.value
                            FROM
                                ds_eventparticipant ep,
                                ds_eventqueue eq,
                                ds_event ev,
                                ds_eventhasoption eho,
                                ds_eventoption eo
                            WHERE
                                ep.id_userinstance = %d AND
                                ep.id_eventqueue = eq.id_eventqueue and
                                eq.istrash = 'f' AND
                                eq.id_event = ev.id_event AND
                                ev.eventname = 'NEW_RESEARCH' AND
                                eho.id_eventqueue = eq.id_eventqueue and
                                eho.id_eventoption = eo.id_eventoption AND
                                eo.optionname = 'ID_Science'",
                            $id_userinstance);
                            
        $res2 = $common->getResultArray ( "", $sql2, "", "", "" );
        
        if($common->errormsg)
        {
            die("can't get researched science"); 
        }
                
        $science = array();     
        $branches = array();    
        
        $sql = 'SELECT
                        id_branch, branchname
                    from
                        ds_sciencebranch';
        
        $br = $common->getResultArray ( "", $sql, "", "", "" );
        
        if ( $br === false || sizeof($br) === 0)
        {
            die("Can't select all science branches"); 
        }
        
        foreach ($br as $k=>$v) $branches[$v['id_branch']] = $v['branchname'];      
        for ($i = 0; $i < sizeof($branches_max); $i++)
        {
            $temp = array();
            for($j = 0; $j < sizeof($res); $j++)
            {
                if($res[$j]['id_branch'] == $branches_max[$i]['id_branch'])
                {
                    $temp[$res[$j]['level']] = $res[$j]['id_science'];                  
                }               
            }
            $science[$branches_max[$i]['id_branch']] = $temp;
        }       
        
        echo '<TABLE width = \'100%\' border=\'1\'>';
        
        $colors = array(1=>'#D3CABA',2=>'#FDE0AD',3=>'#9FBAEC',
                        4=>'#BABA8A',5=>'#72AC68',6=>'#F7F9B7',7=>'#763DBC');
        
        for ($i = 0; $i < sizeof($branches_max); $i++)
        {
            echo "<tr bgcolor=".$colors[$branches_max[$i]['id_branch']]."><td>".$branches[$branches_max[$i]['id_branch']]."</td>";
            for($j = 1; $j <= $branches_max[$i]['max']; $j++)
            {
                if(isset($science[$branches_max[$i]['id_branch']][$j]))
                {
                    if((sizeof($res2) > 0 ) && ($res2[0]['value'] === $science[$branches_max[$i]['id_branch']][$j]))
                    {
                        echo "<td bgcolor='red' align='center'>R</td>";
                    }
                    else 
                    {
                        echo "<td bgcolor='white'>$j</td>";
                    }
                }
                else 
                {
                    echo "<td>$j</td>";
                }
            }
            echo "</tr>";
        }
                
        echo "</TABLE>
                надоумить соту: <br>                
                <TABLE  border=0 cellpadding=0 cellspacing=0>";
        for ($i = 0; $i < sizeof($branches_max); $i++)
        {
            echo "<tr valign=\"center\"><FORM action=\"addScience.php\" method=\"POST\"> 
                        <INPUT type=\"hidden\" value=\"$id_userinstance\" name=\"id_userinstance\">
                        <INPUT type=\"hidden\"  name=\"username\" value=\"$username\">
                        <INPUT type=\"hidden\"  name=\"dbid\" value=\"$dbid\">                      
                        <INPUT type=\"hidden\" value=\"".$branches_max[$i]['id_branch']."\" name=\"id_branch\">
                        <td>".$branches[$branches_max[$i]['id_branch']].'</td>'.
                        '<td><SELECT name=level>';
            for($j = 1; $j <= $branches_max[$i]['max']; $j++)
            {
                if(isset($science[$branches_max[$i]['id_branch']][$j]))
                {
                    echo "<OPTION  value=$j> $j </OPTION>";
                }
            }
            echo '</SELECT></td>
                    <td>
                    <INPUT type="submit" name="add" value="сделать">
                    </FORM></td></tr>';
        }
        echo '</TABLE>';
    }
    
    function showWorlds($username)
    {                   
        global $common;
        global $dbWorldAccess;
        
        $sql = sprintf("SELECT  
                                w.worldname, ui.id_userinstance
                            FROM
                                ds_world w,
                                ds_userinstance ui,
                                ds_user u
                            WHERE
                                u.username = '%s'
                                AND u.id_user = ui.id_user
                                AND ui.id_world = w.id_world
                        ",
                        $username);
                            
        echo "<TD>
                <FORM action=\"addScience.php\" method=\"POST\">
                <INPUT type='hidden' name=username value=$username>
                <TABLE border='0'>
                <TR>
                    <TD>
                        Мирок:
                        <SELECT name='world'>";
                
        foreach ($dbWorldAccess as $k=>$v)
        {
            $common->setDbId($k);
            $res = $common->getResultArray("", $sql, "", "", "");
            if(!$common->errormsg && (sizeof($res) > 0))
            {
                foreach ($res as $k2=>$v2)
                {
                    var_dump($res);
                    echo "<br>";
                    echo "<OPTION value='".$k."-".$v2['id_userinstance']."'>".
                                            $v2['worldname']."</OPTION>";
                }
            }
        }
        echo "          </SELECT>
                    </TD>               
                    <TD>
                        <INPUT type=\"submit\" name=\"search2\">
                    </TD>
                 </TR>
                 </TABLE>
                 </FORM>
                 </TD>";
        return true;
    }
    
    function endScienceEvent($id_userinstance)
    {
        global $common;         
        
        $sql = sprintf ("SELECT 
                                ep.id_eventqueue,
                                eho.value
                            FROM
                                ds_eventparticipant ep,
                                ds_eventqueue eq,
                                ds_event ev,
                                ds_eventhasoption eho,
                                ds_eventoption eo
                            WHERE
                                ep.id_userinstance = %d AND
                                ep.id_eventqueue = eq.id_eventqueue and
                                eq.istrash = 'f' AND
                                eq.id_event = ev.id_event AND
                                ev.eventname = 'NEW_RESEARCH' AND
                                eho.id_eventqueue = eq.id_eventqueue and
                                eho.id_eventoption = eo.id_eventoption AND
                                eo.optionname = 'ID_Science'",
                            $id_userinstance);
                            
        $res = $common->getResultArray("", $sql, "", "", "");
        
        if($common->errormsg)
        {
            die("can't get researched science"); 
        }           
        
        if(!isset($res[0]['id_eventqueue']) || !isset($res[0]['value']))
        {
            return true;
        }
        
        $common->executeQuery ('begin');
        
        $sql1 = sprintf("DELETE FROM 
                            ds_eventhasoption 
                        WHERE 
                            id_eventqueue = %d",
                        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql1);      
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event1" ;
            return false;
        }
        
        $sql2 = sprintf("DELETE FROM 
                            ds_eventparticipant 
                        WHERE 
                            id_eventqueue = %d",
                        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql2);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event2";
            return false;
        }
        
        $sql3 = sprintf("DELETE FROM 
                            ds_combbindeventqueue 
                        WHERE 
                            id_eventqueue = %d",
                        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql3);      
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event3";
            return false;
        }
        
        $sql4 = sprintf("DELETE FROM 
                            ds_userhassciencebindeventqueue 
                        WHERE 
                            id_eventqueue = %d",
                        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql4);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event4";
            return false;
        }
        
        $sql5 = sprintf("DELETE FROM 
                            ds_eventqueue 
                        WHERE 
                            id_eventqueue = %d",
                        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql5);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event5  ";
            return false;
        }
        
        $sql6 = sprintf("DELETE FROM
                                ds_userhasscience
                            WHERE
                                id_userinstance = %d
                                and inresearch = 't'",
                        $id_userinstance);
        $common->executeQuery ($sql6);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event6  ";
            return false;
        }
                
        $common->executeQuery ('commit');       
        return true;
    }
    
    function addScience($id_branch,$level,$id_userinstance,$dbid,$username)
    {       
        assert(isset($id_branch));
        assert(isset($level));
        assert(isset($id_userinstance));
        assert(isset($dbid));
        assert(isset($username));
        
        global $common;     
        $common->setDbId($dbid);
                
        if(!endScienceEvent($id_userinstance))
        {
            echo "can't end scince event <br> science was not added";
            return false;
        }
         
                    
        $sql = sprintf("SELECT
                                id_science                                
                        FROM
                             ds_science                             
                        WHERE
                            id_branch = %d and
                            level <= %d and
                            id_science not in
                            (
                                SELECT
                                    id_science
                                FROM
                                    ds_userhasscience
                                WHERE
                                    id_userinstance = %d
                            )
                            ",
                            $id_branch,
                            $level,
                            $id_userinstance);
                                
        $sciences = $common->getResultArray ( "", $sql, "", "", "" );
        
        if ( $sciences === false || sizeof($sciences) === 0)
        {
            die("error4"); 
        }   
        
        foreach ($sciences as $k=>$v)
        {
            $common->executeQuery('begin'); 
            $sql = sprintf("INSERT INTO
                                        ds_userhasscience
                                        (id_science,id_userinstance,inresearch,starttime,endtime)
                                VALUES
                                        (%d,%d,'f',now(),now())",
                                        $v['id_science'],
                                        $id_userinstance);

            $res = $common->executeQuery ( $sql );  
            if(!$res)
            {
                $common->executeQuery('rollback');  
                echo $sql;
                die("can't add science with id =  ".$v['id_science']);
            }   
            
            $sql = sprintf("insert into 
                                        ds_userhasability 
                                        (id_userinstance, id_ability )
                                        select 
                                            %d, id_ability 
                                        from 
                                            ds_sciencehasability
                                        where 
                                            id_science = %d",                                       
                                        $id_userinstance,
                                        $v['id_science']);

            $res = $common->executeQuery($sql); 
            if(!$res)
            {
                $common->executeQuery('rollback');  
                echo $sql;
                die("can't add science2 with id =  ".$v['id_science']);
            }   
                        
            $common->executeQuery('commit');                    
        }
        showScienceTable($dbid,$id_userinstance,$username);                         
        
        return true;        
    }
?>

</BODY>
</HTML>