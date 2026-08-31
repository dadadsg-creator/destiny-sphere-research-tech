<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteCombs extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{		
		$dsql = sprintf ( "delete
	                 from ds_combhasresource
	                 where id_comb in
	                 (select co.id_comb
	                 from ds_comb co
	                 where co.id_userinstance = %d)",
	                 $this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "delete
				from ds_combname
				where id_comb in
				(select id_comb
				from ds_comb
				where id_userinstance = %d)",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "delete from ds_comballowedtradescope
				where id_comb in
				(select id_comb
				from ds_comb
				where id_userinstance = %d)",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));

		$dsql = sprintf ( "select id_comb
				from ds_comb 
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->getQueryArray ( $dsql );
		assert(empty($this->common->errormsg));
		
		if(is_array($dres))
		foreach ( $dres  as $k=>$v ) {
			$this->resetComb ($v['id_comb']);
		}

		$dsql = sprintf ( "delete
				from ds_colony
				where id_comb_from in
				(select id_comb
				from ds_comb
				where id_userinstance = %d)",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "update ds_comb
				set id_userinstance = null,
				combscore = 0
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
	}
	
	function resetComb ( $id_comb ) 
	{	
		//Test input params
		assert($id_comb > 0);		
		
		$e = 0;
		
		$sql = sprintf ( "insert into ds_combhasresource (id_attribute, id_comb, value)
				select a.id_attribute, %d, 0
				from ds_attribute a,
				ds_attributetype att,
				ds_attributename atn
				where a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename in ('BONUS_ABSOLUTE', 'BONUS_RELATIVE', 'CURRENT_AMOUNT', 
				'MAX_AMOUNT', 'AWAY_AMOUNT', 'TURN_UPKEEP', 'TURN_PRODUCTION', 'STORAGE_FACTOR')
				order by a.id_attribute", $id_comb );
		$res2 = $this->common->executeQuery ( $sql );
		assert(empty($this->common->errormsg));
		
		$this->setBeginningBuildings(1, $id_comb, 1);
		
		return true;	
	}

	function setBeginningBuildings($templateid, $id_comb, $celltype) 
	{
	
		settype($id_comb, 'integer');
		assert ($id_comb > 0);
		
		settype($templateid, 'integer');
		assert ($templateid > 0);
		
		settype($celltype, 'integer');
		assert ($celltype > 0);

		$sql = sprintf ( "select ri.ringname
				from ds_ring ri,
				ds_combcomplex cc,
				ds_comb co
				where ri.id_ring = cc.id_ring
				and cc.id_combcomplex = co.id_combcomplex
				and co.id_comb = %d",
				$id_comb );
		$res = $this->common->getQueryArray ( $sql );
		assert(empty($this->common->errormsg));

		if ( $res[0]['ringname'] == 'RING_COLONIZE' ){
			 $colony = 1;
		}

		$sql = sprintf ( "select cte.cellnumber, 
				cte.id_building,
				b.buildingname
				from DS_CellTemplateElement cte,
				ds_building b
				where cte.id_building = b.id_building
				and cte.ID_CellTemplate = %d
				and cte.ID_CellType = %d
				and cte.ID_Building is not null",
				$templateid, $celltype );

		$res = $this->common->getQueryArray ( $sql );
		assert(empty($this->common->errormsg));
		
		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			$sql = sprintf ( "select ID_Cell 
					from DS_Cell 
					where ID_Comb = %d 
					and ID_CellType = %d 
					and CellNumber = %d",
					$id_comb,
					$celltype,
					$res[$i]['cellnumber'] );
			$res2 = $this->common->getQueryArray ( $sql );
			assert(empty($this->common->errormsg));

			$id_cell = $res2[0]['id_cell'];

			if ( isset ( $colony ) && $res[$i]['buildingname'] == 'CENTRAL_GATE_WRECK' ) {
				
				$sql = sprintf ( "select id_building
						from ds_building
						where buildingname = 'CENTRAL_GATE'" );
				$res2 = $this->common->getQueryArray ( $sql );
				assert(empty($this->common->errormsg));

				$sql = sprintf ( "insert into DS_AttachedBuilding (ID_Building, ID_Cell, active, underconstruction, starttime, endtime, inupgrade, id_comb)
   						values ( %d, %d, true, false, null, null, false, %d )",
						$res2[0]['id_building'], $id_cell, $id_comb );
				$res2 = $this->common->executeQuery ( $sql );
				assert(empty($this->common->errormsg));

				$sql  = sprintf ( "select id_attachedbuilding
						from ds_attachedbuilding
						where id_cell = %d",
						$id_cell );
				$res2 = $this->common->getQueryArray ( $sql );
				assert(empty($this->common->errormsg));
				
				$mab = $res2[0]['id_attachedbuilding'];
			
			} else {

				$sql = sprintf ( "insert into DS_AttachedBuilding (ID_Building, ID_Cell, active, underconstruction, starttime, endtime, inupgrade, id_comb)
   						values ( %d, %d, true, false, null, null, false, %d )",
						$res[$i]['id_building'], $id_cell, $id_comb );
				$res2 = $this->common->executeQuery ( $sql );
				assert(empty($this->common->errormsg));

				$sql  = sprintf ( "select id_attachedbuilding
						from ds_attachedbuilding
						where id_cell = %d",
						$id_cell );
				$res2 = $this->common->getQueryArray ( $sql );
				assert(empty($this->common->errormsg));
				
				$mab = $res2[0]['id_attachedbuilding'];

				$sql = sprintf ( "update ds_combhasresource
						set value = value::real + t.val
						from
						(select a.id_attribute as id_attr,
						bha.value as val
						from ds_buildinghasattribute bha,
						ds_attribute a,
						ds_attributename atn,
						ds_attributetype att
						where bha.id_attribute = a.id_attribute
						and a.id_attributename = atn.id_attributename
						and a.id_attributetype = att.id_attributetype
						and att.typename in ( 'TURN_PRODUCTION', 'TURN_UPKEEP', 'MAX_AMOUNT', 'STORAGE_FACTOR' )
						and bha.id_building = %d) t
						where id_comb = %d
						and id_attribute = t.id_attr",
						$res[$i]['id_building'],
						$id_comb );
				$res2 = $this->common->executeQuery ( $sql );				
				assert(empty($this->common->errormsg));

				$sql = sprintf ( "select 1 as destr
						from ds_buildinghasattribute bha,
						ds_attribute a,
						ds_attributename atn,
						ds_attributetype att
						where bha.id_attribute = a.id_attribute
						and a.id_attributename = atn.id_attributename
						and a.id_attributetype = att.id_attributetype
						and att.typename = 'BEGINNING_FLAG'
						and atn.attributename = 'DESTROYABLE'
						and bha.id_building = %d",
						$res[$i]['id_building'] );
				$res2 = $this->common->getQueryArray ( $sql );
				assert(empty($this->common->errormsg));

				if ( !isset ( $res2[0]['destr'] ) ) {
					$res2[0]['destr'] = 0;
				}

				if ( $res2[0]['destr'] != 1 ) {
					$sql = sprintf ( "insert into DS_AttachedBuildingHasAttribute 
							(ID_AttachedBuilding, ID_Attribute)
							values (%d, (select ID_Attribute from DS_Attribute a, DS_AttributeName atn, DS_AttributeType att
							where a.ID_AttributeName = atn.ID_AttributeName and a.ID_AttributeType = att.ID_AttributeType
							and att.TypeName = 'CURRENT_FLAG' and atn.AttributeName = 'DESTROYABLE'))", $mab );
					$res2 = $this->common->executeQuery ( $sql );
					assert(empty($this->common->errormsg));
				}

				$sql = sprintf ( "select 1 as deact
						from ds_buildinghasattribute bha,
						ds_attribute a,
						ds_attributename atn,
						ds_attributetype att
						where bha.id_attribute = a.id_attribute
						and a.id_attributename = atn.id_attributename
						and a.id_attributetype = att.id_attributetype
						and att.typename = 'BEGINNING_FLAG'
						and atn.attributename = 'DEACTIVEABLE'
						and bha.id_building = %d",
						$res[$i]['id_building'] );
				$res2 = $this->common->getQueryArray ( $sql );
				assert(empty($this->common->errormsg));

				if ( !isset ( $res2[0]['deact'] ) ) {
					$res2[0]['deact'] = 0;
				}

				if ( $res2[0]['deact'] != 1 ) {
					$sql = sprintf ( "insert into DS_AttachedBuildingHasAttribute 
							(ID_AttachedBuilding, ID_Attribute)
							values (%d, (select ID_Attribute from DS_Attribute a, DS_AttributeName atn, DS_AttributeType att
							where a.ID_AttributeName = atn.ID_AttributeName and a.ID_AttributeType = att.ID_AttributeType
							and att.TypeName = 'CURRENT_FLAG' and atn.AttributeName = 'DEACTIVEABLE'))", $mab );
					$res2 = $this->common->executeQuery ( $sql );
					assert(empty($this->common->errormsg));
				}
/*
				if ( $res[$i]['buildingname'] == 'SHELTER' ) {
					$sql = sprintf ( "insert into ds_combhasresource (id_comb, id_attribute, value )
							select %d, a.id_attribute, value
							from ds_buildinghasattribute bha,
							ds_attribute a,
							ds_attributetype att
							where bha.id_attribute = a.id_attribute
							and a.id_attributetype = att.id_attributetype
							and att.typename = 'STORAGE_FACTOR'
							and bha.id_building = %d",
							$id_comb,
							$res[$i]['id_building'] );
					$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings10');
				}
*/				
			}
		}
	}//END OF METHOD
}
?>