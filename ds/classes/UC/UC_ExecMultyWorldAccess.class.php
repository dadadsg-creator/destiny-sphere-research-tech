<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecMultyWorldAccess extends UC_PaymentServiceBase
{
	public function Exec()
	{
		self::_getParams();
		
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();

		switch(self::$action)
		{
			case 'add_multy_world_access':
				if (!self::_AddMultyWorldAccess()) 
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
		self::$data['MODE'] = 'MYLTY_WORLD_ACCESS_INDEX';
		self::$data['EXEC_ACTION'] = 'add_multy_world_access';
		
		$GLOBALS['billing_script_local_mode'] = true;

		$dataArr['id_user'] =self:: $user->getId();
		
		$allower = new BG_AddAllWorldAbility($dataArr);
		
		//Проверка, не является ли игрок уже обладателем этой возможности
		$w = $allower->check();
		$res = $allower->GetResult();

		assert(isset($res['checking_result']));

		if ($res['checking_result'] != 'add_ability_ok')
		{
			self::$data['ALREADY_HAVE'] = $res['checking_result'];
		}
	}
	
	static private function _AddMultyWorldAccess()
	{
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		$GLOBALS['billing_script_local_mode'] = true;

		$dataArr['id_user'] =self:: $user->getId();
		
		$allower = new BG_AddAllWorldAbility($dataArr);
		
		//Проверка, не является ли игрок уже обладателем этой возможности
		$w = $allower->check();
		$res = $allower->GetResult();

		assert(isset($res['checking_result']));

		if ($res['checking_result'] != 'add_ability_ok')
		{
			self::$data['MSG'] = $res['checking_result'];
			return false;
		}
			
		$allower->add();
		$res = $allower->GetResult();


		if ($res['ability_ok'] != 1)
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}

		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_MYLTY_WORLD_ACCESS');
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_MYLTY_WORLD_ACCESS', self::$data['PRICE'], self::$data['CREDIT']))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful");
		return true;
	}
	
	private static function _ShowSuccessful()
	{
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