<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_AuctionTrades
{
	public $currentPage = 1;
	
	public $totalPages = 1;
	
	public $tradesPerPage = 1;
	
	public $trades = array();
	
	public function __construct()
	{
		//parent::__construct();								
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
	
	public function setTrade($tradeData)
	{
		assert(is_array($tradeData) && count($tradeData) > 0);
		
		$this->trades[] = $tradeData;
	}
}
 


?>