<?
class GetDSArmy extends BaseDSMessage
{
	var $new = false;

	function GetDSArmy($new = false)
	{
		$this->BaseDSMessage();
		if ($new == 'new')
		{
			$this->new = true;
		}

	}


	function getText($uiid)
	{
/*
		if ($this->new === true)
		{
			
		} else {
			$result = $this->_getData($uiid);
		}*/
		
		$result = $this->_getDataNew($uiid);

		if ($result === FALSE)
		{
			return '{ARMIE_MSG_ERROR}';
		} else {
			return $this->_formText($result);
		}
	}

	function _formText($result)
	{

		if (sizeof($result) <= 0)
		{
			return '{ARMIE_MSG_NO_ATTACK}';
		}

		if (!isset($ret))
		{
			$ret = array();
		}


		foreach ($result as $value)
		{
			if (sizeof($ret) > 0 && key_exists($value['combto'], $ret))
			{
				$ret[$value['combto']]['count']++;
				continue;
			}

			//$gbdate = new GB_Date($value['eventendtime']);
			$gbdate = GB_TypeFactory::CreateBase('GB_Date', $value['eventendtime']);
			// время без учета таймдельты !!!
			//$time = $gbdate->getFormatedFull($GLOBALS['time_delta'] + constant('TIME_DELTA_DELTA'));
			$time = $gbdate->getFormatedFull();

			$array = array (
			'endtime' => $time,
			'attackername' => $value['attackername'],
			'count' => 1,
			);


			$ret[$value['combto']] = $array;
		}

		$text = '';
		foreach ($ret as $k => $v)
		{
			$a[] = $k.' ('.$v['count'].') '.$v['endtime'].' '.$v['attackername'];
		}

		$text = implode(',', $a);
		return $text;
	}



	function _getData($uiid)
	{
		$common = $this->getCommon();


		$sql = "
 SELECT DISTINCT
        eq.eventendtime,
    (
        select
                r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
        from
            ds_comb as c
            INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
            INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
        where
                c.id_comb =
                        (
                                SELECT
                                        eho1.value
                                FROM
                                ds_eventhasoption as eho1
                                INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                                WHERE
                                eho1.id_eventqueue = ep.id_eventqueue
                                AND
                                eo1.optionname = 'ID_CombTo'
                )
     ) AS combto,
     (
        SELECT
                r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
        FROM
            ds_comb as c
            INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
            INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
        WHERE
                c.id_comb =
                        (
                                SELECT
                                        eho1.value
                                FROM
                                ds_eventhasoption as eho1
                                INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                                WHERE
                                eho1.id_eventqueue = ep.id_eventqueue
                                AND
                                eo1.optionname = 'ID_CombFrom'
                )
    ) AS combfrom,
     (
        select
                u.username
        from
                        ds_user as u
            inner join ds_userinstance as ui on (u.id_user = ui.id_user)
       where
                ui.id_userinstance =
                        (
                                select
                                        c.id_userinstance
                                from
                                ds_comb as c
                                INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
                                INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
                                where c.id_comb =
                                        (
                                                SELECT eho1.value
                                                FROM
                                                ds_eventhasoption as eho1
                                                INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                                                WHERE
                                                eho1.id_eventqueue = ep.id_eventqueue
                                                AND
                                                eo1.optionname = 'ID_CombFrom'
                                )
              )

         ) AS attackername


 FROM
     ds_eventparticipant ep,
     ds_eventqueue eq,
     ds_event ev,
     ds_eventhasoption eho,
     ds_eventoption eo,
     ds_userinstance ui

 WHERE
      --ur.id_user = ui.id_user AND
      ep.id_userinstance = ui.id_userinstance AND
      ep.id_eventqueue = eq.id_eventqueue and
      ev.id_event = eq.id_event AND
      eho.id_eventqueue = eq.id_eventqueue and
      eho.id_eventoption = eo.id_eventoption
      AND ui.id_userinstance = ".$uiid."
      AND eq.istrash = 'f'
      -- AND ev.eventname = 'ARMY_MOVEMENT_TO'
      AND ev.eventname = 'ATTACK'
      AND eo.optionname = 'Stage'
      AND eho.value = '1'
      AND eq.eventendtime > now()
      AND (
              (
                  SELECT eho1.value
                  FROM
                     ds_eventhasoption as eho1
                     INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                  WHERE
                     eho1.id_eventqueue = ep.id_eventqueue
                     AND
                     eo1.optionname = 'ID_CombFrom'
              ) not in (
                          SELECT id_comb
                          FROM ds_comb
                          WHERE id_userinstance = ui.id_userinstance
                       )
          )
ORDER BY eq.eventendtime
		";

		$res = $common->getResultArray( "", $sql, "", "", "");
		if (!empty($common->errormsg))
		{
			echo $common->errormsg.' => '.$sql."\n";
			return false;
		}
		return $res;
	}

	function _getDataNew($uiid)
	{
		$common = $this->getCommon();

		$sql = "
 SELECT DISTINCT
        eq.eventendtime,
    (
        select
                r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
        from
            ds_comb as c
            INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
            INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
        where
                c.id_comb =
                        (
                                SELECT
                                        eho1.value
                                FROM
                                ds_eventhasoption as eho1
                                INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                                WHERE
                                eho1.id_eventqueue = ep.id_eventqueue
                                AND
                                eo1.optionname = 'ID_CombTo'
                )
     ) AS combto,
     (
        SELECT
                r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
        FROM
            ds_comb as c
            INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
            INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
        WHERE
                c.id_comb =
                        (
                                SELECT
                                        eho1.value
                                FROM
                                ds_eventhasoption as eho1
                                INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                                WHERE
                                eho1.id_eventqueue = ep.id_eventqueue
                                AND
                                eo1.optionname = 'ID_CombFrom'
                )
    ) AS combfrom,
     (
        select
                u.username
        from
                        ds_user as u
            inner join ds_userinstance as ui on (u.id_user = ui.id_user)
       where
                ui.id_userinstance =
                        (
                                select
                                        c.id_userinstance
                                from
                                ds_comb as c
                                INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
                                INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
                                where c.id_comb =
                                        (
                                                SELECT eho1.value
                                                FROM
                                                ds_eventhasoption as eho1
                                                INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                                                WHERE
                                                eho1.id_eventqueue = ep.id_eventqueue
                                                AND
                                                eo1.optionname = 'ID_CombFrom'
                                )
              )

         ) AS attackername


 FROM
     ds_eventparticipant ep,
     ds_eventqueue eq,
     ds_event ev,
     ds_eventhasoption eho,
     ds_eventoption eo,
     ds_userinstance ui

 WHERE
      --ur.id_user = ui.id_user AND
      ep.id_userinstance = ui.id_userinstance AND
      ep.id_eventqueue = eq.id_eventqueue and
      ev.id_event = eq.id_event AND
      eho.id_eventqueue = eq.id_eventqueue and
      eho.id_eventoption = eo.id_eventoption
      AND ui.id_userinstance = ".$uiid."
      AND eq.istrash = 'f'
      AND ev.eventname = 'ARMY_MOVEMENT_TO'
      --AND ev.eventname = 'ATTACK'
      --AND eo.optionname = 'Stage'
      --AND eho.value = '1'
      AND eq.eventendtime > now()
      AND (
              (
                  SELECT eho1.value
                  FROM
                     ds_eventhasoption as eho1
                     INNER JOIN ds_eventoption as eo1 on (eo1.id_eventoption = eho1.id_eventoption)
                  WHERE
                     eho1.id_eventqueue = ep.id_eventqueue
                     AND
                     eo1.optionname = 'ID_CombFrom'
              ) not in (
                          SELECT id_comb
                          FROM ds_comb
                          WHERE id_userinstance = ui.id_userinstance
                       )
          )
ORDER BY eq.eventendtime
		";

		$res = $common->getResultArray( "", $sql, "", "", "");
		if (!empty($common->errormsg))
		{
			echo $common->errormsg.' => '.$sql."\n";
			return false;
		}
		return $res;
	}


}

?>