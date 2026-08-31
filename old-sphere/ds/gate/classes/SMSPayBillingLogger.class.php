<?
require_once($_SERVER['DOCUMENT_ROOT'].'/ds/Main.php');
require_once($_SERVER['DOCUMENT_ROOT']."/ds/config/gp_config.php");

class SMSPayBillingLogger
{
    private $errFile;
    private $gateName = 'BILLING';
    //private $receiverScript = "http://billing/Receiver/smsLogReceiver.php";
    private $receiverScript = "https://billing.destinysphere.ru/Receiver/smsLogReceiver.php";
    
    public function __construct($userName, $sum, $phone)
    {
        $this->errFile = constant('LOGING_SYSTEM_ERROR_LOGS_PATH')."SMSLogger_errs.log";
        
        if(empty($userName) || empty($sum) || empty($phone))
            $this->logError("wrong params: userName=".$userName.", sum=".$sum.", phone=".$phone);
            
        if( !$userID = DS_User::GetUserIDByName($userName) )
            $this->logError("can't get id of user with userName=".$userName);
                            
        try{
            $key = GK_GateKeyController::GetNewKey($this->gateName);                
        } catch (Exception $e) {            
            GK_Logger::Error($e, false);
            $this->logError("error in getting key error txt=".$e->getMessage());
            
        }
        
        $request = new GK_HTTPRequest();

        $request->setOption('method', 'POST');      
        $request->addHeader("Content-type: application/x-www-form-urlencoded");
        
        // обязательные параметры       
        $request->setVar(md5('gate_magic'), md5(constant('GATE_KEY_MAGIC').date('Y-d-m'))); 
        $request->setVar(md5('gate_key'), $key);
        $request->setVar('uid', $userID);
        $request->setVar('sum', $sum);
        $request->setVar('phone', $phone);              
        $request->setVar('pid', $_SERVER['HTTP_HOST']);
        //$request->setVar('pid', 'ds-game.ru');
        
        $res = $request->get($this->receiverScript);
        
        if($res != md5(date('Y-d-m')))
            $this->logError("Failed send sms to billing \n  returned: ".$res." \n ");

    }
    
    private  function logError($msg)
    {
        if(empty($msg))
            return ;
        $dated = date("Y-m-d H:i:s");
        error_log($dated. " \n ".$msg. " \n ",3, $this->errFile);
        throw new Exception("SMS not logged error:".$msg);      
    }
        
}

?>