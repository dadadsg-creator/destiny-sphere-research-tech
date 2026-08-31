<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

class UserPremiumBanController
{
    static private $startDate;
    static private $endDate;
    static private $userName;
    static private $user;
    static private $common;
    static private $allowTrade = 'f';
    
    
    static public function Exec(array $data)
    {
        self::_outFirstScreen();
        
        if (!empty($_REQUEST['username'])) 
        {
            //Начинаем транзакцию
            DS_Common::SetConnectWithTransaction(true);
        
            $_SESSION['dbId'] = 0;
            self::$common = DS_Common::CreateForCurentDB(0);
            self::$userName = $data['username'];
            
            // Проверяем ввденые данные о игроке
            if (!VD_UserName::Validate(self::$userName)) {
                ShowCMSMessage("Wrong UserName!", 2);
                return;
            }
            
            $userId = (int)VD_UserName::CheckIfExists(self::$userName, self::$common);
            
            if($userId > 0)
            {
                self::$user = DE_User::CreateFromID($userId);
            } else {
                ShowCMSMessage("Wrong UserName!<br>", 2);
                return;
            }
            
            if(key_exists('startdate', $data))
            {
                if ($data['startdate']['day'] > 31 || empty($data['startdate']['year']) || empty($data['startdate']['day']))
                {
                    echo "<font color=red>Wrong data!</font><br>";
                    self::_outUserMenu();
                    return;
                }
                
                self::$startDate = implode('-', $data['startdate']).date(' H:i');
            }

            if(key_exists('enddate', $data))
            {
                if (empty($data['enddate']['year'])) {
                    self::$endDate = 2+date('Y')."-".date('m')."-".date("d").date(' H:i');
                } else {
                    self::$endDate = implode('-', $data['enddate']).date(' H:i');
                }

                if(!empty($data['enddate']['year']) && ($data['enddate']['day'] > 31 || $data['enddate']['month'] == '-' || empty($data['enddate']['day']) || empty($data['enddate']['year'])))
                {
                    echo "<font color=red>Wrong data!</font><br>";
                    self::_outUserMenu();
                    return;
                }
            }
            
            if(key_exists('allow_trade', $data))
            {
                self::$allowTrade = 't';
            }
            
            
            if(key_exists('ban', $data))
            {
                //Баним
                self::_banUser();
            } else {
                //выводим меню
                self::_outUserMenu();
            }
            
            //Завершаем транзакцию
            ME_EndDBTransactions::Exec(true);
        }
    }
    
    private static function _outFirstScreen()
    {
        echo '<h1>Search user</h1>
        <form action="" method="get">
            <SPAN class=mess_info>Username:</span> <input type="text" name="username" value="'.$_REQUEST['username'].'">&nbsp;&nbsp;<input class="submit" type="submit" value="search">
        </form>';
    }
    
    private static function _outUserMenu()
    {           
        $userBan = self::_getUserBans();    
    
        $isUserPremium = self::_getIsUserPremium();
        $startDate = '';
        $endDate = '';
        $comment = '';
        $allowTrade = 'f';

        if(count($userBan) > 0)
        {
            $startDate = date("Y-m-d", strtotime($userBan['start_date']));
            $startDate = explode('-', $startDate);
            
            $endDate = date("Y-m-d", strtotime($userBan['end_date']));
            $endDate = explode('-', $endDate);
            $comment = $userBan['comments'];
            $allowTrade = $userBan['allow_trade'];
        } else {
            $startDate[0] = date("Y");
            $startDate[1] = date("m");
            $startDate[2] = date("d");
            
            $endDate[0] = "";
            $endDate[1] = "-";
            $endDate[2] = "";
        }
        
        echo '
        <h1>User ban</h1>
        <SPAN class=mess_info>User: </span><span class="dir">'.self::$userName.'</span><br>
        <SPAN class=mess_info>Premium: </span><span class="dir">';
        if ($isUserPremium == 1) {
            echo "Да";
        } else {
            echo "Нет";
        }
        echo '</span><br>';
        if (!empty($comment)) {
            echo '<SPAN class=mess_info>Comment: </span><span class="dir">'.$comment.'</span><br>';
        }
        echo '
        <form action="?action=ban" method="post">
            <input type="hidden" name="username" value="'.self::$userName.'">
            <table cellspacing=0 border=1 cellpadding=0>
                <tr class=th_2>
                    <td>&nbsp;&nbsp;Ban Start&nbsp;&nbsp;</td>
                    <td>&nbsp;&nbsp;Ban End&nbsp;&nbsp;</td>
                </tr>
                <tr>';

        echo "
                    <td>&nbsp;
                        <input type=text size=4 maxlength=4 name='startdate[year]' value='{$startDate[0]}'>
                        <!--input type=text size=2 maxlength=2 name='startdate[month]' value='{$startDate[1]}'-->
                        <select name='startdate[month]'>
            ";
        
        for($i=1; $i<13; $i++)
        {
            echo "<option value=";
            
            if($i < 10)
            {
                echo "0";
            }
            echo $i;
            if($startDate[1] == $i)
            {
                echo " selected=1";
            }
            echo ">";
            if($i < 10)
            {
                echo "0";
            }
            echo $i;
        }
        
        echo "
                        </select>
                        <input type=text size=2 maxlength=2 name='startdate[day]' value='{$startDate[2]}'>
                        &nbsp;</td>
            ";
        echo "
                    <td>&nbsp;
                        <input type=text size=4 maxlength=4 name='enddate[year]' value='{$endDate[0]}'>
                        <!--input type=text size=2 maxlength=2 name='enddate[month]' value='{$endDate[1]}'-->
                        <select name='enddate[month]'>
            ";
        if ($endDate[1] == "-") {
            echo '<option value="-" selected=1>-';
        }
        for($i=1; $i<13; $i++)
        {
            echo "<option value=";
            
            if($i < 10)
            {
                echo "0";
            }
            echo $i;
            if($endDate[1] == $i)
            {
                echo " selected=1";
            }
            echo ">";
            if($i < 10)
            {
                echo "0";
            }
            echo $i;
        }
        
        echo "
                        </select>
                        <input type=text size=2 maxlength=2 name='enddate[day]' value='{$endDate[2]}'>
                        &nbsp;</td>
            ";
        
        if ($allowTrade == 't') {
            $checked = 'checked';           
        } else {
            $checked = '';
        }
        
        echo '</tr>
            </table><br>
            <input type="checkbox" name="allow_trade" '.$checked.'> <SPAN class=mess_info>Разрешить торговые операции</span><br>
            <input name="ban" class="submit" type="submit" value="Apply" style="width: 120px;">
        </form>';
    }
    
    private static function _banUser()
    {
        $startDate = GB_TypeFactory::CreateBase('GB_Date', self::$startDate);
        $endDate = GB_TypeFactory::CreateBase('GB_Date', self::$endDate);
        
        
        $period = new GB_DatePeriod($startDate, $endDate);
                
        self::$common->setDbId(0);
        
        self::$user->setPremiumBan($period, '', self::$allowTrade);
        
        // Коммитим
        GB_SingletonObjectStorage::CommitAllCommitable();
        
        //Отзываем армии с защиты   
        ME_BanUser::ReturnUserArmyOnDefend(self::$user->getId(), self::$common);
        
        echo "<h1>User ban info updated</h1>";
        actionLog(
            'User premium ban'.
            ' Username: '.self::$user->getName(),
            ' Start time: '.self::$startDate.
            ' End time: '.self::$endDate
        );
    }
    
    static private function _getUserBans()
    {
        $sql = "
            SELECT
                *
            FROM
                ds_user_premium_ban
            WHERE
                id_user = ".self::$user->getID()."
                AND
                (
                    end_date > now()
                    OR
                    end_date IS NULL
                )
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) == 0)
        {
            return $res;
        }
        
        return $res[0];
    }
    
    static private function _getIsUserPremium()
    {
        $sql = "
            SELECT 
                    u.id_user,
                    (
                        SELECT 
                            count(upd.id_user)
                        FROM 
                            ds_userprofidata as upd 
                        WHERE 
                            upd.id_user = u.id_user 
                            and upd.profi = true 
                            and upd.active = true 
                            and upd.enddate > now()
                    ) AS premiumactive
                    FROM
                        ds_user u
                    WHERE
                        u.id_user = ".self::$user->getID()."
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        return $res[0]['premiumactive'];
    }
}

UserPremiumBanController::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>