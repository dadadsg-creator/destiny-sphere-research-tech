<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Патчит уже все что относиться к синстнсу и перебирает сот инстнса и выызвает метод патча их
 *
 */
class MP_PatchUserInstance extends MP_Patch
{
	/**
	 * Массив с данными по инстансу	 
	 *
	 * @var array
	 */
	private $userInstanceData;

	static public $userInstancePatches = array
	(
		//'MP_UserInstanceReligion', //Патч пропавших ивентов религии - пока отключаем вроде бы все путем там уже починили.
		'MP_UserInstanceEmptyArmys'//Убиваем пустые армии у инстанса
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

		//Извекаем все соты данного игрока
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
			$combPatch = new MP_PatchComb($combData);
			$combPatch->patch();
		}
	}
}


?>