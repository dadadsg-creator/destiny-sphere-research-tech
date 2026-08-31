<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 *
 * Класс гейта для получения информации о регистраций по партнёрской ссылке
 *
 */
class BG_PartnerStat extends BG_Common
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

		if(!array_key_exists('pid',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
    else
    {
      $pid = $this->incoming_info['pid'];
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


		$data = DS_Partner::getPartnerStat($pid, $date1, $date2);
		$this->addToResult('user_total', $data['user_total']);
		$this->addToResult('user_period', $data['user_period']);

		$this->error_status = 0;
		$this->outputAnswer();
	}
}
?>