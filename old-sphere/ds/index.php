<?     
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 1;

require_once 'Main.php';

if(version_compare(PHP_VERSION,'5','>='))
{
    import('domXML');   
}

if(!SU_GameSessionWork::SessionContinue ()) 
{
    $str = "Location: index_login.php"; 
    Header ( $str );
    exit;
}

if(SU_ClientKey::isCheckGetVarsSet())
{
    SU_ClientKey::checkKey(false);
    SU_ClientKey::setKey(false);
    SU_ClientKey::setCheckGetVars(false);
}

if(!key_exists('uid', $_SESSION))
{
    $str = "Location: index_login.php";     
    Header ( $str );
    exit;
}

if(GL_HaveTrialPremium::GetMode(DE_User::CreateFromID((int)$_SESSION['uid']), (int)$_SESSION['dbId']))
{
    $_SESSION['profi_game'] = 1;
    $_SESSION['after_reg_trial'] = 1;
}


//Проверям не закрыта ли игра
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';
if (GameDown(@$_SESSION['dbId'], @$_SESSION['world']))
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

if (!GL_VersionPathWork::ValidateVersionPath($_SESSION['dbId']))
{
    //DS_Error("Uncorrect script version. Over 3 hits - auto-ban!!!", true);
    $str = "Location: index_login.php";     
    Header ($str );
    exit;
}

//Проверяем на тчо, что елси человек попадал в очердь, то он должен был ее пройти.
if(array_key_exists('INDEX_QUEUE_MEMBER', $_SESSION))
{
    if(!($_SESSION['INDEX_QUEUE_MEMBER'] && $_SESSION['INDEX_QUEUE_PASSED']) )
    {
        $str = "Location: index_login.php";     
        Header ( $str );
        exit;
    }   
}


if ( !isset ( $GLOBALS["common"] ) ) 
{
    $common = ClassFactory::getInstance ( "DS_Common" );
}


if(isset($_POST["xmldata"])) 
{
    if ( strpos ( decode($_POST["xmldata"]), "logout")!==FALSE) {
        session_destroy ();     
        Header("Location: index_login.php");
        exit;
    }
}

if ( isset ( $_SERVER["HTTP_REFERER"] ) ) {
    if ( ! ( ereg ( "index_start.php", $_SERVER["HTTP_REFERER"] )
        || ereg ( $_SERVER["PHP_SELF"], $_SERVER["HTTP_REFERER"] ) 
        || ereg ( "index_queue.php", $_SERVER["HTTP_REFERER"] ) 
        || ereg ( "detect_player.php", $_SERVER["HTTP_REFERER"] ) 
        || ereg ( "a_futher.php", $_SERVER["HTTP_REFERER"] ) 
        || ereg ( "index_login_adm.php", $_SERVER["HTTP_REFERER"] ) 
        || ereg ( "reg_wizard.php", $_SERVER["HTTP_REFERER"] ) 
        // в 9 флеше в переменной HTTP_REFERER приходит имя .swf файла
        || ereg ( "flash/ds3", $_SERVER["HTTP_REFERER"]) ) ) {
        //session_destroy();
        Header("Location: index_login.php");
        exit;
    }
}

if ( !isset ( $_SESSION["uiid"] ) || !isset ( $_SESSION["lang"] ) || !isset ( $_SESSION["race"] )
    || !isset ( $_SESSION["world"] ) || !isset ( $_SESSION["ring"] ) || !isset ( $_SESSION["combcomplex"] )
    || !isset ( $_SESSION["comb"] ) || !isset ( $_SESSION["celltype"] ) || !isset ( $_SESSION["combnumber"] ) ) {
    //session_destroy();
    Header("Location: index_login.php");
    exit;
}

/*
if ( isset ( $_POST["xmldata"] ) ) {
    $_SESSION["s_xmldata"] = $_POST["xmldata"];
}
*/

if ( isset ( $_POST["ck"] ) ) {
    $_SESSION["s_ck"] = $_POST["ck"];
}

//print_r($_SESSION);
//print_r($_REQUEST);

$common->setDbId(0);

$vs = new SU_FlashVersions();
$vs->getVersions();

$version = $vs->files['ds3.swf']['version'];


$_SESSION ["game"] = "active";
$_SESSION ["tracing"] = "active";

$score = 0 + @$_SESSION["userscore"];
$body = "<BODY>";

@$_SESSION['world_uid_new'] = @$_SESSION['world_uid'];

    if(@$_SESSION["language"] == 'G') $charset = 'iso-8859-1'; else $charset = 'windows-1251';

    if($score == 0)
    {
        if(!isset($_SESSION["already_shown"]) && defined('URL_BEGINNERS_HELP'))
        {
            $_SESSION ["already_shown"] = "true";
            //$body = "<BODY onLoad=\"window.open('".constant('URL_BEGINNERS_HELP')."');\">";
        }
    }
?>
<HTML>
<HEAD>
<meta http-equiv=Content-Type content="text/html;  charset=<?=$charset?>">
<TITLE>:: DestinySphere ::</TITLE>
<STYLE>
    .copyright {
        font-size: 10px;
        font-family: Verdana, Arial, Helvetica, sans-serif;
        color: #DDDDDD;
        letter-spacing: -1px;
    }

    body {
        background-color: #040D23;
        scrollbar-face-color: #555555;
        scrollbar-highlight-color: #cccccc;
        scrollbar-shadow-color: #000000;
        scrollbar-3dlight-color: #999999;
        scrollbar-arrow-color: #cccccc;
        scrollbar-track-color: #555555;
        scrollbar-darkshadow-color: #333333;
        color: #FFFFFF;
        margin-left:0; margin-right:0; margin-top:0; margin-bottom:0;
        background-image : url(img/background.jpg);
        font-size : 12px;
    }
</STYLE>
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
<script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</HEAD>
<?= $body ?>

<? 
    if(isset($_SESSION["scaling"]))
    {
        if($_SESSION["scaling"] === 'f')
        {
            $width = "840";
            $height = "500";
        } else {                
            $width = "100%";
            $height = "96%";
        }
    } else {
        $width = "100%";
        $height = "96%";
    }
        
    $mode = "1";
    $comb = $_SESSION['comb'];
    require ('flash_code.php');
    echo '<br>';

    if (!$_SESSION['profi_game']) {
        ?>
        <p class="copyright" align="center">
            <a href="../?p=premium_info_page" target="_blank" style="text-decoration: none;"><span style="font-size:11px; color:orange;"><b>Узнать о Destiny Sphere Premium больше...</b>&nbsp;&nbsp;|&nbsp;&nbsp;</span></a>
            <a href="https://billing.destinysphere.ru/?p=premium_trial" style="text-decoration: none;" target="_blank"><span style="font-size:11px; color:orange; text-decoration: none;"><b>Получить тестовый Premium</b></span></a>
        </p>
        <?
    }
    
    include "counters.php";
?>
           
</body>
</html>

