<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2007/09/11 07:56:36 $';
/**
 * $Author: defo $
 * $Date: 2007/09/11 07:56:36 $
 * $Revision: 1.3 $
 *
 * Класс гейта для перемещения соты на свободное место внутри кольца
 *
 */
class BG_CombMovingInsideRing extends BG_Common
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

		if(!array_key_exists('new_coords',$this->incoming_info) ||
			!array_key_exists('worldname',$this->incoming_info) ||
			!array_key_exists('old_coords',$this->incoming_info) )
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

		try
		{
			$ch_obj = new SU_ChangCombCoords($this->incoming_info['worldname']);
		} catch (Exception $e) {
			$this->error_status = $e->getMessage();
			$this->outputAnswer();
			return;
		}

		if($ch_obj->change($this->incoming_info['new_coords'], $this->incoming_info['old_coords']))
			$ch_obj->checkMoving($this->incoming_info['new_coords'])?
				$this->addToResult('change_success', 1):
				$this->addToResult('change_success', 0);

		if(!empty($ch_obj->error_status))
			$this->error_status = $ch_obj->error_status;

		$this->outputAnswer();
		return;
	}
}

?>