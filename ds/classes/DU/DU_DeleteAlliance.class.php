<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteAlliance extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{
		// delete alliance
		$dsql = "
			SELECT id_alliance
			FROM ds_userhasalliance
			WHERE id_userinstance = ".$this->UIID;				
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		if(count($dres) == 0)
		{
			return;
		}
		$id_alliance = $dres[0]['id_alliance'];
		$dsql = sprintf ( "delete from ds_userhasalliancestatus
					where id_userinstance = %d",
		$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "delete from ds_userhasalliance
					where id_userinstance = %d",
		$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "select count(id_userinstance) as c
					from ds_userhasalliance
					where id_alliance = %d",
		$id_alliance );
		$dres = $this->common->getQueryArray ( $dsql );
		assert(empty($this->common->errormsg));

		if($dres[0]['c'] == 0) 
		{
			$dsql = sprintf ( "delete from ds_alliance
						where id_alliance = %d",
			$id_alliance );
			$dres = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));
		}
	}
}
?>