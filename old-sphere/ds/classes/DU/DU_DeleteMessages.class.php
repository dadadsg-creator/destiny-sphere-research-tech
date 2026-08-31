<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteMessages extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{
		$dsql = sprintf ( "
			DELETE FROM ds_userhasmessage
			WHERE 
				id_userinstance = %d",
			$this->UIID );
		$dres = $this->common->executeQuery  ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "
				SELECT 
					m.id_message
				FROM 
					ds_message m,
					ds_userhasmessage uhm
				WHERE 
					m.id_message = uhm.id_message AND
					(m.id_from_userinstance = %d OR uhm.id_userinstance = %d)" ,
				$this->UIID,
				$this->UIID
				);
		$dres = $this->common->getQueryArray($dsql);
		assert(empty($this->common->errormsg));
				
		foreach($dres as $k3=>$v3) 
		{		
			$dsql = sprintf ( "delete from
					ds_userhasmessage
					where id_message = %d",
					$v3['id_message'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));
		}
		
		$dsql = sprintf ( "delete from ds_message
				where id_from_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "delete from ds_userallowedmessagescope
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
	}
}
?>