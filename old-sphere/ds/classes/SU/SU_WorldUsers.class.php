<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_WorldUsers
{
	static private $world;
	
	static private $dbID;
	
	static private $action = 'index';
	
	static private $file;
	
	static private $common;
	
	static private $getStat = false;
	
	static private $packFilePath = '';
		
	static public function Get($data)
	{
		self::_getParams($data);
		
		self::$packFilePath = $_SERVER['DOCUMENT_ROOT'].'/ds/utils/files/WorldUsers';
		
		switch (self::$action)
		{
			case 'index':
				self::_showIndex();
				break;
				
			case 'get_users':
				self::_getUsers();
				break;
		
			default:
				break;
		}
	}
	
	static private function _getUsers()
	{
		self::$file = self::$packFilePath."_".self::$dbID."_".self::$world.".gz";
		
		$downloadFile = './files/WorldUsers'."_".self::$dbID."_".self::$world.".gz";
		
		if(!file_exists(self::$file) || self::$getStat)
		{
			self::_getCSV();
		} else {
			
			$lastChange = date("Y-d-m H:i:s", filemtime(self::$file));
			
			echo "
				<h1>Stat file last change: $lastChange</h1>
				<a href='$downloadFile'>download file</a><br>
				<a href='./WorldUsers.php?world=".self::$dbID."_".self::$world."&reget=1'>get new stat</a>
				<br><br>
				<a href='./WorldUsers.php'>BACK</a>
				";
		}
	}
	
	static private function _getCSV()
	{
		$users = self::_getStat();
			
		$out = '';
		
		$out .= '"user name"; "registration date"; "combs"; "central gate"; "central gate"; "user score"; "alliance"; "same IP"; "same cookies";'."\n";
		
		foreach ($users as $k => $user)
		{
			$out .= 
			'"'.$user['username'].'"; '.
			'"'.$user['registrationdate'].'"; '.
			'"'.$user['combs'].'"; '.
			'"'.$user['central_gate'].'"; '.
			'"'.$user['userscore'].'"; '.
			'"'.$user['useralliance'].'"; '.
			'"'.$user['count_trades'].'"; '.
			'"'.$user['count_ip'].'"; '.
			'"'.$user['count_cookie'].'"; ';
			$out .= "\n";
		}
			
		self::_packCSV($out);		
		
		if(file_exists(self::$file))
		{
			/*Header("Content-Type: application/x-gzip");		
			Header("Content-Length: ".filesize(self::$file));		
			$fp = fopen(self::$file, "r");		
			fpassthru($fp);
			fclose($fp);
			*/
			$downloadFile = './files/WorldUsers'."_".self::$dbID."_".self::$world.".gz";
			
			echo "<br><a href='".$downloadFile."'>Download file</a>";
		} else {
			Header("Content-Type: text/csv");
			echo $out;
		}
	}
	
	static private function _packCSV($data)
	{
		if(file_exists(self::$file))
		{
			unlink(self::$file);
		}
		$zp = gzopen(self::$file, "w9");
		if(!$zp)
		{
			return false;
		}
		gzwrite($zp, $data);
		gzclose($zp);
	}
	
	
	static private function _showIndex()
	{
		$worldList = self::_getWorldList();
		
		$out = '<html>
				<HEAD>
				<title>World Users on '.$_SERVER['SERVER_NAME'].'</title>
				</head>
				<body>';
		$out .= "<h1>World User List</h1>";
		
		$out .= "<form action='' method='POST'><select name='world'>";
		
		foreach ($worldList as $k => $world)
		{
			$out .= "<option value=".$world['dbid']."_".$world['id_world'].">".$world['worldname'];
		}
		
		$out .= "</select>&nbsp;&nbsp;&nbsp;<input type=submit value=GET></form>";
		
		echo $out;
	}
	
	static private function _getWorldList()
	{
		self::$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				id_world,
				worldname,
				dbname,
				'-1' as dbId
			FROM
				ds_worldcollection
			WHERE
				world_identifier != 'UC'
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		foreach ($res as $k => $world)
		{
			foreach ($GLOBALS['dbWorldAccess'] as $dbID => $db)
			{
				if($world['dbname'] == $db['server'])
				{
					$res[$k]['dbid'] = $dbID;
				}
			}
		}
		
		return $res;
	}
		
	static private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('world', $data))
		{
			list(self::$dbID, self::$world) = explode('_', $data['world']);
			settype(self::$dbID, 'int');
			settype(self::$world, 'int');
			
			assert(self::$dbID >= 0);
			assert(self::$world > 0);
			
			self::$action = 'get_users';
		}
		
		if(key_exists('reget', $data))
		{
			self::$getStat = true;
		}
	}
	
	static private function _getStat()
	{
		
		$common = DS_Common::CreateForCurentDB(self::$dbID);
		
		$sql = "
set STATEMENT_TIMEOUT to 0;

SELECT
    DISTINCT u.username,
    
    ui.id_userinstance,
    
    u.registrationdate,
    
    --lh.lastvisit,

    (
        SELECT
            array_to_string(
                array
                (
                    SELECT
                        --cn.combname||' ('||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber||')'
                        r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
                    FROM
                        ds_comb AS c
                        INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                        INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                        INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
                    WHERE
                        c.id_userinstance = ui.id_userinstance
                ), ', '
            )
    ) as combs,

    COALESCE(al.alliancetag, '') as useralliance,

    COALESCE(fr.thefinal, 0) as userscore,
    
    COALESCE
    (
        (
        SELECT
            CASE
                WHEN ab.active THEN 'active'
                ELSE 'close'
            END
        FROM
            ds_attachedbuilding AS ab
            INNER JOIN ds_comb AS c ON (c.id_comb = ab.id_comb)
            INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
            INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
        WHERE
            NOT r.ringnumber = 4
            AND
            c.id_userinstance = ui.id_userinstance
            AND
            ab.id_building = 6
        ),
        'not open'
    ) as central_gate,

    (
        SELECT
            count(*)
        FROM
            ds_tradehistory as lh
        WHERE
            lh.id_seller = ui.id_userinstance
            OR
            lh.id_customer = ui.id_userinstance
    ) as count_trades  
    
FROM
    ds_userinstance AS ui
    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
    --LEFT JOIN ds_loginhistory AS lh ON (lh.id_user = u.id_user AND lh.lastvisit = (SELECT min(lastvisit) FROM ds_loginhistory WHERE id_user = u.id_user))
    LEFT JOIN ds_userhasalliance AS uha ON (uha.id_userinstance = ui.id_userinstance)
    LEFT JOIN ds_alliance AS al ON (al.id_alliance = uha.id_alliance)
    LEFT JOIN ds_finalranking AS fr ON (fr.id_userinstance = ui.id_userinstance)
WHERE
    ui.id_userinstance > 0
    AND
    ui.id_world = ".self::$world."
";

		$common->setTimeout(0);

echo '==========================================================================================';
echo "<br>\n";
flush();
		$res = $common->getResultArray("", $sql, "", "", "");
		if(!empty($common->errormsg))
		{
			echo $common->errormsg;
			exit;
		}

		foreach($res as $k => $user)
		{
			$sql = "
            SELECT  
            	count(DISTINCT th.trade_date)
            FROM
                ds_tradehistory as th

                INNER JOIN ds_userinstance AS ui_s ON (ui_s.id_userinstance = th.id_seller)
                INNER JOIN ds_user AS u_s ON (u_s.id_user = ui_s.id_user)
                INNER JOIN ds_loginhistory AS lh_s ON (lh_s.id_user = u_s.id_user)

                INNER JOIN ds_userinstance AS ui_c ON (ui_c.id_userinstance = th.id_customer)
                INNER JOIN ds_user AS u_c ON (u_c.id_user = ui_c.id_user)
                INNER JOIN ds_loginhistory AS lh_c ON (lh_c.id_user = u_c.id_user)
            WHERE
                (
                    th.id_seller = ".$user['id_userinstance']."
                    OR
                    th.id_customer = ".$user['id_userinstance']."
                )
                AND
                lh_s.lastvisit < th.trade_date
                AND
                lh_s.lastvisit > th.trade_date - '2 DAY'::interval
                AND
                lh_c.lastvisit < th.trade_date
                AND
                lh_c.lastvisit > th.trade_date - '2 DAY'::interval
                AND
                lh_s.ip = lh_c.ip
		";

			$resIP = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			$res[$k]['count_ip'] = $resIP[0]['count'];
//echo $resIP[0]['count']."; ";

			$sql = "
            SELECT  
            	count(DISTINCT th.trade_date)
            FROM
                ds_tradehistory as th

                INNER JOIN ds_userinstance AS ui_s ON (ui_s.id_userinstance = th.id_seller)
                INNER JOIN ds_user AS u_s ON (u_s.id_user = ui_s.id_user)
                INNER JOIN ds_loginhistory AS lh_s ON (lh_s.id_user = u_s.id_user)

                INNER JOIN ds_userinstance AS ui_c ON (ui_c.id_userinstance = th.id_customer)
                INNER JOIN ds_user AS u_c ON (u_c.id_user = ui_c.id_user)
                INNER JOIN ds_loginhistory AS lh_c ON (lh_c.id_user = u_c.id_user)
            WHERE
                (
                    th.id_seller = ".$user['id_userinstance']."
                    OR
                    th.id_customer = ".$user['id_userinstance']."
                )
                AND
                lh_s.lastvisit < th.trade_date
                AND
                lh_s.lastvisit > th.trade_date - '2 DAY'::interval
                AND
                lh_c.lastvisit < th.trade_date
                AND
                lh_c.lastvisit > th.trade_date - '2 DAY'::interval
                AND
                lh_s.id_cookie = lh_c.id_cookie
		";
echo ".";
if(($k % 100) == 0)
{
	echo "<br>\n";
}
flush();
			$resIP = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			$res[$k]['count_cookie'] = $resIP[0]['count'];
			
//echo $resIP[0]['count'];
//echo "\n";
		}

//echo "</pre>";

		return $res;
	}
}

?>