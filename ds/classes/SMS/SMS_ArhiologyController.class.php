<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс контролера сервиса архиологов
 *
 */
class SMS_ArhiologyController
{
	/**
	 * Класс для работы с БД
	 *
	 * @var DS_Common
	 */
	private $common;
	
	/**
	 * ТИп действия. попвещение или отправка
	 *
	 * @var string
	 */
	private $action;
	
	/**
	 * ID сервеа БД с которым работаем
	 *
	 * @var integer
	 */
	private $DBID;
	
	/**
	 * ID инстанса пользователя
	 *
	 * @var integer
	 */
	private $UIID;	
	
	/**
	 * Колличество архов в партии
	 *
	 * @var integer
	 */
	private $archCount;	
	
	/**
	 * Время на которое отправлять будем архов в турнах
	 *
	 * @var integer
	 */
	private $searchTime;	
	
	/**
	 * Имя места куда посылаем
	 *
	 * @var string
	 */
	private $target;	
	
	/**
	 * Допустимые значения места отправки
	 *
	 * @var array
	 */
	private $targetValues = array('HOME', 'CENTRAL', 'NEUTRAL');
	
	
	public function __construct()
	{
		
	}

	/**
	 * ОСновной метод
	 *
	 */
	public function exec()
	{		
		
		$this->_reciveParams();
				
		$this->common = DS_Common::CreateForCurentDB($this->DBID);
		
		switch ($this->action)
		{
			case 'send':
				$this->_actionSendAll();
				break;
			case 'info':
				$this->_actionGetInfo();
				break;
			default:
				assert(false);
		}		
	}

	/**
	 * Экшен получения состояни архиологов по сотам
	 *
	 */
	private function _actionGetInfo()
	{
		//С перва надо зарефрешить чувака
		if(!$this->_execUserAction())
		{
			die('0');
		}
				
		//Тперь берем список сот со значением архов на базах	
		$sql = "
			SELECT
      			r.ringnumber || '.' || cc.complexnumber || '.' || c.combnumber as comb,
      			chre.value
			FROM
    			ds_comb AS c
    			INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    			INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    			INNER JOIN ds_combhasresource AS chre ON (chre.id_comb = c.id_comb)
			WHERE
     			c.id_userinstance = ".$this->UIID."
     			AND
     			chre.id_attribute = 27 -- CURRENT_AMOUNT => ARCHEOLOGIST";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		$out = "1|";
		
		foreach($rs as $k=>$v)
		{
			$out .= $v['comb']." {FREE} ".$v['value']."; ";
		}
		
		die($out);
	}
	
	
	/**
	 * Экшен отправки архов со всех сот
	 *
	 */
	private function _actionSendAll()
	{
		//засекли время начала отпраки архов
		$startTime = GB_TypeFactory::CreateBase('GB_Date', time() - 5);	
		
		//С перва надо зарефрешить чувака
		if(!$this->_execUserAction())
		{
			die('0');
		}
	
		//Извлекли список компбов игрока
		$sql = "
			SELECT c.id_comb
			FROM ds_comb AS c
			WHERE c.id_userinstance = ".$this->UIID;
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		//проходимся по всем сотам и отправляем архов	
		foreach($rs as $k => $v)
		{
			$combID = (int)$v['id_comb'];
			assert($combID > 0);

			//Сформировали запрос
			$xml = '"<archeologyteam dest=#'.$this->target.'# count=#'.$this->archCount.'# time=#'.$this->searchTime.'# size=#1# sendtype=#all#/>"';
			
			if(!$this->_execUserAction($xml, $combID))
			{
				die('0');
			}
		}
		
		$out = "1|{SEND} ";
		
		//Теперь смотрим с какой соты скольк комманд отправленно
		$sql = "
			SELECT
      			r.ringnumber || '.' || cc.complexnumber || '.' || c.combnumber as comb,
      			count(*) as colvo
			FROM
    			ds_comb AS c
    			INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    			INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    			INNER JOIN ds_archeologicalteam AS at ON (at.id_comb = c.id_comb)
			WHERE
     			c.id_userinstance = ".$this->UIID."
     			AND
     			at.starttime > '".$startTime->getPgFormat()."'
			GROUP BY r.ringnumber, cc.complexnumber, c.combnumber";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($rs) > 0)
		{
			foreach($rs as $k=>$v)
			{
				$out .= $v['comb']." - ".$v['colvo']." {GROUP}; ";
			}	
		}else{
			$out .= "0 {GROUP}";
		}
		
		
		die($out);
	}
	
	
	private function _execUserAction($xml = '', $currentCombID = '')
	{
		assert(defined('PHP_PATH'));
		
		$out = array();

		$command = constant('PHP_PATH')." -f useraction.php ".$this->DBID." ".$this->UIID." ".$xml." ".$currentCombID;				
		$str = exec($command, &$out);
//echo "COMMAND: $command<br>";		
		$outString = implode("\n", $out);
//echo "OUT: $outString<br>";		
		if(strpos($outString, 'xmlerror') || (!strpos($outString, 'overload=0') && strpos($outString, 'overload')))
		{
			return false;
		}else{					
			return true;
		}
	}
	
	/**
	 * Привем параметров
	 *
	 */
	private function _reciveParams()
	{
		assert(key_exists('action', $_REQUEST));		
		$this->action = $_REQUEST['action'];
		assert(!empty($this->action));		
		
		assert(key_exists('dbid', $_REQUEST));		
		$this->DBID = (int)$_REQUEST['dbid'];
		assert(key_exists($this->DBID, $GLOBALS['dbWorldAccess']));			
		
		
		assert(key_exists('uiid', $_REQUEST));
		$this->UIID = (int)$_REQUEST['uiid'];
		assert($this->UIID > 0);					
		
		if(key_exists('arch_count', $_REQUEST))
		{
			$this->archCount = (int)$_REQUEST['arch_count'];
			assert($this->archCount > 0);			
		}
		
		if(key_exists('search_time', $_REQUEST))
		{
			$this->searchTime = (int)$_REQUEST['search_time'];
			assert($this->searchTime > 0);			
		}
		
		if(key_exists('target', $_REQUEST))
		{
			$this->target = $_REQUEST['target'];
			assert(in_array($this->target, $this->targetValues));			
		}
	}
}

?>