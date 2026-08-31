<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteBuildings extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{		
		//delete buildings
		$dsql = sprintf ( "delete
	                 from ds_attachedbuildinghasattribute
	                 where id_attachedbuilding in
	                 (select ab.id_attachedbuilding
	                 from ds_attachedbuilding ab,
	                 ds_comb co
	                 where ab.id_comb = co.id_comb
	                 and co.id_userinstance = %d)",
	                 $this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		
		$dsql = sprintf ( "delete	
	                 from ds_unitinproduction
	                 where id_attachedbuilding in
	                 (select ab.id_attachedbuilding
	                 from ds_attachedbuilding ab,
	                 ds_comb co
	                 where ab.id_comb = co.id_comb
	                 and co.id_userinstance = %d)",
	                 $this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		
		//Как показала практика иногд етсь от ивентов которых нет
		$sql = "DELETE FROM ds_attachedbuildingbindeventqueue
				WHERE 
					id_attachedbuilding IN 
					(
						SELECT id_attachedbuilding
						FROM ds_attachedbuilding
						WHERE
     						id_comb IN
     						(
      							SELECT id_comb
      							FROM ds_comb
      							WHERE id_userinstance = ".$this->UIID."
     						)
					)";
		//$dres = $this->common->executeQuery ( $sql );
		//assert(empty($this->common->errormsg));
		
		//ds_attachedbuilding
		$dsql = sprintf ( "delete
	                 from ds_attachedbuilding
	                 where id_comb in
	                 (select co.id_comb
	                 from ds_comb co
	                 where co.id_userinstance = %d)",
	                 $this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
	}
}
?>