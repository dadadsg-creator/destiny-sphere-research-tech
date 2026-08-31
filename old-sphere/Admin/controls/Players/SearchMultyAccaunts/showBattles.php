<? 
$world_only=checkDir();

if(!isset($_GET['dbid']) || !isset($_GET['user']) || !isset($_GET['world']))

	{header('Location: check_multi.php'); exit; }



$user = $_GET['user'];

$world = $_GET['world'];



if($world === "") {echo "World must be specified!!"; exit;}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

$log_str = "Viewing battles: ";

$common = & ClassFactory::getInstance ( "DS_Common" );
	
if($world_only != 'all')
{
	foreach ($GLOBALS['dbWorldAccess'] as $k=>$v)
	{
		if(strcmp($v['server'],$world_only) === 0)
		{
			if (!$common->setDbId($k)) 
			{
				echo "DB-connection failed to ".$k; exit;
			}
			$log_str .= " in db ".$k;
		}
	}
}
else 
{
	if (!$common->setDbId($_GET['dbid'])) 
	{
		echo "DB-connection failed to ".$_GET['dbid']; exit;
	}
	$log_str .= " in db ".$_GET['dbid'];
}


$sql = "select b.id_battle, username, cn.combname, participantflag, iswinner, battlestartdate, battleenddate
	from
	ds_userinstance ui, ds_user u, ds_battle b, ds_battleparticipant bp,
	ds_comb c, ds_combname cn
	where
	c.id_comb = cn.id_comb and
	b.id_battlecomb = c.id_comb and
	u.id_user = ui.id_user and
	bp.id_userinstance = ui.id_userinstance and
	b.id_battle = bp.id_battle and
  b.id_battle in (select b2.id_battle from 
  ds_battle b2, ds_battleparticipant bp2, ds_user u2,
  ds_userinstance ui2 where
  u2.id_user = ui2.id_user and b2.id_battle = bp2.id_battle and
  bp2.id_userinstance = ui2.id_userinstance and
	u2.username = '" . $_GET["user"] . "' and
  ui2.id_world = " . $_GET["world"] . ")
	order by b.id_battle DESC, participantflag  ASC";

$log_str .= " for user ".$_GET['user'];
$log_str .= " in world with id ".$_GET['world'];
actionLog($log_str);

$battles = $common->getResultArray ( "battles".$user."_$world",$sql, "", "", "NO_CACHE" );

if ( $battles === false ) {

    echo "Unable to get battles for user $user ".pg_last_error(); exit; }

if ( !$battles) {echo "No battles were found for user $user"; exit; }?>

<html><head><title>Users battles history</title></head>

<body bgcolor="white"><center><big><?=$user?></big></center>

 <table width="100%" border="1" cellspacing="0" cellpadding="0">

 <tr><td><b>Attacker</b></td><td><b>Defender</b></td><td><b>Comb</b></td><td><b>Won?</b></td><td><b>Start</b></td><td><b>End</b></td></tr>
 
<?

	$oldBattleID = "";
	$data[] = "";
	
	foreach ($battles as $k=>$v ) {

	 $battleID = $v['id_battle'];

	 if ($battleID == $oldBattleID) 
	 {	 	 	 	 
	 	  $data[1] = "<a href='".$_SERVER['PHP_SELF'].
	 	 			"?dbid=".$_GET['dbid'].
	 	 			"&world=".$_GET['world'].
	 	 			"&user=".$v['username'].
	 	 			"' >".
	 	 			$v['username'].
	 	 			"</a>";
	 	 
	 	 echo "<tr>";
	 	 for ($i=0; $i<=5; $i++)
	 	 {
	 	   echo "<td>" . $data[$i] . "</td>";
		 }
		 echo "</tr>";
	 }
	 else
	 {	 	
	 	 $data[0] = "<a href='".$_SERVER['PHP_SELF'].
	 	 			"?dbid=".$_GET['dbid'].
	 	 			"&world=".$_GET['world'].
	 	 			"&user=".$v['username'].
	 	 			"' >".
	 	 			$v['username'].
	 	 			"</a>";
	 	 $data[2] = $v['combname'];
	 	 if ($v['iswinner'] == 't') {
	 	   $data[3] = "YES"; 
	 	 }
	 	 else {
	 	 	 $data[3] = "NO";
	 	 }
	 	 	
 	 	 $data[4] = preg_replace('/(.+)\..+/','$1',$v['battlestartdate']);
	   $data[5] = preg_replace('/(.+)\..+/','$1',$v['battleenddate']);				   
	  }
	  
	  $oldBattleID = $v['id_battle'];
	}
?>

</table><body></html>
<? 

function checkDir()
{
	if(preg_match('/\/[_a-zA-Z0-9]+\/showBattles\.php/',$_SERVER['PHP_SELF'],$matches) === 0)
	{
		echo 'wrong dir';
		exit;
	}
	if(strlen($matches[0]) < 17)
	{
		echo 'wrong dir';
		exit;
	}

	$dir_name = substr($matches[0],1,-16);
	@list($dir, $db_name) = explode('_',$dir_name);
	if(strcmp($dir,'SearchMultyAccaunts') != 0)
	{
		echo 'wrong dir ';
		exit;
	}

	if(isset($db_name))
	{
		return $db_name;
	}
	
	return 'all';
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php"; ?>
