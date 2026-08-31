<?php $CVS_Version = '$Revision$ ; $Date$';

require_once('classes/Validator.class.php');
require_once('include/GameDown.inc.php');

class UC_LoginAndMoneyActionsLog
{
	static private $action = 'index';
	
	static private $user;
	
	static private $data = array();
	
	static private $uid = false;
	

	public function Exec(DE_User $user, $data)
	{
		self::$user = $user;
		
		assert(isset($_SESSION));
		
		self::$data = $data;
		
		if (key_exists('log_action', $data))
		{
			self::$action = $data['log_action'];
		}
		
		switch (self::$action)
		{
			case 'index':
				self::$data['MODE'] = 'INDEX';
				break;
			case 'show_stat':
				$statistic = self::_getStat();	
		
				self::$data['START_DATE'] = date('d.m.y', strtotime("-10 day"));
				self::$data['END_DATE'] = date('d.m.y');
				
				self::$data['MODE'] = 'STAT';

				self::$data['LOGIN_STATISTIC'] = $statistic['visit'];
				self::$data['TRADE_STATISTIC'] = $statistic['trade'];
				break;
		}

		UC_OutUserCabinet::Out('LoginAndMoneyActionsLog', self::$data);
	}


	private static function _getStat()
	{
		
		$GLOBALS['billing_script_local_mode'] = true;
		
		$dataArr['id_user'] = self::$user->getId();
		
		$stat = new BG_UserStatistics($dataArr);
		$stat -> getTradeAndVisitStatistics();
	
		$statistic = $stat->GetResult();

		$statistic = unserialize(urldecode($statistic['serialized_data']));

		return $statistic;
	}
		
}

?>