<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * команда отправить сообщение
 * 
 */
class UA_SendMessage extends UA_Action 
{
    public $errorname;
    
    public $to;
    public $subject;
    public $messageScopeID;
    public $messageScopeName;
    public $messagetext;
    public $original_messagetext;   
    public $messageID;
    /**
     * ИД альянса, используется для отправки альянсовых сообщений из игры
     *
     * @var integer
     */
    public $allianceID = 0;
        
    public $senderName = '';
    public $recieverName = '';
    public $addressList = array();
    
    public $wrongAddress = 0;

    function __construct() 
    {
        UA_Action::UA_Action();
    }
    
    /**
     * проверка параметров
     * 
     */
    function checkLocalParams()
    {
        $this->errorname = array();

        // выбираем параметры
        $this->_getParams();

        // для частного сообщения должен быть адресат
        if($this->messageScopeName == 'PRIVATE')
        {
            $this->_checkTo();
        }
        
        // проверяем тему
        $this->_checkSubject();
        
        // проверяем текст
        $this->_checkMessage();
        
        return true;
    }
    
    /**
     * проверяем допустимость
     * 
     */
    function checkAction() 
    {
        if(count($this->errorname) > 0)
        {
            return true;
        }
    
        if($this->uiid === 0)
        {
            return true;
        }
        
        // доступен ли пользователю выбранный скоп
        assert(DS_Message::UserHaveMessageScope((int)$this->uiid, $this->messageScopeID));

        return true;
    }
    

    /**
     * Выполнение действия
     *
     * @param bool $withSystemMessages выводить ли сообщения игроку (нужно для использования этого метода при отправке сообщений при действиях в альянсе)
     * @return bool
     */
    function performAction($withSystemMessages = true) 
    {
        if(count($this->errorname) > 0)
        {
            $messparams[0] = "";
            foreach ($this->errorname as $error)
            {
                DE_SystemMessages::AddMessageFromAction(__CLASS__, $error, $messparams);
            }
            
            return true;
        }
        
        $this->senderName = DE_UserInstance::CreateFromID($this->uiid)->getUser()->getName();
        
        $this->_getAddressListByScope();

        if(count($this->addressList) == 0)
        {
            return true;
        }
        
        $this->_createMessage();
        
        $this->_addMessageToUsers();
      
          
        if($withSystemMessages)
        {
            $this->_addSystemMessage();
        }
        

        //
    if ( stripos($_SERVER['HTTP_HOST'],'ds.klase.lt') !==false )
    {
      $this->_sendOAuthNotifications();
    }
        return true;//$this->_refresh();
    }
    
    /**
     * Вытаскивание параметров
     *
     */
    private function _getParams()
    {
        // список получателей
        $ch = $this->root->get_elements_by_tagname("to");
        $node = $ch[0];
        $this->to = trim(utf8_decode($node->get_content()));

        // тема
        $ch = $this->root->get_elements_by_tagname("subject");
        $node = $ch[0];
        //$this->original_subject = $node->get_content();
        
        $this->subject = trim(utf8_decode($node->get_content()));
        
    $this->original_subject = $this->convert_to_utf8($node->get_content());
//SU_DebugTools::LogAll($this->subject, 'Annita.log');      
//SU_DebugTools::LogAll($this->original_subject, 'Annita.log');    
        // messagescope
        $this->messageScopeID = (int)$this->root->get_attribute("id_messagescope");
        assert($this->messageScopeID > 0);
        $this->messageScopeName = DS_Message::GetMessageScopeNameByID($this->messageScopeID);
        
        // текст сообщения
        $ch = $this->root->get_elements_by_tagname("messagetext");
        $node = $ch[0];
        $this->original_messagetext = $this->convert_to_utf8($node->get_content());
        
        $this->messagetext = trim(utf8_decode($node->get_content()));
        // при reply или forward
        $this->messageID = (int)$this->root->get_attribute("id_message");
    }
    
  public function convert_to_utf8($str)
  {
    $res = "";

    $c  = strlen($str);
    for($i=0; $i<$c;$i=$i+3)
    {
      $s  = substr($str, $i, 3 );
      $res .= $this->unichr(hexdec($s));
    }   
    return  $res;
  }  

  function unichr($c) 
  {
    if ($c <= 0x7F) 
    {
        return chr($c);
    } 
    else if ($c <= 0x7FF) 
    {
        return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
    } 
    else if ($c <= 0xFFFF) 
    {
        return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
                                    . chr(0x80 | $c & 0x3F);
    } 
    else if ($c <= 0x10FFFF) 
    {
        return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
                                    . chr(0x80 | $c >> 6 & 0x3F)
                                    . chr(0x80 | $c & 0x3F);
    } 
    else  
    {
        return false;
    }
  }
    
    /**
     * Проверка поля кому
     *
     */
    private function _checkTo()
    {
        if(!empty($this->errorname))
        {
            return;
        }
        
        $arr = array("'", "\"", "`", "~", "!", "@", "#", "$", "%", "^", "*", "(", ")", "<", ">", "[", "]", "{", "}", "\n", "\t" );
        $this->to = str_replace($arr, "", $this->to);

        if(strlen($this->to) > 200)
        {
            $this->errorname[] = 'ADDRESSEE_TOO_LONG';
        } elseif(strlen($this->to) < 2) {
            $this->errorname[] = 'ADDRESSEE_TOO_SHORT';
        }
    }
    
    /**
     * Проверка темы
     *
     */
    private function _checkSubject()
    {
        if(!empty($this->errorname))
        {
            return;
        }
        
        $arr = array("'", "`", "\"","<", ">", "\n", "\t");
        $this->subject = str_replace($arr, "", $this->subject);
        if(strlen($this->subject) > 200)
        {
            $this->errorname[] = 'SUBJECT_TOO_LONG';
        } elseif(strlen ( $this->subject ) < 3){
            $this->errorname[] = 'SUBJECT_TOO_SHORT';
        }
        
        // Анти-Мат спам фильтр сообщений.
        $this->subject = GB_AntiMatFilter::Exec($this->subject);
    }
    
    /**
     * Проверка текста сообщения
     *
     */
    private function _checkMessage()
    {
        if(!empty($this->errorname))
        {
            return;
        }
        
        $arr = array("'", "`", "\"");
        $this->messagetext = str_replace ( $arr, "", $this->messagetext );
        if(strlen($this->messagetext) > 10000) 
        {
            $this->errorname[] = 'MESSAGE_TOO_LONG';
        } elseif(strlen ( $this->messagetext ) < 3) {
            $this->errorname[] = 'MESSAGE_TOO_SHORT';
        }
        
        // Анти-Мат спам фильтр сообщений.
        $this->messagetext = GB_AntiMatFilter::Exec($this->messagetext);
    }

    private function _sendOAuthNotifications()
    {
        if (in_array($this->messageScopeName, array('PRIVATE', 'COMB_COMPLEX', 'INTERNAL_ALLIANCE'))) 
        {
          $params = array('type' => 'message', 'sender'=>$this->senderName, 
          'text' => $this->original_messagetext, 'subject'=> $this->original_subject , 'message_type' => $this->messageScopeName, 'time'=> time());
          
          foreach ($this->addressList as $address)
          {
              $token = DS_User::GetOAuthInfoByUserinstanceId($address);
              if ($token)
              { 
             $odata = MakeOAuthNotification($token['open_key'], $token['secret_key'], $params );    
              }
          }     
        }  
    }   

    /**
     * Получение списка адресатов по скопу
     *
     */
    private function _getAddressListByScope()
    {
        switch($this->messageScopeName) 
        {
            case 'PRIVATE':
                $this->_getListForPrivateScope();
                break;
                
            case 'COMB_COMPLEX':
                $this->_getListForComplexScope();
                break;
                
            case 'INTERNAL_ALLIANCE':
                $this->_getListForAllianceScope();
                break;
                
            case 'WORLD':
                $this->_getListForWorldScope();
                break;
                
            case 'GAME_MASTER':
                $this->_getListForGameScope('Master');
                break;
                
            case 'GAME_HELPER':
                $this->_getListForGameScope('Helper');
                break;
        }
    }
    
    /**
     * Добавление сообщения в базу
     *
     */
    private function _createMessage()
    {
        $this->messageID = DS_Message::CreateMessage($this->messagetext, $this->subject, $this->senderName, $this->recieverName, $this->messageScopeID, $this->uiid);
        assert($this->messageID > 0);
    }
    
    /**
     * Приписка сообщения пользователям
     *
     */
    private function _addMessageToUsers()
    {
        foreach ($this->addressList as $address)
        {
            DS_Message::AddMessageToUser($this->messageID, $address);
        }

        // Посылаем реальную почту мастерам или хелперам
        if ($this->messageScopeName=='GAME_MASTER' || $this->messageScopeName=='GAME_HELPER') {
            $instanceList = implode(",", $this->addressList);
            $common = DS_Common::CreateForCurrentDB();
            $sql = "select email from ds_user where id_user in
                (select id_user from ds_userinstance where id_userinstance in ($instanceList))";
            $res = $common->getResultArray($sql);
            foreach ($res as $row) {
                mail($row['email'], $this->subject, $this->messagetext);
            }
        }
        
        // отправителю приписываем, если не системный пользователь отправлял
        if($this->uiid > 0)
        {
            DS_Message::AddMessageToUser($this->messageID, $this->uiid, true);
        }
    }
    
    /**
     * Отправка сообщения об успешной отправке сообщений :)
     *
     */
    private function _addSystemMessage()
    {
        $messparams[0] = count($this->addressList);

        // системное сообщение об успешно отправленных сообщениях
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "SEND_MESSAGE_MESS1", $messparams );

        // системное сообщение о неотправленных сообщениях
        if($this->wrongAddress != 0) 
        {
            $messparams[0] = $this->wrongAddress;
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "SEND_MESSAGE_MESS2", $messparams );
        }
    }
    
    /**
     * Частное сообщение
     *
     */
    private function _getListForPrivateScope()
    {
        $userList = preg_split("/\, *|\; *| +/", $this->to);
        assert(count($userList) > 0);
        $userList = array_unique($userList);
        
        foreach ($userList as $user)
        {
            if(ereg("^[0-9]+\.[0-9]+\.[0-9]+$", $user))
            {
                list($ringnum, $complexnum, $combnum) = explode(".", $user);
                $comb = DE_Comb::CreateFromCoordinates((int)$this->world, (int)$ringnum, (int)$complexnum, (int)$combnum);
                if($comb instanceof DE_CombNull)
                {
                    $this->wrongAddress++;
                    continue;
                }
                
                $this->addressList[] = (int)$comb->getUserInstanceID();
            } else {
                // В контакте запрещаем отправку сообщений по нику
                if (constant('VERSION_NAME') == 'VKONTAKTE') {
                    $this->wrongAddress++;
                    continue;
                }
                
                if(!VD_UserName::Validate($user))
                {
                    $this->wrongAddress++;
                    continue;
                }
                
                $userID = DS_User::GetUserIDByName($user);
                if(!$userID)
                {
                    $this->wrongAddress++;
                    continue;
                }
                
                $userInstanceID = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($userID, (int)$this->world);
                if(!$userInstanceID)
                {
                    $this->wrongAddress++;
                    continue;
                }
                
                $this->addressList[] = (int)$userInstanceID;
            }
        }
        
        $this->recieverName = implode(',', $userList);
    }
    
    /**
     * Сообщение по комплексу
     *
     */
    private function _getListForComplexScope()
    {
        $this->_getInstancesIDFromArray(DS_Message::GetUserInstancesInComplex($this->combcomplex));
        
        $comb = DE_Comb::CreateFromID($this->comb);
        $this->recieverName = $comb->getRingNumber().'.'.$comb->getComplexNumber().'*';
    }
    
    /**
     * Сообщение по альянсу
     *
     */
    private function _getListForAllianceScope()
    {
        if($this->allianceID > 0)
        {
            $alliance = DE_Alliance::CreateFromID($this->allianceID);
        } else {
            $userInstance = DE_UserInstance::CreateFromID($this->uiid);
            $alliance = $userInstance->getAlliance();
        }
        
        if($alliance instanceof DE_AllianceEmpty)
        {
            return;
        }

        $this->_getInstancesIDFromArray(DS_Alliance::GetAllAllianceMembers($alliance));
        
        $this->recieverName = $alliance->getShortName();
    }
    
    /**
     * Сообщение по миру
     *
     */
    private function _getListForWorldScope()
    {
        $this->_getInstancesIDFromArray(DS_World::GetAllWorldInstances($this->world));
        
        $world = DE_World::CreateForCurrentWorld();
        $this->recieverName = $world->getName();
    }
    
    /**
     * Сообщение гейм-мастерам и гейм-хелперам
     *
     */
    private function _getListForGameScope($type)
    {
        $allowedTypes = array('Helper', 'Master');
        assert(in_array($type, $allowedTypes));
        
        $method = "GetGame{$type}List";
        
        $this->_getInstancesIDFromArray(DS_Message::$method($this->world));
        
        $this->recieverName = 'Game '.$type;
    }
    
    /**
     * Создаем список айдишников, по массиву данных, выбраных из базы
     *
     * @param array $instances
     */
    private function _getInstancesIDFromArray(array $instances)
    {
        foreach ($instances as $instance)
        {
            assert(key_exists('id_userinstance', $instance));
            if($instance['id_userinstance'] == $this->uiid || $instance['id_userinstance'] == 0)
            {
                continue;
            }
            
            $this->addressList[] = (int)$instance['id_userinstance'];
        }
    }
    
    /**
     * Обновление, не используется
     *
     * @return bool
     */
    private function _refresh()
    {
        if($_SESSION['profi_game'])
        {
            $noCellData = true;
        } else {
            $noCellData = false;
        }

        // минимальный рефреш
        if(!$this->refresh(2, "", "", "", $noCellData)) 
        {
            return false;
        }
        
        return true;
    }
}