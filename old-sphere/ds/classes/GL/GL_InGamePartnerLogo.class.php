<?php

/**
 * Ћоготип, который показываетс€ в клиенте
 *
 */
class GL_InGamePartnerLogo
{
	static public function Get()
	{	
		switch (constant('VERSION_NAME')) 
		{
			// локаль
			case 'LOCAL':
				return 'russia.gif';
				
			case 'RU_CLASSIC':
			case 'RU_UNLIM':
			case 'TEST':
				// русские версии, включа€ тестовую	- лого –амблер	
				return 'rambler.swf';
				
			case 'ITALY':				
				// у италь€нцев зависит от того, через какого партнера зарегистрирован пользователь
				return self::_getForItaly();
		
			case 'GERMANY':
				// на будущее
				return '';
				
			case 'LITHUANIA':
				return 'logo_lt.swf';
				
			default:
				// нету лого
				return '';
		}
	}
	
	/**
	 * ” италь€нцев два разных логотипа в одной версии
	 *
	 * @return unknown
	 */
	static private function _getForItaly()
	{
		if(isset($_SESSION['uid']))
		{
			$userID = (int)$_SESSION['uid'];
		} else {
			return '';
		}
		
		if($userID <= 0)
		{
			return '';
		}
		
		if(!key_exists('partner', $_SESSION))
		{
			$_SESSION['partner'] = DE_User::CreateFromID($userID)->getPartnerID();
		}
		
		switch ($_SESSION['partner']) {
			case 1:
				// KALIKANTHUS
				return 'kali.swf';
		
			case 2:
				// YAHOO
				return 'yahoo.swf';
				
			default:
				return '0';
		}
	}
}

?>