<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_ClienServerTimeSync
{
    static private $time;
    static private $userTimeDelta;
    
    static  public function FixTime()
    {       
        
        self::$time = time().'000';
        
        self::$userTimeDelta = DE_User::CreateForCurrentUser()->getTimeDelta(true);

        //self::$userTimeDelta = DE_User::CreateForCurrentUser()->getTimeDelta(true) - 1;

        //self::$time = (time() + (DE_User::CreateForCurrentUser()->getTimeDelta()-1)*3600).'000';
        //self::$time = (time() + (DE_User::CreateForCurrentUser()->getTimeDelta()+constant('TIME_DELTA_DELTA'))*3600).'000';
    }
    
    static public function OutTime()
    {
        SU_OutputHandler::Singleton()->addString(self::$time, "stime");
        SU_OutputHandler::Singleton()->addString(self::$userTimeDelta, "userGMT");
    }   
}

?>