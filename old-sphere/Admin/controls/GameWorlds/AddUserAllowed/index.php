<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=AddUserAllowed><font size=3><b>HELP</b></font></a></p>';

class AddUserAllowed
{
	var $err;
	var $common = false;
	
	function AddUserAllowed()
	{
		$this->common = ClassFactory::getInstance("DS_Common");
		$this->common->connect() or die("can't connect to master");
	
		$this->showAddMenu();
		if(!$this->getPostData($_POST))
		{
			ShowCMSMessage($this->err);
		}	
	}
	
	function getPostData($data)
	{
		if(sizeof($data) === 0)
		{
			return true;
		}
		if(!isset($data['id_world']))
		{
			$this->err = 'Lost world id';
			return false;
		}
		
		if(!isset($data['username']))
		{
			$this->err = 'Lost username';
			return false;
		}
		
		$id_user = $this->getIdUserByName($data['username']);
		if(!$id_user)
		{
			$this->err = 'Wrong username';
			return false;
		}
		
		list($dbname, $id_world) = explode('_', $data['id_world']);
		$db_id = $this->common->getDbIdByName($dbname);
		if(!$this->common->setDbId($db_id))
		{
			$this->err = "Can't connect to ".$dbname;
			return false;
		}
		
		$check = $this->checkOnExists($id_user,$id_world);
		
		if($check === -2)
		{
			$this->err = "Can't check on duplicate ";
			return false;
		}
		
		if(key_exists('count', $data))
		{
			$count = max(0, (int)$data['count']);
		} else {
			$count = 0;
		}
		
		if($check == -1)
		{
			$sql = "
				INSERT INTO ds_usersallowed
           			(id_user, id_world, count)
       			VALUES
             		($id_user, $id_world, $count)
             	";	
		} else {
			$sql = "
				UPDATE
					ds_usersallowed
				SET
					count = count + $count
				WHERE
					id_user = $id_user
					AND
					id_world = $id_world
				";
			$this->err = "User ".$data['username']." already allowed for ".$check." enters";
			//return false;
		}
		
						
		$this->common->executeQuery ($sql);
		if($this->common->errormsg)
		{
			$this->err = "can't insert data into ds_usersallowed";
			return false;
		}
		
		if(!$this->common->setDbId(0))
		{
			$this->err = "Can't connect to master";
			return false;
		}
		
		if($check == -1)
		{
			$sql = "
					INSERT INTO ds_usersallowedcollection
           				(id_worldcollection, id_user, id_world, dbname)
       				SELECT
       					id_worldcollection,
             			".$id_user.",
             			".$id_world.",
             			'".$dbname."'
             		FROM
	             		ds_worldcollection
    	         	WHERE
        	     		dbname = '".$dbname."'
            	 		AND
             			id_world = ".$id_world."
             	";
			$this->common->executeQuery ($sql);
			if($this->common->errormsg)
			{
				$this->err = "can't insert data into ds_usersallowedcollection";
				return false;
			}
		} else {
			$sql = "
				UPDATE
					ds_usersallowedcollection
				SET
					count = count + $count
				WHERE
					dbname = '".$dbname."'
					AND
					id_world = ".$id_world."
					AND
					id_user = ".$id_user."
				";
		}
					
		
		ShowCMSMessage("User ".$data['username'].
						" was succesfully added to allowed users on db ".$dbname);
		return true;
	}
	
	function checkOnExists($id_user,$id_world)
	{
		$sql = sprintf("SELECT
      							count
							FROM
    							ds_usersallowed ua
							WHERE
     							ua.id_user = %d
     							and ua.id_world = %d",						
						$id_user,
						$id_world);
						
		$res = $this->common->getResultArray("", $sql, "", "", "" );
		if($res === false)
		{
			return -2;
		}
		
		if((sizeof($res) === 0))
		{
			return -1;
		}
		
		return $res[0]['count'];
	}
	
	function getIdUserByName($name)
	{
		$sql = sprintf("SELECT
      							u.id_user
							FROM
    							ds_user u
							WHERE
     							u.username = '%s'",						
						$name);
		$res = $this->common->getResultArray("", $sql, "", "", "" );
		if(!$res || (sizeof($res) === 0))
		{
			return false;
		} 
		
		return $res[0]['id_user'];
	}
	
		

	function showAddMenu()
	{
		?>
		<form action='<? echo $_SERVER["PHP_SELF"]?>' method='POST'>				
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
			<tr>
				<td>
				<table width=100% border=0 cellspacing=1 cellpadding=3>
				<tr>
					<td bgcolor='444E6C' class=th_2>
						<? echo local( 'DS_USER_WORLD' );?>
					</td>
					<td bgcolor='444E6C' class=th_2>
						<? echo local('DS_USER_USERNAME');?>
					</td>
					<td bgcolor='444E6C' class=th_2>
						участие в турнире
					</td>
				</tr>
				<tr>
					<td bgcolor='444E6C'>
						<select name='id_world' style='width:250px;'>
						<?
						$sql = sprintf("SELECT
      											wc.worldname,
      											wc.id_world,
      											wc.dbname
											FROM
    											ds_worldcollection wc" );
						
						$this->common->setDbId(0);
						$res = $this->common->getResultArray("", $sql, "", "", "" );
																	
						foreach ($res as $k=>$v)
						{							
							echo "<option 
										value='".$v["dbname"]."_".$v["id_world"]."'>".
										$v['worldname'].
								'</option>';
						}
						
						?>
						</select>
					</td>
					<td bgcolor='444E6C'>
						<input type='text' name='username' size=30>
					</td>
					<td bgcolor='444E6C'>
						<input type='text' name='count' size=2 value=0>
					</td>
				</tr>
				<tr>
					<td colspan=3 bgcolor='444E6C'>
						<input type='submit' class=submit name='next' 
							value='добавить'>
					</td>
				</tr>
				</table>
				</td>
			</tr>
		</table>
		<?
	}
}

$obj = new AddUserAllowed();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>