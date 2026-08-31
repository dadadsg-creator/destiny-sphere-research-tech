  <?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";


$obj = new EndUserEvents();

class EndUserEvents
{
	var $common = false;
	
	function EndUserEvents()
	{
		$this->getDBConnection();
		$this->getPostData($_POST);		
	}
	
	function getDBConnection()
	{
		if(!$this->common)
		{
			$this->common = &ClassFactory::getInstance("DS_Common");
			if (!$this->common->connect()) 
			{
				$this->outputResult('DB connection failed');				
			}	
		}
		
		return $this->common;		
	}
	
	function showRequestForm()
	{		
		isset($_POST['username'])? $username = $_POST['username']: $username = '';
		isset($_POST['dbid'])? $dbid = $_POST['dbid']:$dbid = 0;
		
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2>".
							local('DS_USER_USERNAME' ).
							"</TD>
							<TD bgcolor='444E6C' class=th_2>".
							local('DS_USER_WORLD' ).
							"</TD>
						</TR>
						
						<TR>
							<TD bgcolor='#444E6C' class=maintxt>
								<input name='username' type='text' size='30' value='".
								$username
								."'>
							</TD>
							<TD bgcolor='#444E6C' class=maintxt>".
								$this->getWorldsSelect($dbid)
							."</TD>
						</TR>
						<TR>
							<TD bgcolor='#444E6C' align='right' class=maintxt colspan='2'>
								<input type='submit'  class='Submit' name='search' value='".
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
	
	function getWorldsSelect($dbid)
	{
		$str = "<SELECT name='dbid'>";
		$sql = "SELECT worldname, id_world from ds_world";
		$res = $this->common->getMultyArray("", $sql,"","","");	
		if($this->common->errormsg || (sizeof($res) === 0))
		{
			$this->outputResult("can't get list of worlds");			
		}
		
		foreach ($res as $k=>$v)
		{
			$checked = '';
			if(strcmp($dbid,$v['dbId'].'-'.$v['id_world']) === 0)
			{
				$checked = 'selected';
			}
			$str.= "<OPTION ".$checked." value='".$v['dbId'].'-'.$v['id_world']."'>".$v['worldname']."</OPTION>";
		}
		
		$str.="</SELECT>";
		return $str;
	}
	
	function showUserEvents($username,$dbId,$idWorld)
	{		
		if(!$this->common->setDbId($dbId))
		{
				$this->outputResult("Connection to db ".$dbId." failed");				
		}	
		
		$sql = sprintf("select 
								count(ui.id_userinstance)
							from
								ds_user u,
								ds_userinstance ui
							where
								u.id_user = ui.id_user
								and u.username = '%s'",
						$username
						);
						
		$res = $this->common->getResultArray ( "",$sql, "", "", "" );						
		if($this->common->errormsg)
		{
			$this->outputResult("error in checking user");			
		}	
		
		if((int)$res[0]['count'] === 0)
		{
			$this->outputResult("Здесь такие не живут");
			return false;
		}	
		
		
		$sql = sprintf("SELECT DISTINCT
       									ep.id_eventqueue,
       									ep.id_userinstance,
       									ev.eventname,       									
       									to_char (eq.eventstarttime, 'YYYY-MM-DD HH24:MI:SS' ) as eventstarttime,
       									to_char (eq.eventendtime, 'YYYY-MM-DD HH24:MI:SS' ) as eventendtime,
       									(eq.eventendtime < now()) as ended, 									       									
									(
 									SELECT
       										ur2.username
 										FROM
     										ds_eventparticipant ep,
     										ds_userinstance ui2,
     										ds_user ur2
 										WHERE
      										ep.id_eventqueue = eq.id_eventqueue
      										AND ep.id_userinstance != ui.id_userinstance
      										AND ep.id_userinstance = ui2.id_userinstance
      										AND ui2.id_user = ur2.id_user
									) as participant
 								FROM
     								ds_eventparticipant ep,
     								ds_eventqueue eq,
     								ds_event ev,
     								ds_eventhasoption eho,
     								ds_eventoption eo,
     								ds_userinstance ui,
     								ds_user ur
 								WHERE
      								ur.username = '%s' AND
      								ur.id_user = ui.id_user AND
      								ep.id_userinstance = ui.id_userinstance AND
      								ui.id_world = %d AND
      								ep.id_eventqueue = eq.id_eventqueue and
      								ev.id_event = eq.id_event AND
      								eq.istrash = 'f' and
      								eho.id_eventqueue = eq.id_eventqueue and
      								eho.id_eventoption = eo.id_eventoption
						order by eventstarttime",
						$username,
						$idWorld);
						
		$res = $this->common->getResultArray ( "",$sql, "", "", "" );						
		if($this->common->errormsg)
		{			
			$this->outputResult("can't get user events");			
		}	
		
		if(sizeof($res) === 0)
		{
			$this->outputResult("User has no-no events");			
		}	
		
		echo "<br><br>
				<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<INPUT type='hidden' name='numberOfEvent' value='".sizeof($res)."'>
				<INPUT type='hidden' name='dbid' value='".$dbId.'-'.$idWorld."'>
				<INPUT type='hidden' name='username' value='".$username."'>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2>
							idEventqueue
							</TD>
							<TD bgcolor='444E6C' class=th_2>"
							.local('DS_USER_USERNAME' ).
							"</TD>
							<TD bgcolor='444E6C' class=th_2>
							eventName
							</TD>
							<TD bgcolor='444E6C' class=th_2>
							eventStartTime
							</TD>
							<TD bgcolor='444E6C' class=th_2>
							eventEndTime
							</TD>							
							<TD bgcolor='444E6C' class=th_2>
							Participant
							</TD>
							<TD bgcolor='444E6C' class=th_2>
							End Event
							</TD>
						</TR>";

		$count = 0;
		foreach ($res as $k=>$v)
		{
			if(strcmp($username,$v['participant']) === 0)
			{
				continue;
			}
			
			if(strcmp($v['ended'],'t'))
			{
				$color = '#8080C0';
			}
			else 
			{
				$color = 'red';
			}
			echo "<TR>
					  <TD bgcolor='#444E6C' class=maintxt align='center'>
					  <font color='".$color."'>".
								$v['id_eventqueue']."
						</font>
					  </TD>
					  <TD bgcolor='#444E6C' class=maintxt align='center'>
					  <font color='".$color."'>".
								$username." (".$v['id_userinstance'].")"
						."</font>
						</TD>
					  <TD bgcolor='#444E6C' class=maintxt align='center'>
					  <font color='".$color."'>".
							$v['eventname']."
						</font>
					  </TD>
					  <TD bgcolor='#444E6C' class=maintxt align='center'>
					  <font color='".$color."'>".
								$v['eventstarttime']."
						</font>
					  </TD>
					  <TD bgcolor='#444E6C' class=maintxt align='center'>
					  <font color='".$color."'>".
								$v['eventendtime']."
						</font>
					  </TD>					 
					  <TD bgcolor='#444E6C' class=maintxt align='center'>
					  <font color='".$color."'>".
								$v['participant']."
					  </font>
					  </TD>
					  <TD bgcolor='#444E6C' class=maintxt align='center'>
					  	<INPUT type='checkbox' class='check' value='".
								$v['id_eventqueue']."/".$v['eventname']."' name='ev".$count."'>
					  </TD>
				</TR>";
			$count++;
		}	
		
		echo 	"<TR>									
					<TD bgcolor='#444E6C' align='right' class=maintxt colspan='7'>
						<input type='submit'  class='Submit' name='endAll' value='End all events'>
						<input type='submit'  class='Submit' name='end' value='End checked events'>
					</TD>
				</TR>
				</TABLE>
					</td>
				</tr>
				</form>
				</table>";
		
		return true;
								
	}
	
	function endEvent($dbId, $eventId)
	{		
		assert(isset($eventId));
		assert(isset($dbId));
	
		if(!$this->common->setDbId($dbId))
		{
				$this->outputResult("Connection to db ".$dbId." failed");				
		}			
		$sql = sprintf("UPDATE
      							ds_eventqueue
							SET
   								eventendtime = now()
							WHERE
     							id_eventqueue = %d",
						$eventId);
						
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->outputResult("can't get end event with ID=".$eventId."<br>");				
			return false;		
		}	
		
		//логируем
		//actionLog( 'End event for user ');
		
		return true;
	}
	
	function getPostData($data)
	{			
		$this->showRequestForm();
		
		if(isset($data['search']))
		{			
			assert(isset($data['dbid']));
			assert(isset($data['username']));
			list($dbId,$worldId) = explode('-',$data['dbid']);			
			$this->showUserEvents($data['username'],$dbId,$worldId);
		}
		if(isset($data['end']))
		{	
			assert(isset($data['dbid']));
			assert(isset($data['username']));
			assert(isset($data['numberOfEvent']));
			assert($data['numberOfEvent'] > 0);
			list($dbId,$worldId) = explode('-',$data['dbid']);
			$end_events_counter = 0;
			$detail = "";

			if(!$this->common->setDbId($dbId))
			{
				$this->outputResult("Connection to db ".$dbId." failed");
			}

			//Получаем имя мира по его id
			$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $worldId";

			$res = $this->common->getResultArray("", $sql, "", "", "");
			if($this->common->errormsg)
			{
				$this->outputResult("can't get world name by ID<br>");
				return false;
			}
			$worldName = $res[0]['worldname'];

			for($i=0; $i<$data['numberOfEvent'];$i++)
			{
				if(isset($data['ev'.$i]))
				{
					$tempArr = explode("/", $data['ev'.$i]);
					$evID = $tempArr[0];
					$evName = $tempArr[1];

					if($this->endEvent($dbId, $evID))
					{
						$end_events_counter++;
					}
					$detail .= "End event: $evName<br>";
				}
			}
			
			//логируем
			actionLog( "End events for user '".$data['username']."' in world '$worldName'", $detail);
				
			$this->outputResult("end ".$end_events_counter." of user events");
			$this->showUserEvents($data['username'],$dbId,$worldId);
			
		}
		if(isset($data['endAll']))
		{			
			assert(isset($data['dbid']));
			assert(isset($data['username']));
			
			list($dbId,$worldId) = explode('-',$data['dbid']);				
			
			if(!$this->common->setDbId($dbId))
			{
				$this->outputResult("Connection to db ".$dbId." failed");
			}

			//Получаем имя мира по его id
			$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $worldId";

			$res = $this->common->getResultArray("", $sql, "", "", "");
			if($this->common->errormsg)
			{
				$this->outputResult("can't get world name by ID<br>");
				return false;
			}
			$worldName = $res[0]['worldname'];

			$this->endAllUserEvents($dbId,$data['username']);
			
			//логируем
			actionLog( "End all events for user ".$data['username']." in world '$worldName'");
			
			$this->showUserEvents($data['username'],$dbId,$worldId);
			
		}
	}
	
	function endAllUserEvents($dbId,$username)
	{
		assert(isset($username));
		assert(isset($dbId));
	
		if(!$this->common->setDbId($dbId))
		{
				$this->outputResult("Connection to db ".$dbId." failed");				
		}		
		
		$sql = sprintf("UPDATE
     							ds_eventqueue
 							SET
       							eventendtime = now()
 							WHERE
      							id_eventqueue in (
      							SELECT DISTINCT
             									ep.id_eventqueue
      										FROM
             									ds_eventparticipant ep,
             									ds_eventqueue eq,
             									ds_event ev,
             									ds_eventhasoption eho,
             									ds_eventoption eo,
             									ds_userinstance ui,
             									ds_user ur
      										WHERE
           										ur.username = '%s' AND
           										ur.id_user = ui.id_user AND
           										ep.id_userinstance = ui.id_userinstance AND
           										ep.id_eventqueue = eq.id_eventqueue and
           										eq.istrash = 'f' and
           										eq.eventendtime > now() AND
           										ev.id_event = eq.id_event AND
           										eho.id_eventqueue = eq.id_eventqueue and
           										eho.id_eventoption = eo.id_eventoption)",
								$username);
						
		$this->common->executeQuery($sql);
		if($this->common->errormsg)
		{
			$this->outputResult("can't end user events <br>");				
			return false;		
		}	
		$this->outputResult("end all user events <br>");
		return true;
	}
	
	function outputResult($str)
	{
		ShowCMSMessage( $str, 1 );
		return true;
	}
	
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>