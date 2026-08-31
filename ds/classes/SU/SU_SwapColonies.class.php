<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_SwapColonies
{
	
	static private $db1 = 3;
	
	static private $db2 = 5;
	
	static private $common;
	
	static public function swap()
	{
		self::$common = DS_Common::CreateForCurentDB(self::$db1);
		
		$userList = self::getUserList();
		
		foreach($userList as $k => $user)
		{
			$dbId = self::$common->getDbIdByName($user['server_name']);	
			
			$reserve = self::GetReservedColonies($user['username']);
			$oldColonie = self::GetOldColonies($dbId, $user['username']);
			
			$oldColoniePriority = array_keys($oldColonie);
			
			if(count($oldColonie) == 2)
			{
				$name1 = $oldColonie[0]['combname'];
				$name2 = $oldColonie[1]['combname'];
				
				list($name1_part, $num) = explode("_", $reserve[0]['combname']);
				
				if($reserve[0]['combname'] == $name1 || $name1_part == $name1)
				{
					$n = 0;
				} else {
					$n = 1;
				}
				
				if($oldColoniePriority[$n] == $reserve[0]['priority'])
				{
					echo "OK !!!!\n";
				} else {
					echo "NEED SWAP !!!!\n";
					
					$coords1 = "4.".$oldColonie[0]['complexumber'].".".$oldColonie[0]['combnumber'];
					$coords2 = "4.".$oldColonie[1]['complexumber'].".".$oldColonie[1]['combnumber'];
					
					echo $name1.":".$coords1."<->".$name2.":".$coords2."\n";
				}

			}
		}
	}
	
	static private function getUserList()
	{
		self::$common->setDbId(self::$db1);
		
		$sql = "
SELECT
    DISTINCT rc.username,
    wu.server_name
FROM
    ds_reservedcomb AS rc
    INNER JOIN ds_comb AS c ON (c.id_comb = rc.id_comb)
    INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    INNER JOIN ds_user AS u ON (u.username = rc.username)
    INNER JOIN ds_worldunionuserinfo AS wu ON (wu.id_user = u.id_user)
WHERE
    r.id_world = 2
    AND
    r.ringnumber = 4
    AND
    NOT rc.priority = 0
			";	

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;	
	}
	
	static public function GetReservedColonies($username)
	{
		self::$common->setDbId(self::$db1);
		
		$sql = "
SELECT
    rc.username,
    rc.id_comb,
    rc.priority,
    wu.server_name,
    c.combnumber,
    cc.complexnumber,
    r.ringnumber
FROM
    ds_reservedcomb AS rc
    INNER JOIN ds_comb AS c ON (c.id_comb = rc.id_comb)
    INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    INNER JOIN ds_user AS u ON (u.username = rc.username)
    INNER JOIN ds_worldunionuserinfo AS wu ON (wu.id_user = u.id_user)
    INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
WHERE
    r.id_world = 2
    AND
    r.ringnumber = 4
    AND
    rc.priority = 0
    AND
    rc.username = '$username'
ORDER BY cc.complexnumber, c.combnumber
";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function GetOldColonies($dbId, $username)
	{
		self::$common->setDbId($dbId);
		
		$sql = "
SELECT
	c.combnumber,
	cc.complexnumber,
	r.ringnumber,
	cn.combname
FROM
	ds_comb AS c
    INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    INNER JOIN ds_user AS u ON (u.username = rc.username)
    INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
WHERE
	r.ringnumber = 4
	AND
	u.username = '$username'
	AND
	r.id_world = 1
ORDER BY cc.complexnumber, c.combnumber
";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
}

?>