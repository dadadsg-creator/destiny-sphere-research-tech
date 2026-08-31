<?
exit;
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 1;

require_once 'Main.php';
SU_GameSessionWork::SessionContinue();
if (!$_SESSION['uid'])
{
 die('Ваша сессия истекла');
}
?>

<HTML>
<HEAD>
<meta http-equiv=Content-Type content="text/html;  charset=windows-1251">
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
        font-size : 12px;
    }

</STYLE>
</HEAD>

<script language="JavaScript" type="text/JavaScript" src="a_check.js"></script>
<script language="JavaScript" type="text/JavaScript" src="lib.js"></script>

<BODY>
<b style='font-size:12pt'>Получайте серебряные кредиты бесплатно, участвуя в рекламных предложениях!</b><br><br>
<iframe width="100%" height="600" src="http://iframe.sponsorpay.com/?appid=<?=constant('SPONSORPAY_ID')?>&uid=<?=$_SESSION['uid']?>"></iframe>
</BODY>
</HTML>
<?

?>
