<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

?>    
<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
    <tr>
        <td>
            <form method='post' action='<?=$_SERVER["PHP_SELF"]?>'> 
            <input type="hidden" name="action" value="search_name">
            <table width=100% border=0 cellspacing=1 cellpadding=3>
                <tr>
                    <td bgcolor="444E6C" class=th_2>
                        <? echo local( 'DS_USER_USERNAME' ); ?>
                    </td>
                </tr>
                <tr>
                    <td bgcolor='#444E6C' class=maintxt>
                        <input name='username' type='text' size='50' value="<?=@$_REQUEST["username"]?>">
                    </td>
                </tr>
                <tr>
                    <td bgcolor="#444E6C" class=maintxt align="right">
                        <input type="submit" class=Submit name="search" value="<? echo local( 'DS_ACTION_SEARCH' ); ?>">
                    </td>
                </tr>
            </table>
            </form>
        </td>
    </tr>
    
    <? 
    
    if ($_SERVER['SERVER_NAME'] == 'vk.destinysphere.ru') {
        
        echo '
    <tr>
        <td>
            <form method="post" action="'.$_SERVER["PHP_SELF"].'">  
            <input type="hidden" name="action" value="search_id">
            <table width=100% border=0 cellspacing=1 cellpadding=3>
                <tr>
                    <td bgcolor="444E6C" class=th_2>
                        ID Vkontakte
                    </td>
                </tr>
                <tr>
                    <td bgcolor="#444E6C" class=maintxt>
                        <input name="id_vkontakte" type="text" size="50" value="'.@$_REQUEST["id_vkontakte"].'">
                    </td>
                </tr>
                <tr>
                    <td bgcolor="#444E6C" class=maintxt align="right">
                        <input type="submit" class=Submit name="search" value="'.local( 'DS_ACTION_SEARCH' ).'">
                    </td>
                </tr>
            </table>
            </form>
        </td>
    </tr>   ';
    }  
    ?>
    
    
    <tr>
        <td>
            <form method='post' action='<?=$_SERVER["PHP_SELF"]?>'> 
            <input type="hidden" name="action" value="search_coords">
            <table width=100% border=0 cellspacing=1 cellpadding=3>
                <tr>
                    <td bgcolor="444E6C" class=th_2>
                        <? echo 'Coords' ?>
                    </td>
                    <td bgcolor="444E6C" class=th_2>
                        <? echo 'World' ?>
                    </td>
                </tr>
                <tr>
                    <td bgcolor='#444E6C' class=maintxt>
                        <input name='coords' type='text' size='10' value="<?=@$_REQUEST["coords"]?>">
                    </td>
                    <td bgcolor='#444E6C' class=maintxt>
                        <select name='world' style='width:250px;'>
                        
                        <?
                        $common = & ClassFactory::getInstance ( "DS_Common" );
                        $sql = sprintf ( "select * from ds_world" );
                        $res = $common->getMultySql ($sql);
                        assert(empty($common->errormsg));
                        
                        
                        foreach ($res as $k=>$v)
                        {                           
                            if (isset($_REQUEST["world"]) && $_REQUEST["world"] == $v["dbId"]."_".$v["id_world"]) {
                                $selected = ' selected ';
                            } else {
                                $selected = '';
                            }
                            
                            echo "<option 
                                        value='".$v["dbId"]."_".$v["id_world"]."' $selected>".
                                        $v['worldname'].
                                '</option>';
                        } 
                        ?>
                        
                        </select>
                    </td>
                </tr>
                <tr>
                    <td bgcolor="#444E6C" class=maintxt align="right" colspan="2">
                        <input type="submit" class=Submit name="search" value="<? echo local( 'DS_ACTION_SEARCH' ); ?>">
                    </td>
                </tr>
            </table>
            </form>
        </td>
    </tr>
</table>
</form>
<?

if ( isset ( $_REQUEST["search"] ) )
{
    if (!key_exists('action', $_REQUEST)) {
        print ShowCMSMessage('Error!', 2);
        require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
        exit;
    }

    $common = & ClassFactory::getInstance ( "DS_Common" );
    $common->setDbId(0);

    switch ($_REQUEST['action'])
    {
        case 'search_name':
            if (!empty($_REQUEST["username"]))
            {
                // Проверяем введенное имя
                if (!VD_UserName::Validate(trim($_REQUEST["username"]))) {
                    print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
                    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                    exit;
                }

                //Выбираем id_user по его нику
                $sql = "
                    SELECT
                        id_user
                    FROM
                        ds_user
                    WHERE
                         username = '".trim($_REQUEST["username"])."'";

                $user = $common->getResultArray ("", $sql, "", "", "" );
                assert(empty($common->errormsg));

                if ( sizeof($user) == 0 ) {
                    //Игрока с таким именем нет
                    print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
                    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                    exit;
                } else {
                    $userId = (int)$user[0]['id_user'];
                    assert($userId >= 0);
                }

            } else {
                print ShowCMSMessage('Empty User', 2);
                require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                exit;
            }
            break;

        case 'search_id':
            if (!empty($_REQUEST["id_vkontakte"]))
            {
                $id = (int)$_REQUEST["id_vkontakte"];
                
                // Проверяем введеную цифру
                if ($id <= 0) {
                    print ShowCMSMessage('Wrong Id Vkontakte', 2);
                    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                    exit;
                }

                //Выбираем id_user по его нику
                $sql = "
                    SELECT
                        id_user
                    FROM
                        ds_user
                    WHERE
                         id_user_vkontakte = ".$id;

                $user = $common->getResultArray ("", $sql, "", "", "" );
                assert(empty($common->errormsg));

                if ( sizeof($user) == 0 ) {
                    //Игрока с таким id нет
                    print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
                    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                    exit;
                } else {
                    $userId = (int)$user[0]['id_user'];
                    assert($userId >= 0);
                }
            } else {
                print ShowCMSMessage('Empty Id Vkontakte', 2);
                require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                exit;
            }
            break;

        case 'search_coords':
            if (!empty($_REQUEST["coords"]))
            {
                list($ring, $complex, $combnumber) = explode('.', $_REQUEST["coords"]);
                list($dbid, $world_id) = explode('_', $_REQUEST["world"]);
                
                if ((int)$ring <= 0|| (int)$complex <= 0|| (int)$combnumber <= 0|| (int)$world_id <= 0|| (int)$dbid < 0) {
                    print ShowCMSMessage('Wrong Coords', 2);
                    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                    exit;
                }   

                // Коннектимся к базе мира
                $common->setDbId((int)$dbid);
                //Выбираем id_user по его нику
                $sql = "
                    SELECT
                        u.id_user
                    FROM 
                        ds_user u
                    LEFT JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
                    LEFT JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_combcomplex cc ON (cc.id_combcomplex = c.id_combcomplex)
                    LEFT JOIN ds_ring r ON (r.id_ring = cc.id_ring)
                    LEFT JOIN ds_combname co ON (co.id_comb = c.id_comb)
                    WHERE 
                       r.ringnumber = ".(int)$ring."
                       AND
                       cc.complexnumber =  ".(int)$complex."
                       AND 
                       c.combnumber =  ".(int)$combnumber."
                       AND 
                       r.id_world =  ".(int)$world_id;

                $user = $common->getResultArray ("", $sql, "", "", "" );
                assert(empty($common->errormsg));

                if ( sizeof($user) == 0 ) {
                    //Игрока с таким id нет
                    print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
                    require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                    exit;
                } else {
                    $userId = (int)$user[0]['id_user'];
                    assert($userId >= 0);
                }

            } else {
                print ShowCMSMessage('Empty Coords', 2);
                require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
                exit;
            }
            
            // Возвращаем базу мастер
            $common->setDbId(0);
            break;

    }
    
    $select = "";
    if ($_SERVER['SERVER_NAME'] == 'vk.destinysphere.ru') {
        $select = '
                        u.id_user_vkontakte,';
    }

    //Выбираем общую инфу о игроке
    $sql = "
                    SELECT
                        u.username,
                        $select
                        u.email,
                        u.registrationdate,
                        u.accountactive,
                        upd.startdate,
                        upd.enddate,
                        u.admin,
                        date_part('day', enddate - now()) AS day,
                        date_part('hour', enddate - now()) AS hour,
                        (
                            SELECT
                              lastvisit 
                            FROM
                                ds_loginhistory as l
                            WHERE 
                                l.id_user = u.id_user
                            ORDER BY lastvisit DESC
                            LIMIT 1
                            ) as last_vizit
                    FROM
                        ds_user u
                        LEFT JOIN ds_userprofidata upd USING (id_user)
                    WHERE
                         u.id_user = $userId";
    $userInfo = $common->getResultArray ("", $sql, "", "", "" );
    assert(empty($common->errormsg));

    if ( sizeof($userInfo) == 0 ) {
        //Игрока с таким именем нет
        print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);

    } else {
        assert(sizeof($userInfo) == 1);
        
        $userWorlds = array();

        //Смотрим, в каких мирах зареган игрок
        $sql = "
                    SELECT
                        wo.worldname,
                        cn.combname,
                        ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                        fr.thefinal,
                        ui.creation_date,
                        (
                            SELECT
                              lastvisit
                            FROM
                                ds_loginhistory as l
                            WHERE
                                l.id_user = u.id_user
                            ORDER BY lastvisit DESC
                            LIMIT 1
                         ) as last_vizit,
                         co.is_capture
                    FROM
                        ds_user u
                        left outer join ds_userinstance ui ON (u.id_user = ui.id_user)
                        left outer join ds_world wo ON (wo.id_world = ui.id_world)
                        left outer join ds_comb co ON (co.id_userinstance = ui.id_userinstance)
                        LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                        LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                        left outer join ds_combname cn ON (co.id_comb = cn.id_comb)
                        LEFT JOIN ds_finalranking fr ON (fr.id_userinstance = ui.id_userinstance)
                    WHERE
                         u.id_user = $userId
                        AND
                         not wo.worldname is null
                    ORDER BY wo.worldname, ri.ringnumber, co.id_comb
                    "
        ;


        $userCombs = $common->getMultySql ( $sql );

        if ( $common->errormsg ) {
            die ( "sql error: ".$common->errormsg);
        }

        $currentWorld = 'NULL';

        foreach ($userCombs as $key => $data)
        {
            if ($currentWorld != $data['worldname']) {
                $key = 0;
                $currentWorld = $data['worldname'];
            }
            
            $fontcolor = "";
            $fontEnd = "";
            
            // Если сота захватываемая
            if ($data['is_capture'] == 't') {
                $fontcolor = "<font color=red>";
                $fontEnd = "</font>";
            }
            $userWorlds[$data['worldname']]['comb'][$key] = $fontcolor.$data['combname']." (".$data['coords'].")".$fontEnd;
            $userWorlds[$data['worldname']]['thefinal'] = $data['thefinal'];
            $userWorlds[$data['worldname']]['creation_date'] = $data['creation_date'];
            $userWorlds[$data['worldname']]['last_vizit'] = $data['last_vizit'];
            $key ++;
        }

        $sql = "SELECT 1 FROM ds_user_premium_ban
            WHERE id_user = $userId
            AND (end_date > now() OR end_date IS NULL)";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        $premiumBan = count($res) ? 'Да':'Нет';

        $sql = "
            SELECT 1 FROM ds_userban
            WHERE id_user = $userId and enddate > now()";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        $commonBan = count($res) ? 'Да':'Нет';
    
        // Сумма платежей
        $res = file_get_contents("https://billing.destinysphere.ru/gate/payers.php?&v=".VERSION_NAME."&ids=".$userId);
        $payments = json_decode($res);
        if (isset($payments->$userId)) $totalPaid = $payments->$userId;
        else $totalPaid = 0;

        //Выводим инфу о игроке
            ?>
            <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <tr>
                            <th class=th_2 colspan="2">
                                Данные игрока
                            </th>
                        </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? echo local( 'DS_USER_USERNAME' ); ?>:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? echo $userInfo[0]["username"]; ?>
                                </td>
                            </tr>
                            <? 
                            if ($_SERVER['SERVER_NAME'] == 'vk.destinysphere.ru') {
                                echo '
                            <tr>
                                <td bgcolor="#444E6C" class=maintxt>
                                    Id Vkontakte:
                                </td>
                                <td bgcolor="#444E6C" class=maintxt>
                                    '.$userInfo[0]["id_user_vkontakte"].'
                                </td>
                            </tr>';                         
                            } ?>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Активный аккаунт:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? 
                                    if ($userInfo[0]['accountactive'] == 't')
                                    {
                                        echo "Да";
                                    } else {
                                        echo "Нет";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Админ:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? 
                                    if ($userInfo[0]['admin'] == 't')
                                    {
                                        echo "Да";
                                    } else {
                                        echo "Нет";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? echo local( 'DS_USER_EMAIL' ); ?>:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? echo $userInfo[0]['email'] ?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Дата регистрации:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? 
                                    if (!empty($userInfo[0]['registrationdate'])) {
                                        echo GB_TypeFactory::CreateBase('GB_Date', $userInfo[0]['registrationdate'])->getPgFormat();
                                    } else {
                                        echo "";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Дата последнего захода:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <? 
                                    if (!empty($userInfo[0]['last_vizit'])) {
                                        echo GB_TypeFactory::CreateBase('GB_Date', $userInfo[0]['last_vizit'])->getPgFormat();
                                    } else {
                                        echo "";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Премиум:
                                </td>
                                
                                    <?
                                    //Смотрим есть ли прем у игрока
                                    if ((empty($userInfo[0]['day']) && empty($userInfo[0]['hour'])) || (strtotime($userInfo[0]['enddate']) - strtotime("now") <= 0))
                                    {
                                        echo "
                                        <td bgcolor='#444E6C' class=maintxt>
                                            Нет
                                            ";
                                    } else {
                                        echo "
                                        <td bgcolor='#444E6C' class=maintxt>
                                            с ".GB_TypeFactory::CreateBase('GB_Date', $userInfo[0]['startdate'])->getPgFormat()." 
                                            по ".GB_TypeFactory::CreateBase('GB_Date', $userInfo[0]['enddate'])->getPgFormat()."
                                        </td>
                                    </tr>
                                    <tr>
                                        <td bgcolor='#444E6C' class=maintxt>
                                            Осталось премиума:
                                        </td>
                                        <td bgcolor='#444E6C' class=maintxt>
                                    ";
                                        if (!(empty($userInfo[0]['day']))) {
                                            echo $userInfo[0]['day']." дней ";
                                        }
                                        if (!(empty($userInfo[0]['hour']))) {
                                            echo $userInfo[0]['hour']." часов";
                                        }

                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Премиум бан:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <?=$premiumBan?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Бан:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <?=$commonBan?>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor='#444E6C' class=maintxt>
                                    Заплатил рублей:
                                </td>
                                <td bgcolor='#444E6C' class=maintxt>
                                    <?=$totalPaid?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table border="0">
                <tr>
                    <td>
                    &nbsp;
                    </td>
                </tr>
            </table>
            <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                            <tr>
                                <th class=th_2>
                                    Мир
                                </th>
                                <th class=th_2>
                                    Соты
                                </th>
                                <th class=th_2>
                                    Рейтинг
                                </th>
                                <th class=th_2>
                                    Дата регистрации в мире
                                </th>
                                <th class=th_2>
                                    Дата последнего захода в мир
                                </th>
                            </tr>
                            <?
                            //Проходимся по каждому миру
                            foreach ($userWorlds as $worldName => $data)
                            {
                                echo '
                                    <tr>
                                        <td align="center" class=maintxt_med bgcolor=#444E6C>
                                            '.$worldName.'
                                        </td>
                                    ';
                                echo '
                                        <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    ';
                                //Проходимся по каждой соте
                                foreach ($data['comb'] as $key => $comb)
                                {
                                    echo $comb."<br>";
                                }

                                echo '
                                        <td align="center" class=maintxt_med bgcolor=#444E6C>
                                            '.$data['thefinal'].'
                                        </td>
                                        <td align="center" class=maintxt_med bgcolor=#444E6C>
                                            '.$data['creation_date'].'
                                        </td>
                                        <td align="center" class=maintxt_med bgcolor=#444E6C>
                                            '.$data['last_vizit'].'
                                        </td>
                                        ';

                                echo '
                                    </tr>   
                                    ';
                            }
                            ?>
                        </table>
                    </td>
                </tr>
            </table>    
            <?
    }
}






require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>