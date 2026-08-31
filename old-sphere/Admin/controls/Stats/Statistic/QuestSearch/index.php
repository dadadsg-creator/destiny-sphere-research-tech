<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

//echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=BattlesStat><b>HELP</b></a></p>';

$s = new QuestSearch($_REQUEST);

class QuestSearch
{
    private $action = 'index';

    private $wordls = array();

    private $dateFrom;

    private $dateTo;

    private $detail = '3';

    private $dateFromArr = array();

    private $dateToArr = array();

    public function __construct(array $data)
    {
        $this->_getParams($data);

        switch ($this->action)
        {
            case 'index':
                $this->_showIndex();
                break;
            case 'showStat':
                $this->_showIndex();
                $this->_showStat();
                break;
        }
    }

    private function _showIndex()
    {
        $common = DS_Common::CreateForCurentDB(0);

        $worlds = array();

        foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
        {
            $common->setDbId((int)$dbid);

            $sql = "SELECT id_world, worldname FROM ds_world";
            $res = $common->getResultArray('', $sql, '', '', '');
            assert(empty($common->errormsg));

            foreach($res as $k=>$v)
            {
                $keyRes = $dbid."_".$v['id_world'];
                $worlds[$keyRes] = $v['worldname'];
            }
        }

        $this->_outIndex($worlds, $this->dateFromArr, $this->dateToArr, $this->worlds, $this->detail);
    }

    private function _showStat()
    {
        $stat = $this->_getStat($this->dateFrom, $this->dateTo, $this->worlds, $this->detail);
        $header = $this->_getHeader($this->dateFrom, $this->dateTo, $this->worlds, $this->detail);

        $this->_outStat($stat, $header);
    }

    private function _getParams($data)
    {
        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
        }


        if (key_exists('worlds', $data))
        {
            $this->worlds = $data['worlds'];
            if (!is_array($this->worlds))
            {
                exit('Error: wrong worlds');
            }
        }

        if(key_exists('dateFrom', $data))
        {
            assert(is_array($data['dateFrom']));
            $dateFrom = $data['dateFrom'][0].'-';
            $dateFrom .= $data['dateFrom'][1].'-';
            $dateFrom .= $data['dateFrom'][2].' ';
            $dateFrom .= $data['dateFrom'][3].':00:00';

            $this->dateFromArr = $data['dateFrom'];
            $this->dateFrom = $dateFrom;

            $_SESSION['dateFrom'] = $this->dateFrom;
        }

        if(key_exists('dateTo', $data))
        {
            assert(is_array($data['dateTo']));
            $dateTo = $data['dateTo'][0].'-';
            $dateTo .= $data['dateTo'][1].'-';
            $dateTo .= $data['dateTo'][2].' ';
            $dateTo .= $data['dateTo'][3].':00:00';

            $this->dateToArr = $data['dateTo'];
            $this->dateTo = $dateTo;

            $_SESSION['dateTo'] = $this->dateTo;
        }


        if (key_exists('detail', $data))
        {
            $this->detail = $data['detail'];
        }
    }

    private function _outIndex(array $worlds, array $from, array $to, $selectedWorlds, $detail)
    {
        if(count($from) > 0)
        {
            $start = $from;
        } else {

            $week = strtotime("-1 week");

            $start[0] =  date('Y', $week);
            $start[1] = date('m', $week);
            $start[2] = date('d', $week);
            $start[3] = date('H', $week);
        }

        if(count($to) > 0)
        {
            $end = $to;
        } else {
            $end[0] = date('Y');
            $end[1] = date('m');
            $end[2] = date('d');
            $end[3] = date('H') + 1;
        }

        $out = <<<EOT
    <form action="" method="POST">
    <input type="hidden" name="action" value="showStat">
    <table border=0>
        <tr>
            <td>
                <font color=#F8F8FF>Start date:</font> 
                <table border=0>
                    <tr class=th_2>
                        <td>Year</td><td>month</td><td>day</td><td>hour</td>
                    </tr>
                    <tr>
                        <td><input type='text' size=4 name='dateFrom[0]' value="{$start[0]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateFrom[1]' value="{$start[1]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateFrom[2]' value="{$start[2]}">&nbsp; &nbsp;</td>
                        <td><input type='text' size=2 name='dateFrom[3]' value="{$start[3]}"></td>
                    </tr>
                </table>
            </td>
            <td width=250px>
                <font color=#F8F8FF>End date:</font> 
                <table border=0>
                    <tr class=th_2>
                        <td>Year</td><td>month</td><td>day</td><td>hour</td>
                    </tr>
                    <tr>
                        <td><input type='text' size=4 name='dateTo[0]' value="{$end[0]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateTo[1]' value="{$end[1]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateTo[2]' value="{$end[2]}">&nbsp; &nbsp;</td>
                        <td><input type='text' size=2 name='dateTo[3]' value="{$end[3]}"></td>
                    </tr>
                </table>
            </td>
        </tr>   
EOT;

        $out .= "
                <tr>
                    &nbsp;
                </tr>";

        //Выбор миров
        $out .= "
                <tr>
                    <td valign='top'>
                    ";  
        $out .= "
                    <font color=#F8F8FF>Worlds:</font><br>
                        ";

        foreach ($worlds as $id => $worldName)
        {
            if (empty($selectedWorlds)) {
                $c = 'checked';
            } else {
                if (in_array($id, $selectedWorlds))
                {
                    $c = 'checked';
                } else {
                    $c = '';
                }
            }
            $out .= '<input type=checkbox name="worlds[]" value='.$id.' '.$c.'><font color=#F8F8FF>'.$worldName.'</font>
';
            $out .= '<br>';
        }
        $out .= "  </td>
                    ";

        //Выводим менюшку выбора детализации
        $out .= '
                    <td valign="top">
                            <font color=#F8F8FF>
                                Detail: 
                            </font><br>';

        $out .= "
                <select name='detail'>  
            ";

        $dateParts = array(
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
            $out .= ">$v</option>";
        }

        $out .= "
                </select>
            </td>
            
        </tr>
        ";

        $out .= "<br>";
        $out .= "</table>";

        $out .= "<input type='submit' class='Submit' value='&nbsp;&nbsp;&nbsp;&nbsp;Show Stat&nbsp;&nbsp;&nbsp;&nbsp;'>";
        $out .= "</form>";

        echo $out;
    }

    private function _getStat($from, $to, $worlds, $detail)
    {

        foreach ($worlds as $key => $worldArr)
        {
            $tempArr = explode('_', $worldArr);
            $dbID= $tempArr[0];
            $worldId = $tempArr[1];

            assert($dbID >= 0);
            assert($worldId >= 0);

            $common = DS_Common::CreateForCurentDB($dbID);

            $sql = $this->_getSQL($from, $to, $worldId, $detail);
            $res = $common->getResultArray("", $sql, "", "", "");

            assert(empty($common->errormsg));

            //Получаем имя мира по его id
            $sql = "
                SELECT
                    worldname
                    FROM
                        ds_world
                    WHERE
                     id_world = '$worldId'
                ";
            $w = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));

            $worldName  = $w[0]['worldname'];
            assert(!empty($worldName));

            //Формируем дату
            foreach ($res as $k => $dateArr)
            {
                $date = $this->_generateDate($dateArr, $detail);
                $resArr[$worldName][$date] = $dateArr['count'];
                $date = '';
            }
        }

        return $resArr;
    }

    private function _formSQLPart($detail, $alias = false)
    {
        $dateParts = array(
        0 => 'year',
        1 => 'month',
        2 => 'week',
        3 => 'day',
        4 => 'hour',
        );

        for($i=0; $i<=$detail; $i++)
        {
            $sql[$i] = "extract(".$dateParts[$i]." from q.date)";
            if($alias)
            {
                $sql[$i] .= ' as '.$dateParts[$i];
            }
        }

        return implode(', ', $sql);
    }

    private function _getHeader($from, $to, $worlds, $detail)
    {
        $resArr = array();
        
        foreach ($worlds as $key => $worldArr)
        {
            $tempArr = explode('_', $worldArr);

            $dbId = $tempArr[0];
            $worldId = $tempArr[1];

            assert($dbId >= 0);
            assert($worldId >= 0);

            $common = DS_Common::CreateForCurentDB($dbId);

            $sql = $this->_getSQL($from, $to, $worldId, $detail);

            $res = $common->getResultArray("", $sql, "", "", "");

            assert(empty($common->errormsg));

            //Проходимся по результату
            foreach ($res as $k => $dateArr)
            {

                $date = self::_generateDate($dateArr, $detail);

                //Если данного промежутка еще нет в результирующем массиве, то добавляем его
                if (!in_array($date, $resArr))
                {
                    $resArr[] = $date;
                }
                $date = '';
            }

        }

        sort($resArr);

        return $resArr;
    }

    private function _outStat($stat, $header)
    {
        if (empty($stat)) {
            echo "<h1>No Stat!</h1><br>";
            return ;
        }
        
        echo "<style>";
        echo "<!--";
        echo ".verticaltext {";
        echo "writing-mode: tb-rl;";
        echo "filter: flipv fliph;";
        echo "}";
        echo "-->";
        echo "</style>";

        $countColumn = count($header) + 4;
        $totalCount = array();
        $total = 0;
        $count = 0;

        $out = "<br><br>
                ";

        $out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                    <tr>
                       <td>
                          <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";

        $out .= "<tr>
                    <th bgcolor='#2A3E66'>
                        <font color=#F8F8FF>
                            World
                        </font>
                    </th>";
        foreach ($header as $key => $data)
        {
            $out .= "<td bgcolor='#2A3E66'>
                        <font color=#F8F8FF>
                            <div class=verticaltext>
                                <nobr>
                                    <b>
                                        $data
                                    </b>
                                </nobr>
                            </div>
                        </font>
                    </td>";
            $totalCount[$data] = 0;
        }
        $out .= "<th bgcolor='#2A3E66'>
                    <font color=#F8F8FF>
                        Summ
                    </font>
                </th>";
        $out .= "   </tr>
                ";

        foreach ($stat as $world => $data)
        {
            $out .= "<tr>
                        <td bgcolor='#444E6C'>
                            <font color=#F8F8FF>
                                $world
                            </font>
                        </td>";

            foreach ($header as $headerKey => $headerDate)
            {
                if (key_exists($headerDate, $data))
                {
                    $out .= "<td bgcolor='#444E6C'>
                                <font color=#F8F8FF>
                                    ".$data[$headerDate]."
                                </font>
                            </td>";
                    $count += $data[$headerDate];
                    $totalCount[$headerDate] += $data[$headerDate];
                } else {
                    $out .= "<td bgcolor='#444E6C'>
                                <font color=#F8F8FF>
                                    -
                                </font>                 
                            </td>";
                }
            }

            //Виводим сумму воев
            $out .= "<td bgcolor='#2A3E66'>
                                <font color=#F8F8FF>
                                    <b>
                                        $count
                                    </b>
                                </font>
                    </td>";
            $out .= "</tr>";

            //Обнуляем счетчик
            $count = 0;
        }

        //Выводим количество переездов
        $out .= "<tr>
                        <td bgcolor='#2A3E66'>
                            <font color=#F8F8FF>
                                <b>
                                    Total:
                                </b>
                            </font>";

        foreach ($header as $key => $date)
        {
            $out .= "
                        <td bgcolor='#2A3E66'>
                            <font color=#F8F8FF>
                                <b>
                                    ".$totalCount[$date]."
                                </b>
                            </font>
                        </td>";
            $total += $totalCount[$date];
        }

        $out .= "
                        <td bgcolor='#2A3E66'>
                            <font color=#F8F8FF>
                                <b>
                                    $total
                                </b>
                            </font>
                        </td>";

        $out .= "</tr>";

        $out .= "</table>
                         </td>
                      </tr>
                 </table>";
        echo $out;
    }

    private  function _generateDate($dateArr, $detail)
    {
        assert(is_array($dateArr));

        $separator = '';
        $date = '';

        foreach ($dateArr as $period => $value)
        {
            if ($period == 'count')
            {
                continue;
            }

            if ($period == 'week' AND $detail != 2)
            {
                continue;
            }

            $date .= $separator.$value;

            if ($detail == 2)
            {
                if ($period == 'year')
                {
                    $separator = '-';
                }

                if ($period == 'month')
                {
                    $separator = ' ';
                }

                if ($period == 'week')
                {
                    $separator = '-';
                }

            } else {
                if ($period == 'year' OR $period == 'month')
                {
                    $separator = '-';
                }

                if ($period == 'day')
                {
                    $separator = ' ';
                }

                if ($period == 'hour')
                {
                    $separator = 'hour';
                }
            }
        }

        if ($detail == 2)
        {
            $date .= " week";
        }
        return $date;
    }

    private function _getSQL($from, $to, $world, $detail)
    {
        $sql = "
            
                        SELECT                              
                                ".$this->_formSQLPart($detail, true).",
                                count(*) as count
                            FROM
                                ds_quest as q
                            LEFT JOIN ds_comb as c ON (c.id_comb = q.id_comb)
                            LEFT JOIN ds_combcomplex as cc ON (cc.id_combcomplex = c.id_combcomplex)
                            LEFT JOIN ds_ring as r ON (r.id_ring = cc.id_ring)
                            WHERE
                                 q.date > '$from'
                                AND
                                 q.date < '$to'
                                AND
                                 r.id_world='$world'
                            GROUP BY
                                ".$this->_formSQLPart($detail)."
                            ORDER BY
                                ".$this->_formSQLPart($detail)."
                            ";
    
        return $sql;
    }
}


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>