<?php

class RegStatisticController
{
    private $detail = 0;
    
    private $action = 'index';
    
    private $dateFromArr = array();
    
    private $dateFrom;
    
    private $dateToArr = array();
    
    private $dateTo;
    
    private $type = 'user_reg';
    
    private $world;
    
    private $dbId;
    
    public function __construct(array $data)
    {
    $this->_getParams($data);
        
        switch ($this->action)
        {
            case 'index':
                $this->_showIndex();
                break;
                
            case 'show_stat':
                $this->_showStat();
                break;

      case 'csv':
                $this->_csv();
                break;
    }
    }
    
  private function _csv()
  {
     
     list($users, $totalcnt) = RegStatisticSearch::GetUsers($this->dateFrom, $this->dateTo, 0, 20, $this->filter, $this->vis_date);

     $str = '"ID"|"Íèê"|"Äàòà ğåãèñòğàöèè"|"Àêòèâèğîâàí"|"Îòêóäà ïğèø¸ë"'."\n";

     foreach($users as $u)
     {
       $arr =  explode('.', $u['registrationdate']);
       $d1 =  $arr[0];
       $str .= "{$u['id_user']}|{$u['username']}|{$d1}|{$u['activate_mail']}|{$u['reg_referer']}\n";
     }
     header("Content-Disposition: attachment; filename=userstat.csv");
     echo $str;
  }
    
  private function _showIndex()
    {
        $worlds = RegStatisticSearch::GetWorldList();
        RegStatisticOut::OutIndex($worlds, $this->type, $this->detail, $this->dateFromArr, $this->dateToArr, $this->dbId.'_'.$this->world, $this->filter,  $this->vis_date, $this->id_partner);
    }
    
    private function _showStat()
    {
        $this->_showIndex();

        switch ($this->type) {
            case 0:
        if ($this->detail == 5)
        {
          list($users, $totalcnt, $registered) = RegStatisticSearch::GetUsers($this->dateFrom, $this->dateTo, $this->pg , $this->num_items, $this->filter, $this->vis_date, $this->id_partner);
        }
        else
        {
                  $stat = RegStatisticSearch::GetStatReg($this->dateFrom, $this->dateTo, $this->detail);
                  $min_max = RegStatisticSearch::GetRegMaxValue($this->dateFrom, $this->dateTo, $this->detail);
                  $total = RegStatisticSearch::GetRegSum($this->dateFrom, $this->dateTo);
        }
                break;
            
            case 1:
                $stat = RegStatisticSearch::GetStatWorld($this->dateFrom, $this->dateTo, $this->world, $this->dbId, $this->detail);
                $min_max = RegStatisticSearch::GetWorldMaxValue($this->dateFrom, $this->dateTo, $this->world, $this->dbId, $this->detail);
                $total = RegStatisticSearch::GetWorldSum($this->dateFrom, $this->dateTo, $this->world, $this->dbId);
                break;
                
            case 2:
                $stat = RegStatisticSearch::GetStatWorldOut($this->dateFrom, $this->dateTo, $this->world, $this->dbId, $this->detail);
                $min_max = RegStatisticSearch::GetWorldOutMaxValue($this->dateFrom, $this->dateTo, $this->world, $this->dbId, $this->detail);
                $total = RegStatisticSearch::GetWorldOutSum($this->dateFrom, $this->dateTo, $this->world, $this->dbId);
                break;
        }
        //echo "ID_PARTNER=".$this->id_partner."<br>";
    if ($this->detail==5)
        RegStatisticOut::OutUserStat($users, $this->pg, $totalcnt, $registered, $this->num_items, $this->dateFrom, $this->dateTo, $this->filter, $this->vis_date, $this->id_partner);
    else
        RegStatisticOut::OutStat($stat, $min_max, $total);
  }
    
    private function _getParams(array $data)
    {
        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
        }
        if(key_exists('acsv', $data))
        {
      if ($data['acsv'])
        $this->action = $data['acsv'];
    }

        if(key_exists('detail', $data))
        {
            $this->detail = $data['detail'];
        }
        
        if(key_exists('dateFrom', $data))
        {
            //assert(is_array($data['dateFrom']));
      if (is_array($data['dateFrom']))
      {
            $dateFrom = $data['dateFrom'][0].'-';
            $dateFrom .= $data['dateFrom'][1].'-';
            $dateFrom .= $data['dateFrom'][2].' ';
              $dateFrom .= $data['dateFrom'][3].':00:00';
            $this->dateFromArr = $data['dateFrom'];
      }
      else
      {
        $dateFrom = $data['dateFrom'];
        list($dt, $tm) = explode(' ', $dateFrom);
        $this->dateFromArr = explode('-', $dt);
        $tmr = explode(':',$tm);
        array_push($this->dateFromArr, $tmr[0]);
      }

            $this->dateFrom = GB_TypeFactory::CreateBase('GB_Date', $dateFrom);
        }
        
        if(key_exists('dateTo', $data))
        {
            //if assert(is_array($data['dateTo']));
      if (is_array($data['dateTo']))
      {
              $dateTo = $data['dateTo'][0].'-';
              $dateTo .= $data['dateTo'][1].'-';
              $dateTo .= $data['dateTo'][2].' ';
              $dateTo .= $data['dateTo'][3].':00:00';
            $this->dateToArr = $data['dateTo'];
      }
      else
      {
        $dateTo = $data['dateTo'];
        list($dt, $tm) = explode(' ', $dateTo);
        $this->dateToArr = explode('-', $dt);
        $tmr = explode(':',$tm);
        array_push($this->dateToArr, $tmr[0]);
      }

            $this->dateTo = GB_TypeFactory::CreateBase('GB_Date', $dateTo);
        }
        
        if(key_exists('type', $data))
        {
            $this->type = $data['type'];
        }
        
        if(key_exists('world', $data))
        {
            $arr = explode('_', $data['world']);
            $this->dbId = (int)$arr[0];
            assert($this->dbId >= 0);
            $this->world = (int)$arr[1];
            assert($this->world > 0);
        }

        if(key_exists('pg', $data))
      $this->pg = $data['pg'];
    else
      $this->pg = 1;

    if(key_exists('num_items', $data))
      $this->num_items = $data['num_items'];
    else
      $this->num_items = 20;

    if(key_exists('filterword', $data))
      $this->filter = trim(urldecode($data['filterword']));
    else
      $this->filter = '';

    if(key_exists('vis_date', $data))
    {
      $this->vis_date = trim($data['vis_date']);
    }
    else
    {
      $this->vis_date = date("Y-m-d");
    } 
    
    if(key_exists('id_partner', $data))
    {
      $this->id_partner = trim($data['id_partner']);
    }
    else
    {
      $this->id_partner = 0;
    }     
  }
}

?>