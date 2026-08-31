<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 *  ласс дл€ включени€ игроку возможности построить дом отдыха
 * */

class BG_AllowResthomeCreation extends BG_Common
{
	public function __construct($data)
	{
		parent::__construct($data);
		$this->common = DS_Common::CreateForCurentDB();
	}
	
	/**
	 * ‘ункци€ провер€ет, содержитс€ ли данный игрок в данном мире
	 * */
	public function checkPresence()
	{
		if (!key_exists('id_user', $this->incoming_info) || empty($this->incoming_info['id_user']) ||
			!key_exists('world_name_ru', $this->incoming_info) || empty($this->incoming_info['world_name_ru'])) {
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

		$id_user = intval($this->incoming_info['id_user']);
		$world_name_ru = $this->incoming_info['world_name_ru'];
		
		GE_SearchStart::SetCommon(DS_Common::CreateForCurentDB());
		$worldData = GE_SearchStart::GetWorldsWhereUserIsRegistered($id_user);
		
		if (!is_array($worldData)) {
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
		
		foreach ($worldData as $wd) {
			if (key_exists('worldname', $wd) && 0 === strcmp($world_name_ru, $wd['worldname'])) {
				$this->addToResult('user_present', 1);
				$this->outputAnswer();
				return;
			}
		}
		
		$this->addToResult('user_present', 0);
		$this->outputAnswer();
		return;
	}

	/**
	 * ѕредоставление возможности
	 * */
	public function proceed()
	{
		if (!key_exists('id_user', $this->incoming_info) || !key_exists('world_name_ru', $this->incoming_info)) {
			$this->error_status = 1;
			$this->outputAnswer();
		}
		
		$id_user = intval($this->incoming_info['id_user']);
		$world_name_ru = $this->incoming_info['world_name_ru'];

		// плохо: здесь дублируетс€ код из BG_UserData
		// REFACTORE IT!
		GE_SearchStart::SetCommon(DS_Common::CreateForCurentDB());
		$worldData = GE_SearchStart::GetWorldsWhereUserIsRegistered($id_user);
		
		if (!is_array($worldData)) {
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
		
		foreach ($worldData as $wd) {
			if (key_exists('worldname', $wd) && 0 === strcmp($world_name_ru, $wd['worldname'])) {
				
				$userinstance = intval($wd['id_userinstance']);
				// мы нашли мир
				$dbName = $wd['dbname'];
				
				// это ужасно, но по-другому никак (?)
				$common = DS_Common::CreateForCurentDB();
				$dbId = $common->getDbIdByName($dbName);
				$common->setDbId($dbId);
				
				$sql = '
					insert
						into ds_buildingsalowedtouserinstance
						(id_building, id_userinstance, adddate)
						values (65, ' . $userinstance . ', now())';
				$common->executeQuery($sql);
				
				if (!empty($common->errormsg)) {
					$this->error_status = 1;
					$this->outputAnswer();
					return;
				}
				
				$this->addToResult('allowed', 1);
				$this->outputAnswer();
				return;
			}
		}
		
		$this->addToResult('allowed', 0);
		$this->outputAnswer();
		return;
	}
	
	public function checkAlreadyGiven()
	{
		if (!key_exists('id_user', $this->incoming_info) || !key_exists('world_name_ru', $this->incoming_info)) {
			$this->error_status = 1;
			$this->outputAnswer();
		}
		
		$id_user = intval($this->incoming_info['id_user']);
		$world_name_ru = $this->incoming_info['world_name_ru'];

		// плохо: здесь дублируетс€ код из BG_UserData
		// REFACTORE IT!
		GE_SearchStart::SetCommon(DS_Common::CreateForCurentDB());
		$worldData = GE_SearchStart::GetWorldsWhereUserIsRegistered($id_user);
		
		if (!is_array($worldData)) {
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
		
		foreach ($worldData as $wd) {
			if (key_exists('worldname', $wd) && 0 === strcmp($world_name_ru, $wd['worldname'])) {
				
				$userinstance = intval($wd['id_userinstance']);
				// мы нашли мир
				$dbName = $wd['dbname'];
				
				// это ужасно, но по-другому никак (?)
				$common = DS_Common::CreateForCurentDB();
				$dbId = $common->getDbIdByName($dbName);
				$common->setDbId($dbId);
				
				$sql = '
					select
						count(*) as cnt
					from
						ds_buildingsalowedtouserinstance
					where
						id_userinstance = ' . $userinstance . ' and
						id_building = 65';
				$result = $common->getResultArray('', $sql, '', '', '');
				assert(empty($common->errormsg));
				
				if (is_array($result) && count($result) > 0 && is_array($result[0])) {
					$rowCount = intval($result[0]['cnt']);
				} else {
					$rowCount = false;
				}

				if (0 === $rowCount) {
					$this->addToResult('user_already_given_resthome_ability', 0);
				} else {
					$this->addToResult('user_already_given_resthome_ability', 1);
				}

				$this->outputAnswer();
				return;
			}
		}
		
		$this->addToResult('allowed', 0);
		$this->outputAnswer();
	}
}

?>