<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_CalcRotation
{
	private $action = 'index';
	
	private $calcDate;
	
	private $calcEndDate;
	
	private $targetComb;
	
	private $startDate = false;
	
	private $comb;
	
	private $error = '';
	
	private $tpl;
	
	private $turnTime;
	
	private $prev_action;
	
	private $minFlyTime;
	
	public function __construct($data)
	{
		$this->_getParams($data);
		
		$this->_initTpl();
		
		switch ($this->action)
		{
			case 'index':
				$this->_outIndex();
				break;
			
			case 'get_min_time':
				$this->_outMinTime();
				break;
				
			case 'get_nearest_complex':
				$this->_outNearComplex();
				break;
				
			case 'times_to_complex':
				$this->_outTimesToComplex();
				break;
				
			default:
				break;
		}
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		/*
		$landingTime = DE_WorldSettings::CreateForCurentWorld()->get('RING_ROTATION_LANDING_TIME');
		
		$timeAddon = $landingTime * $turnTime;
		*/
		
		$this->turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		
		//$this->minFlyTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		
		if(key_exists('comb', $data))
		{
			$this->comb = DE_Comb::CreateFromID((int) $data['comb']);
		} else {
			assert(key_exists('comb', $_SESSION));
			$this->comb = DE_Comb::CreateFromID((int) $_SESSION['comb']);
		}
		
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
		
		if(key_exists('date_Y', $data) && key_exists('date_d', $data) && key_exists('date_m', $data) && key_exists('date_H', $data) && key_exists('date_i', $data))
		{
			$date = $data['date_d'].'-'.$data['date_m'].'-'.$data['date_Y'].' '.$data['date_H'].':'.$data['date_i'].':00';
			$this->calcDate = GB_TypeFactory::CreateBase('GB_Date', $date);
		} else {
			$this->calcDate = GB_TypeFactory::CreateBase('GB_Date', time());
		}
		
		if(key_exists('end_date_Y', $data) && key_exists('end_date_d', $data) && key_exists('end_date_m', $data) && key_exists('end_date_H', $data) && key_exists('end_date_i', $data))
		{
			$enddate = $data['end_date_d'].'-'.$data['end_date_m'].'-'.$data['end_date_Y'].' '.$data['end_date_H'].':'.$data['end_date_i'].':00';
			$this->calcEndDate = GB_TypeFactory::CreateBase('GB_Date', $enddate);
			if(($this->calcEndDate->value - $this->calcDate->value) < ($this->turnTime * 0.25))
			{
				$this->calcEndDate->value = $this->calcDate->value + $this->turnTime * 0.25;
			}
		} else {
			$this->calcEndDate = GB_TypeFactory::CreateBase('GB_Date', time() + $this->turnTime * 0.25);
		}
		
		if(key_exists('selected', $data))
		{
			$this->prev_action = (int)$data['selected'];
		}
		
		if(key_exists('ring', $data) && key_exists('complex', $data) && key_exists('t_comb', $data) && ($this->action == 'get_min_time' || $this->action == 'times_to_complex'))
		{
			$targetRing = (int) $data['ring'];
			if(!($targetRing > 0 && $targetRing <= 4))
			{
				$this->error = 'WRONG_TARGET_COMB';
				return;
			}
			
			if($targetRing == $this->comb->getRingNumber())
			{
				$this->error = 'WRONG_TARGET_COMB';
				return;
			}

			$targetComplex = (int) $data['complex'];
			if(!($targetComplex > 0 && $targetComplex < 5000))
			{
				$this->error = 'WRONG_TARGET_COMB';
				return;
			}

			$targetComb = (int) $data['t_comb'];
			if(!($targetComb > 0 && $targetComb <= 6))
			{
				$this->error = 'WRONG_TARGET_COMB';
				return;
			}
			
			assert(key_exists('world', $_SESSION));
			
			$this->targetComb = DE_Comb::CreateFromCoordinates((int)$_SESSION['world'], $targetRing, $targetComplex, $targetComb);
			
			if(($this->targetComb instanceof DE_CombNull) && !($this->targetComb instanceof DE_Comb))
			{
				$this->error = 'WRONG_TARGET_COMB';
				return;
			}
		}
	}
	
	private function _initTpl()
	{
		require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";
		
		// создаем объект смарти
		$this->tpl = new Smarty;
		$this->tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$this->tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$this->tpl->compile_check = true;
		$this->tpl->debugging = false;
		
		$lang = array();
		include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/ring_rotation.lang";
		$this->tpl->assign('lang', $lang);
		unset($lang);
	}
	
	private function _outIndex()
	{
		$rotation = DE_WorldSettings::CreateForCurentWorld()->get('RING_ROTATION');
		if(!$rotation)
		{
			$this->error = 'ROTATION_DISABLED';
		}
		
		$this->tpl->assign('MODE', 'INDEX');
		$this->tpl->assign('COMBS', $this->_getUserCombs());
		$this->tpl->assign('YEAR', (int)date('Y'));
		$this->tpl->assign('MONTH', (int)date('m'));
		$this->tpl->assign('DAY', (int)date('d'));
		$this->tpl->assign('HOUR', (int)date('H'));
		$this->tpl->assign('MAX_YEAR', (int)date('Y') + 2);
		$this->tpl->assign('PREV_ACTION', $this->prev_action);
		$this->tpl->assign('ERROR', $this->error);
		$this->tpl->display('ring_rotation.tpl');
	}
	
	private function _outMinTime()
	{
		$times = $this->_getMinTime();
		$this->tpl->assign('MODE', 'MIN_TIME');
		$this->tpl->assign('ERROR', $this->error);
		$this->tpl->assign('START_COMB', $this->comb->getFullName());
		$this->tpl->assign('FLY_TIME', $this->minFlyTime);
		if(empty($this->error))
		{
			$this->tpl->assign('END_COMB', $this->targetComb->getFullName());
		}
		$this->tpl->assign('TIMES', $times);
		$this->tpl->display('ring_rotation.tpl');
	}
	
	private function _outNearComplex()
	{	
		$complexes = $this->_getNearestComplex();
		$this->tpl->assign('MODE', 'NEAR_COMPLEX');
		$this->tpl->assign('ERROR', $this->error);
		$this->tpl->assign('COMPLEXES', $complexes);
		$this->tpl->assign('START_COMB', $this->comb->getFullName());
		$this->tpl->display('ring_rotation.tpl');
	}
	
	private function _outTimesToComplex()
	{
		$this->tpl->assign('MODE', 'TIMES_TO_COMPLEX');
		$this->tpl->assign('ERROR', $this->error);
		$this->tpl->assign('START_COMB', $this->comb->getFullName());
		if(empty($this->error))
		{
			$times = $this->_getTimesToComb();
			$this->tpl->assign('END_COMB', $this->targetComb->getFullName());
			$this->tpl->assign('TIMES', $times);
			$this->tpl->assign('TIMES', $times);
		}
		$this->tpl->display('ring_rotation.tpl');
	}
	
	private function _getMinTime()
	{
		if(!empty($this->error))
		{
			return false;
		}
		
		$this->startDate = GB_TypeFactory::CreateBase('GB_Date', time());
		
		$countPoints = 5;
		$times = array();
		
		// Время выбрано просто так
		$timeAddon = $this->turnTime * 0.5;
		
		for($i=0; $i<$countPoints; $i++)
		{
			$travelTime = GL_TravelTime::getMinTime($this->comb, $this->targetComb, $this->startDate);
			$time = GB_TypeFactory::CreateConstant('GB_Date', $travelTime + $this->startDate->value);
			$this->startDate->value += $travelTime + $timeAddon;
			$times[] = $time->getPGFormat();
		}
		
		//$this->startDate->value = time() + 
		$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);
		$travelTime = GL_TravelTime::Count($this->comb, $this->targetComb, $speed, $time);
		$this->minFlyTime = $travelTime->getDSFormat();
		
		return $times;
	}
	
	private function _getNearestComplex()
	{
		if(!empty($this->error))
		{
			return false;
		}
		
		$complexes = GL_TravelTime::getCurrentNearestCombs($this->comb);
		
		return $complexes;
	}
	
	private function _getTimesToComb()
	{
		$result = array();
		
		$countPoints = 15;
		
		$timeStep = ($this->calcEndDate->value - $this->calcDate->value) / ($countPoints - 1);
		
		$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);
		
		$startDate = $this->calcDate;

		for($i=0; $i<$countPoints; $i++)
		{
			$travelTime = GL_TravelTime::Count($this->comb, $this->targetComb, $speed, $startDate);
			$result[$i] = array(
				'date' => $startDate->getFormatedFull(),
				'time' => $travelTime->getDSFormat()
			);
			$startDate->value += $timeStep;
		}
		
		ksort($result);
		
		return $result;
	}
	
	private function _getUserCombs()
	{
		assert(key_exists('uiid', $_SESSION));
		assert(key_exists('world', $_SESSION));
		assert(key_exists('dbId', $_SESSION));
		
		$common = DS_Common::CreateForCurentDB();
		
		$sql = "
			SELECT
				c.id_comb,
				cn.combname,
				r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber as comb_coords
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_combname AS cn USING (id_comb)
			WHERE
				c.id_userinstance = ".(int) $_SESSION['uiid']."
				AND
				r.id_world = ".(int) $_SESSION['world']."
			ORDER BY r.ringnumber, cc.complexnumber, c.combnumber
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
}

?>