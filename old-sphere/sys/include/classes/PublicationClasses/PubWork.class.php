<?
class PubWork extends PubManager{

	var $tplNames = array(
	//additional services
	'addservrusalert' => 'add_serv/add_serv_rus_alert.tpl.html',
	'addservrus' => 'add_serv/add_serv_rus.tpl.html',
	'addservukralert' => 'add_serv/add_serv_ukr_alert.tpl.html',
	'addservukr' => 'add_serv/add_serv_ukr.tpl.html',
	'addservazralert' => 'add_serv/add_serv_azr_alert.tpl.html',
	'addservazr' => 'add_serv/add_serv_azr.tpl.html',
	'addservbelalert' => 'add_serv/add_serv_bel_alert.tpl.html',
	'addservbel' => 'add_serv/add_serv_bel.tpl.html',
	'addserveuralert' => 'add_serv/add_serv_eur_alert.tpl.html',
	'addserveur' => 'add_serv/add_serv_eur.tpl.html',
	'addservizralert' => 'add_serv/add_serv_izr_alert.tpl.html',
	'addservizr' => 'add_serv/add_serv_izr.tpl.html',
	'addservmolalert' => 'add_serv/add_serv_mol_alert.tpl.html',
	'addservmol' => 'add_serv/add_serv_mol.tpl.html',
	'addservbalalert' => 'add_serv/add_serv_bal_alert.tpl.html',
	'addservbal' => 'add_serv/add_serv_bal.tpl.html',
	'addservmoscowalert' => 'add_serv/add_serv_moscow_alert.tpl.html',
	'addservmoscow' => 'add_serv/add_serv_moscow.tpl.html',
	'addservtadalert' => 'add_serv/add_serv_tad_alert.tpl.html',
	'addservtad' => 'add_serv/add_serv_tad.tpl.html',
	//
	'alert' => 'news_anons.tpl.html',
	'top' => 'news_top.tpl.html',
	'aliancepage' => 'aliance_page.tpl.html',
	'dealpage' => 'dealmakers_page.tpl.html',
	'comradepage' => 'comrade_page.tpl.html',
	'peoplepage' => 'people_page.tpl.html',
	'weekalert' => 'news_list.tpl.html',
	'allList' => 'news_list.tpl.html',
	'pubList' => 'news_out.tpl.html',
	'tournnew' => 'tourn_news_top.tpl.html',
	'tournlast' => 'tourn_news_top.tpl.html',
	'smsnews' => 'sms/sms_news.tpl.html',
	'linkusShow' => 'linkus_show.tpl.html',
	'linkus' => 'linkus.tpl.html'
	);

	var $links = array (
	'mainUrl' => ''
	);

	function PubWork($db, $tpl, $pageManager, $pubType){
		$this->Object('PubWork');
		$this->PubManager($pubType, $db, $tpl, $GLOBALS['systemParameters'], $pageManager);
	}

	function setMode($mode){
		$this->param->set('pubMode', $mode);
	}

	function setOrder($order){
		$this->param->set('order', $order);
	}

	function process(){
		$pubId = $this->param->get('pubId');
		$pubAction = $this->param->get('pubAction');
		$pubMode = $this->param->get('pubMode');
		$order = $this->param->get('order');

		switch ($pubMode){
			case 'addservrusalert':
			return $this->showAddServRusAlert(99);
			case 'addservrus':
			return $this->showAddServRus();
			case 'addservukralert':
			return $this->showAddServUkrAlert(99);
			case 'addservukr':
			return $this->showAddServUkr();
			case 'addservazralert':
			return $this->showAddServAzrAlert(99);
			case 'addservazr':
			return $this->showAddServAzr();
			case 'addservbelalert':
			return $this->showAddServBelAlert(99);
			case 'addservbel':
			return $this->showAddServBel();
			case 'addserveuralert':
			return $this->showAddServEurAlert(99);
			case 'addserveur':
			return $this->showAddServEur();
			case 'addservizralert':
			return $this->showAddServIzrAlert(99);
			case 'addservizr':
			return $this->showAddServIzr();
			case 'addservmolalert':
			return $this->showAddServMolAlert(99);
			case 'addservmol':
			return $this->showAddServMol();
			case 'addservbalalert':
			return $this->showAddServBalAlert(99);
			case 'addservbal':
			return $this->showAddServBal();
			case 'addservmoscowalert':
			return $this->showAddServMoscowAlert(99);
			case 'addservmoscow':
			return $this->showAddServMoscow();
			case 'addservtadalert':
			return $this->showAddServTadAlert(99);
			case 'addservtad':
			return $this->showAddServTad();
			//*****************************************
			case 'alert':
			return $this->showAlert(3);
			case 'tournnew':
			return $this->showTournNew(1);
			case 'tournlast':
			return $this->showTournLast(1);
			case 'top':
			return $this->showTop(1);
			case 'aliancepage':
			return $this->showAliancePage(250);
			case 'dealpage':
			return $this->showDealPage(25);
			case 'linkus':
			return $this->showLinkUs(25);
			case 'linkusShow':
			return $this->LinkUsShowPub();
			case 'comradepage':
			return $this->showComradePage(10);
			case 'peoplepage':
			return $this->showPeoplePage(50);
			case 'smsnews':
			return $this->showSMSNews(1);
			case 'showPub':
			return $this->showPub();
			case 'showList':
			return $this->showAllLIst(10);
			case 'archive':
			return $this->showArc(10);
			default:
			return $this->showAllLIst(10);
		}
	}
	
//****************************************************************************

	function showAddServRusAlert($count){
		return $this->listAll($this->tplNames['addservrusalert'], $count);
	}

	function showAddServRus($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservrus']);
	}

	function showAddServUkrAlert($count){
		return $this->listAll($this->tplNames['addservukralert'], $count);
	}

	function showAddServUkr($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservukr']);
	}

	function showAddServAzrAlert($count){
		return $this->listAll($this->tplNames['addservazralert'], $count);
	}

	function showAddServAzr($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservazr']);
	}

	function showAddServBelAlert($count){
		return $this->listAll($this->tplNames['addservbelalert'], $count);
	}

	function showAddServBel($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservbel']);
	}

	function showAddServEurAlert($count){
		return $this->listAll($this->tplNames['addserveuralert'], $count);
	}

	function showAddServEur($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addserveur']);
	}

	function showAddServIzrAlert($count){
		return $this->listAll($this->tplNames['addservizralert'], $count);
	}

	function showAddServIzr($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservizr']);
	}

	function showAddServMolAlert($count){
		return $this->listAll($this->tplNames['addservmolalert'], $count);
	}

	function showAddServMol($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservmol']);
	}

	function showAddServBalAlert($count){
		return $this->listAll($this->tplNames['addservbalalert'], $count);
	}

	function showAddServBal($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservbal']);
	}

	function showAddServMoscowAlert($count){
		return $this->listAll($this->tplNames['addservmoscowalert'], $count);
	}

	function showAddServMoscow($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservmoscow']);
	}
	
	function showAddServTadAlert($count){
		return $this->listAll($this->tplNames['addservtadalert'], $count);
	}

	function showAddServTad($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['addservtad']);
	}
	
//****************************************************************************
	
	function showAlert($count){
		return $this->listAll($this->tplNames['alert'], $count);
	}

	function showTop($count){
		return $this->listAll($this->tplNames['top'], $count);
	}

	function showTournNew($count){
		return $this->listAll($this->tplNames['tournnew'], $count);
	}

	function showTournLast($count){
		return $this->listAll($this->tplNames['tournlast'], $count);
	}

	function showComradePage($perPage){
		$this->productsPerPage = $perPage;
		return $this->listAll($this->tplNames['comradepage']);
	}

	function showLinkUs($perPage){
		$this->productsPerPage = $perPage;
		return $this->listAll($this->tplNames['linkus']);
	}

	function showPeoplePage($count){
		return $this->listAll($this->tplNames['peoplepage'], $count);
	}

	function showSMSNews($count){
		return $this->listAll($this->tplNames['smsnews'], $count);
	}

	function showDealPage($count){
		return $this->listAll($this->tplNames['dealpage'], $count);
	}

	function showAliancePage($count){
		return $this->listAll($this->tplNames['aliancepage'], $count);
	}

	function showAllLIst($perPage){
		$this->productsPerPage = $perPage;
		return $this->listAll($this->tplNames['allList']);
	}

	function LinkUsShowPub($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['linkusShow']);
	}

	function showPub($pubId=''){
		if(!$pubId){
			$pubId = $this->param->get('pubId');
		}
		return $this->listProduct($pubId, $this->tplNames['pubList']);
	}

	function showArc($perPage){
		$yearPub = $this->param->get('yearPub');
		$monthPub = $this->param->get('monthPub');

		if($yearPub == 'NULL'){
			$yearPub = date('Y');
		}

		if($monthPub == 'NULL'){
			$monthPub = date('m');
		}

		$this->productsPerPage = $perPage;
		return $this->listAll($this->tplNames['allList'], '', $yearPub, $monthPub, true);
	}
}
?>