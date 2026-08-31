<?
class premiumLogger
{
	var $common;
	
	function premiumLogger($common = false)
	{		
		if(!$common)
		{
			require_once('../DS_Common.php');
			$common = new DS_Common;
			if(!$common->connect()) 
			{
				echo "DB-connection failed";
				exit;
			}
		}
		
		$this->common = $common;		
	}
	
	function log($id_user, $since, $duration, $prolonged, $by_whom)
	{
		assert(is_numeric($id_user));
		assert($id_user > 0);
		$sql = sprintf("INSERT INTO
           						ds_premiumonlog
           						(id_user, since, duration, is_prolonged, by_whom, changetime)
       						VALUES
             						(%d,%s,%d,%s,'%s',now())",
							$id_user,
							$since,
							$duration,
							$prolonged,
							$by_whom);
		 $this->common->executeQuery($sql);
		if ($this->common->errormsg) 
		{
			return false;	
		}
	}
}

?>