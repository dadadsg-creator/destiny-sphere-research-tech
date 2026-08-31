<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteUserInstanceDetails extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{		
		$dsql = sprintf ( "delete from
				ds_useraction
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "delete from
				ds_flashkey
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "delete from
				ds_userhasability
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "delete from
				ds_loginqueue
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "delete from
				ds_gamehelper
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "delete from
				ds_gamemaster
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "delete from
					ds_userbattlexp
					where id_userinstance = %d",
					$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
       	$dsql = sprintf ( "delete from
					ds_userinstance
					where id_userinstance = %d",
					$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
	}
}
?>