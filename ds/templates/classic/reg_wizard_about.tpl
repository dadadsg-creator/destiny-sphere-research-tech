<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
    <title>Сфера Судьбы - регистрация</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
  <style  type="text/css">
  {literal}
table.layout td.layout_center{
    width:1001px;
    background:transparent url('img/layout/bg_main_center_clear.jpg') no-repeat top left;
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
           <!--<img src="img/content/title/where.jpg" width="122" height="18" alt="" />-->
                     <span style="font-size:23pt;color:white;">КУДА Я ПОПАЛ?</span>
          </div>

          <div class="prev_btn"></div>
                    <div class="next_btn">
          </div>
          <table class="world_list">
                        <tr>
                            <td class="list" style="padding-left:30px;">
              <p style="text-align:justify;font-size:9pt">
«Сфера Судьбы» – многопользовательская военная онлайн стратегия, где игрок получает в свое управление некую территорию, называемую сотой. Вы сможете застроить вашу соту различными зданиями (добывающими ресурсы, научными, археологическими и т.д.). Добытые, а также честно заработанные грабежами и торговлей ресурсы можно потратить на дальнейшее развитие, колонизацию и, конечно, армию.
</p>

           <p style="text-align:justify;font-size:9pt">
Военные действия – одна из самых важных составляющих «Сферы Судьбы». Со временем Вы выберете свою тактику боя и научитесь правильно составлять армии для атаки или защиты.
Для военных действий нужны боевые единицы, которые гордо именуются «юниты». Они делятся на 4 класса, по силе и заводу, который их производит. 
<br><br>
У каждого юнита есть 4 параметра: атака (нападение), защита, урон и броня. Схема боя проста: игрок создает армию из определенных юнитов, вводит координаты соты врага и отправляет армию в нападение. Какое-то время армия находится в пути, после чего вступает в бой, который происходит автоматически. Затем армия возвращается, победив или проиграв. Уникальность боя состоит в том, что Вы можете принимать участие в бое и в онлайн режиме, тем самым, влияя на его исход.
<br><br>
За 5 лет существования «Сферы Судьбы» сложилось множество тактик защиты и нападения. Существуют классические схемы, но иногда эти схемы изменяют для запутывания врага. Таких схем придуманы десятки, и Вы легко найдете парочку секретов на форуме игры. Экспериментируйте, и, может быть, Вы станете создателем самой новой идеальной схемы по уничтожению вражеских войск. 
</p>
                            </td>
            </tr>
                    </table>
                </div>

                <!-- Онлайн -->
                <div class="online">{$USERS_ONLINE}</div>
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>
                <div class="flags"> 
                  <a href="?step=_about&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=_about&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=_about&lang=1"><img src="img/de_30.png" border=0></a>
                </div>                  
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
             <a href="index_login.php"><img src="img/content/buttons/enter2.png" width="192" height="83" alt="Вход в игру" /></a>
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
</body>
</html>