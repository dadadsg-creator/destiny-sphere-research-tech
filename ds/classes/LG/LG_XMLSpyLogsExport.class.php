<?php

/**
 * Гейт анализатора.
 * Експорт логов разведки в формате XML
 *
 */
class LG_XMLSpyLogsExport extends LG_Base
{
	
	/**
	 * XML документ
	 *
	 * @var domXML
	 */
	static private $doc;
	
	static private $root;
	
	static private $logID;
	
	/**
	 * ID пользователя
	 *
	 * @var int
	 */
	static private $userID;
	
	/**
	 * Enter description here...
	 *
	 * @var int
	 */
	static private $logLimit;
	
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
	
	static public function Export($userName, $researchDate, $world, $logID = null, $type = 'xml')
	{
		assert(is_int($researchDate) && $researchDate >= 0);
		assert(ereg("[A-Z]{1,3}", $world));
		assert(is_int($logID) || is_null($logID));
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
		
		self::$logLimit = (int)DE_GlobalSettings::Get('ANALIZATOR_SPY_LOGS_LIMIT', true);
		
		self::$logID = $logID;
		
		self::$world = $world;
		
		self::_getCommon();
		
		assert(VD_UserName::Validate($userName, false, self::$common));
		
		$uid = DS_User::GetUserIDByName($userName);
		assert($uid);
		
		self::$userID = $uid;
		
		$xml = '<?xml version="1.0" encoding="utf-8"?><userspylogs user="'.$userName.'" world="'.$world.'"'."/>\n";
		self::$doc = domxml_open_mem($xml);
		self::$root = self::$doc->document_element();
		
		$logs = self::_getLogs(self::$userID, $researchDate);
				
		foreach ($logs as $k => $log)
		{
			self::_exportLog($log);
		}
		
		$res = self::$doc->dump_mem();
		
		echo str_replace(">", ">\n", $res);
	}
	
	static private function _exportLog(array $log)
	{
		$ch = self::$root->new_child('spy_log');		
		$ch->set_attribute('id_log', $log['id_spy_log']);
		$ch->set_attribute('log_owner', $log['log_owner']);
		$ch->set_attribute('date', $log['log_date']);
		$ch->set_attribute('accuracy', $log['accuracy']);
		$ch->set_attribute('comb_from', $log['comb_from']);
		$ch->set_attribute('comb_to', $log['comb_to']);
		$ch->set_attribute('user_from', $log['user_from']);
		$ch->set_attribute('user_to', $log['user_to']);
		$ch->set_attribute('type', $log['unitname']);
		
		if($log['action_name'] == 'RESEARCH')
		{
			$weightPos = strrpos($log['log_text'], ':') + 1;
			$weight = trim(substr($log['log_text'], $weightPos));
			$weight = (float)str_replace(' ', '', $weight);
		} else {
			$weight = '-';
		}
		
		$ch->set_attribute('weight', $weight);
				
		$log['log_text'] = iconv(self::$enc, 'utf-8', html_entity_decode($log['log_text']));

		
		$ch2 = self::$doc->create_text_node($log['log_text']);
		
		$ch->append_child($ch2);
	}
	
	static private function _getLogs($uid, $researchDate)
	{
		if($researchDate > 0)
		{
			$where = " AND sl.log_date > '".GB_TypeFactory::CreateBase('GB_Date', $researchDate)->getPgFormat()."'";
		} elseif(!is_null(self::$logID)) {
			$where = " AND sl.id_spy_log > ".self::$logID;
		} else {
			$where = "";
		}
		
		$sql = "
			SELECT
    			sl.id_spy_log,
    
    			sat.action_name,
    
    			sl.log_date,
    			sl.log_text,
    			sl.accuracy,
    			
    			u.unitname,
    
			    COALESCE(r_from.ringnumber||'.'||cc_from.complexnumber||'.'||c_from.combnumber, '0.0.0') as comb_from,
    			COALESCE(r_to.ringnumber||'.'||cc_to.complexnumber||'.'||c_to.combnumber, '0.0.0') as comb_to,
    
			    COALESCE(u_from.username, '-') AS user_from,
    			COALESCE(u_to.username, '-') AS user_to,
    
			    CASE WHEN ui.id_user = sl.id_user_from THEN 'A'
    			ELSE 'D'
    			END as log_owner
    
			FROM
    			ds_spy_log AS sl
    
			    INNER JOIN ds_spy_action_type AS sat USING (id_spy_action)
    
			    LEFT JOIN ds_user AS u_from ON (u_from.id_user = sl.id_user_from)
    			LEFT JOIN ds_user AS u_to ON (u_to.id_user = sl.id_user_to)
    
    			LEFT JOIN ds_comb AS c_from ON (c_from.id_comb = sl.id_comb_from)
    			LEFT JOIN ds_combcomplex AS cc_from ON (cc_from.id_combcomplex = c_from.id_combcomplex)
    			LEFT JOIN ds_ring AS r_from ON (r_from.id_ring = cc_from.id_ring)
    
    			LEFT JOIN ds_comb AS c_to ON (c_to.id_comb = sl.id_comb_to)
    			LEFT JOIN ds_combcomplex AS cc_to ON (cc_to.id_combcomplex = c_to.id_combcomplex)
    			LEFT JOIN ds_ring AS r_to ON (r_to.id_ring = cc_to.id_ring)
    			
    			LEFT JOIN ds_unit AS u ON (u.id_unit = sl.id_unit)
    
			    INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = sl.id_owner)
			WHERE
				ui.id_user = $uid
				AND
				ui.id_world = ".self::$worldID."
				$where
			ORDER BY sl.id_spy_log
			LIMIT ".self::$logLimit."
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res; 
	}
}
?>