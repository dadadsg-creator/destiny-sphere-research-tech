<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteReligion extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{		
		$dsql = sprintf ( "delete from
				ds_userhasreligion
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
	}
}
?>