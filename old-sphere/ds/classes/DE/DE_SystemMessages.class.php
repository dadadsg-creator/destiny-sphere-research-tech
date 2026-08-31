<?php $CVS_Version = '$Revision$ ; $Date$';

// системные сообщениея

class DE_SystemMessages  extends DE_Base
{
    private $out = array();

    private $langID;

    private $timeDelta;

    private $userInstance;

    static $colors = array();
    
    static $font = '';  

    static public $clearMessage;
    
    public function __construct($id)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_integer($id));
        parent::__construct($id);
        
        assert(key_exists('SYSTEM_MESSAGES_COLORS', $GLOBALS));
        self::$colors = $GLOBALS['SYSTEM_MESSAGES_COLORS'];
        
        self::$font = constant('FONT_NAME');
        
        $this->userInstance = DE_UserInstance::CreateFromID($id);

        $this->langID = $this->userInstance->getUser()->getLanguageID();
        assert($this->langID > 0);

        $this->timeDelta = $this->userInstance->getUser()->getTimeDelta();

        $this->_invariant('construct');
    }

    /**
     * Enter description here...
     *
     * @param int $id
     * @return DE_SystemMessages
     */
    static public function CreateFromUserInstanceID($id)
    {
        assert(is_int($id));
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }

    /**
     * Enter description here...
     *
     * @return DE_SystemMessages
     */
    static public function CreateForCurrentUserInstance()
    {
        settype($_SESSION["uiid"], 'integer');
        $uiid = $_SESSION["uiid"];

        return self::CreateFromUserInstanceID($uiid);
    }

    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;

        parent::_invariant($mode);

        assert(is_int($this->timeDelta));
        assert(is_int($this->langID) && $this->langID>0);
    }

    static public function SendForCorrentUser()
    {
        settype($_SESSION["uiid"], 'integer');
        $uiid = $_SESSION["uiid"];

        //Если старутем из комманлдной строки то ненадо ничего отправлять
        if(RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            return;
        }
        
        $rs = GB_SingletonObjectStorage::GetObjectsOfClass(__CLASS__);
        $rs->resetPointer();
        while($systemMessages = $rs->getNextElement())
        {
            if($uiid == $systemMessages->getID())
            {
                $systemMessages->_sendMessages();
            }
        }
    }

    static public function AddMessageToEventParticipants($eventQueueID, $messageName, $messageParams, $isWar = null, $colors = null, $accumulatorStatus = 'ACCUMULATE')
    {
        settype($eventQueueID, 'integer');
        assert($eventQueueID > 0);

        if($accumulatorStatus != 'ACCUMULATE')
        {
            $accumulatorStatus = 'NOTFORSTORE';
        }

        if(is_null($colors))
        {
            $colors = 'WHITE';
        }


        if(is_null($isWar))
        {
            $isWar = 0;
        }

        $event = DE_EventQueue::CreateFromID((int)$eventQueueID);
        $checktime = $event->getHandler()->checktime;
        if($checktime == '' ||  $checktime == 'now()')
        {
            if ($event->getEndDate()->value > time())
            {
                $messageDate = GB_TypeFactory::CreateBase('GB_Date', time());
            } else {
                $messageDate = $event->getEndDate();
            }

        }else{
            $messageDate = GB_TypeFactory::CreateBase('GB_Date', $checktime);
        }

        $messageID = $event->getHandlerName()."_".$eventQueueID."_".$messageName."_".$accumulatorStatus;

        $eventParticipantsCollection = DC_EventParticipants::CreateFromEventID($eventQueueID);
        $eventParticipantsCollection->resetNext();
        while($partitipant = $eventParticipantsCollection->next())
        {
            // Ели игрок DestinySphere, то ему сообщение не шлем
            // Нужно для захватываемых колоний
            if ($partitipant->getID() == 0) {
                continue;
            }
                    
            if(is_array($colors))
            {
                if (!key_exists($partitipant->getID(), $colors)) {
                    return ;
                }
                
                assert(key_exists($partitipant->getID(), $colors));
                $color = $colors[$partitipant->getID()];
            }else{
                $color = $colors;
            }

            DE_SystemMessages::CreateFromUserInstanceID($partitipant->getID())->_addMessage ($messageID, $messageName, $messageParams, $messageDate, $isWar, $color);
        }
    }
    
    static public function AddMessageFromMessageCompiler(GB_Date $messageDate, $UIID, $messageName, $messageParams, $isWar = null, $colors = null, $accumulatorStatus = 'ACCUMULATE')
    {       
        assert(is_int($UIID));

        if($accumulatorStatus != 'ACCUMULATE')
        {
            $accumulatorStatus = 'NOTFORSTORE';
        }

        if(is_null($colors))
        {
            $colors = 'WHITE';
        }


        if(is_null($isWar))
        {
            $isWar = 0;
        }
        
        $messageID = mt_rand(1, 100000)."_".$UIID."_".$messageName."_".$accumulatorStatus;

        $color = $colors;
                
        DE_SystemMessages::CreateFromUserInstanceID($UIID)->_addMessage ($messageID, $messageName, $messageParams, $messageDate, $isWar, $color);       
    }

    static public function AddMessageFromAction($className, $messageName, $messageParams, $isWar = null, $color = null, $uiid = null)
    {
        assert(!empty($className));

        if(is_null($color) || $color == 'DEFUALT')
        {
            $color = 'WHITE';
        }

        if(is_null($isWar))
        {
            $isWar = 0;
        }

        if(is_null($uiid))
        {
            assert(key_exists('uiid', $_SESSION));
            $uiid = (int)$_SESSION['uiid'];
        }else{
            settype($uiid, 'integer');
        }

        $messageID = $className."_".$messageName."_".mt_rand(1, 100000);

        $messageDate = GB_TypeFactory::CreateBase('GB_Date', time());

        DE_SystemMessages::CreateFromUserInstanceID($uiid)->_addMessage ($messageID, $messageName, $messageParams, $messageDate, $isWar, $color);
    }

    public function getForOut()
    {
        $this->_invariant();

        return $this->_serializeMesages($this->out);
    }
    
    public function getFormatedMessage($messageName, $messageParams, $color, $messageDate = null, $useFont = true)
    {
        $rs = DS_Misc::GetLocolizedSystemMessage($messageName, $this->langID);
        
        if(!is_array($rs))
        {
            ds_error("Cant find sysmsg. Name: $messageName Lang: ".$this->langID);
        }
        
        assert(is_array($rs));
        
        $message = $rs['message'];

        assert(key_exists('numberofparams', $rs));
        $numberOfParams = $rs['numberofparams'];

        if(!($messageDate instanceof GB_Date))
        {
            $messageDate = GB_TypeFactory::CreateBase('GB_Date', time());
        }

        //$messageDate->value += $this->timeDelta * 3600;

        // заменяем заполнители реальными параметрами
        for ($i=0; $i < $numberOfParams; $i++)
        {
            $pos = strpos( $message, "%" );

            if (!key_exists($i, $messageParams))
            {
                $param = "[!LOST_PARAM!]";
            }else{
                $param = $messageParams[$i];
            }

            if(!empty($param) || $param == 0)
            {
                $message = substr_replace ($message, $param, $pos, 1);
            }
        }


        self::$clearMessage = $message;
        
        if($useFont)
        {
            $message =  "{FONT FACE=\"".self::$font."\" SIZE=\"10\" COLOR=\"#".self::$colors[$color]."\"}".$messageDate->getSystemMessageFormat()." - ".$message."{/FONT}";
        }
        return $message;
    }

    public static function getFormatedMessage2($messageName, $messageParams, $color, $lang, $messageDate = null, $useFont = true)
    {
        $rs = DS_Misc::GetLocolizedSystemMessage($messageName, $lang);
        
        if(!is_array($rs))
        {
            ds_error("Cant find sysmsg. Name: $messageName Lang: ".$lang);
        }
        
        assert(is_array($rs));
        
        $message = $rs['message'];

        assert(key_exists('numberofparams', $rs));
        $numberOfParams = $rs['numberofparams'];

        if(!($messageDate instanceof GB_Date))
        {
            $messageDate = GB_TypeFactory::CreateBase('GB_Date', time());
        }

        //$messageDate->value += $this->timeDelta * 3600;

        // Р·Р°РјРµРЅСЏРµРј Р·Р°РїРѕР»РЅРёС‚РµР»Рё СЂРµР°Р»СЊРЅС‹РјРё РїР°СЂР°РјРµС‚СЂР°РјРё
        for ($i=0; $i < $numberOfParams; $i++)
        {
            $pos = strpos( $message, "%" );

            if (!key_exists($i, $messageParams))
            {
                $param = "[!LOST_PARAM!]";
            }else{
                $param = $messageParams[$i];
            }

            if(!empty($param) || $param == 0)
            {
                $message = substr_replace ($message, $param, $pos, 1);
            }
        }


        self::$clearMessage = $message;
        
        if($useFont)
        {
            $message =  "{FONT FACE=\"".self::$font."\" SIZE=\"10\" COLOR=\"#".self::$colors[$color]."\"}".$messageDate->getSystemMessageFormat()." - ".$message."{/FONT}";
        }
        return $message;
    }
    
  public static function win2utf($s)
  {
     for($i=0, $m=strlen($s); $i<$m; $i++)
     {
       $c=ord($s[$i]);
       if ($c<=127) {$t.=chr($c); continue; }
       if ($c>=192 && $c<=207)    {$t.=chr(208).chr($c-48); continue; }
       if ($c>=208 && $c<=239) {$t.=chr(208).chr($c-48); continue; }
       if ($c>=240 && $c<=255) {$t.=chr(209).chr($c-112); continue; }
       if ($c==184) { $t.=chr(209).chr(209); continue; };
       if ($c==168) { $t.=chr(208).chr(129);  continue; };
     }
     return $t;
  } 

   // РґРѕР±Р°РІРёС‚СЊ СЃРёСЃС‚РµРјРЅРѕРµ СЃРѕРѕР±С‰РµРЅРёРµ
    private function _addMessage ($messageID, $messageName, $messageParams, $messageDate, $isWar, $color)
    {
        assert(!empty($messageID));
        assert(!empty($messageName));
        assert(is_array($messageParams));
        assert($isWar == 0 || $isWar == 1 || $isWar == 2);
        assert(key_exists($color, self::$colors));

        $message = $this->getFormatedMessage($messageName, $messageParams, $color, $messageDate);

        switch ($isWar) {
            case 0:
                $this->out[$messageID] = "<m>".$message."</m>";
                break;
                
            case 1:
                $this->out[$messageID] = "<w>".$message."</w>";
                break;
            
            case 2:
                $this->out[$messageID] = "<s>".$message."</s>";
                break;
        
            default:
                break;
        }
        
        $this->isModifyed = true;
    }

    public function commit()
    {
        parent::commit();

        $rs = $this->_serializeMesages($this->out);
        if(empty($rs))
        {
            return;
        }else{
            DS_Misc::InsertSystemMessages($this);
        }

        $this->out = array();
    }

    private function _sendMessages()
    {
        function sortMessages($a, $b) {
            $p1 = strpos($a, "COLOR=") + 7;
            $p2 = strpos($a, '"', $p1);
            $ca = substr($a, $p1, $p2-$p1);
            $p1 = strpos($b, "COLOR=") + 7;
            $p2 = strpos($b, '"', $p1);
            $cb = substr($b, $p1, $p2-$p1);
            return $ca < $cb || $ca==$cb && $b>$a ? 1 : -1;
        }

        $messagesArrays = DS_Misc::GetSystemMessages($this);
        DS_Misc::DeleteSystemMessages($this);

        foreach($messagesArrays as $k=>$v)
        {
            $messagesArrayString = $v['messages'];


            if(empty($messagesArrayString))
            {
                $messagesArray = array();
            }else{
                $messagesArray = $this->_unserializeMesages($messagesArrayString);
            }

            $this->out = array_merge($messagesArray, $this->out);
        }

        if(count($this->out) == 0)
        {
            return;
        }
        /*
        for($i=0; $i<count($this->out); $i++) {
            $this->out[$i] = sprintf("%04d%s", $i, $this->out[$i]);
        }
        usort($this->out, "sortMessages");
        for($i=0; $i<count($this->out); $i++) {
            $this->out[$i] = substr($this->out[$i], 4);
        }*/

        $msg =  "<sysmsg>".implode("", $this->out)."</sysmsg>";

        $matches = array();
        preg_match_all("/\[\:([0-9]+)\:\]/i",$msg, $matches);

        if(key_exists(1, $matches))
        {
            foreach($matches[1] as $k=>$timeStamp)
            {
                $searchPattern = $matches[0][$k];
                $formatedDate = GB_TypeFactory::CreateConstant('GB_Date', (int)$timeStamp)->getFormatedFull($this->timeDelta);

                $msg = str_replace($searchPattern, $formatedDate, $msg);
            }
        }
        
        SU_OutputHandler::Singleton()->addString( $msg, 'sysmsg' );

        $this->out = array();
    }

    private function _serializeMesages($testArray)
    {
        $tmp = array();

        foreach($testArray as $key => $message)
        {
            if(strpos($key, 'ACCUMULATE')!==FALSE)
            {
                $tmp[$key] = $message;
            }
        }

        if(count($tmp) > 0)
        {
            return addslashes(serialize($tmp));
        }else{
            return '';
        }
    }

    private function _unserializeMesages($str)
    {
        $tmp = unserialize(stripslashes($str));

        if(is_array($tmp))
        {
            return $tmp;
        }else{
            return array();
        }

    }

}
?>