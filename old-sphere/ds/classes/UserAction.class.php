<?php $CVS_Version = '$Revision: 1.13 $ ; $Date: 2010/07/07 10:02:00 $';

/**
 * Класс обработчик запросов от клинта.
 */
class UserAction extends GameBase{

    public $root;

    /** Сообщение о последней ошибке */
    public $errormsg;

    /** Параметры текущего пользолвателя */
    public $params;

    /** Экземпляр класса для работы с системными сообщеняими */
    public $sysmsg;

    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    public $common;

    /**
     * Enter description here...
     *
     * @var SU_OutputHandler
     */
    public $oh;

    /**
     * Enter description here...
     *
     * @var DE_WorldSettings
     */
    public $worldSettings;
        
    function __construct() 
    {
        global $params;

        $this->GameBase();

        $this->common = DS_Common::CreateForCurentDB();
        $this->oh = SU_OutputHandler::Singleton();
        $this->worldSettings = DE_WorldSettings::CreateForCurentWorld();    

        assert(key_exists("uiid", $_SESSION));
        $params["uiid"] = (int)$_SESSION["uiid"];
        
        assert(key_exists("lang", $_SESSION));
        $params["lang"] = $_SESSION["lang"];
        assert($params["lang"] > 0);
        
        assert(key_exists("race", $_SESSION));
        $params["race"] = $_SESSION["race"];
        assert($params["race"] > 0);

        return true;
    }

    /**
    * @return unknown
    * @param $request - Запрос
    * @desc Исполняет запро клинета
    */
    function getRequest($request)
    {
        assert(!empty($request));       
        
        global $params;
        
        $request = trim($request);
        
        SU_FlashVersionControlInUseraction::ControlVersions($request);
        
        //В зависимости от того запущенно из коммандной строки или нет
        if(RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            $xmldata = $request;    
        }else{
            $xmldata = decode($request);    
        }       
        
        assert(!empty($xmldata));
        
        
        //Проверяем, что если запрос пришел  по нажатию кнопки F5 то 
        if(!RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            if(key_exists('LAST_PROCESSED_KEY', $_SESSION))
            {
                $lastProcessedKey = $_SESSION['LAST_PROCESSED_KEY'];
            }else{
                $lastProcessedKey = '';
            }
            
            if(key_exists('LAST_PROCESSED_COMMAND', $_SESSION))
            {
                $lastProcessedCommand = $_SESSION['LAST_PROCESSED_COMMAND'];
            }else{
                $lastProcessedCommand = '';
            }
            
            $recivedKey = SU_ClientKey::GetLastRecivedKey();
            $recivedCommand = $xmldata;
            
            if($recivedKey == $lastProcessedKey && $lastProcessedCommand == $recivedCommand)
            {
                //ds_error('Duoble send');  
                return true;
            }else{
                $_SESSION['LAST_PROCESSED_KEY'] = $recivedKey;
                $_SESSION['LAST_PROCESSED_COMMAND'] = $recivedCommand;
            }                   
        }           

        // убираем ненужные символы
        $xmldata = stripcslashes ( $xmldata );
        $xmldata = str_replace ( "&", "", $xmldata );
                
        if($xmldata == 'logout')
        {
            SU_ClientKey::setCheckGetVars(true);
            $back = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"index_start.php?ck=".$_SESSION['client_key']."\"/>";
            $this->oh->addString($back, 'loadkey');
            die($this->oh->fetchAll());         
        }

        // создаем xml-объект
        $xmldata = '<?xml version="1.0" encoding="ISO-8859-1"'."?".">".$xmldata;
        //$xmldata = '<?xml version="1.0" encoding="utf-8"'."?".">".$xmldata;

        $tree = @domxml_open_mem ( $xmldata );      
        assert($tree);      

        // берем рутовый элемент, по нему определятся какая пришло комадна от юзера
        $params["root"] = $tree->document_element ();
        
        assert($params["root"]);
        
        $this->root = & $params["root"];
        
/************ Прямые ссылки в клиенте *********************/

        $clientDirectLinks = array(
                'moneypage' => array('https://billing.destinysphere.ru/LoginOutside.php', 
                //'moneypage' => array('http://billing/LoginOutside.php', 
                //'p=vitrina%26id_category=25%26id_partner=6'
            "partner_id=".constant('BILLING_PARTNER_ID')."%26user_id=".$_SESSION['uid']."%26hash=".md5(md5($_SESSION['s_pass']."REDACTED_API_SECRET")).'%26mode=gold'
                ),
                'clickonallyicon' => array('https://billing.destinysphere.ru/index.php', 'p=vitrina%26id_category=27%26id_partner=6'),
                'artefactactivate' => array(constant('GAME_HOST').'/ds/quiz.php', 'art={id}'),
            );
        
        if(key_exists($this->root->tagname, $clientDirectLinks))
        {
            $id = $this->root->get_attribute("id");
            $back = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"".$clientDirectLinks[$this->root->tagname][0]."?[url_params]\" target_load=\"_blank\"/>";
            $this->oh->addString($back, 'loadkey');
            SU_ClientKey::setKey();
            $str = $this->oh->fetchAll();
            $str = str_replace('[url_params]', $clientDirectLinks[$this->root->tagname][1], $str);
            $str = str_replace('{id}', $id, $str);
            die($str);
        }
        
/**********************************************************/
                    
//      $flash_key = $this->root->get_attribute ( "newkey" );

        $key_xml = '<?xml version="1.0" encoding="utf-8"'."?"."><key/>";

        $key_doc = domxml_open_mem ( $key_xml );

        $key_root = $key_doc->document_element ();

/**************** BEGIN ЗАЩИТА ОТ ФЕЙКОВОГО ПРЕМИУМА BEGIN **************/
$alertTags = array('archeologyteam', 'attachartefact', 'detachartefact',
                        'sell', 'buy', 'transferresource', 'defend',
                         'canceldefend', 'cancelattack', 'createarmy');

$resetTags = array('getbuildmenu');

if(!array_key_exists('PEREMIUM_CHEATER_ALLERT', $_SESSION))
{
    $_SESSION['PEREMIUM_CHEATER_ALLERT'] = false;
}

if(!$_SESSION['profi_game'])
{
    if(in_array($this->root->tagname, $alertTags) && ($_SESSION['PEREMIUM_CHEATER_ALLERT'] == true))
    {
        /*$str = $_SESSION['s_login'].", ".$this->root->tagname.", ".date('Y-m-d H:m:s')."\r\n";
        SU_DebugTools::WriteToLog($str, 'CheatPremium.log');*/
        //ds_error('This action has been already executed.', 1, 1, 1, 0, 'FAKE_PREMIUM');
        return true;
        
    }
    
    if(in_array($this->root->tagname, $resetTags))
    {
        $_SESSION['PEREMIUM_CHEATER_ALLERT'] = false;
    }
    
    
    if(in_array($this->root->tagname, $alertTags))
    {
        $_SESSION['PEREMIUM_CHEATER_ALLERT'] = true;
    }
}

/**************** END ЗАЩИТА ОТ ФЕЙКОВОГО ПРЕМИУМА END **************/

        $key_root->set_attribute ( "valid", "1" );

        $key_root->set_attribute ( "newkey", "123" );

        $this->oh->addString( $key_doc->dump_mem (), 'loadkey');

        GP_SystemStatistic::SetParam('USERACTION', 'ACTION_TAG', $this->root->tagname);

        // если игра идет в профи-режиме, нагрузка на базу не проверяется
        if(!isset($_SESSION['profi_game']) && isset($dontChekOverload)) 
        {
            if($this->root->tagname == "getbuildmenu" || $this->root->tagname == "refresh") 
            {

                $sql = "SELECT count(procpid) AS activity
                        FROM pg_stat_activity
                        WHERE datname = '".$this->common->getDBName()."'";
                $res = $this->common->getResultArray ( "transactions", $sql, "", "", "" );
                assert(empty($this->common->errormsg) && count($res) > 0);              

                $activity = $res[0]["activity"];
                if(empty($activity)) 
                {
                    $activity = 0;
                }

                $mt = (int)DE_WorldSettings::CreateForCurentWorld()->get('MAX_TRANSACTION');

                if($activity >= $mt)
                {
                    $messparams[0] = "";
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, "SYSTEM_OVERLOAD", $messparams, 0, 'RED');

                    $_SESSION["s_xmldata"] = "";
                    //$refresh = @$_SESSION["refresh"];
                    $refresh = '';
                    
                    if(isset($refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]])) 
                    {
                        SU_OutputHandler::Singleton()->addReadyString( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]], 'loadkey');
                    } else {

                        $refr = ClassFactory::getInstance ( "UA_Refresh" );

                        $refr->full = 1;
                        $refr->comb = $_SESSION["comb"];
                        $refr->celltype = $_SESSION["celltype"];
                        $refr->combnumber = $_SESSION["combnumber"];
                        $refr->combcomplex = $_SESSION["combcomplex"];
                        $refr->ring = $_SESSION["ring"];
                        $refr->world = $_SESSION["world"];
                        $refr->noscan = 2;
                        
                        if(!$refr->performAction()) 
                        {                           
                            ds_error($refr->errormsg);                          
                        }
                    }

                    // вряемя задержки при перегрузе
                    SU_OutputHandler::Singleton()->addString(DE_WorldSettings::CreateForCurentWorld()->get('OVERLOAD_DELAY'), 'overload');
                    return false;
                    
                } else {                
                    SU_OutputHandler::Singleton()->addString( '0', 'overload');
                }               
            }else{              
                SU_OutputHandler::Singleton()->addString( '0', 'overload');
            }
        } else {            
            SU_OutputHandler::Singleton()->addString( '0', 'overload');         
        }

        //определяем класс-обработчик команды
        $sql = "SELECT *
                FROM ds_useractiontype
                WHERE tagname = '".$this->root->tagname."'";
        $res = $this->common->getResultArray("useractiontype_tagname_".$this->root->tagname, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
  
                
        assert(empty($this->common->errormsg) && count($res) == 1);     
                        
        $_SESSION['LAST_USER_ACTIONTYPE_ID'][] = array((int)$res[0]["id_useractiontype"], $xmldata);
        
        // получаем экземпляр объекта этого класса
        $className = trim($res[0]['actionhandler']);

      
        GP_CombAttributeChange::Add($_SESSION['USERNAME'], " ".$className."\n\n");

        $action = new $className;

        assert(is_object($action));
                
        // проверяем глабальные параметры
        if(!$action->checkGlobalParams())
        {

            $refresh = @$_SESSION["refresh"];
            if ( isset ( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]] ) ) {
                SU_OutputHandler::Singleton()->addReadyString( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]] );
            }

            $combres = @$_SESSION["combres"];

            if ( isset ( $combres[$_SESSION["comb"]] ) ){
                $this->oh->addReadyString( $combres[$_SESSION["comb"]] );
            }

        
            ds_error( $action->errormsg);

            $_SESSION["s_xmldata"] = "";

            @session_destroy();

            return false;
        }

        // провереяем параметры переданные в команде
        if(!$action->checkLocalParams())
        {
            $refresh = @$_SESSION["refresh"];
            if ( isset ( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]] ) ) {
                $this->oh->addReadyString( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]]);
            }

            $combres = @$_SESSION["combres"];

            if ( isset ( $combres[$_SESSION["comb"]] ) ){
                $this->oh->addReadyString( $combres[$_SESSION["comb"]] );
            }

            ds_error( $action->errormsg);

            if ( !$action->actionResult ( $uat, $action->errormsg ) ) {
                return false;
            }

            $_SESSION["s_xmldata"] = "";

            return false;
        }

        // проверяем допустимость действия
        if(!$action->checkAction()) 
        {
            
            $refresh = @$_SESSION["refresh"];
            
            if(isset($refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]])) 
            {
                $this->oh->addReadyString( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]]);
            }
            
            $combres = @$_SESSION["combres"];
            
            if(isset($combres[$_SESSION["comb"]]))
            {
                $this->oh->addReadyString( $combres[$_SESSION["comb"]] );
            }
            
            ds_error( $action->errormsg);
            
            if(!$action->actionResult($uat, $action->errormsg)) 
            {
                return false;
            }
            
            $_SESSION["s_xmldata"] = "";
            
            return false;
        }

        if(!$action->performAction())
        {

            $refresh = @$_SESSION["refresh"];

            if ( isset ( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]] ) ) {
                $this->oh->addReadyString( $refresh[$_SESSION["comb"]."_".$_SESSION["celltype"]]);
            }

            $combres = @$_SESSION["combres"];

            if(isset($combres[$_SESSION["comb"]]))
            {
                $this->oh->addReadyString( $combres[$_SESSION["comb"]] );
            }

            ds_error( $action->errormsg);

            if ( !$action->actionResult ( $uat, $action->errormsg ) ) {
                return false;
            }

            $_SESSION["s_xmldata"] = "";
            return false;
        }
        

        return true;
    }   
}//END OF CLASS

?>