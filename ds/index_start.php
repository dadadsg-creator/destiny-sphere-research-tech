<?PHP
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 1;
require_once ('Main.php');
//require_once ('IT.php');
require_once (constant('CLASS_PATH').'Validator.class.php');
require_once (constant('INCLUDE_PATH').'GameDown.inc.php');

if (sizeof($_POST)>0)
{
    if (key_exists('uiid',$_POST))
    {
        $uiid = explode("_",$_POST['uiid']);

        if (sizeof($uiid) > 2)
        {
            if (GameDown($uiid[1], $uiid[2]))
            {
                Header("Status: 302 Moved Temporaly");
                Header("Location: index_down.html");
                exit();
            }
        } else {
            $_POST = array();
        }
    }
    if (key_exists('world',$_POST))
    {
        $worldArr = explode('_', $_POST['world']);

        if (sizeof($worldArr) >= 2)
        {
            if (GameDown($worldArr[1]))
            {
                Header("Status: 302 Moved Temporaly");
                Header("Location: index_down.html");
                exit();
            }
        } else {
            $_POST = array();
        }
    }
}

if(GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

if(!SU_GameSessionWork::SessionContinue())
{
    echo '<script>location.href="index_login.php";</script>';
    exit();
}

$start = new GE_ControlStart();
?>