<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_ChangCombCoords
{
	public  $error_status;
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	private $common;
	private $id_world;
	private $moved_comb_name;
	
	public function __construct($worldname)
	{
		if(empty($worldname))
			$this->throwException('101');
		
		if(!$this->connectToNeededDB($worldname))
			$this->throwException($this->error_status);
	}
		
	private function throwException($msg)
	{
		throw new Exception($msg);
	}
	
	private  function connectToNeededDB($worldname)
	{
		if(empty($worldname))
		{
			$this->error_status = 110;
			return false;
		}			
					
		$this->common = DS_Common::CreateForCurentDB(0);

		$sql = "SELECT
					wc.dbname, wc.id_world
				FROM
				    ds_worldcollection wc
				WHERE
				    wc.worldname='".addslashes($worldname)."'";
		
		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	
		if( !empty($this->common->errormsg) || 
			 empty($res[0]['dbname']) ||
			 empty($res[0]['id_world']) )
			 {
			 	$this->error_status = 120;
			 	return false;
			 }
			 
		$this->id_world = $res[0]['id_world'];
		
		$id = $this->common->getDbIdByName($res[0]['dbname']);
		
		if($id === false)
		{
			$this->error_status = 130;
			return false;
		}	
		
		if(!$this->common->setDbId($id))
		{
			$this->error_status = 140;
			return false;
		}	
		
		return true;
	}
	
	private function getCombNameByCoords($ring, $complexnumber, $combnumber)
	{
		$sql = "SELECT
				      cn.combname
				FROM
					ds_comb c,
					ds_combcomplex cc,
					ds_ring r,
					ds_combname cn
				WHERE
					c.combnumber = ".$combnumber."
					AND c.id_combcomplex = cc.id_combcomplex
					AND cc.complexnumber = ".$complexnumber."
					AND cc.id_ring = r.id_ring
					AND r.ringnumber = ".$ring."
					AND r.id_world =".$this->id_world."
					AND c.id_comb = cn.id_comb";
		
		$res = $this->common->getResultArray( "",$sql);						
		if($this->common->errormsg || sizeof($res) === 0)
		{
			$this->error_status = 360;
			return false;
		}			
			
		return $res[0]['combname'];		
	}
	
	public function checkMoving($new_coords)
	{
		if(!preg_match("/^[1-4]{1}\.[0-9]{1,4}\.[1-6]{1}$/", $new_coords))
		{
			$this->error_status = 340;	
			return false;
		}
				
		list($ring_new,$complex_new,$combnumber_new) = explode('.',$new_coords);
		$checking_combname = $this->getCombNameByCoords($ring_new, $complex_new, $combnumber_new);
		
		if(strcmp($checking_combname, $this->moved_comb_name) != 0)
			return false;
			
		return true;
	}
	
	private function getCombIDByCoords($ring, $complexnumber, $combnumber)
	{
		$sql = "SELECT
				      c.id_comb
				FROM
					ds_comb c,
					ds_combcomplex cc,
					ds_ring r
				WHERE
					c.combnumber = ".$combnumber."
					AND c.id_combcomplex = cc.id_combcomplex
					AND cc.complexnumber = ".$complexnumber."
					AND cc.id_ring = r.id_ring
					AND r.ringnumber = ".$ring."
					AND r.id_world =".$this->id_world;
		
		$res = $this->common->getResultArray( "",$sql);						
		if($this->common->errormsg || sizeof($res) === 0)
		{
			$this->error_status = 370;
			return false;
		}
			
		return (int)$res[0]['id_comb'];		
	}
		
	public function change($new_coords, $old_coords)
	{
		if(!preg_match("/^[1-4]{1}\.[0-9]{1,4}\.[1-6]{1}$/", $new_coords) ||
			!preg_match("/^[1-4]{1}\.[0-9]{1,4}\.[1-6]{1}$/", $old_coords))
			{
				$this->error_status = 150;
				return false;
			}

		list($ring_new,$complex_new,$combnumber_new) = explode('.',$new_coords);
		list($ring_old,$complex_old,$combnumber_old) = explode('.',$old_coords);

		$this->moved_comb_name = $this->getCombNameByCoords($ring_old, $complex_old , $combnumber_old);
		if(!$this->moved_comb_name)
			return false;

		$id_comb_new = $this->getCombIDByCoords($ring_new, $complex_new, $combnumber_new);
		$id_comb_old = $this->getCombIDByCoords($ring_old, $complex_old, $combnumber_old);
		if(!$id_comb_new || !$id_comb_old)
			return false;

		$this->common->executeQuery('begin');
		if($this->common->errormsg)
		{
			$this->error_status = 160;
			return false;
		}

		if(!$this->delCondoms($id_comb_new))
		{
			$this->common->executeQuery('rollback');
			return false;
		}

		if(!$this->delCondoms($id_comb_old))
		{
			$this->common->executeQuery('rollback');
			return false;
		}

		if(!$this->clearNewComb($id_comb_new))
		{	
			$this->common->executeQuery('rollback');
			return false;
		}

		if(!$this->setNewCombCoords($ring_new, $complex_new, $combnumber_new, $id_comb_old))
		{
			$this->common->executeQuery('rollback');
			return false;
		}

		if(!$this->setNewCombCoords($ring_old, $complex_old, $combnumber_old, $id_comb_new))
		{
			$this->common->executeQuery('rollback');
			return false;
		}

		if(!$this->updateComplexArtefacts($id_comb_new))
		{
			$this->common->executeQuery('rollback');
			return false;
		}

		if(!$this->updateComplexArtefacts($id_comb_old))
		{
			$this->common->executeQuery('rollback');
			return false;
		}
		
		if(!$this->_clearReserve($id_comb_new))
		{
			$this->common->executeQuery('rollback');
			return false;
		}

		$this->common->executeQuery('commit');
		
		return true;	
	}
	
	function clearNewComb($id_comb)
	{
		$sql = sprintf("UPDATE
      							ds_comb
   							SET
      							combnumber = 7
   							WHERE
        						id_comb = %d",							
							$id_comb);
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{			
			$this->error_status = 310;
			return false;					
		}		

		return true;
	}
	
	function setNewCombCoords($ring,$complex,$combnumber,$id_comb)
	{		
		$sql = sprintf("SELECT
      							cc.id_combcomplex
							FROM
    							ds_combcomplex cc,
    							ds_ring r
							WHERE
     							cc.complexnumber = %d
     							AND cc.id_ring = r.id_ring
     							AND r.id_world = %d
     							AND r.ringnumber = %d",
						$complex,
						$this->id_world,
						$ring);
						
		$res = $this->common->getResultArray ( "", $sql);						
		if($this->common->errormsg || sizeof($res) === 0)
		{
			$this->error_status = 320;
			return false;	
		}							
		
		$sql = sprintf("UPDATE
      							ds_comb
   							SET
      							combnumber = %d,
      							id_combcomplex = %d
   							WHERE
        						id_comb = %d",
							$combnumber,
							$res[0]['id_combcomplex'],
							$id_comb);
							
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{			
			$this->error_status = 330;
			return false;					
		}		

		return true;	
	}
	
	function updateComplexArtefacts($combId)
	{
		$sql = "
				UPDATE 
					ds_artefactattachedcomplex
				SET
					id_combcomplex  = (
						SELECT
							id_combcomplex
						FROM
							ds_comb
						WHERE
							id_comb = ".$combId."
					)
				WHERE
					id_artefact IN (
						SELECT
							ai.id_artefactinstance
						FROM
    						ds_comb AS c
    						INNER JOIN ds_attachedbuilding AS atb ON (atb.id_comb = c.id_comb)
    						INNER JOIN ds_artefactattachedbuilding AS aab ON (aab.id_attachedbuilding = atb.id_attachedbuilding)
    						INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = aab.id_artefact)
    						INNER JOIN ds_artefact AS ar ON (ar.id_artefact = ai.id_artefact)
    						INNER JOIN ds_artefactscope AS asn ON (asn.id_artefactscope = ar.id_artefactscope)
    					WHERE
    						c.id_comb = ".$combId."
    						AND
    						asn.scopename = 'COMBCOMPLEX'
					)
			";

		$res = $this->common->getResultArray ( "",$sql);						
		if($this->common->errormsg)
		{
			$this->error_status = 400;
			return false;	
		}
	
		return true;
	}
	
	
	function delCondomsBuildingEvent($id_attachedbuilding)
	{
		$sql = sprintf("SELECT
						      abbeq.id_eventqueue
							FROM
    							ds_attachedbuildingbindeventqueue abbeq
							WHERE
     							abbeq.id_attachedbuilding = %d",
							$id_attachedbuilding);
						
		$res = $this->common->getResultArray ( "",$sql);						
		if($this->common->errormsg)
		{
			$this->error_status = 240;
			return false;	
		}	
		
		if(sizeof($res) === 0)
			return true;
		
		$sql = "DELETE FROM 
							ds_attachedbuildingbindeventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		
		
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->error_status = 250;
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_eventhasoption 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->error_status = 260;
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_eventparticipant 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->error_status = 270;
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_unitcontainerbindeventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->error_status = 280;
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_combbindeventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->error_status = 290;
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_eventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->error_status = 300;
			return false;							
		}	
		
		return true;
	}
		
	function delCondoms($id_comb)
	{
		$sql = sprintf("SELECT
								ab.id_attachedbuilding
						 	FROM
    							ds_attachedbuilding ab,
    							ds_building b
							WHERE
     							ab.id_comb = %d
     							AND ab.id_building = b.id_building
     							AND b.buildingname = 'GONDOLA_TRANSPORTER'",
							$id_comb
						);
						
		$res = $this->common->getResultArray ( "",$sql);						
		if($this->common->errormsg)
		{
			$this->error_status = 220;
			return false;	
		}	
		
		if(sizeof($res) === 0)
			return true;
		
		if(!$this->delCondomsBuildingEvent($res[0]['id_attachedbuilding']))
			return false;
		
		$sql = sprintf("DELETE FROM
           							ds_attachedbuilding
       							WHERE
            						id_attachedbuilding = %d",
						$res[0]['id_attachedbuilding']
						);
						
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->error_status = 240;
			return false;							
		}	
		return true;
	}
	
	
	private function _clearReserve($id_comb)
	{
		if($id_comb <= 0)
		{
			$this->error_status = 310;
			return false;
		}
		
		$sql = "
			DELETE FROM
				ds_reservedcomb
			WHERE
				id_comb = $id_comb
			";
		$this->common->executeQuery($sql);
		
		if($this->common->errormsg)
		{
			$this->error_status = 320;
			return false;						
		}
		
		return true;
	}
				
}

?>