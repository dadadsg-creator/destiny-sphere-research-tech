<?php $CVS_Version = '$Revision$ ; $Date$';
// общие методы для классов работающий с исследованием науки
class UA_Research extends UA_Action {

	public $sciencename;
	public $scienceid;
	public $branchid;
	public $scientists;
	public $researchcosts;
	public $cellnum;

	function UA_Research () 
	{
		UA_Action::UA_Action ();
	}

	// задать параметры эвента
	function createEventParams () {

		global $params;

		$this->eventparams["ID_Science"] = $this->scienceid;
		$this->eventparams["ID_ScienceBranch"] = $this->branchid;
		$this->eventparams["ID_UserInstance"] = $params["uiid"];
		$this->eventparams["ID_Language"] = $params["lang"];
		$this->eventparams["ID_Comb"] = $this->comb;
		$this->eventparams["ScientistsCount"] = $this->scientists;
		$this->eventparams["TotalResearchCosts"] = $this->researchcosts;
		$this->eventparams["ID_World"] = $this->world;
		
		return true;
	}
	
	// присутствуют ли необходимые здания
	function isNeedsBuildingPresent ( $science, $comb ) {
		
		global $common;
		global $params;

		$sql = sprintf ( "select 
				(select count(snb.id_building)
				from ds_scienceneedsbuilding snb
				where snb.id_science = %d) = 
				(select count(snb.id_building)
				from ds_scienceneedsbuilding snb,
				ds_attachedbuilding ab
				where ab.id_building = snb.id_building
				and ab.id_comb = co.id_comb
				and snb.id_science = %d) as allow
				from ds_comb co
				where co.id_comb = %d",
				$science, $science, $comb );
	
		$res = $common->getResultArray ( "is_needs_building_present_for_science_id_science_id_science_id_comb_".$science."_".$science."_".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_Research::isNeedsBuildingPresent1";
			
			return false;
		}

		if ( $res[0]['allow'] == 'f' ) {
			$this->errormsg .= " SYSTEM ERROR UA_Research::isNeedsBuildingPresent2";
			
			return false;
		}

		$res = "";

		return true;
	}

	// есть необходимая наука
	function isScienceEnough ( $uiid, $science ) {
		
		global $common;

		$sql = sprintf ( "select 
				(select count(id_science_2)
				from ds_scienceneedsscience
				where id_science_1 = s.id_science) = 
				(select count(sns.id_science_2)
				from ds_scienceneedsscience sns,
				ds_userhasscience uhs
				where sns.id_science_2 = uhs.id_science
				and sns.id_science_1 = s.id_science
				and uhs.id_userinstance = %d) as allow
				from ds_science s
				where s.id_science = %d",
				$uiid, $science );

		$res = $common->getResultArray ( "is_science_enough_for_science_is_userinstance_id_science_".$uiid."_".$science, $sql, "", $uiid, "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " UA_Research::isScienceEnough1";
			
			return false;
		}

		if ( $res[0]['allow'] == 'f' ) {
			$this->errormsg .= " UA_Research::isScienceEnough2";
			
			return false;
		}
		
		$res = "";

		return true;
	}

	// id накуи по названию
	function getScienceID ( $sciencename ) {

		global $common;

		$sql = sprintf ( "
				select id_science, 
				id_branch,
				researchcosts
				from ds_science
				where sciencename = '%s'",
				$sciencename );
		$res = $common->getResultArray ( "is_science_sciencename_".$sciencename, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " UA_Research::getScienceID1";
			
			return false;
		}
		
		$this->scienceid = (int)$res[0]['id_science'];
		$this->branchid = (int)$res[0]['id_branch'];
		$this->researchcosts = (float)$res[0]['researchcosts'];

		$res = "";

		return true;
	}

	// есть ли у игрока уже такая наука
	function isUserHasScience ( $scienceid, $uiid ) {

		global $common;

		$sql = sprintf ( "select id_science
				from ds_userhasscience
				where id_userinstance = %d
				and id_science = %d",
				$uiid, $scienceid );

		$res = $common->getResultArray ( "id_science_id_userinstance_id_science_".$uiid."_".$scienceid, $sql, "", $uiid, "LOCAL_CACHE" );
		if ( $res === false ) {
			$this->errormsg .= " UA_Research::isUserHasScience1";
			
			return false;
		}

		if ( sizeof ( $res ) != 0 ) {
			$this->errormsg .= " UA_Research::isUserHasScience2";
			
			return false;
		}
		
		$res = "";

		return true;
	}

	// доступна ли наука расе
	function isBranchDisabled ( $id_world, $id_branch ) {

		global $common;

		$sql = sprintf ( "
				select
					id_branch
				from
					ds_disabledsciencebranch
				where
					id_world = %d
					AND id_branch = %d
				",
				$id_world,
				$id_branch
				);

		$res = $common->getResultArray ( "id_world_disabled_id_branch_".$id_world."_".$id_branch, $sql, "", "", "GLOBAL_CACHE" );
		
		assert( empty($common->errormsg) );
		
		if ( count( $res ) == 0 ) {

			return false;
		}
		
		return true;
	}

	
	function isScienceDisabled($id_world, $id_science)
	{
		global $common;
		
		assert(is_int($id_world) && $id_world > 0);
		assert(is_int($id_science) && $id_science > 0);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_disabledscience
			WHERE
				id_world = $id_world
				AND
				id_science = $id_science
			";
		$res = $common->getResultArray("" ,$sql, "", "", "");
		assert(empty($common->errormsg));
		
		if($res[0]['count'] > 0)
		{
			return true;
		}
		
		return false;
	}

	// идет ли на соте уже исследование
	function isCombHasResearch ( $combid ) {
		
		global $common;
		global $params;

		$sql = sprintf ( "select cbeq.id_eventqueue
				from ds_combbindeventqueue cbeq,
				ds_eventqueue eq,
				ds_event e
				where cbeq.id_comb = %d
				and cbeq.id_eventqueue = eq.id_eventqueue
				and eq.id_event = e.id_event
				and eq.isTrash = false
				and e.eventname = 'NEW_RESEARCH'",
				$combid );

		$res = $common->getResultArray ( "is_comb_has_research_".$combid, $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg .= " UA_Research::isCombHasResearch1";
			
			return false;
		}

		if ( sizeof ( $res ) != 0 ) {
			$this->errormsg .= " UA_Research::isCombHasResearch2";
			
			return false;
		}

		$res = "";

		return true;
	}
	
	// исследует ли игрок уже какую-нибудь науку
	function isUserHasResearch ( $uiid ) {
		
		global $common;
		global $params;

		$sql = sprintf ( "select id_science
				from ds_userhasscience
				where id_userinstance = %d
				and inresearch = true ",
				$uiid);

		$res = $common->getResultArray ( "is_user_has_research_".$uiid, $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg .= " UA_Research::isUserHasResearch1";
			
			return false;
		}

		if ( sizeof ( $res ) != 0 ) {
			$this->errormsg .= " UA_Research::isUserHasResearch2";
			
			return false;
		}

		$res = "";

		return true;
	}
}

?>