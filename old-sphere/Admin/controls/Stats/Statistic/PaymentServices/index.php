<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=PaymentServices><font size=3><b>HELP</b></font></a></p>';
class PaymentServices
{
    private $action = 'index';

    private $dateFrom;

    private $dateTo;

    private $dateFromArr;

    private $dateToArr;

    private $userName = '';

    private $service = 'ALL';

    private $show = 'total';
    
    private $sortBy = 'paid_date';
    
    // Миры, для которых покупается услуга
    private $world = 'ALL';

    // Миры, в которых покупается услуга    
    
    private $worldFrom = 'ALL';
    
    private $dbname = '';

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

            case 'show_detail':
                $this->_showDetail();
                break;
        }
    }

    private function _getParams($data)
    {
        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
        }
        if(key_exists('userName', $data))
        {
            $this->userName = $data['userName'];
        }

        if(key_exists('service', $data))
        {
            $this->service = $data['service'];
        }

        if(key_exists('show', $data))
        {
            $this->show = $data['show'];
        }

        if(key_exists('sortBy', $data))
        {
            $this->sortBy = $data['sortBy'];
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
        
        if(key_exists('world', $data))
        {
            $this->world = $data['world'];
        }
        
        if(key_exists('worldFrom', $data))
        {
            $this->worldFrom = $data['worldFrom'];
        }
        
        if(key_exists('dbname', $data))
        {
            $this->dbname = $data['dbname'];
        }
    }

    private function _showIndex()
    {
        if(count($this->dateFromArr) > 0)
        {
            $start = $this->dateFromArr;
        } else {

            $week = strtotime("-1 week");

            $start[0] =  date('Y', $week);
            $start[1] = date('m', $week);
            $start[2] = date('d', $week);
            $start[3] = date('H', $week);
        }

        if(count($this->dateToArr) > 0)
        {
            $end = $this->dateToArr;
        } else {
            $end[0] = date('Y');
            $end[1] = date('m');
            $end[2] = date('d');
            $end[3] = date('H') + 1;
        }

        //Выбираем список всех платных услуг
        $common = DS_Common::CreateForCurentDB(0);
        $sql = "
            SELECT
                mt.action_name,
                COALESCE(l.text, mt.action_name) as text
            FROM 
                ds_money_action_types mt
            LEFT JOIN ds_localizedtextlanguage l ON (mt.id_localizedname = l.id_localizedtext AND l.id_language = 3)
            WHERE 
                mt.action_name like 'PS_%'
            ORDER BY action_name
            ";
        $paymentServices = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));


        //Выбираем список всех миров
        $sql = "SELECT * FROM ds_world";
        $worlds = $common->getMultySql($sql);
        assert(empty($common->errormsg));

        if ($this->show == 'all')
        {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        if ($this->service == 'ALL') {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        
        if ($this->show == 'all') {
            $selectedOperation = 'selected';
        } else {
            $selectedOperation = '';
        }
        echo "
<table border='0'>
<tr>
    <td>
    <form action='' method='POST'>
    <input type='hidden' name='action' value='showStat'>
    
    <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
        <tr>
            <td>
                
                <table width=100% border=0 cellspacing=1 cellpadding=3>
                    <tr class=maintxt_med bgcolor=#444E6C >
                        <td colspan='4' class=th_2 align=center>
                            Stat Date
                        </td>
                    </tr>
                    <tr class=th_2>
                        <td>Year</td>
                        <td>month</td>
                        <td>day</td>
                        <td>hour</td>
                    </tr>
                    <tr>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=4 name='dateFrom[0]' value=".$start[0].">&nbsp;-&nbsp;</td>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateFrom[1]' value=".$start[1].">&nbsp;-&nbsp;</td>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateFrom[2]' value=".$start[2].">&nbsp; &nbsp;</td>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateFrom[3]' value=".$start[3]."></td>
                    </tr>
                </table>
            </td>
            <td bgcolor=#444E6C>
                &nbsp;
            </td>
            <td> 
                <table width=100% border=0 cellspacing=1 cellpadding=3>
                    <tr class=maintxt_med bgcolor=#444E6C >
                        <td colspan='4' class=th_2 align=center>
                            End Date
                        </td>
                    </tr>
                    <tr class=th_2>
                        <td class=maintxt_med bgcolor=#444E6C>Year</td>
                        <td class=maintxt_med bgcolor=#444E6C>month</td>
                        <td class=maintxt_med bgcolor=#444E6C>day</td>
                        <td class=maintxt_med bgcolor=#444E6C>hour</td>
                    </tr>
                    <tr>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=4 name='dateTo[0]' value=".$end[0].">&nbsp;-&nbsp;</td>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateTo[1]' value=".$end[1].">&nbsp;-&nbsp;</td>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateTo[2]' value=".$end[2].">&nbsp; &nbsp;</td>
                        <td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateTo[3]' value=".$end[3]."></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </td>
    <td>
        <table width=100% border=0 bgcolor=#444E6C>
            <tr>
                <td>
                    <font color=#F8F8FF>UserName: </font>
                </td>
                <td>
                    <input type='text' name='userName' value='".$this->userName."'>
                </td>
            </tr>";
        echo '
        <tr>
            <td>
                <font color=#F8F8FF>Service: </font>&nbsp;&nbsp;
            </td>
            <td>
             <select name="service">
              <option value="ALL" '.$selected.'>ALL</option>
            ';
        foreach ($paymentServices as $key => $name)
        {
            if ($name['action_name'] == $this->service) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            echo '
         <option value="'.$name['action_name'].'" '.$selected.'>'.$name['text'].'</option>
         '; 
        }
        echo '  </select>
            </td>
        </tr>';
        echo '
            <tr>
                <td>
                    <font color=#F8F8FF>Show: </font>
                </td>
                <td>        
                    <select name="show">
                    <option value="total">Total</option>
                    <option value="all" '.$selectedOperation.'>All operations</option>
                    </select>
                </td>
            </tr>';
        echo '
        <tr>
            <td>
                <font color=#F8F8FF>World: </font>&nbsp;&nbsp;
            </td>
            <td>
             <select name="world">
              <option value="ALL">ALL</option>
            ';
        foreach ($worlds as $key => $name)
        {
            if ($name['worldname'] == $this->world) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            echo '
         <option value="'.$name['worldname'].'" '.$selected.'>'.$name['worldname'].'</option>
         '; 
        }
        echo '  </select>
            </td>
        </tr>';
        
        /*if ($name['worldname'] == 'Cabinet') {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        echo '
        <tr>
            <td>
                <font color=#F8F8FF>From world: </font>&nbsp;&nbsp;
            </td>
            <td>
             <select name="worldFrom">
              <option value="ALL">ALL</option>
              <option value="Cabinet" '.$selected.'>Cabinet</option>
            ';
        foreach ($worlds as $key => $name)
        {
            if ($name['worldname'] == $this->worldFrom) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            echo '
         <option value="'.$name['worldname'].'" '.$selected.'>'.$name['worldname'].'</option>
         '; 
        }
        echo '  </select>
            </td>
        </tr>';*/
    echo '
        </table>
    </td>
</tr>       
</table>
    ';

        echo '
         <table border=0>
        <tr>
            <td>
                &nbsp;
            </td>
        </tr>
        <tr>
            <td>
                <input type="submit" class="submit" value="ShowStat">
            </td>
        </tr>   
    </table>
    </form>';

    }

    private function _showStat()
    {           
        global $orderBy;
        
        $userData = "";
        
        switch ($this->sortBy)
        {
            case 'service':
                $orderBy = "action_name ";
                break;
            case 'user':
                $orderBy = "username ";
                break;
            case 'SC':
                $orderBy = "sc_price";
                break;
            case 'GC':
                $orderBy = "gc_price";
                break;
            case 'date':
                $orderBy = "paid_date";
                break;
            case 'dbname':
                $orderBy = "dbname";
                break;
            default:
                $orderBy = "paid_date";
                break;
        }

        if ($this->show == 'all')
        {
            $select = ' * ';
            $groupby = '';


        } else {
            $select = ' p.action_name,
                        sum(p.sc_price) as sc_price,
                        sum(p.gc_price) as gc_price,
                        count(*) as count ';
            $groupby = ' GROUP BY
                            p.action_name ';

        }

        if ($this->service != 'ALL') {
            $whereService = " AND
                                action_name = '$this->service'";
        }
        
        if ($this->world != 'ALL') {
            $whereWorld = " AND
                                info like '%$this->world%'";
        }

        if (!empty($this->userName)) {
            $where = "  AND
                       u.username = '".$this->userName."'
                        AND
                       pu.is_initiator = 't' ";

            //Для ссылки
            $userData = '&userName='.$this->userName;
        }
        
        $common = DS_Common::CreateForCurentDB(0);

        // Сортировку пока убираем, так как все равно потом сотрируем
        $sql = "
            SELECT 
                $select
            FROM 
                ds_paymentserviceslog p
            LEFT JOIN ds_paymentserviceslog_users pu ON (p.id_paymentserviceslog = pu.id_paymentserviceslog)
            LEFT JOIN ds_user u ON (pu.id_user = u.id_user)
            WHERE
                '".$_SESSION['dateFrom']."' < p.paid_date
                 AND
                p.paid_date < '".$_SESSION['dateTo']."'
                $where
                $whereService
                $whereWorld
            $groupby
            ";

        $res = $common->getMultyArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if (empty($res)) {
            echo '<font color="Red">NO DATA</font>';
            return;
        }

        if ($this->show == 'all')
        {
            // Сортируем массив
            usort($res, "cmp");
            
            echo '
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <tr class=th_2 >
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=all&sortBy=user" >User</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=all&sortBy=service" >Service</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=all&sortBy=SC" >Silver Credits</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=all&sortBy=GC" >Gold Credits</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=all&sortBy=date" >Date</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=all&sortBy=dbname" >Dbname</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=all&sortBy=detail" >Detail</a>
                            </th>
                        </tr>';
            $sc = 0;
            $gc = 0;
            $total = 0;

            foreach ($res as $key => $data)
            {
                echo '
                        <tr>
                            <td align="left" class=maintxt_med bgcolor=#444E6C>
                                '.$data['username'].'
                            </td>
                            <td align="left" class=maintxt_med bgcolor=#444E6C>
                                '.$data['action_name'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$data['sc_price'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$data['gc_price'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$data['paid_date'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$data['dbname'].'
                            </td>
                            <td align="left" class=maintxt_med bgcolor=#444E6C>
                                '.$data['info'].'
                            </td>
                        </tr>';
                $sc += $data['sc_price'];
                $gc += $data['gc_price'];
                $total ++;
            }
            echo '
                        <tr>
                            <td align="left" class=maintxt_med bgcolor=#2A3E66>
                                Total:
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                '.$total.' operations
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                '.$sc.'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                '.$gc.'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                &nbsp;
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                &nbsp;
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                &nbsp;
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>   
        </table>
        ';
        } else {
            echo '
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <tr class=th_2 >
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=total&sortBy=service" >Service</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=total&sortBy=dbname" >Dbname</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=total&sortBy=count" >Count</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=total&sortBy=SC" >Silver Credits</a>
                            </th>
                            <th>
                                <a href="?action=showStat&service='.$this->service.$userData.'&world='.$this->world.'&show=total&sortBy=GC" >Gold Credits</a>
                            </th>
                            <th>
                                &nbsp;  
                            </th>
                        </tr>';
            $total = 0;
            $sc = 0;
            $gc = 0;

      $goodtypes = array(
            'PS_BUY_PREMIUM_180' => 'PREMIUM_AND_ANALIZATOR',
            'PS_BUY_PREMIUM_30'  => 'PREMIUM_AND_ANALIZATOR',
            'PS_BUY_PREMIUM_360' => 'PREMIUM_AND_ANALIZATOR',
      'PS_BUY_PREMIUM_90'  => 'PREMIUM_AND_ANALIZATOR',
      'PS_BUY_PREMIUM_PLUS_ANALIZATOR_180' => 'PREMIUM_AND_ANALIZATOR',
      'PS_BUY_PREMIUM_PLUS_ANALIZATOR_30'  => 'PREMIUM_AND_ANALIZATOR',
      'PS_BUY_PREMIUM_PLUS_ANALIZATOR_360' => 'PREMIUM_AND_ANALIZATOR',
      'PS_BUY_PREMIUM_PLUS_ANALIZATOR_90'  => 'PREMIUM_AND_ANALIZATOR',
      'PS_BUY_ANALIZATOR'              => 'PREMIUM_AND_ANALIZATOR',

      'PS_BUY_RESOURCE_FOOD'           => 'RESOURCES',
      'PS_BUY_RESOURCE_KADERIUM'       => 'RESOURCES',
      'PS_BUY_RESOURCE_NANO_CRYSTAL'   => 'RESOURCES',
      'PS_BUY_RESOURCE_PRODIUM'        => 'RESOURCES',
      'PS_BUY_RESOURCE_STASIS_ENERGY'  => 'RESOURCES',

      'PS_BY_BONUS_FOOD'          => 'BONUSES',
      'PS_BY_BONUS_KADERIUM'      => 'BONUSES',
      'PS_BY_BONUS_NANO_CRYSTAL'  => 'BONUSES', 
      'PS_BY_BONUS_PRODIUM'       => 'BONUSES',  
      'PS_BY_BONUS_RESOURCE'      => 'BONUSES',
      'PS_BY_BONUS_STASIS_ENERGY' => 'BONUSES', 
      
      'PS_BY_UNIT_ARCHEOLOG'      => 'UNITS',
      'PS_BY_UNIT_SCIENTIIFIC'    =>  'UNITS', 

      'PS_BUY_TRAINING_COMPLETION' => 'COMB_ACTIONS',
      'PS_BY_PREMIUM_BAN'          => 'COMB_ACTIONS',
      'PS_CHANGE_COMB_NAME'        => 'COMB_ACTIONS',
      'PS_CHANGE_COORDS'           => 'COMB_ACTIONS',
      'PS_CHANGE_COORDS_MELS'      => 'COMB_ACTIONS',
      'PS_DELETE_INSTANCE'         => 'COMB_ACTIONS',
      'PS_DELETE_UNIQUE_BUILDING'  => 'COMB_ACTIONS', 
      'PS_DELYVERY_INSTANCE'       => 'COMB_ACTIONS',                                                       
      'PS_EXCHANGE_COORDS'         => 'COMB_ACTIONS',
      'PS_EXCHANGE_COORDS_MELS'    => 'COMB_ACTIONS',
      'PS_GET_LOG'                 => 'COMB_ACTIONS',
      'PS_GRANT_BUILDING'          => 'COMB_ACTIONS',
      'PS_RESERVE_COMB'            => 'COMB_ACTIONS',  
      );
         
      $res_types = array();
      $agregatedstat = array();
      $total = 0;
      $sc =  $gc = 0;
            foreach ($res as $key => $servise)
        {
         if (array_key_exists($servise['action_name'], $goodtypes))
         {
           $type  = $goodtypes[$servise['action_name']];
         }
         else 
         {
           $type = 'OTHER';
         }  
         
         if (!array_key_exists($type, $agregatedstat))
           {
              $agregatedstat[$type] = array();
              $agregatedstat[$type]['sv'] = array();
              $agregatedstat[$type]['sc'] = 0;
              $agregatedstat[$type]['gc'] = 0;
            $agregatedstat[$type]['cnt'] = 0;          

           }
         array_push($agregatedstat[$type]['sv'], $servise);
         
         $agregatedstat[$type]['sc'] +=   $servise['sc_price'];
         $agregatedstat[$type]['gc'] +=   $servise['gc_price'];
         $agregatedstat[$type]['cnt'] +=   $servise['count'];

                $total += $servise['count'];
                $sc += $servise['sc_price'];
                $gc += $servise['gc_price'];
                       
        }     
        
        ksort($agregatedstat); 
        foreach ($agregatedstat as $type => $group)
        {
          $cntproc = sprintf("%0.1f", 100*$group['cnt']/$total).'%';
          $scproc = sprintf("%0.1f", 100*$group['sc']/$sc).'%';
          $gcproc = sprintf("%0.1f", 100*$group['gc']/$gc).'%';

          echo "<tr>
          <td class=maintxt_med bgcolor=#444E6C align=right><b style='color:red'>$type</b></td> 
          <td class=maintxt_med bgcolor=#444E6C></td>
          <td class=maintxt_med bgcolor=#444E6C align='center'><span style='color:red'>{$group['cnt']} / ({$cntproc})</span></td>
          <td class=maintxt_med bgcolor=#444E6C align='center'><span style='color:red'>{$group['sc']}  / ({$scproc})</span> </td>
          <td class=maintxt_med bgcolor=#444E6C align='center'><span style='color:red'>{$group['gc']}  / ({$gcproc})</span></td>
          <td class=maintxt_med bgcolor=#444E6C></td>
          </tr>";
              foreach ($group['sv'] as $key => $servise)
              {
                 echo '
                        <tr>
                            <td align="left" class=maintxt_med bgcolor=#444E6C>
                                '.$servise['action_name'].'
                            </td>
                            <td align="left" class=maintxt_med bgcolor=#444E6C>
                                '.$servise['dbname'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$servise['count'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$servise['sc_price'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$servise['gc_price'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                <u><a target="_blank" href="?action=show_detail&service='.$servise['action_name'].$userData.'&dbname='.$servise['dbname'].'">Detail</a></u>
                            </td>
                        </tr>';
              }
        }
        
            echo '
                            
                        <tr>
                            <td align="left" class=maintxt_med bgcolor=#2A3E66>
                                Total:
                            </td>
                            <td align="left" class=maintxt_med bgcolor=#2A3E66>
                                &nbsp;
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                <span style="color:red">'.$total.'</span>
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                <span style="color:red">'.$sc.'</span>
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                <span style="color:red">'.$gc.'</span>
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                &nbsp;
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>   
        </table>
        ';
        }

        return;
    }

    
            
    private function _showDetail()
    {
        if (empty($this->action)) {
            echo '<font color="Red">WRONG SERVICE!!!!</font>';
            return;
        }

        if (empty($this->dbname)) {
            echo '<font color="Red">WRONG DBNAME!!!!</font>';
            return;
        }

        if (!empty($this->userName)) {
            $where = "  AND
                       u.username = '".$this->userName."'
                        AND
                       pu.is_initiator = 't' ";

            //Для ссылки
            $userData = '&userName='.$this->userName;
            $desc = 'User: '.$this->userName;
        }
        

        switch ($this->sortBy)
        {
            case 'service':
                $orderBy = " ORDER BY
                                p.action_name ";
                break;
            case 'user':
                $orderBy = " ORDER BY
                                u.username ";
                break;
            case 'SC':
                $orderBy = " ORDER BY
                                sc_price DESC ";
                break;
            case 'GC':
                $orderBy = " ORDER BY
                                gc_price DESC ";
                break;
            case 'date':
                $orderBy = " ORDER BY
                                p.paid_date DESC ";
            case 'detail':
                $orderBy = " ORDER BY
                                p.info DESC ";
                break;
            default:
                $orderBy = " ORDER BY
                            p.paid_date DESC";
                break;
        }

        $Id = 0;
        foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
        {
            if($this->dbname == $db['server'])
            {
                $Id = $dbId;
            }
        }
            
        $common = DS_Common::CreateForCurentDB($Id);

        $sql = "
            SELECT 
                *
            FROM 
                ds_paymentserviceslog p
            LEFT JOIN ds_paymentserviceslog_users pu ON (p.id_paymentserviceslog = pu.id_paymentserviceslog)
            LEFT JOIN ds_user u ON (pu.id_user = u.id_user)
            $join
            WHERE
                '".$_SESSION['dateFrom']."' < p.paid_date
                 AND
                p.paid_date < '".$_SESSION['dateTo']."'
                 AND
                p.action_name = '".$this->service."'
                $where
            $orderBy
            ";
        $res = $common->getResultArray("", $sql, "", "", "");

        assert(empty($common->errormsg));

        $sc = 0;
        $gc = 0;

        echo "<font color=#F8F8FF>Service: ".$this->service." ".$desc."</font><br>";

        echo
        '
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <tr class=th_2>
                            <th>
                                <a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBy=user" >User</a>
                            </th>
                            <th>
                                <a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBySC" >Silver Credits</a>
                            </th>
                            <th>
                                <a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBy=GC" >Gold Credits</a>
                            </th>
                            <th>
                                <a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBy=date" >Date</a>
                            </th>
                            <th>
                                Detail  
                            </th>
                        </tr>';
        foreach ($res as $key => $data)
        {
            echo '
                        <tr>
                            <td align="left" class=maintxt_med bgcolor=#444E6C>
                                '.$data['username'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$data['sc_price'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$data['gc_price'].'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#444E6C>
                                '.$data['paid_date'].'
                            </td>
                            <td align="left" class=maintxt_med bgcolor=#444E6C>
                                '.$data['info'].'
                            </td>
                        </tr>';
            $sc += $data['sc_price'];
            $gc += $data['gc_price'];
        }
        echo '
                        <tr>
                            <td align="left" class=maintxt_med bgcolor=#2A3E66>
                                Total:
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                '.$sc.'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                '.$gc.'
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                &nbsp;
                            </td>
                            <td align="center" class=maintxt_med bgcolor=#2A3E66>
                                &nbsp;
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>   
        </table>
        ';
    }

}

function cmp($a,$b) 
{
    global $orderBy;
    if ($orderBy == 'sc_price') {
        return ($a[$orderBy] < $b[$orderBy]) ? -1 : 1;
    }
    if ($orderBy == 'paid_date') {
        return (strtotime($a[$orderBy]) < strtotime($b[$orderBy])) ? -1 : 1;
    }
    return strcmp($a[$orderBy],$b[$orderBy]);
}

$s = new PaymentServices($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>