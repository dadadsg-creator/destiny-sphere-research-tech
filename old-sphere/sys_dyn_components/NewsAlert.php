<?
    require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
    $inc = new IncludeManager();
    $inc->inc('publications');

    $db = new DBinterface (	constant('DB_HOST'),
    						constant('DB_USER'),
                            constant('DB_PASS'),
                            constant('DB_NAME'));

    $tpl = new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates');

    $pub =  new PubWork($db, $tpl, $this, 'news');
//    $pub =  new PubWork($db, $tpl, $this, 'news\' and NewsDate > \''.date("Y-m-d H:m:s") );
    $pub->links['mainUrl'] = 'news';
	$pub->setMode('alert');
    echo $pub->process();
?>
