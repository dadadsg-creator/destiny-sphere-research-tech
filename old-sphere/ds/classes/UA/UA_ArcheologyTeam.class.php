<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отправить археологическую экспедицию
class UA_ArcheologyTeam extends UA_Action 
{

    private $dest;
    private $teamnumber;
    private $destination;
    private $sendType = 'singl';
    private $countofarcheologist;
    private $combAttributes;
    private $exitwrefresh = false;
    private $minsearchsrtsize;
    private $messparams = array();
    private $teamlimit = false;
    private $countgroups;
    private $groupsSend = 0;
    private $artefactLimit = false;
    
    function __construct() 
    {
        UA_Action::UA_Action ();
    }

    // проверяем параметры
    function checkLocalParams () 
    {       
        // куда отправляет
        $this->dest = $this->root->get_attribute ("dest");
        assert(!empty($this->dest));        

        
        //тип отправки архов
        $this->sendType = $this->root->get_attribute ("sendtype");
        if(empty($this->sendType))
        {
            $this->sendType = 'singl';
        }else{
            $this->sendType = 'all';
        }       
        assert($this->sendType == 'singl' || $this->sendType == 'all');     
                        
        $sql = "SELECT id_celltype
                FROM ds_celltype
                WHERE celltypename = '".$this->dest."'";
        $res = $this->common->getResultArray("id_celltype_celltypename_".$this->dest, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($this->common->errormsg) && count($res) > 0);      

        $this->destination = $res[0]['id_celltype'];

        // сколько археологов
        $this->countofarcheologist = (int)$this->root->get_attribute ("count");
        assert($this->countofarcheologist > 0);
                
        // на какое время
        $this->timetosearch = (int)$this->root->get_attribute ("time");
        assert($this->timetosearch > 0 && $this->timetosearch <= 12);               

        //Какой минимальный размер артов приносить после похода
        $this->minsearchsrtsize = (int)$this->root->get_attribute ("size");
        assert($this->minsearchsrtsize > 0);

        if($_SESSION['profi_game'])
        {
            $this->countgroups = (int)$this->root->get_attribute ("countgroup");
            if($this->countgroups == 0)
            {
                $this->countgroups = 1;
            }
            assert($this->countgroups > 0);
        } else {
            $this->countgroups = 1;
        }
        
        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {       
        $this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
        
        // сколько археологов есть в наличии
        $arch = $this->combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST');
        
        
        if($this->countofarcheologist > $arch)
        {
            $this->countofarcheologist = $arch;
        }
        
        // попытка отправить больше чем есть
        if ($this->countofarcheologist == 0)
        {           
            $this->exitwrefresh = 1;
        }

        // если отправляет не в домашнюю часть соты, проверяем открыты ли ЦВ
        if ( $this->dest == 'CENTRAL' || $this->dest == 'NEUTRAL')
        {
            assert(DS_Comb::HaveOpenCentralGate((int)$this->comb));         
        }
        
        // Проверяем ограничениме на общее количество групп археологов
        if((DS_Archeology::getCountArcheologyTeam($this->uiid) + $this->countgroups) > $this->worldSettings->get('ARCHEOLOGICAL_TEAM_LIMIT'))
        {
            $this->teamlimit = true;
        }
        
        // Проверяем ограничение на количество артефактов
        if(DS_Archeology::getCountArefacts((int)$this->comb) > $this->worldSettings->get('MAX_COMB_ARTEFACT_COUNT'))
        {
            $this->artefactLimit = true;
        }

        return true;
    }

    // выполняем действие
    function performAction ()
    {       
        //if ( $_SESSION['profi_game'] )
        //{
        $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}

        if($this->teamlimit)
        {
            $messparams = array();
            $messparams[0] = $this->worldSettings->get('ARCHEOLOGICAL_TEAM_LIMIT');
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ARCHEOLOGICAL_TEAM_LIMIT', $messparams, 0);
            return true;
        }
            
        if($this->artefactLimit)
        {
            $messparams = array();
            $messparams[0] = $this->worldSettings->get('MAX_COMB_ARTEFACT_COUNT');
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ARTEFACT_ON_COMB_LIMIT', $messparams, 0);
            return true;
        }
        
        //рефрешим соту, в том случае если всего археологов меньше, чем пытаемся отправить, с актуализацие ресурсов
        if ($this->exitwrefresh == 1)
        {
            // рефреш без обработки событий, бей измения вида соты, с актуализацией ресурсов
            assert($this->refresh( 2, "", "", 1, $noCellData));         
            return true;
        }

        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();            
            return true;
        }

        
        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();           
            return true;
        }
        
        // получаем время окончания
        if(!$this->getEventEndTime())
        {
            return false;
        }

        $this->messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";        
        
        // куда отправил
        $sql = "SELECT 
                    ltl.text as localizedname
                FROM 
                    ds_celltype ct,
                    ds_localizedtextlanguage ltl
                WHERE 
                    ct.id_localizedname = ltl.id_localizedtext
                    AND
                    ltl.id_language = ".$this->params["lang"]."
                    AND
                    ct.id_celltype = ".$this->destination;
        $res = $this->common->getResultArray ( "celltypename_id_language_destination_".$this->params["lang"]."_".$this->destination, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($this->common->errormsg) && count($res) > 0);
        
        $this->messparams[2] = "{b} ".$res[0]['localizedname']."{/b}";
        
                
        if($this->sendType == 'singl')
        {
            $this->_sendSingl();            
        }else{
            $this->_sendAll();          
        }       
        
        // рефреш без обработки событий, бей измения вида соты, с актуализацией ресурсов
        assert($this->refresh(2, "", "", 1, $noCellData));
        
        $this->_sendHelper();
                
        return true;
    }
    
    private function _sendAll()
    {
        
        $counter = 0;
        while($this->combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST') != 0)
        {
            $counter++;
            assert($counter < 150);
            
            $currentCount = $this->combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST');
            
            if($currentCount < $this->countofarcheologist)
            {
                $this->countofarcheologist = $currentCount;
            }
            
            $this->messparams[1] = "{b} ".$this->countofarcheologist."{/b}";
            
            $this->_sendTeam();             
        }       
    }
    
    private function _sendSingl()
    {
        $this->groupsSend = 0;
        
        for($i=0; $i<$this->countgroups; $i++)
        {
            $currentCount = $this->combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST');
            
            if($currentCount == 0)
            {
                break;
            }
            
            if($currentCount < $this->countofarcheologist)
            {
                $this->countofarcheologist = $currentCount;
            }
            
            $this->messparams[1] = "{b} ".$this->countofarcheologist."{/b}";
            $this->_sendTeam();
            
            $this->groupsSend++;
        }
        
        if($this->groupsSend > 1)
        {
            // message 1
            // ARCHEOLOGY_MASS_TEAM_SEND
            $this->messparams[3] = $this->messparams[2];
            $this->messparams[2] = $this->messparams[1];
            $this->messparams[1] = $this->groupsSend;
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "ARCHEOLOGY_MASS_TEAM_SEND", $this->messparams, 0, 'BROWN' );
        } else {
            // message 2
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "ARCHEOLOGY_TEAM_MESS1", $this->messparams, 0, 'BROWN' );
        }
    }
    
    private function _sendTeam()
    {       
        // номер команды археологов
        $sql = "SELECT COALESCE
                (
                    (
                        select max(teamnumber) 
                        from ds_archeologicalteam 
                        where id_comb = ".$this->comb."
                    ), 
                    0
                ) + 1 as teamnumber";               
        $res = $this->common->getResultArray ("", $sql, "", "", "");
        assert(empty($this->common->errormsg) && count($res) > 0);      

        $this->teamnumber = $res[0]['teamnumber'];

        // создаем эвент        
        $this->createEvent('SEND_ARCHEOLOGY_TEAM');
        
        
        // добавляем команду археологов
        $sql = sprintf ( "insert into DS_ArcheologicalTeam 
                ( id_comb, teamnumber, id_celltype, starttime, endtime, amount, id_eventqueue, size) 
                values ( %d, %d, %d, '%s', '%s', %d, %d, %d)", 
                $this->comb, 
                $this->teamnumber,
                $this->destination,
                $this->starttime,
                $this->endtime,
                $this->countofarcheologist,
                $this->eqid,
                $this->minsearchsrtsize );
        $res = $this->common->executeQuery ( $sql );
        assert(empty($this->common->errormsg) && $res);     

        // уменьшаем текущее кол-во археологов  
        $this->combAttributes->deltaAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST',-(float)$this->countofarcheologist);
        
        // увеличиваем кол-во археологов в пути
        $this->combAttributes->deltaAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', (float)$this->countofarcheologist);
        
        $sql = sprintf ( "insert into DS_CombBindEventQueue 
                ( ID_EventQueue, ID_Comb ) values ( %d, %d )",
                $this->eqid, 
                $this->comb );
        $res = $this->common->executeQuery ( $sql );
        assert(empty($this->common->errormsg) && $res);     
                

        // добавляем сообщение
        //DE_SystemMessages::AddMessageFromAction(__CLASS__, "ARCHEOLOGY_TEAM_MESS1", $this->messparams, 0, 'BROWN' );
    }
    
    private function _sendHelper()
    {
        if(!(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true)))
        {
            return;
        }
        
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();      
        
        // выбор времени прихода последней экспедиции
        $sql = "SELECT 
                    at.endtime
                FROM
                    ds_archeologicalteam AS at
                WHERE
                    at.id_comb = ".$this->comb."
                ORDER BY starttime DESC
                LIMIT 1 OFFSET 0";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg)); 

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><arch />";
        $doc = domxml_open_mem($xml);
        $root = $doc->document_element();
        
        $endDate = GB_TypeFactory::CreateBase('GB_Date', $res[0]['endtime']);

        $elem = $doc->create_text_node($endDate->getFormatedFull($delta));
        $root->append_child($elem);
        $root->set_attribute('count', $this->groupsSend);

        SU_OutputHandler::Singleton()->addString($doc->dump_mem() , 'helper');
    }
    
    // выдает время окончания эвента
    function getEventEndTime () 
    {           
        $this->starttimeObject = GB_TypeFactory::CreateBase('GB_Date', time());
        $this->starttime = $this->starttimeObject->getPgFormat();
        
        $timeDelta =  $this->worldSettings->get('TURN_TIME') * 60 * $this->timetosearch;
        $this->endtimeObject = GB_TypeFactory::CreateBase('GB_Date', time() + $timeDelta);
        $this->endtime = $this->endtimeObject->getPgFormat();
                
        return true;
    }

    function createEventParams () 
    {
        $this->eventparams["Destination"] = $this->destination;
        $this->eventparams["CountOfArcheologist"] = $this->countofarcheologist;
        $this->eventparams["TimeToSearch"] = $this->timetosearch;
        $this->eventparams["ID_UserInstance"] = $this->params["uiid"];
        $this->eventparams["MinSearchArtSize"] = $this->minsearchsrtsize;
        $this->eventparams["ID_Comb"] = $this->comb;
        $this->eventparams["ID_World"] = $this->world;
        $this->eventparams["TeamNumber"] = $this->teamnumber;
        
        return true;
    }
}

?>