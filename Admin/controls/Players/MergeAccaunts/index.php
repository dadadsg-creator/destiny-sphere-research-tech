<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Mergeaccounts><font size=3><b>HELP</b></font></a></p>';

if ( !isset ( $GLOBALS["common"] ) ) {

	$common = & ClassFactory::getInstance ( "DS_Common" );

	if ( !$common->setDbId(0) ) {
		die( local( 'DS_DB_FAILED' )." : ".$common->errormsg );
	}
}


class mergeAccounts {
	
	var $dbWorldAccess;
	
	var $mainUsername;
	var $usernames;
	
	var $out;
	
	var $errormsg;

	function mergeAccounts( ) {

		$this->dbWorldAccess = $GLOBALS['dbWorldAccess'];
		$str = '';
		
		if( isset( $_POST['submit'] ) ){
			
			if ( $this->getPostData ( $_POST ) ) {
				
				if( !$this->proceed() ){
					
					$str = $this->errormsg ;
					
				}else{
				
					$str = $this->out ;
				}

			}else{
				
				$str = $this->errormsg;
			}
		}

		$this->showFirstMenu( $str );
	}
	
	
	function showFirstMenu ( $str = "" ) {
		
		global $common;

		print $str."\n<br>
		
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=4>
			<tr>
				<td bgcolor='#444E6C' class=th_2>".local( 'DS_USER_MAIN' )."</td>
			</tr>
			<tr>
				<td bgcolor='#444E6C' ><input type='text' name='username_main' value='' size=25></td>
			</tr>
			<tr>
				<td bgcolor='#444E6C' class=th_2>".local( 'DS_USER_2MERGE' )."</td>
			</tr>
			<tr>
				<td bgcolor='#444E6C'>
					<table cellSpacing='0' cellPadding='0'  bgcolor='#444E6C'>";
		for ( $i = 0; $i < 6; $i++ ){
			print "<tr><td bgcolor='#444E6C'><input type='text' name='usernames[]' value='' size=25></td></tr>";
		}
		print "
					</table>
				</td>
			</tr>
			<tr>
				<td bgcolor='#444E6C' align='center'><input type='submit' value=' ". local( 'DS_ACTION_MERGE' )." ' name='submit' class='submit'></td>
			</tr>
		</table></td></tr></table>
		</form>
		";
		
		return true;
	}
	

	function getPostData ( $data ) {
		
		if( isset( $data['usernames'] ) ){
			
			if( is_array( $data['usernames'] ) ){

				foreach ($data['usernames'] as $name){
					if ($name != "") {$names[] = $name;}
				} 
				
				if ( count ( $names ) > 0 ) {
					$this->usernames = "'".implode( "','", $names )."'";
				}
				
			}else{
				
				$this->usernames = "'".$data['usernames']."'";
			}

		}else{
			
			$this->errormsg = 'Enter Usernames to Merge';
			return false;
		}
		
		if( isset( $data['username_main'] ) ){
			
			$this->mainUsername = $data['username_main'];
			
		}else{
			
			$this->errormsg = 'Enter Main Username';
			return false;
		}
		
		return true;
	}
	
	
	function proceed( ){
		
		global $common;
		
		/*GET World List*/
		$sql = "select id_world, worldname from ds_world";
		$res = $common->getMultySql ($sql);
		if ( !$res ) {
			$this->errormsg = " SYSTEM ERROR - GetWorldList";
		}

		foreach($res as $k=>$v)
		{
			$common->setDbId($v['dbId']);
			$this->wid = $v[id_world];
			
			$res = '';
			
			$sql = "		
				SELECT 
					u.username,
					u.id_user,
					ui.id_world,
					ui.id_userinstance
				FROM
					ds_user u
					LEFT OUTER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
				WHERE
					(u.username IN ("."'".$this->mainUsername."', ".$this->usernames."))
			";			
			$res = $common->getQueryArray($sql);	
				
			if ($res)
			{
				$userKey = 0;
				
				foreach ($res as $k => $user)
				{
					if($user['username'] == $this->mainUsername)
					{
						$userKey = $k;
///*DEBUG*/				echo "<h1>User found; key: $userKey</h1>";
						break;
					}
				}

				if($res[$userKey][username] == $this->mainUsername)
				{
					/* Точка разбора, если существует mainUsername*/
					$usedWorlds = array();					
					
					$this->main_username = $res[$userKey][username];
					$this->main_id_user = $res[$userKey][id_user];
					
					if(count(explode(',', $this->usernames)) > count($res))
					{
						$this->errormsg = "Merge Accounts Failed. Virify usernames "."'".$this->mainUsername."', ".$this->usernames." in all worlds";
///*DEBUG*/						echo "<h1> Точка выхода. Один из аккаунтов не сеществует.</h1>";
						return false;
					}

///*DEBUG*/					print_r ($uinfo );
					foreach($res as $key => $uinfo)
					{
///*DEBUG*/				echo "<h1> $key </h1>";
						if(!empty($uinfo[id_world])) 
						{ 
							$world = (int) $uinfo[id_world];
							
							if(in_array($world, $usedWorlds)) 
							{
								$this->errormsg = "Merge Accounts Failed. Double Worlds. Virify usernames "."'".$this->mainUsername."', ".$this->usernames." in all worlds";
///*DEBUG*/								echo "<h1>".$multimerge." Точка выхода. Эти аккаунты обьединить нельзя. У этих пользователей есть соты в одинаковых мирах.</h1>";
								return false;
							} else {
								
								$usedWorlds[] = $world;
								
								if($key == $userKey) 
								{
///*DEBUG*/							echo "<h1>$key == $userKey</h1>";
									continue;
								}

								$res = $common->executeQuery( "BEGIN" );
								
								$sql = "UPDATE
		      								ds_userinstance
										SET
		   									id_user = '".$this->main_id_user."'
		   								WHERE 
		   									id_userinstance = '".$uinfo[id_userinstance]."'
		   								
		   						";

								$res = $common->executeQuery( $sql );
///*DEBUG*/								echo $sql."<br>";
								if ( !$res )
								{
									$this->errormsg = "ERROR Change User in World";
									return false;
								}
								
								$db_old = $common->dbId;
								echo "<h1>$common->dbId</h1>";

								$common->setDbId(0);

								$res = $common->executeQuery( "BEGIN" );
								
								$sql = "UPDATE 
											ds_userinstancecollection 
										SET 
											id_user = '".$this->main_id_user."' 
										WHERE 
											dbname = '".$common->dbWorldAccess[$db_old][server]."'
											AND id_userinstance = '".$uinfo[id_userinstance]."'
								";

								$res = $common->executeQuery( $sql );
///*DEBUG*/								echo $sql."<br>";
								if ( !$res )
								{
									$this->errormsg = "ERROR Change User in InstanceCollection";
									return false;
								}

								//$res = $common->executeQuery( "ROLLBACK" );
								
								$common->setDbId($db_old);
								
								//$res = $common->executeQuery( "ROLLBACK" );
								$res = $common->executeQuery( "COMMIT" );
								
								if( !$res  )
								{
									$this->errormsg = "Failed to Merge Accounts";
									return false;
								} else {
									actionLog( 'Merge Accounts: '.$this->mainUsername ." AND " . $this->usernames . ", Db: " . $this->dbWorldAccess[ $db_old ]['server'] );					
									$this->out = "Merge Accounts Done<br>";
									//return true;
								}
							}
						} else {
							echo "<h1>There is nothing to merge</h1>";
						}
					}
				} else {
					/*Условие не выполнилось. В Базе нет Main игрока.*/
					$this->errormsg = "ERROR - Check Main UserName.";
///*DEBUG*/					echo "<h1>".$multimerge." Точка выхода. Эти аккаунты обьединить нельзя так как нет юзера, в которого будем вливать остальные аккаунты</h1>";
					return false;
				}							
			} else {
				echo "ERROR: Can't get user info. May be user not exist<br>";
			}
		}


				
		if ($this->out)
		{
			//echo $this->out;
			return true;	
		}
		
	
	}
}

$mA = new  mergeAccounts();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>