<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_ExecGetXMLData
{
	/**
	 * Enter description here...
	 *
	 * @var DE_User
	 */
	static private $user;
	
	public function Exec(DE_User $user, array $data)
	{	
		self::$user = $user;
			
		$_SESSION['dbId'] = 0;
		
		$accData = self::_getAccountData();

		$lang = self::_getLang();
		
		//$userInstances = self::_getWorldData();
		$userInstances = UC_ExecUserCabinetIndex::GetUserMoneyAccounts(self::$user);
		
		$xml = 'data='.
			'<locolize '.
				'akkaunt_loc="'.$lang['ACCOUNT_TXT'].'" '.
				'status_loc="'.$lang['ACCOUNT_STATUS'].'" '.
				'login_loc="'.$lang['LOGIN_TXT'].'" '.
				'email_loc="'.$lang['EMAIL_TXT'].'" '.
				'gold_loc="'.$lang['GOLD_CREDITS'].'" '.
				'silver_loc="'.$lang['SILVER_CREDITS'].'" '.
				'world_status_loc="'.$lang['ACCOUNT_STATUS_WORLDS'].'" '.
				'close_but_loc="'.$lang['LOGOUT'].'" '.
				'buy_but_loc="'.$lang['BUY_BUT_TXT'].'" '.
				'buy_but_link_loc="'.urlencode($lang['BUY_BUT_LINK']).'" '.
			'/>'.
			'<data '.
				'login_data="'.$user->getName().'" '.
				'email_data="'.$user->getEmail().'" '.
				'gold_count_data="'.$accData['GC'].'" '.
				'silver_count_data="'.$accData['SC'].'" '.
			'/>'.
			'<worlds>';
			
		foreach ($userInstances as $worldName => $data)
		{
			assert(is_array($data));
			assert(key_exists('GC', $data));
			assert(key_exists('SC', $data));
			assert(key_exists('dbname', $data));
			$xml .= '<sphere name="'.$worldName.'" gold="'.$data['GC'].'" silver="'.$data['SC'].'" pic="'.$data['dbname'].'"/>';
		}
			
		$xml .= '</worlds>';
			
		echo(iconv('cp1251', 'utf-8', $xml));
	}
	
	/*
	// Этот метод перенесен в UC_ExecUserCabinteIndex, а то чего в двух местах одно и то же делается 
	static private function _getWorldData()
	{
		$common = DS_Common::CreateForCurentDB();
		GE_SearchStart::SetCommon($common);		
		$userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered(self::$user->getID());
		
		$userInst = array();
		
		foreach($userWorlds as $k => $instance) 
		{
			$dbId = $common->getDBIdByName($instance['dbname']);

			$_SESSION['dbId'] = $dbId;
			$_SESSION['world'] = $instance['id_world'];

			//Смотрим, разрешены ли в этом мире операции с деньгами
			$allowUserCabinet = DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_USER_CABINET');

			if ($allowUserCabinet == 1) 
			{
				$world = DE_WorldFromCollection::CreateFromID($instance['world_identifier']);
				$member = new MU_EntityOperationMember(self::$user, $world);
				$account = MU_SearchOperations::GetAccountData($member);

				if(count($account) > 0)
				{
					$userInst[$instance['worldname']] = $account;
					$userInst[$instance['worldname']]['dbname'] = $instance['dbname']."_".$instance['id_world'];
				}
			}
		}
		
		return $userInst;
	}*/
	
	static private function _getAccountData()
	{
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember(self::$user, $world);
		$accData = MU_SearchOperations::GetAccountData($member);
		
		if(count($accData) == 0)
		{
			$accData = array('GC' => 0, 'SC' => 0);
		}
		
		return $accData;
	}
	
	static private function _getLang()
	{
		if(isset($_SESSION) && key_exists('lang', $_SESSION))
		{
			$l = $_SESSION['language'];
		} else {
			$l = constant('DEFAULT_LANGUAGE_SHORT');
		}
		
		include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".$l."/user_cabinet_index.lang";
		
		assert(isset($lang) && is_array($lang));
		
		return $lang;
	}
}

?>