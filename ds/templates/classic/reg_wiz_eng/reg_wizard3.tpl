<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
{literal}
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Destiny Sphere - Registration</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 7]>
    <script type="text/javascript" src="js/fixpng.js"></script>
    <![endif]-->
  <script language="Javascript" src="js/reg.js"></script>
<style type="text/css">
table.layout td.layout_center{
    width:1001px;
    background:transparent url('img/reg_wiz_en/layout/bg_main_center_clear.jpg') no-repeat top left;
    vertical-align:top;
}
table.world_list td.list div.list{
    height:267px;
    overflow:auto;
    padding-top:10px;
}
</style>
{/literal}
</head>
<body>
    <table class="layout">
        <tr>
            <td class="layout_left">&nbsp;</td>
            <td class="layout_center">
                <div class="min_width"></div>
                <!-- Êîíòåíò -->
                <div class="content">
                    <div class="title"><img src="img/reg_wiz_en/content/title/2.png" alt="" /></div>
                    <div class="pager">
                        <img src="img/reg_wiz_en/content/pager/1.png" width="12" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/2.png" width="23" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/3_act.png" width="24" height="18" alt="" />
                    </div>
         <form action="" method=POST>
          <div class="prev_btn"><a href="?lang=2&step=2"><img src="img/reg_wiz_en/content/pager/prev.png" width="116" height="41" alt="" /></a></div>
                    <div class="next_btn">
          <input type=image src="img/reg_wiz_en/content/pager/next.png" width="116" height="41" alt="" />
          </div>
          
          <input type=hidden name="step" value="3">
          <input type=hidden name="lang" value="2">
          <input type=hidden name="act" value="sbm">
          <input type=hidden name="race" value="{$race}">
          <input type=hidden name="email" value="{$email}">
          <input type=hidden name="user" value="{$user}">
          <input type=hidden name="id_user" value="{$id_user}">
          <input type=hidden name="pass" value="{$pass}">
          <input type=hidden name="rambler" value="{$rambler}">
          
          <table class="world_list">
                        <tr>
                            <td class="list">
                                <div class="list">
                  <table>
                    {foreach from=$ALL_WORLDS item=WORLD}   
                     <tr>
                                            <td class="pic">
                      <img src="images/world_logo/{$WORLD.dbname}_{$WORLD.id_world}.gif" width="54" height="54" alt="" /></td>
                                            <td class="chk_btn"><input class="chkbx" type="radio" name="world" value="{$WORLD.id_world}_{$WORLD.id_base}" {$WORLD.checked}/></td>
                                            <td class="text"><b style="color:yellow">{$WORLD.worldname}</b><br>{$WORLD.short_desc}<br><br></td>
                                         </tr>
                    {/foreach}
                  </table>
                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="service">
                                <label for="home_name">Choose the name of your comb:</a>
                                <div class="input_text2"><input type="text" class="input_text2" name="combname" id="home_name" value="{$combname}" /></div>
                
                                <div class="text_area">
                <!--
                <textarea rows="10" cols="10" wrap="soft" name="license" class="text_area" style="color:white" disabled="disabled"></textarea>-->

                <br><br>
                <span style="color:red;font-weight:bold">{$errors}</span><br>
                The name can contain only Latin letters, numbers and underline symbol.
                </div>
                            </td>
                        </tr>
                    </table>
                </div>
        </form>
                <!-- Îíëàéí -->
                <div class="online">{$USERS_ONLINE}</div>
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>
                <div class="flags"> 
                  <a href="?step=3&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=3&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=3&lang=1"><img src="img/de_30.png" border=0></a>
                </div>                  
                                
                <div class="left_menu">
                    <div class="btn_1"><a href="?lang=2&step=_about" title="Where am I"></a></div>
                    <div class="btn_2"><a href="?lang=2&step=_races" title="Game races"></a></div>
                    <div class="btn_3"><a href="?lang=2&step=_help" title="Help"></a></div>
                    <div class="btn_4"><a href="?lang=2&step=_license"  title="License agreement"></a></div>
                </div>

                <!-- Ñíîñêà ñëåâà -->
                <div class="left_notes">
                    <a href="reg_wizard.php?lang=2"><img src="img/reg_wiz_en/content/buttons/reg.png" width="192" height="83" alt="Registration" /></a><br>
                    <a href="index_login.php?lang=2"><img src="img/reg_wiz_en/content/buttons/enter2.png" width="192" height="83" alt="Enter game" /></a>

                    <br>
                </div>
                <!-- Ìåíþ ñâåðõó -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="/"  target="_blank" title="Portal"></a></td>
                            <td class="btn_2"  target="_blank"><a href="http://forum.destinysphere.de/" title="Forum"  target="_blank"></a></td>
                            <td class="btn_3"  target="_blank" title="impressum"><a href="?lang=2&step=_impressum"></a></td>
                        </tr>
                    </table>
                </div>
                <!-- Ìåíþ ñïðàâà -->
                <div class="right_menu">
                    <table class="right_menu">
                        <tr>
                            <td colspan="2" class="head_1"><a href="?lang=2&step=_screenshots" title="game screenshot"></a></td>
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
                            <td colspan="2" class="head_2"><a href="?lang=2&step=_videoall" title="video of online game"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="?lang=2&step=_video1" title="video"></a></td>
                            <td class="item"><a href="?lang=2&step=_video2" title="video"></a></td>
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