<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 *
 * Класс гейта для получения информации о премиум-днях и
 * игровых кредитах пользователя в игре
 *
 */
class BG_UserStat extends BG_Common
{
	public function __construct($data)
	{
		parent::__construct($data);
	}

	public function proceed()
	{

		if($this->error_status != 0)
		{
			return;
		}

		if(!array_key_exists('date1',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
    else
    {
      $date1 = $this->incoming_info['date1'];
    }

		if(!array_key_exists('date2',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
    else
    {
      $date2 = $this->incoming_info['date2'];
    }

    $pid = 0;
		if(array_key_exists('pid',$this->incoming_info))
		{
      $pid = $this->incoming_info['pid'];
    }

		$cnt = DS_User::getUsersCnt($date1, $date2, $pid);

		$this->addToResult('cnt_all', $cnt[0]);
		$this->addToResult('cnt_act', $cnt[1]);

		$this->error_status = 0;
		$this->outputAnswer();
	}
}
?>