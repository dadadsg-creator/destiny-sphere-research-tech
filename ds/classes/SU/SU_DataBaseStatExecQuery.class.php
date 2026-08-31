<?php $CVS_Version = "$Revision: 1.7 $ ; $Date: 2007/04/24 12:57:18 $";

class SU_DataBaseStatExecQuery
{
	function ExecQuery($dbId, $query)
	{
		assert($dbId >= 0);
		assert(!empty( $query));

		$common = DS_Common::CreateForCurentDB($dbId);
		$res = $common->getResultArray('', $query);
		echo $common->errormsg;
		assert(empty($common->errormsg));
		
		return $res;
	}
}
?>