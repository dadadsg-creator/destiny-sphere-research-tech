<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_PatchUserInstance extends GP_Patch
{
	private $userInstanceData;

	static public $userInstancePatches = array
	(
		//'GP_UserInstanceReligion', //Патч пропавших ивентов религии - пока отключаем вроде бы все путем там уже починили.
	);

	public function __construct($userInstanceData)
	{
		assert(is_array($userInstanceData) && count($userInstanceData) > 0);
		$this->userInstanceData = $userInstanceData;
	}

	public function patch()
	{
		self::_out(" * Start patch user ".$this->userInstanceData['username']);

		foreach(self::$userInstancePatches as $k=>$patchName)
		{
			$patch = new $patchName($this->userInstanceData);
			$patch->patch();
		}

		$sql = "SELECT *
				FROM 
					ds_comb as c
					INNER JOIN ds_combname as cn on (cn.id_comb = c.id_comb)
				WHERE c.id_userinstance = ".$this->userInstanceData['id_userinstance']."
				ORDER BY cn.combname";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));

		foreach($rs as $k=>$combData)
		{
			$combPatch = new GP_PatchComb($combData);
			$combPatch->patch();
		}
	}
}


?>