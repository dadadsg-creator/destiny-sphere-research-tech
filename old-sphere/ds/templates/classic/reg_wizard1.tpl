<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
<!--BEGIN META-->
<meta   http-equiv="Content-Type"content="text/html; charset=windows-1251">
<meta   http-equiv="Content-Style-Type"content="text/css">
<meta   HTTP-EQUIV="Reply-To"CONTENT="noreply@destinysphere.com">
<meta   NAME="Title"CONTENT="Стратегическая игра Destiny Sphere Unlimited - бесплатная браузерная онлайн игра в реальном времени, созданная на базе технологии Macromedia Flash.">
<meta   NAME="author"CONTENT="DestinySphere GmbH">
<meta   NAME="publisher"CONTENT="DestinySphere GmbH">
<meta   NAME="copyright"CONTENT="Copyright 2003-2010 DestinySphere GmbH - www.destinysphere.сom">

<meta   NAME="keywords"CONTENT="стратегическая, игра, онлайн, стратегия, бесплатная, Destyny, Sphere, Сфера, Судьбы, браузерная, время, flash, игровой, мир, Unlimited">
<meta   NAME="description"CONTENT="Стратегическая онлайн игра Destiny Sphere – многопользовательская бесплатная браузерная игра в реальном времени. Сценарий игры базируется на увлекательной идее мира колец, что придает игровому пространству исключительную гибкость и безграничность. Игровой мир представляет собой бурную  жизнь в виртуальном пространстве десятков тысяч игроков.">
<meta   NAME="page-topic"CONTENT="Игры, Развлечения, Свободное время">
<meta   NAME="page-type"CONTENT="Интернет-игра">
<meta   NAME="audience"CONTENT="all">

<meta   NAME="language"CONTENT="russian, ru, by, ua, su, русский">
<meta   NAME="distribution"CONTENT="global">
<meta   NAME="rating"CONTENT="general">
<meta   NAME="revisit-after"CONTENT="1 days">
<meta   NAME="robots"CONTENT="index,follow">


<!--END META-->
    
    <title>Сфера Судьбы - online стратегия. Регистрация</title>
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
                        <img src="img/content/pager/1_act.png" width="12" height="18" alt="" />
                        <img src="img/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/content/pager/2.png" width="23" height="18" alt="" />
                        <img src="img/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/content/pager/3.png" width="22" height="18" alt="" />
                    </div>
                {if $RAMBLER_LOGIN}
                 <div class="container">
                 <table>
                        <form method='post' action='index_login.php'>
           <div class="next_btn"><input type=image src="img/content/pager/next.png" width="116" height="41" alt="" /></div>
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
          <div class="next_btn"><input type=image src="img/content/pager/next.png" width="116" height="41" alt="" /></div>
                <input type="hidden" name="user" value="{$USERNAME}">
                <input type="hidden" name="pass" value="{$PASSWORD}">
                    <input type="hidden" name="conf" value="{$PASSWORD}">
                    <input type="hidden" name="lang" value="3">
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
                            Игровой ник:
                            
                      </td>
                        <td>
                            <div class="input_text"><input type="text" class="input_text" name="user" value="{$USERNAME}"></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Я подтверждаю <a href="?step=_license" style="color:white">пользовательское соглашение</a>&nbsp;<input class="chkbx" type="checkbox" name="checkbox" value=1 checked/></td>
                    </tr>                   
                 </table>
            <br /><br />
            <span style="color:red;font-weight:bold">{$errors}</span>                
                 </div> 
                </form>
                {else}
                                                                                                        
          <form action="reg_wizard.php" method=POST>
          <div class="next_btn"><input type=image src="img/content/pager/next.png" width="116" height="41" alt="" /></div>
          <input type=hidden name="step" value="1">
                    <input type="hidden" name="ur" value="{$ur}"> 
                <input type="hidden" name="idp" value="{$idp}"> 
                <input type="hidden" name="ids" value="{$ids}"> 
                
          <input type=hidden name="act" value="sbm">
                    <div class="container">
                        <table>
                            <tr>
                                <td>Ник</td>
                                <td><div class="input_text"><input type="text" class="input_text" name="user" value="{$user}" /></div></td>
                            </tr>
              <tr>
                                <td>e-mail</td>
                                <td><div class="input_text"><input type="text" class="input_text" name="email" value="{$email}" /></div></td>
                            </tr>
                            <tr>
                                <td>Пароль</td>
                                <td><div class="input_text"><input type="password" class="input_text" name="pass" value="" /></div></td>
                            </tr>
                            <tr>
                                <td>Повтор пароля</td>
                                <td><div class="input_text"><input type="password" class="input_text" name="conf" value="" /></div></td>
                            </tr>
                            <tr>
                                <td colspan="2">Я подтверждаю <a href="?step=_license" style="color:white">пользовательское соглашение</a>&nbsp;<input class="chkbx" type="checkbox" name="checkbox" value=1 checked/></td>
                            </tr>
                        </table>

            <br /><br /><br /><br /><br /><br />
            <span style="color:red;font-weight:bold">{$errors}</span>
          </div>
                </div>
        </form>
        {/if}
                <!-- Онлайн -->
                <div class="online">{$USERS_ONLINE}</div>
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>
                <div class="flags"> 
                  <a href="?step=1&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=1&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=1&lang=1"><img src="img/de_30.png" border=0></a>
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
                    <a href="index_login.php"><img src="img/content/buttons/enter2.png" width="192" height="83" alt="Вход в игру" /></a>
                </div>

                <!-- Меню сверху -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="reg_wizard.php?step=_news{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="новости"></a></td>
                            <td class="btn_2"  target="_blank"><a href="http://forum.destinysphere.ru" title="Форум"  target="_blank"></a></td>
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