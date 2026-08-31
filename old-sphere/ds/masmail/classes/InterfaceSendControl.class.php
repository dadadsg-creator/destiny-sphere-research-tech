<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:45:57 $';

require_once('./classes/Send.class.php');
require_once('./classes/InterfaceBase.class.php');

class InterfaceSendControl extends InterfaceBase 
{
    function exec()
    {
        if(key_exists('action', $_REQUEST))
        {
            $action = $_REQUEST['action'];  
        }else{
            $action = '';
        }
        
        
        if(key_exists('id', $_REQUEST))
        {
            $id = (int)$_REQUEST['id']; 
        }else{
            $id = 0;
        }
        
        switch($action)
        {
            case 'send':                
                $this->_actionSend($id);
                break;
            case 'view':                
                $this->_actionView($id);
                break;
            default:
                $this->_outForm();  
        }
            
        
    }
    
    
    
    protected function _outForm($data = NULL)
    {
        $common = DS_Common::CreateForCurentDB(0);      
        
        $sql = "SELECT 
                    m.*,
                    (
                        SELECT count(*)
                        FROM ds_mail_recepient as e
                        WHERE 
                            e.id_message = m.id_message
                            AND
                            senddate is null
                    ) as latterstosendcount,
                    (
                        SELECT count(*)
                        FROM ds_mail_recepient as e
                        WHERE 
                            e.id_message = m.id_message
                            AND
                            not(senddate is null)
                    ) as latterssendcount
                FROM ds_mail_message as m
                ORDER BY m.datetosend DESC";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        
        echo "<table border=1>
              <tr><th>DateToSend</th><th>Subject</th><th>StartDate</th><th>EndDate</th><th>LattersCount</th><th>Actions</th></tr>";
        foreach($res as $k=>$messageData)
        {
            $urlView = $_SERVER['PHP_SELF']."?action=view&id=".$messageData['id_message'];
            $urlSend = $_SERVER['PHP_SELF']."?action=send&id=".$messageData['id_message'];          
            
            if($messageData['latterstosendcount'] > 0)
            {           
                $sendAction = "<a href=".$urlSend.">Send</a> | <a href=".$urlView.">View</a>";  
            }else{
                $sendAction = "<a href=".$urlView.">View</a>";
            }
            
            echo "<tr>
                <td>&nbsp;".$messageData['datetosend']."</td>
                <td>".$messageData['subject']."</td>
                <td>&nbsp;".$messageData['startdate']."&nbsp;</td>
                <td>&nbsp;".$messageData['enddate']."&nbsp;</td>
                <td align=center>".$messageData['latterstosendcount']." / ".$messageData['latterssendcount']."</td>
                <td>&nbsp;".$sendAction."</td>              
            <tr>";
        }
        echo "</table>";
    }
    
    function _actionSend($id)
    {
        assert($id > 0);
        
        $common = DS_Common::CreateForCurentDB(0);
        $search = new Search($common);
        
        $sender = new Send($common);    
        $sender->send($id);     
    }
    
    function _actionView($id)
    {
        assert($id > 0);
        
        $common = DS_Common::CreateForCurentDB(0);
        $search = new Search($common);
        
        $messageData = $search->getMessageForSendByID($id);
        
        ?>
        <a href="<?=$_SERVER['PHP_SELF']?>">Back to send list</a><br>
        <br>
        <table border=1>
            <tr>
                <td><b>Subject</b></td>
                <td><?=$messageData[0]['subject']?></td>
            </tr>       
            <tr>
                <td><b>Headers</b></td>
                <td><?=$messageData[0]['headers']?></td>
            </tr>       

            <tr>
                <td><b>Body</b></td>                
                <td><xmp><?=$messageData[0]['body']?></xmp></td>
            </tr>                   
        </table>
        <?
    }
    
    function getName()
    {
        return "Управление рассылкой";
    }
        
    function getOver()
    {
        return "Здесь можно посмотреть список рассылок находящихся в сситеме. А так же осуществить управление ими.";
    }
}


?>