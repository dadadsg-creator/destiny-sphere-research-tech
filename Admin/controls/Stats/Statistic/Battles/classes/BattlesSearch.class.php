<?php

class BattlesSearch
{
    static public function GetStat($from, $to, $worlds, $detail)
    {
        $resArr = array();
        if (!$worlds) return $resArr;

        foreach ($worlds as $key => $worldArr)
        {
            $tempArr = explode('_', $worldArr);
            $dbID= $tempArr[0];
            $worldId = $tempArr[1];
            
            assert($dbID >= 0);
            assert($worldId >= 0);

            $common = DS_Common::CreateForCurentDB($dbID);
            
            $sql = self::_getSQL($from, $to, $worldId, $detail);
            
            $res = $common->getResultArray("", $sql, "", "", "");
        
            assert(empty($common->errormsg));
            
            //Получаем имя мира по его id
            $worldName  = self::GetWorldNameById($dbID, $worldId);
            assert(!empty($worldName));

            //Формируем дату
            foreach ($res as $k => $dateArr)
            {
                $date = self::_generateDate($dateArr, $detail);
                $resArr[$worldName][$date] = $dateArr['count'];
                $date = '';
            }
        }
        
        return $resArr;
    }
    
    private static function _formSQLPart($detail, $alias = false)
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
            $sql[$i] = "extract(".$dateParts[$i]." from b.battleenddate)";
            if($alias)
            {
                $sql[$i] .= ' as '.$dateParts[$i];
            }
        }

        return implode(', ', $sql);
    }


    static public function GetWorldList()
    {
        $common = DS_Common::CreateForCurentDB(0);

        $out = array();

        foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
        {
            $common->setDbId((int)$dbid);

            $sql = "SELECT id_world, worldname FROM ds_world";
            $res = $common->getResultArray('', $sql, '', '', '');
            assert(empty($common->errormsg));

            foreach($res as $k=>$v)
            {
                $keyRes = $dbid."_".$v['id_world'];
                $out[$keyRes] = $v['worldname'];
            }
        }
        
        return $out;
    }
    
    static public function GetWorldNameById($dbID, $worldId)
    {
        $common = DS_Common::CreateForCurentDB($dbID);
        
        $sql = "
            SELECT
                worldname
                FROM
                    ds_world
                WHERE
                 id_world = '$worldId'
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        
        return $res[0]['worldname'];
    }
        
    static private function _getSQL($from, $to, $world, $detail)
    {
        $sql = "
            
                        SELECT                              
                                ".self::_formSQLPart($detail, true).",
                                count(*) as count
                            FROM
                                ds_battle as b
                            LEFT JOIN ds_comb as c ON (c.id_comb = b.id_battlecomb)
                            LEFT JOIN ds_combcomplex as cc ON (cc.id_combcomplex = c.id_combcomplex)
                            LEFT JOIN ds_ring as r ON (r.id_ring = cc.id_ring)
                            WHERE
                                 b.battleenddate > '$from'
                                AND
                                 b.battleenddate < '$to'
                                AND
                                 r.id_world='$world'
                            GROUP BY
                                ".self::_formSQLPart($detail)."
                            ORDER BY
                                ".self::_formSQLPart($detail)."
                            ";
        return $sql;
    }
    
    static public function GetHeader($from, $to, $worlds, $detail)
    {

        $resArr = array();
        if (!$worlds) return $resArr;

        foreach ($worlds as $key => $worldArr)
        {
            $tempArr = explode('_', $worldArr);

            $dbId = $tempArr[0];
            $worldId = $tempArr[1];

            assert($dbId >= 0);
            assert($worldId >= 0);

            $common = DS_Common::CreateForCurentDB($dbId);

            $sql = self::_getSQL($from, $to, $worldId, $detail);

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
    
    static private  function _generateDate($dateArr, $detail)
    {
        assert(is_array($dateArr));

        $separator = '';
            
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
}
?>