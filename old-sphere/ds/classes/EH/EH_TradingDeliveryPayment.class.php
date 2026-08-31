<?php $CVS_Version = '$Revision$ ; $Date$';

class EH_TradingDeliveryPayment extends EH_Event
{

	/**
	 * Экземпляр ивента скоторым работаем
	 *
	 * @var DE_EventQueue
	 */
	private $eventQueue;

	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $sellerComb;

	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $customerComb;

	private $amount;

	private $tradeCategoryID;

	private $articleID;

	private $tradeFromCategoryID;

	private $tradeFromCategoryName;

	private $articleFromID;

	/**
	 * Объект непосредственно самого товара (оплата)
	 *
	 * @var DE_TradeGoodInterface
	 */
	private $tradeGood;

	/**
	 * Объект непосредственно самого товара, который продали
	 *
	 * @var DE_TradeGoodInterface
	 */
	private $tradeGoodFrom;

	private $tradeCategoryName;

	function __construct()
	{
		EH_Event::EH_Event ();
	}

	function handleEvent($eqid)
	{

		//Принимаем сведения о ивенте который орббрабатываем
		assert(is_int($eqid) && $eqid > 0);

		$this->eqid = $eqid;

		$this->eventQueue = DE_EventQueue::CreateFromID($this->eqid);

		if(!$this->checktime)
		{
			$this->checktime = 'now()';
		}

		assert($this->getEventParams());

		//Принимаем параметры
		$this->sellerComb = DE_Comb::CreateFromID((int)$this->eventparams['ID_SellerComb']);

		$this->customerComb = DE_Comb::CreateFromID((int)$this->eventparams['ID_CustomerComb']);

		$this->price = (float)$this->eventparams['Price'];
		assert($this->price >= 0);

		$this->tradeCategoryID = (int)$this->eventparams['ID_Category'];
		assert($this->tradeCategoryID > 0);

		$this->articleID = (int)$this->eventparams['ID_Article'];
		assert($this->articleID > 0);

		$this->amount = (float)$this->eventparams['Amount'];
		assert($this->amount >= 0);

		$this->tradeFromCategoryID = (int)$this->eventparams['ID_CategoryFrom'];
		assert($this->tradeFromCategoryID > 0);

		$this->articleFromID = (int)$this->eventparams['ID_ArticleFrom'];
		assert($this->articleFromID > 0);

		$this->tradeCategoryName = DS_Trade::GetTradeCategoryNameByID($this->tradeCategoryID);
		assert(!empty($this->tradeCategoryName));

		$this->tradeFromCategoryName = DS_Trade::GetTradeCategoryNameByID($this->tradeFromCategoryID);
		assert(!empty($this->tradeFromCategoryName));

		$this->tradeGood = ME_TradeCreateGoodObjet::Exec($this->tradeCategoryName, $this->articleID);

		$this->tradeGoodFrom = ME_TradeCreateGoodObjet::Exec($this->tradeFromCategoryName, $this->articleFromID);

		//Обрабюытваем
		if($this->_isTimeToHandle())
		{
			$this->_handle();
		}else{
			$this->_preHandle();
		}

		return true;
	}//END OF METHOD


	private function _preHandle()
	{
		$messparams[0] = "{b}".GB_Integer::Format($this->price, ' ')."{/b}";
		$messparams[1] = "{b}".$this->tradeGood->getLocalizedName()."{/b}";

		$messparams[2] = "{b}".GB_Integer::Format($this->amount, ' ')."{/b}";
		$messparams[3] = "{b}".$this->tradeGoodFrom->getLocalizedName()."{/b}";
		$messparams[4] =  "{b}".$this->sellerComb->getFullName()."{/b}";

		$timeParts = $this->_getTimePeriodToEnd()->getWithParts();
		$messparams[5] = $timeParts['DAYS'];
		$messparams[6] = $timeParts['HOURS'];
		$messparams[7] = $timeParts['MINUTES'];
		$messparams[8] = $timeParts['SECONDS'];
		
		// Если покупатель решил остаться неизвестным
		if($this->eventparams['BuyAnonymously'] == 1)
		{
			// Оплата % % за продажу % % прибудет на соту % через % д % ч % м % с. Покупатель Аноним.
			$mesname = 'PROGRESS_DELIVERY_MESS_ANONYMOUS1';
		} else {
			// Оплата % % за продажу % % прибудет на соту %  через % д % ч % м % с. Покупатель %.
			$messparams[9] = "{b}".$this->customerComb->getUserInstance()->getUser()->getName()."{/b}";
			$mesname = 'PROGRESS_DELIVERY_MESS1';
		}

		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, $mesname, $messparams, 0, 'YELLOW', 'NOACCUMULATE' );
	}

	private function _handle()
	{
		$messparams[0] = "{b}".GB_Integer::Format($this->price, ' ')."{/b}";
		$messparams[1] = "{b}".$this->tradeGood->getLocalizedName()."{/b}";

		$messparams[2] = "{b}".GB_Integer::Format($this->amount, ' ')."{/b}";
		$messparams[3] = "{b}".$this->tradeGoodFrom->getLocalizedName()."{/b}";
		$messparams[4] = "{b}".$this->sellerComb->getFullName()."{/b}";

		
		// Если покупатель решил остаться неизвестным
		if($this->eventparams['BuyAnonymously'] == 1)
		{
			// ООплата % % за проданный товар (% %) получена на соту %.. Покупатель Аноним.
			$mesname = 'FINISH_DELIVERY_MESS_ANONYMOUS1';
		} else {
			// Оплата % % за проданный товар (% %) получена на соту %. Покупатель %.
			$messparams[5] =  "{b}".$this->customerComb->getUserInstance()->getUser()->getName()."{/b}";
			$mesname = 'FINISH_DELIVERY_MESS1';
		}

		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, $mesname, $messparams, 0, 'YELLOW' );

		ME_DeltaResourcesOnComb::Exec($this->tradeCategoryName, $this->sellerComb, $this->articleID, $this->price, $this->eventQueue->getEndDate());

		$this->eventQueue->setIsTrash(true);
	}
}//END OF CLASS

?>