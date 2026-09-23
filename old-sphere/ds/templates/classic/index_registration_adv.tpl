<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.ENCODING}">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <title>{$lang.TITLE}</title>
    <link rel="stylesheet" href="cabinetstyle.css" type="text/css">
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>
<body>

<script language="javascript">
<!--
function openURL (url, target) {$smarty.ldelim}
    window.open(url, target);
{$smarty.rdelim}
//-->
</script>
<table width=100% height=100% border=0 cellpadding=0 cellspacing=0 class=bg_top>
    <tr>
        <td height=100% align=center valign=top>
            <table width=875 border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td valign=top class=bg_left><img src="img/cabinet/bg/bg_left.jpg" border=0></td>
                    <td height=100% valign=top class=bg01>
                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                            <!--begin top line-->
                            <tr>
                                <td height=10 background="img/cabinet/bg/bg_head_02.gif"></td>
                            </tr>
                            <!--end top line-->
                            
                            <!--begin header-->
                            <tr>
                                <td>
                                    <table width=100% height=86 border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td width=765 >
                        <img src="../img/background/ds4.jpg" border=0>
                      </td>
                                            <td width=100% bgcolor=black></td>  
                    </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end header-->
                            
                            <!--begin menu top-->
                            <tr>
                                <td align=center class=bg_top_menu>
                                    <table height=34 border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div class=menu onclick="openURL('../index.php');" valign=middle><span style="position: relative; top: 9px;">ПОРТАЛ</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div class=menu onclick="openURL('http://forum.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">ФОРУМ</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div class=menu onclick="openURL('http://news.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">ГАЗЕТА</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div class=menu onclick="openURL('http://sms.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">DS-Mobile</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div class=menu onclick="openURL('https://billing.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">БИЛЛИНГ</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end menu top-->
                            
                            <!--begin center-->
                            <tr>
                                <td valign=top>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=3>
                                        <tr>
                                            <td valign=top>
                                                <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td colspan=3>
                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td>
                                                                        <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                    <td width=100% align=left class=header_txt>
                                                                        {$lang.REGISTRATION}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                        <td background="img/cabinet/border/03/t.gif"></td>
                                                        <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                    </tr>
                                                    <tr>
                                                        <td background="img/cabinet/border/03/l.gif"></td>
                                                        <td width=100% align=center bgcolor="#ffffff">
                                                            <table width=100%  border=0 cellpadding=0 cellspacing=3>
                                                                <form action="index_login.php" enctype="multipart/form-data" method="post">
                                                                <input type="hidden" name="rambler" value="{$RAMBLER_ID}">
                                                                <input type="hidden" name="mode" value="register">
                                                                <input type="hidden" name="agreed" value="true">
                                                                <input type="hidden" name="coppa" value="0">
                                                                <tr>
                                                                    <td width=100% colspan=2 align=left>
                                                                        <font color='#DD0D1A'>{$ANY_ERROR}</font><br>
                                                                        <font color='#DD0D1A'>{$ERROR_TEXT}</font><br>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width=100% colspan=2 class=delimetr01>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width=50% align=right>
                                                                        <b>{$lang.USERNAME}</b></td>
                                                                    <td width=50% align=left>
                                                                        <input type="text" name="user" value="{$R_USER}" maxlength="40"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td width=50% align=right>
                                                                        <b>{$lang.EMAIL}</b></td>
                                                                    <td width=50% align=left>
                                                                        <input type="text" name="email" value="{$R_EMAIL}" maxlength="255"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td width=50% align=right>
                                                                        <b>{$lang.PASSWORD}</b></td>
                                                                    <td width=50% align=left>
                                                                        <input type="password" name="pass" maxlength="100"></td>
                                    <input type="hidden" name='lang' value=3>
                                    <input type="hidden" name='register' value=1>
                                                                </tr>
                                <tr>
                                 <td width=50% align=right>
                                   <b>Раса:</b>
                                 </td>
                                 <td>
                                  <input type="radio" name="race" value="1"  style="border: 0'" checked>Воранер<br>
                                                                    <input type="radio" name="race" value="2"  style="border: 0'">Лиенсу<br>
                                                                    <input type="radio" name="race" value="3"  style="border: 0'">Псолао<br>
                                                                    &nbsp;&nbsp;<a href="../?p=game_races" target="_blank" class="wnd_gen_txt_large"><b>Информация о свойствах и характеристиках каждой расы</b></a>
                                                                    </td>
                                </tr>
                                                                <tr>
                                                                    <td width=100% colspan=2 class=delimetr01>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=center colspan=2>
                                                                        <input type="submit" name="register" class=submit_yes value="{$lang.BTN_REGISTER}">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width=100% colspan=2 class=delimetr01>
                                                                    </td>
                                                                </tr>
                                                                </form>
                                                            </table>
                                                        </td>
                                                        <td background="img/cabinet/border/03/r.gif"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                        <td background="img/cabinet/border/03/b.gif"></td>
                                                        <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end center-->
                            
                            <tr>
                                <td height=100%></td>
                            </tr>
                            <!--end spaces-->
                            
                            <!--begin line-->
                            <tr>
                                <td height=3 class=f_d01></td>
                            </tr>
                            <!--end line-->
                            
                            <!--begin footer-->
                            <tr>
                                <td>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td width=100% class=f_bg01>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('../?p=press');" class=b_menu><span style="position: relative; top: 20px;">ПРЕССА</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('../?p=support_contact');" class=b_menu><span style="position: relative; top: 20px;">КОНТАКТЫ</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('../?p=about');" class=b_menu><span style="position: relative; top: 20px;">О НАС</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td height=100% valign=top>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td height=22 valign=top><img height=22 src="img/cabinet/bg/bg_footer_t_l.gif" border=0></td>
                                                    </tr>
                                                    <tr>
                                                        <td align=right valign=middle class=copyrights background="img/cabinet/bg/bg_footer_b_l.gif">&nbsp;{$lang.COPYRIGHT}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end footer-->
                        </table>
                    </td>
                    <td valign=top class=bg_right><img src="img/cabinet/bg/bg_right.jpg" border=0></td>
                </tr>
                <tr>
                    <td><img src="img/cabinet/bg/bg_b_l.gif" border=0></td>
                    <td background="img/cabinet/bg/bg_b_b.gif">{include file='counters.php'}</td>
                    <td><img src="img/cabinet/bg/bg_b_r.gif" border=0></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>