<?php $CVS_Version = '$Revision$ ; $Date$';
require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

define('COMPLEX_PER_PAGE', 60);

class SU_RingView
{
	private $rind = 1;
	
	private $worldID;
	
	private $common;
	
	private $offset = 0;
	
	private $uid;
	
	private $user;
	
	private $uiid;
	
	private $dbId = 0;
	
	private $msg = '';
	
	function __construct(array $data)
	{
		$this->_getParams($data);
		
		$this->_outComplexMap();
	}
	
	private function _getParams(array $data)
	{
		assert(key_exists('ring', $data));
		assert(key_exists('world', $data));
		
		$this->ring = (int)$data['ring'];
		$arr = explode('_', $data['world']);	
		$this->worldID = (int)$arr[1];
		$this->dbId = (int)$arr[0];
			
		assert($this->ring > 0 && $this->ring < 5);
		assert($this->worldID > 0);
		assert($this->dbId >= 0);
		
		SU_GameSessionWork::SessionContinue();
		
		if(GameDown($this->dbId, $this->worldID))
		{
			header("Location: index_down.html");			
			exit;
		}
		
		$this->common = DS_Common::CreateForCurentDB($this->dbId);		

		if(key_exists('offset', $data))
		{
			$this->offset = ((int) $data['offset']) - 1;
			if($this->offset < 0)
			{
				$this->offset = 0;				
			}
			//assert($this->offset >= 0);
			$maxComplex = $this->_getCountOfComplex();
			
			if($this->offset > $maxComplex - 1)
			{
				$this->offset = $maxComplex - 10;
			}
		}
	}

	private function _getComplexData()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
            SELECT
				u.username,
      			co.combscore,
      			co.id_comb,
      			cn.combname,
      			co.combnumber,
      			cc.complexnumber,
      			ri.ringnumber,
      			rc.username AS reserved_by,
      			rc.priority
			FROM
    			ds_comb AS co
            	LEFT JOIN ds_combname AS cn ON (co.id_comb = cn.id_comb)
              	INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
                INNER JOIN ds_ring AS ri ON (cc.id_ring = ri.id_ring)
				LEFT JOIN ds_userinstance AS ui ON (ui.id_userinstance = co.id_userinstance)
				LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)
				LEFT JOIN ds_reservedcomb AS rc ON (rc.id_comb = co.id_comb)
			WHERE
				ri.ringnumber = ".$this->ring."
				AND
				ri.id_world = ".$this->worldID."
			ORDER BY cc.complexnumber, co.combnumber
			OFFSET ".($this->offset*6)."
			LIMIT ".constant('COMPLEX_PER_PAGE')."
		";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		return $res;
	}
	
	private function _getCountOfComplex()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_combcomplex AS cc 
				INNER JOIN ds_ring As r ON (r.id_ring = cc.id_ring)
			WHERE
				r.id_world = ".$this->worldID."
				AND
				r.ringnumber = ".$this->ring."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "WORLD_CACHE");
		assert(empty($this->common->errormsg));
		
		return $res[0]['count'];
	}
	
	private function _outComplexMap()
	{
		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;
		
		$combComplexData = $this->_getComplexData();
		
		$complexNumber = $combComplexData[0]['complexnumber'];
		
		$combComplex = array();
		
		foreach($combComplexData as $k => $comb)
		{
			if($complexNumber != $comb['complexnumber'])
			{
				$complexNumber = $comb['complexnumber'];
			}
			$combComplex[$complexNumber][$comb['combnumber']] = $comb;
		}
		
		$tpl->assign('COMPLEX_DATA', $combComplex);
		$tpl->assign('COMPLEX_COUNT', $complexNumber + 1);
		$tpl->assign('OFFSET', $this->offset); 
		$tpl->assign('RING', $this->ring); 
		$tpl->assign('WORLD', $this->dbId.'_'.$this->worldID); 
		
		// подключение файла с локализацией
		include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/ring_view.lang";
		assert(is_array($lang));		
		$tpl->assign('lang', $lang);
		unset($lang);
		
		$tpl->display("ring_view.tpl");
	}
}
?>