<?php
class TradeStatisticOut
{
    static public function outLogSearch(                $worlds, 
                                                        $world, 
                                                        $scopes, 
                                                        $catecories, 
                                                        $catecoriesfor,
                                                        $recources, 
                                                        $money, 
                                                        $races, 
                                                        $classes, 
                                                        $artscopes, 
                                                        $sizes)
    {
        assert(is_array($worlds));

        //$QuantityOfRows = array(30, 50, 70, 100, All);
        $week = strtotime("-1 week");
        $nowY = date(Y);
        $nowM = date(n);
        $nowD = date(j);

        if ($_SESSION['from_y'] == '')
        {
            $_SESSION['from_y'] = date('Y', $week);
        }

        if ($_SESSION['from_m'] == '')
        {
            $_SESSION['from_m'] = date('m', $week);
        }

        if ($_SESSION['from_d'] == '')
        {
            $_SESSION['from_d'] = date('d', $week);
        }

        if ($_SESSION['to_y'] == '')
        {
            $_SESSION['to_y'] = $nowY;
        }

        if ($_SESSION['to_m'] == '')
        {
            $_SESSION['to_m'] = $nowM;
        }

        if ($_SESSION['to_d'] == '')
        {
            $_SESSION['to_d'] = $nowD;
        }
                
        $out = '<h1>Trade Statistic</h1>';
                
        $out .= '<script language="JavaScript">
                function display(elem) {
                if (document.getElementById(elem).style.display != "") { 
                    document.getElementById(elem).style.display = "";                    
                }
                else {
                    document.getElementById(elem).style.display = "none";
                }
                return false;
                }
                
                function displayElementsByName(elem) {
                if (document.getElementById(elem).style.display != "") { 
                    var elements = document.getElementsByName(elem);
                    for (i=0; i < elements.length; i++)
                    {
                        elements[i].style.display = "";
                    }
                }
                else {
                    var elements = document.getElementsByName(elem);
                    for (i=0; i < elements.length; i++)
                    {
                        elements[i].style.display = "none";
                    }
                }
                return false;
                }
                
                </script>';

        $out .= '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TradeStatistic><font size=3><b>HELP</b></font></a></p>';
        
        $out .= '
            <form action="" method="POST">
            <input type="hidden" name="action" value="trade_log">';
        
        $out .= '
            <table border=0>
                <tr>
                    <td valign=top">
                        <b><font color=#F8F8FF>World:</font></b>
                    </td>
                    <td valign=top">
                        <select name="world">';
                        foreach($worlds as $k => $world)
                        {
                            if($world['dbid'].'_'.$world['id_world'] == $_SESSION['world'])
                            {
                                $s = ' selected=1';
                            } else {
                                $s = '';
                            }
                            $out .= '<option value='.$world['dbid'].'_'.$world['id_world'].'_'.$world['worldname'].' '.$s.'>'.$world['worldname'];
                        }
        
        $out .= '
                    </select>
                    </td>
                    <td valign=top>
                        <font color=#F8F8FF><b>Date from:</b></font>
                    </td>
                    <td valign=top>
                        '.self::GetTimeMenu($_SESSION['from_y'], $_SESSION['from_m'], $_SESSION['from_d'], 'from').'
                    </td>
                </tr>
                <tr>
                    <td valign=top">
                        <b><font color=#F8F8FF>User name:</font></b>
                    </td>
                    <td valign=top">
                        <input type="text" name="user" size="24" value="'.$_SESSION['user'].'">
                    </td>
                    <td valign=top>
                        <font color=#F8F8FF><b>Date to:</b></font>
                    </td>
                    <td valign=top>
                        '.self::GetTimeMenu($_SESSION['to_y'], $_SESSION['to_m'], $_SESSION['to_d'], 'to').'
                    </td>
                </tr>
            </table>';
        
        $out .= "
        <table border=0>
            <tr>
                <td valign=top>
                    <b><font color=#F8F8FF>Scopes:</font></b><br>";

        foreach($scopes as $k => $scope)
        {
            if($_SESSION['scopes'] != '' && in_array($scope['id_tradescope'], $_SESSION['scopes']))
            {
                $c = ' checked ';
            } elseif (!key_exists('scopes',$_SESSION) || $_SESSION['scopes'] == '') {
                $c = ' checked ';
            } else {
                $c = '';
            }
                
            $out .= '<input type=checkbox name="scopes[]" value='.$scope['id_tradescope'].' '.$c.'><font color=#F8F8FF> '.$scope['scopename'].'</font>';
            $out .= '<br>';
        }
        $out .= "
                </td>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                <td valign=top>
                    <b><font color=#F8F8FF>Sell:</font></b><br>";

        //Проходимся по каждой категории
        foreach($catecories as $k => $cat)
        {
            if ($_SESSION['catecories'] != '' && !in_array($cat['id_tradecategory'], $_SESSION['catecories']))
            {
                $c = '';
            } else {
                $c = ' checked ';
            }

            $out .= '<table border=0>
                        <tr>
                            <td valign="buttom">
                                <input type=checkbox name="catecories[]" value='.$cat['id_tradecategory'].' '.$c.'><font color=#F8F8FF> '.$cat['categoryname'].'</font>
                            </td>';

            $out .="<td valign='buttom'>";
            
            switch ($cat['categoryname'])
            {
                case 'RESOURCE':
                    $out .= '
                            <input type="button" class="Submit" onclick="return  display(\'hide_block3\')" value="detail"><br>
                        </td>
                    </tr>
                    <tr id="hide_block3" style="display:none;">
                        <td>'.self::OutSelect($recources, 'recources');
                    break;
                    
                case 'UNIT':
                    //Выводим рассы юнитов
                    $out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="button" class="Submit" onclick="return  display(\'hide_block4\')" value="race">
                                <div id="hide_block4" style="display: none;">
                                '.self::outUnitRaces($races).'
                                </div>
                        </td>
                        <td valign="buttom">
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="button" class="Submit" onclick="return  display(\'hide_block5\')" value="class">';
    
                    //Выводим классы юнитов
                    $out .= '<div id="hide_block5" style="display: none;">
                            '.self::outUnitClasses($classes).'
                            </div>';
    
                    $out .="
                        </td>
                    </tr>
                    <tr>
                        <td colspan='3'>";
                    //Поле ввода имени юнита
                    if (key_exists('unitName', $_SESSION))
                    {
                        $currentUnitName = $_SESSION['unitName'];
                    } else {
                        $currentUnitName = '';
                    }
    
                    $out .= '<font color=#F8F8FF>Unit Name: </font><input type="text" name="unitName" value="'.$currentUnitName.'">';
                    break;
                    
                case 'ARTEFACT':
                    $out .= '<input type="button" class="Submit" onclick="return  display(\'hide_block6\')" value="scope">';
    
                    //Выводим скоупы артефактов
                    $out .= '<div id="hide_block6" style="display: none;">
                            '.self::outArtScoupes($artscopes).'
                            </div>
                        </td>
                        <td valign="buttom" align="left">
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="button" class="Submit" onclick="return  display(\'hide_block7\')" value="size">';
    
                    //Выводим размер артефактов
                    $out .= '<div id="hide_block7" style="display: none;">
                            '.self::outArtSize($sizes).'
                            </div>
                        </td>
                    </tr><tr><td colspan="3">';
    
                    //Поле ввода имени артефакта
                    if (key_exists('artName', $_SESSION))
                    {
                        $currentArtName = $_SESSION['artName'];
                    } else {
                        $currentArtName = '';
                    }
                    $out .= '<font color=#F8F8FF>Art Name:&nbsp;&nbsp;</font><input type="text" name="artName" value="'.$currentArtName.'">';
                    break;
            
            }
            $out .= "
                        </td>
                    </tr>
                </table>";
        }
        $out .= "</td>
                <td>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                </td>
                <td valign=top> 
                    <b><font color=#F8F8FF>Sell for:</font></b><br>";

        //Проходимся по каждой категори, за которую продаем
        foreach($catecoriesfor as $k => $cat)
        {
            if ($_SESSION['catecoriesfor'] != '' && !in_array($cat['id_tradecategory'], $_SESSION['catecoriesfor']))
            {
                $c = '';
            } else {
                $c = ' checked ';
            }

            $out .= '
            <table border=0>
                <tr>
                    <td>
                    <input type=checkbox name="catecoriesfor[]" value='.$cat['id_tradecategory'].' '.$c.'><font color=#F8F8FF> '.$cat['categoryname'].'</font>
                    </td>
                    <td>';
            
            switch ($cat['categoryname'])
            {
                case 'RESOURCE':
                    $out .= '
                            <input type="button" class="Submit" onclick="return  display(\'hide_block1\')" value="detail"><br>
                            </td>
                        </tr>
                    </table>
                    <div id="hide_block1" style="display: none;">
                    '.self::OutSelect($recources, 'recourcesfor').'
                    </div>';
                    break;
                    
                case 'MONEY':
                    $out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="button" id="btn2" class="Submit" onclick="return  display(\'hide_block2\')" value="detail">
                            </td>
                        </tr>
                    </table>
                    <div id="hide_block2" style="display: none;">
                    '.self::OutSelect($money, 'money').'
                    </div>';
                    break;
            }       
        }
        
        $out .= '</td>
            </tr>
        </table>';

        $out .= '<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"></form>';

        return $out;
    }

    static public function outStat($tradeStat)
    {
/*echo "<pre>";
print_r($tradeStat);*/
        if (empty($tradeStat))
        {
            $out = "<h2><font color=red>No data</font></h2>";
            return $out;
        }

        $title = array(KADERIUM, NANO_CRYSTAL, STASIS_ENERGY, FOOD, PRODIUM, SILVER_CREDIT, GOLD_CREDIT);

        $out = "<table width=100% border=1 style=\"font-size: 10pt\" cellpadding=2 bgcolor='#F0FFFF' bordercolor='#191970'>";

        $out .= "<tr>
                <th rowspan=3>NAME</th>";

        foreach ($title as $key => $val)
        {
            $out .= "<th colspan=6>$val</th>";
        }

        $out .= "<th rowspan=3>Recount Cource</th>";
        $out .= "</tr>";
        $out .= "<tr>";

        foreach ($title as $key => $val)
        {
            $out .= "<th rowspan=2>ammount<br>from</th>";
            $out .= "<th rowspan=2>ammount<br>to</th>";
            $out .= "<th rowspan=2>Lots count</th>";
            $out .= "<th>cource</th>";
            $out .= "<th rowspan=2>%</th>";
            $out .= "<th rowspan=2>Show Current</th>";
            
        }
        
        $out .= "
                </tr>
                <tr>";

        foreach ($title as $key => $val)
        {
        
            $out .= "
                        <th>current cource</th>
                    ";
        }

        $out .= '</tr>
            <form action="" method="POST" target="_blank">
            <input type="hidden" name="action" value="recount">';

        $count = 0;

        //Чтобы вдруг не зациклилось
        $i = 0;

        while ($count <= (count($tradeStat)-1) AND $i <= 200)
        {
            if (($i % 2) == 0)
            {
                $bgcolor = '#F8F8FF';
            }
            else {
                $bgcolor = '#F0FFFF';
            }

            $out .= "<tr bgcolor='$bgcolor'>";
            $out .= '<td align="center" rowspan=2>'.$tradeStat[$count]['goods_from'].'</td>';
            $curentGoodsFrom = $tradeStat[$count]['goods_from'];
            
            $out .= '
            <input type="hidden" name="cource['.$i.'][id_from_article]" value="'.$tradeStat[$count]['id_from_article'].'">
            <input type="hidden" name="cource['.$i.'][id_from_category]" value="'.$tradeStat[$count]['id_from_category'].'">
            <input type="hidden" name="cource['.$i.'][goods_from]" value="'.$tradeStat[$count]['goods_from'].'">
            ';

            $currentCources = "";
            
            foreach($title as $key=>$val)
            {
                if ($tradeStat[$count]['goods_to'] == $val and $tradeStat[$count]['goods_from'] == $curentGoodsFrom)
                {
                    $out .= '<td align="center" rowspan=2>';
                    $out .= number_format($tradeStat[$count]['ammounf_from'], 0, ',', ' ');
                    $out .= '</td>';
                    $out .= '<td rowspan=2 align="center">';
                    $out .= number_format($tradeStat[$count]['ammount_to'], 0, ',', ' ');
                    $out .= '</td>';
                    $out .= '<td rowspan=2 align="center">';
                    $out .= number_format($tradeStat[$count]['trade_count'], 0, ',', ' ');
                    $out .= '</td>';
                    $out .= '<td align="center">';
                    $out .= number_format($tradeStat[$count]['cource'], 4, ',', ' ');
                    $out .= '</td>';
                    
                    $out .= '<td align="center" rowspan=2>';
                    
                    if ($tradeStat[$count]['cur_cource'] < $tradeStat[$count]['cource']) {
                        $out .= '<img src="/Admin/img/up.gif" border="0" title="'.number_format($tradeStat[$count]['cource_dif'], 1, ',', ' ')." %".'"></img>';
                    } elseif ($tradeStat[$count]['cource'] < $tradeStat[$count]['cur_cource']){
                        $out .= '<img src="/Admin/img/down.gif" border="0" title="'.number_format($tradeStat[$count]['cource_dif'], 1, ',', ' ')." %".'"></img>';
                    } else {
                        $out .= '&nbsp;';
                    }
                    $out .= '</td>';
                    
                    $out .= '
                    <td align="center" rowspan=2> 
                        <input type="checkbox" name="cource['.$i.'][cources]['.$tradeStat[$count]['goods_to'].'][showCurrent]" style="border:0">
                    </td>';

                    //Считаем общее количество
                    $sum[$key]['ammounf_from'] += $tradeStat[$count]['ammounf_from'];
                    $sum[$key]['ammount_to'] += $tradeStat[$count]['ammount_to'];
                    $sum[$key]['trade_count'] += $tradeStat[$count]['trade_count'];
                    
                    $out .= '<input type="hidden" name="cource['.$i.'][cources]['.$tradeStat[$count]['goods_to'].'][cur_cource]" value="'.$tradeStat[$count]['cur_cource'].'">
                            <input type="hidden" name="cource['.$i.'][cources]['.$tradeStat[$count]['goods_to'].'][counted_cource]" value="'.$tradeStat[$count]['cource'].'">';

                    $currentCources .= '
                        <td align="center"><div title="'.$tradeStat[$count]['update_time'].'">
                            '.number_format($tradeStat[$count]['cur_cource'], 4, ',', ' ').'
                        </div></td>';
                    
                    $count ++;
                }
                else
                {
                    $out .= '<td align="center" rowspan=2>-</td>';
                    $out .= '<td align="center" rowspan=2>-</td>';
                    $out .= '<td align="center" rowspan=2>-</td>';
                    $out .= '<td align="center" >-</td>';
                    $out .= '<td align="center" rowspan=2>-</td>';
                    $out .= '<td align="center" rowspan=2>-</td>';
                    
                    $currentCources .= '<td align="center">-</td>';
                }
                
            }

            
            $out .= '<td align="center" rowspan=2>      
            <input type="checkbox" name="cource['.$i.'][recount]">          
            ';
            $out .= "</tr>";
            
            $out .= "
            <tr bgcolor=".$bgcolor." >
                ".$currentCources."
            </tr>";
            
            
            
            $i ++;
        }
        
        //Выводим общую сумму
        $out .= "
            </tr>
            <tr>
                <td>
                    <b>Sum</b>
                </td>";

        foreach($title as $key=>$val)
        {
            if (key_exists($key, $sum))
            {
                    $out .= '<td align="center"><b>'.number_format($sum[$key]['ammounf_from'], 0, ',', ' ').'</b></td>';    
                    $out .= '<td align="center"><b>'.number_format($sum[$key]['ammount_to'], 0, ',', ' ').'</b></td>';
                    $out .= '<td align="center"><b>'.number_format($sum[$key]['trade_count'], 0, ',', ' ').'</b></td>';
                    $out .= '<td align="center">-</td>';
                    $out .= '<td align="center">-</td>';
                    $out .= '<td align="center">-</td>';
                    
                }
                else
                {
                    $out .= '<td align="center">-</td>';
                    $out .= '<td align="center">-</td>';
                    $out .= '<td align="center">-</td>';
                    $out .= '<td align="center">-</td>';
                    $out .= '<td align="center">-</td>';
                    $out .= '<td align="center">-</td>';
                }
        }
        $out .= '<td align="center">-</td>
                            </tr>';
    
        $out .= '</table>
        <br>
        <input type="submit" class="Submit" value="Recount">
        </form>';

        return $out;
    }
    
    
    static public function OutSelect($recources, $recname)
    {
        if ($_SESSION[$recname] != '')
        {
            foreach($recources as $k => $res)
            {
                if(in_array($res['id'], $_SESSION[$recname]))
                {
                    $c = ' checked ';
                } else {
                    $c = '';
                }
                $out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="'.$recname.'[]" value='.$res['id'].' '.$c.'><font color=#F8F8FF> '.$res['name'].'</font>';
                $out .= '<br>';
            }
        }
        else {
            foreach($recources as $k => $res)
            {
                $out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="'.$recname.'[]" value='.$res['id'].' checked><font color=#F8F8FF> '.$res['name'].'</font>';
                $out .= '<br>';
            }
        }

        return $out;
    }

    public static function outUnitRaces($races)
    {
        $out = "";

        foreach ($races as $key => $data)
        {
            if ($_SESSION['race'] != '')
            {
                if(in_array($data['id'], $_SESSION['race']))
                {
                    $c = ' checked ';
                } else {
                    $c = '';
                }
            } else {
                $c = ' checked ';
            }
            $out .= '<input type=checkbox name="race[]" value='.$data['id'].' '.$c.'><font color=#F8F8FF> '.$data['name'].'</font>';
            $out .= '<br>';
        }

        return $out;
    }

    public static function outUnitClasses($classes)
    {
        $out = "";

        foreach ($classes as $key => $data)
        {
            if ($_SESSION['class'] != '')
            {
                if(in_array($data['id'], $_SESSION['class']))
                {
                    $c = ' checked ';
                } else {
                    $c = '';
                }
            } else {
                $c = ' checked ';
            }
            $out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="class[]" value='.$data['id'].' '.$c.'><font color=#F8F8FF> '.$data['name'].'</font>';
            $out .= '<br>';

        }
        return $out;
    }
    
    public static function outArtScoupes($artscopes)
    {
        $out = "";

        foreach ($artscopes as $key => $data)
        {
            if ($_SESSION['artscope'] != '')
            {
                if(in_array($data['id'], $_SESSION['artscope']))
                {
                    $c = ' checked ';
                } else {
                    $c = '';
                }
            } else {
                $c = ' checked ';
            }
            $out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="artscope[]" value='.$data['id'].' '.$c.'><font color=#F8F8FF> '.$data['name'].'</font>';
            $out .= '<br>';

        }
        return $out;
    }
    
    public static function outArtSize($sizes)
    {
        $out = "";

        foreach ($sizes as $key => $data)
        {
            if ($_SESSION['size'] != '')
            {
                if(in_array($data['id'], $_SESSION['size']))
                {
                    $c = ' checked ';
                } else {
                    $c = '';
                }
            } else {
                $c = ' checked ';
            }
            $out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="size[]" value='.$data['id'].' '.$c.'><font color=#F8F8FF> '.$data['name'].'</font>';
            $out .= '<br>';

        }
        return $out;
    }
    
    
    function outRecount($worlds, $cources)
    {
        $title = array(KADERIUM, NANO_CRYSTAL, STASIS_ENERGY, FOOD, PRODIUM, SILVER_CREDIT, GOLD_CREDIT);

        $out = '<h1>Change Cources</h1>';
        $out .= '<font color=#F8F8FF>WORLD: <b>'.$_SESSION['world_name'].'</b></font><br><br>';


        $out .= "<table width=100% border=1 style=\"font-size: 10pt\" cellpadding=2 bgcolor='#F0FFFF' bordercolor='#191970'>
                <tr>
                    <th>NAME</th>";

        $out .= '<form action="" method="POST">
            <input type="hidden" name="action" value="exec_recount">
            ';

        foreach ($title as $key => $val)
        {
            $out .= "
                    <th>$val</th>";
        }

        $out .= "
                </tr>";


        foreach ($cources as $courceKey => $data)
        {
            if (!key_exists('recount', $data)) {
                continue;
            }
            
            $out .= "
                <tr>
                    <td rowspan=2>
                        ".$data['goods_from']."
                    </td>
                ";

            $out .= '
            <input type="hidden" name="cource['.$courceKey.'][id_from_article]" value="'.$data['id_from_article'].'">
            <input type="hidden" name="cource['.$courceKey.'][id_from_category]" value="'.$data['id_from_category'].'">
            <input type="hidden" name="cource['.$courceKey.'][goods_from]" value="'.$data['goods_from'].'">
            ';

            $helpRow = '';
            
            foreach ($title as $key => $val)
            {
                // Если у нас указано, что данные курсы надо пересчитать
                if (key_exists($val, $data['cources'])) {
                    //Смотрим, какой курс нам надо выводить, текущий или нет
                    if (key_exists('showCurrent', $data['cources'][$val])) {
                        $cource = number_format($data['cources'][$val]['cur_cource'], 6, '.', '');
                        $helpCource = number_format($data['cources'][$val]['counted_cource'], 6, '.', '');
                    } else {
                        $cource = number_format($data['cources'][$val]['counted_cource'], 6, '.', '');
                        $helpCource = number_format($data['cources'][$val]['cur_cource'], 6, '.', '')." (current course)";
                    }
                } else {
                    $cource = '';
                    $helpCource = '';
                }
                    
                $out .= '
                    <td>
                        <input type="text" name="cource['.$courceKey.'][cources]['.$val.']" value="'.$cource.'">
                    </td>';
                    
                    
                $helpRow .= 
                    '<td>
                        &nbsp;'.$helpCource.'
                    </td>';
            }

            $out .= "
                </tr>
                <tr>
                    ".$helpRow."
                </tr>
                ";
        }
        $out .= "   </table>";

        $out .= '
        <br>
        <input type="submit" class="Submit" value="Recount">
        </form>';

        return $out;
    }
    
    function OutOk($cources)
    {
        $out = '<h1>Change Cources</h1>';
        $out .= '<font color=#F8F8FF>WORLD: <b>'.$_SESSION['world_name'].'</b></font><br><br>';
        
        $out .= "<font color=#F8F8FF>Have been established following cources</b></font><br><br>";
        
        foreach ($cources as $courceKey => $data)
        {
            $title = array(KADERIUM, NANO_CRYSTAL, STASIS_ENERGY, FOOD, PRODIUM, SILVER_CREDIT, GOLD_CREDIT);

            $out .= "<table border=0>
                ";

            foreach ($title as $key => $val)
            {
                if (key_exists($val, $data['cources']) && !empty($data['cources'][$val]))
                {
                    $out .= '
                <tr>
                    <td>
                         <font color=#F8F8FF><b>['.$data['goods_from'].']</b> for ['.$val.']:</font><br>
                    </td>
                    <td>
                        <font color=#F8F8FF>'.$data['cources'][$val].'</font><br>
                    </td>
                    <td>
                        &nbsp;
                    </td>
                </tr>
                ';
                }
            }
        }

        $out .= "</table>";

        return $out;
    }
    
    static function GetTimeMenu($year, $month, $day, $direction)
    {
        assert($year > 0);
        assert(0 < $month && $month < 13);
        assert(0 < $day && $day < 32);
        assert(!empty($direction));
        
        $out = '<select name="'.$direction.'_y">';
        
        //Выводим год
        for($i=date('Y')-2; $i<date('Y')+1; $i++)
        {
            if($i == $year)
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }
            $out .= '<option value='.$i.$s.'>'.$i;
        }       
        $out .= '</select><font color=#F8F8FF> - </font>';
        
        //Выводим месяц
        $out .= '<select name="'.$direction.'_m">';
        for($i=1; $i<13; $i++)
        {
            if($i == $month)
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }
            $out .= '<option value='.$i.$s.'>'.$i;
        }
        $out .= '</select><font color=#F8F8FF> - </font>';
        
        //Выводим число
        $out .= '<select name="'.$direction.'_d">';
        for($i=1; $i<32; $i++)
        {
            if($i == $_SESSION[''.$direction.'_d'])
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }
            $out .= '<option value='.$i.$s.'>'.$i;
        }
        $out .= '</select>';
        
        return $out;
    }
    
}

?>
