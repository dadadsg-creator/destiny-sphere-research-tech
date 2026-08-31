<?
    require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
    $inc = new IncludeManager();
    $inc->inc('publications');

    $db = new DBinterface (	constant('DB_HOST'),
    						constant('DB_USER'),
                            constant('DB_PASS'),
                            constant('DB_NAME'));

    $tpl = new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates');

    $pub =  new PubWork($db, $tpl, $this, 'addservazr');
    $pub->links['mainUrl'] = 'add_serv_azr';
	$pub->setMode('addservazralert');
	
	//$pub->setOrder(array("Descript ASC"));
	//$pub->setOrder('Descript ASC');
	 
    echo $pub->process();
?>