<?php

interface SU_SessionInterface
{
    static public function Singleton();
    
    function __construct();
    
    static public function session_delete($sid);
    
    static public function sessionExist($sido);
    
    public function session_open();

    public static function session_close();

    public function session_read($sid);

    public function session_write($sid, $data);

    public function session_gc();
    
    static function countUsersOnLine();
}
?>