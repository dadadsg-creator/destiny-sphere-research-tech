<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Turnir extends DS_Base
{
	static function GetCurentPause($worlId, $chekDate)
	{
		assert(is_int($worlId) && $worlId > 0);
		
		$common = self::getCommon();
		
		if ($chekDate) {
			$date = "'$chekDate'";
		} else {
			$date = 'now()';
		}

		$sql = "SELECT
					*
				FROM
					ds_turnir_pause
				WHERE 
					end_date > $date
					AND
					start_date < $date
					AND
					id_world = ".$worlId."
				ORDER BY end_date DESC";				

		$res = $common->getResultArray( "", $sql);
		assert(empty($common->errormsg));

		if (count($res) > 0) {
			return $res[0]['end_date'];
		}
		
		return array();
	}
}

?>