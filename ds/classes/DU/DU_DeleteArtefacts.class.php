<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteArtefacts extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{
		$dsql = sprintf ( "select id_artefact
				from ds_combhasartefact
				where id_comb in
				( select id_comb
				from ds_comb
				where id_userinstance = %d )",
				$this->UIID );
		$dres = $this->common->getQueryArray ( $dsql );
		assert(empty($this->common->errormsg));
		
		foreach ( $dres as $k=>$v ) 
		{								
			$dsql = sprintf ( "delete from ds_combhasartefact
					where id_artefact = %d",
					$v['id_artefact'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));

			$dsql = sprintf ( "delete from ds_artefactinstance
					where id_artefactinstance = %d",
					$v['id_artefact'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));
		}

		$dsql = sprintf ( "select aab.id_artefact,
				co.id_comb,
				ars.scopename,
				ab.id_attachedbuilding
				from ds_attachedbuilding ab,
				ds_cell ce,
				ds_comb co,
				ds_artefactattachedbuilding aab,
				ds_artefactinstance ai,
				ds_artefact a,
				ds_artefactscope ars
				where ab.id_cell = ce.id_cell
				and ce.id_comb = co.id_comb
				and co.id_userinstance = %d
				and ab.id_attachedbuilding = aab.id_attachedbuilding
				and aab.id_artefact = ai.id_artefactinstance
				and ai.id_artefact = a.id_artefact
				and a.id_artefactscope = ars.id_artefactscope",
				$this->UIID );
		$dres = $this->common->getQueryArray ( $dsql );
		assert(empty($this->common->errormsg));
				
		foreach ( $dres as $k=>$v ) 
		{			
			
			$sql = sprintf ( "delete from ds_artefactattachedcomb 
					where id_artefact = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			assert(empty($this->common->errormsg));

			$sql = sprintf ( "delete from ds_artefactattachedcomplex 
					where id_artefact = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			assert(empty($this->common->errormsg));

			$sql = sprintf ( "delete from ds_artefactattachedalliance
					where id_artefact = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			assert(empty($this->common->errormsg));

			$sql = sprintf ( "delete from ds_artefactattachedbuilding
					where id_artefact = %d",
					$v['id_artefact']);
			$res2 = $this->common->executeQuery ( $sql );
			assert(empty($this->common->errormsg));
			
			$sql = sprintf ( "delete from ds_artefactinstance
					where id_artefactinstance = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			assert(empty($this->common->errormsg));
		}//END OF FOREACH()
	}
}
?>