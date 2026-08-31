<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Destiny Sphere</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 7]>
    <script type="text/javascript" src="js/fixpng.js"></script>
    <![endif]-->
  <script language="Javascript" src="js/reg.js"></script>
   <style type="text/css">
  {literal}
table.layout td.layout_center{
    width:1001px;
    {/literal}  
  background:transparent url('{$backgroundurl}') no-repeat top left;
  {literal}
    vertical-align:top;
}
div.content div.next_btn{
    position:absolute;
    width:116px;
    height:41px;
    margin-top:195px;
    margin-left:260px;
    overflow:hidden;
    z-index:112;
}
  {/literal}
  </style> 
</head>
<body>
    <table class="layout">
        <tr>
            <td class="layout_left">&nbsp;</td>
            <td class="layout_center">
                <div class="min_width"></div>
                <!-- Контент -->
                <div class="content">
                    <div class="title" style="align:top"></div>
                    <div class="pager">
                        <img src="img/reg_wiz_de/content/pager/1_act.png" width="12" height="18" alt="" />
                        <img src="img/reg_wiz_de/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/reg_wiz_de/content/pager/2.png" width="23" height="18" alt="" />
                        <img src="img/reg_wiz_de/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/reg_wiz_de/content/pager/3.png" width="22" height="18" alt="" />
                    </div>
                {if $RAMBLER_LOGIN}
                 <div class="container">
                 <table>
                        <form method='post' action='index_login.php?'>
           <div class="next_btn"><input type=image src="img/reg_wiz_de/content/pager/next.png" width="116" height="41" alt="" /></div>
                        <input type="hidden" name="rambler_id_login" value="1">
                            <tr>
                                <td colspan=2>
                                  <br><br><br><br>
                                  Вы уже зарегистрированы в игре через Rambler ID
                                </td>
                            </tr>
                            <tr>
                              <td>
                                    Логин:
                                </td>
                            <td>
                                    {$USERNAME}
                                </td>
                            </tr>
                         </form>
                        </table>
                    </div>  
                {elseif $RAMBLER_REGISTRATION}
                <form action="" method="POST">
          <div class="next_btn"><input type=image src="img/reg_wiz_de/content/pager/next.png" width="116" height="41" alt="" /></div>
                <input type="hidden" name="user" value="{$USERNAME}">
                <input type="hidden" name="pass" value="{$PASSWORD}">
                    <input type="hidden" name="conf" value="{$PASSWORD}">
                    <input type="hidden" name="lang" value="1">
                  <input type="hidden" name="checkbox" value="on">
                    <input type="hidden" name="email" value="{$EMAIL}">
                    <input type="hidden" name="register" value="1">
                    <input type="hidden" name="rambler" value="1">
                    <input type="hidden" name="ur" value="{$ur}">
                    <input type="hidden" name="rsid" value="{$RSID}">
                    <input type="hidden" name="pid" value="{$PID}">
          <input type=hidden name="step" value="1">
          <input type=hidden name="act" value="sbm">                                                    
                    <div class="container">
                    <table>
                    <tr>
                        <td colspan>
                        Rambler ID:
                        </td>
                        <td>
                            {$EMAIL}
                      </td>
                    </tr>
                    <tr>
                        <td>
                            nick:
                            
                      </td>
                        <td>
                            <div class="input_text"><input type="text" class="input_text" name="user" value="{$USERNAME}"></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Я подтверждаю <a href="?lang=1&step=_license" style="color:white">пользовательское соглашение</a>&nbsp;<input class="chkbx" type="checkbox" name="checkbox" value=1 {$checkedstr}/></td>
                    </tr>                   
                 </table>
            <br /><br />
            <span style="color:red;font-weight:bold">{$errors}</span>                
                 </div> 
                </form>
                {else}
                                                                                                        
          <form action="reg_wizard.php" method=POST>
          <div class="next_btn"><input type=image src="img/reg_wiz_de/content/pager/next.png" width="116" height="41" alt="" /></div>
          <input type=hidden name="step" value="1">
                    <input type="hidden" name="ur" value="{$ur}">          
          <input type=hidden name="act" value="sbm">
                    <input type="hidden" name="lang" value="1">
                    <div class="container">
                        <table>
                            <tr>
                                <td>Nickname</td>
                                <td><div class="input_text"><input type="text" class="input_text" name="user" value="{$user}" /></div></td>
                            </tr>
              <tr>
                                <td>E-mail</td>
                                <td><div class="input_text"><input type="text" class="input_text" name="email" value="{$email}" /></div></td>
                            </tr>
                            <tr>
                                <td>Passwort</td>
                                <td><div class="input_text"><input type="password" class="input_text" name="pass" value="" /></div></td>
                            </tr>
                            <tr>
                                <td>Passwort Bestätigung</td>
                                <td><div class="input_text"><input type="password" class="input_text" name="conf" value="" /></div></td>
                            </tr>
                            <tr>
                                <td colspan="2"> 
                                Ich bin mit  <a href="?lang=1&step=_license" style="color:white">AGB</a> einverstanden
                                <input type=checkbox name="checkbox" value=1></td>
                            </tr>
                        </table>

            <br /><br /><br /><br /><br /><br />
            <span style="color:red;font-weight:bold">{$errors}</span>
          </div>
                </div>
        </form>
        {/if}
        <div class="online">{$USERS_ONLINE}</div>
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>
                <div class="flags"> 
                  <a href="?step=1&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=1&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=2&lang=1"><img src="img/de_30.png" border=0></a>
                </div>
                    <!-- Онлайн -->
                <div class="left_menu">
                    <div class="btn_1"><a href="?lang=1&step=_about" title="Where am I"></a></div>
                    <div class="btn_2"><a href="?lang=1&step=_races" title="Game races"></a></div>
                    <div class="btn_3"><a href="?lang=1&step=_help" title="Help"></a></div>
                    <div class="btn_4"><a href="?lang=1&step=_license"  title="License agreement"></a></div>
                </div>

                <!-- Сноска слева -->
                <div class="left_notes">
                    <!--<img src="img/reg_wiz_de/content/buttons/reg.png" width="192" height="83" alt="Регистрация" />-->
                    <a href="index_login.php?lang=1"><img src="img/reg_wiz_de/content/buttons/enter2.png" width="192" height="83" alt="Enter game" /></a>
                    <br><br>
            </div>
                <!-- Меню сверху -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="/"  target="_blank" title="Portal"></a></td>
                            <td class="btn_2"  target="_blank"><a href="http://forum.destinysphere.de" title="Forum"  target="_blank"></a></td>
                            <td class="btn_3"  target="_blank" title="impressum"><a href="?lang=1&step=_impressum"></a></td>
                        </tr>
                    </table>
                </div>
                <!-- Меню справа -->
                <div class="right_menu">
                    <table class="right_menu">
                        <tr>
                            <td colspan="2" class="head_1"><a href="?lang=1&step=_screenshots" title="game screenshot"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/1.html" title="game screenshot" target="_blank" onClick="return openURL('img/content/screenshots/1.html', 610, 455)"></a></td>
                            <td class="item"><a href="img/content/screenshots/2.html" target="_blank" onClick="return openURL('img/content/screenshots/2.html', 610, 365)" title="game screenshot"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/3.html" target="_blank" onClick="return openURL('img/content/screenshots/3.html', 610, 365)" title="game screenshot"></a></td>
                            <td class="item"><a href="img/content/screenshots/4.html" target="_blank" onClick="return openURL('img/content/screenshots/4.html', 610, 455)"title="game screenshot"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/5.html" target="_blank" onClick="return openURL('img/content/screenshots/5.html', 610, 455)" title="game screenshot"></a></td>
                            <td class="item"><a href="img/content/screenshots/6.html" target="_blank" onClick="return openURL('img/content/screenshots/6.html', 610, 365)" title="game screenshot"></a></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="head_2"><a href="?lang=1&step=_videoall" title="video of online game"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="?lang=1&step=_video1" title="video"></a></td>
                            <td class="item"><a href="?lang=1&step=_video2" title="video"></a></td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="layout_right">&nbsp;</td>
        </tr>
    </table>
{include file='counters.php'}
</body>
</html>