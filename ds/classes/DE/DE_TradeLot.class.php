<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_TradeLot extends DE_Base
{
	/**
	 * ID ивента
	 *
	 * @var integer
	 */
	private $eventQueueID;

	/**
	 * Скопе по торговле
	 *
	 * @var integer
	 */
	private $tradeScopeID;

	/**
	 * Тип торга ID
	 *
	 * @var integer
	 */
	private $tradeTypeID;

	/**
	 * Это надо на случай торговли артами
	 *
	 * @var integer
	 */
	private $articleID;

	/**
	 * Сколько продаем
	 *
	 * @var float
	 */
	private $amount;

	/**
	 * Цена старотовая
	 *
	 * @var float
	 */
	private $price;


	/**
	 * ID соты
	 *
	 * @var unknown_type
	 */
	private $sellerID;

	/**
	 * ID соты откуда продаем
	 *
	 * @var integer
	 */
	private $sellerCombID;

	/**
	 * Дата начала торга
	 *
	 * @var GB_Date
	 */
	private $startDate;

	/**
	 * ДАта окончания торга
	 *
	 * @var GB_Date
	 */
	private $endDate;

	/**
	 * Уакцион ли это
	 *
	 * @var boolean
	 */
	private $isAuction = false;

	/**
	 * ТОрг персональный илил нет
	 *
	 * @var boolean
	 */
	private $isPersonal = false;

	/**
	 * Если торг персональный то имя того кому выставляем
	 *
	 * @var string
	 */
	private $personName = '';

	/**
	 * Пароль для торга
	 *
	 * @var string
	 */
	private $personPassword = '';

	/**
	 * Размер послденей ставик на аукционе
	 *
	 * @var float
	 */
	private $tradeBidPrice;

	/**
	 * ID соты откуда сделагна ставка
	 *
	 * @var integer
	 */
	private $tradeBidFromCombID;

	/**
	 * Цена текущая на аукционе
	 *
	 * @var float
	 */
	private $currentPrice;

	/**
	 * Текущий покупатель id_userinstance
	 *
	 * @var integer
	 */
	private $currentCustomerID;

	/**
	 * id соты текущего покупателя
	 *
	 * @var integer
	 */
	private $currentCustomerCombID;

	/**
	 * Сумма налога которую надо вычесть из оплаты
	 *
	 * @var float
	 */
	private $taxSumm;

	/**
	 * ID мира в котором торг идет
	 *
	 * @var integer
	 */
	private $worldID;

	/**
	 * Лот анонимный или нет
	 *
	 * @var boolean ?
	 */
	private $isAnonymous;

	/**
	 * Цена за единицу товара
	 *
	 * @var float ?
	 */
	private $bestPrice;

	/**
	 * IP с какого выставили
	 *
	 * @var unknown_type
	 */
	private $sellerIP;

	/**
	 * Анонимная ставка
	 *
	 * @var int
	 */
	private $buyAnonymous = 0;

	function __construct($id, $data = '')
	{
		assert(is_int($id) && $id>0);
		parent::__construct($id);

		if($data == '')
		{
			$data = DS_Trade::GetTradeLotByID($id);
		}
		assert(is_array($data) && count($data) > 0);

		assert(key_exists('id_tradescope', $data));
		$this->tradeScopeID = (int)$data['id_tradescope'];
		assert($this->tradeScopeID > 0);

		assert(key_exists('id_article', $data));
		$this->articleID = (int)$data['id_article'];
		assert($this->articleID > 0);

		assert(key_exists('amount', $data));
		$this->amount = (float)$data['amount'];
		assert($this->amount > 0);

		assert(key_exists('startprice', $data));
		$this->price = (float)$data['startprice'];
		assert($this->price > 0);

		assert(key_exists('id_seller', $data));
		$this->sellerID = (int)$data['id_seller'];
		assert($this->sellerID > 0);

		assert(key_exists('id_seller_comb', $data));
		$this->sellerCombID = (int)$data['id_seller_comb'];
		assert($this->sellerCombID > 0);

		assert(key_exists('is_auction', $data));
		$this->isAuction = $data['is_auction'];

		assert(key_exists('is_personal', $data));
		$this->isPersonal = $data['is_personal'];

		assert(key_exists('person_name', $data));
		$this->personName = $data['person_name'];

		assert(key_exists('start_date', $data));
		$this->startDate = GB_TypeFactory::CreateBase('GB_Date', $data['start_date']);

		assert(key_exists('end_date', $data));
		$this->endDate = GB_TypeFactory::CreateBase('GB_Date', $data['end_date']);

		assert(key_exists('id_tradetype', $data));
		$this->tradeTypeID = (int)$data['id_tradetype'];
		assert($this->tradeTypeID > 0);

		assert(key_exists('id_eventqueue', $data));
		$this->eventQueueID = (int)$data['id_eventqueue'];
		assert($this->eventQueueID > 0);

		assert(key_exists('price_bid', $data));
		$this->currentPrice = (float)$data['price_bid'];

		assert(key_exists('id_comb_bid', $data));
		$this->currentCustomerCombID = (int)$data['id_comb_bid'];

		assert(key_exists('id_userinstance_bid', $data));
		$this->currentCustomerID = (int)$data['id_userinstance_bid'];

		assert(key_exists('id_world', $data));
		$this->worldID = (int)$data['id_world'];
		assert($this->worldID > 0);

		assert(key_exists('is_anonymous', $data));
		$this->isAnonymous = $data['is_anonymous'];

		assert(key_exists('seller_ip', $data));
		$this->sellerIP = $data['seller_ip'];
		
		assert(key_exists('is_anonymous_bid', $data));
		$this->buyAnonymous = (int)$data['is_anonymous_bid'];

	}//END OF METHOD

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return DE_TradeLot
	 */
	static function CreateFromID($id, $data = '')
	{
		assert(is_int($id) && $id > 0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return DE_TradeLot
	 */
	static function CreateFromEventQueueID($id)
	{
		assert(is_int($id) && $id > 0);

		$tradeID = DS_Trade::GetTradeLotIDByEventQueueID($id);
		assert(is_int($tradeID) && $tradeID > 0);

		return self::CreateFromID($tradeID);
	}

	/**
	 * Enter description here...
	 *
	 * @param DE_TradeLotPrototipe $tradeLotPrototipe
	 * @param DE_EventQueue $event
	 * @param DE_World $world
	 * @return DE_TradeLot
	 */
	static function CreateInDBFromTradePrototipeAndEventQueue(DE_TradeLotPrototipe $tradeLotPrototipe, DE_EventQueue $event, DE_World $world)
	{
		//Проверям был ли обет заполнен нормально
		$tradeLotPrototipe->validate();

		//Проверям про шел ли прототип валидацию предварительно
		$errorCode = GL_TradeCanSellByThisPriceAndScope::GetErrorCodeForObject($tradeLotPrototipe);
		assert(empty($errorCode));

		DS_Trade::InsertTradeLot($tradeLotPrototipe, $event, $world);

		//Елси это рподлажа артов, то еще и инстансыартов надо приаттачить к артам
		if($tradeLotPrototipe->get_fromArticle()->getTradeCategoryID() == 3)
		{
			ME_TradeArtefactInstancesOnTradeLotWork::AddArtfactsToTradeLotFromComb($tradeLotPrototipe->get_tradeLotID(),
			$tradeLotPrototipe->get_realFromArticleID(), $tradeLotPrototipe->get_amount(), $tradeLotPrototipe->get_fromComb(),
			$tradeLotPrototipe->get_artefactBirthTime(), $tradeLotPrototipe->get_artefactDeathTime());
		}

		return self::CreateFromID($tradeLotPrototipe->get_tradeLotID());
	}

	public function getWorldID()
	{
		return $this->worldID;
	}

	public function getTradeScopeID()
	{
		return $this->tradeScopeID;
	}

	public function getTradeScopeLicalizedName()
	{
		$sql = "SELECT
				lang.text					
				FROM 
				ds_trade_scope AS tsc
				LEFT JOIN ds_localizedtextlanguage AS lang ON lang.id_localizedtext = tsc.id_localizedname
				WHERE lang.id_language = ".$_SESSION['lang'];
		$common = DS_Common::CreateForCurentDB();
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		return $res[0]['text'];
	}


	public function haveCurrentCustomer()
	{
		if(empty($this->currentCustomerID))
		{
			return false;
		}else{
			return true;
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return DE_UserInstance
	 */
	public function getCurrentCustomer()
	{
		assert(is_int($this->currentCustomerID) && $this->currentCustomerID > 0);

		return DE_UserInstance::CreateFromID($this->currentCustomerID);
	}

	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCurrentCustomerComb()
	{
		assert(is_int($this->currentCustomerCombID));

		if($this->currentCustomerCombID == 0)
		{
			return new DE_CombNull('1.1.1');
		}else{
			return DE_Comb::CreateFromID($this->currentCustomerCombID);
		}
	}

	public function getCurrentPrice()
	{
		if($this->getIsAuction())
		{
			if($this->currentPrice == 0)
			{
				return $this->price;
			}else{
				return $this->currentPrice;
			}
		}else{
			return $this->price;
		}
	}

	public function setCurrentCustomer(DE_Comb $comb, $newPrice = null)
	{
		assert(is_float($newPrice) || is_null($newPrice));

		if(!is_null($newPrice))
		{
			assert($this->currentPrice < $newPrice);
			$this->currentPrice = (float)$newPrice;
		}


		$this->currentCustomerID = $comb->getUserInstanceID();
		$this->currentCustomerCombID = $comb->getID();
	}

	public function ereaseCurrentCustomer()
	{
		$this->currentCustomerID = 0;
		$this->currentPrice = 0;
	}

	public function setTaxSumm($taxSumm)
	{
		assert(is_float($taxSumm) && $taxSumm >= 0);
		assert($this->taxSumm <= $this->getCurrentPrice());

		$this->taxSumm = $taxSumm;
	}

	public function setBestPrice($bestPrice)
	{
		assert(is_float($bestPrice) && $bestPrice >= 0);

		$this->bestPrice = $bestPrice;
		$this->isModifyed = true;
	}

	public function getBestPrice()
	{
		return $this->bestPrice;
	}

	public function increaseEndDate(GB_TimePeriod $delta)
	{
		$this->endDate->value += $delta->value;
		$this->isModifyed = true;
	}

	public function getTaxSumm()
	{
		assert(is_float($this->taxSumm) && $this->taxSumm >= 0);

		return $this->taxSumm;
	}

	public function getPriceWithTaxForSendToSeller()
	{
		$price = (float)ceil($this->getCurrentPrice() - $this->getTaxSumm());

		assert($price >= 0);

		return $price;
	}

	/**
	 * Enter description here...
	 *
	 * @return DE_TradeType
	 */
	public function getTradeType()
	{
		assert(is_int($this->tradeTypeID) && $this->tradeTypeID > 0);

		$tradeType = DE_TradeType::CreateFromID($this->tradeTypeID."_".$this->worldID);
		$tradeType->setFromTradeArticle(DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld($tradeType->getTradeFromCategoryID(), $this->articleID));

		return $tradeType;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * Дата начала торга
	 *
	 * @return GB_Date
	 */
	public function getStartDate()
	{
		return $this->startDate;
	}

	/**
	 * Дата окнчания торга
	 *
	 * @return GB_Date
	 */
	public function getEndDate()
	{
		return $this->endDate;
	}

	public function getIsAuction($pgFormat = false)
	{
		assert(is_bool($pgFormat));

		if($pgFormat)
		{
			return $this->isAuction;
		}else{
			if($this->isAuction == 't')
			{
				return true;
			}else{
				return false;
			}
		}

	}

	public function getIsPersonal($pgFormat = false)
	{
		assert(is_bool($pgFormat));

		if($pgFormat)
		{
			return $this->isPersonal;
		}else{
			if($this->isPersonal == 't')
			{
				return true;
			}else{
				return false;
			}
		}

	}

	public function getIsAnonymous($pgFormat = false)
	{
		assert(is_bool($pgFormat));

		if($pgFormat)
		{
			return $this->isAnonymous;
		}else{
			if($this->isAnonymous == 't')
			{
				return true;
			}else{
				return false;
			}
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getSellerComb()
	{
		assert(is_int($this->sellerCombID) && $this->sellerCombID > 0);
		return DE_Comb::CreateFromID($this->sellerCombID);
	}

	/**
	 * Enter description here...
	 *
	 * @return DE_UserInstance
	 */
	public function getSeller()
	{
		assert(is_int($this->sellerID) && $this->sellerID > 0);
		return DE_UserInstance::CreateFromID($this->sellerID);
	}

	public function getArticleID()
	{
		return $this->articleID;
	}

	public function getSellerIP()
	{
		return $this->sellerIP;
	}

	/**
	 * Enter description here...
	 *
	 * @return DE_EventQueue
	 */
	public function getLotEvent()
	{
		assert(is_int($this->eventQueueID) && $this->eventQueueID > 0);

		return DE_EventQueue::CreateFromID($this->eventQueueID);
	}

	public function getPersonName()
	{
		return $this->personName;
	}

	public function getPersonPassword()
	{
		return $this->personPassword;
	}


	public function getbuyAnonymous()
	{
		return $this->buyAnonymous;
	}

	public function delete()
	{
		parent::delete();

		DS_Trade::DeleteTradeLotByID($this->getID());
	}

	public function commit()
	{
		parent::commit();

		if($this->isModifyed)
		{
			DS_Trade::UpdateTradeLot($this);
			$this->isModifyed = false;
		}
	}
}


?>