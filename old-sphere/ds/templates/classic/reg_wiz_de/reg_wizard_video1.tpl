<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Destiny Sphere - Registration</title>
    <link href="reg.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 7]>
    <script type="text/javascript" src="js/fixpng.js"></script>
    <![endif]-->
{literal}
  <style type="text/css">
table.layout td.layout_center{
    width:1001px;
{/literal}
  background:transparent url('{$backgroundurl}') no-repeat top left;
{literal}
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
        
        //flashvars.video = "http://destinysphere.com/movie/DestinySphere.Game.Igromir.v2.ru(not_orig).2006.flv";
    flashvars.video = "http://www.destinysphere.de/ds/flvplayer/content/mov1.flv";
        flashvars.preview = "/ds/flvplayer/content/video1.jpg";                     
        
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
        <form action="reg_wizard.php" method=POST>
        <input type=hidden name="step" value="1">
        <input type=hidden name="act" value="sbm">
                <!-- ������� -->
        <div class="content">
          <div class="title">
           <img src="img/reg_wiz_de/content/title/video.jpg">
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
         </form>

        <!-- online -->
                <div class="online">{$USERS_ONLINE}</div>     
                <div class="copyright"><span style='font-size:8pt'>&copy; 2003-2010 <a href="http://www.destinysphere.com" style='font-size:8pt;color:white' target="_blank">DestinySphere GmbH</a></span></div>
                <div class="flags"> 
                  <a href="?step=_video1&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=_video1&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=_video1&lang=1"><img src="img/de_30.png" border=0></a>
                </div>
                                
                <div class="left_menu">
                    <div class="btn_1"><a href="?lang=1&step=_about" title="Where am I"></a></div>
                    <div class="btn_2"><a href="?lang=1&step=_races" title="Game races"></a></div>
                    <div class="btn_3"><a href="?lang=1&step=_help" title="Help"></a></div>
                    <div class="btn_4"><a href="?lang=1&step=_license"  title="License agreement"></a></div>
                </div>

                <!-- buttons -->
                <div class="left_notes">
                    <a href="reg_wizard.php?lang=1"><img src="img/reg_wiz_de/content/buttons/reg.png" width="192" height="83" alt="Registration" /></a><br>
                    <a href="index_login.php?lang=1"><img src="img/reg_wiz_de/content/buttons/enter2.png" width="192" height="83" alt="Enter game" /></a>
                
                </div>
                <!-- top menu -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="/"  target="_blank" title="Portal"></a></td>
                            <td class="btn_2"  target="_blank"><a href="http://forum.destinysphere.de" title="Forum"  target="_blank"></a></td>
                            <td class="btn_3"  target="_blank" title="impressum"><a href="?lang=1&step=_impressum"></a></td>
                        </tr>
                    </table>
                </div>
                <!-- right menu -->
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