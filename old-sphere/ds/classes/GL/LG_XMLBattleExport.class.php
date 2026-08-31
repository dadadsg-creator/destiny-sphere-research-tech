<?php $CVS_Version = '$Revision: 1.13 $ ; $Date: 2010/07/09 10:00:58 $';

/**
 * Гейт анализатора.
 * Експорт боев пользователя в формате XML
 *
 */
class LG_XMLBattleExport extends LG_Base
{
	/**
	 * Ограничение на кол-ва отдаваемых за раз боев
	 *
	 * @var int
	 */
	static private $battleLimit;

	//static private $battleDate;

	/**
	 * XML документ
	 *
	 * @var domXML
	 */
	static private $doc;

	/**
	 * Корневой элемент XML
	 *
	 * @var nodeXML
	 */
	static private $root;

	/**
	 * Если был передан $battleID, то будет экспортирован только этот бой
	 *
	 * @var int
	 */
	static private $battleID;

	/**
	 * Код стороны
	 *
	 * @var char (A, D)
	 */
	static private $participantCode;

	/**
	 * Код победителя
	 *
	 * @var char (A, D)
	 */
	static private $winner;

	/**
	 * ID пользователя
	 *
	 * @var int
	 */
	static private $userID;

	/**
	 * ds_battleparticipant.id_battleparticipant
	 *
	 * @var int
	 */
	static private $battleParticipantID;

	/**
	 * Enter description here...
	 *
	 * @var bool
	 */
	static private $fullLog = true;

	/**
	 * Армии, которые ропускаем в результатах боя, если включен шпионаж
	 *
	 * @var array
	 */
	static private $disabledArmies = array();

	/**
	 * Експорт
	 *
	 * @param string $userName
	 * @param int $battleDate
	 * @param string(3) $world
	 * @param int || null $battleID
	 */

	static private $enc;
	/**
	 * Исходная кодировка
	 *
	 * 
	 */
	static public function Export($userName, $battleDate, $world, $battleID = null, $type = 'xml')
	{
		assert(is_int($battleDate) && $battleDate >= 0);
		assert(ereg("[A-Z]{1,3}", $world));
		assert(is_int($battleID) || is_null($battleID));
		assert($type == 'txt' || $type == 'xml');

		if(isset($_SESSION['lang']))
		{
			$langID = $_SESSION['lang'];
		}else{
			$langID = 3;
		}


		switch($langID)
		{
			case 1:
				self::$enc = 'cp1250';
				break;
			case 2:
				self::$enc = 'cp1250';
				break;
			case 3:
				self::$enc = 'cp1251';
				break;
			case 5: // Литва
			self::$enc = 'cp1257';
			break;
			default:
				self::$enc = 'cp1251';

		}

		self::$battleLimit = (int)DE_GlobalSettings::Get('ANALIZATOR_BATTLE_LOGS_LIMIT', true);
		if(!self::$battleLimit)
		{
			self::$battleLimit = 60;
		}

		self::$battleID = $battleID;

		self::$world = $world;

		self::_getCommon();

		assert(VD_UserName::Validate($userName, false, self::$common));

		$uid = DS_User::GetUserIDByName($userName);
		assert($uid);

		self::$userID = $uid;

		$xml = '<?xml version="1.0" encoding="utf-8"?><userbattles user="'.$userName.'" world="'.$world.'"'."/>\n";
		self::$doc = domxml_open_mem($xml);
		$root = self::$doc->document_element();

		$battles = self::_getUserBattles((int)$uid, $battleDate);

		foreach ($battles as $k => $battle)
		{
			$ch = $root->new_child('battle');

			self::$root = $ch;
			self::$battleID = (int)$battle['id_battle'];
			self::$battleParticipantID = (int)$battle['id_battleparticipant'];
			self::$participantCode = $battle['participantflag'];
			self::$fullLog = true;

			if($type == 'txt')
			{
				self::_sendBattleReport();
				return;
			}

			self::_exportBattleInfo();
			self::_exportParticipants();
			self::_exportBattleRounds();
			self::_exportStolenResources();
		}

		$res = self::$doc->dump_mem();

		echo str_replace(">", ">\n", $res);
	}

	static private function _invertParticipantCode($code)
	{
		assert($code == 'A' || $code == 'D');
		if($code == 'A')
		{
			return 'D';
		} else {
			return 'A';
		}
	}

	static private function _sendBattleReport()
	{
		echo self::_getBattleReport(self::$battleID);
	}

	/**
	 * Заголовок боя (кто сколько опыта получил)
	 * отдельно нигде не храниться опыт за бой,
	 * поэтому отправляем кусок лога с нужной информацией 
	 *
	 * @return bool
	 */
	static private function _exportBattleInfo()
	{
		$battleInfo = self::_getBattleInfo();

		if(count($battleInfo) == 0)
		{
			return false;
		}

		foreach ($battleInfo as $attr => $value)
		{
			//self::$root->set_attribute($attr, $value);
			self::$root->set_attribute_without_urldecode($attr, $value);
		}

		$ch = self::$root->new_child('battle_log');

		$ch2 = self::$doc->create_text_node(self::_getBattleLogHead(self::$battleID));

		$ch->append_child($ch2);

		return true;
	}

	/**
	 * Список участников
	 *
	 */
	static private function _exportParticipants()
	{
		$participants = self::_getBattleParticipants();

		foreach($participants as $k => $participant)
		{
			$ch = self::$root->new_child('participant');

			foreach ($participant as $attr => $val)
			{
				$ch->set_attribute($attr, $val);
			}

			self::_exportParticipantArmys($ch, (int)$participant['id_battleparticipant']);
		}
	}

	/**
	 * Список армий
	 *
	 * @param xmlNode $xml_node
	 * @param int $battleParticipantID
	 */
	static private function _exportParticipantArmys($xml_node, $battleParticipantID)
	{
		$participantArmys = self::_getBattleParticipantArmys($battleParticipantID);

		foreach ($participantArmys as $k => $participantArmy)
		{
			$ch = $xml_node->new_child('army');

			$participantArmy['unitcontainername'] = iconv(self::$enc, 'utf-8', $participantArmy['unitcontainername']);

			foreach ($participantArmy as $attr => $val)
			{
				if(!self::_getFullLogModifier() && $battleParticipantID == self::$battleParticipantID && self::$participantCode == 'A' && $attr == 'dieafterround' && $val == '1')
				{
					self::$fullLog = false;
				}

				if($battleParticipantID != self::$battleParticipantID && $attr == 'id')
				{
					self::$disabledArmies[] = (int)$val;
				}

				//$ch->set_attribute($attr, $val);
				$ch->set_attribute_without_urldecode($attr, $val);
			}
		}
	}

	/**
	 * Раунды боя
	 *
	 */
	static private function _exportBattleRounds()
	{
		$rounds = self::_getBattleRounds();

		foreach($rounds as $k => $round)
		{
			$ch = self::$root->new_child('battleround');

			foreach($round as $attr => $val)
			{
				//$ch->set_attribute($attr, $val);
				$ch->set_attribute_without_urldecode($attr, $val);
			}

			self::_exportRoundResults($ch, (int)$round['id_battleround']);
		}
	}

	/**
	 * Результаты раунда (потери)
	 *
	 * @param xmlNode $xml_node
	 * @param unknown_type $battleRoundID
	 */
	static public function _exportRoundResults($xml_node, $battleRoundID)
	{
		$roundResults = self::_getBattleRoundResults($battleRoundID);

		foreach ($roundResults as $k => $roundResult)
		{
			foreach ($roundResult as $attr => $val)
			{
				if(!self::$fullLog && $attr == 'id_army' && in_array((int)$val, self::$disabledArmies))
				{
					continue 2;
				}
			}

			$ch = $xml_node->new_child('result');

			foreach($roundResult as $attr => $val)
			{
				$ch->set_attribute($attr, $val);
			}
		}
	}

	/**
	 * Сворованные ресурсы
	 *
	 */
	static private function _exportStolenResources()
	{
		$resources = self::_getBattleStolenResources();

		$ch = self::$root->new_child('stolen');

		foreach($resources as $k => $res)
		{
			$ch2 = $ch->new_child('resource');

			foreach($res as $attr => $val)
			{
				$ch2->set_attribute($attr, $val);
			}
		}
	}

	/**
	 * Разрешен ли полный лог
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
	 * Выборка списка боев
	 *
	 * @param int $uid
	 * @param int $battleDate
	 * @return array
	 */
	static private function _getUserBattles($uid, $battleDate)
	{
		if($battleDate > 0)
		{
			$where = " AND b.battleenddate > '".GB_TypeFactory::CreateBase('GB_Date', $battleDate)->getPgFormat()."'";
		} elseif(!is_null(self::$battleID)) {
			$where = " AND b.id_battle = ".self::$battleID;
		} else {
			$where = "";
		}

		$sql = "
			SELECT
				DISTINCT b.id_battle,
				b.battleenddate,
				bp.participantflag,
				bp.iswinner,
				bp.id_battleparticipant
			FROM
				ds_battleparticipant AS bp
				INNER JOIN ds_battle AS b USING(id_battle)
				INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = bp.id_userinstance)
			WHERE
				ui.id_user = $uid
				AND
				ui.id_world = ".self::$worldID."
				".$where."
				AND
				NOT b.battleenddate IS NULL
			ORDER BY b.battleenddate ASC
			LIMIT ".self::$battleLimit."
			";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	/**
	 * Выборка общей информации о бое (кто, где, когда)
	 *
	 * @return unknown
	 */
	static private function _getBattleInfo()
	{
		$sql = "
			SELECT
    			b.id_battle,

    			(
        			SELECT
            			w.world_identifier
        			FROM
            			ds_comb AS c
            			INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
            			INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
            			INNER JOIN ds_world AS w ON (r.id_world = w.id_world)
        			WHERE
            			c.id_comb = b.id_battlecomb
    			) as world,
    
			    (
        			SELECT
            			r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
        			FROM
            			ds_comb AS c
            			INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
            			INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
        			WHERE
            			c.id_comb = b.id_battlecomb
    			) as oncomb,
    
    			(
        			SELECT
            			r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
        			FROM
            			ds_comb AS c
            			INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
            			INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
        			WHERE
            			c.id_comb = b.id_attackercomb
    			) as fromcomb,
    
			    b.winner,
    
			    b.battlestartdate as startdate,
    
    			b.battleenddate as enddate,
    
				ber.reasonname as endreason

			FROM
    			ds_battle AS b
    			INNER JOIN ds_battleendreasons AS ber ON (ber.id_battleendreason = b.id_battleendreason)
			WHERE
    			b.id_battle = ".self::$battleID."
		";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		if(count($res) == 0)
		{
			return array();
		}

		return $res[0];
	}

	/**
	 * Выборка участников боя 
	 *
	 * @return array
	 */
	static private function _getBattleParticipants()
	{
		$sql = "
			SELECT
				bp.id_battleparticipant,
				
    			COALESCE(u.username, '[DELETED]') as user,
    
    			bp.participantflag as flag,
    
    			CASE
        			WHEN bp.iswinner THEN 1 
        			ELSE 0
    			END as winner,
    
    			CASE
        			WHEN bp.iscombowner THEN 1
        			ELSE 0
    			END as combowner,
    
    			bp.battlexp as exp,
    
			    bp.isstartround as startround
    
			FROM
    			ds_battleparticipant AS bp
    			LEFT JOIN ds_userinstance AS ui ON (ui.id_userinstance = bp.id_userinstance)
    			LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)
			WHERE
    			bp.id_battle = ".self::$battleID."
    	";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	/**
	 * Выборка армий участника
	 *
	 * @param unknown_type $battleParticipantID
	 * @return unknown
	 */
	static private function _getBattleParticipantArmys($battleParticipantID)
	{
		assert(is_int($battleParticipantID) && $battleParticipantID > 0);

		$sql = "
			SELECT
    			bpuc.id_unitcontainer as id,
    			bpuc.unitcontainername,
    			COALESCE(bpuc.startround, 0) as startround,
    			bpuc.dieafterround
			FROM
    			ds_battleparticipanthasunitcontainer AS bpuc
			WHERE
    			bpuc.id_battleparticipant = $battleParticipantID
		";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	/**
	 * Выборка списка раундов
	 *
	 * @return array
	 */
	static private function _getBattleRounds()
	{
		$sql = "
			SELECT
    			br.*
			FROM
    			ds_battleround AS br
			WHERE
    			id_battle = ".self::$battleID."
    		ORDER BY roundnumber ASC
		";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	/**
	 * Выборка результатов раунда
	 *
	 * @param int $battleRoundID
	 * @return array
	 */
	static private function _getBattleRoundResults($battleRoundID)
	{
		assert(is_int($battleRoundID) && $battleRoundID > 0);

		$sql = "
			SELECT
    			bres.id_unitcontainer as id_army,
    			u.unitname as unit,
    			bres.amount,
    			bres.loses
			FROM
    			ds_battleresult AS bres
    			INNER JOIN ds_unit AS u ON (u.id_unit = bres.id_unit)
			WHERE
    			bres.id_battleround = $battleRoundID
			ORDER BY id_army
		";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	/**
	 * Выборка сворованных ресурсов
	 *
	 * @return array
	 */
	static private function _getBattleStolenResources()
	{
		$sql = "
			SELECT
    			an.attributename as name,
    			bha.value as amount
			FROM
    			ds_battlehasattribute AS bha
    			INNER JOIN ds_attribute AS a ON (a.id_attribute = bha.id_attribute)
    			INNER JOIN ds_attributename AS an ON (an.id_attributename = a.id_attributename)
			WHERE
    			id_battle = ".self::$battleID."
		";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}

	/**
	 * Enter description here...
	 *
	 * @param int $battleID
	 */
	static private function _getBattleReport($battleID)
	{
		assert(is_int($battleID) && $battleID > 0);
		assert(self::$participantCode == 'A' || self::$participantCode == 'D');

		$sql = "
			SELECT
				br.report
			FROM
				ds_battlereport as br
			WHERE
				br.id_battle = $battleID
				AND
				(
					br.participantflag = '".self::$participantCode."'
					OR
					br.participantflag IS NULL -- old version compatability
				)
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		if(count($res) != 1)
		{
			return '';
		}

		if (substr($res[0]['report'], 0, 2) == 'a:') {
			
			$_SESSION['lang'] = 3;
			$_SESSION['world'] = 2;
			$reportArr = unserialize($res[0]['report']);

			$battleAction = DE_BattleAction::CreateForCurentLang();
			$report = '';
			foreach ($reportArr as $key => $d)
			{
				if (empty($d)) {
					$report .= "\n";
				} else {
					$report .= $battleAction->formAction($d['NAME'], $d['PARAMS'], false);
					$report .= "\n";
				}
			}
			
		} else {
			$report = $res[0]['report'];
		}

		$report = iconv(self::$enc, 'utf-8', $report);

		return $report;
	}

	static private function _getParticipantCode($battleID, $userID)
	{
		assert(is_int($battleID) && $battleID > 0);
		assert(is_int($userID) && $userID > 0);

		$sql = "
			SELECT
				bp.participantflag
			FROM
				ds_battleparticipant AS bp
				INNER JOIN ds_userinstance AS ui USING (id_userinstance)
			WHERE
				ui.id_user = $userID
				AND
				bp.id_battle = $battleID
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);

		return $res[0]['participantflag'];
	}

	/**
	 * Выборка текстового лога
	 *
	 * @param int $battleID
	 * @return string
	 */
	static  private function _getBattleLogHead($battleID)
	{
		assert(is_int($battleID) && $battleID > 0);

		$report = self::_getBattleReport($battleID);

		//$rep = array('&'); // ???
		$report = str_replace('$', '+', substr($report, 0, strrpos($report, '#)')+2));
		$report = str_replace('#', '%', $report);

		return $report;
	}
}

?>