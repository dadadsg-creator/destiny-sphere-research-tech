<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteBattles extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{
		$dsql = sprintf ( "delete from 
									ds_battlexp
							where 
									id_userinstance = %d",
							$this->UIID );
		$dres2 = $this->common->executeQuery ( $dsql );
		assert(empty($this->common->errormsg));
		
		$dsql = sprintf ( "select id_battle
				from ds_battleparticipant
				where id_userinstance = %d",
				$this->UIID );
		$dres = $this->common->getQueryArray ( $dsql );
		assert(empty($this->common->errormsg));
		
		if(is_array($dres))
		foreach ( $dres as $k=>$v ) {
			$dsql = sprintf ( "delete from ds_roundreport
					where id_battleround in
					(select id_battleround
					from ds_battleround
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));

			$dsql = sprintf ( "delete from ds_battleresult
					where id_battleround in
					(select id_battleround
					from ds_battleround br
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));
			
			$dsql = sprintf ( "delete from ds_battleparticipanthasunitcontainer
					where id_battleparticipant in
					( select id_battleparticipant
					from ds_battleparticipant
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));

			$dsql = sprintf ( "delete from ds_battleparticipant
					where id_battleparticipant in
					( select id_battleparticipant
					from ds_battleparticipant
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));

			$dsql = sprintf ( "delete from ds_battleround
					where id_battle = %d",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			assert(empty($this->common->errormsg));
			
			//провер€м на наличие в бƒ
			$sqlTMP = "SELECT * FROM pg_class WHERE relkind = 'r' AND relname = 'ds_battlehasattribute'";
			$resTMP = $this->common->getResultArray("", $sqlTMP);
			assert(empty($this->common->errormsg));
			
			if(count($resTMP) > 0)
			{
				$dsql = sprintf ( "delete from ds_battlehasattribute
					where id_battle = %d",
					$v['id_battle'] );
				$dres2 = $this->common->executeQuery ( $dsql );			
				assert(empty($this->common->errormsg));	
			}
			
			
			$dsql = sprintf ( "delete from ds_battlereport
					where id_battle = %d",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery($dsql);
			assert(empty($this->common->errormsg));
			
			$dsql = sprintf ( "delete from ds_battle
					where id_battle = %d",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );				
			assert(empty($this->common->errormsg));
		}//END OF foreach ( $dres as $k=>$v )
	}
}
?>