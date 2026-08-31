<?php

class AddArtefactsControl
{
    static private $action = 'index';
    
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    /**
     * Enter description here...
     *
     * @var ArtFilter
     */
    static private $artFilter;
    
    /**
     * Enter description here...
     *
     * @var ArtList
     */
    static private $artList;
    
    /**
     * Enter description here...
     *
     * @var DE_User
     */
    static private $user;
    
    /**
     * Enter description here...
     *
     * @var integer
     */
    static private $worldID;
    
    /**
     * Enter description here...
     *
     * @var integer
     */
    static private $dbID;
    
    /**
     * Enter description here...
     *
     * @var WorldList
     */
    static private $worldList;
    
    /**
     * Enter description here...
     *
     * @string $comment
     */
    static private $comment = '';
    
    /**
     * Время жизни артефакта
     *
     * @int $lifeTime
     */
    static private $lifeTime = 0;
    
    static private $error = '';
    
    static public function Exec(array $data)
    {
        self::_initData();
        self::_getData($data);
        
        switch (self::$action)
        {
            case 'index':
                self::_showIndex();
                break;
        
            case 'art_list':
                self::_showArtList();
                break;
                
            case 'add':
                self::_addArtefacts();
                break;
                
            default:
                die('Wrong action');
                break;
        }
    }
    
    static private function _initData()
    {
        self::$common = DS_Common::CreateForCurrentDB(0);
        
        self::$artFilter = new ArtFilter();
        self::$artFilter->scope = AddArtefactSearch::GetArtefactScopes(self::$common);
        self::$artFilter->bonus = AddArtefactSearch::GetArtefactBonuses(self::$common);
        self::$artFilter->size = AddArtefactSearch::GetArtefactSizes(self::$common);
                
        self::$artList = new ArtList();
        
        self::$worldList = new WorldList(AddArtefactSearch::GetWorldList(self::$common));
    }
    
    static private function _showIndex()
    {
        AddArtefactOut::ShowIndex(self::$artFilter);
    }
    
    static private function _showArtList()
    {
        if(self::$user instanceof DE_User)
        {
            $name = self::$user->getName();
        } else {
            $name = '';
        }
        
        if(count(self::$artList->getFullArtList()) == 0)
        {
            self::$artList->setArtList(AddArtefactSearch::GetArtefactList(self::$common, self::$artFilter));
        }
        
        AddArtefactOut::ShowArtList(self::$worldList, self::$artFilter, self::$artList, $name, self::$comment, self::$lifeTime);
    }
    
    static private function _addArtefacts()
    {
        if(!empty(self::$error))
        {
            $msg = '<b>'.self::$error.'</b>';
            ShowCMSMessage($msg, 2);
            self::_showArtList();
            return;
        }
        
        if (!self::$user instanceof DE_User) {
            $msg = '<b>WRONG USER NAME</b>';
            ShowCMSMessage($msg, 2);
            self::_showArtList();
            return;
        }
        
        if(empty(self::$comment))
        {
            $msg = '<b>ENTER COMMENT!!</b>';
            ShowCMSMessage($msg, 2);
            self::_showArtList();
            return;
        }
        
        self::$common->setDbId(self::$dbID);
        $tempDb = $_SESSION['dbId'];
        $_SESSION['dbId'] = self::$dbID;
        
        // Проверяем, зареган ли игрок в это мире
        if (!AddArtefactSearch::IsUserRegisteredInWorld(self::$common, self::$user, self::$worldID)) {
            $msg = '<b>USER NOT REGISTERED IN THIS WORLD</b>';
            ShowCMSMessage($msg, 2);
            self::_showArtList();
            return;
        }
        
        AddArtefactSearch::AddArtefacts(self::$common, self::$user, self::$artList, self::$worldID, self::$lifeTime);
        
        $res = '';
        $addedArts = '';
        $resCount = 0;
        
        foreach (self::$artList->getArtListForAdd() as $k => $art) 
        {
            $resCount += $art['count'];
            $res .= $art['artefact_name'].' :'.$art['count']."<br>\n";
            $addedArts .= $art['artefact_name'].': '.$art['count']."
";
        }       
    
        // Логируем
        actionLog( "Add $resCount Artefacts to User:'".self::$user->getName()."' in World:'".self::$worldList->getCurrent()."' life time: ".self::$lifeTime.".<br>\nComment: '".self::$comment."'", trim($res));
        
        if ($resCount > 0) {
            // Пишем игроку в зеленку
            $message = 
'На ваш аккаунт были добавлены артефакты: 
'.$addedArts.'
По всем вопросам вы можете обратиться в службу технической поддержки supt@destinysphere.com 
                
-- 
Вам желает удачи
команда поддержки  DestinySphere';
            
            self::_sendMessage($message);
        }
        
        $msg = '<span class=maintxt><b>'.$resCount.' artefacts added</b></span> DB ID '.self::$dbID;
        ShowCMSMessage($msg, 1);
        
        self::_showArtList();
        
        $_SESSION['dbId'] = $tempDb;
    }
        
    static private function _getData(array $data)
    {
        if(key_exists('action', $data))
        {
            self::$action = $data['action'];
        }
        
        if(key_exists('lifeTime', $data))
        {
            self::$lifeTime = (int)$data['lifeTime'];
            
            if (self::$lifeTime < 0) {
                self::$error = 'WRONG LIFE TIME';
            }
        }
        
        if(key_exists('world', $data))
        {
            $arr = explode('_', $data['world']);
            assert(count($arr) == 2);
            self::$dbID = (int)$arr[0];
            self::$worldID = (int)$arr[1];
            assert(self::$dbID >= 0);
            assert(self::$worldID > 0);
            self::$common->setDbId(self::$dbID);
            self::$worldList->setCurrent($data['world']);
        }
        
        if(key_exists('comment', $data))
        {
            self::$comment = pg_escape_string(trim($data['comment']));
        }
        
        if(key_exists('user', $data))
        {
            $userName = $data['user'];
        } elseif(key_exists('user_name_art', $_SESSION)) {
            $userName = $_SESSION['user_name_art'];
        } else {
            $userName = '';
        }
        
        if(!empty($userName))
        {
            if(!VD_UserName::Validate($userName) || (!$userID = (int)VD_UserName::CheckIfExists($userName, self::$common)))
            {
                self::$error = 'WRONG_USER_NAME';
            } else {
                self::$user = DE_User::CreateFromID($userID);
                $_SESSION['user_name_art'] = $userName;
            }
        }
        
        if(key_exists('scope', $data))
        {
            self::$artFilter->currentScope = (int)$data['scope'];
        }
        
        if(key_exists('bonus', $data))
        {
            self::$artFilter->currentBonus = (int)$data['bonus'];
        }
        
        if(key_exists('size', $data))
        {
            self::$artFilter->currentSize = (int)$data['size'];
        }
        
        if(key_exists('art_list', $data))
        {
            assert(is_array($data['art_list']));
            self::$artList->setArtList($data['art_list']);
        }
    }
    
    static private function _sendMessage($message)
    {
        self::$common->setDBID(0);
        
        $messageScopeID = DS_Message::GetMessageScopeIDByName('SYSTEM');
        assert($messageScopeID > 0);
        
        self::$common->setDBID(self::$dbID);
        
        $fromUIID = 0;

        $subject ="Добавление артефарктов";

        $subject = pg_escape_string(urldecode($subject));
        assert(!empty($subject));
        
        $shortMassage = substr($message, 0, 100);
        $message = pg_escape_string(GL_EncodeGameMessage::Encode(urldecode($message)));
        assert(!empty($message));

        $senderName = 'DestinySphere';
        $recieverName = self::$user->getName();
        $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);
        DS_Message::AddMessageToUser($messageID, (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getID(), self::$worldID));

        //Логиреум отправку письма
        actionLog("Send message in World:'".self::$worldID."'", "Masage: '$shortMassage...'");
    }
}


?>