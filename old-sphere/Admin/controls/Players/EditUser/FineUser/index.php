<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once "classes/FineUserSearch.class.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=FineUser><font size=3><b>HELP</b></font></a></p>';


class FineUser
{
    private $err;
    private $common = false;
    private $action = 'index';
    private $fineAction = 'resource';
    
    private $word;
    private $dbId;
    private $worldId;
    private $worldName;
    
    private $username;
    private $id_user;
    private $userInstance;
    
    private $persent = 10;
    private $unitsParams = array();
    private $fined = '';
    
    function FineUser()
    {
        $this->common = DS_Common::CreateForCurentDB(0);
        
        if (!$this->getData($_REQUEST)) {
            ShowCMSMessage($this->err, 2);
            $this->showMenu();
            return;
        }       

        switch ($this->action)
        {
            case 'search':
                // Получаем userInstance
                $ui = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($this->id_user, (int)$this->worldId);
                $this->userInstance = DE_UserInstance::CreateFromID((int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($this->id_user, (int)$this->worldId));
        
                $this->showMenu();
                switch ($this->fineAction)
                {
                    case 'resource':
                        if(!$this->showResources())
                        {
                            ShowCMSMessage($this->err, 2);
                        }
                        break;
                    
                    case 'unit':
                        if(!$this->showArmy())
                        {
                            ShowCMSMessage($this->err, 2);
                        }
                        break;
                    default:
                        ShowCMSMessage('ERROR', 2);
                        break;
                }   
                
                break;
            
            case 'fine':
                // Получаем userInstance
                $ui = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($this->id_user, (int)$this->worldId);
                $this->userInstance = DE_UserInstance::CreateFromID((int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($this->id_user, (int)$this->worldId));
        
                $this->showMenu();
                switch ($this->fineAction)
                {
                    case 'resource':
                        if(!$this->fineResource())
                        {
                            ShowCMSMessage($this->err, 2);
                            $this->showResources();
                        } else {
                            ShowCMSMessage(local( 'DS_ACTION_SUCCEED ' ), 1);
                            echo "<font color='White'>
                                    <br>Fined:<br>".$this->fined."
                                    </font>";
                        }
                        break;
                    
                    case 'unit':
                        if(!$this->fineArmy())
                        {
                            ShowCMSMessage($this->err, 2);
                            $this->showArmy();
                        } else {
                            ShowCMSMessage(local( 'DS_ACTION_SUCCEED ' ), 1);
                            echo "<font color='White'>
                                    <br>Fined:<br>".$this->fined."
                                    </font>";
                        }
                    default:
                        ShowCMSMessage('ERROR', 2);
                        break;
                }
                break;
            default:
                $this->showMenu();
                break;
        }
    }
    
    private function getData($data)
    {
        if(isset($data['world']))
        {
            $this->word = $data['world'];
            $tmp = explode('_', $this->word);
            
            $this->dbId  = (int)$tmp[0];
            assert($this->dbId >= 0);
            
            $this->worldId  = (int)$tmp[1];
            assert($this->worldId > 0);
            
            $this->common->setDbId($this->dbId);
            $_SESSION['dbId'] = $this->dbId;
            $_SESSION['world'] = $this->worldId;
        
            $this->worldName  = FineUserSearch::getWorldNameById($this->common, $this->worldId);
            assert(!empty($this->worldName));
        }
        
        if(isset($data['username']))
        {
            if (empty($data['username'])) {
                $this->err = "Enter user name";
                return false;
            }
            
            $this->username = $data['username'];
            
            // Смотрим, есть ли игрок с таким именем
            $id_user = (int)FineUserSearch::getIdUserByName($this->common, $this->username);
            if(!$id_user || $id_user <= 0)
            {
                $this->err = "Wrong user name";
                return false;
            }
            
            $this->id_user = $id_user;
        }
        
        if(isset($data['persent']))
        {
            $this->persent = (int)$data['persent'];
            
            if($this->persent <= 0 || $this->persent > 100)
            {
                $this->err = "Wrong persent";
                return false;
            }
        }
        
        if(isset($data['delta']))
        {
            $this->unitsParams = $data['delta'];
        }
        
        if(isset($data['action']))
        {
            $this->action = $data['action'];
        }
        
        if(isset($data['fineAction']))
        {
            $this->fineAction = $data['fineAction'];
        }
        
        return true;
    }
    
    function showArmy()
    {
        $this->common->setDbId($this->dbId);
        $_SESSION['dbId'] = $this->dbId;
        $_SESSION['world'] = $this->worldId;
        
        //Получаем все соты игрока
        $combsCollection = $this->userInstance->getCombsCollection();
        
        // Смотрим, если ли бои на сотах игрока
        if (DS_Battle::NotEndedBattlesWithUser($this->userInstance->getId())) {
            $this->err = 'Not Ended Battles With User!';
            return false;
        }
        
        // Завершаем трансфер юнитов
        FineUserSearch::cancelTransferUnit($this->common, $this->userInstance->getId());
        
        // Отменяем всю торговлю, но не снимаем налог
        ME_CancelAllUserTrades::Cancel($this->userInstance, false); 
        
        
        //Рефрешим игрока, чтобы ивенты завершились
        //$command = "/usr/bin/php -f ".$_SERVER['DOCUMENT_ROOT'].$GLOBALS['dbWorldAccess'][$this->dbId]['dir']."useraction.php ".$this->common->dbId." ".$this->userInstance->getId();
        $command = "/usr/local/bin/php -f ".$_SERVER['DOCUMENT_ROOT'].$GLOBALS['dbWorldAccess'][$this->dbId]['dir']."useraction.php ".$this->common->dbId." ".$this->userInstance->getId();
        $out = '';
        $str = exec($command, &$out);
        $outString = implode("\n", $out);   
        if (!strpos($outString, 'refresh=')) {
            echo "!".$outString."!";
        }
        assert(strpos($outString, 'refresh='));

        // Начинаем транзакцию
        $this->common->executeQuery ( 'begin' );

        while($comb = $combsCollection->next())
        {                       
            // Сливаем армии в одну
            $this->_mergeAllArmysOnCombInHomeArmy($comb);
        }
                    
        // Коммитим 
        GB_SingletonObjectStorage::CommitAllCommitable();
        
        // Завершаем транзакци
        //$this->common->executeQuery ( 'rollback' );
        $this->common->executeQuery ( 'commit' );
        
        // Пишем в зеленку
        $message = '
            Все торги на вашей соте были отменены. Армии слиты в одну.
            По всем вопросам вы можете обратиться в Дисциплинарное Ведомство dv@destinysphere.com 
            или Техническая поддержку support@ru.destinysphere.com 
            
            -- 
            Вам желает удачи
            команда поддержки  DestinySphere';
        $this->sendMessage($message);
        
        // Получаем количество юнитов на соте
        $army = FineUserSearch::GetUserArmy($this->common, $this->username, $this->worldId);
        if (empty($army)) {
            $this->err = "Can't get user army";
            return false;
        }
    
        $temp_id_comb = '';
        $temp_id_unitcontainer = '';
        
        echo "<table width=100% border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td width=50% valign=top>
        <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
        <input type='hidden' name='action' value='fine'>
        <input type='hidden' name='fineAction' value='unit'>
        <input type='hidden' name='username' value='".$this->username."'>
        <input type='hidden' name='world' value='".$this->word."'>
        <input type='hidden' name='persent' value='".$this->persent."'>             
        
        <table width=500 border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                <table width=100% border=0 cellspacing=1 cellpadding=3>
        ";
        
        $this->selectUnitContainer = "<select name='id_unitcontainer'>";

        foreach ( $army as $key => $row ){
            

            if( $temp_id_comb != $row['id_comb'] ){
                print "
            <tr>
                <th colspan=3 bgcolor='444E6C' class='th_3'><span class=select_txt>Comb - <b>".$row['combname']." ( ".$row['coords']." )</b></span></th>
            </tr>
            ";
                $temp_id_comb = $row['id_comb'];
            }

            if( $temp_id_unitcontainer != $row['id_unitcontainer'] ){

                print "
            <tr>
                <td heidth=25 colspan=3 bgcolor='444E6C'></td>
            </tr>
            <tr>
                <th bgcolor='444E6C' class='th_2'>UnitName</th>
                <th bgcolor='444E6C' class='th_2'>UnitsAmount</th>
                <th bgcolor='444E6C' class='th_2'>Fine</th>
            </tr>
            ";
                $temp_id_unitcontainer = $row['id_unitcontainer'];
                $this->selectUnitContainer .= "<option value='".$row['id_unitcontainer']."'>".$row['unitcontainername']."</option>";
            }
            
            if ($row['unitname']) {
                $fine = floor($row['amount']*$this->persent/100);
            print "
            <tr>
                <td bgcolor='444E6C' class='maintxt_med' nowrap>".$row['unitname']."</td>
                <td bgcolor='444E6C' class='maintxt_med' align=right>".(!$row['unitname']? "" : sprintf( '%.0f', $row['amount']))."&nbsp;&nbsp;</td>
                <td bgcolor='444E6C' class='maintxt_med' align=center><input type='text' size=25 name='delta[".$row['id_unitcontainerhasunit']."]' value='".$fine."'></td>
            </tr>";
            }
        }
        
        echo "
                </table>
                </td>
            </tr>
        </table>
                </td>
            </tr>
        </table>
        <br><input type='submit' class=submit name='next' value='&nbsp;&nbsp;&nbsp;Fine&nbsp;&nbsp;&nbsp;'>
        </form>
        ";
        
        // Логируем
        actionLog( "Show armys of user: '".$this->username."' in World '".$this->worldName."'",'All armyes has been returned on comb and<br> all battled has been ended');

        return true;
    }
    
    function showResources()
    {       
        $this->common->setDbId($this->dbId);
        
        $_SESSION['lang'] = 3;
        
        // Завершаем трансфер ресурсов
        FineUserSearch::cancelTransferResources($this->common, $this->userInstance->getId());
        
        // отменяем всю торговлю, но не снимаем налог
        ME_CancelAllUserTrades::Cancel($this->userInstance, false); 
        
        //Рефрешим игрока, чтобы ивенты завершились
        $command = "/usr/bin/php -f ".$_SERVER['DOCUMENT_ROOT'].$GLOBALS['dbWorldAccess'][$this->dbId]['dir']."useraction.php ".$this->common->dbId." ".$this->userInstance->getId();
        //$command = "/usr/local/bin/php -f ".$_SERVER['DOCUMENT_ROOT'].$GLOBALS['dbWorldAccess'][$this->dbId]['dir']."useraction.php ".$this->common->dbId." ".$this->userInstance->getId();
        $out = '';
        $str = exec($command, &$out);
        $outString = implode("\n", $out);   
        if (!strpos($outString, 'refresh=')) {
            echo $outString;
        }
        // assert(strpos($outString, 'refresh='));
        
        // Пишем в зеленку
        $message = '
            Все торги на вашей соте были отменены.
            По всем вопросам вы можете обратиться в Дисциплинарное Ведомство dv@destinysphere.com 
            или Техническая поддержку support@ru.destinysphere.com 
            
            -- 
            Вам желает удачи
            команда поддержки  DestinySphere';
        $this->sendMessage($message);
        
        
        // Получаем количество ресов на соте
        $resources = FineUserSearch::GetUserResources($this->common, $this->username,  $this->worldId);

        if (empty($resources)) {
            $this->err = "Can't get user resources";
            return false;
        }
            
        $temp_id_comb = '';
        
        echo "<table width=100% border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td width=50% valign=top>
        <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
        <input type='hidden' name='action' value='fine'>
        <input type='hidden' name='fineAction' value='resource'>
        <input type='hidden' name='username' value='".$this->username."'>
        <input type='hidden' name='world' value='".$this->word."'>
        <input type='hidden' name='persent' value='".$this->persent."'>             
        
        <table width=500 border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                <table width=100% border=0 cellspacing=1 cellpadding=3>
        ";
        
        foreach ( $resources as $key => $row ){
            

            if( $temp_id_comb != $row['id_comb'] ){
                echo "
                <tr>
                    <th colspan=3 bgcolor='444E6C' class='th_3'><span class=select_txt>Comb - <b>".$row['combname']." ( ".$row['coords']." )</b></span></th>
                </tr>
                ";
                
                $temp_id_comb = $row['id_comb'];
                
                echo "
                <tr>
                    <td heidth=25 colspan=3 bgcolor='444E6C'></td>
                </tr>
                <tr>
                    <th bgcolor='444E6C' class='th_2'>Resource</th>
                    <th bgcolor='444E6C' class='th_2'>Resource Amount</th>
                    <th bgcolor='444E6C' class='th_2'>Fine</th>
                </tr>
                ";
            }

            $fine = floor($row['value']*$this->persent/100);
            
            echo "
            <tr>
                <td bgcolor='444E6C' class='maintxt'>". local ( 'ADMIN_' . $row['attributename']) ."</td>
                <td bgcolor='444E6C' class='maintxt'>".$row['value']."</td>
                <td bgcolor='444E6C' class='maintxt_med' align=center><input type='text' size=25 name='delta[".$row['id_combhasresource']."]' value='".$fine."'></td>
            </tr>";
        }
        
        echo "
                </table>
                </td>
            </tr>
        </table>
                </td>
            </tr>
        </table>
        <br><input type='submit' class=submit name='next' value='&nbsp;&nbsp;&nbsp;Fine&nbsp;&nbsp;&nbsp;'>
        </form>
        ";

        // Логируем
        actionLog( "Show resources of user: '".$this->username."' in World '".$this->worldName."'");

        return true;
    }
    
    private function fineArmy()
    {
        assert(!empty($this->username));
        $this->common->setDbId($this->dbId);
                
        if ( $this->unitsParams == "" || !is_array( $this->unitsParams ) ){
            $this->err = "Wrong Params";
            return false;
        }

        $fine = '';
        $totalFine = array();
        
        $this->common->executeQuery ( 'begin' );
    
        // Проходимся по каждему юниту
        foreach ( $this->unitsParams as $id_unitcontainerhasunit => $amount )
        {           

            $amount = (int)$amount;
            
            // Если количество не введено или равно нулю
            if($amount == '' || $amount == 0)
            {               
                continue;
            }
            
            // Если введено отрицательное количество
            if ( $amount < 0 )
            {
                $this->err = "Negative sign!";
                $this->common->executeQuery ( 'rollback' );
                return false;
            }
            
            // Получаем название юнита
            $unitname = FineUserSearch::getLocalizedUnitName($this->common, $id_unitcontainerhasunit, 0);

            if(!$unitname)
            {
                $this->common->executeQuery ( 'rollback' );
                $this->err = " SYSTEM ERROR - Can't get Unit Name! ";
                return false;
            }
            
            // Получаем текущее количество юнитов
            $sql = sprintf("select 
                                    amount
                                from
                                    ds_unitcontainerhasunit
                                where
                                    id_unitcontainerhasunit = %d",
                            $id_unitcontainerhasunit);
                            
            $res = $this->common->getResultArray ( "", $sql, "", "", "" );
            if($res === false) 
            {
                $this->common->executeQuery ( 'rollback' );
                $this->errormsg = " SYSTEM ERROR - addUnits::getUnitsAmount !!! ".$sql;
                return false;
            }
            
            $curAmount = (int)(float)$res[0]['amount'];
            
            // Если сейчас на соте меньше юнитов, чем мы пытаемя удалить
            if ($curAmount < $amount) {
                $amount = $curAmount;
            }
            
            $fine .= ' '.number_format($amount, "", ".", " ").' to '.$curAmount.' of '.$unitname.'<br>';
            if (key_exists($unitname, $totalFine)) {
                $totalFine[$unitname] += $amount;
            } else {
                $totalFine[$unitname] = $amount;
            }
            
            // Если мы снимаем всех юнитов данного типа в этой армии
            if($curAmount == $amount)
            {
                $sql = sprintf("delete from
                                            ds_unitcontainerhasunit
                                        where
                                            id_unitcontainerhasunit = %d",
                                $id_unitcontainerhasunit);
            } else {            
                $sql = sprintf("UPDATE
                                        ds_unitcontainerhasunit
                                    SET
                                        amount = amount - %d
                                    WHERE
                                        id_unitcontainerhasunit = %d",
                                $amount,
                                $id_unitcontainerhasunit
                                );
            }
            
            $this->common->executeQuery ( $sql );
            if ($this->common->errormsg) 
            {   
                echo $this->common->errormsg;
                $this->common->executeQuery ( 'rollback' );
                return false;
            }           
        }
        
        $letter = '';
        foreach ($totalFine as $res => $amount)
        {
            $letter .= $res.": ".number_format($amount, "", ".", " ")." 
            ";
        }
        $message = 'Здравствуйте!

            На аккаунт был наложен штраф. 
            Снято:
            '.$letter.'
            По всем вопросам вы можете обратиться в Дисциплинарное Ведомство dv@destinysphere.com 
            или Техническая поддержку support@ru.destinysphere.com 
            
            -- 
            Вам желает удачи
            команда поддержки  DestinySphere
            ';
        
        
        //Высылаем письмо
        $this->sendMessage($message);       
        
        //Логируем
        actionLog( "Fined ".$this->persent." % army of user: '".$this->username."' in World '".$this->worldName."'",'Fined:<br>'.$fine);
        
        $this->fined =  $fine;
        
        $this->common->executeQuery ( 'commit' );
        return true;
    }
    
    private function fineResource()
    {
        assert(!empty($this->username));
        assert(!empty($this->word));

        $this->common->setDbId($this->dbId);

        if ( $this->unitsParams == "" || !is_array( $this->unitsParams ) ){
            $this->err = "Wrong Params";
            return false;
        }

        $fine = '';
        $totalFine = array();
        
        $this->common->setDbId($this->dbId);
        $this->common->executeQuery ( 'begin' );
    
        // Проходимся по каждему ресурсу
        foreach ( $this->unitsParams as $id_combhasresource => $value )
        {           
            $value = (int)$value;

            // Если количество не введено или равно нулю
            if($value == '' || $value == 0)
            {               
                continue;
            }
            
            // Если введено отрицательное количество
            if ( $value < 0 )
            {
                $this->err = "Negative sign!";
                $this->common->executeQuery ( 'rollback' );
                return false;
            }
            
            $cur = FineUserSearch::GetCurrentAmountResource($this->common, (int)$id_combhasresource);
            $max = FineUserSearch::getMaxAmmount($this->common, (int)$id_combhasresource);      
            $oldValue = $value;         
            
            if(((int)$value + $cur) > $max)
            {
                $value = $max;
            } elseif ($value >= $cur) {
                $value = 0;
            } else {
                $value = $cur - $value;
            }

            // Вычитаем ресы        
            FineUserSearch::FineResource($this->common, (int)$id_combhasresource, $value);
            
            //Получаем имя ресурса
            $resName = FineUserSearch::GetResNameByIdCombHasRes($this->common, (int)$id_combhasresource);
            
            $fine .= "Fine ".number_format($oldValue, "", ".", " ")." of ".number_format($cur, "", ".", " ")." $resName<br>";   
            if (key_exists($resName, $totalFine)) {
                $totalFine[$resName] += $oldValue;
            } else {
                $totalFine[$resName] = $oldValue;
            }
        }
        
        $letter = '';
        foreach ($totalFine as $res => $amount)
        {
            $letter .= $res.": ".number_format($amount, "", ".", " ")."
            ";
        }

        $this->common->executeQuery ( 'commit' );

        //Логируем
        actionLog( "Fined ".$this->persent." % resources of user: '".$this->username."' in World '".$this->worldName."'",'Fined:<br>'.$fine);
        
        $message = 'Здравствуйте!

            На аккаунт был наложен штраф. 
            Снято:
            '.$letter.'
            По всем вопросам вы можете обратиться в Дисциплинарное Ведомство dv@destinysphere.com 
            или Техническая поддержку support@ru.destinysphere.com 
            
            -- 
            Вам желает удачи
            команда поддержки  DestinySphere
            ';
        
        // Пишем в  зеленку
        $this->sendMessage($message);
        
        $this->fined =  $fine;
        
        return true;
    }
        
    function showMenu()
    {
        ?>
        <form action='<? echo $_SERVER["PHP_SELF"]?>' method='POST'>
        <input type="hidden" name="action" value="search">
        
        <?
        switch ($this->fineAction)
        {
            case 'resource':
                $a = '<font color="White"><b>Fine Resources</b>&nbsp;&nbsp;-></font>&nbsp;&nbsp;<a href="?action=index&fineAction=unit">Fine Units</a>';
                break;
            case 'unit':
                $a = '<a href="?action=index&fineAction=resource">Fine Resources</a>&nbsp;&nbsp;<font color="White">->&nbsp;&nbsp;<b>Fine Units</b></font>';
                break;
            default:
                echo '<font color="Red">Wrong Action!</font>';
                return false;
        }
        
        echo $a."<br><br>";
        echo '<input type="hidden" name="fineAction" value="'.$this->fineAction.'">';
        ?>

        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                <table width=100% border=0 cellspacing=1 cellpadding=3>
                <tr>
                    <td bgcolor='444E6C' class=th_2>
                        <? echo local( 'DS_USER_WORLD' );?>
                    </td>
                    <td bgcolor='444E6C' class=th_2>
                        <? echo local('DS_USER_USERNAME');?>
                    </td>
                    <td bgcolor='444E6C' class=th_2>
                        Fine size
                    </td>
                </tr>
                <tr>
                    <td bgcolor='444E6C'>
                        <select name='world' style='width:250px;'>
                        <?
                        $sql = sprintf ( "select * from ds_world" );
                        $res = $this->common->getMultySql ($sql);
                        assert(empty($this->common->errormsg));
                                                                    
                        foreach ($res as $k=>$v)
                        {                           
                            if ($this->word == $v["dbId"]."_".$v["id_world"]) {
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
                    <td bgcolor='444E6C'>
                        <input type='text' name='username' size=30 value="<? echo $this->username; ?>">
                    </td>
                    <td bgcolor='444E6C'>
                        <input type='text' name='persent' size=3 value="<? echo $this->persent; ?>"><font color="White">%</font>
                    </td>
                </tr>
                <tr>
                    <td colspan=3 bgcolor='444E6C'>
                        <input type='submit' class=submit name='next' 
                            value='<? echo local( 'DS_ACTION_SEARCH' );?>' onclick="return confirm('All trades will be canseled. All battles will be ended and armyes wiil be returned on comb. Are you shure?');">
                    </td>
                </tr>
                </table>
                </td>
            </tr>
        </table>
        
        </form><br><br>
        <?
    }
    
    private function _mergeAllArmysOnCombInHomeArmy(DE_COMB $comb)
    {
        // Получаем домашку
        $homeArmy = DE_Army::CreateHomeArmyByCombID($comb->getId());

        // Получаем коллекцию армий игрока
        $allArmysCollection = $comb->GetCombArmysCollection();
        $_SESSION['lang'] = 3;
        
        $allArmysCollection->resetNext();
        while($army = $allArmysCollection->next())
        {
            // Если это домаашка, то ничего с ней не делаем
            if($army->getIsDefault())
            {
                continue;
            }

            // Получаем коллекцию групп юнитов в армии
            $unitsGroupCollection = DC_ArmyUnitGroups::CreateFromArmyID($army->getID());
            $unitsGroupCollection->resetNext();

            while($unitsGroup = $unitsGroupCollection->next())
            {
                // Количество юнитов
                $unitAmount = $unitsGroup->getAmount();

                // Смотрим, есть ли такая группа юнитов в домашке
                $homeUnitGroup = $homeArmy->getUnitsGroupCollection()->GetUnitGroupByUnit($unitsGroup->getUnit());
                if ($homeUnitGroup === false) {
                    // Если нет, то создаем эту группу
                    $homeUnitGroup = DE_UnitsGroup::CreateInDB($homeArmy, $unitsGroup->getUnit(), $unitAmount);
                    $homeArmy->getUnitsGroupCollection()->AddUnitGroup($homeUnitGroup);
                } else {
                    // Если есть, то просто добавляем этих юнитов
                    $homeUnitGroup->deltaAmount($unitAmount);
                }

                // Удаляем эту группу юнитов
                $unitsGroup->delete();
                $unitsGroupCollection->resetNext();
            }

            // Еще раз проверяем, что это не домашка!!
            assert(!$army->getIsDefault());

            // Удаляем эту армию
            $army->delete();
            $allArmysCollection->resetNext();
        }
    }
    
    private function sendMessage($message)
    {
        $this->common->setDBID(0);
        
        $messageScopeID = DS_Message::GetMessageScopeIDByName('SYSTEM');
        assert($messageScopeID > 0);
        
        $this->common->setDBID($this->dbId);
        
        $fromUIID = 0;

        $subject ="Снятие штрафа";

        $subject = pg_escape_string(urldecode($subject));
        assert(!empty($subject));
        
        $shortMassage = substr($message, 0, 100);
        $message = pg_escape_string(GL_EncodeGameMessage::Encode(urldecode($message)));
        assert(!empty($message));

        $senderName = 'DestinySphere';
        //$instances = DS_World::GetAllWorldInstances((int)$id_world);
        //$world = DE_World::CreateForCurrentWorld();
        $recieverName = $this->username;
        $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);
        DS_Message::AddMessageToUser($messageID, (int)$this->userInstance->getId());

        //Логиреум отправку письма
        actionLog("Send message in World:'".$this->worldName."'", "Masage: '$shortMassage...'");
    }
}

$obj = new FineUser();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>