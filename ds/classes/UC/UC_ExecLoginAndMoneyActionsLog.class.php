<?php $CVS_Version = '$Revision$ ; $Date$';

require_once('classes/Validator.class.php');
require_once('include/GameDown.inc.php');

class UC_ExecLoginAndMoneyActionsLog extends UC_PaymentServiceBase
{	
	static private $uid = false;
	
	static private $email;
	

	public function Exec()
	{
		self::_GetParams();
		
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();
		
		switch (self::$action)
		{
			case 'show_stat':
				if (!self::_ShowStat()) 
				{
					self::_ShowIndex();
				}			
				break;
				
			case 'successful':
					self::_ShowSuccessful();
				break;
				
			default:
				self::_ShowIndex();
				break;
		}

		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}
			
	private static function _ShowIndex()
	{
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		self::$data['MODE'] = 'LOGS_SECOND_MENU';
		self::$data['EXEC_ACTION'] = 'show_stat';
		self::$data['BUTTON_TEXT'] = 'SEND';

		if (empty(self::$email)) 
		{
			self::$data['EMAIL'] = self::$user->getEmail();	
		} else {
			self::$data['EMAIL'] = self::$email;
		}
		
		self::$data['START_DATE'] = date('d.m.y', strtotime("-10 day"));
		self::$data['END_DATE'] = date('d.m.y');
	}
	
	private static function _ShowStat()
	{
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		//Проверяем корректность введенного мыла
		if (!VD_Email::Validate(self::$email)) 
		{
			self::$data['MSG'] = 'WRONG_EMAIL';
			return false;
		}

		//Получаем статистику
		$GLOBALS['billing_script_local_mode'] = true;
		$dataArr['id_user'] = self::$user->getId();
		
		$stat = new BG_UserStatistics($dataArr);
		$stat -> getTradeAndVisitStatistics();
	
		$statistic = $stat->GetResult();

		$statistic = unserialize(urldecode($statistic['serialized_data']));
	
		$language = constant("DEFAULT_LANGUAGE_SHORT");
		require_once("language/".$language."/login_and_money_action.lang");
		require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		$tpl->assign('lang', $lang);

		$tpl->assign('VISIT', $statistic['visit']);
		$tpl->assign('TRADE', $statistic['trade']);

		$letter = $tpl->fetch('login_and_money_action.tpl');

		$headers = "From: \"".$lang['LATTER_FROM']."\" <noreply@destinysphere.com>\n".
		"Return-Path: noreply@destinysphere.com \n".
		"Content-Type: text/html; charset=\"" . constant("DEFAULT_CHARSET") . "\"\n";

		//Высылаем на мыло
		if (!mail(self::$email , $lang['LATTER_SUBJECT'], $letter, $headers)) 
		{
			self::$data['MSG'] = 'WRONG_EMAIL';
			return false;	
		}
		
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_GET_LOG');
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_GET_LOG', self::$data['PRICE'], self::$data['CREDIT'], "mail: ".self::$email))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&mail=".self::$email);
		return true;
	}
	
	private static function _ShowSuccessful()
	{
		self::$data['MAIL'] = $_REQUEST['mail'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}
	
	static private function _GetParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('email', self::$data))
		{
			self::$email = self::$data['email'];
		}
	}
}

?>