<?php

class TransferStat
{
	private $action = 'index';
	
	private $direction;
	
	private $wordls = array();
	
	private $detail;
	
	private $dateFrom;
	
	private $selected = array();
	
	private $dateTo;
	
	private $showDetail = false;
	
	private $worldAndBBId;
	
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
			case 'showFrom':
				$this->_showStatFrom();
				break;
		}
	}
	
	private function _showIndex()
	{
		$worlds = TransferStatSearch::GetWorldList();
		
		TransferStatOut::OutIndex($worlds, $this->dateFromArr, $this->dateToArr, $this->worlds, $this->showDetail, $this->detail);
	}	
	
	private function _showStat()
	{
		$stat = TransferStatSearch::GetTransferStat($this->dateFrom, $this->dateTo, $this->worlds, $this->detail, $this->showDetail);

		if ($this->showDetail == true) {
			$header = TransferStatSearch::GetHeader($this->dateFrom, $this->dateTo, $this->worlds, $this->detail);
			TransferStatOut::OutStatWithDetail($stat, $header, $this->detail);			
		}
		else {
			TransferStatOut::OutStat($stat);
		}
	}
	
	private function _showStatFrom()
	{
		$tempArr = explode('_', $this->worldAndDbId);
		$worldId = $tempArr[0];
		$DbId = $tempArr[1];
		$worldName = $tempArr[2];

		$stat = TransferStatSearch::GetTransferStatFrom( $_SESSION['dateFrom'], $_SESSION['dateTo'], $worldId."_".$DbId, $this->detail, $this->direction);
		$header= TransferStatSearch::GetHeaderFrom($_SESSION['dateFrom'], $_SESSION['dateTo'], $worldId."_".$DbId, $this->detail, $this->direction);

		TransferStatOut::OutStatFrom($stat, $header, $worldName, $this->direction);
		
	}
	
	
	private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}

		if (key_exists('direction', $data))
		{			
			$this->direction = $data['direction'];

			assert($this->direction == 'from' OR $this->direction == 'to');
		}

		if (key_exists('worlds', $data))
		{
			$this->worlds = $data['worlds'];
			if (!is_array($this->worlds))
			{
				exit('Error: wrong worlds');
			}
		}
		
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
		
		if(key_exists('showDetail', $data))
		{
			$this->showDetail = true;
		}
		
		if(key_exists('worldAndDbId', $data))
		{
			$this->worldAndDbId = $data['worldAndDbId'];

			if (empty($this->worldAndDbId))
			{
				exit('Error: wrong world!');
			}
		}
		
		if (key_exists('detail', $data)) 
		{
			$this->detail = $data['detail'];
		}
	}
}

?>