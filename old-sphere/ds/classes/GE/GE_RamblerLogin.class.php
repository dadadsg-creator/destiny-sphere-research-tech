<?php $CVS_Version = '$Revision$ ; $Date$';

/*
===================
	РАМБЛЁР ИД
===================

ОПИСАНИЕ:

Для аутентификации Рамблер-пользователей используется скрипт auth.cgi (http://id.rambler.ru/script/auth.cgi).
ЗАМЕНЕН НА int-id.rambler.ru 1 февраля 2010 г
*************ИНФОМАЦИЯ УСТАРЕЛА****************
1. Заведение сессии

Для аутентификации заводится форма вида:

<form action="http://id.rambler.ru/script/auth.cgi" method="post"> <input type="hidden" name="back"
value="http://куда-вернуться/"> <input type="text" name="login"> <input type="password" name="passw"> <input type="submit">
</form>

(можно вместо этого просто сослаться на auth.cgi с параметром back=<полный адрес, включая http:// или https://>).

В случае успешной аутентификации пользователь будет возвращен на указанный адрес с проставленной кукой rsid (кука
устанавливается на домен .rambler.ru), в которой живет session id.

В случае, если браузер пользователя не поддерживает cookie, будут использованы url-сессии (их можно форсировать, передав
параметр url=1). 
При использовании url-сессий строка '$sid$' в параметре back заменяется на идентификатор сессии. Если такой строки нет -
автоматически добавляется '/session/$sid$' после имени хоста.

Пример вызова для форсирования url-сессий:

auth.cgi?mod=login&url=1&back=http://your-project-name.ru/logged?sid=$sid$

2. Проверка сессии

Валидность сессии следует проверить с помощью вызова auth.cgi с параметрами mode=check, sid=<session id> (проверка
разрешена только с определенных ip-адресов, перед использованием - предупредите). 

3. Завершение сессии

Для завершения сессии следует вызвать auth.cgi с параметром mode=logout и, если сессия, которую нужно закрыть, не
находится в куке rsid, то с параметром sid=<session id>. Понимается также параметр back.

Например:
<a href="http://id.rambler.ru/script/auth.cgi?mode=logout&back=http://your-project-name.rambler.ru">Выйти</a>


4. Что именно возвращается при вызове auth.cgi?mode=check

Если сессия неправильная - возвращается "450 Session check error", если правильная - 200 OK и содержимое сессии в
tab-separated формате.

Ключи, на которые следует обратить внимание:

_session_id - идентификатор сессии (интересен в основном при использовании mode=checklogin)
Login - полный логин пользователя (anysymbols@rambler.ru), как мы его поняли (могли убрать лишние пробелы и пр.)
login_ip - ip-адрес, с которого пользователь завел сессию; может проверяться для повышения безопасности
login_time - время заведения сессии
name - полное имя пользователя

*/
class GE_RamblerLogin
{
	/**
	 * ИД из рамблеровского кукиса
	 *
	 * @var unknown_type
	 */
	private $rsid;
	
	
	private $hasRSID = false;
	
	/**
	 * Объект для отправки HTTP запроса рамблеровсокого скрипта
	 *
	 * @var unknown_type
	 */
	private $getContent;
	
	/**
	 * Данные полученные с id.rambler.ru
	 *
	 * @var unknown_type
	 */
	private $vars = array();
	
	/**
	 * Объект для работы с БД
	 *
	 * @var unknown_type
	 */
	private $common;
	
	private $ramblerLogin = false;
	
	public function __construct()
	{
		if($this->_getRSID())
		{
			if($this->_getRamblerID() && $this->_getRamblerLogin() && !GameDown(0))
			{
				$common = DS_Common::CreateForCurentDB(0);
				GE_SearchStart::SetCommon($common);
				$this->hasRSID = true;
			}
		}
	
	}
	
	public function hasRSID()
	{
	  return $this->hasRSID;
	}
	
	public function getRamblerLogin()
	{
		return $this->ramblerLogin;
	}

	public function canLogin()
	{
		$info = GE_SearchStart::GetRamblerIDInfo($this->ramblerLogin);
		  		
		if(count($info) > 0 && $info['login_enable'] == 'f')
		{
			return false;
		}

		return true;
	}
	
	public function canRegistrate()
	{
		$info = GE_SearchStart::GetRamblerIDInfo($this->ramblerLogin);
		if(count($info) > 0 && $info['registration_enable'] == 'f')
		{
			return false;
		}
		
		return true;
	}
	
	private function _getRSID()
	{		
		if(key_exists('rsid', $_GET))
		{
			$this->rsid = $_GET['rsid'];
		} elseif(key_exists('rsid', $_POST)) {
			$this->rsid = $_POST['rsid'];
		} elseif(key_exists('rsid', $_COOKIE)) {
			$this->rsid = $_COOKIE['rsid'];
		} else {
			return false;
		}
		
		if(empty($this->rsid))
		{
			return false;
		}

		return true;
	}
	
	
	private function _getRamblerLogin()
	{
		if(!key_exists('LOGIN', $this->vars))
		{
			return false;
		}
		
		$this->ramblerLogin = $this->vars['LOGIN'];
		
		return true;
	}
	
	/**
	 * Проверка наличия пользователя с рамблеровским мылом
	 *
	 * @return unknown
	 */
	public function execLogin()
	{
		if(GameDown(0))
		{
			return false;
		}
		
		$this->common = DS_Common::CreateForCurentDB(0);
	
		GE_SearchStart::SetCommon($this->common);
		$uid = GE_SearchStart::GetUserIdByEMail($this->ramblerLogin);

		if(!$uid)
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

	
	/**
	 * Проверка валидности РИДишника и получение данных о пользователе
	 *
	 * @return unknown
	 */
	private function _getRamblerID()
	{
	  //$request = "http://id.rambler.ru/check-rsid/";
	  $request = "http://int-id.rambler.ru/check-rsid/{$_SERVER['REMOTE_ADDR']}/".$this->rsid; 
		///$request = "http://id.rambler.ru/script/auth.cgi";
		//$request = "http://unlim/ds/test_RSID.php";
		
		$this->getContent = new SU_HTTPGetContent();
		$this->getContent->setUrl($request);
		$this->getContent->setMethod("GET");
		//$this->getContent->setPostVar("mode", "check");
		//$this->getContent->setPostVar("sid", $this->rsid);
		$this->getContent->Start();		

		$headers = $this->getContent->GetResponseHeaders();
		$start = strpos($headers, " ") + 1;
		$end = strpos($headers, " ", $start);
		
		$statusHeader = substr($headers, $start, $end - $start); 
		  
		if($statusHeader == '200')
		{
			$content = $this->getContent->GetResponseContent();
			$this->_parseContent($content);
			return true;
		}
		return false;
	}
	
	/* temp function */
	public function IsRSID()
	{
		if($this->hasRSID())
		{
			if ($this->canLogin() && $this->execLogin() !== false)
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	* Разбираем данные полученные от рамблеровского скрипта
	*
	* @param unknown_type $content
	*/
	private function _parseContent($content)
	{
		$lines = explode("\n", $content);
		foreach($lines as $k => $line)
		{
			list($name, $value) = explode("\t", $line);
			$this->vars[strtoupper($name)] = trim($value);
		}
	}
	
	static public function ClearRamblerCookies()
	{
		if(key_exists('rsid', $_COOKIE))
		{
			setcookie('rsid', '', time()-42000);
		}
	}
	
	
	public function getUserNameForRegistration(DS_Common $common)
	{
		$email = $this->getRamblerLogin();		
		$baseLogin = substr($email, 0, strpos($email, '@'));
		$login = $baseLogin;

		$counter = 0;
		
		while(VD_UserName::CheckIfExists($login, $common) !== false)
		{
			$random = rand(100, 999);
			$login = $baseLogin."_".$random;
			$counter++;
		}
		
		return $login;
	}
	
	public function getRSID()
	{
		return $this->rsid;
	}
}
?>