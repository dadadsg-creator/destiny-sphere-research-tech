<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
{literal}
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
    <title>Сфера Судьбы - online стратегия. Регистрация</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 7]>
    <script type="text/javascript" src="js/fixpng.js"></script>
    <![endif]-->
  <script language="Javascript" src="js/reg.js"></script>
<style type="text/css">
table.world_list td.list div.list{
    height:267px;
    overflow:auto;
    padding-top:10px;
}

table.layout td.layout_center{
    width:1001px;
    {/literal}  
  background:transparent url('{$backgroundurl}') no-repeat top left;
  {literal}
    vertical-align:top;
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
                <!-- Контент -->
                <div class="content">
                    <div class="title"><img src="img/content/title/2.png" alt="" /></div>
                    <div class="pager">
                        <img src="img/content/pager/1.png" width="12" height="18" alt="" />
                        <img src="img/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/content/pager/2.png" width="23" height="18" alt="" />
                        <img src="img/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/content/pager/3_act.png" width="24" height="18" alt="" />
                    </div>
         <form action="" method=POST>
          <div class="prev_btn"><a href="?step=2"><img src="img/content/pager/prev.png" width="116" height="41" alt="" /></a></div>
                    <div class="next_btn">
          <input type=image src="img/content/pager/next.png" width="116" height="41" alt="" />
          </div>
          
          <input type=hidden name="step" value="3">
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
                                            <td class="text"><b style="color:yellow">{$WORLD.worldname}</b><br>{$WORLD.description}<br><br></td>
                                         </tr>
                    {/foreach}
                  </table>
                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="service">
                                <label for="home_name">Придумайте название Вашей первой базы:</a>
                                <div class="input_text2"><input type="text" class="input_text2" name="combname" id="home_name" value="{$combname}" /></div>
                
                                <div class="text_area">
                <!--
                <textarea rows="10" cols="10" wrap="soft" name="license" class="text_area" style="color:white" disabled="disabled"></textarea>-->

                <br><br>
                <span style="color:red;font-weight:bold">{$errors}</span><br>
                Название базы может содержать только латинские буквы, цифры и символ подчёркивания.
                </div>
                            </td>
                        </tr>
                    </table>
                </div>
        </form>
                <!-- Онлайн -->
                <div class="online">{$USERS_ONLINE}</div>
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>
                <div class="flags"> 
                  <a href="?step=3&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=3&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=3&lang=1"><img src="img/de_30.png" border=0></a>
                </div>                  
                <!-- Меню слева -->
                <div class="left_menu">
                    <div class="btn_1"><a href="?step=_about"></a></div>
                    <div class="btn_2"><a href="?step=_races"></a></div>
                    <div class="btn_3"><a href="?step=_help"></a></div>
                    <div class="btn_4"><a href="?step=_license"></a></div>
                </div>
                <!-- Сноска слева -->
                <div class="left_notes">
                    <a href="reg_wizard.php?step=1"><img src="img/content/buttons/reg.png" width="192" height="83" alt="Регистрация" /></a>
          <br>
                    <a href="index_login.php?mode=login"><img src="img/content/buttons/enter2.png" width="192" height="83" 
          alt="Вход в игру" /></a>
                </div>


                <!-- Меню сверху -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="/?p=news" title="новости" target="_blank"></a></td>
                            <td class="btn_2"  target="_blank"><a href="{$forumlink}" title="Форум"  target="_blank"></a></td>
                            <td class="btn_3"  target="_blank" title="Импрессум"><a href="?step=_impressum"></a></td>
                        </tr>
                    </table>
                </div>
                <!-- Меню справа -->
                <div class="right_menu">
                    <table class="right_menu">
                        <tr>
                            <td colspan="2" class="head_1"><a href="?step=_screenshots" title="рисунок онлайн игры"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/1.html" title="рисунок онлайн игры" target="_blank" onClick="return openURL('img/content/screenshots/1.html', 610, 455)"></a></td>
                            <td class="item"><a href="img/content/screenshots/2.html" target="_blank" onClick="return openURL('img/content/screenshots/2.html', 610, 365)" title="рисунок онлайн игры"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/3.html" target="_blank" onClick="return openURL('img/content/screenshots/3.html', 610, 365)" title="рисунок онлайн игры"></a></td>
                            <td class="item"><a href="img/content/screenshots/4.html" target="_blank" onClick="return openURL('img/content/screenshots/4.html', 610, 455)"title="рисунок онлайн игры"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/5.html" target="_blank" onClick="return openURL('img/content/screenshots/5.html', 610, 455)" title="рисунок онлайн игры"></a></td>
                            <td class="item"><a href="img/content/screenshots/6.html" target="_blank" onClick="return openURL('img/content/screenshots/6.html', 610, 365)" title="рисунок онлайн игры"></a></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="head_2"><a href="?step=_videoall" title="видео онлайн игры" b></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="?step=_video1" title="видео онлайн игры"></a></td>
                            <td class="item"><a href="?step=_video2" title="видео онлайн игры"></a></td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="layout_right">&nbsp;</td>
        </tr>
    </table>
    {$partnercountercode}
</body>
</html>