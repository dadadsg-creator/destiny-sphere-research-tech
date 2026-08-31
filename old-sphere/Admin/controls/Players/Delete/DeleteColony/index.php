<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";


$obj = new DeleteColony();

class DeleteColony
{
	var $common = false;	
	var $error_msg = '';
	
	function DeleteColony()
	{
		$this->getPostData($_REQUEST);
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
	
	function getPostData($data)
	{		
		$this->showRequestForm();
		
		if(isset($data['action']) && isset($data['dbId']) &&
			isset($data['id_comb']) && isset($data['id_userinstance']))
			{
				$this->delete($data['dbId'],$data['id_comb'],$data['id_userinstance']);
			}
		
		if((isset($data['search']) || isset($data['action'])) && isset($data['username']))
		{
			$this->showUserColonies($data['username']);
		}		
	}
	
	function delete($dbId, $id_comb, $id_userinstance)
	{
		assert(isset($dbId));
		assert(isset($id_comb));
		assert(isset($id_userinstance));
	}
	
	function deleteEvents($id_userinstance,$id_comb)
	{
		
		echo 'Deleting comb events<br>';
		echo 'Deleting defend events (you defender)<br>';
		$sql = "
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
							eho.value = ".$id_userinstance.")
					and
					id_eventqueue in (
						SELECT eho.id_eventqueue
				       	FROM
							ds_eventhasoption eho,
				       		ds_eventqueue eq
				       	WHERE
							eho.id_eventoption = 152 AND
				       		eho.id_eventqueue = eq.id_eventqueue AND
				       		eq.istrash = false AND
							eho.value = ".$id_comb.")";

		$this->getDBConnection()->executeQuery($sql);
		if($this->getDBConnection()->errormsg)
		{
			$this->error_msg = 'Error in deleting events1';
			return false;
		}

		echo 'Deleting defend/attack events (defend/attack to you)<br>';
		// stopping here sql to check
		$sql = "
				SELECT
      eq.id_eventqueue
FROM
    ds_eventqueue eq
WHERE
     eq.istrash = false
     AND eq.id_eventqueue in
(
 SELECT
					eho.id_eventqueue
				FROM
					ds_eventhasoption eho
				WHERE
					eho.id_eventoption in ( 133, 155 ) AND				
				    eho.value = $id_userinstance
)
 AND eq.id_eventqueue in
(
SELECT
					eho2.id_eventqueue
				FROM
					ds_eventhasoption eho2
				WHERE
					eho2.id_eventoption in ( 131, 153 ) AND				
				    eho2.value = $id_comb
)";
		
		$res = $this->getDBConnection()->getQueryArray($sql);
		if($this->getDBConnection()->errormsg)
		{
			$this->error_msg = 'Error in deleting events2';
			return false;
		}

		if(is_array($res))
		foreach ( $res as $k=>$v ) 
		{
			echo 'Deleting event with id ='.$v['id_eventqueue'].'<br>';

			$sql = sprintf ( "
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
				        	
			$this->common->executeQuery($sql);
			if($this->getDBConnection()->errormsg)
			{
				$this->error_msg = 'Error in deleting events3 on id_eventqueue'.$v['id_eventqueue'];
				return false;
			}

			$sql = sprintf("UPDATE
								ds_unitcontainer
			        		SET
								inaction = false,
			        			id_currentcomb = null
			        		WHERE
								id_unitcontainer in (
								SELECT
			             				ucbeq.id_unitcontainer
			             			FROM 
			             				ds_unitcontainerbindeventqueue ucbeq
			        				WHERE 
			        					ucbeq.id_eventqueue = %d
							)",
			        		$v['id_eventqueue']);
			        		
			$this->common->executeQuery($sql);
	   		if($this->getDBConnection()->errormsg)
			{
				$this->error_msg = 'Error in deleting events4 on id_eventqueue'.$v['id_eventqueue'];
				return false;
			}

			$sql = sprintf("DELETE FROM
										ds_unitcontainerbindeventqueue
			        				WHERE
										id_unitcontainer in (
			            				SELECT
			             						ucbeq.id_unitcontainer
			             					FROM
												ds_unitcontainerbindeventqueue ucbeq
				        					WHERE 
				        						ucbeq.id_eventqueue = %d
							)",
			        		$v['id_eventqueue'] );
			        		
			$this->common->executeQuery($sql);
	   		if($this->getDBConnection()->errormsg)
			{
				$this->error_msg = 'Error in deleting events5 on id_eventqueue'.$v['id_eventqueue'];
				return false;
			}

			$sql = sprintf("UPDATE
									ds_eventqueue
			    				SET
									istrash = true
			    				WHERE
									id_eventqueue = %d",
			    				$v['id_eventqueue']);
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

			// delete trade*/
	}
	
	function showUserColonies($username)
	{
		$sql = sprintf("SELECT
  								co.id_comb,
  								ui.id_userinstance,
  								ri.ringnumber,
  								cn.combname || ' ('
  								|| ri.ringnumber || '.'
  								|| cc.complexnumber || '.'
  								|| co.combnumber
  								|| ')' as coords,
  								u.username,
  								w.worldname
							FROM
  								ds_comb co,
  								ds_combcomplex cc,
  								ds_ring ri,
  								ds_combname cn,
  								ds_userinstance ui,
  								ds_user u,
  								ds_world w
							WHERE
  								co.id_combcomplex = cc.id_combcomplex AND
  								cc.id_ring = ri.id_ring AND
  								co.id_comb = cn.id_comb AND
  								co.id_userinstance = ui.id_userinstance AND
  								ui.id_user = u.id_user AND
  								u.username = '%s' AND
  								ui.id_world = w.id_world
  							ORDER BY ui.id_userinstance, ri.ringnumber",
							$username);
							
		$res = $this->getDBConnection()->getMultyArray("", $sql,"","","");	
		
		if($this->getDBConnection()->errormsg || (sizeof($res) === 0))
		{
			$this->outputResult("can't get user combs");
			exit();
		}

		echo "<br><br>
				<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>				
				<tr>";	
		
		$current_world = false;
			
		foreach ($res as $k=>$v)
		{
			if(strcmp($current_world,$v['worldname']) != 0)
			{
				if($current_world)
				{
					echo "</table></td>";
				}
				
				$current_world = $v['worldname'];
				echo "<td valign='top' width='200'>
					<table width=100% border=0 cellspacing=1 cellpadding=1>					
					<tr>
						<td bgcolor='444E6C' class=th_2 align='center'>".
							local('DS_USER_WORLD ').' &nbsp '.$v['worldname']
						."</td>
				  	</tr>
				  	<tr>
						<td bgcolor='#444E6C' class=maintxt align='center'>";
					if((int)$v['ringnumber'] === 4)
					{
						echo "<a href='".$_SERVER["PHP_SELF"].
							"?action=delete&id_comb=".$v['id_comb'].
							"&id_userinstance=".$v['id_userinstance'].
							"&dbId=".$v['dbId'].
							"&username=".$username.
							"'>".$v['coords']."</a>";
					}
					else 
					{
						echo $v['coords'];
					}
					echo "</td>
				  	</tr>";
			}
			else 
			{
				echo "<tr>
						<td bgcolor='#444E6C' class=maintxt align='center'>";
				if((int)$v['ringnumber'] === 4)
				{
					echo "<a href='".$_SERVER["PHP_SELF"].
							"?action=delete&id_comb=".$v['id_comb'].
							"&id_userinstance=".$v['id_userinstance'].
							"&dbId=".$v['dbId'].
							"&username=".$username.
							"'>".$v['coords']."</a>";
				}
				else 
				{
					echo $v['coords'];
				}
				echo "</td>
				  	</tr>";
			}
		}
		
		echo "</table>
				</td>
				</tr>				
				</table>";
						
	}
	
	function showRequestForm()
	{		
		isset($_REQUEST['username'])? $username = $_REQUEST['username']: $username = '';
		
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2>".
							local('DS_USER_USERNAME' ).
							"</TD>
						</TR>
						<TR>
							<TD bgcolor='#444E6C' class=maintxt>
								<input name='username' type='text' size='30' value='".
								$username
								."'>
							</TD>
						</TR>
						<TR>
							<TD bgcolor='#444E6C' class=maintxt>
								<input type='submit' class='Submit' name='search' value='".
									local('DS_ACTION_SEARCH')."'>
							</TD>
						</TR>
						</TABLE>
					</td>
				</tr>
				</form>
				</table>";
									
		return true;									
	}
	
	function outputResult($str)
	{
		echo $str;
		return true;
	}
}



require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>