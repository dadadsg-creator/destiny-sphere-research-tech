<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Destiny Sphere - Registration</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 7]>
    <script type="text/javascript" src="js/fixpng.js"></script>
    <![endif]-->
  <style type="text/css">
  {literal}
table.layout td.layout_center{
    width:1001px;
    background:transparent url('img/reg_wiz_en/layout/bg_main_center2.jpg') no-repeat top left;
    vertical-align:top;
}
  {/literal}
  </style>
  <script language="Javascript" src="js/reg.js"></script>
</head>
<body>
    <table class="layout">
        <tr>
            <td class="layout_left">&nbsp;</td>
            <td class="layout_center">
                <div class="min_width"></div>
        <form action="" method=POST>
        <input type=hidden name="step" value="2">
        <input type=hidden name="lang" value="2">        
        <input type=hidden name="act" value="sbm">
        <input type=hidden name="email" value="{$email}">
        <input type=hidden name="user" value="{$user}">
        <input type=hidden name="id_user" value="{$id_user}">
        <input type=hidden name="pass" value="{$pass}">
        <input type=hidden name="rambler" value="{$rambler}">
        
        <!-- Êîíòåíò -->
                <div class="content">
          <div class="title">
          </div>
                    <div class="pager">
                        <img src="img/reg_wiz_en/content/pager/1.png" width="13" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/2_act.png" width="23" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/_.png" width="11" height="18" alt="" />
                        <img src="img/reg_wiz_en/content/pager/3.png" width="24" height="18" alt="" />
                    </div>
                    <div class="prev_btn">
          <a href="?lang=2&step=1"><img src="img/reg_wiz_en/content/pager/prev.png" width="116" height="41" alt="" /></a>
          </div>
          <div class="next_btn">
          <input type=image src="img/reg_wiz_en/content/pager/next.png" width="116" height="41" alt="" />
          </div>
                    <table class="container">
                        <tr>
                            <td class="item">
                                <table class="block">
                  <tr>
                                        <td colspan="2" class="head">
                                            <img src="img/reg_wiz_en/content/races/lien.png" width="104" height="28" alt="" />
                                            <input class="chkbx" type="radio" name="race" value="2" {$lienchecked}/>
                                        </td>
                                    </tr>
                  <tr>
                                        <td class="image_left" title="Monster"><a href="?lang=2&step=_races"><img src="img/reg_wiz_en/content/races/pic_3.png" width="82" height="83" alt="Monster" /></a>
                    <a href="?lang=2&step=_races" title="Liensu comb" ><img src="img/reg_wiz_en/content/races/pic_4.png" width="82" height="83" alt="Liensu comb" /></a>
                    </td>
                                        <td class="text_right">
                    Masters of biology and genetics, they create new life forms using directional mutations. Liensu breed very fast and can make huge armies in a short time.
                    </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="item">
                <table class="block">
                 <tr>
                                        <td colspan="2" class="head">
                                            <img src="img/reg_wiz_en/content/races/voran.png" width="104" height="28" alt="" />
                                            <input class="chkbx" type="radio" name="race" value="1" {$voranchecked}/>
                                        </td>
                                    </tr>
                  <tr>
                                        <td><a href="?lang=2&step=_races" title="soldier"><img src="img/reg_wiz_en/content/races/pic_1.png" width="84" height="85" alt="soldier" /></a>
<a href="?lang=2&step=_races" title="Voraner comb" ><img src="img/reg_wiz_en/content/races/pic_2.png"  width="84" height="83" alt="Voraner comb" /></a>
                    </td>
                                        <td  class="text_right" align="left">
The race is highly developed in techniques, very warlike<br>and strong<br>in attack. Voraners<br>are the best<br>in caderium<br>production.
                                        </td>
                                    </tr>

                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="item">
                                <table class="block">
                  <tr>
                                        <td colspan="2" class="head">
                                            <img src="img/reg_wiz_en/content/races/psolao.png" width="104" height="28" alt="" />
                                            <input class="chkbx" type="radio" name="race" value="3" {$psolaochecked}/>
                                        </td>
                                    </tr>
                  <tr>
                                        <td class="image_left" > 
                    <a href="?lang=2&step=_races" title="mage"><img src="img/reg_wiz_en/content/races/pic_5.png" width="83" height="82" alt="mage" /></a>
                    <a href="?lang=2&step=_races" title="Psolao comb"><img src="img/reg_wiz_en/content/races/pic_6.png" width="83" height="82" alt="Psolao comb" /></a>
                    </td>

                    <td  class="text_right" style="margin-left:3px;padding-left:3px" >
The race of mystics and mages. They&apos;re luckier then other races in artifacts searching. This race can successfully defend themselves and build huge nano-crystallizers.    
                    </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="item" style="padding-left:35px;padding-top:40px;">
              </td>
                        </tr>
                    </table>
                </div>
          </form>
                <!-- Îíëàéí -->
                <div class="online">{$USERS_ONLINE}</div>               
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>                
                <div class="flags"> 
                  <a href="?step=2&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=2&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=2&lang=1"><img src="img/de_30.png" border=0></a>
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