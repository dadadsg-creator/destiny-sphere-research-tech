<?php $CVS_Version = '$Revision$ ; $Date$';

// обработчик клика на €чейке соты
class UA_GetBuildMenu extends UA_Building 
{
    public $tab;
    public $orderby;
    public $id_message;
    public $reply_to;
    public $forward_to;

    public $sort_place;
    public $sort_type;
    public $sort_name;
    public $immediate;

    public $page;
    public $own_trades;
    public $own_bets;
    public $buy_by;
    public $order;
    public $days;
    
    public $personal;
    public $traderSearch;
    
    public $art_sort = array();
    
    public $fromDays;
    public $oponents;
    public $combs;
    public $status;
    public $winner;
    
    public $messageType;
    public $messageID;
    public $name;
    
    public $colony;
    public $spyActionTypes;
    public $dateFrom;
    public $dateTo;
    
    
    public $cellnumber = 0;

    function UA_GetBuildMenu () 
    {
        UA_Building::UA_Building ();
    }

    // возможные параметры
    function checkLocalParams () 
    {   
        
        if ($this->root->get_attribute("c")=='warbase') {
            $sql = "select c.cellnumber from ds_cell c, ds_attachedbuilding ab
                where c.id_cell=ab.id_cell and c.id_comb=".$this->comb."
                and ab.id_building=26";
            $res = $this->common->getResultArray("", $sql);
            $this->cellnumber = (int)$res[0]['cellnumber'];
        }
        else $this->cellnumber = (int)$this->root->get_attribute("c");

        if($this->cellnumber != 0)
        {
            $cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec($this->cellnumber, true);

            $this->id_cell = $cellData['CELL_ID'];
            assert(is_int($this->id_cell) && $this->id_cell > 0);

            $this->groundid = $cellData['GROUND_TYPE_ID'];
            assert(is_int($this->groundid) && $this->groundid > 0);

            if(!is_null($cellData['ATTACHED_BUILDING_ID']))
            {
                $this->attachedbid = $cellData['ATTACHED_BUILDING_ID'];
                assert(is_int($this->attachedbid) && $this->attachedbid >= 0);

                $this->bid = $cellData['BUILDING_ID'];
                assert(is_int($this->bid) && $this->bid >= 0);

                $_SESSION['UA_LAST_BID'] = $this->bid;
            }
        }

        $this->tab = $this->root->get_attribute ( "tab" );
        
        $this->orderby = $this->root->get_attribute ( "sort" );
        $this->id_message = $this->root->get_attribute ( "id_message" );

        $this->sort_place = $this->root->get_attribute ( "sort_place" );
        $this->sort_type = $this->root->get_attribute ( "sort_type" );
        
        $this->sort_name = explode(",", $this->root->get_attribute("sort_name")); //!!
        if(count($this->sort_name) == 1 && empty($this->sort_name[0]))
        {
            $this->sort_name = array();
        }
        
        $this->buy_by = explode(",", $this->root->get_attribute("buy_by")); //!!
        if(count($this->buy_by) == 1 && empty($this->buy_by[0]))
        {
            $this->buy_by = array();
        }
        
        $this->immediate = $this->root->get_attribute("immediate");

        $this->page = $this->root->get_attribute("page");
        $this->own_trades = $this->root->get_attribute("own_trades");
        $this->own_bets = $this->root->get_attribute("own_bets");
        
        $this->order = $this->root->get_attribute("order");
        $this->days = $this->root->get_attribute("days");
        
        $this->traderSearch = $this->root->get_attribute("trader");
        $this->personal = (int)$this->root->get_attribute("personal");
        
        // ћеню хранилища артефактов
        $this->art_sort['type'] = $this->root->get_attribute("sorttype");
        $this->art_sort['size'] = $this->root->get_attribute("sortsize");
        $this->art_sort['scope'] = $this->root->get_attribute("sortscope");
        
        // ћеню обзора списка сражений
        $this->fromDays = $this->root->get_attribute("fromd");
        $this->oponents = $this->root->get_attribute("opp");
        $this->combs = $this->root->get_attribute("sot");
        $this->status = $this->root->get_attribute("stat");
        $this->winner = $this->root->get_attribute("winner");
        
        // меню почты
        $this->messageType = $this->root->get_attribute("type");
        $this->messageID = (int)$this->root->get_attribute("id");
        $this->name = $this->root->get_attribute("name"); // + в шпионаже есть такой же атрибут
        
        // шпионаж
        $this->colony = explode(',', $this->root->get_attribute("colony"));
        if(count($this->colony) == 1 && empty($this->colony[0]))
        {
            $this->colony = array();
        }
        $this->spyActionTypes = explode(',', $this->root->get_attribute("types"));
        if(count($this->spyActionTypes) == 1 && empty($this->spyActionTypes[0]))
        {
            $this->spyActionTypes = array();
        }
        $this->dateFrom = (int)$this->root->get_attribute("datefrom");
        $this->dateTo = (int)$this->root->get_attribute("dateto");
        
        return true;
    }

    function checkAction()
    {
        global $params;
        global $common;
        
        
        if($this->id_cell > 0)
        {
            // не разрешаем открывать меню в центральной и нейтралдьной соте, если врота закрыты
            $sql = "
                SELECT
                    c.id_celltype
                FROM
                    ds_cell AS c
                WHERE
                    c.id_cell = ".$this->id_cell."
                ";
            $res = $this->common->getResultArray("", $sql, "", "", "");
            assert(empty($this->common->errormsg));
            assert(count($res) == 1);
        
            $cellType = $res[0]['id_celltype'];
        
            if($cellType == 2 || $cellType == 3)
            {
                $comb = DE_Comb::CreateFromID($this->comb);
            
                if(!$comb->haveOpenCentralGate())
                {
                    $this->errormsg .= " CENTRAL GATE CLOSED";

                    return false;
                }
            }
        }
        
        
        return true;
    }

    // выполн€ем действие
    function performAction ()
    {

        global $params;
        global $common;

        // если не передано id_attachedbuilding (клик по пустой €чейке)
        if($this->attachedbid == "")
        {
            // если не передано id €чейки или закладка
            if($this->id_cell != "" && $this->tab != "")
            {
                $this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::performAction1";
                return false;

            } else if($this->id_cell != ""){ // если передано id €чейки

                // провер€ем сво€ ли сота
                if(!$this->isOwnComb($this->id_cell))
                {
                    return false;
                }

                // вызываем меню со списком доступных дл€ строительства зданий
                $panel = new PN_AllowBuildings;
                if(!$panel)
                {
                    $this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::performAction3";
                    return false;
                }

                // передаем в панель параметры
                $panel->world = $this->world;
                $panel->ring = $this->ring;
                $panel->combcomplex = $this->combcomplex;
                $panel->combnumber = $this->combnumber;
                $panel->comb = $this->comb;
                $panel->celltype = $this->celltype;
                $panel->cellnum = $this->cellnum;
                $panel->groundid = $this->groundid;
                $panel->id_cell = $this->id_cell;

                if ( $this->bid != "" ) $panel->bid = $this->bid;
                if ( $this->attachedbid != "" ) $panel->attachedbid = $this->attachedbid;
                if ( $this->tab != "" ) $panel->tab = $this->tab;

                if(!$panel->getPanel())
                {
                    $this->errormsg .= $panel->errormsg;
                
                    return false;
                }
                
                return true;

            } else { // если нет закладки, ошибка
                if(!$this->tab)
                {
                    $this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::performAction4";
                    return false;
                }
            }
        } else { // если клик по зданию
            // сво€ ли сота
            if(!$this->isOwnComb($this->id_cell))
            {
            
                return false;
            }

            // должно быть id_building
            if(!$this->bid)
            {
                $this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::performAction5";
                return false;
            }
            
            // если не указано какую закладку открывать, берем дефолтовую
            if(empty($this->tab))
            {
                $this->tab = GL_DefaultBuildingTab::Get($this->bid);
            }
            
            assert(!empty($this->tab));

            // если знание не активно или строитс€, доступно только информаци€ по зданию
            $sql = sprintf ( "select underconstruction,
                    active
                    from ds_attachedbuilding
                    where id_attachedbuilding = %d",
                    $this->attachedbid );

            $res = $common->getResultArray ( "underconstraction_active_id_attachedbuiding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
            if ( !$res ) {
                $this->errormsg = "SYSTEM ERROR UA_GetBuildMenu::performAction7";
                return false;
            }

            if ( $res[0]['underconstruction'] == 't' || $res[0]['active'] == 'f' )
            {
                $this->tab = 'PN_BuildingInfo';
            }

        }

        // если закладка имеет дочерние закладки, указываем какую из них выбрать
        if ( $this->tab == 'Message' ) $this->tab = 'PN_MessageOverview';
        if ( $this->tab == 'Alliance' ) $this->tab = 'PN_AllianceManagement';
        if ( $this->tab == 'Army' ) $this->tab = 'PN_ArmyOverview';

        if ($this->tab == 'PN_TransferResource' )
        {

            $sql = sprintf ( "select co1.id_comb
                    from ds_comb co,
                    ds_colony cl left join ds_comb co1
                    on cl.id_comb_to =co1.id_comb
                    where co.id_comb = cl.id_comb_from
                    and co.id_comb = %d
                    and co1.id_userinstance = %d",
                    $this->comb,
                    $params['uiid'] );

            $res = $common->getResultArray ( "has_colony_id_comb_id_userinstance_".$this->comb."_".$params['uiid'], $sql, "", $params["uiid"], "LOCAL_CACHE" );
            if($res === false) 
            {
                $this->errormsg = "SYSTEM ERROR GetBuildMenu::performAction7";
                return false;
            }

            if ( sizeof ( $res ) == 0 ) $this->tab == 'PN_BuildingInfo';
        }
        
        //«атыка, вобще это нужно править на клиенте. Ёто дл€ табов которые пеердаютьс€ напр€мую
        $testTMP = strpos($this->tab, 'PN_');
        if(!is_int($testTMP))
        {
            $this->tab = 'PN_'.$this->tab;
        }
        
        // дл€ обновлени€ менюшки, в случае ошибки
        $_SESSION['UA_LAST_TAB'] = $this->tab;
        
        $panel = new $this->tab;
        assert(is_object($panel));      

        // передаем параметры в панель
        if ( $this->world != "") $panel->world = $this->world;
        if ( $this->combcomplex != "" ) $panel->combcomplex = $this->combcomplex;
        if ( $this->comb != "" ) $panel->comb = $this->comb;
        if ( $this->ring != "" ) $panel->ring = $this->ring;
        if ( $this->celltype != "" ) $panel->celltype = $this->celltype;
        if ( $this->cellnum != "" ) $panel->cellnum = $this->cellnum;
        if ( $this->groundid != "" ) $panel->groundid = $this->groundid;
        if ( $this->id_cell != "" ) $panel->id_cell = $this->id_cell;
        if ( $this->bid != "" ) $panel->bid = $this->bid;
        if ( $this->buildingname != "" ) $panel->buildingname = $this->buildingname;
        if ( $this->attachedbid != "" ) $panel->attachedbid = $this->attachedbid;
        if ( $this->tab != "" ) $panel->tab = $this->tab;
        if ( $this->orderby != "" ) $panel->orderby = $this->orderby;
        if ( $this->id_message != "" ) $panel->id_message = $this->id_message;
        if ( $this->reply_to != "" ) $panel->reply_to = $this->reply_to;
        if ( $this->forward_to != "" ) $panel->forward_to = $this->forward_to;
        if ( $this->sort_place != "" ) $panel->sort_place = $this->sort_place;
        if ( $this->sort_type != "" ) $panel->sort_type = $this->sort_type;
        if ( $this->sort_name != "" ) $panel->sort_name = $this->sort_name;
        if ( $this->immediate != "" ) $panel->immediate = $this->immediate;
        if ( $this->page != "" ) $panel->page = $this->page;
        if ( $this->own_trades != "" ) $panel->own_trades = $this->own_trades;
        if ( $this->own_bets != "" ) $panel->own_bets = $this->own_bets;
        if ( $this->buy_by != "" ) $panel->buy_by = $this->buy_by;
        if ( $this->order != "" ) $panel->order = $this->order;
        if ( $this->days != "" ) $panel->days = $this->days;
        
        $panel->art_sort = $this->art_sort;
        $panel->personal = $this->personal;
        $panel->traderSearch = $this->traderSearch;
        
        $panel->fromDays = $this->fromDays;
        $panel->oponents = $this->oponents;
        $panel->combs = $this->combs;
        $panel->status = $this->status;
        $panel->winner = $this->winner;
        
        $panel->messageType = $this->messageType;
        $panel->messageID = $this->messageID;
        $panel->name = $this->name;
        
        $panel->colony = $this->colony;
        $panel->spyActionTypes = $this->spyActionTypes;
        $panel->dateFrom = $this->dateFrom;
        $panel->dateTo = $this->dateTo;
        
        // получаем панель
        if(!$panel->getPanel())
        {
            $this->errormsg .= $panel->errormsg;
            return false;
        }

        $_SESSION["s_xmldata"] = "";
    
        return true;
    }
    
    public function setBID($bid)
    {
        assert(is_int($bid) && $bid >= 0);
            
        $this->bid = $bid;
    }
        
    public function setTAB($tab)
    {
        $this->tab = $tab;
    }
}

?>