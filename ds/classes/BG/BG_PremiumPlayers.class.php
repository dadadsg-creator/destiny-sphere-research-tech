<?php $CVS_Version = '$Revision$ ; $Date$';

/*
 *
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для получения списка игроков, 
 * у которых действует премиум
 */

class BG_PremiumPlayers extends BG_Common 
{

	public function __construct($data)
	{
		parent::__construct($data);
	}

	public function getPlayers()
	{
		if (!array_key_exists('active',$this->incoming_info) &&
			!array_key_exists('active',$this->incoming_info)) {
			
			$this->error_status = 1;
			$this->outputAnswer(); 
		} else {
			if (key_exists('limit', $this->incoming_info) && is_numeric($this->incoming_info['limit'])) {
				$limit = intval($this->incoming_info['limit']);
			} else {
				$limit = 30;
			}
			
			if (key_exists('offset', $this->incoming_info) && is_numeric($this->incoming_info['offset'])) {
				$offset = intval($this->incoming_info['offset']);
			} else {
				$offset = 0;
			}
			
			$active = $this->incoming_info['active'];
			$trial  = $this->incoming_info['trial'];
			
			$dbh = DS_Common::CreateForCurentDB();
			
			$sql = '
				select
					id_user
				from
					ds_userprofidata
				where
					profi = true and
					trial = ' . $trial . ' and
					active = ' . $active . '
				limit ' . $limit . ' offset ' . $offset;
			$res = $dbh->getResultArray('', $sql, '', '', '');
			assert(empty($res->errormsg));
			$userCount = count($res);
			
			for ($i = 1; $i <= $userCount; $i++) {
				$this->addToResult('' . $i, $res[$i - 1]['id_user']);
			}
			
			$this->addToResult('user_count', $userCount);
		}

		$this->error_status = 0;
		$this->outputAnswer();
	}
}

?>