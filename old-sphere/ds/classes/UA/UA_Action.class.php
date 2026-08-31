<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/07/01 08:56:38 $';
/**
 * Родителтьский класс для всех астионов
 *
 * Использует:
 *  $GLOBALS['params']
 *  $_SESSION["comb"]
 *  $GLOBALS['common']
 *  $_SESSION["lang"]
 */

class UA_Action extends GameBase {

    /** ОБъект дляработы с XML */
    public $root = '';

    /** ID мира  */
    public $world = 0;

    /** ID кольца */
    public $ring = 0;

    /** ID сотовго комплекса */
    public $combcomplex = 0;

    /** ID соты */
    public $comb = 0;

    /** ????  */
    public $celltype = 0;

    /** Номер соты в комлексе */
    public $combnumber = 0;

    /** Текст сообщения об ошибке послденей */
    public $errormsg = '';

    /** ??? */
    public $cellnum = 0;

    /** Класс для отправки сиситемных сообщений */
    public $sysmsg = '';

    /** ??? */
    public $uat = 0;

    /** ???? */
    public $res = '';

    /** Время начала генерируемого ивента */
    public $starttime = '';
    public $starttimeObject;

    /** Время окончания генерируемого ивента */
    public $endtime = '';
    public $endtimeObject;

    /** ID типа создаваемого ивента */
    public $eventid = 0;

    /** Массив ассоциативный с параметрами создаваемого ивента */
    public $eventparams = array();

    /** ID созданного экземпляра ивента */
    public $eqid = 0;

    /** ??? */
    public $noscan = '';

    /** Список параметров  */
    public $params = array ();

    /** UIID пользователя с которым работаем */
    public $uiid = 0;

    /** ID языка текущего */
    public $langId = 0;

    /**  действия, не изменяющие условия доступности других действий */
    public $abpa = array ( "getbuildmenu", "navigate", "empty", "activatebuild",
    "cancelactivatebuild", "research", "cancelresearch",
    "complexview", "archeologyteam", "dropartefact",
    "detachartefact", "attachartefact", "createarmy",
    "transferunit", "sendmessage", "createalliance",
    "deletemessage", "joinalliance", "leavealliance",
    "expelalliance", "attack", "cancelattack",
    "getbattleinfo", "defendcanceldefend",
    "colonize", "changeleader", "changepassword" );


    public $common = NULL;

    public $worldSettings = NULL;

    public $local = NULL;

    public $haveProcessedErrors = false;

    /**
     * Конструктор класса
     */
    function UA_Action () {
    

        GP_SystemStatistic::AddToArray('USERACTION', 'HAHDLERS_STARTED', get_class($this));
        

        /** Конструтор родителя */
        $this->GameBase();

        $this->common = DS_Common::CreateForCurentDB();
        $this->worldSettings = DE_WorldSettings::CreateForCurentWorld();

        //Экземпляр класса для работы с сиитемными сообщениями
        //$this->sysmsg = SystemMessages::GetInstance();

        //Устанавливаем params
        $this->setParams ( $GLOBALS['params'] );

        //Устанваливаем $this->comb
        $this->setComb ( $_SESSION["comb"] );

        //Устанавливаем $this->uiid
        $this->setUIID ( $GLOBALS['params'] );

        //Устанавливаем $this->langId
        $this->setLangId ( $_SESSION["lang"] );

        //Устанавливаем $this->root
        $this->setRoot ();

        //CONVERTATION OF THIS METHOD IS NOT COMPLITED!!!!!
        $this->world = (int)$_SESSION["world"];
        $this->ring = (int)$_SESSION["ring"];
        $this->combcomplex = (int)$_SESSION["combcomplex"];
        $this->celltype = $_SESSION["celltype"];
        $this->combnumber = (int)$_SESSION["combnumber"];

        // КУСОЧЕК ОТ ЗАХВАТЫВАЕМЫХ КООНИЙ
        // Проверяем, не захватили ли колонию, на которой мы находимся
        if (DE_Comb::CreateFromID($this->comb)->getIsCapture() && (int)$this->uiid != (int)DE_Comb::CreateFromID($this->comb)->getUserInstanceID()) {
            // Получаем id домашней соты
            $combsCollection = DE_UserInstance::CreateFromID((int)$this->uiid)->getCombsCollection();
            $combsCollection->resetNext();
            while($combH = $combsCollection->next())
            {
                if ($combH->getRingNumber() != 4) {

                    $idCombHome = (int)$combH->getCombID();
                }
            }
            assert($idCombHome > 0);
            
            $comb = DE_Comb::CreateFromID($idCombHome);

            // меняем координаты в сессии
            $_SESSION["celltype"] = 1;
            $_SESSION["combcomplex"] = $comb->getComplexNumber();
            $_SESSION["ring"] = $comb->getRingNumber();
            $_SESSION['comb'] = $idCombHome;
            $_SESSION["combnumber"] = $comb->getNumber();


            $refr = new UA_Refresh;

            $refr->langId = 1;
            $refr->uiid = $comb->getUserInstanceID();
            $refr->comb = $comb->getID();
            $refr->celltype = 1;
            $refr->combnumber = $comb->getNumber();
            $refr->combcomplex = $comb->getComplexNumber();
            $refr->ring = $comb->getRingNumber();
            $refr->world = 1;
            $refr->noscan = 1;
            $refr->sysmsg = "";

            $notCellInfo = false;

            $_SESSION["complexview"] = "";
            $_SESSION["complex_ring"] = "";
            $_SESSION["complex_complex"] = "";

            $userName = $_SESSION['s_login'];
            $combData = DS_Comb::GetByID($_SESSION['comb']);
            $combCoords = $combData['combname'].'('.$combData['ringnumber'].'.'.$combData['complexnumber'].'.'.$combData['combnumber'].')';
            $worldData = DS_World::GetByID($_SESSION['world']);
            $worldName = $worldData['worldname'];


            $textD = "nick=Имя: $userName&complex=Сота: $combCoords&world=Мир: $worldName";

            SU_OutputHandler::Singleton()->addReadyString($textD);

            $refr->performAction($notCellInfo, 1 , 1, 1);

            return true;
        }

    }//END OF UA_Action ()

    /**
     * Базовый метод invariant. проверяет состояние данного класса
     * Используеться потомками.
     *
     * Параметры:
     */
    function invariant ( $file, $line ) {



        return true;
    }//END OF METHOD invariant();

    /**
     * Устанавливаем значение $this->root
     *
     * Требует уже установленный атриьбут: $this->paerams
     */
    function setRoot () {
        assert ( !empty($this->params) );
        assert ( is_array($this->params) );
        assert ( array_key_exists ( "root", $this->params ) );
        assert ( !empty ( $this->params["root"] ) );
        assert ( is_object ( $this->params["root"] ) );
        //assert ( is_a ( $this->params["root"], "????") )
        $this->root = $this->params["root"];
    }//END OF METHOD setRoot()

    /**
     * Устанавливаем значение $this->common
     * Параметры:
     * $common - объект класса DS_Common
     */
    function setCommon ( $common ){
        assert($common instanceof DS_Common);
        $this->common = $common;
        return true;
    }//END OF METHOD setCommon($common)

    /**
     * Устанавливаем значение $this->params
     * Параметры:
     * $params - массив с параметрами пользователя для которого мы тисполняем
     * ивент
     */
    function setParams ( $params )
    {
        assert ( is_array ( $params ) );
        assert ( count ( $params ) > 0 );
        $this->params = $params;
        return true;
    }//END OF METHOD setParams($params)

    /**
     * Устанавливаем значение ID соты
     * Параметры:
     * $id - целочисленное ID соты не равный 0
     */
    function setComb($id)
    {
        if($id instanceof DE_Comb)
        {
            $this->comb = (int)$id->getID();
        }else{
            settype ( $id, 'integer' );
            assert ( $id > 0 );
            $this->comb = $id;
        }

        return true;
    }//END OF METHOD setComb ( $id )

    /**
     * Устанавливаем значение $this->uiid
     * Параметры:
     * $params - массив с параметрами пользователя для которого мы тисполняем
     * ивент
     */
    function setUIID ( $params ) {
        assert ( !empty ( $params ) );
        assert ( is_array ( $params ) );
        assert ( count ( $params ) > 0 );
        assert ( array_key_exists ( "uiid", $params ) );
        settype ( $params['uiid'], 'integer' );

        $this->uiid = $params['uiid'];

        return true;
    }//END OF METHOD setUIID($params)

    /**
     * Метод занимаеться установкой язфка текущего
     *
     * Для свой работы требует что бы уже был устанволен $this->common
     * Параметры:
     * $langId - ID религии.Ц елое не равное нулю.
     *
     */
    function setLangId ( $langId )
    {
        settype ( $langId, 'integer' );
        assert ( $langId > 0 );

        $this->langId = $langId;

        return true;
    }//END OF METHOD setLangId ($langId)

    /** Пока оставлен для совместимости. Скоро сносим */
    function logResponse ( $response )
    {

    }//END OF METHOD logResponse ( $response )

    // проверка общих параметров
    function checkGlobalParams ()
    {
        if (in_array($_SESSION['s_login'], $GLOBALS['USERS_FOR_LOG_PROTOCOL']))
        
        assert ( !empty ( $this->root->tagname ) );
        assert ( is_array ( $this->abpa ) );

        if ( !in_array ( $this->root->tagname, $this->abpa ) )
        {
            unset ( $_SESSION["allow_buildings"] );
            unset ( $_SESSION["allow_units"] );
        }

        return true;
    }

    /**
     * Метод для логирования ошибок обнаруженных при работе экшенов
     * На данный момент бесполезна, оставлена для совместимости со тарой вресией
     */
    function actionResult ( $idactiontype, $msg )
    {
        return true;
    }

    /**  методы, которые переопределяет каждый класс-обработчик */
    function checkLocalParams ()
    {
        return true;
    }

    function checkAction ()
    {
        return true;
    }

    function performAction ()
    {
        return true;
    }


    /**
     * Обновление информации по соте.
     *
     * Использует:
     * $_SESSION['cells'] - список ячеек
     *
     * Парметры:
     * $noscan - не обрабатывать события
     * $cell - передавать ячейки соты
     * $full - смотреть изменился ли внешний вид соты
     * $combres - смотреть изменились ли данные о ресурсах в наличии
     */
    function refresh ( $noscan = "", $cell = "", $full = "", $combres= "", $notCellInfo = false )
    {
        $refr = new UA_Refresh;

        if ($full == 1)
        {
            $refr->full = 1;
        }

        if ($combres == 1)
        {
            $refr->combres = 1;
        }

        $refr->langId = $this->langId;
        $refr->uiid = $this->uiid;
        $refr->comb = $this->comb;
        $refr->celltype = $this->celltype;
        $refr->combnumber = $this->combnumber;
        $refr->combcomplex = $this->combcomplex;
        $refr->ring = $this->ring;
        $refr->world = $this->world;
        $refr->noscan = $noscan;
        $refr->sysmsg = &$this->sysmsg;

        if ( $cell != "" ) $refr->cell = 1;

        if (isset($_SESSION['cells']))
        {
            if ( $_SESSION['cells'] == 1 ) $refr->cell = 1;
            unset($_SESSION['cells']);
        }

        if ( $noscan != 1 && $noscan != 2 )
        {
            if ( !$refr->checkAction())
            {
                $this->errormsg = $refr->errormsg;
                return false;
            }
        }

        if (!$refr->performAction($notCellInfo))
        {
            $this->errormsg = $refr->errormsg;
            return false;
        }

        return true;
    }//END OF METHOD refresh ( $noscan = "", $cell = "", $full = "", $combres= "" )

    /**
     * Занесенеи ивента в базу
     */
    function createEvent ($eventName, $inputParticipant = '')
    {
        $this->createEventParams();

        if(empty($this->starttime))
        {
            $dateFrom = GB_TypeFactory::CreateBase('GB_Date', time());
        }else{
            if($this->starttimeObject instanceof GB_Date)
            {
                $dateFrom = $this->starttimeObject;
            }else{
                $dateFrom = GB_TypeFactory::CreateBase('GB_Date', $this->starttime);
            }

        }

        if(empty($this->endtime))
        {
            $dateTo = GB_TypeFactory::CreateBase('GB_Date', time());
        }else{
            if($this->endtimeObject instanceof GB_Date)
            {
                $dateTo = $this->endtimeObject;
            }else{
                $dateTo = GB_TypeFactory::CreateBase('GB_Date', $this->endtime);
            }
        }

        $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);

        if(!empty($inputParticipant))
        {
            assert($inputParticipant>0);
            $participants[] = $inputParticipant;
        }else{
            $participants[] = $this->uiid;
        }

        $this->eqid = (int)DE_EventQueue::CreateInDB($eventName, $participants, $this->eventparams, $datePeriod)->getID();
        assert($this->eqid>0);

        return true;
    } //END OF METHOD setEvent ()


    /**
     * Проверка принадлежности ячейки своей соте
     *
     * Парметры:
     * $id_cell - ID ячейки
     */
    function isOwnComb ( $id_cell )
    {
        settype($id_cell, 'integer');
        assert($id_cell>0);

        /*if(!DE_CombCells::CreateFromCombID((int)$this->comb)->haveCellID($id_cell))
        {
        ds_error("You are try to access not your cell");
        };*/

        /*$cellData = DS_Comb::GetCellDataByCombAndCell((int)$this->comb, $id_cell);
        if(is_array($cellData) && count($cellData) == 0)
        {
        ds_error("You are try to access not your cell");
        }*/


        return true;
    }//END OF isOwnComb ( $id_cell )


    protected function _vocationModeAlert()
    {
        $messparams = array();
        DE_SystemMessages::AddMessageFromAction(__CLASS__, 'VACATION_ACTION_NOTICE', $messparams, 0);
    }

    protected function _shildModeAlert()
    {
        $powerShild = DE_PowerShild::CreateFromUserInstance((int)$this->uiid);

        $timeDelta = DE_UserInstance::CreateFromID((int)$this->uiid)->getUser()->getTimeDelta();

        $messparams = array(
        0 => $powerShild->getEndDate()->getFormatedFull($timeDelta)
        );

        DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_ACTION_NOTICE', $messparams, 0);
    }
}//END OF CLASS UA_Action
?>