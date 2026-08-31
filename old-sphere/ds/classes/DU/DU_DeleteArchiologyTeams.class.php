<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteArchiologyTeams extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{
		// delete archeology
		$dsql = sprintf ( "
			DELETE FROM 
				ds_archeologicalteam
			WHERE 
				id_comb IN (
					SELECT id_comb
					FROM ds_comb 
					WHERE id_userinstance = %d
				)",
			$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
	}
}
?>