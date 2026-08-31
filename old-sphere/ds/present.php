<?
include_once ( 'classfactory.php' );

class Present
{
	var $common;
	function Present()
	{		
		$this->common = & ClassFactory::getInstance ( "DS_Common" );
		if(!$this->common->setDbId(0) ) 
		{
			echo "DB-connection failed";
			exit;
		}
		$this->getPostData($_REQUEST);
	}
	
	function getPostData ($data) 
	{
		if(!isset($data['uid']))
		{
			echo 'Present::error1';
			exit;
		}
		
		if($this->isUserHavingPresent($data['uid']))
		{
			echo 'Present::error2';
			exit;
		}
		else 
		{
			if($this->isUserHavingPremiumRegistration($data['uid']))
			{
				if($this->isUserTrial($data['uid']))
				{
					$this->doPresent($data['uid']);
					$this->showSuccessResult();
				}
				else 
				{
					echo 'Present::error6';
					exit;
				}
			}
			else 
			{
				$this->showRegistrationWelcome();
			}
		}
		
		return true;
	}
	
	function isUserHavingPresent($uid)
	{
		$sql = sprintf("SELECT
      						*
						FROM
    						ds_present p
						WHERE
     						p.id_user = %d",
						$uid);
						
		$res = $this->common->getResultArray("",$sql,"","","");
		if($this->common->errormsg)
		{
			echo 'Present::error3';
			exit; 
		}
		if(sizeof($res)>0)
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
	function isUserHavingPremiumRegistration($uid)
	{
		$sql = sprintf("SELECT
      							*
							FROM
    							ds_userprofidata upd
							WHERE
     							upd.id_user = %d",
						$uid);
						
		$res = $this->common->getResultArray("",$sql,"","","");
		if($this->common->errormsg)
		{
			echo 'Present::error4';
			exit; 
		}
		if(sizeof($res)>0)
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
	function showRegistrationWelcome()
	{
		echo 'welcome';
	}
	
	function isUserTrial($uid)
	{
		$sql = sprintf("SELECT
      							*
							FROM
    							ds_userprofidata upd
							WHERE
     							upd.id_user = %d
     							AND upd.trial = 't'
     							AND upd.enddate < now()",
						$uid);
						
		$res = $this->common->getResultArray("",$sql,"","","");
		if($this->common->errormsg)
		{
			echo 'Present::error5';
			exit; 
		}
		if(sizeof($res)>0)
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
	function doPresent($uid)
	{
		$this->common->executeQuery('begin');
		
		$sql = sprintf("INSERT INTO
          					 ds_present
           					(id_user, days)
						VALUES
      						(%d,5)",
						$uid);
						
		$this->common->executeQuery($sql);
		
		if($this->common->errormsg)
		{
			$this->common->executeQuery('rollback');
			echo 'Present::error7';
			exit; 
		}		

		$sql = sprintf("UPDATE
      						ds_userprofidata
						SET
   							startdate = now(),
   							enddate = now() + '5 DAYS'::interval,
   							active = 't'
						WHERE
     						id_user = %d",
						$uid);
						
		$this->common->executeQuery($sql);
		
		if($this->common->errormsg)
		{
			$this->common->executeQuery('rollback');
			echo 'Present::error8';
			exit; 
		}				
		
		$this->common->executeQuery('commit');
		return true;
	}
	
	function showSuccessResult()
	{
		echo 'success';
		exit;
	}
}
?>