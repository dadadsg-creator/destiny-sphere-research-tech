<?php

class srchStoreUser
{
	private $srchName = false;
	
	private $srchReason = false;
	
	private $srchRestore = false; 
	
	private $srchStoreUserId = 0; 
	
	private $common;
	
	function __construct($common)
	{
		 $this->setCommon($common);
	}
	
	function setCommon($common)
	{
		assert($common);
		$this->common = $common;
	}
	
	function setSrchName($name)
	{
		$this->srchName = $name;
	}
	
	function setSrchReason($reason)
	{
		//assert((int)$reason);
		$this->srchReason = (int)$reason;
	}
	
	function setSrchRestore($restore)
	{
		$this->srchRestore = $restore;
	}
	
	function getStoreUserById($id_storeuserdata)
	{
		assert($id_storeuserdata);
		
		$sql = "
			SELECT
				*
			FROM
				ds_storeuserdata AS sud
				INNER JOIN ds_user AS u ON u.id_user = sud.id_user
			WHERE
				sud.id_storeuserdata = $id_storeuserdata
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		
		assert(empty($this->common->errormsg));
		
		return $res[0];
		
	}
	
	private function _getSrchQuery()
	{
		$query = '';
		
		$nameSrchPart = '';
		if($this->srchName)
		{
			$nameSrchPart = " u.username LIKE '%".$this->srchName."%' ";
		}	
		
		$reasonSrchPart = '';
		if($this->srchReason)
		{
			$reasonSrchPart = " sud.id_storeuserreason = ".$this->srchReason." ";
		}	
		
		if($nameSrchPart || $reasonSrchPart)
		{
			$query = "WHERE ";
			if($nameSrchPart && $reasonSrchPart)
			{
				$query .= $nameSrchPart."AND".$reasonSrchPart;
			} else {
				$query .= $nameSrchPart.$reasonSrchPart;
			}
		}	
		
		$query = "
			SELECT 
				* 
			FROM 
				ds_storeuserdata AS sud
				INNER JOIN ds_user AS u ON u.id_user = sud.id_user
				INNER JOIN ds_storeuserreason AS sur ON sur.id_storeuserreason = sud.id_storeuserreason
			".$query;
		return $query;	
	}
	
	public function search()
	{
		$sql = $this->_getSrchQuery();
		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		
		assert(empty($this->common->errormsg));
		
		return $res;
	}
}
?>