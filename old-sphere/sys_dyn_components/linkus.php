<?
    require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
    $inc = new IncludeManager();
    $inc->inc('publications');

    $db = new DBinterface (	constant('DB_HOST'),
    						constant('DB_USER'),
                            constant('DB_PASS'),
                            constant('DB_NAME'));

    $tpl = new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates');

    $pub =  new PubWork($db, $tpl, $this, 'linkus');
    $pub->links['mainUrl'] = 'linkus';
	 $pub->setMode('linkus'); 
    echo $pub->process();
?>