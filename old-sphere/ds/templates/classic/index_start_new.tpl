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
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
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
            <table width=875 border=0 cellpadding=0 cellspacing=0>
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
                                                        <td valign=top width=80%>
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
                                                                                <td width=100% align=left class=header_txt>
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
                                                                                <td width=100% align="justify" valign=top bgcolor="#ffffff" style="padding: 5px;">
                                                                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=3>
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
                                                                                <td width=100% align=left class=header_txt>
                                                                                    <a href="" onClick="return ShowWorldReg()">{$lang.REGISTRATION}</a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                     <td valign=top style="padding:5px;" colspan=3>
                                                                      <div id="world_reg" style='display:{if !$WORLDS_TO_ENTER}block{else}none{/if}'>
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=3  bgcolor='#ffffff'>
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
                                                        <td valign=top> 
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td colspan=3>
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                            <tr>
                                                                                <td>
                                                                                    <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                <td width=100% align=left class=header_txt>
                                                                                    {$lang.ACCOUNT}
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
                                                                    <td width=100% valign=top bgcolor="#ffffff">
                                                                        <form action="index_login.php" method="post">
                                                                        <input type="hidden" name="ck" value="{$CLIENT_KEY}">
                                                                        <table width=100% border=0 cellpadding=0 cellspacing=2>
                                                                            <tr>
                                                                                <td align="right" width="45%">
                                                                                    <b>{$lang.NAME}:</b>
                                                                                </td>
                                                                                <td width="55%">
                                                                                    <span style="font-size:11px;">{$LOGIN}</span>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="right">
                                                                                    <b>{$lang.EMAIL}:</b>
                                                                                </td>
                                                                                <td>
                                                                                    <span style="font-size:11px;">{$EMAIL}</span>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height=100% align=center valign=top colspan=2>
                                                                                    <input type="submit" class="submit_no" value="{$lang.LOGOUT}">
                                                                                    <input type="hidden" name="logout" value="1">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td colspan=2 align='center'>
                                                                                    <a href="{$REFLINK}" target="_blank">{$lang.REFLINK_TEXT}</a><br>
                                                                                    (<a href="{$lang.ABOUT_REF_LINK}" target="_blank">{$lang.REFLINK_INFO}</a>)
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                        </form>
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan=3>
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                            <tr>
                                                                                <td>
                                                                                    <img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                                <td width=100% align=left class=header_txt>
                                                                                    {$lang.PREMIUM}
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
                                                                    <td width=100% valign=top bgcolor="#ffffff">
                                                                        <!--SERVISE BLOCK-->
                                                                        <table>                       
                                                                            <form action="user_cabinet.php" method="post" target="_self">
                                                                            <input type="hidden" name="action" value="login">
                                                                            <tr>
                                                                                <td align="center"  bgcolor="#ffffff">
                                                                                    <input type="submit" value="{$lang.CABINET_SUBMIT}" class="submit02">
                                                                                </td>
                                                                            </tr>
                                                                            </form>
            
                                                                            <tr>
                                                                                <td height=3 colspan=2 class=delimetr01>
                                                                                </td>
                                                                            </tr>
                                                                
                                                                            {if isset($PREMIUM_DAYS)}
                                                                            <tr>
                                                                                <td colspan=2 align=center><span style="font-size:12px;">{$lang.VALID_TILL}<span style="color: {if $PREMIUM_DAYS.day < 2}Red{else}Black{/if};"><b>{$PREMIUM_DAYS.day}</b></span>{$lang.DAYS} <b>{$PREMIUM_DAYS.hour}</b> {$lang.HOURS}</span></td>
                                                                            </tr>
                                                                            {else}
                                                                            <tr>
                                                                                <td colspan=2 align=center>
                                                                                <!--
                                                                                <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="468" height="60" id="468x60" align="middle">
                                                                                    <param name="allowScriptAccess" value="sameDomain" />
                                                                                    <param name="movie" value="images/468x60.swf" />
                                                                                    <param name="quality" value="high" />
                                                                                    <param name="bgcolor" value="#ffffff" />
                                                                                    <embed src="images/468x60.swf" quality="high" bgcolor="#ffffff" width="468" height="60" name="468x60" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
                                                                                </object>
                                                                                -->
                                                                                </td>
                                                                            </tr>
                                                                            {/if}
                                                                
                                                                            {if isset($PREMIUM_PRESENT) && $PREMIUM_PRESENT}
                                                                            <tr>
                                                                                <td height=3 colspan=2 class=delimetr01>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td colspan=2 align="center">
                                                                                    <a href="{$lang.PREMIUM_PRESENT_HREF}?ck={$CLIENT_KEY}&add_premium_present=1"><b>{$lang.PREMIUM_PRESENT_TEXT}</b></a><br><br>
                                                                                </td>
                                                                            </tr>
                                                                            {/if}       
                                                                            <tr>
                                                                                <td width="30%" align="middle" valign="middle">
                                                                                    <form action="sms.php" target="_blank" method="POST">
                                                                                    <input type="submit" value="{$lang.SMS}" class="submit_yes">
                                                                                    </form> 
                                                                                </td>
                                                                            </tr>
                                                                        </table> 
                                                                        <!-- END SERVISE BLOCK -->
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>

                                                               <tr>
                                                                 <td></td>
                                                                 <td align="center">
                                                                   <br><img src="img/goldbar.gif"><br>
                                                                   Быстрая покупка золотых кредитов.<br>
                                                                   Пополни прямо сейчас!<br>
                                                                   <form method="post" action="/ds/billing.php?mode=gold">
                                                                   Количество: <input name="quantity" value="15" style="width:80px"><br><br>
                                                                   <input type="submit" value="Получить" class="submit01">
                                                                   </form>
                                                                 </td>
                                                                 <td></td>
                                                               </tr>
                                                                {if !isset($PREMIUM_DAYS)}
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/t.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="img/cabinet/border/03/l.gif"></td>
                                                                    <td width="100%" valign="top" align="center" bgcolor="#ffffff" style="padding: 4px">
<div id="yandex_ad"></div>
<script type="text/javascript">
(function(w, d, n, s, t) {
w[n] = w[n] || [];
w[n].push(function() {
Ya.Direct.insertInto(124333, "yandex_ad", {
ad_format: "direct",
font_size: 1,
type: "vertical",
limit: 2,
title_font_size: 3,
site_bg_color: "FFFFFF",
header_bg_color: "FEEAC7",
title_color: "0000CC",
url_color: "006600",
text_color: "000000",
hover_color: "0066FF",
favicon: true,
no_sitelinks: false
});
});
t = d.documentElement.firstChild;
s = d.createElement("script");
s.type = "text/javascript";
s.src = "http://an.yandex.ru/system/context.js";
s.setAttribute("async", "true");
t.insertBefore(s, t.firstChild);
})(window, document, "yandex_context_callbacks");
</script>                                    
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                {/if}
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>                                                       
                                                            </table>
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