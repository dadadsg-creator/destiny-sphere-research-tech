<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="../dsstyle.css" type="text/css">
    <link rel="top" href="index.php" title="" />
    <link rel="search" href="search.php" title="" />
    <link rel="help" href="faq.php" title="" />
    <link rel="author" href="memberlist.php" title="" />
    
    <title>{$lang.TITLE}</title>
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>
<body>
<table width=100% height=95% border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td>
            <table width=100% border=0 cellpadding=0 cellspacing=0>
                <!-- header begin -->
                <tr height=86>
                    <td valign=top>
                        <table width=100% height=86 border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td width=500>
                                    <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="500" height="86" id="header_1" align="middle">
                                        <param name="allowScriptAccess" value="sameDomain">
                                        <param name="movie" value="../img/flash/header.swf">
                                        <param name="quality" value="low">
                                        <param name="menu" value="false">
                                        <param name="settings" value="false">
                                        <param name="wmode" value="opaque">
                                        <param name="bgcolor" value="#ffffff">
                                        <embed src="../img/flash/header.swf" quality="low" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
                                    </object></td>
                                <td width=265><img src="../img/background/header_bg.jpg" border=0></td>
                                <td width=100% background="../img/background/header_bg2.gif"></td>  
                            </tr>
                        </table>    
                    <td>    
                </tr>
                <!--begin-->
                <tr height=3>
                    <td background="../img/background/buttons_bg.gif" align=center class=wnd_gen_txt></td>
                </tr>
                <!--end-->
                <tr height=5>
                    <td background="../img/background/bg.gif" align=center class=wnd_gen_txt></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td valign=top height=100%>
            <table width="100%" border="0" cellpadding="0" cellspacing="10">
                <tr>
                <td valign=top>
                        <table width="100%" border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td width=5 height=5><img src="../img/border/01/t_l.gif" border=0 width=5 height=5></td>
                                <td height=5 background="../img/border/01/t.gif"></td>
                                <td width=10 height=5><img src="../img/border/01/t_r.gif" border=0 width=10 height=5></td>
                            </tr>
                            <tr>
                                <td width=5 background="../img/border/01/l.gif"></td>
                                <td width=100% class=wnd_outer valign=top>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <!-- -->
                                        {if !empty($MSG)}
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% align="center" class=wnd_inner>
                                                <div class="wnd_gen_txt_large" style="font-size: 10pt;"><b><font color='#993333'><li>{$lang.$MSG}</li></font></b></div>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        {/if}
                                        <!-- -->
                                        <tr height=25>
                                            <td colspan=3 align=center valign="bottom">
                                                <img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><b>{$lang.COMMENTS_CAPTION}</b></span></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% align="center" class=wnd_inner>
                                                <div class="wnd_gen_txt_large" style="font-size: 10pt;"><b><li>{$lang.COMMENTS}</li></b></div>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <!-- -->                                        
                                        <tr height=25>
                                            <td colspan=3 align=center valign="bottom">
                                                <img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><b>{$lang.REQUEST_TYPE.$REQUEST_TYPE}</b></span></td>
                                        </tr>                                       
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% class=wnd_inner align="center" >
                                                <table width=100% border=0 cellpadding=3 cellspacing=3>
                                                {if $REQUEST_SEND}
                                                    <tr>
                                                        <td align="center">
                                                            <div class="wnd_gen_txt_large" style="font-size: 10pt;"><b><font color='#993333'><li>{$lang.REQUEST_SEND}</li></font></b></div>
                                                        </td>
                                                    </tr>
                                                {else}
                                                    <form action="DV_Request.php" method="POST" enctype="multipart/form-data">
                                                        <input type='hidden' name='Send_request' value='1'>
                                                        <input type='hidden' name='Request_type' value='{$REQUEST_TYPE}'>
                                                        <input type='hidden' name='User_game_world' value='{$DATA.User_game_world}'>
                                                    {if $REQUEST_TYPE == 'Mat'}
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.MAT_VIOLATION_TYPE}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <select name="Violation_Type" class="select_gen">
                                                                <option value="Game_PM" {if $DATA.Violation_Type == 'Game_PM'}selected{/if}>{$lang.MAT_VIOLATION_PM}
                                                                <option value="Army_Name" {if $DATA.Violation_Type == 'Army_Name'}selected{/if}>{$lang.MAT_VIOLATION_ARMY}
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.EMAIL}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Email" value="{$DATA.Email}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.USER_NAME}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="User_Name" value="{$DATA.User_Name}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.GAME_WORLD}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <!-- input type="text" name="Game_World" value="{$DATA.Game_World}" -->
                                                            <select name="Game_World" class=select_gen>
                                                            {foreach from=$DATA.User_Game_Worlds item=WORLD}<option value="{$WORLD.worldname}" {if $DATA.Game_World.worldname == $WORLD.worldname}{/if}>{$WORLD.worldname}{/foreach}
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.DATE}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Date" value="{$DATA.Date}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.TEXT}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Text" size=60 value="{$DATA.Text}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.ADDITIONAL_INFO}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="Additional_Info" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.Additional_Info}</textarea>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.IMAGE} 1:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="file" id="uploaded_images[]" name="uploaded_images[]" class="select_gen" style="border: 0; width: 60%">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.IMAGE} 2:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="file" id="uploaded_images[]" name="uploaded_images[]" class="select_gen" style="border: 0; width: 60%" >
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.IMAGE} 3:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="file" id="uploaded_images[]" name="uploaded_images[]" class="select_gen" style="border: 0; width: 60%">
                                                        </td>
                                                    </tr>
                                                    {elseif $REQUEST_TYPE == 'MultiAccount'}
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.EMAIL}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Email" value="{$DATA.Email}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.USER_NAME}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="User_Name" value="{$DATA.User_Name}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.GAME_WORLD}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <!-- input type="text" name="Game_World" value="{$DATA.Game_World}" -->
                                                            <select name="Game_World" class=select_gen>
                                                            {foreach from=$DATA.User_Game_Worlds item=WORLD}<option value="{$WORLD.worldname}" {if $DATA.Game_World.worldname == $WORLD.worldname}{/if}>{$WORLD.worldname}{/foreach}
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" width=45% align="right" class="wnd_gen_txt_large" style="font-weight: normal;">
                                                            {$lang.REASON}
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="Reason" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.Reason}</textarea>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.ADDITIONAL_INFO}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="Additional_Info" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.Additional_Info}</textarea>
                                                        </td>
                                                    </tr>
                                                    {elseif $REQUEST_TYPE == 'AccountSteal'}
                                                                                                        <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.EMAIL}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Email" value="{$DATA.Email}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.USER_NAME}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="User_Name" value="{$DATA.User_Name}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.USER_COMB}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="User_Comb" value="{$DATA.User_Comb}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" style="font-weight: normal;">
                                                            {$lang.LOST_LIST}:
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="Lost_List" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.Lost_List}</textarea>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.USER_KNOW_PASSWORD}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="User_Know_Password" size=60 value="{$DATA.User_Know_Password}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.ADDITIONAL_INFO}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="Additional_Info" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.Additional_Info}</textarea>
                                                        </td>
                                                    </tr>
                                                    {elseif $REQUEST_TYPE == 'SittingRequest'}
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.EMAIL}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Email" value="{$DATA.Email}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.USER_NAME_FOR_SITTING}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="User_Name_For_Sitting" value="{$DATA.User_Name_For_Sitting}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.SITTER_USER_NAME}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Sitter_User_name" value="{$DATA.Sitter_User_name}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.USER_COMB}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Sitting_Comb" value="{$DATA.Sitting_Comb}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.GAME_WORLD}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <!-- input type="text" name="Game_World" value="{$DATA.Game_World}" -->
                                                            <select name="Sitting_World" class=select_gen>
                                                            {foreach from=$DATA.All_Game_Worlds item=WORLD}<option value="{$WORLD.worldname}" {if $DATA.Sitting_World == $WORLD.worldname}{/if}>{$WORLD.worldname}{/foreach}
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.START_DATE}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Sitting_Start_Date" value="{$DATA.Sitting_Start_Date}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.END_DATE}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Sitting_End_Date" value="{$DATA.Sitting_End_Date}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.ADDITIONAL_INFO}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="Additional_Info" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.Additional_Info}</textarea>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="wnd_gen_txt_large" align="center">
                                                            <div style="width: 60%" align="left">&nbsp;&nbsp;&nbsp;<b>{$lang.SITTING_RULES}</b></div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.READ_RULES}:</b>
                                                        </td>
                                                        <td align="left" class="wnd_gen_txt_large">
                                                            <input type="checkbox" name="Read_Rules" {if $DATA.Read_Rules}checked="1"{/if}>
                                                        </td>
                                                    </tr>
                                                    {elseif $REQUEST_TYPE == 'Same_IP'}
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.EMAIL}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Email" value="{$DATA.Email}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.USER_LIST}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="User_List" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.User_List}</textarea>                                                         
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.CONSTANT_PLAY}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="radio" name="Constant_Play" value="yes" {if !key_exists('Constant_Play', $DATA) || $DATA.Constant_Play == 'yes'}checked{/if} >{$lang.CONSTANT}
                                                            <input type="radio" name="Constant_Play" value="no" {if $DATA.Constant_Play == 'no'}checked{/if} >{$lang.PERIOD}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.SAME_IP_START_DATE}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Start_Date" value="{$DATA.Start_Date}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.SAME_IP_END_DATE}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="End_Date" value="{$DATA.End_Date}" class="select_gen">
                                                        </td>
                                                    </tr>                                           
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.READ_RULES_IP}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="checkbox" name="Read_Rules_Same_Comp" {if $DATA.Read_Rules_Same_Comp}checked="1"{/if}>
                                                        </td>
                                                    </tr>
                                                    {elseif $REQUEST_TYPE == 'Other'}
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.EMAIL}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <input type="text" name="Email" value="{$DATA.Email}" class="select_gen">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width=45% align="right" class="wnd_gen_txt_large" valign="top">
                                                            <b>{$lang.REQUEST_INFO}:</b>
                                                        </td>
                                                        <td width=55% align="left" class="wnd_gen_txt_large">
                                                            <textarea name="Additional_Info" cols="60" rows="4" class="select_gen" style="height: 80px"">{$DATA.Additional_Info}</textarea>
                                                        </td>
                                                    </tr>
                                                    {/if}
                                                    <tr>
                                                        <td colspan="2" class="wnd_gen_txt_large" align="center">
                                                            <input type="submit" value="{$lang.SUBMIT}" class="input_button">
                                                        </td>
                                                    </tr>
                                                    </form>
                                                {/if}
                                                </table>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td colspan=3 height=25 align=center valign=bottom>
                                                <!--img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><!--b>{$lang.EXISTING_WORLDS}</b--></span></td>
                                        </tr>
                                        <!--Description world-->
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% class=wnd_inner>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <a href="javascript:window.close();" class='wnd_header_txt_large'>закрыть окно</a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                    <!-- end world collection -->
                                    </table>
                                </td>
                                <td width=5 background="../img/border/01/r.gif"></td>
                            </tr>
                            <tr>
                                <td width=5 height=10><img src="../img/border/01/b_l.gif" border=0 width=5 height=10></td>
                                <td height=10 background="../img/border/01/b.gif"></td>
                                <td width=10 height=10><img src="../img/border/01/b_r.gif" border=0 width=10 height=10></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td align=center class=copyright_txt>{$lang.COPYRIGHT}</td>
    </tr>
    <tr>
        <td align="center" class="copyright_txt"><a href="{$lang.ABOUT_HREF}" target="_blank" class=copyright_txt>{$lang.ABOUT}</a></td>
    </tr>
    <tr> 
        <td align=center>
            {include file='counters.php'}
        </td>
    </tr>
</table>
</body>