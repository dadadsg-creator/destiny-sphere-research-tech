<?

require_once ('Main.php');
require_once ('IT.php');
require_once ('classes/Validator.class.php');
require_once ('classes/Logger.class.php' );

SU_GameSessionWork::SessionStart();

if ( !isset ( $GLOBALS["common"] ) ) {
    $common = ClassFactory::getInstance ( "DS_Common" );
    $common->connect();
}

class Login {

    var $errormsg;

    function Login()
    {
        if (!$this->getPostData($_POST)) {
            echo $this->errormsg;
        }
    }

    function getPostData($data)
    {
        global $common;

        if (sizeof($data) == 0)
        {
            $this->showLoginForm();
        } else if ( isset ( $data['enter'] ) )
        {   
            $error = '';

            $worldArr = explode('_', $_POST['id_world']);

            $_POST['id_world'] = $worldArr[0];
            $_SESSION['dbId'] = $worldArr[1];
            $common->setDbId($worldArr[1]);

            $logger = new Logger($_SERVER['DOCUMENT_ROOT'].'/ds/admin/access_history.log');

            $v=new ValidateAdminLogin($_POST['login'], $_POST['pass']);

            //          if (!$v->isValid() || (($_POST['login'] == 'Solo') && ((int)$worldArr[1] != 10) )) {
            //              if($_POST['login'] == 'Solo') $error[1] = "Вам разрешено входить только в турнир"; else
            //              $error = $v->getError();
            //              $logger->log("UNAUTHORIZED", "Failed admin login try: " . $v->getError() . ", with admin-username : " . $_POST['login'] . " and as user: " . $_POST['userlogin']);
            //          }

            if (!$v->isValid() || (($_POST['login'] == 'Solo') && ((int)$worldArr[1] != 10) ))
            {               
                $error = $v->getError();
            }

            if(isset($error[1]))
            {
                $logger->log($_POST['login'], "INVALID ADMIN LOGIN OR PASSWORD");
                $this->showLoginForm("<p><b>".$error[1]."</b></p>");
            }else{
                $sql = sprintf("
                        SELECT 
                            u.ID_Language,
                            l.shortname,
                            u.id_user,
                            u.time_delta,
                            u.password,
                            ui.ID_World,
                            ra.ID_Race,
                            co.ID_Comb,
                            co.CombNumber,
                            co.id_combcomplex,
                            ri.id_ring,
                            ui.id_userinstance,
                            wt.worldtype
                        FROM 
                            DS_UserInstance ui,
                            DS_User u,
                            DS_Race ra,
                            DS_Comb co,
                            ds_language l,
                            ds_ring ri,
                            ds_combcomplex cc,
                            ds_worldhastype wht,
                            ds_worldtype wt
                        WHERE 
                            ui.id_world = wht.id_world
                            AND
                            wht.id_worldtype = wt.id_worldtype
                            AND
                            ui.ID_User = u.ID_User
                            AND 
                            ui.ID_Race = ra.ID_Race
                            AND 
                            co.ID_UserInstance = ui.ID_UserInstance
                            AND 
                            u.id_language = l.id_language
                            AND 
                            co.id_combcomplex = cc.id_combcomplex
                            AND 
                            cc.id_ring = ri.id_ring
                            AND 
                            ui.id_world = %d
                            AND 
                            u.username = '%s'
                        order by ri.ringnumber limit 1",
                        $_POST['id_world'],
                        $_POST['userlogin'] );

                $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );


                if($res === false)
                {
                    $this->errormsg = "SystemError - AdminLogin::getPostData2";
                    return false;
                }

                if(sizeof($res) == 0)
                {
                    $this->errormsg = "BAD USERNAME";
                    $this->showLoginForm ( $this->errormsg );
                    return true;
                }

                $_SESSION["uid"] = (int)$res[0]['id_user'];
                $_SESSION["shortname"] = $res[0]['shortname'];
                $_SESSION["try"] = "";
                $_SESSION["uiid"] = (int)$res[0]['id_userinstance'];
                $_SESSION["lang"] = (int)$res[0]['id_language'];
                $_SESSION["language"] = (int)$res[0]['id_language'];
                $_SESSION["race"] = (int)$res[0]['id_race'];
                $_SESSION["world"] = (int)$res[0]['id_world'];
                $_SESSION["ring"] = (int)$res[0]['id_ring'];
                $_SESSION["combcomplex"] = (int)$res[0]['id_combcomplex'];
                $_SESSION["comb"] = (int)$res[0]['id_comb'];
                $_SESSION["celltype"] = 1;
                $_SESSION["combnumber"] = (int)$res[0]['combnumber'];
                $_SESSION["time_delta"] = (int)($res[0]['time_delta'] + constant('TIME_DELTA_DELTA'));
                $_SESSION["LOGIN_ADM_ENTER"] = true;
                $_SESSION["worldtype"] = $res[0]['worldtype'];
                
                if(sizeof($_POST)>0 && key_exists("premium", $_POST) && $_POST['premium'] == 1)
                {
                    $_SESSION["profi_game"] = 1;
                } else {
                    $_SESSION["profi_game"] = 0;
                }
        
                if(sizeof($_POST)>0 && key_exists("index_start", $_POST) && $_POST['index_start'] == 1)
                {   
                    $_SESSION["s_login"] = $_POST['userlogin'];
                    $_SESSION["s_pass"] = $res[0]['password'];

                    $_SESSION['dbId'] = 0;
                    $url_index = "index_start.php";
                } else {
                    $common->setDbId($_SESSION['dbId']);

                    //Генерируем код для блокировки окошек
                    $windowKey = mt_rand(10, 99);
                    $_SESSION['windowKey'] = $windowKey;

                    $sql = "DELETE FROM ds_userinstancewindowkey
                        WHERE id_userinstance = ".$_SESSION["uiid"];
                    $res = $common->executeQuery ( $sql );
                    assert(empty($common->errormsg));

                    $sql = "INSERT INTO ds_userinstancewindowkey
                        (id_userinstance, windowkey)
                        VALUES
                        (".$_SESSION["uiid"].", ".$windowKey.")";
                    $res = $common->executeQuery ( $sql );
                    assert(empty($common->errormsg));

                    $url_index = "index.php";
                }

                $logger->log($_POST['login'], "logged in under user >>>".$_POST['userlogin']."<<<");
                
                SU_ClientKey::setKey(false);
                SU_ClientKey::setCheckGetVars(true);
                
                if(!GL_VersionPathWork::ValidateVersionPath($_SESSION['dbId']))
                {
                    $dir = GL_VersionPathWork::GetVersionPath($_SESSION['dbId']);
                    $url_index = $dir.$url_index;
                }

                $url_index .= "?ck=".$_SESSION['client_key'];
                
                if(session_id())
                {
                    $url_index .= "&".session_name()."=".session_id();
                }
                
                Header("Location: ".$url_index);
                exit;
            }
        }

        return true;
    }

    function showLoginForm($str = "")
    {
        global $common;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html dir="ltr">
<head>
<? if (constant('DEFAULT_LANGUAGE') == 3) { ?>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<? } else { ?>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<? } ?>
    <meta http-equiv="Content-Style-Type" content="text/css">

    <title>DestinySphere :: Login</title>
    <link rel="stylesheet" href="DestinySphere.css" type="text/css">
</head>
<body bgcolor="#000000" text="#DDDDDD" link="#8BC7FF" vlink="#5493B4">
    <a name="top"></a>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="30" height="30" background="images/corner1.gif" >&nbsp;</td>
            <td height="30" background="images/corner_bg1.gif">&nbsp;</td>
            <td height="30" width="30" background="images/corner2.gif">&nbsp;</td>
        </tr>
        <tr>
        <td background="images/corner_bg2.gif" width="30">&nbsp;</td>
        <td>
        <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#000000">
        <tr>
        <td valign="bottom" class="bodyline">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
        <td align="center"><a href="../index.php"><span class="maintitle"><img src="images/logo_ds.gif" border="0" alt="Главная страница Destiny Sphere" vspace="1" width="196" height="60" /></span></a></td>
        </tr>
        </table>
        <br>
        <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
        <tr>
        <td valign="top" width="100%">
        <table width="100%" height="200" border="0" align="right" cellpadding="1" cellspacing="1">
        <tr>
                      <td valign="top">
        <form method='post' action='<?=$_SERVER["PHP_SELF"]?>'>
        <table width='100%' cellpadding='2' cellspacing='1' border='0' class='forumline'>
        <tr>
        <td height='25' align="center" class='catHead'><span class='genblue'><b>
                                &nbsp;&nbsp;&nbsp;Login</b></span></td>
        </tr>
        <tr><td class='row1' height="30"></td>
        </tr>
        <tr>
        <td align="center" class='row1'><p><span class='gen' style='line-height=150%'>
<?
if ( $str != "" ) {
    print ( "<font color='#993333'>".$str."</font>" );
}
?>
        Benutzername:<br />
        <input class='post' type='text' name='login' size='15' />
        <br />
        Passwort:<br />
        <input class='post' type='password' name='pass' size='15' />
        <br>
        <br>
<?
print "<select name='id_world'>";

$connections = $GLOBALS['dbWorldAccess'];
unset($allrs);

foreach ($connections as $k=>$v) {

    $common->setDbId ($k);

    $sql = "select * from ds_world order by id_world";

    $res = $common->getResultArray("", $sql, "", "", "");

    if ( !$res ) {
        /*$this->errormsg = " SYSTEM ERROR - AdminLogin::getPostData2";

        return false;*/
    };

    for ( $i=0; $i<sizeof ( $res ); $i++ ) {
        print "<option value='".$res[$i]['id_world']."_".$k."'>".$res[$i]['worldname']."</option>";
    };
}
print "</select>";

?>
        <input type='text' class='post' name='userlogin' maxlength='30' />
        <br><br>
        <input type="checkbox" name="premium" value="1" <?if (sizeof($_POST)>0 && !key_exists("premium", $_POST)) {echo "";} else {echo  " checked";}?>> On/Off Premium
        <br><br>
        <input type="checkbox" name="index_start" value="1"> Go to index_start
        <br><br>
        <input type='submit' class='mainoption' name='enter' value='Login' />
        <br>
        <br>
        <a href='#'>Ich habe mein Passwort vergessen!</a><br />
        </span></p> </span>
        <p><br>
        <span class='gensmall' style='line-height=150%'>Beachte
        bitte, dass Du unbedingt die Flash Plug-In Version
        ab <a href="http://www.macromedia.com/software/flash/about/" target="_blank">6.0.65.0</a>
        brauchst.<br />
        &nbsp;</span></p></td>
        </tr>
        </table>
        <input type='hidden' name='<?=session_name()?>' value='<?=session_id()?>'>
        </form>
        </td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        <br>
        <div align="center"><span class="copyright"><br />
        <br />
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
        <td align="center" class="copyright">&copy; Copyright 2003 by
        DestinySphere GmbH</td>
        </tr>
        <tr>
        <td align="center" class="copyright"><a href="#" onClick="MM_openBrWindow('impressum.php','Impressum','scrollbars=yes,width=600,height=400')">Impressum</a></td>
        </tr>
        </table>
        <span class="copyright"><br>
        <br />
        </span></div>
        </td></tr></table>
        </td>
        <td width="30" background="images/corner_bg3.gif">&nbsp;</td>
        </tr>
        <tr>
        <td height="30" width="30" background="images/corner3.gif">&nbsp;</td>
        <td height="30" background="images/corner_bg4.gif">&nbsp;</td>
        <td height="30" width="30" background="images/corner4.gif">&nbsp;</td>
        </table>
        </body>
        </html>
<?
    }
}

$log = new Login;

?>