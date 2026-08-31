<?php $CVS_Version = '$Revision$ ; $Date$';

interface MU_EntityMoneySummInterface
{
	public function getAmount();
	
	public function getCreditName();
	
	public function getFieldName();

	public function getAmountGC();
	
	public function getAmountSC();
}

?>