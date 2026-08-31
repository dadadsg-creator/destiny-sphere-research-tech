<?php

class TradeLogOut
{
    static public function outLogSearch($worlds, $world, $scopes, $catecories, $catecoriesfor, $recources, $money, $races, $classes, $artscopes, $sizes)
    {
        assert(is_array($worlds));
        
        //Переменные для менюшки разницы курсов
        //$signs = array('<' => '&lt', '=' => '=', '>' => '&gt');
        //$condition = array('or' => 'OR', 'and' => 'AND');
        $QuantityOfRows = array(30, 50, 70, 100, All);
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

        $out = '<h1>Trade Logs</h1>';

        $out .= '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TradeLog><font size=3><b>HELP</b></font></a></p>';

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

        $out .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
        $out .= '<input type="hidden" name="action" value="trade_log">';
        $out .= '<input type="hidden" name="offset" value="0">';

        $out .= "
        <table border=0>
            <tr>
                <td valign=top>
                    <font color=#F8F8FF><b>World:</b></font>
                </td>
                <td valign=top>
                    <select name='world'>";
        
        foreach($worlds as $k => $world)
        {
            if($world['dbid'].'_'.$world['id_world'] == $_SESSION['world'])
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }
            $out .= '<option value='.$world['dbid'].'_'.$world['id_world'].' '.$s.'>'.$world['worldname'];
        }
            
        $out .= '
                    </select>       
                </td>
                <td>
                    <font color=#F8F8FF><b>Date from:</b></font>
                </td>
                <td>
                    '.self::GetTimeMenu($_SESSION['from_y'], $_SESSION['from_m'], $_SESSION['from_d'], 'from').'
                </td>
                <td>
                    <font color=#F8F8FF><b>Cource difference: </b></font>
                </td>   
                <td>
                    <table border=0>
                        <tr> 
                            <td>
                            ';
        if (key_exists('lt', $_SESSION))
        {
            $ltVal = $_SESSION['lt'];
        } else {
            $ltVal = '';
        }

        if (key_exists('gt', $_SESSION))
        {
            $gtVal = $_SESSION['gt'];
        } else {
            $gtVal = '';
        }

        $signs = array('<' => '&lt', '=' => '=', '>' => '&gt');
        $condition = array('or' => 'OR', 'and' => 'AND');

        $out .= '<select name="firstSign">';
        foreach ($signs as $key => $val)
        {
            if($key == $_SESSION['firstSign'])
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }
            $out .= "<option value='$key' $s>$val";
        }
        $out .= '</select>&nbsp;&nbsp;';

        $out .= '<input type=text size="6" name="lt" value='.$ltVal.'><font color=#F8F8FF> % </font>
        ';

        $out .= '<select name="condition">';
        foreach ($condition as $key => $val)
        {
            if($key == $_SESSION['condition'])
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }
            $out .= "<option value='$key' $s>$val";
        }
        $out .= '</select>&nbsp;&nbsp;';

        $out .= '<select name="secondSign">';
        foreach ($signs as $key => $val)
        {
            if($key == $_SESSION['secondSign'])
            {
                $s = ' selected=1';
            } else {
                $s = '';
            }
            $out .= "<option value='$key' $s>$val";
        }
        $out .= '</select>&nbsp;&nbsp;
        <input type=text size="6" name="gt" value='.$gtVal.'><font color=#F8F8FF> % </font>
                            </tr>
                        </td>
                    </table>
                </td>
                <td>
                    
                </td>
            </tr>
            <tr>
                <td>
                    <font color=#F8F8FF><b>User name:</b></font>
                </td>
                <td>
                    <font color=#F8F8FF><input type="text" name="user" size="24" value="'.$_SESSION['user'].'"></font>
                </td>
                <td>
                    <font color=#F8F8FF><b>Date to:</b></font>
                </td>
                <td>
                    '.self::GetTimeMenu($_SESSION['to_y'], $_SESSION['to_m'], $_SESSION['to_d'], 'to').'
                </td>
                <td>
                    <font color=#F8F8FF><b>Quantity of rows on page:</b></font>
                </td>
                <td>
                    <select name=limit>';
                    foreach ($QuantityOfRows as $k => $v)
                    {
                        if($v == $_SESSION['limit'])
                        {
                            $s = ' selected=1';
                        } else {
                            $s = '';
                        }
                        $out .= '<option value="'.$v.'" '.$s.'>'.$v.'</option>';
                    }
        $out .= '
                    </select>
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

        if (key_exists('showSameIp', $_SESSION) && $_SESSION['showSameIp'] == 1) {
            $c = ' checked ';
        } else {
            $c = '';
        }
        
        if (key_exists('AuctionOnly', $_SESSION) && $_SESSION['AuctionOnly'] == 1) {
            $ca = ' checked ';
        } else {
            $ca = '';
        }
        
        if (!key_exists('ipAddress', $_SESSION)) {
            $_SESSION['ipAddress'] = '';
        }
        $out .= '</td>
            </tr>
        </table>
        <input type="checkbox" name="AuctionOnly" value="1" '.$ca.'> <font color=#F8F8FF><i>Show Only Auction</i></font><br>
        <input type="checkbox" name="showSameIp" value="1" '.$c.'> <font color=#F8F8FF><i>Show trade with same IP</i></font><br>
        <font color=#F8F8FF><i>IP:</i></font> <input type="text" name="ipAddress" value='.$_SESSION['ipAddress'].'><br><br>
        <input type="submit"  class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;">
        </form>';

        return $out;
    }

    static public function outTradeLog($tradelog, $pageCount, $limit)
    {
        $out = '';
        $count = 0;
        $countSell =0;
        $countSellFor =0;
        $countTax =0;
        $countCource =0;
/*echo "<pre><br>".print_r($scopes)."<br>
<font color=white>"; 
print_r($tradelog);
exit;*/
        $out .= '
            <table>
                <tr>
                    <td class=maintxt>
                        <b>Additional field:</b>
                    </td>
                </tr>
                <tr>
                    <td class=maintxt>
                        <a href="#" onclick="return  displayElementsByName(\'Ip\')">Ip</a>
                    </td>
                </tr>
                <tr>
                    <td class=maintxt>
                        <a href="#" onclick="return  displayElementsByName(\'CourseRecount\')">Course recount</a>
                    </td>
                </tr>
                <tr>
                    <td class=maintxt>
                        <a href="#" onclick="return  displayElementsByName(\'Tax\')">Tax</a>
                    </td>
                </tr>
                <tr>
                    <td class=maintxt>
                        <a href="#" onclick="return  displayElementsByName(\'Scope\')">Scope</a>
                    </td>
                </tr>
                <tr>
                    <td class=maintxt>
                        <a href="#" onclick="return  displayElementsByName(\'Courses\')">Counted and Real courses</a>
                    </td>
                </tr>
                <tr>
                    <td class=maintxt>
                        <a href="#" onclick="return  displayElementsByName(\'Auction\')">Auction</a>
                    </td>
                </tr>
                <tr>
                    <td class=maintxt>
                        &nbsp
                    </td>
                </tr>
            </table>
                        ';  



        $out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <tr>
                    <td>
                        <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";    

        $out .= "
            <tr>
                <td bgcolor=#2A3E66 align=center>
                    <b><font color=#F8F8FF>Start date</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center>
                    <b><font color=#F8F8FF>End date</font></b></td>
                <td bgcolor=#2A3E66 align=center><b><font color=#F8F8FF>Customer</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center>
                    <b><font color=#F8F8FF>Customer comb</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center>
                    <b><font color=#F8F8FF>Seller</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center>
                    <b><font color=#F8F8FF>Seller comb</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center width=10%>
                    <b><font color=#F8F8FF>Ammount sell</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center width=10%>
                    <b><font color=#F8F8FF>Sell</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center>
                    <b><font color=#F8F8FF>Amount sell for</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center>
                    <b><font color=#F8F8FF>Sell for</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='Tax' style='display: none;'>
                    <b><font color=#F8F8FF>Tax</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='Scope' style='display: none;'>
                    <b><font color=#F8F8FF>Scope</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='Courses' style='display: none;'>
                <b><font color=#F8F8FF >Counted course</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='Courses' style='display: none;'>
                    <b><font color=#F8F8FF>Real course</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='Courses' style='display: none;'>
                    <b><font color=#F8F8FF>Course difference</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='CourseRecount' style='display: none;'>
                    <b><font color=#F8F8FF>Course recount</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='Ip' style='display: none;'>
                    <b><font color=#F8F8FF>Ip Seller</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center id='Ip' style='display: none;'>
                    <b><font color=#F8F8FF>Ip Customer</font></b>
                </td>
                <td bgcolor=#2A3E66 align=center align=center id='Auction' style='display: none;'>
                    <b><font color=#F8F8FF>Auction</font></b>
                </td>
            </tr>";

        $out .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">
                <input type="hidden" name="action" value="edit_recount">';

        //Считаем сумму всех числовых полей
        foreach($tradelog as $k => $trade)
        {
            $countSell += $trade['from_ammount'];
            $countSellFor += $trade['to_ammount'];
            $countTax += $trade['tax_summ'];
            $countRealCource += ($trade['cource']);
            $countCource += ($trade['to_ammount']/$trade['from_ammount']);
            $count ++;
        }

        //Выводим сумму всех числовых полей
        $out .= "<tr>";

        $out .= "<td bgcolor=#444E6C>
                    <font color=#F8F8FF><b>Sum</b></font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF><b>".number_format($countSell, 0, ',', ' ')."</b></font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF><b>&nbsp;</b>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF><b>".number_format($countSellFor, 0, ',', ' ')."</b></font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF><b>&nbsp;</b><br>
                <td bgcolor=#444E6C id='Tax' style='display: none;'>
                    <font color=#F8F8FF><b>".number_format($countTax, 0, ',', ' ')."</b></font>
                </td>
                <td bgcolor=#444E6C id='Scope' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C id='Courses' style='display: none;'>
                    <font color=#F8F8FF><b>".number_format($countCource, 5, ',', ' ')."</b></font>
                </td>
                <td bgcolor=#444E6C id='Courses' style='display: none;'>
                    <font color=#F8F8FF><b>".number_format($countRealCource, 5, ',', ' ')."</b></font>
                </td>
                <td bgcolor=#444E6C id='Courses' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C align='center' id='CourseRecount' style='display: none;'>
                    <font color=#F8F8FF>
                    <input type='submit'  class='Submit' value='Edit'></font>
                </td>
                <td bgcolor=#444E6C id='Ip' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C id='Ip' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td bgcolor=#444E6C align=center id='Auction' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
        </tr>";

        //Выводим данные таблицы
        foreach($tradelog as $k => $trade)
        {
            $out .= "
            <tr>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['start_date']."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['end_date']."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['customer']."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['comb_to']."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['seller']."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['comb_from']."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".number_format($trade['from_ammount'], 0, ',', ' ')."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['goods_from']."<br></font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".number_format($trade['to_ammount'], 0, ',', ' ')."</font>
                </td>
                <td bgcolor=#444E6C>
                    <font color=#F8F8FF>".$trade['goods_to']."</font>
                </td>
                <td bgcolor=#444E6C id='Tax' style='display: none;'>
                    <font color=#F8F8FF>".number_format($trade['tax_summ'], 0, ',', ' ')."</font>
                </td>
                <td bgcolor=#444E6C id='Scope' style='display: none;'>
                    <font color=#F8F8FF>".$trade['tradescopename']."</font>
                </td>
                <td bgcolor=#444E6C id='Courses' style='display: none;'>
                    <font color=#F8F8FF>".number_format($trade['to_ammount']/$trade['from_ammount'], 5, ',', ' ')."</font>
                </td>
                <td bgcolor=#444E6C id='Courses' style='display: none;'>
                    <font color=#F8F8FF>".number_format($trade['cource'], 5, ',', ' ')."</font>
                </td>
                <td bgcolor=#444E6C id='Courses' style='display: none;'>
                    <font color=#F8F8FF>".number_format($trade['course_dif'], 2, ',', ' ')."%</font>
                </td>";

            if($trade['course_recount'] == 't')
            {
                $c = ' checked ';
            } else {
                $c = '';
            }

            $out .= "<input type=hidden name=courseRecount[".$trade['id_trade']."][id] value=t>
                <td bgcolor=#444E6C align=center id='CourseRecount' style='display: none;'>
                    <font color=#F8F8FF><input type=checkbox name=courseRecount[".$trade['id_trade']."][state] value=t $c></font>
                </td>
                <td bgcolor=#444E6C id='Ip' style='display: none;'>
                    <font color=#F8F8FF>".$trade['from_ip']."</font>
                </td>
                <td bgcolor=#444E6C id='Ip' style='display: none;'>
                    <font color=#F8F8FF>".$trade['to_ip']."</font>
                </td>";
    
            //Аукцион или нет
            if ($trade['is_auction'] == 't') {
                $out .= "
                    <td bgcolor=#444E6C align=center align=center id='Auction' style='display: none;'>
                        <font color=#F8F8FF><a target='_blank' href='?action=show_bid&idTrade=".$trade['id_trade']."'>Show bit!</a></font>
                    </td>";
            } else {
                $out .= "
                    <td bgcolor=#444E6C align=center align=center id='Auction' style='display: none;'>
                        <font color=#F8F8FF>-</font>
                    </td>";
            }

            $out .= "</tr>";
        }

        //Выводим сумму всех числовых полей
        $out .= "<tr bgcolor=#2A3E66 align=center>";

        $out .= "<td>
                    <font color=#F8F8FF><b>Sum</b></font>
                </td>
                <td>
                    <font color=#F8F8FF>-</font>
                </td>
                <td>
                    <font color=#F8F8FF>-</font>
                </td>
                <td>
                    <font color=#F8F8FF>-</font>
                </td>
                <td>
                    <font color=#F8F8FF>-</font>
                </td>
                <td>
                    <font color=#F8F8FF>-</font>
                </td>
                <td>
                    <font color=#F8F8FF><b>".number_format($countSell, 0, ',', ' ')."</b></font>
                </td>
                <td>
                    <font color=#F8F8FF><b>&nbsp;</b>
                </td>
                <td>
                    <font color=#F8F8FF><b>".number_format($countSellFor, 0, ',', ' ')."</b></font>
                </td>
                <td>
                    <font color=#F8F8FF><b>&nbsp;</b><br>
                <td id='Tax' style='display: none;'>
                    <font color=#F8F8FF><b>".number_format($countTax, 0, ',', ' ')."</b></font>
                </td>
                <td id='Scope' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td id='Courses' style='display: none;'>
                    <font color=#F8F8FF><b>".number_format($countCource, 5, ',', ' ')."</b></font>
                </td>
                <td id='Courses' style='display: none;'>
                    <font color=#F8F8FF><b>".number_format($countRealCource, 5, ',', ' ')."</b></font>
                </td>
                <td id='Courses' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td align='center' id='CourseRecount' style='display: none;'>
                    <font color=#F8F8FF>
                    <input type='submit'  class='Submit' value='Edit'></font>
                </td>
                <td  id='Ip' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td id='Ip' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
                <td align=center id='Auction' style='display: none;'>
                    <font color=#F8F8FF>-</font>
                </td>
        </tr>";
        
        //Выводим средний курс
        if ($countRealCource > $countCource) {
            $dif = -(100*($countRealCource/$countCource));
        } elseif ($countRealCource == $countCource)
        {
            $dif = 0;
        }
        else {
            $dif = 100*($countCource/$countRealCource);
        }

        /*$out .= "<tr bgcolor=#4F456E align=center>";

        $out .= "<td colspan=12 align=right><font color=#F8F8FF><b>Average Course</b></font></td>";
        $out .= "<td><font color=#F8F8FF><b>".number_format($countCource/$count, 5, ',', ' ')."</b></font></td>";
        $out .= "<td><font color=#F8F8FF><b>".number_format($countRealCource/$count, 5, ',', ' ')."</b></font></td>";
        $out .= "<td><font color=#F8F8FF><b><nobr>".number_format($dif, 2, ',', ' ')."%</nobr></b></font></td>";
        //$out .= "<td><font color=#F8F8FF>&nbsp;</font></td>";
        $out .= "<td colspan=4 align=right>&nbsp;</td>";
        $out .= "</tr>";*/
        $out .= "</table>";

        $out .= "</tr>";
        $out .= "</table>";

        //Выводим меню выбора сраниц
        if($pageCount > 1)
        {
            $out .= "<table border=0 bgcolor=#444E6C><tr><td>";
            for($i=0; $i<$pageCount; $i++)
            {
                if ($_SESSION['page'] == $i)
                {
                    $p = ($i+1);
                    $out .= "<font size=3><b>[$p]</b></font>&nbsp;&nbsp;&nbsp;";
                }
                else {
                    $offset = $i*$limit;
                    $p = ($i+1);
                    $out .= "<a href=".$_SERVER['PHP_SELF']."?action=trade_log&offset=$offset><font size=3><b>$p</b></font></a>&nbsp;&nbsp;&nbsp;";
                }

                if ((($i+1)%34) == 0)
                {
                    $out .= "</td></tr><tr><td>";
                }else {
                    $out .= "</td><td>";
                }
            }
            $out .= "</td></tr></table>";
        }

        $out .= "<hr>";

        //Выводим кнопочку экспорта в csv
        $out .= "<table bgcolor=#444E6C border=0 cellspacing=6 cellpadding=1>";
        $out .= "<tr><td valign='top'>";

        $out .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
        $out .= '<input type="hidden" name="action" value="import_csv">';

        $out .= '<input type="submit" class="Submit" value=" Export in csv "></form>';

        $out .= "</td><td valign='top'>";

        $scvFile = constant("GAME_HOST")."/ds/utils/files/TradeLog.csv";
        $out .= '<a title="Save" href="'.$scvFile.'"><font size=4><b>Last saved csv file</b></font></a><br><br>';

        $out .= "</td></tr></table>";

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

    function outUnitRaces($races)
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

    function outUnitClasses($classes)
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

    function outArtScoupes($artscopes)
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
    
    function outArtSize($sizes)
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
    
    static public function outLogBid($bidLog)
    {
        $out = '';
        
        //Выводим заголовки таблицы
        $out .= "
            <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <tr>
                    <td>
                        <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";    

        $out .= "
            <tr>
                <td bgcolor=#2A3E66 align=center><b><font color=#F8F8FF>Bid Date</font></b></td>
                <td bgcolor=#2A3E66 align=center><b><font color=#F8F8FF>User Name</font></b></td>
                <td bgcolor=#2A3E66 align=center><b><font color=#F8F8FF>Ip</font></b></td>
                <td bgcolor=#2A3E66 align=center><b><font color=#F8F8FF>Summ</font></b></td>
                <td bgcolor=#2A3E66 align=center><b><font color=#F8F8FF>Cancel Date</font></b></td>
                <td bgcolor=#2A3E66 align=center><b><font color=#F8F8FF>Cancel Ip</font></b></td>
            </tr>";
        
        foreach ($bidLog as $key => $data)
        {
            $out .= "<tr>";

            $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>".$data['bid_date']."</font></td>";
            $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>".$data['username']."</font></td>";

            $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>".$data['ip']."</font></td>";
            $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>".$data['summ']."</font></td>";

            if(!empty($data['cancel_date']))
            {
                $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>".$data['cancel_date']."</font></td>";
                $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>".$data['cancel_ip']."</font></td>";
            } else {
                $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>-</font></td>";
                $out .= "<td bgcolor=#444E6C><font color=#F8F8FF>-</font></td>";
            }
            $out .= "</tr>";
        }
        
        $out .= "
                        </table>
                    </td>
                </tr>
            </table>";
        
        return $out;
                
    }
}

?>