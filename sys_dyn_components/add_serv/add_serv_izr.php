<?
    require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
    $inc = new IncludeManager();
    $inc->inc('publications');
    $inc->inc('lists');

    $db = new DBinterface (	constant('DB_HOST'),
    						constant('DB_USER'),
                            constant('DB_PASS'),
                            constant('DB_NAME'));

    $tpl = new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates');

    require_once("./sys_dyn_components/add_serv/add_serv_worlds.inc.php");
	$tpl->assign('WORLDS', $htmlWorldsList);
    
    $pub =  new PubWork($db, $tpl, $this, 'addservizr');
    $pub->links['mainUrl'] = 'add_serv_izr';
	$pub->setMode('addservizr');
	
    echo $pub->process();
?>