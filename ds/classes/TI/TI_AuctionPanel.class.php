<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_AuctionPanel extends TI_BaseMenu
{
	public $pageNumber;

	public $sortPlace = array();
	
	public $sortType = array();
	
	public $sortTypeNamesTmp = array();
	
	public $trades = array();
	
	public $tradeBy = array();
	
	public $currentPage = 1;
	
	public $totalPages = 1;
	
	public $tradesPerPage = 1;
	
	public $isOwnTrades = 0;
	
	public $isOwnBets = 0;
	
	public $noTrade = 1;
	
	public $order = '';
	
	public $personal = 0;
	
	public $trader = '';
	
	public $filters = array();
	
	public $listSortings = array();
	
	public $currentSortings = array();
	
	public function __construct($buildingID, $tabName)
	{
		parent::__construct($buildingID, $tabName);								
	}	
	
	public function setPageNumber($pageNumber)
	{
		assert(is_int($pageNumber) && $pageNumber >= 0);
		
		$this->pageNumber = $pageNumber;
	}
	
	public function setSortPlace($placeName, $isCurrent = 0)
	{
		assert(is_string($placeName) && !empty($placeName));
		assert(is_int($isCurrent) && ($isCurrent == 0 || $isCurrent == 1));
		
		$this->sortPlace[] = array($placeName, $isCurrent);
	}
	
	public function setTradeBy($tradeByName, $isCurrent)
	{
		assert(!empty($tradeByName));
		assert(is_int($isCurrent) && ($isCurrent == 0 || $isCurrent == 1));
		$this->tradeBy[] = array($tradeByName, $isCurrent);
	}
	
	public function setSortTypeNameTmp($sortName, $isCurrent)
	{
		assert(is_string($sortName) && !empty($sortName));
		assert(is_int($isCurrent) && ($isCurrent == 0 || $isCurrent == 1));
		
		$this->sortTypeNamesTmp[] = array($sortName, $isCurrent);
	}
	
	public function amountSortTypeNameTo($sortName, $isCurrent)
	{
		assert(is_string($sortName) && !empty($sortName));
		assert(is_int($isCurrent) && ($isCurrent == 0 || $isCurrent == 1));
		
		assert(count($this->sortTypeNamesTmp) > 0);
		$this->sortType[] = array($sortName, $isCurrent, $this->sortTypeNamesTmp);
		
		$this->sortTypeNamesTmp = array();	
	}
	
	public function setTrade($tradeData)
	{
		assert(is_array($tradeData) && count($tradeData) > 0);
		
		$this->trades[] = $tradeData;
	}
	
	public function setPages($currentPage, $totalPages, $tradesPerPage)
	{
		assert(is_int($currentPage) && $currentPage > 0);
		assert(is_int($totalPages));
		assert(is_int($tradesPerPage) && $tradesPerPage > 0);
		
		$this->currentPage = $currentPage;
		$this->totalPages = $totalPages;
		$this->tradesPerPage = $tradesPerPage;
	}
	
	public function setIsOwnBets($val)
	{
		assert(is_int($val) && ($val == 1 || $val == 0));
		
		$this->isOwnBets = $val;
	}
	
	public function setIsOwnTrades($val)
	{
		assert(is_int($val) && ($val == 1 || $val == 0));
		
		$this->isOwnTrades = $val;
	}
	
	public function setOrder($order)
	{
		$this->order = $order;		
	}
	
	public function setPersonal($personal)
	{
		$this->personal = $personal;		
	}
	
	public function setTrader($trader)
	{
		$this->trader = $trader;		
	}
		
	public function setNoTrade($val)
	{
		assert(is_int($val) && ($val == 1 || $val == 0));
		
		$this->noTrade = $val;
	}
	
	public function setFilter($name, $id, $val)
	{
		assert(!empty($name));
		assert(is_int($id) && $id > 0);
		assert(is_int($val) && $val >= 0);
		
		$filter = array('NAME' => $name, 'ID' => $id, 'VALUE' => $val);
				
		$this->filters[] = $filter;
	}
	
	public function setSorting($name, $id, $num, $dir)
	{
		$allowedDirs = array('D', 'U');
		
		assert(!empty($name));
		assert(is_int($id) && $id > 0);
		assert(is_int($num) && $num >= 0);
		assert(in_array($dir, $allowedDirs));
			
		$this->listSortings[] = array('NAME' => $name, 'ID' => $id);
		
		if($num > 0)
		{
			$this->currentSortings[] = array('NUM' => $num, 'ID' => $id, 'DIR' => $dir);
		}
	}
}

?>