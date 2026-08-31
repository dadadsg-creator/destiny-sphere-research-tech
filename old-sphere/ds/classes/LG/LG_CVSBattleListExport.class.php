<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2008/12/25 10:02:13 $';
/**
 * √ейт анализатора.
 * 
 * —тади€ 1: идет запрос, которому возвращаетс€ csv с данными:
 * 
 * - ид лога
 * - координаты соты с боем
 * - врем€  начала бо€
 * - врем€ конца бо€
 * - раунды
 * - атакер
 * - защищающийс€
 * - победивша€ сторона
 * 
 * - разница потерь обоих сторон, выгода дл€ игрока
 * - остаток на соте в проде
 * 
 * - stolen в формате кад|нана|прод|еда|стата| 
 * 
 */

class LG_CVSBattleListExport extends LG_Base 
{
	/**
	 * Ќачальна€ дата
	 *
	 * @var GB_Date
	 */
	static private $from;
	
	/**
	 *  онечна€ дата
	 *
	 * @var GB_Date
	 */
	static private $to;
	
	/**
	 * —писок боев
	 *
	 * @var array
	 */
	static private $battleList;
	
	/**
	 * ѕользователь
	 *
	 * @var DE_User
	 */
	static private $user;
	
	/**
	 * ћаасив с бо€ми, которые не надо выводить 
	 *
	 * @var array
	 */
	static private $exlid = array();
	
	/**
	 * ≈кспорт боев игрока за указанный промежуток времени
	 *
	 * @param timestamp(int) $from
	 * @param timestamp(int) $to
	 * @param string(3) $world
	 * @param string $nick
	 */
	static public function Export($from, $to, $world, $nick, $exlid = array())
	{
		assert(is_int($from) && $from > 0);
		assert(is_int($to) && $to > 0);
		assert(ereg("[A-Z]{1,3}", $world));
		assert(!empty($nick));
		//assert(is_array($exlid));

		self::$from = GB_TypeFactory::CreateBase('GB_Date', $from);
		self::$to = GB_TypeFactory::CreateBase('GB_Date', $to);	
		self::$world = $world;
		self::$exlid = $exlid;
		
		self::_getCommon();
		
		self::_getUser($nick);
		
		self::_getBattleList();
		
		self::_getArmiesWeights();
		
		self::_getStolenResources();
		
		self::_exportData();
	}
	
	/**
	 * ѕолучение данных пользовател€ по нику
	 *
	 * @param unknown_type $nick
	 */
	static private function _getUser($nick)
	{
		assert(!empty($nick));
		
		assert(VD_UserName::Validate($nick));
		
		$uid = DS_User::GetUserIDByName($nick);
		
		assert($uid > 0);
		
		self::$user = DE_User::CreateFromID($uid);
	}
	
	/**
	 * - разница потерь обоих сторон (от защиты отнимаетс€ атакующий)
	 * - остаток на соте в проде
	 * 
	 */
	static private function _getArmiesWeights()
	{
		
		foreach (self::$battleList as $k => $battle)
		{
			$atackerStartWeight = self::_getArmyWeightInRound((int)$battle['id_battle'], 0, 'A');
			$atackerEndWeight = self::_getArmyWeightInRound((int)$battle['id_battle'], (int)$battle['count_rounds'], 'A');
			$defenderStartWeight = self::_getArmyWeightInRound((int)$battle['id_battle'], 0, 'D');
			$defenderEndWeight = self::_getArmyWeightInRound((int)$battle['id_battle'], (int)$battle['count_rounds'], 'D');
			
			$atackerLoses = $atackerStartWeight - $atackerEndWeight;
			$defenderLoses = $defenderStartWeight - $defenderEndWeight;
			
			if($battle['atacker'] == self::$user->getName())
			{
				if((int)$battle['count_rounds'] == 1 && $atackerEndWeight == 0 && !self::_getFullLogModifier())
				{
					self::$battleList[$k]['loses_diff'] = -round(floatval($atackerLoses));
					self::$battleList[$k]['defender_rest'] = 0;
				} else {
					self::$battleList[$k]['loses_diff'] = round(floatval($defenderLoses - $atackerLoses));
					self::$battleList[$k]['defender_rest'] = round(floatval($defenderEndWeight));
				}
			} else {
				self::$battleList[$k]['loses_diff'] = round(floatval($atackerLoses - $defenderLoses));
				self::$battleList[$k]['defender_rest'] = round(floatval($atackerEndWeight));
			}
			
			/*
			// это дл€ отладки доп. расчеты весов
			self::$battleList[$k]['defender_loses'] = self::_getSideLosesInBattle((int)$battle['id_battle'], 'D');
			self::$battleList[$k]['attacker_loses'] = self::_getSideLosesInBattle((int)$battle['id_battle'], 'A');
			self::$battleList[$k]['defender_start'] = self::_getArmyWeightInRound((int)$battle['id_battle'], 0, 'D');
			self::$battleList[$k]['defender_end'] = self::_getArmyWeightInRound((int)$battle['id_battle'], (int)$battle['count_rounds'], 'D');
			self::$battleList[$k]['attacker_start'] = self::_getArmyWeightInRound((int)$battle['id_battle'], 0, 'A');
			self::$battleList[$k]['attacker_end'] = self::_getArmyWeightInRound((int)$battle['id_battle'], (int)$battle['count_rounds'], 'A');
			*/
		}
	}
	
	
	/**
	 * –азрешен ли полный лог
	 *
	 * @return unknown
	 */
	static private function _getFullLogModifier()
	{
		$sql = "
			SELECT
				value
			FROM
				ds_gamemodifier
			WHERE
				modifiername = 'BATTLE_ONE_ROUND_DEATH_LOG'
				AND
				id_world = ".self::$worldID."
			";
		$res = self::$common->getResultArray(__METHOD__.'_'.self::$worldID, $sql, "", "", "GLOBAL_CACHE");
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 0)
		{
			return 1;
		}
		
		return (int)$res[0]['value'];
	}
	
	
	/**
	 * - stolen в формате кад|нана|прод|еда|стата| 
	 *
	 */
	static private function _getStolenResources()
	{
		foreach (self::$battleList as $k => $battle)
		{
			if($battle['winner'] == 'A')
			{
				$res = self::_getBattleAttributes((int)$battle['id_battle']);
				if(count($res) == 0)
				{
					self::$battleList[$k]['stolen'] = "0|0|0|0|0";
					continue;
				}
				
				self::$battleList[$k]['stolen'] = $res['KADERIUM']."|".$res['NANO_CRYSTAL']."|".$res['PRODIUM']."|".$res['FOOD']."|".$res['STASIS_ENERGY'];
			} else {
				self::$battleList[$k]['stolen'] = "0|0|0|0|0";
			}
		}
	}
	
	/**
	 * ¬ывод результата
	 *
	 */
	static private function _exportData()
	{
		$str = '';
		
		foreach (self::$battleList as $k => $battle)
		{
			$str = implode(';', $battle);
			$str .= "\n";
			echo $str;
		}
	}
	
	/**
	 * ѕолучение списка боев из базы
	 *
	 */
	static private function _getBattleList()
	{
		$whereBattleNotIn =  '';

		if (count(self::$exlid) > 0) {
		
			$whereBattleNotIn = 'AND b.id_battle NOT IN (';
			$comma = '';
			
			foreach (self::$exlid as $key => $data)
			{
				$whereBattleNotIn .= $comma.$data;
				$comma = ',';
			}
			
			$whereBattleNotIn .=  ")"; 
		}		
		
		$sql = "
			SELECT
				b.id_battle,
				
				-- сота, на которой был бой
				(
					SELECT
						r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
					FROM
						ds_comb AS c
						INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
						INNER JOIN ds_ring AS r USING (id_ring)
					WHERE
						c.id_comb = b.id_battlecomb						
				) as battle_comb,
				
				b.battlestartdate,
				b.battleenddate,

				-- кол-во раундов
				(
        			SELECT
            			count(*) - 1
        			FROM
            			ds_battleround AS br
        			WHERE
            			br.id_battle = b.id_battle
				) AS count_rounds,
		
				-- атакер
			    (
        			ARRAY_TO_STRING
        			(
            			ARRAY
            			(
                			SELECT
                    			ua.username
                			FROM
                    			ds_battleparticipant AS bpa
                    			INNER JOIN ds_userinstance AS uia USING (id_userinstance)
                    			INNER JOIN ds_user AS ua USING (id_user)
                			WHERE
                    			bpa.id_battle = b.id_battle
                    			AND
                    			bpa.participantflag = 'A'
            			),
            			', '
        			)
    			) AS atacker,
				
				-- список защитников
				(
        			ARRAY_TO_STRING
        			(
            			ARRAY
            			(
                			SELECT
                    			ud.username
                			FROM
                			    ds_battleparticipant AS bpd
                    			INNER JOIN ds_userinstance AS uid USING (id_userinstance)
                    			INNER JOIN ds_user AS ud USING (id_user)
                			WHERE
                    			bpd.id_battle = b.id_battle
                    			AND
                    			bpd.participantflag = 'D'
                    			AND
                    			bpd.iscombowner
            			),
            			', '
        			)
    			) AS defender,
    			
    			b.winner
    			
			FROM
				ds_battle AS b
				INNER JOIN ds_battleparticipant AS bp USING (id_battle)
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
			WHERE
				b.battlestartdate > '".self::$from->getPgFormat()."'
				AND
				b.battleenddate < '".self::$to->getPgFormat()."'
				AND
				ui.id_user = ".self::$user->getID()."
				AND
				ui.id_world = ".self::$worldID."
				$whereBattleNotIn
			ORDER BY b.battlestartdate
			";
		self::$battleList = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
	}
	
	/**
	 * ¬ыборка весов армии, одной из воевавших сторон, (в продиуме!) в конкретном раунде
	 *
	 * @param int $battleID
	 * @param int $round
	 * @param char $side
	 * @return float
	 */
	static private function _getArmyWeightInRound($battleID, $roundNumber, $side)
	{
		assert(is_int($battleID) && $battleID > 0);
		assert(is_int($roundNumber) && $roundNumber >= 0);
		assert($side == 'A' || $side == 'D');
		
		$sql = "
			SELECT
            	sum(uha.value * r.exchangerate * ceil(brs.amount)) * 0.1 AS sumtotal -- 0,1 - prodium
            FROM
   			    ds_battleparticipant AS bpd
 			    INNER JOIN ds_battleround AS br USING (id_battle)
  			    INNER JOIN ds_battleresult AS brs ON (brs.id_battleround = br.id_battleround AND brs.id_battleparticipant = bpd.id_battleparticipant)
   			    INNER JOIN ds_unithasattribute AS uha ON (uha.id_unit = brs.id_unit)
	            INNER JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
	            INNER JOIN ds_attributetype AS at ON (at.id_attributetype = a.id_attributetype)
                INNER JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
  			WHERE
       			bpd.id_battle = $battleID
       			AND
       			bpd.participantflag = '$side'
       			AND
       			br.roundnumber = $roundNumber
		       	AND
       			at.typename = 'PRODUCTION_COSTS'
		";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res[0]['sumtotal'];
	}
	
	/**
	 * —уммарные потери стороны в бою
	 *
	 * @param itn $battleID
	 * @param char $side
	 * @return float
	 */
	static private function _getSideLosesInBattle($battleID, $side)
	{
		assert(is_int($battleID) && $battleID > 0);
		assert($side == 'A' || $side == 'D');
		
		$sql = "
			SELECT
            	sum(uha.value * r.exchangerate * brs.loses) * 0.1 AS sumtotal
            FROM
   			    ds_battleparticipant AS bpd
 			    INNER JOIN ds_battleround AS br USING (id_battle)
  			    INNER JOIN ds_battleresult AS brs ON (brs.id_battleround = br.id_battleround AND brs.id_battleparticipant = bpd.id_battleparticipant)
   			    INNER JOIN ds_unithasattribute AS uha ON (uha.id_unit = brs.id_unit)
	            INNER JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
	            INNER JOIN ds_attributetype AS at ON (at.id_attributetype = a.id_attributetype)
                INNER JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
  			WHERE
       			bpd.id_battle = $battleID
       			AND
       			bpd.participantflag = '$side'
		       	AND
       			at.typename = 'PRODUCTION_COSTS'
		";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res[0]['sumtotal'];
	}
	
	/**
	 * ¬ыборка атрибутов бо€ (сворованные ресурсы)
	 *
	 * @param int $battleID
	 * @return array
	 */
	static private function _getBattleAttributes($battleID)
	{
		assert(is_int($battleID) && $battleID > 0);
		
		$sql = "
			SELECT
				bha.value,
				an.attributename
			FROM
				ds_battlehasattribute AS bha
				INNER JOIN ds_attribute AS a USING (id_attribute)
				INNER JOIN ds_attributename AS an USING (id_attributename)
			WHERE
				bha.id_battle = ".$battleID."
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		$arr = array('KADERIUM' => 0, 'PRODIUM' => 0, 'NANO_CRYSTAL' => 0, 'FOOD' => 0, 'STASIS_ENERGY' => 0);
			
		foreach ($res as $k => $r)
		{
			$arr[$r['attributename']] = (int)$r['value'];
		}
		
		return $arr;
	}
}

?>