<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2011/04/20 13:58:50 $';

require_once('Main.php');

$common = DS_Common::CreateForCurentDB(0);

$sql = "
						SELECT
							w.world_identifier,
                            w.worldname
						FROM
							ds_worldcollection w
                        WHERE 
                        	w.world_status != 5
                        ORDER BY 
                        	w.sort_order DESC";
$worlds = $common->getResultArray("WORLD_LIST", $sql, "", "", "GLOBAL_CACHE");
assert(empty($common->errormsg));

$str = '
	<WORLDLIST>';
foreach ($worlds as $key => $data)
{
$str .= '
		<wr name="'.$data['worldname'].'" SIGN="'.$data['world_identifier'].'" />';
}
$str .= '
	</WORLDLIST>';

echo iconv('cp1251', 'utf8', $str);
?>