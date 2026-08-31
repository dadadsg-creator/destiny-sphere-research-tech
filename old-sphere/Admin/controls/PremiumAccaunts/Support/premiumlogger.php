<?
class premiumLogger
{
	var $common;
	
	function premiumLogger()
	{		
		if(!isset($GLOBALS["common"]))
		{
			$common = &ClassFactory::getInstance("DS_Common");
			if(!$common->setDbId(0)) 
			{
				echo local('DS_DB_FAILED')." : ".$common->errormsg;
				exit;
			}
			$this->common = $common;				
		}
		else 
		{			
			 $this->common = $GLOBALS["common"];	
		}			
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