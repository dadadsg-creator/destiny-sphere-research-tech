<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 *
 *
 *
 */
class BG_PartnerTotalRegStat extends BG_Common
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

		if(!array_key_exists('plist',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

    $data = DS_Partner::getAllTotalPartnerStat($this->incoming_info['plist']);

		$this->addToResult('regdata', $data);

		$this->error_status = 0;
		$this->outputAnswer();
	}
}
?>