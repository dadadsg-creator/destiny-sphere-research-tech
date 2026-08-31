<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ѕредок хандлеров операций инстанса
 *
 */
class MU_ExecOperation
{
	/**
	 * Enter description here...
	 *
	 * @var MU_EntityOperation
	 */
	protected $operation;
	
	public function __construct(MU_EntityOperation $operation)
	{
		$this->operation = $operation;
	}
	
	public function exec()
	{
		ds_error('Must be implemented in child classes');
	}
}

?>