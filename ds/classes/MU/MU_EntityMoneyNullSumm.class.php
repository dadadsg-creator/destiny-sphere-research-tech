<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_EntityMoneyNullSumm implements MU_EntityMoneySummInterface
{	
	public function __construct()
	{
	}
	
	public function getAmount()
	{
		return 0;
	}
	
	public function getCreditName()
	{
		return 'SC';
	}
	
	public function getFieldName()
	{
		return 'sc_amount';
	}
	
	public function getAmountGC()
	{
		return 0;
	}
	
	public function getAmountSC()
	{
		return 0;
	}
}

?>