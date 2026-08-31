<?
//foreach($_POST as $key=>$val) $$key = $val;

require_once $_SERVER['DOCUMENT_ROOT']."/sys/define.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/MYSQLManager.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/UserManager.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/session.class.php";

$session = new session();

$UserManager = new UserManager($session->user_id);

if (!isset($_REQUEST['action'])) $_REQUEST['action'] = '';
switch(strval($_REQUEST['action'])) {
    case "login":
        if (!$_POST['login']) {
            $ErrMsg = local('ADMIN_INPUT_LOGIN');
        } elseif (!$_POST['password']) {
            $ErrMsg = local('ADMIN_INPUT_PASS');
        } else {
            if ($UserManager->Login( $_POST['login'] , md5($_POST['password']) )) {
                $session->register($UserManager->GetUserID());
                if ($session->ErrMsg) $ErrMsg = $session->ErrMsg;
            } else {
                if ($UserManager->ErrMsg) $ErrMsg = $UserManager->ErrMsg;
            }

// echo "<br><br><br>UserManager Otvalilsya!";
        }
        break;
    case "logout":
            $UserManager->Logout();
            $session->logoff();
            if ($UserManager->ErrMsg) $ErrMsg = $UserManager->ErrMsg;
            session_unset('');
        break;
}

if ($UserManager->CheckUser()) headerLocation("/Admin/");


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML><HEAD><TITLE><? echo local('ADMIN_ADMIN_CONSOLE'); ?></TITLE>
<META http-equiv=Content-Type content="text/html; charset=Windows-1251">
<SCRIPT language=JavaScript>
<!--
css = '"ie.css">';
nn4 = (document.layers);
ie4 = (document.all);
if (nn4) { css = '"nn.css">';}
document.write('<LINK REL="stylesheet" TYPE="text/css" HREF=' + css);
//-->
</SCRIPT>


<META content="MSHTML 6.00.3790.0" name=GENERATOR></HEAD>
<BODY bgColor=#ffffff leftMargin=0 topMargin=0 marginheight="0" marginwidth="0"
margin="0">

<table cellspacing="0" cellpadding="0" border="0" align="center" width="423">
<tr><td colspan="2" align="center" style="padding-top: 50px;"><img src="img/logo_small.gif" width="262" height="39" alt="" border="0"></td></tr>
<tr>
    <td colspan="2" align="center" style="padding-top: 25px; padding-bottom: 25px;"><img src="img/authorize.gif" width="350" height="20" alt="" border="0"></td>
</tr>
<?if(isset($ErrMsg) || isset($Msg)) {?>
    <tr bgcolor="#040D23">
        <td align="center" colspan="4">
            <?if ($ErrMsg) echo "<span class=select_txt><b>".$ErrMsg.$m."</b></span><br><br>";?>
            <?if ($Msg) echo "<span class=maintxt><font color=blue><b>".$Msg.$m."</b></font></span><br><br>";?>
        </td>
    </tr>
    <?}?>
<tr>
    <td colspan="2" align="center">
        <table cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td width="4" height="4"><img src="img/border/b1_t_l.gif" border="0"></td>
            <td height="4" background="img/border/b1_t.gif"></td>
            <td width="4" height="4"><img src="img/border/b1_t_r.gif" border="0"></td>
         </tr>

         <tr>
            <td width="4" height="100%" background="img/border/b1_l.gif"></td>
            <td bgcolor="#444E6C">
        <table cellspacing="2" cellpadding="0" border="0">
        <form name="login" action="<?=$_SERVER['PHP_SELF']?>" method="post">
            <input type=hidden name="action" value="login">
        <tr>
            <td align="center"><img src="img/name.gif" width="90" height="14" alt="" border="0"></td>
            <td><input type="text" name="login"  value="<?=isset($_POST['login'])?$_POST['login']:''?>" class="main_in" style="width: 130px"></td>
        </tr>
        <tr><td colspan="2"><img src="img/emp.gif" width="1" height="8" alt="" border="0"></td></tr>
        <tr>
            <td align="center"><img src="img/pass.gif" width="90" height="14" alt="" border="0"></td>
            <td><input type="password" name="password" class="main_in" style="width: 130px"></td>
        </tr>
        <tr><td colspan="2"><img src="img/emp.gif" width="1" height="12" alt="" border="0"></td></tr>
        <tr><td colspan="2" align="center"><input type="IMAGE" src="img/author_button.gif" width="82" height="21" alt="" border="0"></td></tr>
        </table>
            </td>
            <td width="4" height="100%" background="img/border/b1_r.gif"></td>
        </tr>

        <tr>
            <td width="4" height="4"><img src="img/border/b1_b_l.gif" border="0"></td>
            <td height="4" background="img/border/b1_b.gif"></td>
            <td width="4" height="4"><img src="img/border/b1_b_r.gif" border="0"></td>
         </tr>
      </table>
    </td>
</tr>
</table>

<script>
    document.forms['login'].login.focus();
</script>

</body>
</html>