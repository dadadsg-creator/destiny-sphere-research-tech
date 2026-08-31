<?php
require_once 'Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';
require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

define('ANSWER_LIMIT', 25);

class QuestForm
{
	private $common;
	
	private $action;
	
	private $userName;
	
	private $world;
	
	private $keyWord;
	
	private $info;
	
	private $errorMsg = '';
	
	public function __construct($data)
	{
		if(GameDown(0))
		{
			Header("Location: index_down.html");
			die();
		}
		
		$this->common = DS_Common::CreateForCurentDB(0);
		
		if($this->_getParams($data))
		{
			$this->_insertData();
		}
		
		$this->_out();
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));

		$action = 'index';
		
		if(key_exists('add', $data))
		{
			if(key_exists('username', $data))
			{
				$this->userName = trim($data['username']);
				
				if(!VD_UserName::Validate($this->userName))
				{
					$this->errorMsg = VD_UserName::FetchErrorCode();
//					return false;
				}
			
				$UID = (int)VD_UserName::CheckIfExists($this->userName, $this->common);
				
				if($UID == 0)
				{
					$this->errorMsg = 'WRONG_USERNAME';
					//return false;
				}
			} else {
				$this->errorMsg = 'NO_USERNAME';
				//return false;
			}
			
			if(key_exists('key_word', $data))
			{
				$this->keyWord = substr(trim($data['key_word']), 0, 50);
				
				if(empty($this->keyWord))
				{
					$this->errorMsg = 'NO_KEYWORD';
					//return false;
				}
				
				if(!get_magic_quotes_gpc()) 
				{
					$this->keyWord = addslashes($this->keyWord);
				}
			} else {
				$this->errorMsg = 'NO_KEYWORD';
				//return false;
			}
			
			if(key_exists('info', $data))
			{
				$this->info = substr(trim($data['info']), 0, 2000);
				
				if(!get_magic_quotes_gpc())
				{
					$this->info = addslashes($this->info);
				}
			} else {
				$this->errorMsg = 'NO_INFO';
//				return false;
			}
			
			if(key_exists('world', $data))
			{
				$this->world = $data['world'];
				$worlds = $this->_getWorldList();
				$valid = false;
				foreach($worlds as $k => $world)
				{
					if($world['world_identifier'] == $this->world)
					{
						$valid = true;
						break;
					}
				}
				if(!$valid)
				{
					$this->errorMsg = 'WRONG_WORLD';
					//return false;
				}
			} else {
				$this->errorMsg = 'NO_WORLD';
				//return false;
			}
			
			$action = 'add';
			if(!empty($this->errorMsg))
			{
				return false;
			}
			
			return true;
		} 
		
		return false;
	}
	
	private function _getWorldList()
	{
		$sql = "
			SELECT
				worldname,
				id_world,
				dbname,
				world_identifier
			FROM
				ds_worldcollection
			";
		
		$res = $this->common->getResultArray("quest_form_world_list", $sql, "", "", "GLOBAL_CACHE");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _check()
	{
		$sql = "
			SELECT 
				count(*) 
			FROM
				ds_questinfo
			WHERE
				upper(username) = upper('".$this->userName."')
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if($res[0]['count'] < ANSWER_LIMIT)
		{
			return true;
		}
		
		return false;
	}
	
	
	private function _insertData()
	{
		if(!$this->_check())
		{
			$this->errorMsg = 'ANSWER_LIMIT';
			return false;
		}
		
		$sql = "
			INSERT INTO ds_questinfo
				(username, world, key_word, info, date)
			VALUES
				('".$this->userName."', '".$this->world."', '".$this->keyWord."', '".$this->info."', now())
			";
		$this->common->executeQuery($sql);
		//assert(empty($this->common->errormsg));
		
		$this->errorMsg = 'ANSWER_SEND';

		$mailBody  = "\nUser name: ".$this->userName;
		$mailBody .= "\nWorld: ".$this->world;
		$mailBody .= "\nKey word: ".$this->keyWord;
		$mailBody .= "\nInfo: ".$this->info;
		
		mail('viktorina@destinysphere.com', "Quest Form", $mailBody, "Content-type: text/plain; charset=windows-1251\r\nfrom: no-reply@destinysphere.ru\r\n");
	}
	
	private function _out()
	{
		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;
		
		$tpl->assign('MSG', $this->errorMsg);
		$tpl->assign('WORLDS', $this->_getWorldList());
		
		$tpl->assign('USERNAME', $this->userName);
		$tpl->assign('KEYWORD', $this->keyWord);
		$tpl->assign('INFO', $this->info);
		$tpl->assign('SEL_WORLD', $this->world);
		
		include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/questform.lang";
		assert(is_array($lang));		
		$tpl->assign('lang', $lang);
		unset($lang);
		
		$tpl->display("questform.tpl");
	}
	
}

$q = new QuestForm($_REQUEST);
?>