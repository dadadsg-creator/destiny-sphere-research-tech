<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ChangeCoords><font size=3><b>HELP</b></font></a></p>';

$obj = new ChangeCombCoords();

class ChangeCombCoords
{
	var $err_msg;
	var $common = false;
	
	function ChangeCombCoords()
	{
		if(!$this->getPostData($_POST))
		{
			print ShowCMSMessage($this->err_msg);
			exit;
		}
	}
	
	function getPostData($data)
	{
		isset($data['username'])? $username = $data['username']:$username='';
		isset($data['dbw'])? $dbw = $data['dbw']:$dbw = '';
		
		$this->showRequestForm($username,$dbw);
		if(isset($data['dbw']) && isset($data['username']))
		{	
			if(isset($data['usercomb_id']) 
						&& isset($data['newcoords']))
			{		
				if(!$this->showConfirmation($dbw,
										$data['usercomb_id'],
										$data['newcoords'],
										$username))
				{
					return false;
				}
			}
			elseif (isset($data['comb_id']) 
					&& isset($data['coords'])
					&& isset($data['new_comb_id']) 
					&& isset($data['coords_old']))
			{		
				isset($data['del_cond'])? $del_cond = true:$del_cond=false;		
				if(!$this->change($data['dbw'],
									$data['comb_id'],
									$data['coords'],
									$data['new_comb_id'],
									$data['coords_old'],
									$del_cond))
				{
					return false;
				}	
				else 
				{
					list($dbid,$world_id) = explode('-',$data['dbw']);
					
					//ѕолучаем им€ мира по его id
					$worldName = $this->getWorldNameByID($world_id);
							
					actionLog( "Changing comb coordinates in for user:'".$username."' in world '$worldName'",
								"from ".$data['coords_old']." to ".$data['coords']);
					$this->outputResult("Succesfull");
				}	

				if(!$this->showUserCombs($username,$dbw))
				{
					return false;
				}			
			}
			else 
			{
				if(!$this->showUserCombs($username,$dbw))
				{
					return false;
				}	
			}			
		}
		
		return true;
	}
	
	function showConfirmation($dbw,$usercomb_id,$newcoords,$username)
	{		
		list($ring,$complex,$combnumber) = explode('.',$newcoords);
		list($dbid,$world_id) = explode('-',$dbw);
		assert((int)$ring > 0);	
		assert((int)$complex > 0);	
		assert((int)$combnumber > 0);	
		assert((int)$usercomb_id > 0);	
		assert((int)$world_id > 0);	
		assert((int)$dbid > -1);	
		
		$conn = $this->setDBId($dbid);					
						
		$newCombData = $this->checkNewComb($ring,$complex,$combnumber,$world_id);
		if(!$newCombData)
		{			
			return false;
		}
		
		$coords = $this->getCombCoords($usercomb_id);
		if(!$coords)
		{
			return false;
		}	
		$coords_sp = $coords['ringnumber'].'.'.
						$coords['complexnumber'].'.'.
							$coords['combnumber'];
		
		isset($newCombData['username'])?$newCombOwner = $newCombData['username']:
										$newCombOwner = 'nobody';		
		echo "<br><br>
			<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<input type='hidden' name='dbw' value='".$dbw."'>
				<input type='hidden' name='comb_id' value='".$usercomb_id."'>
				<input type='hidden' name='coords' value='".$newcoords."'>
				<input type='hidden' name='new_comb_id' value='".$newCombData['id_comb']."'>
				<input type='hidden' name='coords_old' value='".$coords_sp."'>				
				<input type='hidden' name='username' value='".$username."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 width='140' colspan='2' align='center'>
								Really want to move
							</TD>
						</TR>
						<TR>
							<TD bgcolor='444E6C' class=th_2 width='140' align='center'>
								From:
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140' align='center'>
								To:
							</TD>
						</TR>
						<TR>
							
								<TD bgcolor='#444E6C' class=maintxt width='140' align='center'>
								".$username."'s comb (".$coords_sp.")
								</TD>
								<TD bgcolor='#444E6C' class=maintxt width='140' align='center'>
								".
									$newCombOwner
								."'s comb (".$newcoords.")</TD>
							
						</TR>
						<TR>
							<TD bgcolor='#444E6C' class=maintxt colspan='2' align='left'>
								<input type='checkbox' checked class=check 
								name='del_cond' >
								&nbsp delete gondola-transporter
							</TD>
						</TR>
						<TR>
							<TD bgcolor='#444E6C' class=maintxt colspan='2' align='center'>
								<input type='submit'  class='Submit' name='search' 
								value='proceed ?!'>
							</TD>
						</TR>
						</TABLE>
					</td>
				</tr>
				</form>
				</table>";
									
		return true;		
		
	}
	
	function setDBId($dbid)
	{
		if(!$this->common)
		{
			$this->common = &ClassFactory::getInstance("DS_Common");
			if (!$this->common->connect()) 
			{
				$this->outputResult('DB connection failed');
				exit();
			}	
		}
		$this->common->setDbId($dbid);
		return $this->common;
	}
	
	function change($dbw,$comb_id,$coords,$new_comb_id,$coords_old,$del_cond)
	{
		list($ring,$complex,$combnumber) = explode('.',$coords);
		list($ring_old,$complex_old,$combnumber_old) = explode('.',$coords_old);
		list($dbid,$world_id) = explode('-',$dbw);
		assert((int)$ring > 0);	
		assert((int)$complex > 0);	
		assert((int)$combnumber > 0);	
		assert((int)$ring_old > 0);	
		assert((int)$complex_old > 0);	
		assert((int)$combnumber_old > 0);	
		assert((int)$comb_id > 0);	
		assert((int)$new_comb_id > 0);
		assert((int)$world_id > 0);	
		assert((int)$dbid > -1);
		
		$conn = $this->setDBId($dbid);		
				
		// убираем пока дл€ тестов	
		/*if((int)$ring_old != (int)$ring)
		{
			$this->err_msg = "rings must be alike";		
			return false;
		}*/
		
		$conn->executeQuery('begin');
		if($conn->errormsg)
		{
			$this->err_msg = "can't begin <br>";	
			return false;						
		}	
		
		if($del_cond)
		{			
			if(!$this->delCondoms($comb_id))
			{
				$conn->executeQuery('rollback');
				if($conn->errormsg)
				{			
					$this->err_msg = "can't rollback1 <br>";	
					return false;					
				}	
				return false;
			}
			
			if(!$this->delCondoms($new_comb_id))
			{
				$conn->executeQuery('rollback');
				if($conn->errormsg)
				{			
					$this->err_msg = "can't rollback1 <br>";	
					return false;					
				}	
				return false;
			}
		}
		
		if(!$this->clearNewComb($new_comb_id))
		{
			$conn->executeQuery('rollback');
			if($conn->errormsg)
			{			
				$this->err_msg = "can't rollback2 <br>";	
				return false;					
			}	
			return false;
		}
		
		if(!$this->setNewCombCoords($ring,$complex,$combnumber,$comb_id,$world_id))
		{
			$conn->executeQuery('rollback');
			if($conn->errormsg)
			{			
				$this->err_msg = "can't rollback3 <br>";	
				return false;					
			}	
			return false;
		}
		
		
		if(!$this->setNewCombCoords($ring_old,$complex_old,$combnumber_old,
									$new_comb_id,$world_id))
		{
			$conn->executeQuery('rollback');
			if($conn->errormsg)
			{			
				$this->err_msg = "can't rollback4 <br>";	
				return false;					
			}	
			return false;
		}
		
		$conn->executeQuery('commit');
		
		if($conn->errormsg)
		{			
			$this->err_msg = "can't commit, fuck <br>";	
			return false;					
		}		
		
		return true;	
	}
	
	function clearNewComb($id_comb)
	{
		$conn = $this->getDBConnection();
		$sql = sprintf("UPDATE
      							ds_comb
   							SET
      							combnumber = 7
   							WHERE
        						id_comb = %d",							
							$id_comb);
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{			
			$this->err_msg = "can't clear new comb with comb_id=".$id_comb;	
			return false;					
		}		

		return true;
	}
	
	function setNewCombCoords($ring,$complex,$combnumber,$id_comb,$id_world)
	{
		$conn = $this->getDBConnection();
		
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
						$id_world,
						$ring);
						
		$res = $conn->getResultArray ( "",$sql, "", "", "" );						
		if($conn->errormsg || sizeof($res) === 0)
		{
			$this->err_msg = "can't get id_combcomplex for comb_id=".$id_comb;		
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
							
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{			
			$this->err_msg = "can't set new coords for comb_id=".$id_comb;	
			return false;					
		}		

		return true;	
	}
	
	function delCondomsBuildingEvent($id_attachedbuilding)
	{
		$conn = $this->getDBConnection();
		$sql = sprintf("SELECT
						      abbeq.id_eventqueue
							FROM
    							ds_attachedbuildingbindeventqueue abbeq
							WHERE
     							abbeq.id_attachedbuilding = %d",
						$id_attachedbuilding
						);
						
		$res = $conn->getResultArray ( "",$sql, "", "", "" );						
		if($conn->errormsg)
		{
			$this->err_msg = "can't check event of building condoms";		
			return false;	
		}	
		
		if(sizeof($res) === 0)
		{
			return true;
		}
		
		$sql = "DELETE FROM 
							ds_attachedbuildingbindeventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{
			$this->err_msg = "can't delete from ds_attachedbuildingbindeventqueue";
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_eventhasoption 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{
			$this->err_msg = "can't delete from ds_eventhasoption";
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_eventparticipant 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{
			$this->err_msg = "can't delete from ds_eventparticipant";
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_unitcontainerbindeventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{
			$this->err_msg = "can't delete from ds_unitcontainerbindeventqueue";
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_combbindeventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{
			$this->err_msg = "can't delete from ds_combbindeventqueue";
			return false;							
		}	
		
		$sql = "DELETE FROM 
							ds_eventqueue 
						WHERE 
							id_eventqueue = ".$res[0]['id_eventqueue'];
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{
			$this->err_msg = "can't delete from ds_eventqueue";
			return false;							
		}	
		
		return true;
	}
		
	function delCondoms($id_comb)
	{
		$conn = $this->getDBConnection();
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
						
		$res = $conn->getResultArray ( "",$sql, "", "", "" );						
		if($conn->errormsg)
		{
			$this->err_msg = "can't get ring for comb";		
			return false;	
		}	
		
		if(sizeof($res) === 0)
		{
			return true;
		}
		
		if(!$this->delCondomsBuildingEvent($res[0]['id_attachedbuilding']))
		{
			return false;
		}
		
		$sql = sprintf("DELETE FROM
           							ds_attachedbuilding
       							WHERE
            						id_attachedbuilding = %d",
						$res[0]['id_attachedbuilding']
						);
		$conn->executeQuery($sql);
		if($conn->errormsg)
		{
			$this->err_msg = "can't delete condoms";
			return false;							
		}	
		return true;
	}
	
	function getCombCoords($id_comb)
	{
		$conn = $this->getDBConnection();
		$sql = sprintf("SELECT
    						  r.ringnumber,
    						  cc.complexnumber,
    						  c.combnumber
							FROM
    							ds_comb c,
    							ds_combcomplex cc,
    							ds_ring r
							WHERE
     							c.id_comb = %d
     							AND  c.id_combcomplex = cc.id_combcomplex
     							AND  cc.id_ring = r.id_ring",
							$id_comb
						);		
		$res = $conn->getResultArray ( "",$sql, "", "", "" );						
		if($conn->errormsg || sizeof($res) === 0)
		{
			$this->err_msg = "can't get ring for comb";		
			return false;	
		}	
		
		$coords = array('combnumber'=>$res[0]['combnumber'],
						'complexnumber'=>$res[0]['complexnumber'],
						'ringnumber'=>$res[0]['ringnumber']);
		
		return $coords;
	}
	
	function checkNewComb($ring,$complex,$combnumber,$id_world)
	{
		$conn = $this->getDBConnection();
		$sql = sprintf("SELECT
      							c.id_userinstance,
      							c.id_comb,
      							u.username
							FROM
								ds_comb as c
    							INNER JOIN	ds_combcomplex as cc ON (c.id_combcomplex = cc.id_combcomplex)
    							INNER JOIN	ds_ring as r ON (cc.id_ring = r.id_ring)
    							LEFT JOIN ds_userinstance as ui ON (c.id_userinstance = ui.id_userinstance)
    							LEFT JOIN ds_user as u ON (ui.id_user = u.id_user) 						
							WHERE
								c.combnumber = %d
   								AND cc.complexnumber = %d
   								AND r.ringnumber = %d
								AND r.id_world = %d",
						$combnumber,
						$complex,
						$ring,
						$id_world
						);
						
		$res = $conn->getResultArray ( "",$sql, "", "", "" );						
		if($conn->errormsg)
		{
			echo $sql;
			$this->err_msg = "error in checking comb";		
			return false;	
		}	
		
		if(sizeof($res) === 0)
		{
			$this->err_msg = "no such comb";		
			return false;	
		}
		
		/*if(strlen($res[0]['id_userinstance']) != 0 )
		{
			$this->err_msg = "this comb is occupied now";		
			return false;	
		}*/
		
		return $res[0];
	}
	
	function showUserCombs($username, $dbw)
	{		
		$conn = $this->getDBConnection();
		list($dbid,$world_id) = explode('-',$dbw);
		assert((int)$world_id > 0);	
		assert((int)$dbid > -1);
				
		$conn->setDbId($dbid);		
		$sql = sprintf("SELECT
  								co.id_comb,
  								ri.ringnumber,
  								cc.complexnumber,
  								co.combnumber,
  								cn.combname
							FROM
  								ds_comb co,
  								ds_combcomplex cc,
  								ds_ring ri,
  								ds_combname cn,
  								ds_userinstance ui,
  								ds_user u
							WHERE
  								co.id_combcomplex = cc.id_combcomplex AND
  								cc.id_ring = ri.id_ring AND
  								co.id_comb = cn.id_comb AND
  								co.id_userinstance = ui.id_userinstance AND
  								ui.id_world = %d AND
  								ui.id_user = u.id_user AND
  								u.username = '%s'",
							$world_id,
							$username);
							
		$res = $conn->getResultArray ( "",$sql, "", "", "" );						
		if($conn->errormsg)
		{
			$this->err_msg = "error in getting user combs";		
			return false;	
		}	
		if(sizeof($res) === 0)
		{
			$this->err_msg = "user have no combs in this world";		
			return false;	
		}	
		
		echo "<br><br>
				<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<input type='hidden' name='username' value='".$username."'>
				<input type='hidden' name='dbw' value='".$dbw."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 width='140'>
							user combs:
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140'>
							where to move
							</TD>
						</TR>
						<TR>							
								<TD bgcolor='#444E6C' class=maintxt width='140'>
								<select name = 'usercomb_id'>"; 
		foreach ($res as $k=>$v)
		{
			echo "<option value=".$v['id_comb'].">"
					.$v['combname']." (".
					$v['ringnumber'].".".
					$v['complexnumber'].".".
					$v['combnumber'].")".
					"</option>";
		}
		echo					"</select>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt width='140'>
								<input name = 'newcoords' type ='text'> 
								</TD>							
						</TR>
						<TR>
							<TD bgcolor='#444E6C' align='right' class=maintxt colspan='2'>
								<input type='submit'  class='Submit' name='search' 
								value='".local( 'DS_ACTION_SUBMIT' )."'>
							</TD>
						</TR>
						</TABLE>
					</td>
				</tr>
				</form>
				</table>";
									
		return true;			
	}
	
	
	function getDBConnection()
	{
		if(!$this->common)
		{
			$this->common = &ClassFactory::getInstance("DS_Common");
			if (!$this->common->connect()) 
			{
				$this->outputResult('DB connection failed');
				exit();
			}	
		}
		
		return $this->common;		
	}
	
	function outputResult($str)
	{
		echo ShowCMSMessage($str);
		return true;
	}
	
	function getWorldsSelect($dbw)
	{
		$conn = $this->getDBConnection();
		$str = "<SELECT name='dbw'>";
		$sql = "SELECT worldname, id_world from ds_world";
		$res = $conn->getMultyArray("", $sql,"","","");	
		if($conn->errormsg || (sizeof($res) === 0))
		{
			$this->outputResult("can't get list of worlds");			
		}
		
		foreach ($res as $k=>$v)		
		{
			if(strcmp($dbw,$v['dbId'].'-'.$v['id_world']) === 0)
			{
				$str.= "<OPTION selected value='".
						$v['dbId'].'-'.$v['id_world'].
						"'>".$v['worldname']."</OPTION>";
			}
			else 
			{
				$str.= "<OPTION value='".
						$v['dbId'].'-'.$v['id_world'].
						"'>".$v['worldname']."</OPTION>";
			}
		}
		
		$str.="</SELECT>";
		return $str;
	}
	
	function showRequestForm($username,$dbw)
	{
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 width='140'>
							".local( 'DS_USER_USERNAME' ).":
							</TD>
							<TD bgcolor='444E6C' class=th_2 width='140'>
							".local('ADMIN_WORLD_NAME')."
							</TD>
						</TR>
						
						<TR>
							
								<TD bgcolor='#444E6C' class=maintxt width='140'>
								<input name = 'username' type ='text'
								value='".$username."'> 
								</TD>
								<TD bgcolor='#444E6C' class=maintxt width='140'>
								".
									$this->getWorldsSelect($dbw)
								."</TD>
							
						</TR>
						<TR>
							<TD bgcolor='#444E6C' align='right' class=maintxt colspan='2'>
								<input type='submit'  class='Submit' name='search' 
								value='".local( 'DS_ACTION_SEARCH' )."'>
							</TD>
						</TR>
						</TABLE>
					</td>
				</tr>
				</form>
				</table>";
									
		return true;			
	}
	
	function getWorldNameByID($world_id)
	{
		assert((int)$world_id >= 0);

		$conn = $this->setDBId($dbid);

		$sql = "SELECT
									worldname
									FROM ds_world 
									WHERE id_world =$world_id";


		$res = $conn->getResultArray ( "",$sql, "", "", "" );
		if($conn->errormsg || sizeof($res) === 0)
		{
			$this->err_msg = "can't get world name by ID";
			return false;
		}
		
		return $res[0]['worldname'];
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>