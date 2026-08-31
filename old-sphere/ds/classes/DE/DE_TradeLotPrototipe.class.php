<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Прототип торга
 *
 */
class DE_TradeLotPrototipe extends BS_Base 
{	
	/**
	 * ID лота
	 *
	 * @var integer
	 */
	private $tradeLotID;	
	
	/**
	 * Скопе по торговле
	 *
	 * @var integer
	 */
	private $tradeScopeID;
	
	/**
	 * Тип торга
	 *
	 * @var DE_TradeType
	 */
	private $tradeType;
	
	/**
	 * Какой предмет торгуем
	 *
	 * @var DE_TradeArticle
	 */
	private $fromArticle;
	
	/**
	 * За какой предмет
	 *
	 * @var DE_TradeArticle
	 */
	private $toArticle;
	
	/**
	 * Это надо на случай торговли артами
	 *
	 * @var integer
	 */
	private $realFromArticleID;
		
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
	 * С какой соты барохло
	 *
	 * @var DE_Comb
	 */
	private $fromComb;
	
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
	 * Длительность торга в торнах
	 *
	 * @var integer
	 */
	private $duration;
		
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
	 * Анонимный лот
	 *
	 * @var bool
	 */
	private $isAnonymous = false;
	
	/**
	 * IP продавца
	 *
	 * @var string
	 */
	private $sellerIP;
		
	/**
	 * Время рождения артефакта
	 *
	 * @var str
	 */
	public $artefactDeathTime = '';
			
	/**
	 * Время смерти артефакта
	 *
	 * @var str
	 */
	public $artefactBirthTime = '';
		
	public function __construct($tradeScopeID)
	{		
		assert(is_int($tradeScopeID) && $tradeScopeID > 0);
						
		$this->tradeLotID = DS_Trade::GetNewTradeLotID();	
		
		$this->tradeScopeID = $tradeScopeID;
	}

	
	//////////////////////////////////////// SET METHODS ///////////////////////////////////////////
	
	public function setTradeArticles(DE_TradeArticle $fromArticle, DE_TradeArticle $toArticle)
	{		
		$this->fromArticle = $fromArticle;
		$this->toArticle = $toArticle;
		
		$this->tradeType = DE_TradeType::CreateFromTradeArticlesAndWorld($fromArticle, $toArticle, DE_World::CreateForCurrentWorld());
		
		$this->realFromArticleID = $this->fromArticle->getArticleID();
	}

	/**
	 * Это нужно только при торговле артами. Тту надо указать его инстанс
	 *
	 */
	/*public function setRealArticleID($realArticleID)
	{
		assert(is_int($realArticleID) && $realArticleID > 0);
		
		$this->realFromArticleID = $realArticleID;
	}*/

		
	public function setFromComb(DE_Comb $comb)
	{
		$this->fromComb = $comb;
	}
	
	public function setPriceAndAmount($price, $amount)
	{
		assert(is_float($price) && $price > 0);		
		$this->price = ceil($price);
		assert($this->price > 0);
		
		assert(is_float($amount) && $amount > 0);
		$this->amount = floor($amount);
		assert($this->amount > 0);		
	}
	
	public function setStartDateAndDuration(GB_Date $startDate, $duration)
	{
		assert(is_int($duration) && $duration > 0);
		
		$this->startDate = $startDate;
		$this->duration = $duration;
		
		$delta = $duration * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		
		$this->endDate = GB_TypeFactory::CreateBase('GB_Date', $this->startDate->value + $delta);
		
		assert($this->endDate->value > $this->startDate->value);		
	}
	
	public function setIsAuction()
	{	
		assert(!$this->isPersonal);					
		
		$this->isAuction = true;						
	}
	
	public function setIsPersonal($personName, $personPassword = '')
	{
		assert(!empty($personName));
		assert(!$this->isAuction);
		
		$this->isPersonal = true;
		$this->personName = $personName; 	
		$this->personPassword = $personPassword;
	}
			
	public function setIsAnonymous($anonymous)
	{
		assert(is_bool($anonymous));
		
		$this->isAnonymous = $anonymous;
	}
	
	public function setSellerIP($ip)
	{		
		if(!preg_match('/^([0-9]{1,3}\.?){4}$/', $ip))
		{
			$ip = '1.1.1.1';
		}
		
		assert(preg_match('/^([0-9]{1,3}\.?){4}$/', $ip));
		
		$this->sellerIP = $ip;
	}
	
	public function setArtefactBirthTime($artefactBirthTime)
	{		
		$this->artefactBirthTime = $artefactBirthTime;
	}
	
	public function setArtefactDeathTime($artefactDeathTime)
	{		
		$this->artefactDeathTime = $artefactDeathTime;
	}
	
	////////////////////////////////////// GET METHODS ////////////////////////////////////////////////
	
	/**
	 * Фрмат для вызоав будет get_ИМЯ_АТРИБУТА_КЛАССА
	 *
	 * @param имя метода которого не существует $name
	 * @param массив с аргументами $arguments
	 */
	public function __call ($name, $arguments)
	{	
		if(!is_array($arguments))		
		{
			$arguments = array();
		}
	
		$methodNameParts = explode('_', $name);
		assert(count($methodNameParts) == 2);
		
		assert(key_exists(0, $methodNameParts));
		$methodType = $methodNameParts[0];
		assert(!empty($methodType));
		
		assert($methodType == 'get');
		
		assert(key_exists(1, $methodNameParts));
		$attributeName = $methodNameParts[1];
		assert(!empty($attributeName));
		
		$objectVars = get_object_vars($this);
				
		assert(key_exists($attributeName, $objectVars));
		
		$value = null;
		
		switch ($attributeName)
		{
			//Можно передать в качестве певого параметра функции true и тогда будет возвращатсья значение в формате посгреса		
			case 'isAuction':
			case 'isPersonal':
			case 'isAnonymous':
				if(key_exists(0, $arguments) && $arguments[0] == true)
				{
					if($objectVars[$attributeName])
					{
						$value = 't';
					}else{
						$value = 'f';
					}
				}else{
					$value = $objectVars[$attributeName];
				}
				break;
				
			default:
				$value = $objectVars[$attributeName];
		}
		
		return 	$value;
	}
	
	
	/**
	 * Проверка на валидность и заверщенность всей этой конструкции
	 *
	 */
	public function validate()
	{
		assert(is_int($this->tradeLotID) && $this->tradeLotID > 0);	
		assert(is_int($this->tradeScopeID) && $this->tradeScopeID > 0);
		
		assert($this->tradeType instanceof DE_TradeType);
		assert($this->fromArticle instanceof DE_TradeArticle);
		assert($this->toArticle instanceof DE_TradeArticle);
		assert(is_int($this->realFromArticleID) && $this->realFromArticleID > 0);
		
		assert(is_float($this->amount) && $this->amount > 0);
		assert(is_float($this->price) && $this->price > 0);
		
		assert($this->fromComb instanceof DE_Comb);
		
		assert($this->startDate instanceof GB_Date);
		assert($this->endDate instanceof GB_Date);
		assert(is_int($this->duration) && $this->duration > 0);
		
		assert(is_bool($this->isAuction));
		assert(is_bool($this->isPersonal));
		assert(is_bool($this->isAnonymous));
		assert(($this->isAuction != $this->isPersonal) || ($this->isAuction == false && $this->isPersonal == false));
		
		if($this->isPersonal)
		{
			assert(!empty($this->personName));
		}		
	}
}

?>