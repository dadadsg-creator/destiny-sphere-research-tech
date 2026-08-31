<?php $CVS_Version = '$Revision$ ; $Date$';
$common = DS_Common::CreateForCurentDB($dbId);
		$res = $common->getResultArray('', $query);
		assert(empty($common->errormsg));
		return $res;
//SU_ViewBattleSystemLogs:_getBattlesList();
?>