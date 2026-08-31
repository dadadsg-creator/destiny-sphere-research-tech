<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
    <title>Сфера Судьбы - регистрация</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 7]>
    <script type="text/javascript" src="js/fixpng.js"></script>
    <![endif]-->
{literal}
  <style type="text/css">
table.layout td.layout_center{
    width:1001px;
    background:transparent url('img/reg_wiz_en/layout/bg_main_center_clear.jpg') no-repeat top left;
    vertical-align:top;
}
table.world_list td.list div.list{
    height:367px;
    overflow:auto;
    padding-top:10px;
}
  </style>
  <script language="Javascript" src="js/reg.js"></script>
<script type="text/javascript" src="flvplayer/swfobject.js"></script>
<script type="text/javascript">
    var flashvars = {};
    
        /* flv player parameters */     
        flashvars.playerpath = "";              
        flashvars.contentpath = ""; 
        
        //flashvars.video = "http://destinysphere.com/movie/DestinySphere.Game.KRI.2007.flv";
    flashvars.video = "http://www.destinysphere.de/ds/flvplayer/content/mov2.flv";
        flashvars.preview = "/ds/flvplayer/content/video2.jpg";                     
        
      flashvars.skin = "skin-applestyle.swf";
        flashvars.skincolor = "0x2c8cbd";
        flashvars.skinscalemaximum = "1";               
    
        //flashvars.captions = "demo-captions.xml";
        
        // ...
        //see documentation for all the parameters
        
        
        /* end */       
                    
        var params = {};
            params.scale = "noscale";
            params.allowfullscreen = "true";
            params.salign = "tl";   
            params.base = ".";  
        
        var attributes = {};
            attributes.align = "left";   
           
        
    /* embed flv player */          
    // adapt the path to flvplayer.swf and expressInstall.swf
    // adapt the display size of the flash file 
    swfobject.embedSWF("flvplayer/flvplayer.swf", "videoPlayer", "320", "265",  "9.0.28", "flvplayer/expressInstall.swf", flashvars, params, attributes);

    /* end */   
</script>
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
          <div class="title">
          <!--<img src="img/reg_wiz_en/content/title/video.jpg">-->
                    <span style="font-size:23pt;color:white;">VIDEO</span>
          </div>
                    <div class="next_btn">
          </div>
                    <table class="world_list">
                        <tr>
              <td class="list" style="padding-left:25px">
               <div class="list">
                 <div  style="margin-left:70px">
                   <div id="videoPlayer">
                   </div>
                 </div>
               </div>
                            </td>
            </tr>
                    </table>
                </div>

                <!-- Онлайн -->
                <div class="online">{$USERS_ONLINE}</div>
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>
                <div class="flags"> 
                  <a href="?step=_video2&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=_video2&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=_video2&lang=1"><img src="img/de_30.png" border=0></a>
                </div>  

                
                <div class="left_menu">
                    <div class="btn_1"><a href="?lang=2&step=_about" title="Куда я попал?"></a></div>
                    <div class="btn_2"><a href="?lang=2&step=_races" title="Игровые расы"></a></div>
                    <div class="btn_3"><a href="?lang=2&step=_help" title="Помощь"></a></div>
                    <div class="btn_4"><a href="?lang=2&step=_license"  title="Лицензионное соглашение"></a></div>
                </div>
                <!-- Сноска слева -->
                <div class="left_notes">
                    <a href="reg_wizard.php"><img src="img/reg_wiz_en/content/buttons/reg.png" width="192" height="83" alt="Registration" /></a><br>
                    <a href="index_login2.php"><img src="img/reg_wiz_en/content/buttons/enter2.png" width="192" height="83" alt="Enter game" /></a>
               </div>
                <!-- Меню сверху -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="/"  target="_blank" title="Portal"></a></td>
                            <td class="btn_2"  target="_blank"><a href="http://forum.destinysphere.de/" title="Forum"  target="_blank"></a></td>
                            <td class="btn_3"  target="_blank" title="Импрессум"><a href="?lang=2&step=_impressum"></a></td>
                        </tr>

                    </table>
                </div>
                <!-- Меню справа -->
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