<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс типа торгового лота
 * определяет все характеристики данного типа торговли
 */

class DE_TradeType extends DE_Base
{	
	
	private $tradeTypeID;
	
	private $worldID;
	
	private $tradeOnCategoryID;

	private $tradeOnTypeID;
	
	private $fromArticle;
	
	private $tradeByCategoryID;
	
	private $tradeByTypeID;
	
	private $toArticle;	
	
	private $isAllowed;
	
	private $maxPriceDelta;
	
	private $minPriceDelta;
	
	private $tax;
	
	public function __construct($id, $data = '')
	{
		assert(!empty($id));		
		parent::__construct($id);
		
		$partsOfID = explode('_', $id);
		
		assert(key_exists(0, $partsOfID));
		$this->tradeTypeID = (int)$partsOfID[0];
		assert($this->tradeTypeID > 0);
		
		assert(key_exists(1, $partsOfID));
		$this->worldID = (int)$partsOfID[1];
		assert($this->worldID > 0);
				
		if(!is_array($data))
		{
			$data = DS_Trade::TradeTypeDataByID($this->tradeTypeID, $this->worldID);
		}
		
		assert(is_array($data) && count($data) > 0);
		
		assert(key_exists('id_trade_on_category', $data));
		$this->tradeOnCategoryID = (int)$data['id_trade_on_category'];
		assert($this->tradeOnCategoryID > 0);
		
		assert(key_exists('id_trade_on_type', $data));
		$this->tradeOnTypeID = (int)$data['id_trade_on_type'];
		assert($this->tradeOnTypeID > 0);
		
		assert(key_exists('id_trade_by_category', $data));
		$this->tradeByCategoryID = (int)$data['id_trade_by_category'];
		assert($this->tradeByCategoryID > 0);

		assert(key_exists('id_trade_by_type', $data));
		$this->tradeByTypeID = (int)$data['id_trade_by_type'];
		assert($this->tradeByTypeID > 0);		
		
		assert(key_exists('is_allowed', $data));
		$this->isAllowed = $data['is_allowed'];
		if($data['is_allowed'] == 't')
		{
			$this->isAllowed = true;
		}else{
			$this->isAllowed = false;
		}					
		
		assert(key_exists('max_price_delta', $data));
		$this->maxPriceDelta = (float)$data['max_price_delta'];
		assert($this->maxPriceDelta >= 0);
		
		assert(key_exists('min_price_delta', $data));
		$this->minPriceDelta = (float)$data['min_price_delta'];
		assert($this->minPriceDelta < 0 && $this->minPriceDelta <= 100);
		
		assert(key_exists('tax', $data));
		$this->tax = (float)$data['tax'];
		assert($this->tax >= 0);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $id
	 * @return DE_TradeType
	 */
	static public function CreateFromID($id)
	{
		assert(!empty($id) && strpos($id, "_"));			
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, '', '');
	}	
	
	/**
	 * Enter description here...
	 *
	 * @param integer $typeID
	 * @return DE_TradeType
	 */
	static public function CreateFromTypeIDForCurrentWorld($typeID)
	{
		assert(is_int($typeID) && $typeID > 0);
		
		$world = DE_World::CreateForCurrentWorld();
		
		return self::CreateFromID($typeID."_".$world->getID());
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_TradeArticle $fromTradeArticle
	 * @param DE_TradeArticle $toTradeArticle
	 * @param DE_World $world
	 * @return DE_TradeType
	 */
	static public function CreateFromTradeArticlesAndWorld(DE_TradeArticle $fromTradeArticle, DE_TradeArticle $toTradeArticle, DE_World $world)
	{
		$tradeTypeData = DS_Trade::GetTradeTypeByTradeArticlesAndWorld($fromTradeArticle, $toTradeArticle, $world);
		assert(is_array($tradeTypeData));

		if(count($tradeTypeData) == 0)
		{
			return false;
		}
		
		assert(key_exists('id_tradetype', $tradeTypeData));
		$typeID = (int)$tradeTypeData['id_tradetype'];
		assert($typeID > 0);		
		
		$tradeType = GB_SingletonObjectStorage::GetSingleton(__CLASS__, $typeID."_".$world->getID(), $tradeTypeData, '');
		$tradeType->setFromTradeArticle($fromTradeArticle);
		
		return $tradeType;
	}
	
	
	public function isAllowed()
	{
		return $this->isAllowed;
	}
	
	public function getMaxPriceDelta()
	{
		return $this->maxPriceDelta;
	}
	
	public function getMinPriceDelta()
	{
		return $this->minPriceDelta;
	}
	
	public function getTradeFromCategoryID()
	{
		return $this->tradeOnCategoryID;
	}
	
	public function getTradeToCategoryID()
	{
		return $this->tradeByCategoryID;
	}
	
	public function getTradeToTypeID()
	{
		return $this->tradeByTypeID;
	}
	
	public function getTradeFromTypeID()
	{
		return $this->tradeOnTypeID;
	}
	
	public function getTradeTypeID()
	{
		return $this->tradeTypeID;
	}
	
	
	/**
	 * Enter description here...
	 *
	 * @return DE_TradeArticle
	 */
	public function getFromArticle()
	{
		if(!($this->fromArticle instanceof DE_TradeArticle))
		{
			$this->fromArticle = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld($this->getTradeFromCategoryID(), $this->getTradeFromTypeID());
		}
		
		return $this->fromArticle;
	}
		
	public function setFromTradeArticle(DE_TradeArticle $article)
	{
		$this->fromArticle = $article;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_TradeArticle
	 */
	public function getToArticle()
	{
		if(is_null($this->toArticle))
		{
			$this->toArticle = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld($this->getTradeToCategoryID(), $this->tradeByTypeID);
		}
		
		return $this->toArticle;
	}
	
	public function getTax()
	{
		return $this->tax;
	}		
}

?>