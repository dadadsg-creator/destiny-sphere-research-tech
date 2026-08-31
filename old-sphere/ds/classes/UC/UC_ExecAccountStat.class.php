<?php $CVS_Version = '$Revision: 1.11 $ ; $Date: 2008/12/18 10:58:46 $';


class UC_ExecAccountStat
{
	static private $common = null;
	
	static private $user;
	
	static private $data;
	
	/**
	 * За сколько дней показывать статистику
	 * если 0, то показывать всю статистику
	 *
	 * @var int
	 */
	static private $statPeriod = 60;
	
	static private $actionHandlers = array();
	
	static public $finalStat = array();
	
	static private $action;
		
	public function Exec(DE_User $user, $data)
	{
		assert(is_array($data));
		
		//self::$common = DS_Common::CreateForCurentDB(0);
		
		self::GetCommon();
		
		self::$user = $user;
		self::$data = $data;
		
		self::_getParams();
		
		$accounts = self::_getUserAccounts($user);
		
		switch(self::$action)
		{
			case 'index':
				$data['accounts'] = $accounts;
				UC_OutUserCabinet::Out('Stat', $data);
				break;
		
			case 'acсount':
				self::_getAccountOperations($accounts[self::$data['acсount']]);
				$data['stat'] = self::$finalStat;
				UC_OutUserCabinet::Out('Stat', $data);
				break;	
				
			default:
				break;
		}
	}
	
	static public function GetCommon()
	{
		if(is_null(self::$common))
		{
			self::$common = DS_Common::CreateForCurentDB(0);
		}
	}
	
	static private function _getParams()
	{
		self::$action = 'index';
		
		if(key_exists('acсount', self::$data))
		{
			self::$action = 'acсount';
		}
	}
	
	static private function _getUserAccounts(DE_User $user)
	{
		// get user info 
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember($user, $world);
		$cab = UC_LocalizationStat::Get('CABINET');
		$accounts[$cab] = MU_SearchOperations::GetAccountData($member);
		$accounts[$cab]['is_user'] = true;
		$accounts[$cab]['id_user'] = $user->getID();
		$accounts[$cab]['world'] = '0_UC';
		
		GE_SearchStart::SetCommon(self::$common);		
		$userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($user->getID());
		
		foreach($userWorlds as $k => $instance) 
		{
			$dbId = self::$common->getDBIdByName($instance['dbname']);
			
			$world = DE_WorldFromCollection::CreateFromID($instance['world_identifier']);
			$member = new MU_EntityOperationMember($user, $world);
			$account = MU_SearchOperations::GetAccountData($member);
			
			if(count($account) > 0)
			{
				$accounts[$instance['worldname']] = $account;
				$accounts[$instance['worldname']]['world'] = $dbId.'_'.$instance['world_identifier'];
				$accounts[$instance['worldname']]['id_user'] = $user->getID();
			}
		}
		
		return $accounts;
	}
	
	static public function _getAccountOperations($account, $limit = 50)
	{
		/**
		 * $account => array(
		 * 		ID_ACCOUNT => int
		 * 		id_user => int
		 * 		world => dbid_world
		 * )
		 */
		
		assert(is_array($account));
		assert(key_exists('ID_ACCOUNT', $account));
		
		$accountId = (int)$account['ID_ACCOUNT'];
		
		assert(key_exists('id_user', $account));
		
		//if(!key_exists('is_user', $account))
		//{
		/*} else {
			$dbId = 0;
		}*/
		assert(key_exists('world', $account));
			
		$arr = explode('_', $account['world']);
		assert(count($arr) == 2);
			
		$dbId = (int)$arr[0];
		assert($dbId >= 0);
			
		$world = $arr[1];
		assert(ereg('[A-Z]{1,3}', $world));
				
		self::$common->setDbId($dbId);
		
		if(self::$statPeriod > 0)
		{
			$where = "
				AND
				ml.add_date > NOW() - '".self::$statPeriod." DAY'::INTERVAL
			";
		} else {
			$where = '';
		}
		
		$sql = "
			SELECT
				ml.*,
				mat.action_name,
				mat.log_action_table,
				$dbId as dbid
			FROM
				ds_money_list as ml
				INNER JOIN ds_money_action_types AS mat ON (mat.id_money_action = ml.id_action)
			WHERE
				-- ml.id_account = $accountId
				-- AND
			-- --------------------------------------- --	
				ml.id_user = ".$account['id_user']."
				AND
				ml.world_identifier = '".$world."'
			-- --------------------------------------- --	
				$where
			ORDER BY ml.add_date DESC
			LIMIT $limit
			";
		
		$res = self::$common->getResultArray("", $sql, "", "" ,"");
		assert(empty(self::$common->errormsg));
	
		// Получаем классы обработчики услуг
		self::$actionHandlers = MU_SearchOperations::GetActionsStatHandler();
		
		foreach($res as $k => $moneyList)
		{
			if(!key_exists($moneyList['action_name'], self::$actionHandlers))
			{
				$handlerClass = 'UC_StatNull';
			} else {
				$handlerClass = self::$actionHandlers[$moneyList['action_name']];
			}
			
			$handler = new $handlerClass($moneyList);
			$stat = $handler->formStat();
			if(is_array($stat) && count($stat))
			{
				self::$finalStat[] = $stat;
			}
		}
	}
	
}

?>