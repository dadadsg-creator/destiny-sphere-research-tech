<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Сущность вознаграждения игрока
 */

class DE_Reward extends DE_Base
{
	/**
	 * Вознаграждение
	 *
	 * @var array
	 */
	private $reward;

	function __construct($id)
	{
		assert(is_integer($id ) ); // && $id > 0

		parent::__construct($id);

		$data = DS_Reward::GetByUserInstanceId($id);

		$this->reward = $data;

		$this->_invariant('construct');
	}

	/**
	 * Создание на основание UserInstanceID
	 *
	 * @param int $id
	 * @return DE_ArmyWeight
	 */
	static public function CreateFromUserInstanceID($id)
	{
		//assert((int)$id >= 0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, (int)$id);
	}

	/**
	 * Возвращает значение
	 *
	 * @return float
	 */
	public function getReward($resourceName)
	{
		assert(key_exists($resourceName, $this->reward) && $this->reward[$resourceName] >= 0);
		
		return $this->reward[$resourceName];
	}

	/**
	 * Изменяем значение вознаграждение за конкретный ресурс
	 *
	 * @param string $resourceName
	 * @param float $rewardRes
	 */
	public function deltaReward($resourceName, $rewardRes)
	{
		assert(is_float($rewardRes));
		assert(key_exists($resourceName, $this->reward));

		$this->reward[$resourceName] += $rewardRes;
		
		// Проверяем, чтобы новое значение было не меньше 0
		if(!($this->reward[$resourceName] >= 0))
		{
			ds_error('Try to set negative reward');	
		}
		
		$this->isModifyed = true;
	}

	protected function _invariant($mode = 'public')
	{
		parent::_invariant($mode);

		assert($this->reward['KADERIUM'] >= 0);
		assert($this->reward['NANO_CRYSTAL'] >= 0);
		assert($this->reward['STASIS_ENERGY'] >= 0);
		assert($this->reward['FOOD'] >= 0);
		assert($this->reward['PRODIUM'] >= 0);
	}

	public function commit()
	{
		parent::commit();
		
		if($this->isModifyed == true)
		{
			if(DS_Reward::UpdateReward($this))
			{
				$this->isModifyed = false;
			}
		}
	}
}


?>