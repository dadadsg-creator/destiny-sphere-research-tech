<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
    <title>Destiny Sphere</title>
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
table.world_list td.list div.list{
    height:367px;
    overflow:auto;
    padding-top:10px;
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
                <!-- ������� -->
                <div class="content">
                    <div class="title">
          <!--<img src="img/content/title/impressum.jpg" alt="" />-->
                 <span style="font-size:23pt;color:white;">�������</span>
          </div>

          <div class="prev_btn"></div>
                    <div class="next_btn">
          </div>
          <table class="world_list">
                        <tr>
              <td class="list" style="padding-left:30px">
              <div class="list">
              <p style="text-align:justify">
               {section name=ix loop=$news}
               <b><i style='font-size:10pt'>{$news[ix].addDate}</i></b>&nbsp;&nbsp; <b style='color:yellow'>{$news[ix].Title}</b><br><br>
               <b>{$news[ix].vvodka}</b><br>
               <b>{$news[ix].arttxt}</b>
               <hr>
               {/section}
              </div>
              </td>
            </tr>
            <tr>
            <td style='padding-left:30px'>{$PageList}</td>
            
            </tr>
                    </table>
                    
                </div>

                <!-- ������ -->
                <div class="online">{$USERS_ONLINE}</div>
                <div class="flags"> 
                  <a href="?step=_news&lang=3"><img src="img/ru_30.png" border=0></a>&nbsp;
                  <a href="?step=_news&lang=2"><img src="img/en_30.png" border=0></a>&nbsp;
                  <a href="?step=_news&lang=1"><img src="img/de_30.png" border=0></a>
                </div>
                
                    <!-- ���� ����� -->
                <div class="left_menu">
                    <div class="btn_1"><a href="?step=_about{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                    <div class="btn_2"><a href="?step=_races{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                    <div class="btn_3"><a href="?step=_help{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                    <div class="btn_4"><a href="?step=_license{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></div>
                </div>
                <!-- ������ ����� -->
                <div class="left_notes">
                    <a href="reg_wizard.php?step=1{if $idp>0}&idp={$idp}&ids={$ids}{/if}"><img src="img/content/buttons/reg.png" width="192" height="83" alt="�����������" /></a>
                    <br>
                    <a href="index_login.php?mode=login"><img src="img/content/buttons/enter2.png" width="192" height="83" alt="���� � ����" /></a>
                </div>

                <!-- ���� ������ -->
                <div class="top_menu">
                    <table class="top_menu">
                        <tr>
                            <td class="btn_1"><a href="/?p=news" title="�������" target="_blank"></a></td>
                            <td class="btn_2"  target="_blank"><a href="{$forumlink}" title="�����"  target="_blank"></a></td>
                            <td class="btn_3"  target="_blank" title="���������"><a href="?step=_impressum{if $idp>0}&idp={$idp}&ids={$ids}{/if}"></a></td>
                        </tr>

                    </table>
                </div>
                
                <!-- ���� ������ -->
                <div class="right_menu">
                    <table class="right_menu">
                        <tr>
                            <td colspan="2" class="head_1"><a href="?step=_screenshots{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="������� ������ ����"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/1.html" title="������� ������ ����" target="_blank" onClick="return openURL('img/content/screenshots/1.html', 610, 455)"></a></td>
                            <td class="item"><a href="img/content/screenshots/2.html" target="_blank" onClick="return openURL('img/content/screenshots/2.html', 610, 365)" title="������� ������ ����"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/3.html" target="_blank" onClick="return openURL('img/content/screenshots/3.html', 610, 365)" title="������� ������ ����"></a></td>
                            <td class="item"><a href="img/content/screenshots/4.html" target="_blank" onClick="return openURL('img/content/screenshots/4.html', 610, 455)"title="������� ������ ����"></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="img/content/screenshots/5.html" target="_blank" onClick="return openURL('img/content/screenshots/5.html', 610, 455)" title="������� ������ ����"></a></td>
                            <td class="item"><a href="img/content/screenshots/6.html" target="_blank" onClick="return openURL('img/content/screenshots/6.html', 610, 365)" title="������� ������ ����"></a></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="head_2"><a href="?step=_videoall{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="����� ������ ����" b></a></td>
                        </tr>
                        <tr>
                            <td class="item"><a href="?step=_video1{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="����� ������ ����"></a></td>
                            <td class="item"><a href="?step=_video2{if $idp>0}&idp={$idp}&ids={$ids}{/if}" title="����� ������ ����"></a></td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="layout_right">&nbsp;</td>
        </tr>
    </table>
    {php}
    if (stripos($_SERVER['HTTP_HOST'],'ds.meta.ua')!==false)
  {
    include 'ua_counters.php';
  }  
  else
  {
    include 'counters.php';
  }
{/php}
</body>
</html>