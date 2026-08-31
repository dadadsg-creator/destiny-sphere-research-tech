<?php

class RegStatisticSearch
{
    static public function GetStatReg(GB_Date $from, GB_Date $to, $detail = '')
    {
        $common = DS_Common::CreateForCurentDB(0);

        $sql = self::_getSQLReg($from, $to, $detail, false);
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        $sql = self::_getSQLReg($from, $to, $detail, true);
        $res2 = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        $j = 0;
        for ($i=0; $i<count($res); $i++) {
            $noActive = false;
            foreach ($res[$i] as $key=>$val) {
                if ($key!='count' and $val!=$res2[$j][$key]) {
                    $noActive = true;
                    break;
                }
            }
            $count = $res[$i]['count'];
            unset($res[$i]['count']);
            if ($noActive) {
                $res[$i]['active'] = 0;
                $res[$i]['percent'] = '0.0%';
            } else {
                $res[$i]['active'] = $res2[$j]['count'];
                $res[$i]['percent'] = sprintf("%.1f%%", $res2[$j]['count'] / $count*100);
                $j++;
            }
            $res[$i]['count'] = $count;
        }

        return $res;
    }

    static public function GetRegMaxValue(GB_Date $from, GB_Date $to, $detail)
    {
        $common = DS_Common::CreateForCurentDB(0);

        $sql = "
        SELECT
            min(t.count) as min,
            max(t.count) as max
        FROM
            (
            ".self::_getSQLReg($from, $to, $detail)."
            ) as t
        ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        return $res[0];
    }

    static public function GetRegSum(GB_Date $from, GB_Date $to)
    {
        $common = DS_Common::CreateForCurentDB(0);

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_user
            WHERE
                registrationdate > '".$from->getPgFormat()."'
                AND
                registrationdate < '".$to->getPgFormat()."'
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function GetStatWorld(GB_Date $from, GB_Date $to, $world, $dbId, $detail)
    {
        $common = DS_Common::CreateForCurentDB($dbId);

        $sql = self::_getSQLWorld($from, $to, $world, $detail);

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetUsers(GB_Date $from, GB_Date $to, $pg = 1, $items = 20, $filter = '', $vis_date, $id_partner)
    {
        $common = DS_Common::CreateForCurentDB(0);

        $LIMIT = "";
        if (($pg > 0) && !$vis_date)
        {
            $offset = ($pg - 1)*$items;
            $LIMIT  = " LIMIT $items OFFSET $offset";
        }

        $filterstr = '';
        if ($filter)
        {
            $filter2 = iconv("cp1251", "UTF-8", $filter);
            $encfilter = urlencode($filter2);
            $filterstr = "AND
      (reg_referer SIMILAR TO '%({$filter})%'
      OR
      reg_referer SIMILAR TO '%({$encfilter})%')";
        }

        if ($id_partner > 0)
        $filterstr .= "AND from_partner={$id_partner}";

        $sql = "SELECT
                *
            FROM
                ds_user
            WHERE
                registrationdate > '".$from->getPgFormat()."'
                AND
                registrationdate < '".$to->getPgFormat()."'
        $filterstr
            ORDER BY id_user
        $LIMIT
            ";

        //echo $sql;
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        $sql = "SELECT count(id_user) as cnt FROM ds_user
            WHERE
                registrationdate > '".$from->getPgFormat()."'
                AND
                registrationdate < '".$to->getPgFormat()."'
        $filterstr";
        $cnt = $common->getResultArray("", $sql);

        $final_res = array();
        if ($vis_date)
        $tmvis = strtotime($vis_date);
        else
        $tmvis = time();

        $totalcnt = 0;
        $total_registered = 0;
        for($i=0;$i < count($res);$i++)
        {
            //Смотрим, в каких мирах зареган игрок
            $sql = "
                    SELECT
                        wo.worldname,
                        cn.combname,
                        ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                        fr.thefinal,
                        ui.creation_date,
                        --lr.lastrefresh as last_vizit
 (
                            SELECT
                              lastvisit
                            FROM
                                ds_loginhistory as l
                            WHERE
                                l.id_user = u.id_user
                            ORDER BY lastvisit DESC
                            LIMIT 1
                         ) as last_vizit                        
                    FROM
                        ds_user u
                        left outer join ds_userinstance ui ON (u.id_user = ui.id_user)
                        left outer join ds_world wo ON (wo.id_world = ui.id_world)
                        left outer join ds_comb co ON (co.id_userinstance = ui.id_userinstance)
                        LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                        LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                        left outer join ds_combname cn ON (co.id_comb = cn.id_comb)
                        LEFT JOIN ds_finalranking fr ON (fr.id_userinstance = ui.id_userinstance)
                    WHERE
                         u.id_user = {$res[$i]['id_user']}
                        AND
                         not wo.worldname is null";

            
            $userCombs = $common->getMultySql($sql);
            if ($i==0)
            {
              //SU_DebugTools::LogAll("$sql \n res=".var_export($userCombs, true),'ns.log'); 
            } 
            $maxtm = 0;
            if ($userCombs)
            {

                foreach ($userCombs as $u)
                {
                    $tm2 = strtotime($u['last_vizit']);
                    if ($tm2 > $maxtm)
                    {
                        $res[$i]['last_visit'] = $u['last_vizit'];
                        $res[$i]['rank'] = $u['thefinal'];
                        $maxtm = $tm2;
                    }
                }
            }
            else
            {
                $res[$i]['last_visit'] = '';
                $res[$i]['rank'] = '';
            }

            if ($vis_date)
            {
                $total_registered++;
                if ($maxtm >= $tmvis)
                {
                    $final_res[] = $res[$i];
                    $totalcnt++;
                }
            }
            else
            {
                $final_res[] = $res[$i];
            }
        }
        if (!$vis_date)
        {
            $total_registered = $totalcnt= $cnt[0]['cnt'];

        }
        return array($final_res, $totalcnt, $total_registered);
    }

    static public function GetStatWorldOut(GB_Date $from, GB_Date $to, $world, $dbId, $detail)
    {
        $common = DS_Common::CreateForCurentDB($dbId);

        $sql = self::_getSQLWorldOut($from, $to, $world, $detail);

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetWorldMaxValue(GB_Date $from, GB_Date $to, $world, $dbId, $detail)
    {
        $common = DS_Common::CreateForCurentDB($dbId);

        $sql = "SELECT
            min(t.count) as min,
            max(t.count) as max
        FROM
            (
            ".self::_getSQLWorld($from, $to, $world, $detail)."
            ) as t
        ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res[0];
    }

    static public function GetWorldOutMaxValue(GB_Date $from, GB_Date $to, $world, $dbId, $detail)
    {
        $common = DS_Common::CreateForCurentDB($dbId);

        $sql = "
        SELECT
            min(t.count) as min,
            max(t.count) as max
        FROM
            (
            ".self::_getSQLWorldOut($from, $to, $world, $detail)."
            ) as t
        ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res[0];
    }

    static public function GetWorldSum(GB_Date $from, GB_Date $to, $world, $dbId)
    {
        $common = DS_Common::CreateForCurentDB($dbId);

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_user_stat_in
            WHERE
                creation_date > '".$from->getPgFormat()."'
                AND
                creation_date < '".$to->getPgFormat()."'
                AND
                id_world = $world
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function GetWorldOutSum(GB_Date $from, GB_Date $to, $world, $dbId)
    {
        $common = DS_Common::CreateForCurentDB($dbId);

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_user_stat_out
            WHERE
                delete_date > '".$from->getPgFormat()."'
                AND
                delete_date < '".$to->getPgFormat()."'
                AND
                id_world = $world
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static private function _getSQLWorldOut(GB_Date $from, GB_Date $to, $world, $detail)
    {
        $sql = "
            SELECT
                ".self::_formSQLPart($detail, 'delete_date', true).",
                count(*) as count
            FROM
                ds_user_stat_out
            WHERE
                delete_date > '".$from->getPgFormat()."'
                AND
                delete_date < '".$to->getPgFormat()."'
                AND
                id_world = $world
            GROUP BY
                ".self::_formSQLPart($detail, 'delete_date')."
            ORDER BY
                ".self::_formSQLPart($detail, 'delete_date')."
            ";
        return $sql;
    }

    static private function _getSQLWorld(GB_Date $from, GB_Date $to, $world, $detail)
    {
        $sql = "
            SELECT
                ".self::_formSQLPart($detail, 'creation_date', true).",
                count(*) as count
            FROM
                ds_user_stat_in
            WHERE
                creation_date > '".$from->getPgFormat()."'
                AND
                creation_date < '".$to->getPgFormat()."'
                AND
                id_world = $world
            GROUP BY
                ".self::_formSQLPart($detail, 'creation_date')."
            ORDER BY
                ".self::_formSQLPart($detail, 'creation_date')."
            ";
        return $sql;
    }

    static private function _getSQLReg(GB_Date $from, GB_Date $to, $detail, $active=false)
    {
        if ($active) {
            return "
                SELECT
                    ".self::_formSQLPart($detail, 'registrationdate', true).",
                    count(*) as count
                FROM
                    ds_user u, ds_userinstancecollection uic
                WHERE
                    u.registrationdate > '".$from->getPgFormat()."'
                    AND
                    u.registrationdate < '".$to->getPgFormat()."'
                    and u.id_user=uic.id_user
                GROUP BY
                    ".self::_formSQLPart($detail, 'registrationdate')."
                ORDER BY
                    ".self::_formSQLPart($detail, 'registrationdate')."
                ";
        }
        else {
            return "
                SELECT
                    ".self::_formSQLPart($detail, 'registrationdate', true).",
                    count(*) as count
                FROM
                    ds_user
                WHERE
                    registrationdate > '".$from->getPgFormat()."'
                    AND
                    registrationdate < '".$to->getPgFormat()."'
                GROUP BY
                    ".self::_formSQLPart($detail, 'registrationdate')."
                ORDER BY
                    ".self::_formSQLPart($detail, 'registrationdate')."
                ";
        }
    }

    private function _formSQLPart($detail, $field, $alias = false)
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
            $sql[$i] = "extract(".$dateParts[$i]." from $field)";
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
        $sql = "
            SELECT
                dbname,
                id_world,
                worldname
            FROM
                ds_worldcollection
            WHERE
                world_identifier != 'UC'
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        $worlds = array();

        foreach($res as $k => $world)
        {
            foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
            {
                if($db['server'] == $world['dbname'])
                {
                    $worlds[$dbId.'_'.$world['id_world']] = $world['worldname'];
                    continue 2;
                }
            }
        }

        return $worlds;
    }
}
?>