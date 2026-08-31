<?php $CVS_Version = '$Revision$ ; $Date$';

require_once('Search.class.php');

/**
 * Класс для отправки сообщений
 *
 */
class Send
{
    /**
     * Экземпляр класса работы с БД
     *
     * @var Search
     */
    private $search;
    
    /**
     * Счетчик отправленных мылов
     *
     * @var integer
     */
    private $sendMailsCount = 0;
    
    /**
     * Счетчик для организации пауз между порциями отсылки
     *
     * @var integer
     */
    private $tmpSendCounter = 0;
    
    /**
     * Колличество писем между паузами
     *
     * @var integer
     */
    private $sendPakageSize = 1000;
    
    /**
     * Время паузы в секундах между задержками
     *
     * @var integer
     */
    private $pakageTimePeriod = 3;
    
    public function __construct(DS_Common $common)
    {
        $this->search = new Search($common);    
    }
    
    /**
     * Метод для иноциаци отпавки сообщения. Может как отправлять все сообщения которые достпуны
     * для отправки так и сообщение какие-то конретное
     *
     * @param integer $messageID
     * @return void
     */
    public function Send($messageID = null)
    {
        if(is_null($messageID))
        {           
            $messagesForSend = $this->search->getMessageForSendToThisTime();
            
            $str = "START SEND MESSAGES. Select (".count($messagesForSend).") messages.";
            $this->_log($str);
        }else{
            assert((int)$messageID > 0);            
            $messagesForSend = $this->search->getMessageForSendByID((int)$messageID);   
            
            $str = "Start personal send of message with ID = ".$messageID;
            $this->_log($str);
        }

        foreach($messagesForSend as $k=>$messageData)
        {
            $this->_sendMessage($messageData);
        }
        
        $str = "COMPLIT. Sended ($this->sendMailsCount) mails.";
        $this->_log($str);
    }
    
    /**
     * Приватный метод отправки сообщения по назначенным к нему пользователям
     *
     * @param array $messageData
     * @return void
     */
    private function _sendMessage($messageData) 
    {
        assert(is_array($messageData));
        
        assert(key_exists('id_message', $messageData));
        $messageRecipients = $this->search->getRecepietsForMessage((int)$messageData['id_message']);
        
        $str = "Send message with subject '".$messageData['subject']."'";
        $this->_log($str);
        
        $this->search->markStartMessageSend((int)$messageData['id_message']);
        foreach($messageRecipients as $k=>$messageRecipient)
        {
            $this->_sendMail($messageData, $messageRecipient);
        }
        $this->search->markEndMessageSend((int)$messageData['id_message']);             
    }
    
    /**
     * Отправка мыла конкретному пользователю
     *
     * @param array $messageData
     * @param array $messageRecipient
     */
    private function _sendMail($messageData, $messageRecipient)
    {
        assert(is_array($messageData));
        assert(is_array($messageRecipient));
        
        assert(key_exists('username', $messageRecipient));
        $username = $messageRecipient['username'];
        
        assert(key_exists('email', $messageRecipient));
        $email = $messageRecipient['email'];                
        
        assert(key_exists('subject', $messageData));
        $subject = '=?windows-1251?B?'.base64_encode(stripslashes ($messageData['subject'])).'?=';
        
        assert(key_exists('body', $messageData));
        $message = stripslashes ($messageData['body']); 
        
        assert(key_exists('headers', $messageData));
        $headers = stripslashes ($messageData['headers']);  
                            
        $message = str_replace ("%username%", $username, $message);
        $message = str_replace ("%email%", $email, $message);       
        
        $unsubscribeURL = constant('GAME_HOST')."/ds/MassMailUnsubscribe.php?p=".md5($email);
        $message = str_replace ("%unsubscribelink%", $unsubscribeURL, $message);            

        $res = (boolean)mail($email, $subject, $message, $headers);
        //$this->remote_mail($email, $subject, $message, $headers);
        //$res = true;

        if($res)
        {
            $this->sendMailsCount++;
        }
        
        $str = " -- Send mail to $username($email). Result: ".$res;
        $this->_log($str);
        
        $this->search->markSendResulForRecepient((int)$messageRecipient['id_recepient'], $res);
        
        $this->tmpSendCounter++;
        
        //Если пришло время то делаем паузу     
        if($this->tmpSendCounter >= $this->sendPakageSize)
        {
            $this->tmpSendCounter = 0;
            
            $str = " -- PAUSE! $this->pakageTimePeriod seconds";
            $this->_log($str);
            
            sleep($this->pakageTimePeriod);
        }
        
        
    }
    
    private function _log($str)
    {
        echo  SU_DebugTools::GetInternalDate()." $str<br>";
        flush();
        //SU_DebugTools::WriteToLog($str, 'MasMailSend.log');
        
    }       

    private function remote_mail($to, $subj, $body, $headers)
    {
        $to = urlencode($to);
        $subj = urlencode($subj);
        $body = urlencode($body);
        $headers = urlencode($headers);
        $queryString = "to=$to&subj=$subj&body=$body&headers=$headers";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://noname.gamespace.ru/winemaker/mail.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
        $output = curl_exec($ch);
        curl_close($ch);
    }

}



?>