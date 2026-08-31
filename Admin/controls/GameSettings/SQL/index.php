<?PHP

set_time_limit(0);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";



class executeQuery {
	
	var $dbWorldAccess;
	
	var $conn;

	var $dbId;
	var $getMulty;
	var $username;
	var $password;
	var $query;
	var $getResult;
	
	var $out;
	
	var $errormsg;

	function executeQuery ( ) {
		
		$this->dbWorldAccess = $GLOBALS['dbWorldAccess'];
		
		if( isset( $_POST['submit'] ) ){
			
			if ( $this->getPostData ( $_POST ) ) {
				
				if( !$this->proceed() ){
					
					echo $this->errormsg."\n<br>";
				}

				echo "<table cellSpacing='0' cellPadding='5'>".$this->out."</table>";

			}else{
				echo $this->errormsg;
			}
		}else{
			
			$this->showFirstMenu();
		}
	}
	
	
	function showFirstMenu ( $str = "" ) {
		
		print "
		<table>
		".$str."
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		
		<table cellSpacing='0' cellPadding='5'>
			<tr>
				<td><textarea name='query' style='width:500px;height:400px;' ></textarea></td>
				<td colspan =2 bgcolor='#444E6C'>
					<table cellSpacing='0' cellPadding='5'>
						<tr>
							<td colspan =2 bgcolor='#444E6C'>
		 					<div class='FormText'>";
		
		echo "<strong>".local( 'DS_USER_WORLD' ).":</strong><br> <select name='dbId[]' size='10' multiple>";
		
		for ( $i=0; $i< count( $this->dbWorldAccess ); $i++ ) {
			
			echo "<option value='".$i."'>".$this->dbWorldAccess[$i]['server']."</option>";
		}
		
		echo "
								</select><br>
								</div>
							</td>
							<td>
								<table cellSpacing='0' cellPadding='5'>
									<tr>
										<td colspan =2 bgcolor='#444E6C' class=maintxt>".local( 'DS_USER_USERNAME' )." : </td>
										<td colspan =2 bgcolor='#444E6C'><input type='text' name='exec_username' value=''></td>
									</tr>
									<tr>
										<td colspan =2 bgcolor='#444E6C' class=maintxt>".local( 'DS_USER_PASSWORD' )." : </td>
										<td colspan =2 bgcolor='#444E6C'><input type='password' name='exec_password' value=''></td>
									</tr>
									<tr>
										<td colspan =2 bgcolor='#444E6C' class=maintxt>".local( 'DS_RECEIVE_RESULT' )."</td>
										<td colspan =2 bgcolor='#444E6C'><input type='checkbox' name='getresult' value=1 class=check></td>
									</tr>
									<tr>
										<td colspan =2 bgcolor='#444E6C'></td>
										<td colspan =2 bgcolor='#444E6C'><input type='submit' value='". local( 'DS_ACTION_SUBMIT' )."' name='submit'></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		";
		
		return true;
	}
	

	function getPostData ( $data ) {
		
//		echo '<pre>';
//		print_r( $data );
//		echo '</pre>';
//		die();
		
		if( isset( $data['dbId'] ) ){
			
			$this->dbId = $data['dbId'];
			$this->getMulty = is_array( $this->dbId );

		}else{
			$this->errormsg = 'SELECT DB';
			return false;
		}
		
		if( isset( $data['query'] ) ){
			$this->query = stripcslashes( $data['query'] );
		}else{
			$this->errormsg = 'Enter Query';
			return false;
		}
		
		if( isset( $data['exec_username'] ) ){
			$this->username = $data['exec_username'];
		}else{
			$this->errormsg = 'Enter UserName';
			return false;
		}
		
		if( isset( $data['exec_password'] ) ){
			$this->password = $data['exec_password'];
		}else{
			$this->errormsg = 'Enter Password';
			return false;
		}
		
		$this->getResult = isset( $data['getresult'] );
		
		return true;
	}
	
	
	function proceed( ){
		
		if( $this->getMulty ){
					
			for( $i=0; $i < count( $this->dbId ); $i++  ){
				
				if( !$this->proceedSingl( $this->dbId[$i] ) ){
					
					return false;
				}
			}
			
		}else{
			
			if( !$this->proceedSingl( $this->dbId ) ){
				
				return false;
			}
		}
		
		
		
		return true;
	}
	
	function proceedSingl( $dbId ){
		
		if( !$this->connectSingl( $dbId ) ){
			return false;
		}
		
		actionLog( 'execute Query On DB: '.$this->dbWorldAccess[ $dbId ]['server']."  Admin : ".$UserManager->User[Login] . $this->query );
		
		$this->out .= "<tr><td colspan =2 bgcolor='EEEEEE'>";
		
		$this->out .= "On DB: <strong>".$this->dbWorldAccess[ $dbId ]['server']."</strong>";
		
		$res = @pg_query ( $this->conn, $this->query );
		
		if ( !$res ) {
			$this->errormsg = pg_last_error ( $this->conn )." dbId = ".$dbId;
			return false;
		}
		
		if( $this->getResult ){
			
			if ( pg_num_rows ( $res ) > 0 ) {
				
				$this->out .= $this->formatResult( pg_fetch_all($res) );
				
			}else{
				
				$this->out .= " selected 0 rows.";
				
			}
			
		}else{
			
			$this->out .= " affected ".pg_affected_rows( $res )." rows.";
		}
		
		$this->out .= "</td></tr>";
		
		return true;

	}
	
	function formatResult( $array = ''){
		
		$out = "<table cellSpacing='0' cellPadding='5'><tr>";
		
		foreach ( $array[0] as $key => $val ){
			
			$out .= "<td colspan =2 bgcolor='5eAA5e'>".$key."</td>";
		}
		
		foreach ( $array as $row ){
			
			$out .= "<tr>";
			
			foreach ( $row as $col ){
				
				$out .= "<td colspan =2 bgcolor='e5e5e5'>".$col."</td>";
			}
			
			$out .= "</tr>";
			
		}
		
		$out .= "</tr></table>";
		
		return $out;
	}
	
	
		
	function connectSingl( $dbId ){
		
		$host = $this->dbWorldAccess[$dbId]['host'];
		$dbname = $this->dbWorldAccess[$dbId]['dbname'];
		$user = $this->username;
		$password = $this->password;

		$conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;

		$this->conn = pg_connect ($conn_string);

		if ( !$this->conn ) {
			
			$this->errormsg = local( 'DS_DB_FAILED' );
			return false;
		}
		
		return true;
	}
}

$eQ = new executeQuery ();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>