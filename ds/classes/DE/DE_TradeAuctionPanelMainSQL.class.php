<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/08/25 09:41:18 $';

class DE_TradeAuctionPanelMainSQL extends Base
{	
    public $sortPlace = 'COMB_COMPLEX_TRADING';

    public $sortType = 'RESOURCES';

    public $buyBy = array();

    public $sortName = 'ALL';

    public $isImidiate = false;

    public $order = '';

    public $isOwnTrades = false;

    public $isOwnBets = false;

    public $currentPage = 1;

    public $tradesPerPage = 10;

    public $trader = '';

    public $isPersonal;

    public $personName;

    /**
     * Позволена ли в данном не-рпр мире межрассовая торговля юнитами
     *
     * @var unknown_type
     */
    public $nonRvrUnitsAllowd = false;

    /**
     * Массив с данными установленными по филтрам
     *
     * @var array
     */
    public $filters = array();

    /**
     * Массив с данными сортировок котоыре усттанвленными
     *
     * array(
     * 	'SORT_NAME' => array('ORDER' => 1, 'DIRECTION' => D)
     *  )
     * )
     *
     * @var array
     */
    public $sorting = array();

    /**
     * Являкться ли мир рассой против рассы. Т.е запрещенна торговля с другой рассой
     *
     * @var boolean
     */
    public $isRVRTrade = false;

    /**
     * ЗАдержка в секундах после выставлоения на торг перед появлениям в спсиках
     * лотов.
     *
     * @var integer
     */
    public $tradeDelay = 0;

    static private $allowHrefs = array('SELECT', 'FROM', 'WHERE', 'ORDER', 'LIMIT', 'OFFSET');

    public $sqlBase = "
        SELECT
            {SELECT}
        FROM
            ds_trade_lots AS tl
            INNER JOIN ds_trade_type AS tt ON (tt.id_tradetype = tl.id_tradetype)
            LEFT JOIN ds_trade_bids AS tb ON (tb.id_trade_lot = tl.id_trade_lot)
            {FROM}
        WHERE
            tl.end_date > now()
            {WHERE}";

    public $sqlOrderPart = "ORDER BY {ORDER}";

    public $sqlLimitPart = "LIMIT {LIMIT} OFFSET {OFFSET}";

    public $sqlFinal = array();

    /**
     * Мир для которого создаем запрос
     *
     * @var DE_World
     */
    public $world;

    /**
     * Enter description here...
     *
     * @var DE_Comb
     */
    public $comb;

    public function __construct(DE_World $world)
    {
    	parent::__construct();

      $this->world = $world;
    }

    //////////////////// FROM SQL METHODS ///////////////////////
    public function createSQLVersion($versionName, $withOrderPart = false, $withLimitPart = false)
    {
        assert(!key_exists($versionName, $this->sqlFinal));
        assert(is_bool($withOrderPart));
        assert(is_bool($withLimitPart));

        $this->sqlFinal[$versionName] = $this->sqlBase;

        if($withOrderPart)
        {
            $this->sqlFinal[$versionName] .= "\n".$this->sqlOrderPart;
        }

        if($withLimitPart)
        {
            $this->sqlFinal[$versionName] .= "\n".$this->sqlLimitPart;
        }

        $this->addHrefValue($versionName, 'WHERE', 'AND tl.id_world = '.$this->world->getID());
    }

    public function addHrefValue($versionName, $hrefName, $value)
    {

        assert(key_exists($versionName, $this->sqlFinal));
        assert(in_array($hrefName, self::$allowHrefs));

        $str = $this->sqlFinal[$versionName];
        $href = "{".$hrefName."}";
        $data = $value.$href."\n";

        $this->sqlFinal[$versionName] = str_replace($href, $data, $str);
    }

    public function getVersionSql($versionName)
    {

        //SU_DebugTools::LogAll($this->sqlFinal,'oleg123oleg.log');

        assert(key_exists($versionName, $this->sqlFinal));
        $out = $this->sqlFinal[$versionName];

        foreach(self::$allowHrefs as $k=>$hrefName)
        {
            $out = str_replace("{".$hrefName."}", '', $out);
        }
//SU_DebugTools::LogAll($out,'AnnitaTr.log');
        return $out;
    }


    /////////////////// SET METHODS ////////////////////////////

    public function setRvrFlag($val)
    {
        assert(is_bool($val));

        $this->isRVRTrade = $val;
    }

    public function setPersonal($personName)
    {
        $this->personName = $personName;
    }

    public function setSortPlace($val)
    {
        if(empty($val)) return;

        $this->sortPlace = $val;
    }

    public function setSortType($val)
    {
        if(empty($val)) return;

        $this->sortType = $val;
    }

    public function setSortName($val)
    {
        if(empty($val)) return;

        $this->sortName = $val;
    }

    public function setBuyBy($val)
    {
        if(empty($val)) return;

        $this->buyBy = $val;
    }

    public function setImidiate($val)
    {
        $this->isImidiate = (bool)$val;
    }

    public function setOrder(array $val)
    {
        if(empty($val)) return;

        $this->sorting = $val;
    }

    public function setOwnTrades($val)
    {
        if(empty($val)) return;

        $this->isOwnTrades = true;
    }

    public function setOwnBets($val)
    {
        if(empty($val)) return;

        $this->isOwnBets = true;
    }

    public function setComb(DE_Comb $comb)
    {
        $this->comb = $comb;
    }

    public function setTradeDelay($delay)
    {
        assert(is_int($delay) && $delay >= 0);

        $this->tradeDelay = $delay;
    }

    public function setPages($currentPage, $tradesPerPage)
    {
        assert(is_int($currentPage) && $currentPage > 0);
        assert(is_int($tradesPerPage) && $tradesPerPage > 0);

        $this->currentPage = $currentPage;
        $this->tradesPerPage = $tradesPerPage;
    }

    public function setTrader($trader)
    {
        $this->trader = $trader;
    }

    public function setFilters(array $filters)
    {
        assert(count($filters) > 0);
        $this->filters = $filters;
    }

    public function setNonRvrUnitsInterRaceFlag($val)
    {
        assert(is_bool($val));

        $this->nonRvrUnitsAllowd = $val;
    }
}

?>