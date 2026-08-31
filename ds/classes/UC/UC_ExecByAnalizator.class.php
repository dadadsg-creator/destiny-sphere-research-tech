<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecByAnalizator extends UC_PaymentServiceBase
{	
	public function Exec()
	{
		$_SESSION['dbId'] = 0;
		
		self::_getParams();
		
		self::_GetPrice();

		switch(self::$action)
		{
			case 'buy_analizator':				
				if (!self::_BuyAnalizator())
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
	
	static private function _ShowIndex()
	{
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}	
		
		self::$data['MODE'] = 'BUY_ANALIZATOR_INDEX';
	}
	
	static private function _BuyAnalizator()
	{
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
				
		
		if (!PU_ExecChangeAnalizerPremium::Exec(self::$user, 30, 'CABINET', 'payment servises', true)) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_BUY_ANALIZATOR');
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_BUY_ANALIZATOR', self::$data['PRICE'], self::$data['CREDIT']))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		//Смотрим, сколько осталось до окончания доступа к анализатору
		$dates = DS_PremiumAnalizer::getUserPremiumDates(self::$user->getId());
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&toDate=".$dates[0]['enddate_pg']);
		return true;
	}
	
	private static function _ShowSuccessful()
	{
		self::$data['TO_DATE'] = $_REQUEST['toDate'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}
	
	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
	}
}

?>