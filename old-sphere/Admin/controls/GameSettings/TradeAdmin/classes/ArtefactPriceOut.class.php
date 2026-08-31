<?php

class ArtefactPriceOut
{
    static public function outArtSearch($worlds, $filters, $selected)
    {
        assert(is_array($worlds));
        assert(is_array($filters));
        assert(is_array($selected));

        $out = '<h1>Edit Artefact Course</h1>';
        
        $out .= '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ArtefactPrice><font size=4><b>HELP</b></font></a></p>';
        
        $out .= '<form action="" method="POST">';
        $out .= '<input type="hidden" name="action" value="art_search">';

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

        $out .= '<input type="hidden" name="action" value="art_list">';

        foreach ($filters as $name => $filterData)
        {
            assert(key_exists($name, $selected));
            assert(is_array($filterData));
            $out .= '<b><font color=#F8F8FF> '.$name.':</font></b> <select name='.$name.'>';
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

        $out .= '<input type="submit" class="Submit" value="Select"></form>';

        return $out;
    }

    static public function outArtefactList($title, $rithTitle, $artefacts, $lottery, $worldID, $scope, $size)
    {
        $out = '<form action="" method="POST">';
        $out .= '<input type="submit" class="Submit" value="Change courses">';
        $out .= '<br>';
        $out .= '<br>';

        $out .= '<table border=1>';

        $rowLength = 1;

        //-------------- ‘ÓÏËÓ‚‡ÌËÂ ¯‡ÔÍË Ú‡·ÎËˆ˚ ----------------------
        $out .= "<tr>
                <th rowspan=2><font color=#FFFFFF>&nbsp;name</font></th>
                <th align=center rowspan=2><font color=#FFFFFF>power</font></th>";

        foreach($title as $k=>$v)
        {
            $out .= "<th colspan=".count($v['TYPES'])."><font color=#FFFFFF>".$v['CATEGORY_NAME']."</font></th>";
            $rowLength += count($v['TYPES']);
        }
        $out .= "<th colspan=2><font color=#FFFFFF>ÀŒ“≈–≈ﬂ</font></th>";
        $out .= "<th align=center rowspan=2><font color=#FFFFFF>history</font></th>";
        $out .="</tr>\n";

        $out .="<tr>\n";
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
                $out .= "<th><font color=#FFFFFF>".$tit."</font></th>";
            }
        }
        $out .= "<th><font color=#FFFFFF>¬≈–Œﬂ“ÕŒ—“‹</font></th>";
        $out .= "<th><font color=#FFFFFF>¿ “»¬ÕŒ</font></th>";

        $out .= "</tr>\n";

        //------------- ‘ÓÏËÓ‚‡ÌËÂ ‰‡ÌÌ˚ı Ú‡·ÎËˆ˚ -----------------------------

        $keyValue = 0;
        $key = 0;

        foreach ($rithTitle as $keyart => $artList)
        {
            $out .= '<input type="hidden" name="action" value="edit">';
            $out .= '<input type="hidden" name="scope" value="'.$scope.'">';
            $out .= '<input type="hidden" name="size" value="'.$size.'">';
            $out .= '<input type="hidden" name="world" value="'.$worldID.'">';
            $out .= '<tr><td><font color=#FFFFFF>&nbsp;'.$artList['name'].'&nbsp;</font></td>';
            //echo "key=$key, artList['name']={$artList['name']}, artefacts[key]['name']=   {$artefacts[$key]['name']}, power={$artefacts[$key]['power']}<br>";
            if ($artList['name'] ==  $artefacts[$key]['name'])
            {
                //die('n='.number_format($artefacts[$key]['power'], '', '.', ''));
       //number_format($artefacts[$key]['power'], '', '.', '')
        $nn = $artefacts[$key]['power'];
                $out .= '<td><input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'][power]"  size=11 value="'.$nn.'">'.$n.'</td>';
            }else
            {
                $out .= '<td><input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'][power]"  size=11></td>';
            }

            foreach($title as $k1=>$v1)
            {
                foreach($v1['TYPES'] as $k2=>$v2)
                {
                    if ($artList['name'] ==  $artefacts[$key]['name'] AND $artefacts[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
                    {
                        $timestamp = strtotime($artefacts[$key]['add_date']);
                        $data = date('d-m-Y G:i:s', $timestamp);

                        $out .= '<td bgcolor="#F8F8FF" align="center">';
                        $out .= "<span style=font-size:10pt>".$data."</span><br>";
                        $out .= '<input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 value="'.number_format($artefacts[$key]['course'], '5', '.', '').'"></td>';
                        $key ++;
                    }
                    else
                    {
                        $out .= '<td bgcolor="#EEE9E9" align="center"><input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 ></td>';
                    }
                }
            }

            $out .= '<td bgcolor="#EEE9E9" align="center"><input style="text-align: right;" name="prob['.$artList['id_artefact'].']" value="'.$lottery[$artList['id_artefact']]['prob'].'" size=11></td>';
            $out .= '<td bgcolor="#EEE9E9" align="center"><input type="checkbox" name="active['.$artList['id_artefact'].']"'.($lottery[$artList['id_artefact']]['active']=='t'?' checked':'').'></td>';
            $out .= '<td>&nbsp;&nbsp;<a target="_blank" href="?world='.$worldID;
            $out .= '&artefact='.$artList['id_artefact'];
            $out .= '&action=history">history</a>&nbsp;&nbsp;</td></tr>';
            $keyValue = $key;
        }

        $out .= "</form>";
        
        
        $out .="<tr>";
        $out .= "<td bgcolor='#B0C4DE' colspan=2 align=center><font color=#00008B><b>EDIT ALL COLUMN</b></font></td>";

        foreach($title as $k1=>$v1)
        {
            foreach($v1['TYPES'] as $k2=>$v2)
            {
                    $out .= "<form action='".$_SERVER['PHP_SELF']."' method='POST'>\n";
                    $out .= '<input type="hidden" name="scope" value="'.$scope.'">';
                    $out .= '<input type="hidden" name="size" value="'.$size.'">';
                    $out .= '<input type="hidden" name="world" value="'.$worldID.'">';
                    $out .= "<td  align=center bgcolor='#B0C4DE'><font color=#00008B>";
                    $out .= "<br>";
                    $out .= "<input type=text style='text-align: right;' size=11 name=course[".$v1['CATEGORY_ID']."_".$v2['TYPE_ID']."]><br>";
                    $out .= "<input type=hidden name=action value=edit_column>\n";
                    $out .= "<br><input type='submit' class='Submit' value='Edit Column'>";
                    $out .= "</form>";
                    $out .= "</font></td>\n";
            }
        }
            
        $out .= "<td>&nbsp;</td>";
        $out .="</tr>";
        $out .= '</table>';

            
        $scvFile = constant("GAME_HOST")."/ds/utils/files/ArtefactPrace.csv";
        
        $out .= '<table>
                    <tr>
                        <td valign="top">
                            <form action="" method="POST">
                            <input type="hidden" name="scope" value="'.$scope.'">
                            <input type="hidden" name="size" value="'.$size.'">
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
                            <input type="hidden" name="scope" value="'.$scope.'">
                            <input type="hidden" name="size" value="'.$size.'">
                            <input type="hidden" name="world" value="'.$worldID.'">
                            <input type="file" size="20" name="csv_file"><br>
                            <input type="hidden" name="action" value="import_csv"><br>
                            <input type="submit" class="Submit" value="Import CSV">
                            &nbsp;<a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ArtefactPrice><font size=3><b>HELP</b></font></a>
                            </form>
                        </td>
                    </tr>
                </table>';

        return $out;
    }

    static public function outArtefactEdit($artefact)
    {
        $out = '';

        return $out;
    }

    static public function outHistory($title, $history)
    {
        assert(is_array($title));
        assert(is_array($history));

        $out = "<h2><font color=#F8F8FF>History of Artefact Course</font></h2>";

        $out .= "<h2><font color=#B0C4DE>".$history[0]['artefact_name']."</font></h2>";

        if (count($history) == 0)
        {
            $out .= '<h2><font color=#B0C4DE>No History on this artefact</font></h2>';
        }
        else {

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
        }
        /*
        $objChart->CreateChartImage(LINE_CHART);
        $out .= '<br><img src="chart_art_history.png"><br>';
        */
        $out .= '<a href="javascript:window.close()">close window</a>';

        $out .= "<br><br><font color='#FF34B3'>Current date is allocated by bold</font>";

        return $out;
    }
}

?>