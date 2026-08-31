<?php

class RegStatisticOut
{
    static public function OutIndex(array $worlds, $type, $detail, array $from, array $to, $world, $filter, $vis_date, $id_partner=0)
    {
        if(count($from) > 0)
        {
            $start = $from;
        } else {
            $start[0] = date('Y');
            $start[1] = date('m');
            $start[2] = date('d');
            $start[3] = '00';
        }

        if(count($to) > 0)
        {
            $end = $to;
        } else {
            $end[0] = date('Y');
            $end[1] = date('m');
            $end[2] = date('d');
      if (date('H') < 23)
            $end[3] = date('H') + 1;
      else
            $end[3] = date('H');

        }

        $partners = DS_Partner::getPartner();
        $pr  = array();
      $pr[] = array('t'=>'Все партнёры', 'v' => 0);     
        foreach ($partners as $p)
        {
          $slist = explode("\n",$p['site_list']);
          $url = $slist[0];
          $pr[] = array('t'=>$url.'('.$p['id_partner'].')'." - ".$p['name'], 'v' => $p['id_partner']);
        }
        
      $plist = self::form_select_str('id_partner', $pr, $id_partner);
        $out = <<<EOT
<script language="JavaScript">
<!--

function checkOption()
{
    var worldDiv = document.getElementById('world_select');

    var sel = document.getElementById('type');

    if(sel.selectedIndex == 0)
    {
        worldDiv.style.display = 'none';
        filter.style.display = 'block';
        acsv.style.display = 'block';
  } else {
        worldDiv.style.display = 'block';
        filter.style.display = 'none';
        acsv.style.display = 'none';
  }
}

function getCSV()
{
  dateFrom = document.getElementById("df0").value+'-'+document.getElementById("df1").value +'-'+
             document.getElementById("df2").value + " "+ document.getElementById("df3").value+":00:00";

  dateTo = document.getElementById("dt0").value+'-'+document.getElementById("dt1").value+'-'+
           document.getElementById("dt2").value + " "+ document.getElementById("dt3").value+":00:00";

  filterword = document.getElementById("filterword").value;
  vis_date = document.getElementById("vis_date").value;
  URL = "csv.php?action=csv&dateFrom="+dateFrom+"&dateTo="+dateTo+"&filterword="+filterword+
    "&vis_date="+vis_date;
  window.open(URL);
  return false;
}
//-->
</script>

<form action="index.php" method="POST">
    <input type="hidden" name="action" value="show_stat">
  <b span style='color:brown'>Чтобы выбрать ВСЕХ игроков за указанный период, оставьте поле "дата последнего захода" пустым</b>
<table border=1>
        <tr bgcolor='#8888A0'>
            <td>
                Start date:
                <table border=0>
                    <tr class=th_2>
                        <td>Year</td><td>month</td><td>day</td><td>hour</td>
                    </tr>
                    <tr>
                        <td><input type='text' size=4 id='df0' name='dateFrom[0]' value="{$start[0]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 id='df1' name='dateFrom[1]' value="{$start[1]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 id='df2' name='dateFrom[2]' value="{$start[2]}">&nbsp; &nbsp;</td>
                        <td><input type='text' size=2 id='df3' name='dateFrom[3]' value="{$start[3]}"></td>
                    </tr>
                </table>
            </td>
            <td width=250px>
                End date:
                <table border=0>
                    <tr class=th_2>
                        <td>Year</td><td>month</td><td>day</td><td>hour</td>
                    </tr>
                    <tr>
                        <td><input type='text' size=4 id='dt0' name='dateTo[0]' value="{$end[0]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 id='dt1' name='dateTo[1]' value="{$end[1]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 id='dt2' name='dateTo[2]' value="{$end[2]}">&nbsp; &nbsp;</td>
                        <td><input type='text' size=2 id='dt3' name='dateTo[3]' value="{$end[3]}"></td>
                    </tr>
                </table>
            </td>
            <td>&nbsp;</td>
        </tr>
        <tr bgcolor='#8888A0'>
EOT;
        $out .= "
            <td valign='bottom'>
                &nbsp;&nbsp;Statistic:&nbsp;<select name='type' id='type' onChange='checkOption();'>
                    <option value='0'>User registration
                    <option value='1'";
        if($type == '1')
        {
            $out .= " selected";
        }
        $out .= ">World population
                    <option value='2'";

        if($type == '2')
        {
            $out .= " selected";
        }
        $out .= ">User delete";

        $out .= "
                </select>
            </td>
            <td valign='bottom'>
                <div  id='world_select' ";
        if($type != '1' && $type != '2')
        {
            $out .= "style='display: none;'";
        }
        $out .= "><span>

                    &nbsp;&nbsp;World:&nbsp;<select name='world'>
        ";

        foreach ($worlds as $id => $worldName)
        {
            if($world == $id)
            {
                $s = 'selected';
            } else {
                $s = '';
            }
            $out .= "<option value='$id' $s>".$worldName."\n";
        }

        $out .= "
                    </select>
                </span>
        </div>
        <div id='filter' ";
        if ($type != 0)
        {
            $out .= "style='display: none;'";
      $display = 'none';
        }
    else
     $display = 'block';

        $out .= ">
        URL: <input type='text' size=25 id='filterword' name='filterword' value='$filter'>
        Миним. дата последнего захода: 
        <input type='text' size=12 id='vis_date' name='vis_date' value='$vis_date'><br>
        $plist
        
        </div>
      </td>
            <td valign='bottom'>
                Detail:
                <select name='detail'>
            ";

        $dateParts = array(
                5 => 'one user',
        0 => 'year',
                1 => 'month',
                2 => 'week',
                3 => 'day',
                4 => 'hour',
    );

        foreach ($dateParts as $k => $v)
        {
            $out .= "<option value=$k";
            if($detail == $k)
            {
                $out .= ' selected ';
            }
            $out .= ">$v";
        }

        $out .= "
                </select>
            </td>

        </tr>
        <tr>
            <td colspan=3>
        <table><tr><td>
                <input type='submit' value='OK'></td>
        <td>
        <input type='submit' name='acsv' value='CSV' onClick='return getCSV()'></td>
        </td></tr></table>
            </td>
        </tr>
    </table>
</form>";
        echo $out;
    }

    static public function OutUserStat($users, $pg, $totalcnt, $total_registered, $items_num, GB_Date $from, GB_Date $to, $filter, $vis_date, $id_partner)
    {
    $out =  "<script src='/Admin/js/pager.js'></script>";
    $out .=  "<link href='/Admin/styles/pager.css' rel='stylesheet' type='text/css' />";
    //echo "total_cnt=$totalcnt, items_num=$items_num";
    $PaginatorURL = "?action=show_stat&type=0&detail=5&dateFrom=".$from->getPgFormat()."&dateTo=".$to->getPgFormat().
      "&filterword=".urlencode($filter)."&vis_date={$vis_date}&id_partner=$id_partner&pg=";
    $PaginatorURL = str_replace(' ', '%20',$PaginatorURL);

    $cnt_pages = ceil($totalcnt/$items_num);
    $out .= '<b style="color:white">Игроки, зарегистрировавшиеся за указанный период:</b><br>';
    $out .= '<b style="color:white">ВСЕГО игроков (с заданными критериями, без учёта даты последнего захода):</b> <b style="color:brown">'.$total_registered.'</b> <br>';

    if ($vis_date)
      $out .= '<b style="color:white">ВСЕГО игроков, зашедших после указанной даты:</b> <b style="color:brown">'.count($users).'</b> <br>';

    $out .= "<table border=1 width=500px>
        <tr class=th_2>
        <td>ID</td>
        <td>Ник</td>
        <td>Дата регистрации</td>
        <td>Откуда пришёл</td>
        <td>IP</td>
        <td>Последний заход в мир</td>
        <td>Рейтинг</td>
        <!--<td>from_partner</td>-->
        </tr>
        ";
     
    if ($vis_date)
    {
      //$users = array_slice($users, ($pg-1)*$items_num, $items_num);
    }
    $bgColor = '8888A0';
    foreach($users as $u)
    {
       $arr =  explode('.', $u['registrationdate']);
       $d1 =  $arr[0];
       $alt = urldecode($u['reg_referer']);
       $alt = iconv("UTF-8","cp1251",$alt);
       $out .= "<tr>
        <td bgcolor='#$bgColor'>{$u['id_user']}</td>
        <td bgcolor='#$bgColor'><a href='/Admin/controls/Players/EditUser/UserInfo/index.php?username={$u['username']}&search=1' target='_blank'>{$u['username']}</a></td>
        <td bgcolor='#$bgColor'>{$d1}</td>
        <td bgcolor='#$bgColor'><a href='{$u['reg_referer']}' target=_blank title='".$alt."'>".$u['reg_referer']."</a></td>
        <td bgcolor='#$bgColor'><a href='http://www.db.ripe.net/whois?form_type=simple&full_query_string=&searchtext={$u['ip_addr']}+&submit.x=12&submit.y=12&submit=Search' target='_blank'>{$u['ip_addr']}</a></td>
        <td bgcolor='#$bgColor'>{$u['last_visit']}</td>
        <td bgcolor='#$bgColor'>{$u['rank']}</td>
        <!--<td bgcolor='#$bgColor'>{$u['from_partner']}</td>-->
       </tr>";
    }
    $out .= "</table>";

    if (!$vis_date)
    {
      $out .= "<div class='paginator' id='paginator_users'></div>
<script type='text/javascript'>
    p = new Paginator('paginator_users', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>";
    } 
    echo $out;
  }

    static public function OutStat(array $stat, array $min_max, $total)
    {
        $out = "<table border=1 width=500px>";

        $head = true;

        $detail = '';

        $count = 0;

        foreach ($stat as $k => $s)
        {
            if($head)
            {
                $out .= "<tr class=th_2>\n";
                $count = count($s) + 1;
                foreach ($s as $par => $val)
                {
                    if($par=='year' || $par=='month' || $par=='week' || $par=='day')
                    {
                        $detail = $par;
                    }
                    $out .= "<td align='center' width='50px'><b>".$par."</b></td>\n";
                }
                $out .= "<td>&nbsp;</tr>\n";
                $out .= "</tr>\n";
                $head = false;
            }

            if(key_exists('day', $s))
            {
                $weekDay = date('D', mktime(1, 0, 0, $s['month'], $s['day'], $s['year']));
            } else {
                $weekDay = '';
            }

            if(!empty($weekDay) && ($weekDay == 'Sun' || $weekDay == 'Sat'))
            {
                $bgColor = 'A08888';
            } else {
                $bgColor = '8888A0';
            }

            $out .= "<tr>";
            foreach ($s as $par => $val)
            {
                if($par == 'count')
                {
                    $w = round(100.0 * (int)$val / (int)$min_max['max']);
                    if($val == $min_max['max'])
                    {
                        $val = "<span style='color: #00dd00'><b>".$val."</b></span>";
                    } elseif($val == $min_max['min']) {
                        $val = "<span style='color: red'><b>".$val."</b></span>";
                    }

                    $out .= "<td align='center' bgcolor='#$bgColor'>".$val."</td>\n";
                    $out .= "<td bgcolor='#$bgColor'><img src='/ds/drowimage.php?width=$w&ws=1.5' alt='$w% from max'></td>\n";
                } elseif($par == 'day') {

                    $out .= "<td align='center' bgcolor='#$bgColor'>".$val.' ('.$weekDay.")</td>\n";
                } else {
                    $out .= "<td align='center' bgcolor='#$bgColor'>".$val."</td>\n";
                }
            }
            $out .= "</tr>\n";
        }

        $countData = count($stat);
        $avg = round($total / $countData, 2);

        $avgWMax = min(2 * ($avg - $min_max['min']) / max(1, $min_max['max'] - $min_max['min']), 1);
        $avgWMin = min(2 * ($min_max['max'] - $avg) / max(1, $min_max['max'] - $min_max['min']), 1);

        $avgW = ($avg - $min_max['min']) / ($min_max['max'] - $min_max['min']);

        $colW = 1 / $avgW;

        $avgCR = round($avgWMin * 221);
        $avgCG = round($avgWMax * 221);

        if($avgCR == 0 && $avgCG == 0)
        {
            $avgCG = 221;
        }

        $avgColor = "rgb($avgCR, $avgCG, 0)";

        $out .= "<tr class=th_2><td colspan=$count>Total: $total for ".$countData." {$detail}s&nbsp;&nbsp;&nbsp;&nbsp;Min: <span style='color: red'><b>".$min_max['min']."</b></span>&nbsp;&nbsp;&nbsp;&nbsp;Max: <span style='color: #00dd00'><b>".$min_max['max']."</b></span>&nbsp;&nbsp;&nbsp;&nbsp;Average: <span style='color: $avgColor'><b>$avg</b></span> per $detail</td></tr>";

        $out .= "</table>";

        echo $out;
    }
  
    static public function form_select_str($name, $arr, $selv)
  {
     $str = "<select name=\"$name\" id=\"$name\">\n";

     foreach ($arr as $el)
     {
       $selected  = '';
       if ($selv == $el['v']) {$selected = 'selected';}
       $str .= "<option value=\"".$el['v']."\" $selected>".$el['t']."</option>\n";
     }
     $str .= "</select>\n";
     return $str;
  } 
}

?>