<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2007/10/19 09:48:54 $';
require_once('Main.php');

class UsersOnline
{
    static public function Get($js = true)
    {
        $count = 0;
        
        $cache = DS_CacheMEM::Singleton();

        $identifier = CACHING_PREFIX.'CountOFUsersOnline';
        $lifetime = 120;

        $rs = $cache->get($identifier);

        if($rs !== false)
        {
            $count = $rs;
        } else {
            $count = SU_GameSessionWork::GetUsersOnline();

            $cache->set($identifier, $lifetime, $count);
        }

        if($js)
        {
            echo "document.write(\"".$count."\");\n";
        }
        return $count;
    }
    
    
}
?>