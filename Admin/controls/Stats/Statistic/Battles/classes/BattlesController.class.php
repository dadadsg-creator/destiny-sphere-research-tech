<?php

class BattlesStat
{
	private $action = 'index';
	
	private $wordls = array();
	
	private $dateFrom;
	
	private $dateTo;
	
	private $detail = '3';
	
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
		$worlds = BattlesSearch::GetWorldList();

		BattlesOut::OutIndex($worlds, $this->dateFromArr, $this->dateToArr, $this->worlds, $this->detail);
	}	
	
	private function _showStat()
	{
		$stat = BattlesSearch::GetStat($this->dateFrom, $this->dateTo, $this->worlds, $this->detail);
		$header = BattlesSearch::GetHeader($this->dateFrom, $this->dateTo, $this->worlds, $this->detail);
		
/*echo "<font color=white><pre>";
print_r($header);
echo "</pre>";
exit;*/		
		//$min_max = BattlesSearch::GetMaxValue($this->dateFrom, $this->dateTo, $this->detail);
		//$total = BattlesSearch::GetSum($this->dateFrom, $this->dateTo);
/*echo "<font color=white><pre>";
echo $total;
echo "</pre>";
exit;*/
			BattlesOut::OutStat($stat, $header);			
	}
	
	private function _showStatFrom()
	{
		$tempArr = explode('-', $this->worldAndDbId);
		$worldAndDbId = $tempArr[0];
		$world = $tempArr[1];

		$stat = TransferStatSearch::GetTransferStatFrom( $_SESSION['dateFrom'], $_SESSION['dateTo'], $worldAndDbId, $this->detail, $this->direction);
		$header= TransferStatSearch::GetHeaderFrom($_SESSION['dateFrom'], $_SESSION['dateTo'], $worldAndDbId, $this->detail, $this->direction);

		TransferStatOut::OutStatFrom($stat, $header, $world, $this->direction);
		
	}
	
	
	private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
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
		
		
		if (key_exists('detail', $data)) 
		{
			$this->detail = $data['detail'];
		}
	}
}

?>