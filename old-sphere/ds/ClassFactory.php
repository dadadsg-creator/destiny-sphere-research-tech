<?PHP

class ClassFactory {	

	public $errormsg;

	function getError () {
		return $this->errormsg;
	}

	function getInstance ( $classname, $dbname = "" ) {

		include_once ( $classname.".php" );

		if ( !class_exists ( $classname ) ) {
			echo $classname;
			//die ( "class not found $classname" );
			$this->errormsg = "class $classname not found";
			return false;
		}

		if ( $classname == "DB" )	{

			if ( $dbname == "" ) {
				/*if($GLOBALS['dbId']){
					$dbname = $GLOBALS['dbWorldAccess'][$GLOBALS['dbId']]['dbname'];
					$host = $GLOBALS['dbWorldAccess'][$GLOBALS['dbId']]['host'];
        			$user = $GLOBALS['dbWorldAccess'][$GLOBALS['dbId']]['user'];
					$pass = $GLOBALS['dbWorldAccess'][$GLOBALS['dbId']]['password'];
				}else{
					$dbname = "destiny1";
					$host = "gamedb1.destinysphere.ru";
        			$user = 'dsgame';
					$pass = 'REDACTED_DB_PASSWORD';
				}*/
				
				$dbname = "destiny1";
					$host = "gamedb2.destinysphere.ru";
        			$user = 'dsgame';
					$pass = 'REDACTED_DB_PASSWORD';
				
			} else {	

				if ( $dbname == "forum" || $dbname == "forum_ru") {
					
					$user = 'rouslan';
					$pass = 'REDACTED_DB_PASSWORD';
					$host = "forum.destinysphere.ru";
				}
			}

			
			
			//if ( $dbname == "" ) $dbname = "destiny1";
			//$user = 'rouslan';
			//$pass = '';
			//$host = 'localhost';

			$dsn = "pgsql://$user:$pass@$host/$dbname";
echo"<hr><pre>"; var_dump($dsn); echo "</pre><hr>";
			$db = DB::connect ( $dsn );
			if ( DB::isError ( $db ) ) {
				error_log( $db->getMessage () );
				die ( $db->getMessage () );
			}
die();
			$db->setFetchMode( DB_FETCHMODE_OBJECT );
			$instance = & $db;	

			return $instance;

		} else {

			$instance = new $classname;

			return $instance;
		}
	}
}

?>