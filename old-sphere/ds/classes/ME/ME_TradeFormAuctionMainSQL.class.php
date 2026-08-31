<?php $CVS_Version = '$Revision: 1.11 $ ; $Date: 2009/02/02 11:28:15 $';

class ME_TradeFormAuctionMainSQL
{
    /**
     * Объект с данными для генерации SQL.
     *
     * @var DE_TradeAuctionPanelMainSQL
     */
    static private $mainSQL;

    static public function Exec(DE_TradeAuctionPanelMainSQL $mainSQL)
    {

        self::_init($mainSQL);

        assert(!($mainSQL->isOwnTrades && $mainSQL->isOwnBets));

        self::_applyRVRTrade();

        self::_applyTradeDelay();

        self::_applyTradeScope();

        self::_applySortType();

        self::_applyBuyBy();

        self::_applyIsImidiate();

        self::_applyOwnTrades();

        self::_applyOwnBeds();

        self::_applyOrder();

        //self::_applyPages();

        self::_applyTrader();

        self::_applyPersonalTrades();

        self::_applyTrands();

    }

    static private function _init(DE_TradeAuctionPanelMainSQL $mainSQL)
    {
        self::$mainSQL = $mainSQL;

        self::$mainSQL->createSQLVersion('MAIN_SELECT', true, false);

        self::$mainSQL->addHrefValue('MAIN_SELECT', 'SELECT', 'tl.id_trade_lot AS id, *');

        $sqlFromPart1 = " INNER JOIN ds_trade_scope AS ts ON (ts.id_tradescope = tl.id_tradescope)";
        self::$mainSQL->addHrefValue('MAIN_SELECT', 'FROM', $sqlFromPart1);

        $sqlFromPart2 = " LEFT JOIN ds_trade_bids AS tbd ON (tbd.id_trade_lot = tl.id_trade_lot)";
        self::$mainSQL->addHrefValue('MAIN_SELECT', 'FROM', $sqlFromPart2);

        self::$mainSQL->createSQLVersion('COUNT_ROWS', false, false);
        self::$mainSQL->addHrefValue('COUNT_ROWS', 'SELECT', 'count(tl.id_trade_lot) AS colvo');

        self::$mainSQL->createSQLVersion('ALL_LOT_IDS', true, false);
        self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'SELECT', 'tl.id_trade_lot');
    }

    static public function _applyRVRTrade()
    {
        if(!self::$mainSQL->isRVRTrade)
        {
            return;
        }else{

            switch(self::$mainSQL->sortType)
            {
                case 'RESOURCES':
                    if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_RVR_ALLOW_RESOURCE'))
                    {
                        return;
                    }
                    break;
                case 'UNITS':
                    if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_RVR_ALLOW_UNITS'))
                    {
                        return;
                    }
                    break;
                case 'ARTEFACTS':
                    if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_RVR_ALLOW_ARTEFACTS'))
                    {
                        return;
                    }
                    break;
                default:
                    DS_Error('Unknown SortType for form SQL = '.self::$mainSQL->sortType);
                }
            }



            $sqlFromPart = " INNER JOIN ds_userinstance AS ui2 ON (ui2.id_userinstance = tl.id_seller) ";

            $sqlWherePart = " AND ui2.id_race = ".$_SESSION['race'];
/*
            self::$mainSQL->addHrefValue('MAIN_SELECT', 'FROM', $sqlFromPart);
            self::$mainSQL->addHrefValue('COUNT_ROWS', 'FROM', $sqlFromPart);
            self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
            self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
*/
            self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'FROM', $sqlFromPart);
            self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);

        }

        static public function _applyTradeDelay()
        {

            if(self::$mainSQL->tradeDelay == 0)
            {
                return;
            }

            $sqlWherePart = " AND now() > ( tl.start_date + '".self::$mainSQL->tradeDelay." SECOND'::INTERVAL ) ";

            /*
            self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
            self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
             */
            self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
        }

        static public function ApplyPages($mainSQL)
        {
            if(!($mainSQL instanceof DE_TradeAuctionPanelMainSQL))
            {
                $mainSQL = self::$mainSQL;
            }

            $offset = $mainSQL->tradesPerPage * ($mainSQL->currentPage - 1);

            $mainSQL->addHrefValue('MAIN_SELECT', 'OFFSET', $offset);
            $mainSQL->addHrefValue('MAIN_SELECT', 'LIMIT', $mainSQL->tradesPerPage);

        }

        static private function _applyOrder()
        {
            $sqlOrderPartArray = array();
            $sqlFromPart = '';

            $direction = array('D' => 'ASC', 'U' => 'DESC');

            foreach(self::$mainSQL->sorting as $sortName => $sortDir)
            {
                switch($sortName)
                {
                    case 'SORT_NAME':
                        $sqlOrderPartArray[] = "tl.id_article ".$direction[$sortDir];
                        break;
                    case 'SORT_AMOUNT':
                        $sqlOrderPartArray[] = "tl.amount ".$direction[$sortDir];
                        break;
                    case 'SORT_PRICE':
                        $sqlOrderPartArray[] = "tb.price_bid ".$direction[$sortDir].", tl.startprice ".$direction[$sortDir];
                        break;
                    case 'SORT_END_DATE':
                        $sqlOrderPartArray[] = "tl.end_date ".$direction[$sortDir];
                        break;
                    case 'SORT_SELLER_NAME':
                        $sqlFromPart = "
                        INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = tl.id_seller)
                        INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)";
                        $sqlOrderPartArray[] = "u.username ".$direction[$sortDir];
                        break;
                    case 'SORT_BEST_PRICE':
                        $sqlOrderPartArray[] = "tl.best_price ".$direction[$sortDir];
                        break;
                    default:
                        DS_Error('Unknown SortName for form SQL = '.$sortName);
                    }
                }

                if(count($sqlOrderPartArray) == 0)
                {
                    $sqlOrderPartArray[] = "tl.end_date DESC";
                }

                $sqlOrderPart = implode(', ', $sqlOrderPartArray);

                self::$mainSQL->addHrefValue('MAIN_SELECT', 'ORDER', $sqlOrderPart);
                self::$mainSQL->addHrefValue('MAIN_SELECT', 'FROM', $sqlFromPart);

                self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'ORDER', $sqlOrderPart);
                self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'FROM', $sqlFromPart);
            }

            static public function _applyPersonalTrades()
            {
            	if(self::$mainSQL->filters['SHOW_PERSONAL_TRADE'] == 1 && self::$mainSQL->filters['SHOW_NO_PERSONAL_TRADE'] == 1)
                {
                    // Выводим персональные лоты, предназначченые мне, неперсональные лоты, и лоты, выставеные мной
                	$sqlWherePart = " 
                    AND 
                    (
                        (tl.is_personal = true AND tl.person_name = '".self::$mainSQL->personName."') 
                    	OR 
                    	tl.is_personal = false
                     	OR
                        (tl.is_personal = true AND tl.id_seller = ".$_SESSION['uiid'].")
                     )";
                } elseif(self::$mainSQL->filters['SHOW_PERSONAL_TRADE'] == 1 && self::$mainSQL->filters['SHOW_NO_PERSONAL_TRADE'] == 0) {
                    // Выводим персональные лоты, предназначченые мне, и лоты, выставеные мной
                    $sqlWherePart = " 
                    AND 
                   (tl.is_personal = true AND tl.person_name = '".self::$mainSQL->personName."')
                    	"; 
                    
                    /*$sqlWherePart = " 
                    AND 
                    (
                    	(tl.is_personal = true AND tl.person_name = '".self::$mainSQL->personName."')
                    	OR
                    	(tl.is_personal = true AND tl.id_seller = ".$_SESSION['uiid'].")
                    )";*/
                } elseif(self::$mainSQL->filters['SHOW_PERSONAL_TRADE'] == 0 && self::$mainSQL->filters['SHOW_NO_PERSONAL_TRADE'] == 1) {
                    $sqlWherePart = " AND tl.is_personal = false";
                } elseif(self::$mainSQL->filters['SHOW_PERSONAL_TRADE'] == 0 && self::$mainSQL->filters['SHOW_NO_PERSONAL_TRADE'] == 0) {
                	// По логиге, такой ситуации не должно быть, в этом случае выводим все лоты (раньше ничего не выводили)
                    // Выводим персональные лоты, предназначченые мне, неперсональные лоты, и лоты, выставеные мной
                	$sqlWherePart = " 
                    AND 
                    (
                        (tl.is_personal = true AND tl.person_name = '".self::$mainSQL->personName."') 
                    	OR 
                    	tl.is_personal = false
                     	OR
                        (tl.is_personal = true AND tl.id_seller = ".$_SESSION['uiid'].")
                     )";            	
                   // $sqlWherePart = " AND 1 != 1";
                } else {
                    assert(false);
                    
                }

                 /*
                self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
                self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
                  */
                self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
            }

            static private function _applyIsImidiate()
            {
                if(self::$mainSQL->filters['SHOW_AUCTION'] == 1 && self::$mainSQL->filters['SHOW_NO_AUCTION'] == 1)
                {
                    return;
                }elseif(self::$mainSQL->filters['SHOW_AUCTION'] == 1 && self::$mainSQL->filters['SHOW_NO_AUCTION'] == 0){
                    $sqlWherePart = " AND tl.is_auction = true";
                }elseif(self::$mainSQL->filters['SHOW_AUCTION'] == 0 && self::$mainSQL->filters['SHOW_NO_AUCTION'] == 1){
                    $sqlWherePart = " AND tl.is_auction = false";
                }elseif(self::$mainSQL->filters['SHOW_AUCTION'] == 0 && self::$mainSQL->filters['SHOW_NO_AUCTION'] == 0){
                    //$sqlWherePart = " AND 1 != 1";
                }else{
                    assert(false);
                }

                //                self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
                //                self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);

                self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
            }

            static private function _applyOwnTrades()
            {
                $sqlWherePart = "";
                if(self::$mainSQL->filters['SHOW_MY_TRADES'] == 1 && self::$mainSQL->filters['SHOW_FOREIGN_TRADES'] == 1)
                {
                    return;
                }elseif(self::$mainSQL->filters['SHOW_MY_TRADES'] == 1 && self::$mainSQL->filters['SHOW_FOREIGN_TRADES'] == 0){
                    $sqlWherePart = " AND tl.id_seller = ".$_SESSION['uiid'];
                }elseif(self::$mainSQL->filters['SHOW_MY_TRADES'] == 0 && self::$mainSQL->filters['SHOW_FOREIGN_TRADES'] == 1){
                    $sqlWherePart = " AND tl.id_seller != ".$_SESSION['uiid'];
                }elseif(self::$mainSQL->filters['SHOW_MY_TRADES'] == 0 && self::$mainSQL->filters['SHOW_FOREIGN_TRADES'] == 0){
                    return;
                    //$sqlWherePart = " AND 1 != 1";
                }else{
                    assert(false);
                }

                //                self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
                //                self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
                self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
            }

            static private function _applyOwnBeds()
            {
                if(self::$mainSQL->filters['SHOW_MY_BIDS'] == 1 && self::$mainSQL->filters['SHOW_FOREIGN_BIDS'] == 1)
                {
                    return;
                }elseif(self::$mainSQL->filters['SHOW_MY_BIDS'] == 1 && self::$mainSQL->filters['SHOW_FOREIGN_BIDS'] == 0){
                    $sqlWherePart = " AND tb.id_userinstance_bid = ".$_SESSION['uiid'];
                }elseif(self::$mainSQL->filters['SHOW_MY_BIDS'] == 0 && self::$mainSQL->filters['SHOW_FOREIGN_BIDS'] == 1){
                    $sqlWherePart = " AND tb.id_userinstance_bid != ".$_SESSION['uiid'];
                }elseif(self::$mainSQL->filters['SHOW_MY_BIDS'] == 0 && self::$mainSQL->filters['SHOW_FOREIGN_BIDS'] == 0){
                    $sqlWherePart = '';
                    //$sqlWherePart = " AND 1 != 1";
                }else{
                    assert(false);
                }

                //                self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
                //                self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
                self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
            }

            static private function _applySortType()
            {
                $baseWherePart = " AND tt.id_trade_on_category = ";

                switch(self::$mainSQL->sortType)
                {
                    case 'RESOURCES':
                        $baseWherePart .= "1\n";
                        if(self::$mainSQL->sortName != "ALL")
                        {
                            $baseWherePart .= "
                         AND
                         tl.id_article IN
                         (
                            SELECT
                                id_attributename
                            FROM
                                ds_attributename
                            WHERE
                                attributename IN ('".implode("','", self::$mainSQL->sortName)."')
                        )";
                        }
                        break;
                    case 'UNITS':
                        $baseWherePart .= "2\n";
                        if(self::$mainSQL->sortName != "ALL")
                        {
                            $baseWherePart .= "
                         AND EXISTS
                            (
                                SELECT
                                    u.id_unit
                                FROM
                                    ds_unit as u
                                WHERE
                                    u.unitname IN ('".implode("','", self::$mainSQL->sortName)."')
                                    AND
                                    u.id_unit = tl.id_article
                            ) ";
                        }
                        break;
                    case 'ARTEFACTS':
                        $baseWherePart .= "3\n";
                        if(self::$mainSQL->sortName != "ALL")
                        {
                            $baseWherePart .= "
                         AND
                         tl.id_article IN ('".implode("','", self::$mainSQL->sortName)."')
                    ";
                        }
                        break;
                    default:
                        DS_Error('Unknown SortType for form SQL = '.self::$mainSQL->sortType);
                    }

                    //                    self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $baseWherePart);
                    //                    self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $baseWherePart);
                    self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $baseWherePart);
                }

                static private function _applyBuyBy()
                {
                    $baseWherePart = " AND
                        (
                            SELECT
                                CASE
                                WHEN tt2.id_trade_by_category = 1 THEN an.attributename
                                WHEN tt2.id_trade_by_category = 4 THEN m.money_name
                                END
                            FROM
                                ds_trade_type AS tt2
                                LEFT JOIN ds_attributename AS an ON (an.id_attributename = tt2.id_trade_by_type)
                                LEFT JOIN ds_money AS m ON (m.id_money = tt2.id_trade_by_type)
                            WHERE tt2.id_tradetype = tt.id_tradetype
                        ) IN ('".implode("','", self::$mainSQL->buyBy)."') ";

                    //                    self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $baseWherePart);
                    //                    self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $baseWherePart);
                    self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $baseWherePart);
                }

                static private function _applyTrader()
                {
                    if(empty(self::$mainSQL->trader) || DE_WorldSettings::CreateForCurentWorld()->get('TRADE_ANONYMOUS'))
                    {
                        return;
                    }

                    $sqlFromPart = "
                    INNER JOIN ds_userinstance AS uitr ON (uitr.id_userinstance = tl.id_seller)
                    INNER JOIN ds_user AS utl ON (utl.id_user = uitr.id_user)";
                    $sqlWherePart = " AND upper(utl.username) = '".self::$mainSQL->trader."' AND NOT tl.is_anonymous ";

                    self::$mainSQL->addHrefValue('MAIN_SELECT', 'FROM', $sqlFromPart);
                    self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
                    //
                    //                    self::$mainSQL->addHrefValue('COUNT_ROWS', 'FROM', $sqlFromPart);
                    //                    self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
                    //
                    self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'FROM', $sqlFromPart);
                    self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
                }

                static private function _applyTradeScope()
                {
                    $sqlFromPart = '';
                    $sqlWherePart = '';

                    switch (self::$mainSQL->sortPlace)
                    {
                        case 'COMB_COMPLEX_TRADING':

                            $sqlFromPart = "INNER JOIN ds_comb AS sc ON (sc.id_comb = tl.id_seller_comb)";
                            $sqlWherePart = "
                    AND
                    sc.id_combcomplex = ".self::$mainSQL->comb->getComplexID()."
                    AND
                    tl.id_tradescope = 1";

                            break;
                        case 'RING_TRADING':

                            $sqlFromPart = "
                    INNER JOIN ds_comb AS sc ON (sc.id_comb = tl.id_seller_comb)
                    INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = sc.id_combcomplex)";

                            $sqlWherePart = "
                    AND
                    cc.id_ring = ".self::$mainSQL->comb->getCombRing()->getID()."
                    AND
                    tl.id_tradescope = 2";

                            break;
                        case 'INTERRING_TRADING':

                            $sqlWherePart = "
                    AND
                    tl.id_tradescope = 3";

                            self::_applyNonRVRUnitsAllowd(3);

                            break;
                        case 'ALLIANCE_TRADING':
                            $aliance = DE_Alliance::CreateFromUserInstanceID(self::$mainSQL->comb->getUserInstanceID());

                            if($aliance->isEmpty())
                            {
                                $sqlWherePart = "
                    AND
                    true = false
                    AND
                    tl.id_tradescope = 4
                    ";
                            }else{
                                $sqlWherePart = "
                    AND EXISTS
                    (
                        SELECT
                            uha.id_alliance
                        FROM
                            ds_userhasalliance uha
                        WHERE
                            uha.id_userinstance = tl.id_seller
                            AND
                            uha.id_alliance = ".$aliance->getID()."
                    )
                    AND
                    tl.id_tradescope = 4
                    ";

                                self::_applyNonRVRUnitsAllowd(4);
                            }

                            break;
                        default:
                            ds_error('Unknown tradescope for form SQL = '.self::$mainSQL->sortPlace);
                        }

                        self::$mainSQL->addHrefValue('MAIN_SELECT', 'FROM', $sqlFromPart);
                        //self::$mainSQL->addHrefValue('COUNT_ROWS', 'FROM', $sqlFromPart);
                        self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'FROM', $sqlFromPart);

                        self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
                        //self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
                        self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
                    }

                    static private function _applyNonRVRUnitsAllowd($tradescope)
                    {
                        assert(!empty($tradescope) && is_int($tradescope));
                        if(self::$mainSQL->nonRvrUnitsAllowd)
                        {
                            return ;
                        }

                        if(self::$mainSQL->sortType != 'UNITS')
                        {
                            return ;
                        }

                        assert(!empty($_SESSION['race']) && $_SESSION['race'] >0);
                        $noUnitsOfOtherRace = " AND tl.id_trade_lot NOT IN (

                                                        SELECT
                                                            trLots.id_trade_lot
                                                        FROM
                                                            ds_trade_lots AS trLots
                                                        JOIN
                                                            ds_trade_type AS trType ON trType.id_tradetype = trLots.id_tradetype
                                                        JOIN
                                                            ds_userinstance AS uIns ON uIns.id_userinstance = trLots.id_seller
                                                        WHERE
                                                            id_tradescope = $tradescope AND
                                                            uIns.id_race <> ".$_SESSION['race']." AND
                                                            trType.id_trade_on_category = 2
                                        ) ";

                        //                        self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $noUnitsOfOtherRace);
                        //                        self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $noUnitsOfOtherRace);
                        self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $noUnitsOfOtherRace);
                    }

                    static public function _applyTrands()
                    {
                        // Мотрим, включены ли торговые корридоры
                        if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TRANDS'))
                        {
                            // На сколько процетнов могут отклоняться курсы от текущего корридора
                            $enableTrande = (int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_ENABLE_TRAND_IN_LOTS');

                            if ($enableTrande == 0) {
                                return;
                            }
                            assert($enableTrande > 0);

                            $sqlWherePart = "
                        AND
                       -- Отклонение курса от торгового корридора
                       abs( 1 -
                                -- Текущая цена лота
                                ((CASE
                                    tb.price_bid
                                 WHEN
                                    0
                                 THEN
                                     (SELECT tl.best_price)
                                  ELSE
                                     (SELECT tl.startprice)
                                   END)/tl.amount)
                                 /
                                 -- Курс в нане того товара, который продаем
                                 (CASE id_trade_by_category
                                     WHEN 4 --money
                                          THEN
                                            -- Курс в СЕРЕ того товара, который продаем
                                             (CASE
                                                    -- Resourses
                                                    WHEN tt.id_trade_on_category = 1
                                                        THEN
                                                        (
                                                        SELECT a.value FROM ( SELECT *
                                                        FROM ds_trade_resorce_course_data as trcd
                                                        WHERE
                                                               trcd.id_resource = tl.id_article
                                                                AND
                                                                trcd.id_tradecourse = (SELECT
                                                                                            id_tradecourse
                                                                                        FROM
                                                                                            ds_trade_course
                                                                                        WHERE
                                                                                            id_tradecategory = 4
                                                                                            AND
                                                                                            id_article = tt.id_trade_by_type
                                                                                         )
                                                                AND
                                                                trcd.id_world = tl.id_world
                                                                ORDER BY trcd.add_date DESC
                                                                 ) as a
                                                                LIMIT 1
                                                       )
                                                   -- Units
                                                    WHEN tt.id_trade_on_category = 2
                                                        THEN
                                                        (
                                                        SELECT a.value FROM ( SELECT *
                                                        FROM ds_trade_units_cours_data as trucd
                                                        WHERE     trucd.id_unit = tl.id_article
                                                                AND
                                                                trucd.id_tradecourse = (SELECT
                                                                                            id_tradecourse
                                                                                        FROM
                                                                                            ds_trade_course
                                                                                        WHERE
                                                                                            id_tradecategory = 4
                                                                                            AND
                                                                                            id_article = tt.id_trade_by_type
                                                                                         )
                                                                AND
                                                                trucd.id_world = tl.id_world
                                                                ORDER BY trucd.add_date DESC
                                                             ) as a
                                                                LIMIT 1
                                                    )

                                                    -- Artefacts
                                                    WHEN tt.id_trade_on_category = 3
                                                        THEN
                                                        (
                                                            SELECT a.value FROM ( SELECT *
                                                        FROM ds_trade_artefact_course_data as tracd
                                                        WHERE tracd.id_artefact = tl.id_article
                                                                AND tracd.id_tradecourse = (SELECT
                                                                                            id_tradecourse
                                                                                        FROM
                                                                                            ds_trade_course
                                                                                        WHERE
                                                                                            id_tradecategory = 4
                                                                                            AND
                                                                                            id_article = tt.id_trade_by_type
                                                                                         )
                                                                AND
                                                                tracd.id_world = tl.id_world
                                                                ORDER BY tracd.add_date DESC
                                                                ) as a
                                                                LIMIT 1
                                                           )
                                                            END)
                                            ELSE -- resources

                                            -- Курс в НАНЕ того товара, который продаем
                                             (CASE
                                                    -- Resourses
                                                    WHEN tt.id_trade_on_category = 1
                                                        THEN
                                                        (
                                                        SELECT a.value FROM ( SELECT *
                                                        FROM ds_trade_resorce_course_data as trcd
                                                        WHERE
                                                               trcd.id_resource = tt.id_trade_on_type
                                                                AND
                                                                trcd.id_tradecourse = 2
                                                                AND
                                                                trcd.id_world = tl.id_world
                                                                ORDER BY trcd.add_date DESC
                                                                 ) as a
                                                                LIMIT 1
                                                       )
                                                   -- Units
                                                    WHEN tt.id_trade_on_category = 2
                                                        THEN
                                                        (
                                                        SELECT a.value FROM ( SELECT *
                                                        FROM ds_trade_units_cours_data as trucd
                                                        WHERE     trucd.id_unit = tl.id_article
                                                                AND
                                                                trucd.id_tradecourse = 2
                                                                AND
                                                                trucd.id_world = tl.id_world
                                                                ORDER BY trucd.add_date DESC
                                                             ) as a
                                                                LIMIT 1
                                                    )

                                                    -- Artefacts
                                                    WHEN tt.id_trade_on_category = 3
                                                        THEN
                                                        (
                                                            SELECT a.value FROM ( SELECT *
                                                        FROM ds_trade_artefact_course_data as tracd
                                                        WHERE tracd.id_artefact = tl.id_article
                                                                AND tracd.id_tradecourse = 2
                                                                AND
                                                                tracd.id_world = tl.id_world
                                                                ORDER BY tracd.add_date DESC
                                                                ) as a
                                                                LIMIT 1
                                                           )
                                                            END)/
                                                            -- Курс в нане того товара, за который продаем
                                                           (
                                                                    SELECT a.value FROM ( SELECT *
                                                                    FROM ds_trade_resorce_course_data as trcd
                                                                    WHERE
                                                                           trcd.id_resource = tt.id_trade_by_type
                                                                            AND
                                                                            trcd.id_tradecourse = 2
                                                                            AND
                                                                            trcd.id_world = tl.id_world
                                                                            ORDER BY trcd.add_date DESC
                                                                             ) as a
                                                                            LIMIT 1
                                                                   )

                                               END
                                       )
                        ) < 0.".$enableTrande;

                            //self::$mainSQL->addHrefValue('MAIN_SELECT', 'WHERE', $sqlWherePart);
                            //self::$mainSQL->addHrefValue('COUNT_ROWS', 'WHERE', $sqlWherePart);
                            self::$mainSQL->addHrefValue('ALL_LOT_IDS', 'WHERE', $sqlWherePart);
                            return;
                        }
                    }

                }

                ?>