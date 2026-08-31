<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * БВАзовый класс для всех методоу удаления частей инстанса
 *
 */
class DU_DeleteBase extends BS_Base
{
	/**
	 * РАбота с БД
	 *
	 * @var DS_Common
	 */
	protected $common;
	
	/**
	 * ИНстанс ID ск аторым работем
	 *
	 * @var integer
	 */
	protected $UIID;
	
	
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct();
		
		$this->common = $common;
		
		$this->UIID = $UIID;
	}
	
	public function Exec()
	{
		ds_error('Must be redifined in child');
	}
}

?>