<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/classes/VD/VD_Base.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/classes/VD/VD_UserName.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/classes/VD/VD_Email.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/classes/VD/VD_CombName.class.php";

?>

<form method='post' action='<?=$_SERVER["PHP_SELF"]?>'>       
<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
<tr><td bgcolor="444E6C" class=th_2><? echo local( 'DS_USER_EMAIL' ); ?></td></tr>
<tr><td bgcolor='#444E6C' class=maintxt><input name='email' type="text" size="50" value="<?=@$_REQUEST["email"]?>"></td></tr>
<tr><td bgcolor="444E6C" class=th_2><? echo local( 'DS_USER_USERNAME' ); ?></td></tr>
<tr><td bgcolor='#444E6C' class=maintxt><input name='username' type='text' size='50' value="<?=@$_REQUEST["username"]?>"></td></tr>


<?

?>
<tr><td bgcolor="#444E6C" class=maintxt><input type="submit" class=Submit name="search" value="<? echo local( 'DS_ACTION_SEARCH' ); ?>">
<input type="checkbox" name="compliancesearch" value="FULL" class="check" <?if (key_exists('compliancesearch', $_REQUEST) && $_REQUEST['compliancesearch'] == 'FULL') {echo 'checked="checked"';}?>><? echo local( 'DS_COMPLIANCE_SEARCH' ); ?>
</td></tr>
</TABLE></td></tr></table>
</form>
<?


// Проверяем входные данные
if (key_exists("email", $_REQUEST) && !empty($_REQUEST['email'])) {
    if (!VD_Email::Validate($_REQUEST["email"])) {
        ShowCMSMessage("Wrong E-mail!<br>", 2);
        require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
        exit;
    }
}

if (key_exists("username", $_REQUEST)  && !empty($_REQUEST['username'])) {
    if (!VD_UserName::Validate($_REQUEST["username"])) {
        ShowCMSMessage("Unknown UserName!<br>", 2);
        require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
        exit;
    }
}

$common = ClassFactory::getInstance ( "DS_Common" );
$common->setDbId(0);

if ( isset ( $_REQUEST["search"] ) )
{
    if (key_exists('compliancesearch', $_REQUEST) && $_REQUEST['compliancesearch'] == "FULL")
    {
        if (!empty($_REQUEST["username"])) {$likeusername = " AND u.username = '".$_REQUEST["username"]."'";}
        if (!$likeusername) {$separator = 'AND';} else {$separator = 'AND';}
        $likeemail = ''.(!empty($_REQUEST["email"]) ? " ".$separator." u.email = '".$_REQUEST["email"]."'" : " ").'';
    }else {
        $likeusername = ''.(!empty($_REQUEST["username"]) ? " AND u.username ilike '".trim('%%'.$_REQUEST["username"].'%%')."'" : " ").'';
        $likeemail = ''.(!empty($_REQUEST["email"]) ? " AND u.email ilike '".trim('%%'.$_REQUEST["email"].'%%')."'" : " ").'';
    }

    $sql = sprintf ( "
                    SELECT
                        username,
                        accountactive,
                        email,
                        ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                        ui.id_userinstance,
                        u.id_user,
                        wo.worldname,
                        cn.combname,
                        u.id_language,
                        co.id_comb,
                        ((
                        SELECT
                            id_userinstance
                        FROM
                            ds_gamehelper gh
                        WHERE
                            gh.id_userinstance = ui.id_userinstance
                        ) IS NOT NULL ) as gamehelper,
                        ((
                        SELECT
                            id_userinstance
                        FROM
                            ds_gamemaster gm
                        WHERE
                            gm.id_userinstance = ui.id_userinstance
                        ) IS NOT NULL ) as gamemaster,
                        ((
                        SELECT
                            id_userinstance
                        FROM
                            ds_userallowedmessagescope uams
                            LEFT JOIN ds_messagescope ms ON ( uams.id_messagescope = ms.id_messagescope )
                        WHERE
                            uams.id_userinstance = ui.id_userinstance
                            AND ms.messagescopename = 'WORLD'
                        ) IS NOT NULL ) as sendglobals
              
                    FROM
                        ds_user u
                        left outer join ds_userinstance ui ON (u.id_user = ui.id_user)
                        left outer join ds_world wo ON (wo.id_world = ui.id_world)
                        left outer join ds_comb co ON (co.id_userinstance = ui.id_userinstance)
                        LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                        LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                        left outer join ds_combname cn ON (co.id_comb = cn.id_comb)
                    WHERE
                        u.id_user >= 0
                        %s
                        %s
                    ORDER BY
                        u.username",
    $likeusername,
    $likeemail
    );


    $res = $common->getMultySql ( $sql );
    if ( $common->errormsg ) {
        die ( "sql error" . $res->getMessage());
    }

    if ( sizeof($res) == 0 ) {

        print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);

    } else {

        echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor=222233><tr><td><table width="100%" border="0" cellspacing="1" cellpadding="3">
                            <tr>                              
                              <th class=th_2>'.local( 'DS_USER_UID' ).'</th>                              
                              <th class=th_2>'.local( 'DS_USER_USERNAME' ).'</th>
                             <th class=th_2>'.local( 'DS_USER_EMAIL' ).'</th>
                              <th class=th_2>'.local( 'DS_USER_UIID' ).'</th>
                              <th class=th_2>'.local( 'DS_USER_WORLD' ).'</th>
                              <th class=th_2>'.local( 'DS_USER_CID' ).'</th>
                             <th class=th_2>'.local( 'DS_USER_COMBNAME' ).'</th>
                             <th class=th_2>'.local( 'DS_USER_COORD' ).'</th>
                              <th class=th_2>'.local( 'DS_USER_GAMEHELPER' ).'</th>
                              <th class=th_2>'.local( 'DS_USER_GAMEMASTER' ).'</th>
                              <th class=th_2>'.local( 'DS_USER_SENDGLOBALS' ).'</th>
                              <th class=th_2>'.local( 'DS_USER_DATA_ACTION' ).'</th>

                            </tr>';

        foreach ( $res as $k=>$v ) {

            if(!$v['worldname']){
                $v['worldname'] = '&nbsp;';
            }
            //              if ($v['id_comb'] == '' && count($res) > 1) {continue;}

            @$output .= '
                            <form action="'.$_SERVER['PHP_SELF'].'" method="post">  
                            <input name="worlName" type="hidden" value="'.$v['worldname'].'">   
                            <input name="lang" type="hidden" value="'.$v['id_language'].'">
                            <input name="old_username" type="hidden" value="'.$v['username'].'">
                            <input name="old_email" type="hidden" value="'.$v['email'].'">
                            <input name="old_combname" type="hidden" value="'.$v['combname'].'">
                            <input name="old_gamehelper"  type="hidden" value="'.( $v['gamehelper'] == 't' ? '1' : '0' ).'">
                            <input name="old_gamemaster"  type="hidden" value="'.( $v['gamemaster'] == 't' ? '1' : '0' ).'">
                            <input name="old_sendglobals" type="hidden" value="'.( $v['sendglobals'] == 't' ? '1' : '0' ).'">
                            <input name="dbId" type="hidden" value="'.$v['dbId'].'">
                            <tr>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>'.$v['id_user'].'
                                    <input name="uid" type="hidden" size="5" value="'.$v['id_user'].'" readonly >
                                </td>           
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    <input name="username" type="text" size="10" value="'.$v['username'].'">
                                </td>           
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    <input name="email" type="text" size="15" value="'.$v['email'].'">
                                </td>           
                                <td align="center" class=maintxt_med bgcolor=#444E6C>'.$v['id_userinstance'].'
                                    <input name="uiid" type="hidden" size="5" value="'. $v['id_userinstance'].'" readonly>
                                </td>                          
                                <td align="center" class=maintxt_med bgcolor=#444E6C>'.$v['worldname'].'</td>                          
                                <td align="center" class=maintxt_med bgcolor=#444E6C>'.$v['id_comb'].'
                                    <input name="cid" type="hidden" size="5" value="'.$v['id_comb'].'" readonly>
                                </td>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    <input name="combname" type="text" size="15" value="'.$v['combname'].'">
                                </td>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    '.$v['coords'].'
                                </td>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    <input name="gamehelper" class="check" type="checkbox" value="1" '.( $v['gamehelper'] == 't' ? 'checked' : '' ).'>
                                </td>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    <input name="gamemaster" class="check" type="checkbox" value="1" '.( $v['gamemaster'] == 't' ? 'checked' : '' ).' >
                                </td>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    <input name="sendglobals" class="check" type="checkbox" value="1" '.( $v['sendglobals'] == 't' ? 'checked' : '' ).' >
                                </td>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    <input type="submit" class=Submit name="edit" value="'.local( 'ADMIN_SAVE' ).'">
                                </td>
                            </tr>
                            </form>';

        }

        $output .= sprintf ( '
                    <form action="'.$_SERVER['PHP_SELF'].'" method="post">
                    <input name="uid" type="hidden" size="5" value="'.$v['id_user'].'" readonly >
                    <input name="old_username" type="hidden" value="'.$v['username'].'">
                    <input name="username" type="hidden" value="'.$v['username'].'">

                    <tr>
                    <td align="left" class=maintxt bgcolor=#444E6C colspan="12">' . local( 'ADMIN_CHANGE_PASSWORD' ) . '
                        <input name="password" type="text" size="25" value="">
                        &nbsp;<input type="submit" class=Submit name="edit" value="'.local( 'ADMIN_APPLY' ).'">
                    </td>
                    </tr>
                    ');

        $output .= sprintf ( '</form>');
        $output .= sprintf ( '</table></td></tr></table>');

        $res = $common->getResultArray("", "select end_date from ds_analizer_premium where id_user={$v['id_user']}");
        if (count($res)) $analEnd = $res[0]['end_date'];
        else $analEnd = "";
        $output .= '
            <br><form method="post">
            <input type="hidden" name="id_user" value="'.$v['id_user'].'">
            <span class="maintxt">Анализатор до</span> <input name="endDate" value="'.$analEnd.'">            <input type="submit" name="analizer" value="Изменить">
            </form>
        ';
        echo $output;
    }
} elseif ( isset ( $_POST["analizer"] ) ) {
    $id_user = (int) $_POST['id_user'];
    $res = $common->getResultArray("", "select 1 from ds_analizer_premium where id_user=$id_user");
    if (count($res)) {
        $sql = "update ds_analizer_premium set end_date='{$_POST['endDate']}' where id_user=$id_user";
    }
    else {
        $sql = "insert into ds_analizer_premium (id_user, active, start_date, end_date, creation_date) values ($id_user, true, now(), '{$_POST['endDate']}', now())";
    }
    $common->executeQuery($sql);
    echo '<div class="maintxt">Дата окончания анализатора изменена</div>';

} elseif ( isset ( $_POST["edit"] ) ) {
    $common = & ClassFactory::getInstance ( "DS_Common" );
    $common->setDbId(0);

    $forumCommon = & ClassFactory::getInstance ( "DS_Common" );
    $forumCommon->connect('forum');

    if (!($_POST["old_username"] === $_POST["username"]))
    {
        if(preg_match("/^[a-zA-Z0-9_]+$/",$_POST["username"]) != 1)
        {
            print ShowCMSMessage("new username with bad symbols!!!", 2);
            die();
        }

        $sql = sprintf("select
                            id_user
                        from 
                            ds_user
                        where 
                            upper(username) = upper('%s')",
        $_POST["username"]);

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        if($common->errormsg)
        {
            print ShowCMSMessage("can't check username" . $sql, 2);
            die();
        }

        if(sizeof($res) != 0)
        {
            print ShowCMSMessage("username exists", 2);
            die();
        }

        $sql = sprintf ("   UPDATE
                                ds_user
                            SET
                                username = '%s'
                            WHERE
                                id_user = %d",
        $_POST["username"],
        $_POST["uid"] );

        $res = $common->executeQuery ( $sql );
        if ( $common->errormsg )
        {
            die ( "sql error1" . $sql);
        }

        $sql = sprintf ( "
                        UPDATE %s_users
                        SET username = '%s'
                        WHERE username = '%s'",
        FORUM_PREFIX,
        $_POST["username"],
        $_POST["old_username"] );

        $res = $forumCommon->executeQuery ( $sql );
        if ( $forumCommon->errormsg )
        {
            echo "sql error1.1" . $sql;
        }
        else {
            actionLog( "Editing UserName with oldName:'".@$_POST['old_username']."' or newName:'".@$_POST['username']."' in world '".@$_POST['worlName']."'");
        }

    }

    if (!($_POST['old_email'] === $_POST['email']))
    {

        if(preg_match("/[\'\"\:]+/",$_POST["email"]) != 0)
        {
            print ShowCMSMessage("new email with bad symbols!!!", 2);
            die();
        }

        $sql = "
                        SELECT 
                            u.id_user
                        FROM
                            ds_user u
                        WHERE
                            (u.email = '".$_POST['email']."')
        ";


        $res = $common->getQueryArray( $sql );
        if ( $common->errormsg ) {
            die ( "sql error 1.1.1".$common->errormsg );
        }

        if( !$res ){

            $sql = sprintf ( "
                            UPDATE ds_user
                            SET email = '%s'
                            WHERE id_user = %d",
            $_POST["email"],
            $_POST["uid"] );

            $res = $common->executeQuery ( $sql );
            if ( $common->errormsg ) {
                die ( "sql error2" . $res->getMessage());
            }

            $sql = sprintf ( "
                            UPDATE %s_users
                            SET user_email = '%s'
                            WHERE username = '%s'",
            FORUM_PREFIX,
            $_POST["email"],
            $_POST["old_username"] );

            $res = $forumCommon->executeQuery ( $sql );
            if ( $forumCommon->errormsg ) {
                die ( "sql error2.1 " . $sql . mysql_error() . $forumCommon->errormsg);
            }   else {
                actionLog("Editing User Email with oldEmail:'".@$_POST['old_email']."' or newEmail:'".@$_POST['email']."' in world '".@$_POST['worlName']."'");
            }

        }
        else die ("E-mail '<b>".$_POST[email]."</b>' exist!");



    }

    /*Added by Zya*/
    if (!empty( $_POST["password"] ) && !empty( $_POST["uid"]))
    {
        if (!VD_Password::Validate($_POST["password"])) {
            print ShowCMSMessage("new password with bad symbols!!!", 2);
            die();
        }

        $sql = sprintf ( "
                        UPDATE ds_user
                        SET password = '%s'
                        WHERE id_user = %d",
        md5 ( $_POST["password"] ),
        $_POST["uid"] );

        $res = $common->executeQuery ( $sql );
        if ( $common->errormsg ) {
            die ( "sql error1" . $sql);
        }

        $sql = sprintf ( "
                        UPDATE %s_users
                        SET user_password = '%s'
                        WHERE username = '%d'",
        FORUM_PREFIX,
        md5 ( $_POST["password"] ),
        $_POST["username"] );

        $res = $forumCommon->executeQuery ( $sql );

        if ( $forumCommon->errormsg ) {
            echo "<font color=red>Cant update forum password!</font>";
        }

        actionLog( "Change UserPassword with User:'".$_POST['username']."'");
    }

    $common->setDbId($_POST['dbId']);
    if (!($_POST["old_combname"] === $_POST["combname"]))
    {
        if(preg_match("/[\'\"\:\&]+/",$_POST["combname"]) != 0)
        {
            print ShowCMSMessage("new combname with bad symbols!!!", 2);
            die();
        }
        
        if (!VD_CombName::Validate($_POST["combname"])) {
            print ShowCMSMessage("new combname with bad symbols!!!", 2);
            die();
        }

        $sql = sprintf ( "update ds_combname
                          set combname = '%s'
                          where id_comb = %d",
        $_POST["combname"],
        $_POST["cid"] );

        $res = $common->executeQuery($sql);
        if ( $common->errormsg )
        {
            die ( "sql error4" . $res->getMessage());
        }

        $sql = sprintf ( "UPDATE
                                ds_unitcontainer
                            SET
                                unitcontainername = '%s' || ' ' ||
                                (
                                    select
                                            ltl.text
                                    from
                                            ds_localizedtextlanguage ltl,
                                            ds_interface i,
                                            ds_user u
                                    where
                                        ltl.id_localizedtext = i.id_localizedname
                                        and i.interfacename = 'HARNIZON'
                                        and ltl.id_language = u.id_language
                                        and u.id_user = %d
                                )
                            WHERE
                                isdefault = 't'
                                AND id_basecomb = %d
                                AND id_userinstance = %d",
        $_POST["combname"],
        $_POST["uid"],
        $_POST["cid"],
        $_POST["uiid"]
        );

        $res = $common->executeQuery ( $sql );
        if ( $common->errormsg )
        {
            die ( "sql error4.2" . $res->getMessage());
        }
        else
        {
            actionLog( "Editing User CombName with oldCombName:'".$_POST['old_combname']."' or newCombName:'".$_POST['combname']."' in world '".@$_POST['worlName']."'");
        }

    }


    if(empty($_POST['gamehelper']))
    {
        $_POST['gamehelper'] = 0;
    }

    if($_POST['old_gamehelper'] != $_POST['gamehelper'])
    {

        $sql = sprintf(
        "
            SELECT
                *
            FROM
                ds_gamehelper gh
            WHERE
                gh.id_userinstance = '%d'
            ",
        $_POST["uiid"]
        );


        $res = $common->getQueryArray( $sql );
        if ( $common->errormsg ) {
            die ( "sql error4".$common->errormsg );
        }

        if( empty( $res ) )
        {

            $sql = sprintf (
            "
                INSERT INTO ds_gamehelper ( id_userinstance )
                VALUES ( '%d' );
                ",
            $_POST["uiid"]
            );
            $status = "Enable";
        }
        else
        {

            $sql = sprintf (
            "
                DELETE
                FROM
                    ds_gamehelper
                WHERE
                    id_userinstance = '%d';
                ",
            $_POST["uiid"]
            );
            $status = "Disable";
        }

        $res = $common->executeQuery ( $sql );
        if ( $common->errormsg )
        {
            die ( "sql error4".$common->errormsg );
        }
        else
        {
            actionLog( "Editing User Status GameHelper userName:'".$_POST['username']."' Status:'$status' World:'".$_POST['worlName']."'" );
        }

    }



    if (empty($_POST['gamemaster']))
    {
        $_POST['gamemaster'] = 0;
    }

    if( $_POST['old_gamemaster'] != $_POST['gamemaster'] )
    {

        $sql = sprintf (
        "
            SELECT
                *
            FROM
                ds_gamemaster gm
            WHERE
                gm.id_userinstance = '%d'
            ",
        $_POST["uiid"]
        );

        $res = $common->getQueryArray( $sql );
        if ( $common->errormsg ) {
            die ( "sql error4".$common->errormsg );
        }

        if( empty( $res ) ){

            $sql = sprintf (
            "
                INSERT INTO ds_gamemaster ( id_userinstance )
                VALUES ( '%d' );
                ",
            $_POST["uiid"]
            );
            $status = "Enable";
        }
        else
        {
            $sql = sprintf (
            "
                DELETE
                FROM
                    ds_gamemaster
                WHERE
                    id_userinstance = '%d';
                ",
            $_POST["uiid"]
            );
            $status = "Enable";
        }

        $res = $common->executeQuery ( $sql );
        if ( $common->errormsg )
        {
            die ( "sql error4".$common->errormsg );
        }
        else {
            actionLog( "Editing User Status GameMaster userName:'".$_POST['username']."' Status:'$status' World:'".$_POST['worlName']."'" );
        }

    }


    if (empty($_POST['sendglobals']))
    {
        $_POST['sendglobals'] = 0;
    }
    if($_POST['old_sendglobals'] != $_POST['sendglobals'])
    {
        actionLog( "Editing User Status SendGlobalsMessages userName:'".$_POST['username']."' Status:'$status'  World:'".$_POST['worlName']."'" );

        $sql = sprintf (
        "
            SELECT
                *
            FROM
                ds_userallowedmessagescope sg
            WHERE
                sg.id_userinstance = '%d'
                AND id_messagescope = '9'
            ",
        $_POST["uiid"]
        );


        $res = $common->getQueryArray( $sql );
        if ( $common->errormsg ) {
            die ( "sql error4".$common->errormsg );
        }

        if(empty( $res ))
        {
            $sql = sprintf (
            "
                INSERT INTO ds_userallowedmessagescope ( id_userinstance , id_messagescope)
                VALUES ( '%d' , '9');
                ",
            $_POST["uiid"]
            );
            $status = "Enable";
        }
        else
        {

            $sql = sprintf (
            "
                DELETE
                FROM
                    ds_userallowedmessagescope
                WHERE
                    id_userinstance = '%d'
                    and id_messagescope = 9;
                ",
            $_POST["uiid"]
            );
            $status = "Enable";
        }

        $res = $common->executeQuery ( $sql );
        if ( $common->errormsg ) {
            die ( "sql error4".$common->errormsg );
        }   else {
            actionLog( "Editing User Status SengGlobalMessages userName:'".$_POST['username']." Status:'$status'  World:'".$_POST['worlName']."'");
        }

    }


    echo ShowCMSMessage(local( 'DS_ACTION_SUCCEED' ), 1);
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>