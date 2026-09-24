<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.ENCODING}">
    <meta http-equiv="Content-Style-Type" content="text/css">
    
    <link rel="stylesheet" href="cabinetstyle.css" type="text/css">
    <link rel="top" href="index.php" title="" />
    <link rel="search" href="search.php" title="" />
    <link rel="help" href="faq.php" title="" />
    <link rel="author" href="memberlist.php" title="" />
    
    <title>{$lang.TITLE}</title>
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
    <style>
        .ss-header {
            font-size: 13px;
            letter-spacing: 0.4px;
            padding: 6px 4px 10px 4px;
        }
        .ss-panel td {
            padding: 6px;
        }
        .ss-panel input[type="text"],
        .ss-panel select {
            height: 24px;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #A1AFC6;
            font-size: 12px;
        }
        .ss-panel input[type="text"]:focus,
        .ss-panel select:focus {
            border-color: #2D447E;
            outline: none;
            box-shadow: 0 0 4px rgba(45,68,126,0.35);
        }
        .ss-panel .submit_yes {
            width: auto;
            min-width: 150px;
            height: 34px;
            background-image: none;
            background: linear-gradient(180deg, #1c9a1e 0%, #0e6b10 100%);
            color: #ffffff;
            border-radius: 5px;
            font-size: 12px;
            letter-spacing: 0.4px;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.25);
            transition: background 0.15s ease, box-shadow 0.15s ease;
        }
        .ss-panel .submit_yes:hover {
            background: linear-gradient(180deg, #22b024 0%, #126b13 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .ss-panel input[type="radio"] {
            transform: scale(1.25);
            vertical-align: middle;
            margin-right: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<script language="javascript">
<!--
{literal}
function openURL (url, target) {
    window.open(url, target);
}

function ShowSupportForm()
{
    if (document.getElementById("support").style.display=="none")
    {
        document.getElementById("support").style.display = "block";
    }
    else
    {
        document.getElementById("support").style.display = "none";
    }
    return false;
}

function ShowWorldList()
{
    if (document.getElementById("worldlist").style.display=="none")
    {
        document.getElementById("worldlist").style.display = "block";
    }
    else
    {
        document.getElementById("worldlist").style.display = "none";
    }
    return false;
}

function ShowMiscBlock()
{
    if (document.getElementById("miscblock").style.display=="none")
    {
        document.getElementById("miscblock").style.display = "block";
    }
    else
    {
        document.getElementById("miscblock").style.display = "none";
    }
    return false;
}

function ShowWorldReg()
{
    if (document.getElementById("world_reg").style.display=="none")
    {
        document.getElementById("world_reg").style.display = "block";
    }
    else
    {
        document.getElementById("world_reg").style.display = "none";
    }
    return false;
}
//-->
</script>

<!-- Check Poll Answer selected -->

<script language="javascript">
function checkAnswer(checked_value)
{
    if ( checked_value == null ) return false;
    for ( var i = 0; i < checked_value.length; i++ ){
        if ( checked_value[i].checked === true ) return true;
    }
    alert('{/literal}{$lang.CHOOSE_ANSWER}{literal}');
    return false;
}
</script>
{/literal}
<table width=100% height=100% border=0 cellpadding=0 cellspacing=0 class=bg_top>
    <tr>
        <td height=100% align=center valign=top>
            <table width=1000 border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td valign=top class=bg_left><img src="img/cabinet/bg/bg_left.jpg" border=0></td>
                    <td height=100% valign=top class=bg01>
                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                            <!--begin top line-->
                            <tr>
                                <td height=10 background="img/cabinet/bg/bg_head_02.gif">{include file='counters.php'}</td>
                            </tr>
                            <!--end top line-->
                            
                            <!-- header begin -->
                            {include file='header.tpl'}
                            <!-- header end -->
                            
                            <!-- BEGIN message -->
                            {if $MSG}
                            <tr>
                                <td valign=top style="padding:5px;">
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr height="5">
                                            <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                            <td background="img/cabinet/border/03/t.gif"></td>
                                            <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                        </tr>
                                        <tr>
                                            <td background="img/cabinet/border/03/l.gif"></td>
                                            <td width=100% align=center valign=top bgcolor="#ffffff">
                                                <span style="font-size:12px; color:#993333;"><li><b>{$MSG}</b></li></span>
                                            </td>
                                            <td background="img/cabinet/border/03/r.gif"></td>
                                        </tr>
                                        <tr height="5">
                                            <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                            <td background="img/cabinet/border/03/b.gif"></td>
                                            <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--begin line-->
                            <tr>
                                <td height=3 class=delimetr01></td>
                            </tr>
                            <!--end line-->
                            {/if}
                            <!-- END message -->
                            
                            <tr>
                                <td height=100% valign=top style="padding:5px;">
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td valign=top style="padding-right:2px;" colspan=2>
                                                <!-- content here!-->
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <!--LEFT BLOCK -->
                                                        <td valign=top>
                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                <!-- START WORLD LIST -->
                                                                {if $WORLDS_TO_ENTER && !$BAN && !$MAIL_ACTIVATION_REQUIRE && $BLOCKS.WORLD_ENTER == 1}
                                                                <tr>
                                                                    <td colspan=3>
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                            <tr>
                                                                                <td>
                                                                                    <img src="img/cabinet/graph/a_01.gif" border=0>
                                                                                </td>
                                                                                <td width=100% align=left class="header_txt ss-header">
                                                                                    {$lang.TEXT1}
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top style="padding:5px;" colspan=3>
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                            <tr height="5">
                                                                                <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                                <td background="img/cabinet/border/03/t.gif"></td>
                                                                                <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td background="img/cabinet/border/03/l.gif"></td>
                                                                                <td width=100% align="justify" valign=top bgcolor="#ffffff" style="padding: 16px;">
                                                                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=3 class="ss-panel">
                                                                                        <form action="index_start.php" method="post" name="start" onSubmit="if((document.start.uiid.value == -1)) return false;">
                                                                                        <tr>
                                                                                            <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                                            <input type="hidden" name="start" value="1">
                                                                                            <td width="45%" align="right"><span><b>{$lang.GAME_WORLD}</b></span>&nbsp;&nbsp;</td>
                                                                                            <td width="55%">
                                                                                                <select name="uiid" style="width:150px;">
                                                                                                    {foreach from=$WORLDS_TO_ENTER key=ID item=WORLD}<option {if $WORLDS_DOWN[$ID]}disabled{else}value="{$ID}" selected="1"{/if}>{$WORLD} {if $WORLDS_DOWN[$ID]}{$lang.DOWN}{/if}{/foreach}
                                                                                                </select>
                                                                                            </td>
                                                                                        </tr>
                                                                                        {if $BLOCKS.PROTECTION_CODE == 1}
                                                                                        <tr>
                                                                                            <td align="right"><span><b>{$lang.PROTECTION}</b></span>&nbsp;&nbsp;</td>
                                                                                            <td>
                                                                                                <input type="text" size="5" maxlength="4" name="protection">
                                                                                                <script language="Javascript">
                                                                                                <!--
                                                                                                document.write('<img src="./ImageCode/{$RANDOM_IMG}?{$S_NAME}={$S_ID}&'+new Date().getTime()+'" align="absmiddle" alt="" width="110" height="30" vspace=4 hspace=0 border=1>')
                                                                                                //-->
                                                                                                </script>
                                                                                                <noscript>
                                                                                                    <Img src="./ImageCode/{$RANDOM_IMG}?{$S_NAME}={$S_ID}" align="absmiddle" alt="" width="110" height="30" vspace=4 hspace=0 border=1>
                                                                                                </noscript>
                                                                                                <span>{$lang.PROTECTION_HINT}</span>
                                                                                            </td>
                                                                                        </tr>
                                                                                        {/if}
                                                                                        <tr>
                                                                                            <td colspan=2 class=delimetr01></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td></td>
                                                                                            <td>
                                                                                                <input {if !$HAS_WORLD_TO_ENTER}disabled type="button"{else}type="submit"{/if} style="font-weight:bold;font-size:11pt" value="{$lang.SUBMIT}" class="submit_yes"/>
                                                                                            </td>
                                                                                        </tr>
                                                                                        </form>
                                                                                    </table>
                                                                                </td>
                                                                                <td background="img/cabinet/border/03/r.gif"></td>
                                                                            </tr>
                                                                            <tr height="5">
                                                                                <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                                <td background="img/cabinet/border/03/b.gif"></td>
                                                                                <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                {/if}  
                                                                <!-- END WORLD LIST -->
                                                                
                                                                <!-- BEGIN REGISTRATION -->
                                                                {if $WORLDS_TO_REG && !$BAN && !$MAIL_ACTIVATION_REQUIRE && $BLOCKS.REGISTRATION == 1}
                                                                <tr>
                                                                    <td colspan=3>
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                            <tr>
                                                                                <td>
                                                                                    <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                <td width=100% align=left class="header_txt ss-header">
                                                                                    <a href="" onClick="return ShowWorldReg()">{$lang.REGISTRATION}</a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                     <td valign=top style="padding:5px;" colspan=3>
                                                                      <div id="world_reg" style='display:{if !$WORLDS_TO_ENTER}block{else}none{/if}'>
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=3 bgcolor='#ffffff' class="ss-panel" style="padding:16px;">
                                                                                <form action='index_start.php' method='post'>
                                                                                <tr>
                                                                                    <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                                    <td width="35%" align=right><b>{$lang.GAME_WORLD}&nbsp;&nbsp;</b></td>
                                                                                    <td width="65%">
                                                                                        <select name="world" maxlength="14">        
                                                                                            {html_options options=$WORLDS_TO_REG selected=$REG_WORLD}
                                                                                        </select>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td width="35%" align=right><b>{$lang.RACE}&nbsp;&nbsp;</b></td>
                                                                                    <td width="65%" valign=middle>
                                                                                        {foreach from=$RACES item=RACE_NAME key=RACE}
                                                                                            <input type="radio" name="race" value="{$RACE}" {if $RACE == $REG_RACE}checked{/if} style="border: 0'">{$RACE_NAME}&nbsp;
                                                                                        {/foreach}
                                                                                        <br><br><a href="{$lang.RACE_INFO_HREF}" target="_blank" class="wnd_gen_txt_large"><b>{$lang.RACE_INFO}</b></a><br>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <input type='hidden' name='uid' value='{$UID}'>
                                                                                    <input type='hidden' name='add' value='1'>
                                                                                    <td width="35%" align=right>
                                                                                        <b>{$lang.COMB_NAME}&nbsp;&nbsp;</b></td>
                                                                                    <td width="65%">
                                                                                        <input type="text" name="comb" maxlength="14" value="{$REG_COMBNAME}"></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td colspan=2 class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td colspan=2 align="left">
                                                                                        <i>{$lang.COMB_NAME_HINT}</i></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td colspan=2 class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td width="35%"></td>
                                                                                    <td width="65%">
                                                                                        <input type="submit" name="add_btn" value="{$lang.ADD}" class="submit_yes" /></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td colspan=2 class=delimetr01></td>
                                                                                </tr>
                                                                                </form> 
                                                                                {if $WORLDS_TO_REG && !$WORLDS_TO_ENTER}
                                                                                <tr>
                                                                                    <td colspan="2" align="left">
                                                                                        <b>{$lang.RECOMENDED_FOR_NUBIE}</b>
                                                                                        {foreach from=$ALL_WORLDS item=WORLD}
                                                                                            {if $WORLD.for_new_players == 't'}
                                                                                                <li><span><b>{$WORLD.worldname}</b></span>
                                                                                            {/if}
                                                                                        {/foreach}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height="5" colspan="2"></td>
                                                                                </tr>
                                                                                {/if}
                                                                            </table>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <!--begin line-->
                                                                <tr>
                                                                    <td colspan=3 height=3 class=delimetr01></td>
                                                                </tr>
                                                                <!--end line-->
                                                                {/if}
                                                                <!-- END BEGIN REGISTRATION --> 
                                                                
                                                                <!-- BEGIN NEW USER HELP -->
                                                                {if $NEW_USER_HELP}
                                                                <tr>
                                                                    <td  colspan=3 valign=top style="padding:5px;">
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                            <tr height="5">
                                                                                <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                                <td background="img/cabinet/border/03/t.gif"></td>
                                                                                <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td background="img/cabinet/border/03/l.gif"></td>
                                                                                <td width=100% align="justify" valign=top bgcolor="#ffffff" style="padding: 5px;">
                                                                                {$lang.NEW_USER_HELP}
                                                                                </td>
                                                                                <td background="img/cabinet/border/03/r.gif"></td>
                                                                            </tr>
                                                                            <tr height="5">
                                                                                <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                                <td background="img/cabinet/border/03/b.gif"></td>
                                                                                <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <!--begin line-->
                                                                <tr>
                                                                    <td colspan=3 height=3 class=delimetr01></td>
                                                                </tr>
                                                                <!--end line-->
                                                                {/if}
                                                                <!-- END NEW USER HELP -->
                                                                
                                            
                                                                <!-- MAIL ACTIVATION INFO -->
                                                                {if !$MAIL_ACTIVATION}
                                                                <tr>
                                                                    <td colspan=3>
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr>
                                                                                    <td>
                                                                                        <img src="img/cabinet/graph/a_01.gif" border=0>
                                                                                    </td>
                                                                                    <td width=100% align=left class=header_txt>
                                                                                            {$lang.MAIL_ACTIVATION_TITLE}
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr height="5">
                                                                        <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                        <td background="img/cabinet/border/03/t.gif"></td>
                                                                        <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td background="img/cabinet/border/03/l.gif"></td>
                                                                        <td width=100% height=100% valign=top bgcolor="#ffffff">
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr>
                                                                                    <td align="center" style="font-size:11px;">
                                                                                        <font color='#993333'><li>{$lang.PERSON} <b>{$LOGIN}</b>! {$lang.TEXT_MAIL_ACTIVATION_1}
                                                                                        <br>
                                                                                        {if !$MAIL_ACTIVATION_REQUIRE}
                                                                                            <b>{$lang.MAIL_ACTIVATION_DAYS}&nbsp;{$MAIL_ACTIVATION_DAYS_LEFT}.</b>
                                                                                            <br>
                                                                                        {/if}
                                                                                        {$lang.TEXT_MAIL_ACTIVATION_2}</li></font>
                                                                                    </td>
                                                                                </tr>
                                                                                {if $CAN_CHANGE_MAIL}
                                                                                <tr>
                                                                                    <td align="center" style="font-size:11px;">
                                                                                        <br>{$lang.CAN_CHANGE_MAIL}
                                                                                        <form action="index_start.php" method="POST">
                                                                                            <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                                            <input type="hidden" name="changemail" value="1">
                                                                                            {$lang.NEW_MAIL}: <input type='text' name="new_email"><br><br>
                                                                                            <input type="submit" value="{$lang.CHANGE}" class="submit01">
                                                                                        </form>
                                                                                    </td>
                                                                                </tr>
                                                                                {/if}
                                                                                <tr align="center" style="font-size:11px;">
                                                                                    <td>
                                                                                        <form action="index_start.php" method="POST">
                                                                                            <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                                            <input type="hidden" name="activationemail" value="1">
                                                                                            {$lang.CHANGE_MAIL}<br><br>
                                                                                            <input type="submit" value="{$lang.SEND_MAIL}" class="submit01">
                                                                                        </form>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td background="img/cabinet/border/03/r.gif"></td>
                                                                    </tr>
                                                                    <tr height="5">
                                                                        <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                        <td background="img/cabinet/border/03/b.gif"></td>
                                                                        <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                    </tr>
                                                                    {/if}
                                                                    <!-- END MAIL ACTIVATION INFO --> 
                                                                    
                                                                    <!-- BAN INFO -->
                                                                    {if $BAN}
                                                                    <tr>
                                                                        <td colspan=3>
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr>
                                                                                    <td>
                                                                                        <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                    <td width=100% align=left class=header_txt>
                                                                                        {$lang.BAN_TITLE}
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr height="5">
                                                                        <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                        <td background="img/cabinet/border/03/t.gif"></td>
                                                                        <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td background="img/cabinet/border/03/l.gif"></td>
                                                                        <td width=100% height=100% valign=top bgcolor="#ffffff">
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr>
                                                                                    <td align="center" style="font-size:12px;">
                                                                                        <font color='#993333'><li>{$lang.TEXT_BAN}</li></font>
                                                                                        {if count($BAN_REASON)}
                                                                                            {$lang.BAN_REASON}&nbsp;{$BAN_REASON.text}<br>
                                                                                            {$lang.BAN_ENDDATE}&nbsp;{$BAN_REASON.enddate}<br>
                                                                                        {/if}
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td background="img/cabinet/border/03/r.gif"></td>
                                                                    </tr>
                                                                    <tr height="5">
                                                                        <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                        <td background="img/cabinet/border/03/b.gif"></td>
                                                                        <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                    </tr>
                                                                    {/if}
                                                                    <!-- END OF BAN INFO -->
                                                                    
                
                                                                    <!-- POLL -->                      
                                                                    {if !empty($POLL_QUESTION)}
                                                                    <tr>
                                                                            <td colspan=3>
                                                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                        <td width=100% align=left class=header_txt>
                                                                                            {$lang.POLL}
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                    </tr>
                                                                    <form name="poll"  action="index_start.php" method="post">
                                                                    <input type="hidden" name="submitpoll" value="1">
                                                                    <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                    <input type="hidden" name="id_question" value="{$POLL_QUESTION.id_question}">
                                                                    <tr>
                                                                        <td valign=top style="padding:5px;" colspan=3>
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr height="5">
                                                                                    <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                                    <td background="img/cabinet/border/03/t.gif"></td>
                                                                                    <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td background="img/cabinet/border/03/l.gif"></td>
                                                                                    <td width=100% align="justify" valign=top bgcolor="#ffffff" style="padding: 5px;">
                                                                                        <table border="0" width="100%">
                                                                                        <tr>
                                                                                            <td colspan="2">
                                                                                                <b>{$POLL_QUESTION.question}</b>
                                                                                            </td>
                                                                                        </tr>
                                                                                        {foreach from=$POLL_ANSWERS key=key item=ANSWER}
                                                                                        <tr>
                                                                                            <td colspan="2">
                                                                                                <input type=radio name=id_answer value={$ANSWER.id_answer} style="border: 0"> {$ANSWER.answer}
                                                                                            </td>
                                                                                        </tr>
                                                                                        {/foreach}
                                                                                        <tr>
                                                                                            <td align="right">
                                                                                                <input type="submit" class="submit_yes" value="{$lang.VOTE}" onclick="return checkAnswer(document.poll.id_answer)">
                                                                                                &nbsp;&nbsp;
                                                                                            </td>
                                                                                            <td align="left">
                                                                                                &nbsp;&nbsp;
                                                                                                <input type="submit" name="abstain" class="submit_no" value="{$lang.REFRAIN}" onclick="javascript:return confirm('{$lang.CONFIRM_VOTE}')">
                                                                                            </td>
                                                                                        </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                                </tr>
                                                                                <tr height="5">
                                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    </form>
                                                                    {elseif !empty($POLL_RESULT)}
                                                                    <tr>
                                                                        <td valign=top style="padding:5px;" colspan=3>
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr>
                                                                                    <td colspan=3>
                                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                                <tr>
                                                                                                    <td>
                                                                                                        <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                                    <td width=100% align=left class=header_txt>
                                                                                                        {$lang.POLL}
                                                                                                    </td>
                                                                                                </tr>
                                                                                            </table>
                                                                                        </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td valign=top style="padding:5px;">
                                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                            <tr>
                                                                                                <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                                                <td background="img/cabinet/border/03/t.gif"></td>
                                                                                                <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td background="img/cabinet/border/03/l.gif"></td>
                                                                                                <td width=100% align="justify" valign=top bgcolor="#ffffff" style="padding: 5px;">
                                                                                                    <table border="0" width="100%">
                                                                                                    <tr>
                                                                                                        <td colspan="2">
                                                                                                            <b>{$POLL_RESULT.0.question}</b>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    {foreach from=$POLL_RESULT key=key item=DATA}
                                                                                                    
                                                                                                    <tr>
                                                                                                        <td>
                                                                                                            {$DATA.answer}
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <img src='/ds/drowimage.php?width={$PERCENT}'>
                                                                                                        
                                                                                                            {$DATA.count} {$lang.HUM}
                                                                                                        </td>
                                                                                                    </tr>   
                                                                                                    {/foreach}
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
                                                                    {/if}
                                                                    <!-- END POLL -->
                                                                    
                                                                    <!-- SUPPORT -->
                                                                    {if $BLOCKS.SUPPORT_FORM == 1}
                                                                     <tr>
                                                                       <td colspan=3>
                                                                             <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                 <tr>
                                                                                    <td>
                                                                                     <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                        <td width=100% align=left class=header_txt>
                                                                                            <a href="" onClick="return ShowSupportForm();">{$lang.SUPPORT_CAPTION}</a>
                                                                                        </td>
                                                                                 </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                    <td valign=top style="padding:5px;" colspan=3>
                                                                        <div id="support" style='display:{if $POLL_QUESTION || !$WORLDS_TO_ENTER}none{else}block{/if}'>
                                                                            <table width=100% border=0 cellpadding=0 cellspacing=3  bgcolor="#ffffff">
                                                                                <tr>
                                                                                    <td colspan="2" align="left" valign=top>{$lang.PREMIUM_SUPPORT}</td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td colspan="2" class="delimetr01"></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="right" nowrap valign=top><b>{$lang.GAME_WORLD}</b></td>
                                                                                    <td valign=top>
                                                                                        <form action="" method="POST">
                                                                                        <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                                        <input type="hidden" name="support" value="1">
                                                                                        <select name="world" class=select_gen style="width:150px;">
                                                                                        {foreach from=$WORLDS_TO_ENTER item=WORLD}<option value="{$WORLD}">{$WORLD}{/foreach}<option value=" --- ">&nbsp;-&nbsp;
                                                                                        </select>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="right" valign="top" nowrap><b>{$lang.SUPPORT_MESSAGE}</b></td>
                                                                                    <td valign=top><textarea name="message" cols="50" rows="4"></textarea></td>
                                                                                </tr>
                                                                                <tr>
                                                                                  <td></td>
                                                                                    <td valign=top><input type="submit" value="{$lang.SEND}" class="submit01"></td> 
                                                                                </tr>
                                                                                </form>
                                                                            </table>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    {/if}
                                                                    <!-- END support -->
                                                                    
                                                                    <!--begin line-->
                                                                    <tr>
                                                                        <td colspan=3 height=3 class=delimetr01></td>
                                                                    </tr>
                                                                    <!--end line-->                 
                                                                    <tr>
                                                                        <td colspan=3>
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr>
                                                                                    <td>
                                                                                        <img src="img/cabinet/graph/a_01.gif" border=0>
                                                                                    </td>
                                                                                    <td width=100% align=left class=header_txt>
                                                                                        <a href="" onClick="return ShowMiscBlock();">{$lang.MISC}</a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td valign=top style="padding:5px;" colspan=3>
                                                                            <div id="miscblock" style='display:none'>
                                                                            <table>
                                                                                {if $BLOCKS.WORLD_TRANSFER_QUEST}
                                                                                    <tr>
                                                                                        <td height=2 align="left">
                                                                                            {$lang.WORLD_TRANSFER_QUEST_HREF}
                                                                                        </td>
                                                                                    </tr>
                                                                                {/if}
                                                                                {if $BLOCKS.COMB_TRANSFER}
                                                                                    <tr>
                                                                                        <td height=2 align="left">
                                                                                            {$lang.COMB_TRANSFER_HREF}
                                                                                        </td>
                                                                                    </tr>
                                                                                {/if}
                                                                                {if $BLOCKS.WORLD_TRANSFER}
                                                                                    <tr>
                                                                                        <td height=2 align="left">
                                                                                            {$lang.WORLD_FREE_TRANSFER_HREF}
                                                                                        </td>
                                                                                    </tr>
                                                                                {/if}
                                                                                {if $BLOCKS.WORLD_COPY}
                                                                                    <tr>
                                                                                        <td height=2 align="left">
                                                                                            {$lang.WORLD_FREE_COPY_HREF}
                                                                                        </td>
                                                                                    </tr>
                                                                                {/if}
                                                                                {if $BLOCKS.DV_REQUEST}
                                                                                    <tr>
                                                                                        <td height=2 align="left">
                                                                                            {$lang.DV_REQUEST}
                                                                                        </td>
                                                                                    </tr>
                                                                                {/if}
                                                                            </table>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    
                                                            </table>                            
                                                        </td>
                                                        <!-- END LEFT BLOCK -->
                                                                    
                                                                                        
                                                        <!--RIGHT BLOCK -->
                                                        <td valign=top width=260>
                                                        <div style="width:260px;box-sizing:border-box;background-color:#1E2A4A;border:1px solid #3A4A7A;border-radius:8px;padding:16px;font-family:Arial,Helvetica,sans-serif;">

                                                        <div style="padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid #3A4A7A;text-align:left;">
                                                            <div style="color:#93A2C7;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px;margin:0 0 8px;">{$lang.ACCOUNT}</div>
                                                            <div style="color:#ffffff;font-size:20px;font-weight:bold;word-break:break-word;margin:0 0 4px;">{$LOGIN}</div>
                                                            <div style="color:#93A2C7;font-size:12px;margin:0 0 8px;word-break:break-all;">{$EMAIL}</div>
                                                            <form action="index_login.php" method="post" style="margin:0;padding:0;">
                                                                <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                <input type="hidden" name="logout" value="1">
                                                                <button type="submit" style="color:#93A2C7;font-size:12px;text-decoration:underline;background:transparent;border:0;padding:0;cursor:pointer;font-family:Arial,Helvetica,sans-serif;">{$lang.LOGOUT}</button>
                                                            </form>
                                                        </div>

                                                        <div style="padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid #3A4A7A;text-align:left;">
                                                            <div style="color:#ffffff;font-size:15px;font-weight:bold;margin:0 0 12px;text-align:left;"><img src="../img/cabinet/icons/premium50x50.png" width="20" height="20" border="0" align="absmiddle" style="margin-right:8px;" alt="">{$lang.PREMIUM_SHORT}</div>
                                                            {if isset($PREMIUM_DAYS)}
                                                            <div style="color:#4CD964;font-size:14px;font-weight:bold;margin:0 0 8px;">{$lang.PREMIUM_STATUS_ON}</div>
                                                            <div style="color:#93A2C7;font-size:12px;margin:0 0 2px;">{$lang.PREMIUM_LEFT_LABEL}</div>
                                                            <div style="color:#ffffff;font-size:20px;font-weight:bold;margin:0 0 12px;">{$PREMIUM_LEFT_TEXT}</div>
                                                            <a href="user_cabinet.php?action=payment_services" onmouseover="this.style.backgroundImage='linear-gradient(180deg,#22b024 0%,#126b13 100%)';this.style.color='#EAFFEA';" onmouseout="this.style.backgroundImage='linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%)';this.style.color='#ffffff';" style="display:block;width:100%;box-sizing:border-box;text-align:center;padding:14px 12px;min-height:44px;border-radius:6px;background-color:#0e6b10;background-image:linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%);color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;line-height:1.2;">{$lang.PREMIUM_EXTEND}</a>
                                                            {else}
                                                            <div style="color:#93A2C7;font-size:14px;font-weight:bold;margin:0 0 8px;">{$lang.PREMIUM_STATUS_OFF}</div>
                                                            <a href="user_cabinet.php?action=payment_services" onmouseover="this.style.backgroundImage='linear-gradient(180deg,#22b024 0%,#126b13 100%)';this.style.color='#EAFFEA';" onmouseout="this.style.backgroundImage='linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%)';this.style.color='#ffffff';" style="display:block;width:100%;box-sizing:border-box;text-align:center;padding:14px 12px;min-height:44px;border-radius:6px;background-color:#0e6b10;background-image:linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%);color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;line-height:1.2;">{$lang.PREMIUM_ACTIVATE}</a>
                                                            {/if}
                                                            {if isset($PREMIUM_PRESENT) && $PREMIUM_PRESENT}
                                                            <a href="{$lang.PREMIUM_PRESENT_HREF}?ck={$CLIENT_KEY}&add_premium_present=1" onclick="if(this.getAttribute('data-busy'))return false;this.setAttribute('data-busy','1');this.style.pointerEvents='none';this.style.opacity='.6';" onmouseover="this.style.backgroundColor='rgba(201,162,39,0.15)';this.style.color='#FFF6D9';" onmouseout="this.style.backgroundColor='transparent';this.style.color='#ffffff';" style="display:block;width:100%;box-sizing:border-box;text-align:center;padding:14px 12px;min-height:44px;border-radius:6px;background-color:transparent;border:1px solid #C9A227;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;margin-top:12px;line-height:1.2;">{$lang.PREMIUM_PRESENT_LABEL}</a>
                                                            {/if}
                                                        </div>

                                                        <div style="text-align:left;">
                                                            <div style="color:#ffffff;font-size:15px;font-weight:bold;margin:0 0 12px;text-align:left;"><img src="../img/cabinet/icons/gold-50x50.png" width="20" height="20" border="0" align="absmiddle" style="margin-right:8px;" alt="">{$lang.GOLD_CREDITS}</div>
                                                            <a href="/ds/billing.php?mode=gold" onmouseover="this.style.backgroundImage='linear-gradient(180deg,#22b024 0%,#126b13 100%)';this.style.color='#EAFFEA';" onmouseout="this.style.backgroundImage='linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%)';this.style.color='#ffffff';" style="display:block;width:100%;box-sizing:border-box;text-align:center;padding:14px 12px;min-height:44px;border-radius:6px;background-color:#0e6b10;background-image:linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%);color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;line-height:1.2;">{$lang.GOLD_CREDITS_BUY}</a>
                                                        </div>

                                                        </div>
                                                        </td>
                                                        <!--END RIGHT BLOCK -->
                                                        
                                                        
                                                        <!-- BEGIN DOWN FRAME -->   
                                                        <tr>
                                                            <td colspan=2 height=3 class=delimetr01></td>
                                                        </tr>               
                                                        <tr>
                                                            <td colspan=2 align=left cellpadding=0 cellspacing=0>
                                                                <!--WORLD COLLECTION -->
                                                                {if $BLOCKS.WORLD_COLLECTION == 1}
                                                                <table cellpadding=0 cellspacing=0>
                                                                    <tr>
                                                                        <td height=100% colspan=2 valign=top>
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                <tr>
                                                                                    <td colspan=3>
                                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                                <td width=100% align=left class=header_txt>
                                                                                                    <a href="" onClick="return ShowWorldList();">{$lang.EXISTING_WORLDS}</a>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            <tr>
                                                                             <td valign=top>
                                                                              <div id="worldlist" style='display:none;background-color:#ffffff'>
                                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                                            {foreach from=$ALL_WORLDS item=WORLD}                                                   
                                                                                            <tr>
                                                                                                <td width="35%">
                                                                                                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                                                                        <tr>
                                                                                                            <td width="25%" align="center">
                                                                                                                <img src="images/world_logo/{$WORLD.dbname}_{$WORLD.id_world}.gif" border="0">
                                                                                                            </td>
                                                                                                            <td width="5%"></td>
                                                                                                            <td width="70%">
                                                                                                                <span class="{if $WORLD.profi == 'f'}ind_st1{else}ind_st2{/if}" style="font-size: 12px;"><b>{$WORLD.worldname}</b></span><br>
                                                                                                                <span class="wnd_gen_txt">{$WORLD.type}</span>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </table>
                                                                                                </td>
                                                                                                <td width="55%">
                                                                                                    <span class="{if $WORLD.profi == 'f'}ind_st1{else}ind_st2{/if}">{$WORLD.description}</span><br>
                                                                                                    <span class="wnd_header_txt_small">{$lang.LEFT} {$WORLD.max_users} {$lang.PLACE_FOR_REG}</span><br>
                                                                                                    <span>{$WORLD.worldtype_descr}</span>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td colspan="2" hieght="5" class="row2">&nbsp;</td>
                                                                                            </tr>                               
                                                                                            {/foreach}
                                                                                        </table>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                                {/if}
                                                                <!--END WORLD COLLECTION -->
                                                            </td>
                                                        </tr>
                                                    </table>
                                                <!-- end content-->
                                            </td>
                                        <tr>
                                    </table>
                                </td>
                            </tr>
                                                        
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
                                                            <div valign=middle onclick="openURL('../?p=press');" class=b_menu><span style="position: relative; top: 20px;">{$lang.PRESSA}</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('../?p=support_contact');" class=b_menu><span style="position: relative; top: 20px;">{$lang.CONTACT}</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('../?p=about');" class=b_menu><span style="position: relative; top: 20px;">{$lang.ABOUT_AS}</span></div></td>
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
                    <td background="img/cabinet/bg/bg_b_b.gif"></td>
                    <td><img src="img/cabinet/bg/bg_b_r.gif" border=0></td>
                </tr>
            </table>
        </td>
    </tr>

</table>

</body>
</html>