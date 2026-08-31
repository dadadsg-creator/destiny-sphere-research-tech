<?php $CVS_Version = '$Revision$ ; $Date$';

/*

*/
class GE_MetaUALogin
{
	private $token;
	
	/**
	 * Объект для работы с БД
	 *
	 * @var unknown_type
	 */
	private $common;
	
	
	public function __construct($metaua_user)
	{
		$this->userinfo = $metaua_user;
	}

	
	/**
	 * Проверка наличия пользователя с рамблеровским мылом
	 *
	 * @return unknown
	 */
	public function execLogin()
	{
    //SU_DebugTools::LogAll("execLogin Start", "oauth.log");    
	  if(GameDown(0))
		{
			return false;
		}
		
		$this->common = DS_Common::CreateForCurentDB(0);

		GE_SearchStart::SetCommon($this->common);
    
		$res = GE_SearchStart::GetUserIdByMetaUALogin($this->userinfo);
    $uid = intval($res['game_uid']);
    			
		if (!$uid)
		{
			return false;
		}

		$this->_startSession($uid);
		
		$user = DE_User::CreateFromID($uid);
		
		return $user;
	}
	
	
	/**
	 * Стартуем сессию и заносим в нее ID юзера
	 *
	 * @param unknown_type $uid
	 * @return unknown
	 */
	private function _startSession($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$user = DE_User::CreateFromID($uid);
		$username = $user->getName();
		$password = $user->getPassword();
		
		SU_GameSessionWork::SessionStart();
		$_SESSION['uid'] = $uid;
	}
}
?>