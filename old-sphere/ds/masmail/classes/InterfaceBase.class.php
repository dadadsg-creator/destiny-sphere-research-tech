<?php $CVS_Version = '$Revision$ ; $Date$';

require_once('./classes/Search.class.php');
require_once('./classes/Send.class.php');

class InterfaceBase
{   
    
    function getName()
    {
        assert(false);
    }
    
    function getOver()
    {
        assert(false);
    }
    
    protected function _getAdditionalFormPart($data)
    {
        
    }
    
    protected function _testAdditionalFormPart($data)
    {
        
    }
    
    protected function _getParticipants($common, $langID, $data)
    {       
        assert(false);
    }
        
    protected function _out($msg)
    {
        echo $msg."<br>";
    }
    
    public function exec()
    {
        if(key_exists('action', $_REQUEST))
        {
            $action = $_REQUEST['action'];  
        }else{
            $action = '';
        }
        
        switch($action)
        {
            case 'form':
                try{
                    $this->_testForm($_REQUEST);    
                }catch(Exception $e) {
                    $data = $_REQUEST;
                    $data['errror_code'] = "ERROR: ".$e->getMessage();
                    $this->_outForm($data);         
                    break;
                }

                $this->_formSend($_REQUEST);
                break;
            default:
                $this->_outForm();
                return;
        }               
    }
    
    protected function _testForm($data)
    {       
        $this->_testAdditionalFormPart($data);
        
        if(empty($data['from']))
        {
            throw new Exception('Dont seted field "From"');
        }
        
        if(empty($data['reply']))
        {
            throw new Exception('Dont seted field "ReplyTo"');
        }
        
        if(empty($data['subject']))
        {
            throw new Exception('Dont seted field "Subject"');
        }
        
        if(empty($data['message']))
        {
            throw new Exception('Dont seted field "Body"');
        }       
    }
    
    protected function _formMessage($data)
    {
        assert(is_array($data));
        
        $out = array();
        
        $out['subject'] = $data['subject'];
        
        $out['body'] = $data['message'];        
        
        $out['header'] = "MIME-Version: 1.0\n"
                        ."Content-Type: ".$data['charset']."\n"
                        ."Precedence: bulk\n"
                        ."From: \"Destiny Sphere\" <" . $data['reply'] . ">\n";
    
        return $out;
    }
    
    protected function _formSend($data)
    {
        assert(is_array($data));
        
        $common = DS_Common::CreateForCurentDB(0);
        $search = new Search($common);
        
        $this->_out("START FORM SUBSCRIBE");                                        
        $messageData = $this->_formMessage($data);                 
        $this->_out("Form message complit");

        $res = $this->_getParticipants($common, $data['lang'], $data);
        $this->_out(count($res)." participants selected");

        if(count($res) > 0)
        {
            $common->executeQuery('BEGIN');
            assert(empty($common->errormsg));
        
            $messageID = $search->insertMessage($messageData['subject'], $messageData['body'], $messageData['header']);
            $this->_out("Message inserted");
            
            foreach($res as $k=>$userData)
            {
                $search->insertRecipient($messageID, $userData['id_user'], $userData['email']);
                $this->_out($userData['email']." email subscribet");
            }
        
            $common->executeQuery('COMMIT');
            assert(empty($common->errormsg));   
        }       
        
        $this->_out("SUBSCRIBE COMPLIT");
    }
    
    protected function _outForm($data = null)
    {
        assert(is_null($data) || is_array($data));
        
        ?>
        <?=@$data['errror_code']?>
         <form method='post' action='<?=$_SERVER["PHP_SELF"]?>'>   
            
           <?php $this->_getAdditionalFormPart($data) ?>
            
            Charset: <br>
            <select name='charset'>
                <option value='text/plain; charset=windows-1251'>text/plain; charset=windows-1251</option>
                <option value='text/html; charset=iso-8859-1'>text/html; charset=iso-8859-1</option>
                <option value='text/plain; charset=iso-8859-1'>text/plain; charset=iso-8859-1</option>
                <option value='text/html; charset=windows-1251'>text/html; charset=windows-1251</option>                
            </select><br>
            UserLang: <br>
            <select name='lang'>
                <option value='3'>Russian</option>                  
                <option value='1'>German</option>
                <option value='2'>English</option>                  
            </select><br>
            From:<br> <input type ='text' name = 'from' size = '50' maxlength= '100' value="<?=($data['from']?$data['from']:'noreply@destinysphere.com')?>"><br>
            ReplyTo:<br> <input type ='text' name = 'reply' size = '50' maxlength= '100' value="<?=($data['reply']?$data['reply']:'noreply@destinysphere.com')?>"><br>
            Subject:<br> <input type = 'text' name='subject' size='75' maxlength='250' value=<?=@$data['subject']?>><br>
            Body:<br>
            <textarea name='message' cols='70' rows='30'><?=($data['message']?$data['message']:
            "Вы получили данное письмо, т.к. вы дали своё согласие на получение рассылки Destiny Sphere. Чтобы отписаться, нажмите тут:\n%unsubscribelink%")?></textarea><br>
        
            <input type='hidden' name='action' value = 'form'>
            <input type='submit' name='start' value = ' Start '>
        </form>
        <?
    }
}

?>