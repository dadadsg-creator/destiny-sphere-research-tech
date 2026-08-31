<?
/*
require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
$inc = new IncludeManager();
$inc->inc('publications');

$db = new DBinterface (	constant('DB_HOST'),
constant('DB_USER'),
constant('DB_PASS'),
constant('DB_NAME'));

$tpl = new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates');
*/
/*
$pub =  new PubWork($db, $tpl, $this, 'aliancepages');
$pub->links['mainUrl'] = 'aliancepages';
$pub->setMode('aliancepage');
echo $pub->process();
*/



require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
$inc = new IncludeManager();
$inc->inc('publications');

$db = new DBinterface (	constant('DB_HOST'),
constant('DB_USER'),
constant('DB_PASS'),
constant('DB_NAME'));

$tpl = new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates');



$tpl->define( array( 'units' => '/Publication/aliance_page_row.tpl.html', 'total' => '/Publication/aliance_page_header.tpl.html' ) );
$tpl->defineDynamic('row', 'units');
$tpl->defineDynamic('total_row', 'total');



//$assign['PAGE_TITLE'] = "PAGE_TITLE";
//$assign['PAGE_HEADER'] = "HEADER";


$worls_array[] = "KASSIOPEA";
$worls_array[] = "OPION";
$worls_array[] = "VODOLEY";



$query = "SELECT * FROM Alliance_Pages as ap, Worlds as w WHERE ap.World = w.ID ORDER BY World";
$db->dbQuery($query);
$res = $db->dbResult();

foreach ($res as $value)
{

	$worlds[$value['World']] = array("ID"=>$value['World'], "Name"=>$value['Name'], "Image"=>$value['Image']);
	$rows[$value['World']][] = array(
	'Full_Name' => $value['Full_Name'],
	'Short_Name' => $value['Short_Name'],
	'Description' => $value['Description'],
	'Url' => $value['Url']
	);
}

foreach ($worlds as $val) {
		$assign['LOGO_NAME'] = $val['Image'];
		$assign['WORLD_NAME'] = $val['Name'];
	foreach ($rows[$val['ID']] as $value) {
		$assign['FULL_NAME'] = $value['Full_Name'];
		$assign['SMALL_NAME'] = $value['Short_Name'];
		$assign['URL'] = $value['Url'];
		$tpl->assign( $assign );
		$tpl->parse('ROW', '.row');
	}

	$tpl->parse('UNITS', 'units');
	$assign['TOTALS'] = $tpl->fetch('UNITS');
	$tpl->clear("ROW");
	$tpl->assign( $assign );
	$tpl->parse('TOTAL_ROW', '.total_row');
}

$tpl->parse('MAIN', 'total');

echo $tpl->fetch('MAIN');

?>