<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_Sell extends UA_Action
{
    /**
     * ID Скопа торговых операций
     *
     * @var integer
     */
    public $tradeScopeID;

    /**
     * ID категори которую выставляем
     *
     * @var integer
     */
    public $fromCategoryID;

    /**
     * Имя категории которую выставляем на торги
     *
     * @var string
     */
    public $fromTradeCategoryName;

    /**
     * ID товара который выставляем
     *
     * @var integer
     */
    public $fromArticleID;


    /**
     * Настощий ID шмотки который выставляем нужен тольк одля торговли артами. тут
     * он равен ID инстанса артефакта в остальбных случаях $fromRealArticleID = $fromArticleID
     *
     * @var integer
     */
    public $fromRealArticleID;

    /**
     * Кооличество
     *
     * @var float
     */
    public $amount;

    /**
     * Категория за которую выставляем
     *
     * @var integer
     */
    public $toCategoryID;

    /**
     * Предет за который выставляем
     *
     * @var integer
     */
    public $toArticleID;

    /**
     * ЗА сколько выставляем
     *
     * @var float
     */
    public $price;

    /**
     * Аукционый ли торг
     *
     * @var boolean
     */
    public $isAuction;

    /**
     * Персональный ли ник лил нет
     *
     * @var boolean
     */
    public $isPersonal;

    /**
     * Имя кому мы выставляем
     *
     * @var string
     */
    public $personName;

    /**
     * Пароль на торг
     *
     * @var string
     */
    public $personPassword;

    /**
     * Время торга в турнах
     *
     * @var integer
     */
    public $tradeTime;


    /**
     * Прототип лота
     *
     * @var DE_TradeLotPrototipe
     */
    public $tradeLotPrototipe;


    /**
     * Что выставляем на торг
     *
     * @var DE_TradeArtcile
     */
    public $fromTradeArticle;

    /**
     * За что выставляем на торг
     *
     * @var DE_TradeArtcile
     */
    public $toTradeArticle;

    /**
     * С какой соты выставляем
     *
     * @var DE_Comb
     */
    public $fromComb;

    /**
     * Датат начала торга
     *
     * @var GB_Date
     */
    public $startDate;

    /**
     * Анонимный лот
     *
     * @var bool
     */
    public $isAnonymous;

    /**
     * Enter description here...
     *
     * @var DE_UnitsMoveCostFullValue
     */
    public $liveUpkeep;
    
    /**
     * Время жизни артефакта
     *
     * @var str
     */
    public $artefactTime;

    function __construct()
    {
        UA_Action::UA_Action();
    }

    // проверяем параметры
    function checkLocalParams ()
    {
        $this->tradeScopeID = (int)$this->root->get_attribute("id_tradescope");
        assert($this->tradeScopeID > 0);

        $this->fromCategoryID = (int)$this->root->get_attribute("id_fromcategory");
        assert($this->fromCategoryID > 0);

        $this->fromArticleID = (int)$this->root->get_attribute("id_fromarticle");
        assert($this->fromArticleID > 0);

        $this->amount = (float)ceil($this->root->get_attribute("amount"));
        assert($this->amount > 0);

        $this->toCategoryID = (int)$this->root->get_attribute("id_tocategory");
        assert($this->toCategoryID > 0);

        $this->toArticleID = (int)$this->root->get_attribute("id_toarticle");
        assert($this->toArticleID > 0);

        $this->price = (float)ceil($this->root->get_attribute("price"));
        assert($this->price > 0);

        $this->tradeTime = (int)$this->root->get_attribute("tradetime");
        assert($this->tradeTime > 0 && $this->tradeTime < 1000);

        $this->isAuction = (boolean)$this->root->get_attribute("isauction");

        $this->isPersonal = (boolean)$this->root->get_attribute("ispersonal");

        $this->personName = trim($this->root->get_attribute("nick"));

        $this->personPassword = $this->root->get_attribute("pass");

        $this->isAnonymous = (boolean)$this->root->get_attribute("anonymous");
        
        $this->artefactTime = $this->root->get_attribute("death");

        return true;
    }


    // проверяем допустимость действия
    function checkAction ()
    {
        $this->fromTradeArticle = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld($this->fromCategoryID, $this->fromArticleID);
        $this->toTradeArticle = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld($this->toCategoryID, $this->toArticleID);

        $this->fromComb = DE_Comb::CreateFromID($this->comb);

        $this->startDate = GB_TypeFactory::CreateBase('GB_Date', time());

        $this->tradeLotPrototipe = new DE_TradeLotPrototipe($this->tradeScopeID);
        $this->tradeLotPrototipe->setTradeArticles($this->fromTradeArticle, $this->toTradeArticle);
        //$this->tradeLotPrototipe->setRealArticleID($this->fromRealArticleID);
        $this->tradeLotPrototipe->setFromComb($this->fromComb);
        $this->tradeLotPrototipe->setPriceAndAmount($this->price, $this->amount);
        $this->tradeLotPrototipe->setStartDateAndDuration($this->startDate, $this->tradeTime);
        
        if (!empty($this->artefactTime)) {
            $arTpp = explode('|', $this->artefactTime);
            
            // Часы и минуты не учитываем, потому что это нам не принципиально
            $this->tradeLotPrototipe->setArtefactBirthTime(date('Y-m-d', $arTpp[0]));
            $this->tradeLotPrototipe->setArtefactDeathTime(date('Y-m-d', $arTpp[1]));
        }

        if($this->isAuction)
        {
            $this->tradeLotPrototipe->setIsAuction();
        }

        if($this->isPersonal)
        {
            $this->tradeLotPrototipe->setIsPersonal($this->personName, $this->personPassword);
        }

        if($this->isAnonymous && DE_WorldSettings::CreateForCurentWorld()->get('TRADE_ANONYMOUS_SELL'))
        {
            $this->tradeLotPrototipe->setIsAnonymous($this->isAnonymous);
        }

        $ip = SU_DebugTools::GetIP(0);
        $this->tradeLotPrototipe->setSellerIP($ip['IP']);

        return true;
    }

    // выполняем действие
    function performAction ()
    {
        //Проверка можно лди это дело делать под считом
        if(!$this->_testFreezPermission())
        {
            return true;
        }

        //Проверям полностью лот на политкорректность
        if(!GL_TradeCanSellByThisPriceAndScope::Test($this->tradeLotPrototipe))
        {
            $errorCode = GL_TradeCanSellByThisPriceAndScope::GetErrorCodeForObject($this->tradeLotPrototipe);
                
            switch ($errorCode)
            {
                case 'UNCORRECT_USERNAME_NAME_FOR_PERSON_TRADE':
                        
                    $messparams = array($this->tradeLotPrototipe->get_personName());
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_NO_PERSON_FOR_LOT', $messparams);
                    return true;
                        
                    break;
                case 'DONT_HAVE_NEED_AMOUNT':

                    assert($this->refresh(1, "", "", 1, $noCellData));
                        
                    $this->_sendMenu();

                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_DONT_HAVE_NEED_AMOUNT', $messparams);
                        
                    return true;
                        
                    break;
                        
                // допрыгался, запретили торговлю
                case 'CANT_SELL_WITH_TRADE_BAN':
                        
                    $messparams = array();
                    //$banInfo = DS_User::GetTradeBanDetails($this->fromComb->getUserInstance()->getUser());
                    //assert(count($banInfo) > 0);
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_BAN', $messparams);
                    return true;
                        
                    break;
                        
                // на соте идет бой
                case 'CANT_SEL_UNITS_WHEN_BATTLE_IN_PROGRESS':
                        
                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_UNITS_WHEN_BATTLE_IN_PROGRESS', $messparams);
                    return true;
                        
                    break;
                    
                // допрыгался, запретили торговлю
                case 'USER_HAS_NOT_TRADE_ABILITY':
                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'USER_HAS_NOT_TRADE_ABILITY', $messparams);
                    return true;
                        
                    break;
                    
                // рандомная защита
                case 'CANT_SELL_WITH_RANDOM_DEFEND':
                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_SELL_WITH_RANDOM_DEFEND', $messparams);
                    return true;
                        
                    break;
                        
                default:
                    DS_Error("TradeLotPrototipe validation error: ".$errorCode);
            }
        }

        //Смотрим максимальное количество лотов
        $maxLots = DE_WorldSettings::CreateForCurentWorld()->get('TRADE_MAX_LOTS');

        $countOfLots = 0;

        //Если ограничение включено
        if ($maxLots != 0) {
            //Получаем текущее количество лотов игрока
            $countOfLots = DS_Trade::GetCountUserLots((int)$this->uiid);
                
            if ($countOfLots > $maxLots) {
                $messparams[0] = $maxLots;
                DE_SystemMessages::AddMessageFromAction(__CLASS__, 'MAX_LOTS', $messparams);
                return true;
            }
        }
            
        //Создаем сам лот
        try {
            $this->tradeLot = ME_TradeExecLotSell::Exec($this->tradeLotPrototipe);
        } catch (ME_TradeExecLotSellException $e) {
            switch ($e->getMessage())
            {
                case 'NOT_ENOUGHT_RESOURCES_FOR_SELL_THIS_UNITS':
                    assert($this->refresh(1, "", "", 1, $noCellData));
                        
                    $this->_sendMenu();

                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_NOT_ENOUGHT_RESOURCES_FOR_SELL_THIS_UNITS', $messparams);
                        
                    return true;
                    break;
                default:
                    ds_error('Sell error: '.$e->getMessage());
            }
        }

        //полылаем сиситемное сообщение что все хорошо
        $this->_sendOkMessage($maxLots, $countOfLots);

        //Послыаем хелпер и рефреш
        $this->_sendRefreshAndHalper();

        return true;
    }

    /**
     * Помылаем сообщение об успешном выставление на торги
     *
     */
    private function _sendOKMessage($maxLots = 0, $countOfLots = 0)
    {
        //Опредляем cперва само сообщение
        if($this->isAuction)
        {
            //Вы выставили на аукнцион {GOOD_NAME}({COOD_AMOUNT}) за {ARTICLE_NAME}({ARTICLE_AMOUNT})[{SCOPE}]. Длительность торга: {TURNS}
            $messageName = 'SELL_MESSAGE_AUCTION';
                
            $messparams[0] = $this->fromTradeArticle->getLocolizedName();
            $messparams[1] = GB_Integer::Format($this->amount, ' ');
            $messparams[2] = $this->toTradeArticle->getLocolizedName();
            $messparams[3] = GB_Integer::Format($this->price, ' ');
            $messparams[4] = DS_Trade::GetLocalizedTradeScope($this->tradeScopeID);
            $messparams[5] = $this->tradeTime;
        }elseif ($this->isPersonal){
            //Вы выставили персональный лот для {USERNAME} {GOOD_NAME}({COOD_AMOUNT}) за {ARTICLE_NAME}({ARTICLE_AMOUNT})[{SCOPE}]. Длительность торга: {TURNS}
            $messageName = 'SELL_MESSAGE_PERSONAL';
                
            $messparams[0] = $this->personName;
            $messparams[1] = $this->fromTradeArticle->getLocolizedName();
            $messparams[2] = GB_Integer::Format($this->amount, ' ');
            $messparams[3] = $this->toTradeArticle->getLocolizedName();
            $messparams[4] = GB_Integer::Format($this->price, ' ');
            $messparams[5] = DS_Trade::GetLocalizedTradeScope($this->tradeScopeID);
            $messparams[6] = $this->tradeTime;
        }else{
            //Вы выставили на продажу {GOOD_NAME}({COOD_AMOUNT}) за {ARTICLE_NAME}({ARTICLE_AMOUNT})[{SCOPE}]. Длительность торга: {TURNS}
            $messageName = 'SELL_MESSAGE_SIMPL';
            $messparams[0] = $this->fromTradeArticle->getLocolizedName();
            $messparams[1] = GB_Integer::Format($this->amount, ' ');
            $messparams[2] = $this->toTradeArticle->getLocolizedName();
            $messparams[3] = GB_Integer::Format($this->price, ' ');
            $messparams[4] = DS_Trade::GetLocalizedTradeScope($this->tradeScopeID);
            $messparams[5] = $this->tradeTime;
        }

        //Если ограничение включено
        if ($maxLots != 0) {
            //Посылаем сообщение о том, сколько еще лотов может выставить игрок
            $messparams[] = $maxLots-$countOfLots;
            $messageName = $messageName.'_AVAILABLE_LOTS';
        }

        DE_SystemMessages::AddMessageFromAction(__CLASS__, $messageName, $messparams, 0, 'YELLOW');
    }

    /**
     * Проверка можно ли это действие делать
     *
     * @return boolean
     */
    private function _testFreezPermission()
    {
        //ДЕйствие нельзя делать в режиме отпуска
        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();
            return false;
        }

        //ДЕйсвтие нельзя делать под щитом
        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();
            return false;
        }

        return true;
    }

    private function _sendMenu()
    {
        $menu = new PN_TradePanel();

        $menu->setBID(23);
        $menu->setTAB('PN_TradePanel');

        assert($menu->getPanel());
    }

    /**
     * Посылаем хелпер и рефреш НАДО ПЕРЕДЕЛАТЬ!!!!!
     */
    private function _sendRefreshAndHalper()
    {
        $noCellData = true;

        $this->refresh(1, "", "", 1, $noCellData);

        //Посылаем хелпер
        if($_SESSION['profi_game'])
        {
            $str  = '<?xml version="1.0" encoding="utf-8" ?>'."\n".'<sellok>';
            // Если это арты
            
            $str .= '<article id_category="'.$this->fromCategoryID.'" id="'.$this->fromArticleID.'" amount="'.GB_Integer::Format($this->amount).'" death="'.$this->artefactTime.'"/>';

            if($this->liveUpkeep instanceof DE_UnitsMoveCostFullValue)
            {
                foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
                {
                    $amount = (float)$this->liveUpkeep->getPay($resourceName);
                        
                    if($amount == 0)
                    {
                        continue;
                    }
                        
                    $resourceID = ST_Attributes::Create()->getAttributeIDByAttributeName($resourceName);
                        
                    $str .= '<article id_category="1" id="'.$resourceID.'" amount="'.GB_Integer::Format($amount).'" />';
                }
            }
            $str .= '<sellok/>';

            SU_OutputHandler::Singleton()->addString($str , 'helper');
        }
    }
}

?>