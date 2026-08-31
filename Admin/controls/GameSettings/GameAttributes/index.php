<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

$obj = new GameAttributes();

class GameAttributes
{
    var $err_msg;
    var $common = false;
    var $types = array('unit' => 'Units',
    'arts' => 'Artefacts',
    'building' => 'Buildings',
    'religion' => 'Religions',
    'race' => 'Races',
    'terraforming' => 'Terraforming');

    function GameAttributes()
    {
        $this->getPostData($_POST);
    }

    function getPostData($data)
    {
        isset($data['attr_type'])? $type = $data['attr_type']:$type='unit';
        isset($data['dbid'])? $world = $data['dbid']:$world = '';

        $this->showRequestForm($type,$world);

        if(isset($data['attr_type']))
        {
            switch ($data['attr_type'])
            {
                case 'unit':
                    if(isset($data['id_unit']) && isset($data['edit']))
                    {
                        $this->updateUnitAttributes($data);
                    }
                    if(isset($data['id_unit']) && isset($data['editScinceLevel']))
                    {
                        $this->updateUnitScinceLevel($data);
                    }
                    if(isset($data['id_unit'])
                    && isset($data['add']))
                    {
                        $this->addUnitAttribute($data);
                    }
                    isset($data['id_unit'])? $id_unit = $data['id_unit']:$id_unit = '';
                    $this->showUnitAttributes($id_unit);
                    break;
                case 'terraforming':
                    if(isset($data['edit']))
                    {
                        $this->updateTFAttributes($data);
                    }
                    if(isset($data['id_tf'])
                    && isset($data['add']))
                    {
                        $this->addTFAttribute($data);
                    }
                    isset($data['id_tf'])? $id_tf = $data['id_tf']:$id_tf = '';
                    $this->showTFAttributes($id_tf);
                    break;
                case 'arts':
                    isset($data['id_artefactscope'])?
                    $id_artefactscope = $data['id_artefactscope']:
                    $id_artefactscope = '';
                    isset($data['id_attributename'])?
                    $id_attributename = $data['id_attributename']:
                    $id_attributename = '';
                    isset($data['id_attributetype'])?
                    $id_attributetype = $data['id_attributetype']:
                    $id_attributetype = '';
                    isset($data['id_artefactprobability'])?
                    $id_artefactprobability = $data['id_artefactprobability']:
                    $id_artefactprobability = '';
                    if(isset($data['search2']) || isset($data['edit']))
                    {
                        $show = true;
                    }
                    else
                    {
                        $show = false;
                    }

                    if(isset($data['edit']))
                    {
                        $this->updateArtefactAttributes($data);
                    }
                    $this->showArtefactAttributes($id_artefactscope,
                    $id_attributename,
                    $id_attributetype,
                    $id_artefactprobability,
                    $show);
                    break;
                case 'building':

                    if(isset($data['id_building']))
                    {
                        $bildingArr = explode("/", $data['id_building']);

                        $id_building = $bildingArr[0];
                        $id_race = $bildingArr[1];
                    }else {
                        $id_building = '';
                    }
                    if(isset($data['edit']))
                    {
                        $this->updateBuildingsAttributes($data);
                    }

                    if(isset($data['id_building']) && isset($data['editScinceLevel']))
                    {
                        $this->updateBuildingScinceLevel($data);
                    }

                    if(isset($data['id_building'])
                    && isset($data['add']))
                    {
                        $this->addBuildingAttribute($data);
                    }

                    $this->showBuildingsAttributes($id_building, $id_race);
                    break;
                case 'religion':
                    isset($data['id_religion'])?
                    $id_religion = $data['id_religion']:
                    $religion = '';

                    if(isset($data['edit']))
                    {
                        $this->updateReligionAttributes($data);
                    }

                    $this->showReligionAttributes($id_religion);
                    break;
                case 'race':
                    isset($data['id_race'])?
                    $id_race = $data['id_race']:
                    $id_race = '';

                    if(isset($data['edit']))
                    {
                        $this->updateRacesAttributes($data);
                    }

                    $this->showRacesAttributes($id_race);
                    break;
                default:
                    echo 'not implemented yet';
            }
        }
        return true;
    }

    function addTFAttribute($data)
    {
        assert(isset($data['id_tf']));
        assert(isset($data['new_attr_value']));
        assert(isset($data['new_tf_attribute']));

        $sql = sprintf("INSERT INTO
                                    ds_terraforminghasattribute
                                    (id_attribute,id_terraforming,value)
                            VALUES
                                    (%d,%d,%d)",
        $data['new_tf_attribute'],
        $data['id_tf'],
        $data['new_attr_value']);

        $this->getDBConnection()->executeQuery($sql);
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("can't add attribute with id ".$data['new_tf_attribute'].
            "for tf with id".$data['id_tf']."<br>");
        }
        
        //Получаем имя аттрибута по его id
        $attributeName = $this->getTFAttributeNameById($data['new_tf_attribute']);

        //Получаем тип аттрибута по его id
        $attributeType = $this->getTFAttributeTypeById($data['new_tf_attribute']);
        
        //Логируем
        actionLog("Edit attribute for terraforming '".$data['from']."->".$data['to']."' ", "Attribute type '$attributeType'<br>Attribute name '$attributeName'<br>value=".$data['new_attr_value']);
        
        return true;
    }

    function addBuildingAttribute($data)
    {
        assert(isset($data['id_building']));
        assert(isset($data['new_attr_value']));
        assert(isset($data['new_building_attribute']));

        //Получаем название здания по его id и рассе
        $buildingName = $this->getBuildingNameByIDAndRace($data['id_building']);

        $id_attribute = $data['new_building_attribute'];

        $sql = sprintf("INSERT INTO
                                    ds_buildinghasattribute
                                    (id_attribute,id_building,value)
                            VALUES
                                    (%d,%d,%f)",
        $data['new_building_attribute'],
        $data['id_building'],
        $data['new_attr_value']);

        $this->getDBConnection()->executeQuery($sql);
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("can't add attribute with id ".
            $data['new_building_attribute'].
            "for building with id".$data['id_building'].
            "<br>");
        }
        
        //Получаем имя аттрибута по его id
        $attributeName = $this->getAttributeNameByIdBuildingattribute($id_attribute);

        //Получаем тип аттрибута по его id
        $attributeType = $this->getAttributeTypeByIdBuildingattribute($id_attribute);
        
        //Логируем
        actionLog("Edd attribute for building '$buildingName'", "Attribute type '$attributeType'<br>Attribute name '$attributeName'<br>value=".$data['new_attr_value']);
        
        return true;
    }

    function updateTFAttributes($data)
    {
        if(sizeof($data) <= 0)
        {
            return false;
        }

        $detail = "";
        
        foreach ($data as $k=>$v)
        {
            if(preg_match('/^idtfa[0-9]*$/',$k) > 0)
            {
                $id_terraforminghasattribute =  substr($k,5);

                assert((int)$id_terraforminghasattribute > 0);
                $sql = sprintf("UPDATE
                                        ds_terraforminghasattribute
                                    SET
                                        value = %d
                                    WHERE
                                        id_terraforminghasattribute = %d",
                $v,
                $id_terraforminghasattribute);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't change terraforming attribute with id".
                    $id_terraforminghasattribute."<br>");
                }
                
                //Получаем имя аттрибута по его id
                $attributeName = $this->getTerraformingAttributeNameBy($id_terraforminghasattribute);
        
                //Получаем тип аттрибута по его id
                $attributeType = $this->getTerraformingAttributeTypeById($id_terraforminghasattribute);
                
                $detail .= "Set value = $v<br>(Attribute type '$attributeType'<br> Attribute name '$attributeName')<br>";
            }
        }

        //Логируем
        actionLog("Edit attribute for terraforming '".$data['from']."->".$data['to']."' ", $detail);
        
        return true;
    }

    function showTFAttributes($id_tf)
    {
        $sql = sprintf("SELECT
                                tf.id_terraforming,
                                (
                                SELECT
                                        ltl.text                        
                                    FROM
                                        ds_groundtype gt,
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        tf.id_groundtypefrom = gt.id_groundtype
                                        AND lt.id_localizedtext = gt.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as from,
                                (
                                SELECT
                                        ltl.text                    
                                    FROM
                                        ds_groundtype gt,
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        tf.id_groundtypeto = gt.id_groundtype
                                        AND lt.id_localizedtext = gt.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as to
                            FROM
                                ds_terraforming tf
                        ",
        constant('DEFAULT_LANG_ID'),
        constant('DEFAULT_LANG_ID')
        );

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting terraformings");
            return false;
        }

        echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='terraforming'>             
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                <SELECT name='id_tf'>";         
        foreach($res as $row)
        {
            $selected = '';
            if(strcmp($id_tf,$row['id_terraforming']) === 0)
            {
                $selected = 'selected';
            }
            echo "<OPTION ".$selected." value='".
            $row['id_terraforming'].
            "'>".$row['from'].
            " --> ".
            $row['to'].
            "</OPTION>";
        }
        echo "</SELECT>
                            </TD>
                        </TR>                       
                        <TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt>
                                <input type='submit'  class='Submit' name='search2' value='Get'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                </table>";

        if(isset($id_tf) && $id_tf > 0)
        {
            $sql = sprintf("SELECT
                                    tfa.id_terraforminghasattribute,
                                    at.typename,
                                    an.attributename,
                                    tf.id_terraforming,
                                    tfa.value,
                                (
                                    SELECT
                                        ltl.text                        
                                    FROM
                                        ds_groundtype gt,
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        tf.id_groundtypefrom = gt.id_groundtype
                                        AND lt.id_localizedtext = gt.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as from,
                                (
                                SELECT
                                        ltl.text                    
                                    FROM
                                        ds_groundtype gt,
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        tf.id_groundtypeto = gt.id_groundtype
                                        AND lt.id_localizedtext = gt.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as to
                                FROM
                                    ds_terraforming tf,
                                    ds_terraforminghasattribute tfa,
                                    ds_attribute a,
                                    ds_attributename an,
                                    ds_attributetype at
                                WHERE
                                    tf.id_terraforming = %d
                                    AND tf.id_terraforming = tfa.id_terraforming
                                    AND tfa.id_attribute = a.id_attribute
                                    AND a.id_attributename = an.id_attributename
                                    AND a.id_attributetype = at.id_attributetype                                
                                ORDER BY at.id_attributetype",                          
            constant('DEFAULT_LANG_ID'),
            constant('DEFAULT_LANG_ID'),
            $id_tf
            );

            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("error in getting attributes of terraforming");
                return false;
            }

            if(sizeof($res) > 0)
            {
                echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='terraforming'>             
                <INPUT type='hidden' name='id_tf' value='".$id_tf."'>                               
                <INPUT type='hidden' name='from' value='".$res[0]['from']."'>                               
                <INPUT type='hidden' name='to' value='".$res[0]['to']."'>                               
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2 colspan=4 align='center'>".
                $res[0]['from'].' --> '.$res[0]['to']
                ."</TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute type
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute name
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Value
                            </TD>                           
                        </TR>";                 
                foreach ($res as $k=>$v)
                {
                    echo "<TR>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['typename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['attributename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>
                                <INPUT type='text' name='idtfa".
                    $v['id_terraforminghasattribute'].
                    "' value='".round($v['value'], 8)."'>
                            </TD>                           
                        </TR>";                 
                }
                echo        "<TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt colspan='3'>
                                <input type='submit'  class='Submit' name='edit' value='Edit'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form> 
                
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                    <INPUT type='hidden' name='attr_type' value='terraforming'> 
                    <INPUT type='hidden' name='from' value='".$res[0]['from']."'>                               
                    <INPUT type='hidden' name='to' value='".$res[0]['to']."'>           
                    <INPUT type='hidden' name='id_tf' value='".$id_tf."'>   
                <tr>
                    <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=maintxt>".   
                $this->getTFNewAttributesSelect($id_tf)
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>         
                                <INPUT type='text' maxlength=10' name='new_attr_value'>             
                            </TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=maintxt colspan=3 align='right'>     
                                <INPUT class='Submit' type='submit' name='add' value='Add'>                 
                            </TD>                           
                        </TR>   
                    </table>
                    </td>
                </tr>
                </form>         
                </table>";
            }
        }


        return true;
    }

    function showBuildingsAttributes($id_building, $id_race)
    {
        $sql = sprintf("SELECT
                                b.id_building,
                                (
                                SELECT
                                        ltl.text
                                    FROM
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        lt.id_localizedtext = bcbbbr.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as buildingname,
                                r.id_race,
                                (
                                SELECT
                                        ltl.text
                                    FROM
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        lt.id_localizedtext = r.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as racename,
                                bt.type
                            FROM
                                ds_building b,
                                ds_buildingcanbuildbyrace bcbbbr,
                                ds_buildingtype bt,
                                ds_race r
                            WHERE
                                b.id_building = bcbbbr.id_building
                                AND b.id_buildingtype = bt.id_buildingtype
                                AND bcbbbr.id_race = r.id_race
                                ORDER BY bcbbbr.id_race, bt.id_buildingtype",
        constant('DEFAULT_LANG_ID'),
        constant('DEFAULT_LANG_ID')
        );

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );

        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of buildings");
            return false;
        }

        echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='building'>             
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                <SELECT name='id_building'>";   

        $current_race = '';
        $current_type = '';
        foreach($res as $row)
        {
            if(strcmp($current_race,$row['racename']) != 0)
            {
                echo "<OPTION disabled>----------".$row['racename']."---------</OPTION>";
                $current_race = $row['racename'];
            }
            if(strcmp($current_type,$row['type']) != 0)
            {
                echo "<OPTION disabled>-------------".$row['type']."------------</OPTION>";
                $current_type = $row['type'];
            }

            if(strcmp($id_building,$row['id_building']) === 0)
            {
                echo "<OPTION selected value='".$row['id_building']."/".$row['id_race']."'>".$row['buildingname']."</OPTION>";
            }
            else
            {
                echo "<OPTION value='".$row['id_building']."/".$row['id_race']."'>".$row['buildingname']."</OPTION>";
            }
        }
        echo "</SELECT>
                            </TD>
                        </TR>                       
                        <TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt>
                                <input type='submit'  class='Submit' name='search2' value='Get'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                </table>";

        if(isset($id_building) && $id_building > 0)
        {
            $sql = sprintf("SELECT
                                    bha.id_buildinghasattribute,
                                    at.typename,
                                    an.attributename,
                                    b.buildingname,
                                    bha.value
                                FROM
                                    ds_building b,
                                    ds_buildinghasattribute bha,
                                    ds_attribute a,
                                    ds_attributename an,
                                    ds_attributetype at
                                WHERE
                                    b.id_building = %d
                                    AND b.id_building = bha.id_building
                                    AND bha.id_attribute = a.id_attribute
                                    AND a.id_attributename = an.id_attributename
                                    AND a.id_attributetype = at.id_attributetype                                    
                                ORDER BY at.id_attributetype",
            $id_building,
            constant('DEFAULT_LANG_ID')
            );

            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("error in getting attributes of building");
                return false;
            }

            echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>";

            if(sizeof($res) > 0)
            {
                echo "<br>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='building'>             
                <INPUT type='hidden' name='id_building' value='".$id_building."/".$id_race."'>                              
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2 colspan=4 align='center'>".
                $res[0]['buildingname']
                ."</TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute type
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute name
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Value
                            </TD>                           
                        </TR>";                 
                foreach ($res as $k=>$v)
                {
                    echo "<TR>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['typename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['attributename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>
                                <INPUT type='text' name='idbha".
                    $v['id_buildinghasattribute'].
                    "' value='".round($v['value'], 8)."'>
                            </TD>                           
                        </TR>";                 
                }
                echo        "<TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt colspan='3'>
                                <input type='submit'  class='Submit' name='edit' value='Edit'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form> ";
            } else {
                echo "<br>";
            }
            echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
                    <INPUT type='hidden' name='attr_type' value='building'>             
                    <INPUT type='hidden' name='id_building' value='".$id_building."/".$id_race."'>
                <tr>
                    <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=maintxt>".   
            $this->getBuildingNewAttributesSelect($id_building)
            ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>         
                                <INPUT type='text' maxlength=10' name='new_attr_value'>             
                            </TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=maintxt colspan=3 align='right'>     
                                <INPUT class='Submit' type='submit' name='add' value='Add'>                 
                            </TD>                           
                        </TR>   
                    </table>
                    </td>
                </tr>
                </form>";

            $sql = sprintf("SELECT sb.id_branch,
                                   sb.branchname
                            FROM ds_sciencebranch as sb
                            ORDER BY sb.id_branch");


            $title = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg || sizeof($title) === 0)
            {
                $this->outputResult("can't get science list");
                return false;
            }

            $sql = sprintf("SELECT s.id_science,
                               s.id_branch,
                               s.level
                        FROM ds_buildingneedsscience as bns
                        LEFT JOIN ds_science as s
                        ON (s.id_science = bns.id_science)
                        WHERE bns.id_building = %d
                        ORDER BY s.id_branch",                          
            $id_building);


            $buildingNeedScience = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );

            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("can't get science list needs for building ".$id_building);
                return false;
            }
            echo "<tr>
                        <td>
                            <table width=100% border=0 cellspacing=1 cellpadding=3>";
            echo "<tr>
                    <td bgcolor='444E6C' class=th_2>Scince</td>";

            foreach ($title as $key => $branch)
            {
                switch ($branch['branchname'])
                {
                    case 'KADERIUM':
                        $tit = 'KADERIUM';
                        break;
                    case 'NANO_CRYSTAL':
                        $tit = 'NANO';
                        break;
                    case 'STASIS_ENERGY':
                        $tit = 'STASIS';
                        break;
                    case 'FOOD':
                        $tit = 'FOOD';
                        break;
                    case 'PRODIUM':
                        $tit = 'PRODIUM';
                        break;
                    case 'TRADE':
                        $tit = 'TRADE';
                        break;
                    case 'SPYING':
                        $tit = 'SPYING';
                        break;
                }

                echo "
                    <td align=center bgcolor='444E6C' class=th_2 width=12,5%>$tit</td>";
            }

            echo "</tr>";
            echo "<tr>";

            echo "<TD bgcolor='444E6C' class=maintxt><b>LEVEL</b></td>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='building'>             
                    <INPUT type='hidden' name='id_building' value='".$id_building."/".$id_race."'>";    

            $key = 0;
            foreach ($title as $k => $branch)
            {
                if ($branch['id_branch'] == $buildingNeedScience[$key]['id_branch'])
                {
                    echo "<TD bgcolor='444E6C' class=maintxt align=center>
                    <input type=text style='text-align: right;' size=6 name='science[".$branch['id_branch']."]' value=".$buildingNeedScience[$key]['level'].">
                    </td>";
                    $key ++;

                }else {
                    echo "<TD bgcolor='444E6C' class=maintxt align=center>
                                <input type=text style='text-align: right;' size=6 name='science[".$branch['id_branch']."]'>
                                </td>";
                }
            }
            echo "</tr>";
            echo "<tr>";
            echo "<td bgcolor='444E6C' class=maintxt colspan=8 align='right'>
                    <INPUT class='Submit' type='submit' name='editScinceLevel' value=' Edit '>  
                    </td>
                    </form>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>";
            echo "<table width=100% border=0 cellspacing=1 cellpadding=3>";
            /*print_r($title);
            echo "<br>";
            echo "<br>";
            print_r($unitNeedScience);  */
            /*echo "<xmp>";
            echo $sql;
            echo "</xmp>";  */

            //echo "<tr>
            //<td bgcolor='444E6C' class=maintxt>";
            $sql = sprintf("SELECT bnb.id_building_1,
            bnb.id_building_2,
                b.*,
                (
                                SELECT
                                        ltl.text
                                    FROM
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        lt.id_localizedtext = b.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as buildingname
                                
                                
                            FROM ds_buildingneedsbuilding as bnb
                            LEFT JOIN ds_buildingcanbuildbyrace as b ON (b.id_building = bnb.id_building_2)
                            WHERE bnb.id_building_1 = %d
                            AND
                            b.id_race = %d",
            constant('DEFAULT_LANG_ID'), $id_building, $id_race);
            //echo "<pre>";
            //echo $sql;

            $buildingNeedBuilding = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            //print_r($buildingNeedBuilding);
            //echo "</pre>";
            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("can't get building list needs for building ".$id_building);
                return false;
            }

            if (!(sizeof($buildingNeedBuilding) === 0))
            {
                //echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!";
                echo "<tr>";
                echo "<td bgcolor='444E6C' colspan=2 class=th_2>Buildings needs for building</td>";
                echo "</tr>";

                foreach ($buildingNeedBuilding as $key => $building)
                {
                    echo "<tr>";
                    echo "<td bgcolor='444E6C' class=maintxt>";
                    echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>";
                    echo $building['buildingname']." ";
                    echo "</td>";
                    echo "<td bgcolor='444E6C' class=maintxt>";
                    echo "<input class='Submit' type=submit name='DeleteBuildingneedsbuilding' value='Delete'>";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }
            }

            $sql = sprintf("SELECT

                                bcbbr.id_building,
                                (
                                SELECT
                                        ltl.text
                                    FROM
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        lt.id_localizedtext = bcbbr.id_localizedname
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as buildingname
                        FROM
                        ds_buildingcanbuildbyrace as bcbbr
                        WHERE
                        bcbbr.id_race = %d",
            constant('DEFAULT_LANG_ID'), $id_race);

            $BuildingList = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );

            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("can't get building list needs for building ".$id_building);
                return false;
            }
            echo "<td bgcolor='444E6C' class=maintxt>";

            echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>";
            echo "<SELECT name='id_race'>";
            foreach ($BuildingList as $key => $data)
            {
                echo "<OPTION selected value='".$data['id_building']."'>".$data['buildingname']."</OPTION>";
            }
            echo "</SELECT>";
            echo "<td bgcolor='444E6C' class=maintxt>";
            echo "<input class='Submit' type=submit name='DeleteBuildingneedsbuilding' value='Add new building'>";
            echo "</td>";
            echo "</form>";
            echo "</td>";
            echo "</td>
                    </tr></table>
                    </td>
                    </tr></table>";
        }

        return true;
    }

    function getBuildingNewAttributesSelect($id_building)
    {
        assert(isset($id_building));
        $sql = sprintf("SELECT
                                an.attributename, 
                                at.typename,
                                a.id_attribute
                            FROM
                                    ds_attribute a,
                                    ds_attributename an,
                                    ds_attributetype at
                                WHERE
                                    a.id_attributename = an.id_attributename
                                    AND a.id_attributetype = at.id_attributetype
                                    AND a.id_attribute not in
                                    (
                                    SELECT
                                            bha.id_attribute
                                        FROM
                                            ds_buildinghasattribute bha
                                        WHERE
                                            bha.id_building = %d
                                    )
                                    order by at.typename, an.attributename",
        $id_building);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg || sizeof($res) === 0)
        {
            $this->outputResult("can't get new attributes for building with id= ".
            $id_building);
            return false;
        }

        $str = "<SELECT name='new_building_attribute'>";

        foreach ($res as $k=>$v)
        {
            $str.= "<OPTION value='".
            $v['id_attribute'].
            "'>".$v['typename'].' --> '.$v['attributename']."</OPTION>";
        }

        $str.="</SELECT>";
        return $str;
    }

    function updateArtefactAttributes($data)
    {
        if(sizeof($data) <= 0)
        {
            return false;
        }

        $detail = '';

        foreach ($data as $k=>$v)
        {
            if(preg_match('/^id_artbonus[0-9]*$/',$k) > 0)
            {
                $id_artefacthasbonus = substr($k,11);

                assert((float)$id_artefacthasbonus > 0);

                //Получаем имя атрибута по его id
                $attributeName = $this->getAttributeNameByid_artefacthasbonus($id_artefacthasbonus);
                
                $sql = sprintf("UPDATE
                                        ds_artefacthasbonus
                                    SET
                                        value = %f
                                    WHERE
                                        id_artefacthasbonus = %d",
                $v,
                $id_artefacthasbonus);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't change artefact bonus with id_artefacthasbonus".
                    $id_artefacthasbonus."<br>");
                }
                
                $detail .= "Set bonus=$v for artefact '$attributeName'<br>";
            }
        }
        
        //Логируем
        actionLog("Change artefacts attribute", $detail);

        return true;
    }

    function outputResult($str)
    {
        echo $str;
        return true;
    }

    function addUnitAttribute($data)
    {
        assert(isset($data['id_unit']));
        assert(isset($data['new_attr_value']));
        assert(isset($data['new_unit_attribute']));

        //Получаем имя юнитa по его id
        $sql = sprintf("SELECT u.unitname
                                FROM ds_unit as u
                                WHERE
                                u.id_unit = %s",
        $data['id_unit']);
        $unitNameArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        $unitName = $unitNameArr[0]['unitname'];

        //Получаем название аттрибута по его id
        $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                                    SELECT a.id_attributename
                                                                    FROM ds_attribute as a
                                                                    WHERE a.id_attribute = %s
                                                                )",
        $data['new_unit_attribute']);

        $attributeNameArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        $attributeName = $attributeNameArr[0]['attributename'];

        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                                    SELECT a.id_attributetype
                                                                    FROM ds_attribute as a
                                                                    WHERE a.id_attribute = %s
                                    )",
        $data['new_unit_attribute']);

        $attributeTypeArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        $attributeType = $attributeTypeArr[0]['typename'];


        $sql = sprintf("INSERT INTO
                                    ds_unithasattribute
                                    (id_attribute,id_unit,value)
                            VALUES
                                    (%d,%d,%f)",
        $data['new_unit_attribute'],
        $data['id_unit'],
        $data['new_attr_value']);

        $this->getDBConnection()->executeQuery($sql);
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("can't attribute with id ".$data['new_unit_attribute'].
            "to unit with id".$data['id_unit']."<br>");
        }

        actionLog("Edd attribute for unit $unitName", "Attribute type '$attributeType'<br>Attribute name '$attributeName'<br>value=".$data['new_attr_value']);

        return true;
    }

    function updateUnitAttributes($data)
    {
        if(sizeof($data) <= 0)
        {
            return false;
        }

        $detail = "";
        
        //Получаем имя юнитa по его id
        $sql = sprintf("SELECT u.unitname
                                FROM ds_unit as u
                                WHERE
                                u.id_unit = %s",
        $data['id_unit']);

        $unitNameArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        $unitName = $unitNameArr[0]['unitname'];


        foreach ($data as $k=>$v)
        {
            if(preg_match('/^idattr[0-9]*$/',$k) > 0)
            {
                $id_unithasattribute =  substr($k,6);

                assert((int)$id_unithasattribute > 0);

                //Получаем название аттрибута по его id
                $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                                    SELECT a.id_attributename
                                                                    FROM ds_attribute as a
                                                                    WHERE a.id_attribute = (SELECT u.id_attribute
                                                                                            FROM ds_unithasattribute as u
                                                                                            WHERE u.id_unithasattribute = %s
                                                                                            )
                                                                )",
                $id_unithasattribute);

                $attributeNameArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
                $attributeName = $attributeNameArr[0]['attributename'];

                //Получаем тип аттрибута по его id
                $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                                    SELECT a.id_attributetype
                                                                    FROM ds_attribute as a
                                                                    WHERE a.id_attribute = (SELECT u.id_attribute
                                                                                            FROM ds_unithasattribute as u
                                                                                            WHERE u.id_unithasattribute = %s
                                                                                            )
                                    )",
                $id_unithasattribute);

                $attributeTypeArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
                $attributeType = $attributeTypeArr[0]['typename'];

                $detail .= "Set value = $v<br>(Attribute type '$attributeType'<br> Attribute name '$attributeName')<br>";


                $sql = sprintf("UPDATE
                                        ds_unithasattribute
                                    SET
                                        value = %f
                                    WHERE
                                        id_unithasattribute = %d",
                $v,
                $id_unithasattribute);


                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't change unit attribute with id".
                    $id_unithasattribute."<br>");
                }
            }
            if(preg_match('/^delattr[0-9]*$/',$k) > 0)
            {
                $id_unithasattribute =  substr($k,7);

                assert((int)$id_unithasattribute > 0);

                //Получаем название аттрибута по его id
                $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                                    SELECT a.id_attributename
                                                                    FROM ds_attribute as a
                                                                    WHERE a.id_attribute = (SELECT u.id_attribute
                                                                                            FROM ds_unithasattribute as u
                                                                                            WHERE u.id_unithasattribute = 758
                                                                                            )
                                                                )",
                $id_unithasattribute);

                $attributeNameArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
                $attributeName = $attributeNameArr[0]['attributename'];

                //Получаем тип аттрибута по его id
                $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                                    SELECT a.id_attributetype
                                                                    FROM ds_attribute as a
                                                                    WHERE a.id_attribute = (SELECT u.id_attribute
                                                                                            FROM ds_unithasattribute as u
                                                                                            WHERE u.id_unithasattribute = 758
                                                                                            )
                                    )",
                $id_unithasattribute);


                $attributeTypeArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
                $attributeType = $attributeTypeArr[0]['typename'];



                $sql = sprintf("DELETE FROM
                                        ds_unithasattribute                                 
                                    WHERE
                                        id_unithasattribute = %d",                                  
                $id_unithasattribute);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't change unit attribute with id".
                    $id_unithasattribute."<br>");
                }



                $detail .= "<b>Delete attribute</b><br>(Attribute type '$attributeType'<br>Attribute name '$attributeName'<br>Old value=$v)<br>";
            }
        }

        actionLog("Edit attribute for unit $unitName", $detail);

        return true;
    }

    function updateUnitScinceLevel($data)
    {
        assert(isset($data['id_unit']));


        if(sizeof($data) <= 0)
        {
            return false;
        }

        $newScienceLevel = array();
        $oldScienceLevel = array();
        $detail = "";

        //Получаем имя юнитa по его id
        $sql = sprintf("SELECT u.unitname
                                FROM ds_unit as u
                                WHERE
                                u.id_unit = %s",
        $data['id_unit']);

        $unitNameArr = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        $unitName = $unitNameArr[0]['unitname'];
    
        //Определяем id_science по id_branch и level
        foreach ($data['science'] as $id_branch=>$level)
        {
            
        /*//Получаем имя науки
        $sql = "SELECT
                        branchname
                    from
                        ds_sciencebranch
                    where
                        id_branch ='$id_branch'
                    ";
        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );

        if ($common->errormsg)
        {
            echo "<h2><font color=red>Can't normal log thise action</font></h2>";
        }
        $scienceName = $res[0]['branchname'];
echo $scienceName;
exit;   */  
            if ($level != '')
            {
                $sql = sprintf("SELECT id_science
                            FROM ds_science
                            WHERE id_branch = %d
                                  AND
                                  level = %d",                                  
                $id_branch,
                $level);

                $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't get new science id");
                    return false;
                }
                if (sizeof($res) === 0)
                {
                    $sql = sprintf("SELECT branchname
                                    FROM ds_sciencebranch 
                                    WHERE id_branch=%d",                                    
                    $id_branch);

                    $branch = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );

                    echo "<h1><font color=red>Wrong level ".$level." of science '".$branch[0]['branchname']."'!</font></h1>";
                    echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
                    <INPUT type='hidden' name='attr_type' value='unit'>             
                    <INPUT type='hidden' name='id_unit' value='".$data['id_unit']."'>   
                    <INPUT class='Submit' type='submit' value=' Back '> 
                    </form>";
                    exit;
                }else {
                    $newScienceLevel[]  = $res;
                }
                    $detail .= "Set level='$level' of science '".$branch[0]['branchname']."<br>";

            }
            
        }

        //Получаем список наук, которые были нужны раньше
        $sql = sprintf("SELECT id_science
                        FROM ds_unitneedsscience
                        WHERE id_unit = %d",                                    
        $data['id_unit']);

        $oldScienceLevel = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("can't get old science id");
            return false;
        }

        //Добавляем новые науки
        $flag = false;

        foreach ($newScienceLevel as $newkey => $newValue)
        {
            foreach ($oldScienceLevel as $oldkey => $oldValue)
            {
                if ($newValue[0]['id_science'] == $oldValue['id_science'])
                {
                    $flag = true;
                }
            }
            if ($flag == false)
            {
                assert((int)$newValue[0]['id_science'] > 0);

                $sql = sprintf("INSERT into
                                ds_unitneedsscience (id_science, id_unit)
                                values
                                (%d, %d)",                                  
                $newValue[0]['id_science'], $data['id_unit']);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't update science level need for unit with id".
                    $data['id_unit']."<br>");
                }
            }
            $flag = false;
        }

        //Удаляем старые науки
        $flag = false;

        foreach ($oldScienceLevel as $oldkey => $oldValue)
        {
            foreach ($newScienceLevel as $newkey => $newValue)
            {
                if ($newValue[0]['id_science'] == $oldValue['id_science'])
                {
                    $flag = true;
                }
            }
            if ($flag == false)
            {
                assert((int)$newValue[0]['id_science'] > 0);

                $sql = sprintf("DELETE FROM
                            ds_unitneedsscience                             
                            WHERE
                            id_unit = %d AND id_science = %d",                                  
                $data['id_unit'], $oldValue['id_science']);
                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't update science level need for unit with id".
                    $data['id_unit']."<br>");
                }
                
                //$detail .= "Delete science '$scienceName'<br>";

            }
            $flag = false;
        }
        
        actionLog("Edit science level for unit $unitName", $detail);
        return true;
    }

    function updateBuildingScinceLevel($data)
    {
        assert(isset($data['id_building']));

        if(sizeof($data) <= 0)
        {
            return false;
        }

        //Получаем имя здания по его id
        $buildingName = $this->getBuildingNameByIDAndRace($data['id_building']);
        
        $newScienceLevel = array();
        $oldScienceLevel = array();
        $detail = "";

        //Определяем id_science по id_branch и level
        foreach ($data['science'] as $id_branch=>$level)
        {
            //Получаем имя науки
            $sql = "SELECT
                            branchname
                        from
                            ds_sciencebranch
                        where
                            id_branch ='$id_branch'
                        ";
            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
    
            if ($common->errormsg)
            {
                echo "<h2><font color=red>Can't normal log thise action</font></h2>";
            }
            $scienceName = $res[0]['branchname'];
        
            if ($level != '')
            {
                $sql = sprintf("SELECT id_science
                            FROM ds_science
                            WHERE id_branch = %d
                                  AND
                                  level = %d",                                  
                $id_branch,
                $level);

                $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't get new science id");
                    return false;
                }
                if (sizeof($res) === 0)
                {
                    $sql = sprintf("SELECT branchname
                                    FROM ds_sciencebranch 
                                    WHERE id_branch=%d",                                    
                    $id_branch);

                    $branch = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );

                    echo "<h1><font color=red>Wrong level ".$level." of science '".$branch[0]['branchname']."'!</font></h1>";
                    echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
                    <INPUT type='hidden' name='attr_type' value='building'>             
                    <INPUT type='hidden' name='id_building' value='".$data['id_building']."'>   
                    <INPUT class='Submit' type='submit' value=' Back '> 
                    </form>";
                    exit;
                }else {
                    $newScienceLevel[]  = $res;
                }
            }
            $detail .= "Set level='$level' of science '".$scienceName."<br>";
        }

        //Получаем список наук, которые были нужны раньше
        $sql = sprintf("SELECT id_science
                        FROM ds_buildingneedsscience
                        WHERE id_building = %d",                                    
        $data['id_building']);

        $oldScienceLevel = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("can't get old science id");
            return false;
        }

        //Добавляем новые науки
        $flag = false;

        foreach ($newScienceLevel as $newkey => $newValue)
        {
            foreach ($oldScienceLevel as $oldkey => $oldValue)
            {
                if ($newValue[0]['id_science'] == $oldValue['id_science'])
                {
                    $flag = true;
                }
            }
            if ($flag == false)
            {
                assert((int)$newValue[0]['id_science'] > 0);

                $sql = sprintf("INSERT into
                                ds_buildingneedsscience (id_science, id_building)
                                values
                                (%d, %d)",                                  
                $newValue[0]['id_science'], $data['id_building']);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't update science level need for unit with id".
                    $data['id_building']."<br>");
                }
            }
            $flag = false;
        }

        //Удаляем старые науки
        $flag = false;

        foreach ($oldScienceLevel as $oldkey => $oldValue)
        {
            foreach ($newScienceLevel as $newkey => $newValue)
            {
                if ($newValue[0]['id_science'] == $oldValue['id_science'])
                {
                    $flag = true;
                }
            }
            if ($flag == false)
            {
                assert((int)$newValue[0]['id_science'] > 0);

                $sql = sprintf("DELETE FROM
                            ds_buildingneedsscience                             
                            WHERE
                            id_building = %d AND id_science = %d",                                  
                $data['id_building'], $oldValue['id_science']);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't update science level need for unit with id".
                    $data['id_building']."<br>");
                }
            }
            $flag = false;
        }
        
        //Логируем
        actionLog("Edit science level for building '$buildingName'", $detail);
        
        return true;
    }


    function showArtefactAttributes($id_artefactscope,
    $id_attributename,
    $id_attributetype,
    $id_artefactprobability,
    $show)
    {
        echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='arts'>             
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                ".
        $this->getArtefactScopeSelect($id_artefactscope)
        ."</TD>
                            <TD bgcolor='444E6C' class=th_2>
                                ".
        $this->getArtefactAttributeTypeSelect($id_attributetype)
        ."</TD>
                            <TD bgcolor='444E6C' class=th_2>
                                ".
        $this->getArtefactBonusType($id_attributename)
        ."</TD>
                            <TD bgcolor='444E6C' class=th_2>
                                ".
        $this->getArtefactSizeSelect($id_artefactprobability)
        ."</TD>
                        </TR>                       
                        <TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt colspan='4'>
                                <input type='submit'  class='Submit' name='search2' value='Get'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                </table>";
        if($show)
        {
            $sql = "SELECT
                            ltl.text,
                            a.id_artefact,
                            ass.scopename,
                        --    apa.id_artefactprobability,
                            apa.prefix,
                        --      at.id_artefacttype,                             
                            ahb.value,
                            ahb.id_artefacthasbonus,
                            an.attributename,
                            attt.typename
                        FROM
                            ds_artefact a,
                            ds_artefacttype at,
                            ds_artefactscope ass,
                            ds_artefactprobability apa,
                            ds_artefacthasbonus ahb,
                            ds_attribute att,
                            ds_attributename an,
                            ds_attributetype attt,
                            ds_localizedtext lt,
                            ds_localizedtextlanguage ltl
                        WHERE
                            a.id_artefactprobability = apa.id_artefactprobability
                            AND a.id_artefactscope = ass.id_artefactscope
                            AND a.id_artefacttype = at.id_artefacttype
                            AND a.id_localizedname = lt.id_localizedtext
                            AND lt.id_localizedtext = ltl.id_localizedtext
                            AND ltl.id_language = ".constant('DEFAULT_LANG_ID')."
                            AND  a.id_artefact = ahb.id_artefact
                            AND ahb.id_attribute = att.id_attribute
                            AND att.id_attributetype = attt.id_attributetype
                            AND att.id_attributename = an.id_attributename ";

            $scope_revision = '';
            if(isset($id_artefactscope) && $id_artefactscope > 0)
            {
                $scope_revision = " AND ass.id_artefactscope=".$id_artefactscope;
            }

            $attr_type_revision = '';
            if(isset($id_attributetype) && $id_attributetype > 0)
            {
                $attr_type_revision = " AND att.id_attributetype =".$id_attributetype;
            }

            $attr_name_revision = '';
            if(isset($id_attributename) && $id_attributename > 0)
            {
                $attr_name_revision = " AND att.id_attributename =".$id_attributename;
            }

            $probability_revision = '';
            if(isset($id_artefactprobability) && $id_artefactprobability > 0)
            {
                $probability_revision = " AND apa.id_artefactprobability=".$id_artefactprobability;
            }

            $sql .= $scope_revision;
            $sql .= $attr_type_revision;
            $sql .= $attr_name_revision;
            $sql .= $probability_revision;
            $sql .= "ORDER BY ass.id_artefactscope, an.attributename, apa.id_artefactprobability";

            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg)
            {
                echo pg_last_error();
                $this->outputResult("error in getting arts");
                return false;
            }

            echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='arts'>             
                <INPUT type='hidden' name='id_artefactscope' value='".$id_artefactscope."'>
                <INPUT type='hidden' name='id_attributetype' value='".$id_attributetype."'>
                <INPUT type='hidden' name='id_attributename' value='".$id_attributename."'>
                <INPUT type='hidden' name='id_artefactprobability' value='".$id_artefactprobability."'>
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2 align='center'>
                                Artefact name
                            </TD>                           
                            <TD bgcolor='444E6C' class=th_2 align='center'>
                                Scope
                            </TD>
                            <TD bgcolor='444E6C' class=th_2 align='center'>
                                Size
                            </TD>                           
                            <TD bgcolor='444E6C' class=th_2 align='center'>
                                Kind of bonus
                            </TD>
                            <TD bgcolor='444E6C' class=th_2 align='center'>
                                 Bonus for
                            </TD>
                            
                            <TD bgcolor='444E6C' class=th_2 align='center'>
                                Bonus
                            </TD>
                        </TR>";     

            foreach ($res as $k=>$v)
            {
                echo    "<TR>
                            <TD bgcolor='444E6C' class=maintxt>".
                $v['text']
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt align='center'>".
                $v['scopename']
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt align='center'>".
                $v['prefix']
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt align='center'>".
                $v['typename']
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt align='center'>".
                $v['attributename']
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt align='center'>
                                <input type='text' size='7' value='".round($v['value'], 8)."' name = 'id_artbonus".$v['id_artefacthasbonus']."'>
                            </TD>
                        </TR>";
            }

            echo "<TR>
                    <TD bgcolor='#444E6C' align='right' class=maintxt colspan='6'>
                        <input type='submit'  class='Submit' name='edit' value='Edit'>
                    </TD>
                  </TR>
                  </TABLE>
                  </td>
                </tr>
                </form>
                </table>";
        }

        return true;
    }

    function getArtefactSizeSelect($id_artefactprobability = 0)
    {
        $str = "<SELECT name='id_artefactprobability'>
                <OPTION value='-1'>-----select size--------</OPTION>";
        $sql = sprintf("SELECT
                                apa.id_artefactprobability,
                                apa.prefix
                            FROM
                                ds_artefactprobability apa
                                order by  apa.id_artefactprobability");

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of artefact sizes");
            return false;
        }

        foreach ($res as $k=>$v)
        {
            if(strcmp($id_artefactprobability,$v['id_artefactprobability']) === 0)
            {
                $str .= "<OPTION selected value='".$v['id_artefactprobability']."'>".$v['prefix']."</OPTION>";
            }
            else
            {
                $str .= "<OPTION value='".$v['id_artefactprobability']."'>".$v['prefix']."</OPTION>";
            }
        }

        $str .="</SELECT>";
        return $str;
    }

    function getArtefactScopeSelect($id_artefactscope = 0)
    {
        $str = "<SELECT name='id_artefactscope'>
                <OPTION value='-1'>-----select scope--------</OPTION>";
        $sql = sprintf("SELECT
                                ass.id_artefactscope,
                                ltl.text
                            FROM
                                ds_artefactscope ass,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl
                            WHERE
                                ass.id_localizedscope = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d",
        constant( 'DEFAULT_LANG_ID' ));
        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of artefactscopes");
            return false;
        }

        foreach ($res as $k=>$v)
        {
            if(strcmp($id_artefactscope,$v['id_artefactscope']) === 0)
            {
                $str .= "<OPTION selected value='".$v['id_artefactscope']."'>".$v['text']."</OPTION>";
            }
            else
            {
                $str .= "<OPTION value='".$v['id_artefactscope']."'>".$v['text']."</OPTION>";
            }
        }

        $str .="</SELECT>";
        return $str;
    }

    function getArtefactAttributeTypeSelect($id_attributetype = 0)
    {
        $str = "<SELECT name='id_attributetype'>
                <OPTION value='-1'>-----select bonus type-----</OPTION>";
        $sql = sprintf("SELECT DISTINCT
                                        ltl.text,
                                        attt.id_attributetype
                                    FROM
                                        ds_artefact a,
                                        ds_artefacthasbonus ahb,
                                        ds_attribute att,
                                        ds_attributetype attt,
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        a.id_artefact = ahb.id_artefact
                                        AND ahb.id_attribute = att.id_attribute
                                        AND att.id_attributetype = attt.id_attributetype
                                        AND attt.id_localizedtype = lt.id_localizedtext
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d",
        constant( 'DEFAULT_LANG_ID' ));
        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of artefactscopes");
            return false;
        }

        foreach ($res as $k=>$v)
        {
            if(strcmp($id_attributetype,$v['id_attributetype']) === 0)
            {
                $str .= "<OPTION selected value='".$v['id_attributetype']."'>".$v['text']."</OPTION>";
            }
            else
            {
                $str .= "<OPTION value='".$v['id_attributetype']."'>".$v['text']."</OPTION>";
            }
        }

        $str .="</SELECT>";
        return $str;
    }

    function getArtefactBonusType($id_attributename = 0)
    {
        $str = "<SELECT name='id_attributename'>
                <OPTION value='-1'>-----select to that bonus-----</OPTION>";
        $sql = sprintf("SELECT DISTINCT
                                        att.id_attributename,
                                        an.attributename
                                    FROM    
                                        ds_artefact a,
                                        ds_artefacthasbonus ahb,
                                        ds_attribute att,
                                        ds_attributename an
                                    WHERE
                                        a.id_artefact = ahb.id_artefact
                                        AND ahb.id_attribute = att.id_attribute
                                        AND att.id_attributename = an.id_attributename");

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of bonus types");
            return false;
        }

        foreach ($res as $k=>$v)
        {
            if(strcmp($id_attributename,$v['id_attributename']) === 0)
            {
                $str .= "<OPTION selected value='".$v['id_attributename']."'>".$v['attributename']."</OPTION>";
            }
            else
            {
                $str .= "<OPTION value='".$v['id_attributename']."'>".$v['attributename']."</OPTION>";
            }
        }

        $str .="</SELECT>";
        return $str;
    }

    function showUnitAttributes($id_unit)
    {
        $races_names = array('Voraners','Liensu','Psolao','Quest units');

        echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='unit'>             
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                <SELECT name='id_unit'>
                                <OPTION disabled>---------------".$races_names[0]."--------------</OPTION>";    
        for($i=1; $i<5; $i++)
        {
            $sql = sprintf("
                            SELECT
                                un.id_unit,
                                ltl.text as unitname
                            FROM    
                                ds_unit un  
                                LEFT JOIN ds_unitcanbebuildbyrace ucbbbr USING(id_unit)
                                LEFT JOIN ds_unitorcontainertype uct USING(id_unitcontainertype)
                                LEFT JOIN ds_unithasclass uhc USING(id_unit)
                                LEFT JOIN ds_unitclass uc ON (uhc.id_unitclass = uc.id_unitclass)
                                LEFT JOIN ds_localizedtext lot ON (un.id_localizedname = lot.id_localizedtext)
                                LEFT JOIN ds_localizedtextlanguage ltl ON ( lot.id_localizedtext = ltl.id_localizedtext)
                            WHERE
                                -- uct.containertypename = 'ARMY'
                                -- AND 
                                ltl.id_language = %d
                                AND ucbbbr.id_race = %d                                                                                                             
                        ORDER BY
                                uc.classnumber",
            constant( 'DEFAULT_LANG_ID' ),
            $i);
            echo $sql;

            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );

            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("error in getting list of units");
                return false;
            }

            foreach($res as $row)
            {
                if(strcmp($id_unit,$row['id_unit']) === 0)
                {
                    echo "<OPTION selected value='".$row['id_unit']."'>".$row['unitname']."</OPTION>";
                }
                else
                {
                    echo "<OPTION value='".$row['id_unit']."'>".$row['unitname']."</OPTION>";
                }
            }
            echo "<OPTION disabled>---------------".$races_names[$i]."--------------</OPTION>";
        }

        $sql = sprintf("
                        SELECT
                                un.id_unit,
                                ltl.text as unitname
                            FROM    
                                ds_unit un,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl,
                                ds_unitorcontainertype uct
                            WHERE
                                un.id_unitcontainertype = uct.id_unitcontainertype
                                AND uct.containertypename = 'ARMY'
                                AND un.id_localizedname = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d
                                AND un.id_unit not in
                                (
                                SELECT
                                        id_unit
                                    FROM
                                        ds_unitcanbebuildbyrace
                                )",
        constant('DEFAULT_LANG_ID')
        );

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of units");
            return false;
        }


        foreach($res as $row)
        {
            if(strcmp($id_unit,$row['id_unit']) === 0)
            {
                echo "<OPTION selected value='".$row['id_unit']."'>".$row['unitname']."</OPTION>";
            }
            else
            {
                echo "<OPTION value='".$row['id_unit']."'>".$row['unitname']."</OPTION>";
            }
        }

        echo "</SELECT>
                            </TD>
                        </TR>                       
                        <TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt colspan='2'>
                                <input type='submit'  class='Submit' name='search2' value='Get'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                </table>";

        if(isset($id_unit) && (int)$id_unit > 0)
        {
            $sql = sprintf("SELECT
                                an.attributename,
                                at.typename,
                                (
                                SELECT
                                        ltl.text
                                    FROM
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        lt.id_localizedtext = u.id_localizedname
                                        and lt.id_localizedtext = ltl.id_localizedtext
                                        and ltl.id_language = %d
                                ) as unitname,                                  
                                uha.id_unithasattribute,
                                uha.value
                            FROM
                                ds_unit u,
                                ds_unithasattribute uha,
                                ds_attribute a,
                                ds_attributename an,
                                ds_attributetype at
                            WHERE
                                u.id_unit = %d
                                and u.id_unit = uha.id_unit
                                and uha.id_attribute = a.id_attribute
                                and a.id_attributename = an.id_attributename
                                and a.id_attributetype = at.id_attributetype
                            order by at.id_attributetype",                          
            constant( 'DEFAULT_LANG_ID'),
            $id_unit);
            /*echo "<xmp>";
            echo $sql;
            echo "</xmp>";*/

            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg || sizeof($res) === 0)
            {
                $this->outputResult("can't get attributes of unit with id".$id_unit);
                return false;
            }


            echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='unit'>             
                <INPUT type='hidden' name='id_unit' value='".$id_unit."'>               
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2 colspan=4 align='center'>".
            $res[0]['unitname']
            ."</TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute type
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute name
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Value
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Delete attribute
                            </TD>
                        </TR>";                 
            foreach ($res as $k=>$v)
            {
                echo "<TR>
                            <TD bgcolor='444E6C' class=maintxt>".
                $v['typename']
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>".
                $v['attributename']
                ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>
                                <INPUT type='text' name='idattr".
                $v['id_unithasattribute'].
                "' value='".round($v['value'], 8)."'>
                            </TD>
                            <TD bgcolor='444E6C' class=maintxt align='center'>
                                <INPUT type='checkbox' class='check' name='delattr".
                $v['id_unithasattribute'].
                "'>
                            </TD>
                        </TR>";                 
            }
            echo        "<TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt colspan='4'>
                                <input type='submit'  class='Submit' name='edit' value='Edit'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                    <INPUT type='hidden' name='attr_type' value='unit'>             
                    <INPUT type='hidden' name='id_unit' value='".$id_unit."'>   
                <tr>
                    <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=maintxt>".   
            $this->getUnitNewAttributesSelect($id_unit)
            ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>         
                                <INPUT type='text' maxlength=10' name='new_attr_value'>             
                            </TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=maintxt colspan=3 align='right'>     
                                <INPUT class='Submit' type='submit' name='add' value='Add'>                 
                            </TD>                           
                        </TR>   
                    </table>
                    </td>
                </tr>
                </form>
                ";

            $sql = sprintf("SELECT sb.id_branch,
                                   sb.branchname
                            FROM ds_sciencebranch as sb
                            ORDER BY sb.id_branch");


            $title = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg || sizeof($title) === 0)
            {
                $this->outputResult("can't get science list");
                return false;
            }

            $sql = sprintf("SELECT s.id_science,
                               s.id_branch,
                               s.level
                        FROM ds_unitneedsscience as uns
                        LEFT JOIN ds_science as s
                        ON (s.id_science = uns.id_science)
                        WHERE uns.id_unit = %d
                        ORDER BY s.id_branch",                          
            $id_unit);


            $unitNeedScience = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("can't get science list needs for unit ".$id_unit);
                return false;
            }
            echo "<tr>
                        <td>
                            <table width=100% border=0 cellspacing=1 cellpadding=3>";
            echo "<tr>
                    <td bgcolor='444E6C' class=th_2>Scince</td>";

            foreach ($title as $key => $branch)
            {
                switch ($branch['branchname'])
                {
                    case 'KADERIUM':
                        $tit = 'KADERIUM';
                        break;
                    case 'NANO_CRYSTAL':
                        $tit = 'NANO';
                        break;
                    case 'STASIS_ENERGY':
                        $tit = 'STASIS';
                        break;
                    case 'FOOD':
                        $tit = 'FOOD';
                        break;
                    case 'PRODIUM':
                        $tit = 'PRODIUM';
                        break;
                    case 'TRADE':
                        $tit = 'TRADE';
                        break;
                    case 'SPYING':
                        $tit = 'SPYING';
                        break;
                }

                echo "
                    <td align=center bgcolor='444E6C' class=th_2 width=12,5%>$tit</td>";
            }

            echo "</tr>";
            echo "<tr>";

            echo "<TD bgcolor='444E6C' class=maintxt><b>LEVEL</b></td>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='unit'>             
                <INPUT type='hidden' name='id_unit' value='".$id_unit."'>";

            $key = 0;
            foreach ($title as $k => $branch)
            {
                if ($branch['id_branch'] == $unitNeedScience[$key]['id_branch'])
                {
                    echo "<TD bgcolor='444E6C' class=maintxt align=center>
                    <input type=text style='text-align: right;' size=6 name='science[".$branch['id_branch']."]' value=".$unitNeedScience[$key]['level'].">
                    </td>";
                    $key ++;

                }else {
                    echo "<TD bgcolor='444E6C' class=maintxt align=center>
                                <input type=text style='text-align: right;' size=6 name='science[".$branch['id_branch']."]'>
                                </td>";
                }
            }
            echo "</tr>";
            echo "<tr>";
            echo "<td bgcolor='444E6C' class=maintxt colspan=8 align='right'>
                    <INPUT class='Submit' type='submit' name='editScinceLevel' value=' Edit '>  
                    </td>
                    </form>
                </tr>
            </table>
        </td>
    </tr>";
            /*print_r($title);
            echo "<br>";
            echo "<br>";
            print_r($unitNeedScience);  */
            /*echo "<xmp>";
            echo $sql;
            echo "</xmp>";  */
            echo "</table>";
        }

        return true;
    }

    function updateBuildingsAttributes($data)
    {
        if(sizeof($data) <= 0)
        {
            return false;
        }
        $detail = "";

        //Получаем имя здания по его id
        $buildingName = $this->getBuildingNameByIDAndRace($data['id_building']);
        
        foreach ($data as $k=>$v)
        {
            if(preg_match('/^idbha[0-9]*$/',$k) > 0)
            {
                $id_buildinghasattribute =  substr($k,5);

                assert((int)$id_buildinghasattribute > 0);
                $sql = sprintf("UPDATE
                                        ds_buildinghasattribute
                                    SET
                                        value = %f
                                    WHERE
                                        id_buildinghasattribute = %d",
                $v,
                $id_buildinghasattribute);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't change building attribute with id".
                    $id_buildinghasattribute."<br>");
                }
                
                //Получаем имя атрибута по его id
                $attributeName = $this->getBuilldingAttributeNameByID($id_buildinghasattribute);
                
                //Получаем тип атрибута по его id
                $attributeType = $this->getBuilldingAttributeTipeByID($id_buildinghasattribute);
                
                
                $detail .= "Set value = $v<br>(Attribute type '$attributeType'<br>Attribute name '$attributeName')<br>";
            }
        }
        
        //Логируем
        actionLog("Change attributes for building '$buildingName' ", $detail);

        return true;
    }

    function getUnitNewAttributesSelect($id_unit)
    {
        assert(isset($id_unit));
        $sql = sprintf("SELECT
                                an.attributename, 
                                at.typename,
                                a.id_attribute
                            FROM
                                    ds_attribute a,
                                    ds_attributename an,
                                    ds_attributetype at
                                WHERE
                                    a.id_attributename = an.id_attributename
                                    AND a.id_attributetype = at.id_attributetype
                                    AND a.id_attribute not in
                                    (
                                    SELECT
                                            uha.id_attribute
                                        FROM
                                            ds_unithasattribute uha
                                        WHERE
                                            uha.id_unit = %d
                                    )
                                    order by at.typename, an.attributename",                            
        $id_unit);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg || sizeof($res) === 0)
        {
            $this->outputResult("can't get new attributes for unit with id".$id_unit);
            return false;
        }

        $str = "<SELECT name='new_unit_attribute'>";

        foreach ($res as $k=>$v)
        {
            $str.= "<OPTION value='".
            $v['id_attribute'].
            "'>".$v['typename'].' --> '.$v['attributename']."</OPTION>";
        }

        $str.="</SELECT>";
        return $str;
    }

    function getTFNewAttributesSelect($id_tf)
    {
        assert(isset($id_tf));
        $sql = sprintf("SELECT
                                an.attributename, 
                                at.typename,
                                a.id_attribute
                            FROM
                                    ds_attribute a,
                                    ds_attributename an,
                                    ds_attributetype at
                                WHERE
                                    a.id_attributename = an.id_attributename
                                    AND a.id_attributetype = at.id_attributetype
                                    AND a.id_attribute not in
                                    (
                                    SELECT
                                            tfa.id_attribute
                                        FROM
                                            ds_terraforminghasattribute tfa
                                        WHERE
                                            tfa.id_terraforming = %d
                                    )
                                    order by at.typename, an.attributename",
        $id_tf);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg || sizeof($res) === 0)
        {
            $this->outputResult("can't get new attributes for terraforming with id= ".
            $id_tf);
            return false;
        }

        $str = "<SELECT name='new_tf_attribute'>";

        foreach ($res as $k=>$v)
        {
            $str.= "<OPTION value='".
            $v['id_attribute'].
            "'>".$v['typename'].' --> '.$v['attributename']."</OPTION>";
        }

        $str.="</SELECT>";
        return $str;
    }

    function getDBConnection()
    {
        if(!$this->common)
        {
            $_SESSION['dbId'] = 0;

            $this->common = DS_Common::CreateForCurentDB(0);
            if (!$this->common->connect())
            {
                $this->outputResult('DB connection failed');
                exit();
            }
        }
        $this->common->setDbId(0);

        return $this->common;
    }

    function getWorldsSelect($world)
    {
        $str = "<SELECT name='dbid'>";
        $sql = "SELECT worldname, id_world from ds_world";
        $res = $this->getDBConnection()->getMultyArray("", $sql,"","","");
        if($this->getDBConnection()->errormsg || (sizeof($res) === 0))
        {
            $this->outputResult("can't get list of worlds");
        }

        foreach ($res as $k=>$v)
        {
            if(strcmp($world,$v['dbId'].'-'.$v['id_world']) === 0)
            {
                $str.= "<OPTION selected value='".
                $v['dbId'].'-'.$v['id_world'].
                "'>".$v['worldname']."</OPTION>";
            }
            else
            {
                $str.= "<OPTION value='".
                $v['dbId'].'-'.$v['id_world'].
                "'>".$v['worldname']."</OPTION>";
            }
        }

        $str.="</SELECT>";
        return $str;
    }

    function getAttribytesTypeSelect($type)
    {
        $str = "<SELECT name='attr_type'>";
        foreach ($this->types as $k => $v)
        {
            if(strcmp($type,$k) === 0)
            {
                $str .= "<OPTION value='".$k."' selected>".$v."</OPTION>";
            }
            else
            {
                $str .= "<OPTION value='".$k."'>".$v."</OPTION>";
            }
        }

        $str .= "</SELECT>";

        return $str;
    }

    function showRequestForm($type, $world)
    {
        //Выводим ссылку на помощь
        echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=EditGameAttributes><font size=4><b>HELP</b></font></a></p>';
        
        echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2 width='140'>
                            Attributes for:
                            </TD>
                            
                        </TR>
                        
                        <TR>
                            <TD bgcolor='#444E6C' class=maintxt width='140'>".
        $this->getAttribytesTypeSelect($type)
        ./*"
        </TD>
        <TD bgcolor='#444E6C' class=maintxt>".
        $this->getWorldsSelect($world)
        .*/"</TD>
                        </TR>
                        <TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt>
                                <input type='submit'  class='Submit' name='search' value='Get'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                </table>";

        return true;
    }

    function showRacesAttributes($id_race)
    {
        $sql = sprintf("SELECT
                                r.id_race,
                                ltl.text
                            FROM
                                ds_race r,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl
                            WHERE
                                r.id_localizedname = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d",                      
        constant('DEFAULT_LANG_ID')
        );

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of races");
            return false;
        }

        echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='race'>             
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                <SELECT name='id_race'>";           
        foreach($res as $row)
        {
            if(strcmp($id_race,$row['id_race']) === 0)
            {
                echo "<OPTION selected value='".$row['id_race']."'>".$row['text']."</OPTION>";
            }
            else
            {
                echo "<OPTION value='".$row['id_race']."'>".$row['text']."</OPTION>";
            }
        }
        echo "</SELECT>
                            </TD>
                        </TR>                       
                        <TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt>
                                <input type='submit'  class='Submit' name='search2' value='Get'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                </table>";

        if(isset($id_race) && $id_race > 0)
        {
            $sql = sprintf("SELECT
                                    raha.id_racehasattribute,
                                    ltl.text,
                                    raha.value,
                                    an.attributename,
                                    at.typename
                                FROM
                                    ds_race r,
                                    ds_racehasattribute raha,
                                    ds_attribute a,
                                    ds_attributename an,
                                    ds_attributetype at,
                                    ds_localizedtext lt,
                                    ds_localizedtextlanguage ltl
                                WHERE
                                    r.id_race = %d
                                    AND r.id_race = raha.id_race
                                    AND raha.id_attribute = a.id_attribute
                                    AND a.id_attributetype = at.id_attributetype
                                    AND a.id_attributename = an.id_attributename
                                    AND r.id_localizedname = lt.id_localizedtext
                                    AND lt.id_localizedtext = ltl.id_localizedtext
                                    AND ltl.id_language = %d",
            $id_race,
            constant('DEFAULT_LANG_ID'));

            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("error in getting attributes of race");
                return false;
            }

            if(sizeof($res) > 0)
            {
                echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='race'>             
                <INPUT type='hidden' name='id_race' value='".$id_race."'>                               
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2 colspan=4 align='center'>".
                $res[0]['text']
                ."</TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute type
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute name
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Value
                            </TD>                           
                        </TR>";                 
                foreach ($res as $k=>$v)
                {
                    echo "<TR>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['typename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['attributename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>
                                <INPUT type='text' name='idraha".
                    $v['id_racehasattribute'].
                    "' value='".round($v['value'], 8)."'>
                            </TD>                           
                        </TR>";                 
                }
                echo        "<TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt colspan='3'>
                                <input type='submit'  class='Submit' name='edit' value='Edit'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>             
                </table>";
            }
        }
    }

    function updateRacesAttributes($data)
    {
        if(sizeof($data) <= 0)
        {
            return false;
        }

        //Получаем имя рассы по ее id
        $raceName = $this->getRaceNameByID($data['id_race']);
        
        foreach ($data as $k=>$v)
        {
            if(preg_match('/^idraha[0-9]*$/',$k) > 0)
            {
                $id_racehasattribute = substr($k,6);

                assert((float)$id_racehasattribute > 0);

                $sql = sprintf("UPDATE
                                        ds_racehasattribute
                                    SET
                                        value = %f
                                    WHERE
                                        id_racehasattribute = %d",
                $v,
                $id_racehasattribute);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't change race attribute with id_racehasattribute".
                    $id_racehasattribute."<br>");
                }
                
                //Получаем имя атрибута по его id
                $attributeName = $this->getRaceAttributeNameByID($id_racehasattribute);
                
                //Получаем тип атрибута по его id
                $attributeType = $this->getRaceAttributeTipeByID($id_racehasattribute);
                
                $detail .= "Set value = $v<br>(Attribute type '$attributeType'<br>Attribute name '$attributeName')<br>";

            }
        }

        //Логируем
        actionLog("Edd attribute for race '$raceName'", $detail);
        
        return true;
    }

    function updateReligionAttributes($data)
    {
        if(sizeof($data) <= 0)
        {
            return false;
        }

        $detail = "";
        
        //Получаем название религии по id
        $religionName = $this->getReligionNameByID($data['id_religion']);
        
        foreach ($data as $k=>$v)
        {
            if(preg_match('/^idrha[0-9]*$/',$k) > 0)
            {
                $id_religionhasattribute = substr($k,5);

                assert((float)$id_religionhasattribute > 0);

                $sql = sprintf("UPDATE
                                        ds_religionhasattribute
                                    SET
                                        value = %f
                                    WHERE
                                        id_religionhasattribute = %d",
                $v,
                $id_religionhasattribute);

                $this->getDBConnection()->executeQuery($sql);
                if($this->getDBConnection()->errormsg)
                {
                    $this->outputResult("can't change religion attribute with id_religionhasattribute".
                    $id_religionhasattribute."<br>");
                }

                //Получаем имя атрибута по его id
                $attributeName = $this->getReligionAttributeNameByID($id_religionhasattribute);
                
                //Получаем тип атрибута по его id
                $attributeType = $this->getReligionAttributeTipeByID($id_religionhasattribute);
                
                
                $detail .= "Set value = $v<br>(Attribute type '$attributeType'<br>Attribute name '$attributeName')<br>";
            }
        }
        
        //Логируем
        actionLog("Edit attribute for religion '$religionName'", $detail);

        return true;
    }

    function showReligionAttributes($id_religion)
    {
        $sql = sprintf("SELECT
                                r.id_religion,
                                ltl.text
                            FROM
                                ds_religion r,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl
                            WHERE
                                r.id_localizedname = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d",                      
        constant('DEFAULT_LANG_ID')
        );

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("error in getting list of religions");
            return false;
        }

        echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='religion'>             
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                <SELECT name='id_religion'>";           
        foreach($res as $row)
        {
            if(strcmp($id_religion,$row['id_religion']) === 0)
            {
                echo "<OPTION selected value='".$row['id_religion']."'>".$row['text']."</OPTION>";
            }
            else
            {
                echo "<OPTION value='".$row['id_religion']."'>".$row['text']."</OPTION>";
            }
        }
        echo "</SELECT>
                            </TD>
                        </TR>                       
                        <TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt>
                                <input type='submit'  class='Submit' name='search2' value='Get'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>
                </table>";

        if(isset($id_religion) && $id_religion > 0)
        {
            $sql = sprintf("SELECT
                                    rha.id_religionhasattribute,
                                    rha.value,
                                    an.attributename,
                                    at.typename,
                                    ltl.text
                                FROM
                                    ds_religion r,
                                    ds_religionhasattribute rha,
                                    ds_attribute a,
                                    ds_attributename an,
                                    ds_attributetype at,
                                    ds_localizedtext lt,
                                    ds_localizedtextlanguage ltl
                                WHERE
                                    r.id_religion = %d
                                    AND r.id_religion = rha.id_religion
                                    AND rha.id_attribute = a.id_attribute
                                    AND a.id_attributename = an.id_attributename
                                    AND a.id_attributetype = at.id_attributetype
                                    AND r.id_localizedname = lt.id_localizedtext
                                    AND lt.id_localizedtext = ltl.id_localizedtext
                                    AND ltl.id_language =%d",
            $id_religion,
            constant('DEFAULT_LANG_ID'));

            $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
            if($this->getDBConnection()->errormsg)
            {
                $this->outputResult("error in getting attributes of religion");
                return false;
            }

            if(sizeof($res) > 0)
            {
                echo "<br>
                <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <form method='post' action='".$_SERVER["PHP_SELF"]."'>
                <INPUT type='hidden' name='attr_type' value='religion'>             
                <INPUT type='hidden' name='id_religion' value='".$id_religion."'>                               
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <TR>
                            <TD bgcolor='444E6C' class=th_2 colspan=4 align='center'>".
                $res[0]['text']
                ."</TD>
                        </TR>   
                        <TR>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute type
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Attribute name
                            </TD>
                            <TD bgcolor='444E6C' class=th_2>
                                Value
                            </TD>                           
                        </TR>";                 
                foreach ($res as $k=>$v)
                {
                    echo "<TR>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['typename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>".
                    $v['attributename']
                    ."</TD>
                            <TD bgcolor='444E6C' class=maintxt>
                                <INPUT type='text' name='idrha".
                    $v['id_religionhasattribute'].
                    "' value='".round($v['value'], 8)."'>
                            </TD>                           
                        </TR>";                 
                }
                echo        "<TR>
                            <TD bgcolor='#444E6C' align='right' class=maintxt colspan='3'>
                                <input type='submit'  class='Submit' name='edit' value='Edit'>
                            </TD>
                        </TR>
                        </TABLE>
                    </td>
                </tr>
                </form>             
                </table>";
            }
        }


        return true;
    }

    function getAttributeNameByid_artefacthasbonus($id_artefacthasbonus)
    {
        $sql = sprintf("SELECT l.text
                        FROM ds_localizedtextlanguage as l
                        WHERE
                            l.id_language = 3
                            AND
                            l.id_localizedtext = (
                                        SELECT a.id_localizedname
                                        FROM ds_artefact as a 
                                        WHERE a.id_artefact = (
                                                    SELECT ab.id_artefact
                                                    FROM ds_artefacthasbonus as ab
                                                    WHERE ab.id_artefacthasbonus = %d
                                                                )
                                                    )",
                    $id_artefacthasbonus);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get artefact name by id_artefacthasbonus");
            return false;
        }

        return $res[0]['text'];
    }
    
    function getBuildingNameByIDAndRace($building)
    {
        $temp = explode("/", $building);
        $id_building = $temp[0];
        $id_race = $temp[1];
    
        $sql = sprintf("SELECT l.text
                        FROM
                        ds_localizedtextlanguage as l
                        WHERE
                        l.id_language = %d
                        AND
                        l.id_localizedtext = (
                                    SELECT
                                        b.id_localizedname
                                    FROM
                                        ds_buildingcanbuildbyrace as b
                                    WHERE
                                        b.id_building = %d
                                        AND
                                        b.id_race = %d
                                                )",
                    constant('DEFAULT_LANG_ID'),$id_building, $id_race);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get building name by id_building and id_race");
            return false;
        }

        return $res[0]['text'];
    }
    
    function getBuilldingAttributeNameByID($id_buildinghasattribute)
    {
        //Получаем название аттрибута по его id
        $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                    SELECT a.id_attributename
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                    SELECT b.id_attribute
                                                                    FROM ds_buildinghasattribute as b
                                                                    WHERE b.id_buildinghasattribute = %s
                                                                            )
                                                                )",
        $id_buildinghasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute name by id_buildinghasattribute");
            return false;
        }
        
        return $res[0]['attributename'];
    }
    
    function getBuilldingAttributeTipeByID($id_buildinghasattribute)
    {
        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                    SELECT a.id_attributetype
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                SELECT b.id_attribute
                                                                    FROM ds_buildinghasattribute as b
                                                                    WHERE b.id_buildinghasattribute = %s
                                                                            )
                                                            )",
        $id_buildinghasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute type by id_buildinghasattribute");
            return false;
        }

        return  $res[0]['typename'];
    }
    
    function getAttributeNameByIdBuildingattribute($id_attribute)
    {
        //Получаем название аттрибута по его id
        $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                    SELECT a.id_attributename
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute =  %s
                                                                )",
        $id_attribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute name by id_buildinghasattribute");
            return false;
        }
        
        return $res[0]['attributename'];
    }
    
    function getAttributeTypeByIdBuildingattribute($id_attribute)
    {
        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                    SELECT a.id_attributetype
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = %s
                                                            )",
        $id_attribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute type by id_buildinghasattribute");
            return false;
        }

        return  $res[0]['typename'];
    }
    
    function getReligionNameByID($id_religion)
    {
        $sql = sprintf("SELECT l.text
                            FROM
                            ds_localizedtextlanguage as l
                            WHERE
                                l.id_language = %d
                                AND
                                l.id_localizedtext = (
                                            SELECT r.id_localizedname
                                            FROM ds_religion as r
                                            WHERE r.id_religion = %d
                                                    )",
        constant('DEFAULT_LANG_ID'), $id_religion);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get religion name by id_religion");
            return false;
        }

        return  $res[0]['text'];
    }
    
        function getReligionAttributeNameByID($id_religionhasattribute)
    {
        //Получаем название аттрибута по его id
        $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                    SELECT a.id_attributename
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                    SELECT r.id_attribute
                                                                    FROM ds_religionhasattribute as r
                                                                    WHERE r.id_religionhasattribute = %s
                                                                            )
                                                                )",
        $id_religionhasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute name by id_buildinghasattribute");
            return false;
        }
        
        return $res[0]['attributename'];
    }
    
    function getReligionAttributeTipeByID($id_religionhasattribute)
    {
        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                    SELECT a.id_attributetype
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                SELECT r.id_attribute
                                                                FROM ds_religionhasattribute as r
                                                                WHERE r.id_religionhasattribute = %s
                                                                            )
                                                            )",
        $id_religionhasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute type by id_buildinghasattribute");
            return false;
        }

        return  $res[0]['typename'];
    }
    
    function getRaceNameByID($id_race)
    {
        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT l.text
                        FROM
                            ds_localizedtextlanguage as l
                            WHERE
                                l.id_language = %d
                                AND
                                l.id_localizedtext = (
                                            SELECT r.id_localizedname
                                            FROM ds_race as r
                                            WHERE r.id_race = %d
                                            )",
        constant('DEFAULT_LANG_ID'), $id_race);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get race name by id");
            return false;
        }

        return  $res[0]['text'];
    }
    
    function getRaceAttributeNameByID($id_racehasattribute)
    {
        //Получаем название аттрибута по его id
        $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                    SELECT a.id_attributename
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                    SELECT r.id_attribute
                                                                    FROM ds_racehasattribute as r
                                                                    WHERE r.id_racehasattribute = %s
                                                                            )
                                                                )",
        $id_racehasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute name by id_buildinghasattribute");
            return false;
        }
        
        return $res[0]['attributename'];
    }
    
    function getRaceAttributeTipeByID($id_racehasattribute)
    {
        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                    SELECT a.id_attributetype
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                SELECT r.id_attribute
                                                                FROM ds_racehasattribute as r
                                                                WHERE r.id_racehasattribute = %s
                                                                            )
                                                            )",
        $id_racehasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute type by id_buildinghasattribute");
            return false;
        }

        return  $res[0]['typename'];
    }
    
    function getTerraformingAttributeNameBy($id_terraforminghasattribute)
    {
        //Получаем название аттрибута по его id
        $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                    SELECT a.id_attributename
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                    SELECT t.id_attribute
                                                                    FROM ds_terraforminghasattribute as t
                                                                    WHERE t.id_terraforminghasattribute = %s
                                                                            )
                                                                )",
        $id_terraforminghasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute name by id_terraforminghasattribute");
            return false;
        }
        
        return $res[0]['attributename'];
    }
    
    function getTerraformingAttributeTypeById($id_terraforminghasattribute)
    {
        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                    SELECT a.id_attributetype
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = (
                                                                SELECT t.id_attribute
                                                                    FROM ds_terraforminghasattribute as t
                                                                    WHERE t.id_terraforminghasattribute = %s
                                                                            )
                                                            )",
        $id_terraforminghasattribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get attribute type by id_terraforminghasattribute");
            return false;
        }

        return  $res[0]['typename'];
    }
    
        function getTFAttributeNameById($id_attribute)
    {
        //Получаем название аттрибута по его id
        $sql = sprintf("SELECT an.attributename
                                FROM ds_attributename as an
                                WHERE an.id_attributename = (
                                                    SELECT a.id_attributename
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute =  %s
                                                                )",
        $id_attribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get TF attribute name by id_buildinghasattribute");
            return false;
        }
        
        return $res[0]['attributename'];
    }
    
    function getTFAttributeTypeById($id_attribute)
    {
        //Получаем тип аттрибута по его id
        $sql = sprintf("SELECT at.typename
                                FROM ds_attributetype as at
                                WHERE at.id_attributetype= (
                                                    SELECT a.id_attributetype
                                                    FROM ds_attribute as a
                                                    WHERE a.id_attribute = %s
                                                            )",
        $id_attribute);

        $res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
        if($this->getDBConnection()->errormsg)
        {
            $this->outputResult("Can't get TF attribute type by id_buildinghasattribute");
            return false;
        }

        return  $res[0]['typename'];
    }
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>