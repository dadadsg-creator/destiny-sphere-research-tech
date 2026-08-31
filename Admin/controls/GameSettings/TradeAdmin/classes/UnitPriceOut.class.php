<?php

class UnitPriceOut
{
    static public function outUnitSearch($worlds, $filters, $selected, $worldID)
    {
        assert(is_array($worlds));
        assert(is_array($filters));
        assert(is_array($selected));

        $out = '<h1>Edit Unit Course</h1>';
        
        $out .= '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=UnitPrice><font size=3><b>HELP</b></font></a></p>';

        $out .= '<form action="" method="POST">';
        $out .= '<input type="hidden" name="action" value="unit_search">';

        $out .= '<b><font color=#F8F8FF>World: </font></b><select name="world">';
        foreach($worlds as $k => $world)
        {
            if($world['dbid'].'_'.$world['id_world'] == $selected['world'])
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }

            $out .= '<option value='.$world['dbid'].'_'.$world['id_world'].' '.$s.'>'.$world['worldname'];
        }
        $out .= '</select>&nbsp;';

        $out .= '<input type="hidden" name="action" value="unit_list">';

        foreach ($filters as $name => $filterData)
        {
            assert(key_exists($name, $selected));
            assert(is_array($filterData));
            $out .= '<font color=#F8F8FF><b>'.$name.'</b>: </font><select name='.$name.'>';
            foreach($filterData as $k => $filter)
            {
                if($filter['id'] == $selected[$name])
                {
                    $s = ' selected=1';
                } else {
                    $s = '';
                }
                $out .= '<option value='.$filter['id'].$s.'>'.$filter['name'];
            }
            if ($selected[$name] == 'all')
            {
                $out .= '<option value=all selected>ALL';
            } else
            {
                $out .= '<option value=all>ALL';
            }
            $out .= '</select>&nbsp;&nbsp;';
        }

        $out .= '<input type="submit" class="Submit" value="select"></form>';

        return $out;
    }

    static public function outUnitList($units, $lottery, $title, $rithTitle, $worldID, $race, $class, $coursesInNano)
    {
        $out = '<form action="" method="POST">';
        $out .= '<input type="submit" class="Submit" value="Change courses">';
        $out .= '<br>';
        $out .= '<br>';

        //$out .= '<table border=1>';
            
        $out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <tr>
                    <td>
                        <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>\n";

        $rowLength = 1;

        //-------------- Формирование шапки таблицы ----------------------
        //$out .= "<tr>
        //      <th class=th_3 rowspan=2><font color=#F8F8FF>&nbsp;Name</font></th>";
        //<th align=center rowspan=2>power</th>";

        $out .= "<tr>
                <th class=th_2>&nbsp</th>";
        
        foreach($title as $k=>$v)
        {
            $out .= "<th class=th_3 colspan=".count($v['TYPES'])."><font color=#F8F8FF>".$v['CATEGORY_NAME']."</font></th>";
            $rowLength += count($v['TYPES']);
        }
        $out .= "<th class=th_3 colspan=3>ЛОТЕРЕЯ</th>";
        $out .= "<th class=th_3>&nbsp</th>";
        $out .="</tr>\n";

        $out .="<tr>\n";
        $out .= "<th class=th_2>Name</th>";
        
        foreach($title as $k1=>$v1)
        {
            foreach($v1['TYPES'] as $k2=>$v2)
            {
                switch ($v2['TYPE_NAME'])
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
                    case 'SILVER_CREDIT':
                        $tit = 'SILVER';
                        break;
                    case 'GOLD_CREDIT':
                        $tit = 'GOLD';
                        break;
                }
                $out .= "<th class=th_3>".$tit."</th>";
            }
        }
        $out .= "<th class=th_3>КОЛИЧЕСТВО</th>";
        $out .= "<th class=th_3>ВЕРОЯТНОСТЬ</th>";
        $out .= "<th class=th_3>АКТИВНО</th>";
        $out .= "<th class=th_2>History</th>";
        $out .= "</tr>\n";

        //------------- Формирование данных таблицы -----------------------------

        $keyValue = 0;
        $key = 0;
        $currace = '';
/*echo "<pre>";
print_r($units);
exit;*/
        // Смотрим, как у нас считаются курсы
        $recountInNano = DE_WorldSettings::CreateForCurentWorld()->get('RECOUNT_COURSES_IN_NANO');
        if ($recountInNano == 1) {
            foreach ($units as $kk => $unitsCourses)
            {
                $newUnits[$unitsCourses['name']][$unitsCourses['trade_artyicle_name']] = $unitsCourses;
            }
        }

        foreach ($rithTitle as $keyUnit => $unitList)
        {
            $out .= '<input type="hidden" name="action" value="edit">';
            $out .= '<input type="hidden" name="race" value="'.$race.'">';
            $out .= '<input type="hidden" name="class" value="'.$class.'">';
            $out .= '<input type="hidden" name="world" value="'.$worldID.'">';
            //Смотрим, изменилась ли расса
            if ($currace != $unitList['race'])
            {
                if ($unitList['race'] == '')
                {
                    $out .= "<tr><td bgcolor='#2A3E66' align='center'><font color=#F8F8FF><b>No race</b></font></td>";
                    $out .= "<td bgcolor='#2A3E66' colspan='11'></td></tr>";
                }else
                {
                    $out .= "<tr><td bgcolor='#2A3E66' align='center'><font color=#F8F8FF><b>".$unitList['race']."</b></font></td>";
                    $out .= "<td bgcolor='#2A3E66' colspan='11'></td></tr>";
                }
                $currace = $unitList['race'];
            }
            $out .= '<tr><td bgcolor="#2A3E66"><font color=#F8F8FF>&nbsp;'.$unitList['name'].'&nbsp;</font></td>';
            $curUnit = $unitList['name'];

            foreach($title as $k1=>$v1)
            {
                foreach($v1['TYPES'] as $k2=>$v2)
                {
/*echo "<pre>";
print_r($newUnits);
exit;*/
                    // Если все курсы выражаются через нану
                    if ($recountInNano == 1) 
                    {
                        if (($v1['CATEGORY_ID'] == 1 && $v2['TYPE_ID'] == 2) ||
                        ($v1['CATEGORY_ID'] == 4 && $v2['TYPE_ID'] == 1) ||
                        $v1['CATEGORY_ID'] == 4 && $v2['TYPE_ID'] == 2) {

                            $timestamp = strtotime($units[$key]['add_date']);
                            $data = date('d-m-Y G:i:s', $timestamp);

                            $out .= '<td bgcolor="#F8F8FF" align="center">';
                            $out .= "<span style=font-size:10pt>".$data."</span><br>";
                            $out .= '<input style="text-align: right;" type=text name="units['.$newUnits[$curUnit]['NANO_CRYSTAL']['id'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 value="'.number_format($newUnits[$curUnit]['NANO_CRYSTAL']['value'], '5', '.', '').'"></td>';
                            
                        } else {
                            $recCourse = $units[$key]['id']/$v2['TYPE_ID'];
                            //$out .= = $newUnits[$curUnit]['NANO_CRYSTAL']['value']."/".$coursesInNano[$v2['TYPE_ID']];
                            $out .= '<td bgcolor="#EEE9E9" align="center">'.$newUnits[$curUnit]['NANO_CRYSTAL']['value']/$coursesInNano[$v2['TYPE_ID']].'</td>';
                        }
                        

                    } else {
                        if ($unitList['name'] ==  $units[$key]['name'] AND $units[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
                        {
                            $timestamp = strtotime($units[$key]['add_date']);
                            $data = date('d-m-Y G:i:s', $timestamp);

                            $out .= '<td bgcolor="#F8F8FF" align="center">';
                            $out .= "<span style=font-size:10pt>".$data."</span><br>";
                            $out .= '<input style="text-align: right;" type=text name="units['.$units[$key]['id'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 value="'.number_format($units[$key]['value'], '5', '.', '').'"></td>';
                            $key ++;
                        }
                        else
                        {
                            $out .= '<td bgcolor="#EEE9E9" align="center"><input style="text-align: right;" type=text name="units['.$unitList['id_unit'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 ></td>';
                        }
                    }
                }
            }
            $out .= '<td bgcolor="#EEE9E9" align="center"><input style="text-align: right;" name="num['.$unitList['id_unit'].']" value="'.$lottery[$unitList['id_unit']]['num'].'" size=11></td>';
            $out .= '<td bgcolor="#EEE9E9" align="center"><input style="text-align: right;" name="prob['.$unitList['id_unit'].']" value="'.$lottery[$unitList['id_unit']]['prob'].'" size=11></td>';
            $out .= '<td bgcolor="#EEE9E9" align="center"><input type="checkbox" name="active['.$unitList['id_unit'].']"'.($lottery[$unitList['id_unit']]['active']=='t'?' checked':'').'></td>';
            $out .= '<td bgcolor="#2A3E66">&nbsp;&nbsp;<a target="_blank" href="?world='.$worldID;
            $out .= '&unit='.$units[$keyValue]['id'];
            $out .= '&action=history"><font color=#CFCFCF>history</font></a>&nbsp;&nbsp;</td></tr>';
            $keyValue = $key;
        }

        //$out .= '</table>';
        $out .= "</table>
                    </td>
                </tr>
            </table>";

        $out .= '<br>';
        $out .= '<input type="submit" class="Submit" value="Change courses"></form>';
        
        $scvFile = constant("GAME_HOST")."/ds/utils/files/UnitPrace.csv";
        
        $out .= '<table>
                    <tr>
                        <td valign="top">
                            <form action="" method="POST">
                            <input type="hidden" name="race" value="'.$race.'">
                            <input type="hidden" name="class" value="'.$class.'">
                            <input type="hidden" name="world" value="'.$worldID.'">
                            <input type="hidden" name="action" value="export_csv">
                            <input type="submit" class="Submit" value="Export CSV">
                            </form>
                        </td>
                        <td valign="top">
                            <a title="Save" href="'.$scvFile.'"><font size=4><b>Last saved csv file</b></font></a>
                        </td>
                    </tr>
                </table>';
        
        $out .= '<table border = 1 cellpadding="6">
                    <tr>
                        <td>
                            <form action="" method="POST" enctype="multipart/form-data">
                            <font size=4 color=#F8F8FF>Import from csv file:<br></font><br>
                            <input type="hidden" name="race" value="'.$race.'">
                            <input type="hidden" name="class" value="'.$class.'">
                            <input type="hidden" name="world" value="'.$worldID.'">
                            <input type="file" size="20" name="csv_file"><br>
                            <input type="hidden" name="action" value="import_csv"><br>
                            <input type="submit" class="Submit" value="Import CSV">
                            &nbsp;<a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=UnitPrice><font size=3><b>HELP</b></font></a>
                            </form>
                        </td>
                    </tr>
                </table>';
        
    
        return $out;
    }

    static public function outHistory($title, $history)
    {
        assert(is_array($title));
        assert(is_array($history));

        $out = "<h2><font color=#F8F8FF>History of Unit Course</font></h2>";

        $out .= "<table border=1>
                <tr>
                <th><font color=#F8F8FF>&nbsp;DATE&nbsp;</font></th>";
        foreach ($title as $k => $data)
        {
            $out .= "<th><font color=#F8F8FF>".$data['trade_name']."</font></th>";
        }
        $out .= '</tr>';
        /*
        define("CHARTS_SOURCE", "../phpcharts/");
        include(CHARTS_SOURCE."chartclasses.php");
        $objChart = new Chart(800, 200, "chart_art_history");
        */

        $key = 0;
        $curdata = $history[$key]['add_date'];

        while ($key < count($history))
        {
            $out .= "<tr align='center'>
                <td><b><font color=#F8F8FF>".$history[$key]['add_date']."</font></b></td>";
            foreach ($title as $k => $data)
            {
                if ($history[$key]['trade_name'] == $data['trade_name'] AND $history[$key]['add_date'] == $curdata)
                {
                    if($history[$key]['is_current'] == 't')
                    {
                        $bo = '<font color="#FF34B3"><b>';
                        $bc = '</b></font>';
                    } else {
                        $bo = '';
                        $bc = '';
                    }
                    $out .= "<td bgcolor='$color'><font color=#F8F8FF>".$bo.$history[$key]['value'].$bc."</font></td>";
                    $key ++;
                    $curdata = $history[$key]['add_date'];
                }else {
                    $out .= "<td>-</td>";
                }

            }
        }
        $out .= "</tr>";
        $out .= '</table>';

        /*
        $objChart->CreateChartImage(LINE_CHART);
        $out .= '<br><img src="chart_art_history.png"><br>';
        */
        $out .= '<a href="javascript:window.close()">close window</a>';

        $out .= "<br><br><font color='#FF34B3'>Current date is allocated by color</font>";

        return $out;
    }

    static public function outBackButton()
    {
        $out = '<form action="" method="POST">';
        $out .= '<input type="hidden" name="action" value="index">';
        $out .= '<input type="submit" class="Submit" value="Back to choose world">';
        $out .= '</form>';

        return $out;
    }
}

?>