<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteArmys extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{
		$dsql = sprintf ( "
			DELETE FROM ds_unitcontaineraction
			WHERE 
				id_unitcontainer IN (
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d 
				)",
			$this->UIID );
		$dres = $this->common->executeQuery  ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainerhasunit
			WHERE 
				id_army IN	(
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d 
				)",
			$this->UIID );
		$dres = $this->common->executeQuery  ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainerhasattribute
			WHERE 
				id_unitcontainer IN (
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d 
				)",
			$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		

		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainerbindeventqueue
			WHERE 
				id_unitcontainer IN (
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d 
				)",
			$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainer
			WHERE id_userinstance = %d",
			$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
	}
}
?>