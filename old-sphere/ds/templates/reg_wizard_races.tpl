<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
    <title>Сфера Судьбы - регистрация</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
  <style  type="text/css">
  {literal}
table.layout td.layout_center{
    width:1001px;
    {/literal}  
  background:transparent url('{$backgroundurl}') no-repeat top left;
  {literal}
    vertical-align:top;
}
  {/literal}
  </style>
    <!--[if lt IE 7]>
    <script type="text/javascript" src="js/fixpng.js"></script>
    <![endif]-->
  <script language="Javascript" src="js/reg.js"></script>
</head>
<body>
    <table class="layout">
        <tr>
            <td class="layout_left">&nbsp;</td>
            <td class="layout_center">
                <div class="min_width"></div>
                <!-- Контент -->
                <div class="content">
                    <div class="title">
          <!--<img src="img/content/title/races.jpg" width="121" height="18" alt="" />-->
                 <span style="font-size:23pt;color:white;">ИГРОВЫЕ РАСЫ</span>
          </div>

          <div class="prev_btn"></div>
                    <div class="next_btn">
          </div>
          <table class="world_list">
                        <tr>
                            <td class="list" style="padding-left:30px;">
              <div class="list">
           <p style="text-align:justify;font-size:9pt">

           <b style="color:yellow">Лиенсу</b><br><br>
           <img src="img/content/races/liensu_bio_scienstist.jpg">
           <img src="img/content/races/liensu_bio_archeolog_girl.jpg">
Представители расы Лиенсу считают, что биология и генетика – это два ключа к доминированию во Вселенной. Лиенсу не знакомо понятие «демографическая проблема», они быстро размножаются и могут в короткие сроки вырастить в своих инкубаторах целые армии. Эту расу нельзя назвать технически развитой, зато в производстве еды им нет равных. А вот с точки зрения науки и технологий, дела у Лиенсу обстоят не так, чтобы очень уж хорошо. Скорее на твердую «тройку» по пятибальной шкале.
Лиенсу – это многочисленная армия и полные холодильники еды.
</p>

              <p style="text-align:justify;font-size:9pt">
              <b style="color:yellow">Воранеры</b><br><br>

             <img src="img/content/races/woraner_worker_man.jpg">
             <img src="img/content/races/woraner_military_girl.jpg">
Воранеры не сильны в «магии» и не отличаются хорошей физической формой, но зато в технике им нет равных. Не удивительно, что они превзошли другие расы в производстве кадериума, чудесного легкого металла, незаменимого при любом строительстве. Но эта раса не стремится к миру во вселенной, она воинственна и, благодаря своей технике, очень сильна в нападении. К сожалению, у Воранеров есть недостатки – слабые интуиция и фантазия не позволяют Воранерам достичь успехов в археологии и оборонительном искусстве.
</p>

           <p style="text-align:justify;font-size:9pt">
           <b style="color:yellow">Псолао</b><br><br>

           <img src="img/content/races/psolao_man_economist.jpg">
           <img src="img/content/races/psolao_girl_military.jpg">

Раса Пcолао целиком и полностью состоит из экстрасенсов и жрецов. Представители этой расы тысячелетиями совершенствовали свои ментальные и духовные способности. Благодаря развитой интуиции и общению с духами, псолао серьезно опережают другие расы в области археологии. И только мудрецы псолао знают, как строить огромные нано-кристаллизаторы – источник загадочных синеватых кристаллов. В ходе военных действий Пcолао предпочитают строить непробиваемые оборонительные рубежи.
Псолао – это отборные войска, боевые маги и древние артефакты.
</p>
              </div>
                            </td>
            </tr>
                    </table>
                </div>

                <!-- Онлайн -->
                <div class="online">{$USERS_ONLINE}</div>
                        <!-- Меню слева -->
                <div class="left_menu">
                    <div class="btn_1"><a href="?step=_about{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                    <div class="btn_2"><a href="?step=_races{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                    <div class="btn_3"><a href="?step=_help{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                    <div class="btn_4"><a href="?step=_license{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                </div>
                <!-- Сноска слева -->
                <div class="left_notes">
                    <a href="reg_wizard.php?step=1{if $idp>0}&idp={$idp}&ids={$ids}{/if}"><img src="img/content/buttons/reg.png" width="192" height="83" alt="Регистрация" /></a>
          <br>
                <a href="index_login.php?mode=login"><img src="img/content/buttons/enter2.png" width="192" height="83" alt="Вход в игру" /></a>
                </div>

                <!-- Меню сверху -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="/?p=news" title="новости" target="_blank"></a></td>
                            <td class="btn_2"  target="_blank"><a href="{$forumlink}" title="Форум"  target="_blank"></a></td>
                            <td class="btn_3"  target="_blank" title="Импрессум"><a href="?step=_impressum{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></td>
                        </tr>

                    </table>
                </div>
                
                <!-- Меню справа -->
                <div class="right_menu">
                    <table class="right_menu">
                        <tr>
                            <td colspan="2" class="head_1"><a href="?step=_screenshots{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="рисунок онлайн игры"></a></td>
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
                            <td colspan="2" class="head_2"><a href="?step=_videoall{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="видео онлайн игры" b></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="?step=_video1{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="видео онлайн игры"></a></td>
                            <td class="item"><a href="?step=_video2{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="видео онлайн игры"></a></td>
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