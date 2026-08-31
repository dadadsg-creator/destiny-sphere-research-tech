<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:36:01 $';

/**
 * ѕредок хандлеров операций инстанса
 *
 */
class MU_ExecOperationUserInstance
{
	/**
	 * Enter description here...
	 *
	 * @var GB_MoneyUserInstanceOperation
	 */
	protected $operation;
	
	public function __construct(GB_MoneyUserInstanceOperation $operation)
	{
		$this->operation = $operation;
	}
	
	public function exec()
	{
		ds_error('Must be implemented in child classes');
	}
}

?>