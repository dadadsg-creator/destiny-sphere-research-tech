<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_DeleteUserInstance 
{

	/** Text of last error */
	public $errormsg = '';

	/** Object for working with DB */
	public $common = '';

	/** Output during deleting */
	public $output = false;

	function setOutput ($mode = true){
		settype ($mode, 'boolean');
		$this->output = $mode;
	}

	function setCommon ($common) {
		settype ($mode, 'object');
		$this->assert (strtolower(get_class($common)) == 'ds_common', 'setCommon-1');
		$this->common = $common;
	}	

	/** Output during deleting */
	function out($msg)
	{
		if(php_sapi_name() != "apache")
		{
			return;
		} 
		
		if(!$this->output)
		{
			return;
		}
				
		echo $msg."\n";
		flush();
	}

	/** Deleteing of userinstance */
	function drop($id_userinstance)
	{		
		//Test input params
		settype($id_userinstance, 'integer');
		assert($id_userinstance > 0);
		
		$this->out("---DELETE INSTANCE $id_userinstance PROCESS BEGIN---<br>");

		$this->out('Deleting events<br>');
		
		$dsql = "
				UPDATE
					ds_eventqueue
				SET
					istrash = true
				WHERE
					id_eventqueue in (
						SELECT eho.id_eventqueue
				       	FROM
							ds_eventhasoption eho,
				       		ds_eventqueue eq
				       	WHERE
							eho.id_eventoption = 154 AND
				       		eho.id_eventqueue = eq.id_eventqueue AND
				       		eq.istrash = false AND
							eho.value = ".$id_userinstance.")";

		$dres = $this->common->executeQuery ( $dsql );
		$this->assert ($this->common->errormsg == '', 'Drop: 59-1');

		$dsql = "
				SELECT
					eho.id_eventqueue
				FROM
					ds_eventhasoption eho,
					ds_eventqueue eq
				WHERE
					eho.id_eventoption in ( 133, 155 ) AND
				    eho.id_eventqueue = eq.id_eventqueue AND
				    eq.istrash = false AND
				    eho.value = ".$id_userinstance;
		$dres = $this->common->getQueryArray ( $dsql );
		$this->assert ($this->common->errormsg == '', 'Drop: 59-2');

		if(is_array($dres))
		foreach ( $dres as $k=>$v ) {

			$this->out('Deleting event with id ='.$v['id_eventqueue'].'<br>');

			$dsql = sprintf ( "
							DELETE FROM ds_unitcontaineraction
				            WHERE
								id_unitcontainer in (
				             		SELECT
				             			ucbeq.id_unitcontainer
				             		FROM
										ds_unitcontainerbindeventqueue ucbeq
					        		WHERE
										ucbeq.id_eventqueue = %d)",
				        	$v['id_eventqueue'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert ($this->common->errormsg == '', 'Drop: 60');

			$dsql = sprintf ( "
				 	UPDATE
						ds_unitcontainer
			        SET
						inaction = false,
			        	id_currentcomb = null
			        WHERE
						id_unitcontainer in (
							SELECT
			             		ucbeq.id_unitcontainer
			             	FROM ds_unitcontainerbindeventqueue ucbeq
			        		WHERE ucbeq.id_eventqueue = %d
						)",
			        $v['id_eventqueue'] );
			$dres2 = $this->common->executeQuery ( $dsql );
	   		$this->assert ($this->common->errormsg == '', 'Drop: 61');

			$dsql = sprintf ( "
				  	DELETE FROM
						ds_unitcontainerbindeventqueue
			        WHERE
						id_unitcontainer in (
			            	SELECT
			             		ucbeq.id_unitcontainer
			             	FROM
								ds_unitcontainerbindeventqueue ucbeq
				        	WHERE ucbeq.id_eventqueue = %d
						)",
			        $v['id_eventqueue'] );
			$dres2 = $this->common->executeQuery ( $dsql );
	   		$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance60');

			$dsql = sprintf ( "
				UPDATE
					ds_eventqueue
			    SET
					istrash = true
			    WHERE
					id_eventqueue = %d",
			    $v['id_eventqueue'] );
			$dres2 = $this->common->executeQuery ( $dsql );
	   		$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance62');

		}//foreach()

		$dsql = sprintf ( "
				SELECT
					ID_EventQueue
				FROM
					DS_EventParticipant
				WHERE
					ID_UserInstance = %d",
				$id_userinstance );
		$dres = $this->common->getQueryArray ( $dsql );
		$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance1');

		if(is_array($dres))
		foreach ( $dres as $k=>$v ) {

			$this->out('Deleting EventParticipant for event with id ='.$v['id_eventqueue'].'<br>');

			$sql = sprintf ( "
				DELETE FROM
					DS_EventParticipant
				WHERE
					ID_EventQueue = %d",
				$v['id_eventqueue'] );
			$res2 = $this->common->executeQuery ($sql);
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance2');

			$sql = sprintf ( "
				DELETE FROM
					DS_EventHasOption
				WHERE
					ID_EventQueue = %d",
				$v['id_eventqueue'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance3');

			$sql = sprintf ( "
				DELETE FROM
					DS_AttachedBuildingBindEventQueue
				WHERE
					ID_EventQueue = %d",
				$v['id_eventqueue'] );
				$res2 = $this->common->executeQuery ( $sql );
				$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance4');

			$sql = sprintf ( "
				DELETE FROM
					DS_UserHasScienceBindEventQueue
				WHERE
					ID_EventQueue = %d",
				$v['id_eventqueue'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance5');

			$sql = sprintf ( "DELETE FROM
								DS_CombBindEventQueue
							  WHERE
							  	ID_EventQueue = %d",
								$v['id_eventqueue'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance6');

			// delete trade
			$sql = sprintf ( "
					SELECT
						id_trade
					FROM
						ds_tradebindeventqueue
					WHERE
						id_eventqueue = %d",
					$v['id_eventqueue'] );
			$res2 = $this->common->getQueryArray ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance7');

			$id_trade = '';

			if ( $res2 ) {
				$id_trade = $res2[0]['id_trade'];

				$sql = sprintf ( "
						DELETE FROM
							ds_tradebindeventqueue
						WHERE
							id_trade = %d",
						$id_trade );
				$res2 = $this->common->executeQuery ( $sql );
				$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance8-1');

				$sql = sprintf ( "
						DELETE FROM ds_trade
						WHERE id_trade =  '%d';",
						$id_trade );
	   			$res2 = $this->common->executeQuery ( $sql );
				$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance8-2'." Error: ".$sql);
				$id_trade = "";
			}//END OF if(@$id_trade)


			// unit container actions
			$sql = sprintf ( "
				DELETE FROM
					ds_unitcontaineraction
				WHERE
					id_unitcontainer = (
						SELECT
							id_unitcontainer
						FROM
							ds_unitcontainerbindeventqueue
						WHERE
							ID_EventQueue = %d
					)",
					$v['id_eventqueue'] );

			$res2 = $this->common->executeQuery ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance9');

			$sql = sprintf ( "
				UPDATE
					ds_unitcontainer
					set inaction = false
				WHERE
					id_unitcontainer =	(
						SELECT id_unitcontainer
						FROM ds_unitcontainerbindeventqueue
						WHERE ID_EventQueue = %d
					)",
					$v['id_eventqueue'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance10');

			$sql = sprintf ( "
				DELETE FROM
					ds_unitcontainerbindeventqueue
				WHERE ID_EventQueue = %d",
				$v['id_eventqueue'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance11');

			$sql = sprintf ( "
				DELETE FROM
					DS_EventQueue
				WHERE ID_EventQueue = %d",
				$v['id_eventqueue'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance12');

		}//END OF foreach()

		$this->out('Delete archiology<br>');
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
			$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance13');

		$this->out('Delete Army<br>');
		$dsql = sprintf ( "
			DELETE FROM ds_unitcontaineraction
			WHERE
				id_unitcontainer IN (
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d
				)",
			$id_userinstance );
		$dres = $this->common->executeQuery  ( $dsql );
		$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance14');

		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainerhasunit
			WHERE
				id_army IN	(
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d
				)",
			$id_userinstance );
		$dres = $this->common->executeQuery  ( $dsql );
		$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance15');

		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainerhasattribute
			WHERE
				id_unitcontainer IN (
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d
				)",
			$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert ($this->common->errormsg == '', 'Drop: dropUserInstance16');


		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainerbindeventqueue
			WHERE
				id_unitcontainer IN (
					SELECT id_unitcontainer
					FROM ds_unitcontainer
					WHERE id_userinstance = %d
				)",
			$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance16-1');

		$dsql = sprintf ( "
			DELETE FROM ds_unitcontainer
			WHERE id_userinstance = %d",
			$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance17');

		$this->out('Delete messages<br>');
		// delete messages
		$dsql = sprintf ( "
			DELETE FROM ds_userhasmessage
			WHERE
				id_userinstance = %d",
			$id_userinstance );
		$dres = $this->common->executeQuery  ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance18');

		$dsql = sprintf ( "
				SELECT
					m.id_message
				FROM
					ds_message m,
					ds_userhasmessage uhm
				WHERE
					m.id_message = uhm.id_message AND
					(m.id_from_userinstance = %d OR uhm.id_userinstance = %d)" ,
				$id_userinstance,
				$id_userinstance
				);
		$dres = $this->common->getQueryArray($dsql);
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance19');

		if(is_array($dres))
		foreach ( $dres as $k3=>$v3 ) {

			$this->out('Delete attributes for message with id = '.$v3['id_message'].' <br>');
			$dsql = sprintf ( "delete from
					ds_userhasmessage
					where id_message = %d",
					$v3['id_message'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance19-1');
		}

		$dsql = sprintf ( "delete from ds_message
				where id_from_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance20');

		$this->out('Delete artifacts <br>');
		$dsql = sprintf ( "select id_artefact
				from ds_combhasartefact
				where id_comb in
				( select id_comb
				from ds_comb
				where id_userinstance = %d )",
				$id_userinstance );
		$dres = $this->common->getQueryArray ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance21');

		if(is_array($dres))
		foreach ( $dres as $k=>$v ) {

			$this->out('Delete artifact with ID = '.$v['id_artefact'].' <br>');

			$this->out('Delete artifact with id = '.$v['id_artefact'].'  from comb <br>');

			$dsql = sprintf ( "delete from ds_combhasartefact
					where id_artefact = %d",
					$v['id_artefact'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance22');

			$dsql = sprintf ( "delete from ds_artefactinstance
					where id_artefactinstance = %d",
					$v['id_artefact'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance23');
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
				$id_userinstance );
		$dres = $this->common->getQueryArray ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance24');

		if(is_array($dres))
		foreach ( $dres as $k=>$v ) {

			$this->out('Delete artifact with id = '.$v['id_artefact'].'  from complex, buildings, aliance <br>');

			$sql = sprintf ( "delete from ds_artefactattachedcomb
					where id_artefact = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance26');

			$sql = sprintf ( "delete from ds_artefactattachedcomplex
					where id_artefact = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance28');

			$sql = sprintf ( "delete from ds_artefactattachedalliance
					where id_artefact = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance32');

			$sql = sprintf ( "delete from ds_artefactattachedbuilding
					where id_artefact = %d",
					$v['id_artefact']);
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance34');

			$sql = sprintf ( "delete from ds_artefactinstance
					where id_artefactinstance = %d",
					$v['id_artefact'] );
			$res2 = $this->common->executeQuery ( $sql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance35');
		}//END OF FOREACH()

		// delete alliance
		$dsql = sprintf ( "select id_alliance
				from ds_userhasalliance
				where id_userinstance = %d",
				$id_userinstance );

		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance36');

		if ( $dres ) {

			$this->out('Delete alliance <br>');

			$id_alliance = $dres[0]['id_alliance'];
			$dsql = sprintf ( "delete from ds_userhasalliancestatus
					where id_userinstance = %d",
					$id_userinstance );
			$dres = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance37');

			$dsql = sprintf ( "delete from ds_userhasalliance
					where id_userinstance = %d",
					$id_userinstance );
			$dres = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance38');

			$dsql = sprintf ( "select count(id_userinstance) as c
					from ds_userhasalliance
					where id_alliance = %d",
					$id_alliance );
			$dres = $this->common->getQueryArray ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance39');

			if ( $dres[0]['c'] == 0 ) {
				$dsql = sprintf ( "delete from ds_alliance
						where id_alliance = %d",
						$id_alliance );
				$dres = $this->common->executeQuery ( $dsql );
				$this->assert($this->common->errormsg == '','Drop:dropUserInstance40');
			}
		}//if ( count($dres) > 0 )

		$dsql = sprintf ( "delete from ds_userallowedmessagescope
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance41');

		$this->out('Delete buildings <br>');
		//delete buildings
		$dsql = sprintf ( "delete
	                 from ds_attachedbuildinghasattribute
	                 where id_attachedbuilding in
	                 (select ab.id_attachedbuilding
	                 from ds_attachedbuilding ab,
	                 ds_comb co
	                 where ab.id_comb = co.id_comb
	                 and co.id_userinstance = %d)",
	                 $id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance42');


		$dsql = sprintf ( "delete
	                 from ds_unitinproduction
	                 where id_attachedbuilding in
	                 (select ab.id_attachedbuilding
	                 from ds_attachedbuilding ab,
	                 ds_comb co
	                 where ab.id_comb = co.id_comb
	                 and co.id_userinstance = %d)",
	                 $id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance43');


		//ds_attachedbuildingbindeventqueue
		$dsql = "
		DELETE FROM ds_attachedbuildingbindeventqueue
WHERE
  id_attachedbuilding IN (
     SELECT ab.id_attachedbuilding
     FROM ds_attachedbuilding AS ab
     WHERE
          ab.id_comb in (
              SELECT c.id_comb
              FROM ds_comb AS c
              WHERE c.id_userinstance = ".$id_userinstance."
          )
  )";

		$dres = $this->common->executeQuery($dsql);
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance44-1');

		//ds_attachedbuilding
		$dsql = sprintf ( "delete
	                 from ds_attachedbuilding
	                 where id_comb in
	                 (select co.id_comb
	                 from ds_comb co
	                 where co.id_userinstance = %d)",
	                 $id_userinstance );
		$dres = $this->common->executeQuery($dsql);

		if(!empty($this->common->errormsg))
		{
			echo pg_last_error();
		}

		$this->assert($this->common->errormsg == '','Drop:dropUserInstance44');

		$this->out('Delete science <br>');
		$dsql = sprintf ( "delete
				from ds_userhasscience
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance45');

		//resources
		$this->out('Delete sources <br>');
		$dsql = sprintf ( "delete
	                 from ds_combhasresource
	                 where id_comb in
	                 (select co.id_comb
	                 from ds_comb co
	                 where co.id_userinstance = %d)",
	                 $id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance46');

		// DELETE COMBS
		$this->out('Delete combs <br>');
		$dsql = sprintf ( "delete
				from ds_combname
				where id_comb in
				(select id_comb
				from ds_comb
				where id_userinstance = %d)",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance47');

		$dsql = sprintf ( "delete from ds_comballowedtradescope
				where id_comb in
				(select id_comb
				from ds_comb
				where id_userinstance = %d)",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance48');

		$dsql = sprintf ( "select id_comb
				from ds_comb
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->getQueryArray ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance49');

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
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance50');

		$this->out('Delete userdata <br>');
		$dsql = sprintf ( "update ds_comb
				set id_userinstance = null,
				combscore = 0
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance51');

		$dsql = sprintf ( "delete from
				ds_useraction
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance52');

		$dsql = sprintf ( "delete from
				ds_flashkey
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance53');

		$dsql = sprintf ( "delete from
				ds_userhasability
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance54');

		$this->out('Delete battles <br>');
		// delete battles

		$dsql = sprintf ( "delete from
									ds_battlexp
							where
									id_userinstance = %d",
							$id_userinstance );
		$dres2 = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance54.2');

		$dsql = sprintf ( "select id_battle
				from ds_battleparticipant
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->getQueryArray ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance55');

		if(is_array($dres))
		foreach ( $dres as $k=>$v ) {
			$dsql = sprintf ( "delete from ds_roundreport
					where id_battleround in
					(select id_battleround
					from ds_battleround
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance56');

			$dsql = sprintf ( "delete from ds_battleresult
					where id_battleround in
					(select id_battleround
					from ds_battleround br
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance57');

			$dsql = sprintf ( "delete from ds_battleparticipanthasunitcontainer
					where id_battleparticipant in
					( select id_battleparticipant
					from ds_battleparticipant
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance58');

			$dsql = sprintf ( "delete from ds_battleparticipant
					where id_battleparticipant in
					( select id_battleparticipant
					from ds_battleparticipant
					where id_battle = %d)",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance59');

			$dsql = sprintf ( "delete from ds_battleround
					where id_battle = %d",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance60');

			$dsql = sprintf ( "delete from ds_battlehasattribute
					where id_battle = %d",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery ( $dsql );
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance61-1');

			$dsql = sprintf ( "delete from ds_battlereport
					where id_battle = %d",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery($dsql);
			$this->assert($this->common->errormsg == '','Drop:dropUserInstance61-2');

			$dsql = sprintf ( "delete from ds_battle
					where id_battle = %d",
					$v['id_battle'] );
			$dres2 = $this->common->executeQuery($dsql);

			$this->assert($this->common->errormsg == '','Drop:dropUserInstance61-3');
		}//END OF foreach ( $dres as $k=>$v )

		$dsql = sprintf ( "delete from
				ds_userhasreligion
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance62');

		$dsql = sprintf ( "delete from
				ds_loginqueue
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance62.5');

		$dsql = sprintf ( "delete from
				ds_gamehelper
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance62.1');

		$dsql = sprintf ( "delete from
				ds_gamemaster
				where id_userinstance = %d",
				$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance62.2');

		$dsql = sprintf ( "delete from
					ds_userbattlexp
					where id_userinstance = %d",
					$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );
		$this->assert($this->common->errormsg == '','Drop:dropUserInstance62.3');

       	$dsql = sprintf ( "delete from
					ds_userinstance
					where id_userinstance = %d",
					$id_userinstance );
		$dres = $this->common->executeQuery ( $dsql );		

		$this->assert($this->common->errormsg == '','Drop:dropUserInstance63');

		$this->out('--- DELETING PROCES COMPLIT---<br>');

		return true;

	} //END OF METHOD drop ( $id_userinstance)

	function checkUserOnPremium($uiId)
	{
		$sql = sprintf("SELECT
      							upd.id_user
							FROM
    							ds_userinstance ui,
    							ds_userprofidata upd
							WHERE
     							ui.id_userinstance = %d
     							AND ui.id_user = upd.id_user
     							AND upd.active = 't'
     							AND upd.enddate > now()
     							AND upd.profi = 't'",
								$uiId);


		$res = $this->common->getResultArray("", $sql, "", "", "");
		$this->assert($this->common->errormsg == '', 'Drop:checkUserOnPremium-stats1');

		if(is_array($res) && (sizeof($res) > 0))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	function incrementDelInstanceCounter($dbid, $worldname, $id_world, $premium)
	{
		//2006-01-11 Временно оключенно так как были проблемы с правами на этй таблицы в альфе
		return;
		
		$statsId = $this->getStatisticId();
		$id_worldstats = $this->getWorldStatsId($dbid, $worldname, $id_world);

		$sql = sprintf("SELECT
      							iowu.id_inoutworldusers
						FROM
    							ds_inoutworldusers iowu
						WHERE
     							iowu.id_statistic = %d
     							AND iowu.id_worldstats = %d",
								$statsId,
								$id_worldstats);

		$res = $this->common->getResultArray("", $sql, "", "", "");
		$this->assert($this->common->errormsg == '', 'Drop:incrementDelInstanceCounter-stats1');
		if(sizeof($res) === 0)
  		{
  			$sql = sprintf("INSERT INTO
           								ds_inoutworldusers
           								(id_statistic, id_worldstats,  del_users, delpremiumusers)
           							VALUES
           								(%d, %d, 1, %d)",
  									$statsId,
  									$id_worldstats,
  									$premium);

  		}
  		else
  		{
  			$sql = sprintf("UPDATE
      								ds_inoutworldusers
      							SET
           							del_users = del_users + 1,
           							delpremiumusers = delpremiumusers + %d
      							WHERE
           							id_inoutworldusers = %d",
  								$premium,
  								$res[0]['id_inoutworldusers']);


  		}

  		$res = $this->common->executeQuery($sql);
		$this->assert($this->common->errormsg == '', 'Drop:incrementDelInstanceCounter-stats2');

	}

	function getWorldStatsId($dbid, $worldname, $id_world)
	{
		$sql = sprintf("SELECT
      						ws.id_worldstats
						FROM
    						ds_worldsstatisic ws
						WHERE
     						ws.dbid = %d
     						AND ws.world_id = %d",
							$dbid,
							$id_world);
		$res = $this->common->getResultArray("", $sql, "", "", "");
		$this->assert($this->common->errormsg == '', 'Drop:getWorldStatsId-stats1');

  		if(sizeof($res) === 0)
  		{
  				$sql = sprintf("INSERT INTO
           								ds_worldsstatisic
           								(dbid, world_id, worldname)
           							VALUES
           								(%d,%d,'%s')",
								$dbid,
								$id_world,
								$worldname);

				$res = $this->common->executeQuery($sql);
  				$this->assert($this->common->errormsg == '', 'Drop:getWorldStatsId-stats2');

  				$oid = pg_last_oid($this->common->getRES());

  				$this->assert($oid, 'Drop:getWorldStatsId-stats3');

				$sql = 'SELECT w.id_worldstats FROM ds_worldsstatisic w WHERE oid = '.$oid;
				$res = $this->common->getResultArray("", $sql, "", "", "");
				$this->assert($this->common->errormsg == '', 'Drop:getWorldStatsId-stats4');
				$this->assert((sizeof($res) != 0), 'Drop:getWorldStatsId-stats5');
  		}

  		return $res[0]['id_worldstats'];
	}

	function getWorldsData($uiid)
	{

		//assert($dbid!=0);
		$sql = sprintf("SELECT
      							w.id_world, w.worldname
						FROM
    						ds_world w,
    						ds_userinstance ui
						WHERE
     						ui.id_userinstance = %d
     						AND ui.id_world = w.id_world",
							$uiid);

		$res = $this->common->getResultArray("", $sql, "", "", "");
		$this->assert($this->common->errormsg == '', 'Drop:getWorldsData-stats1');
		return array('id_world'=>$res[0]['id_world'],
					'worldname'=>$res[0]['worldname']);
	}

	function getStatisticId()
	{
		$sql = sprintf("SELECT
								id_statistic
							FROM
								ds_statistic
							WHERE
								statsdate < CURRENT_DATE+1
								and statsdate > CURRENT_DATE");

		$res = $this->common->getResultArray("", $sql, "", "", "");
		$this->assert($this->common->errormsg == '', 'Drop:dropUserInstance-stats1');

  		if(sizeof($res) === 0)
  		{
  			$sql = "INSERT INTO
        							ds_statistic	(statsdate)
           					VALUES
           						(now())";

  			$res = $this->common->executeQuery($sql);
  			$this->assert($this->common->errormsg == '', 'Drop:dropUserInstance-stats2');

  			$oid = pg_last_oid($this->common->getRES());

  			$this->assert($oid, 'Drop:dropUserInstance-stats3');

			$sql = 'select id_statistic from ds_statistic where oid = '.$oid;
			$res = $this->common->getResultArray("", $sql, "", "", "");
			$this->assert($this->common->errormsg == '', 'Drop:dropUserInstance-stats4');
			$this->assert(!empty($res[0]['id_statistic']), 'Drop:dropUserInstance-stats5');

  			$id_stats = $res[0]['id_statistic'];
  			return $id_stats;
  		}
  		else
  		{
  			$id_stats = $res[0]['id_statistic'];
  			return $id_stats;
  		}
	}

	function resetComb ( $id_comb ) {
		//Test object condition
		$this->invariant ();

		//Test input params
		settype($id_comb, 'integer');
		$this->assert ($id_comb > 0, 'resetComb: 1');

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
		$this->assert($this->common->errormsg == '','resetComb:createCombComplex11');

		$this->setBeginningBuildings(1, $id_comb, 1);

		return true;
	}

	function setBeginningBuildings($templateid, $id_comb, $celltype) {
		//Test object condition
		$this->invariant ();

		settype($id_comb, 'integer');
		$this->assert ($id_comb > 0, 'setBeginningBuildings: 1');

		settype($templateid, 'integer');
		$this->assert ($templateid > 0, 'setBeginningBuildings: 2');

		settype($celltype, 'integer');
		$this->assert ($celltype > 0, 'setBeginningBuildings: 3');

		$sql = sprintf ( "select ri.ringname
				from ds_ring ri,
				ds_combcomplex cc,
				ds_comb co
				where ri.id_ring = cc.id_ring
				and cc.id_combcomplex = co.id_combcomplex
				and co.id_comb = %d",
				$id_comb );
		$res = $this->common->getQueryArray ( $sql );
		$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings1');

		if ( $res[0]['ringname'] == 'RING_COLONIZE' ){
			 $colony = 1;
		}

		/* Приводим состояние соты к виду как должно быть у выбранном темплэйте. */
		$sql = "
					SELECT
						*
					FROM
						ds_celltemplateelement as cte
					WHERE
						cte.id_celltemplate = ".$templateid." AND
						cte.id_celltype = ".$celltype."
					ORDER BY cellnumber
			";


		$res = $this->common->getQueryArray ( $sql );
		$this->assert($this->common->errormsg == '','setBeginningBuildings:setCellsFromTemplate');
		
		foreach ($res as $key => $value)
		{
			$sql = "UPDATE ds_cell SET id_groundtype = ".$value['id_groundtype']." WHERE id_comb = ".$id_comb." AND id_celltype = ".$value['id_celltype']." AND cellnumber = ".$value['cellnumber']."";
			$res2 = $this->common->getQueryArray ( $sql );
			$this->assert($this->common->errormsg == '','setBeginningBuildings:setCellsFromTemplate1');
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
		$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings2');

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
			$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings3');

			$id_cell = $res2[0]['id_cell'];

			if ( isset ( $colony ) && $res[$i]['buildingname'] == 'CENTRAL_GATE_WRECK' ) {

				$sql = sprintf ( "select id_building
						from ds_building
						where buildingname = 'CENTRAL_GATE'" );
				$res2 = $this->common->getQueryArray ( $sql );
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings4');

				$sql = sprintf ( "insert into DS_AttachedBuilding (ID_Building, ID_Cell, active, underconstruction, starttime, endtime, inupgrade, id_comb)
   						values ( %d, %d, true, false, null, null, false, %d )",
						$res2[0]['id_building'], $id_cell, $id_comb );
				$res2 = $this->common->executeQuery ( $sql );
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings3');

				$sql  = sprintf ( "select id_attachedbuilding
						from ds_attachedbuilding
						where id_cell = %d",
						$id_cell );
				$res2 = $this->common->getQueryArray ( $sql );
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings4');

				$mab = $res2[0]['id_attachedbuilding'];

			} else {

				$sql = sprintf ( "insert into DS_AttachedBuilding (ID_Building, ID_Cell, active, underconstruction, starttime, endtime, inupgrade, id_comb)
   						values ( %d, %d, true, false, null, null, false, %d )",
						$res[$i]['id_building'], $id_cell, $id_comb );
				$res2 = $this->common->executeQuery ( $sql );
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings3');

				$sql  = sprintf ( "select id_attachedbuilding
						from ds_attachedbuilding
						where id_cell = %d",
						$id_cell );
				$res2 = $this->common->getQueryArray ( $sql );
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings4');

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
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings5');

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
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings6');

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
					$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings7');
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
				$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings8');

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
					$this->assert($this->common->errormsg == '','setBeginningBuildings:setBeginingBuildings9');
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
}//END OF CLASS DropUser
?>