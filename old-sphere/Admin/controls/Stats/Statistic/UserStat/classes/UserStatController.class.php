<?php

class UserStat
{
	private $action = 'index';
	
	//private $wordls = array();
	
	private $dateFrom;
	
	private $detail;
	
	private $dateTo;
	
	private $dateFromArr = array();
	
	private $dateToArr = array();
	
	public function __construct(array $data)
	{
		$this->_getParams($data);

		switch ($this->action)
		{
			case 'index':
				$this->_showIndex();
				break;
			case 'showStat':				
				$this->_showIndex();
				$this->_showStat();
				break;
		}
	}
	
	private function _showIndex()
	{
		//$worlds = UserStatSearch::GetWorldList();

		//UserStatOut::OutIndex($worlds, $this->dateFromArr, $this->dateToArr, $this->worlds, , $this->detail);
		UserStatOut::OutIndex($this->dateFromArr, $this->dateToArr, $this->worlds, $this->detail);
	}	
	
	private function _showStat()
	{
		$stat = UserStatSearch::GetUresrStat($this->dateFrom, $this->dateTo, $this->detail);
		$min_max = UserStatSearch::GetMaxValue($this->dateFrom, $this->dateTo, $this->detail);
		$total = UserStatSearch::GetSum($this->dateFrom, $this->dateTo);

		UserStatOut::OutStat($stat, $min_max, $total);			
	}
	
	private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}

		/*if (key_exists('worlds', $data))
		{
			$this->worlds = $data['worlds'];
			if (!is_array($this->worlds))
			{
				exit('Error: wrong worlds');
			}
		}*/
		
		if(key_exists('dateFrom', $data))
		{	
			assert(is_array($data['dateFrom']));
			$dateFrom = $data['dateFrom'][0].'-';
			$dateFrom .= $data['dateFrom'][1].'-';
			$dateFrom .= $data['dateFrom'][2].' ';
			$dateFrom .= $data['dateFrom'][3].':00:00';
			
			$this->dateFromArr = $data['dateFrom'];
			$this->dateFrom = $dateFrom;

			$_SESSION['dateFrom'] = $this->dateFrom;
		}
		
		if(key_exists('dateTo', $data))
		{
			assert(is_array($data['dateTo']));
			$dateTo = $data['dateTo'][0].'-';
			$dateTo .= $data['dateTo'][1].'-';
			$dateTo .= $data['dateTo'][2].' ';
			$dateTo .= $data['dateTo'][3].':00:00';
			
			$this->dateToArr = $data['dateTo'];
			$this->dateTo = $dateTo;
			
			$_SESSION['dateTo'] = $this->dateTo;
		}
		
		if (key_exists('detail', $data)) 
		{
			$this->detail = $data['detail'];
		}
	}
}

?>