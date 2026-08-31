<?
require "../ds/Main.php";

class UsersOnline
{
    static public function Get($js = true)
    {
        $count = 0;
        
        $cache = DS_Cache::Singleton();

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


if(key_exists('mode', $_GET) && $_GET['mode'] == 'r')   // RAMBLER
{
    echo(UsersOnline::Get(false));
} elseif(key_exists('mode', $_GET) && $_GET['mode'] == 'p') {   // PORTAL NEW
    $counter = UsersOnline::Get(false);
    echo "data=<menu name=\"".$counter."\" />";
} else {    // PORTAL OLD (JavaScript)
    UsersOnline::Get();
}
?>