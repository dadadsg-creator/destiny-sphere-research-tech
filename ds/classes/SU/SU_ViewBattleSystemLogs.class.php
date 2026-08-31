<?php $CVS_Version = "$Revision: 1.11 $ ; $Date: 2007/06/01 10:10:58 $";

class SU_ViewBattleSystemLogs extends BS_Base
{
    private $common;

    private $DBID = null;

    private $userName = null;

    private $worldID = null;

    private $battleID;

    private $action;

    private $flag;

    private $UIID;

    private $returnBattleID = null;
    private $returnBattlesID = array();
    const GROUP_ACTION = true;
    const SINGLE_ACTION = false;

    public function __construct()
    {
        $this->common = DS_Common::CreateForCurentDB();
    }

    public function main($data, $flag = false)
    {
        $this->_getParams($data);

        $out = '';

        $this->flag = $flag;

        $this->common->setDbID($this->DBID);
        switch ($this->action)
        {
            case 'getsystemlog':
                $this->_viewSystemLog();
                break;
            case 'getuserbattles':
                $this->_getUserBattles();
                $this->_getBattlesList();
                break;
            case 'returnbattle':
                $this->returnBattle(SU_ViewBattleSystemLogs::SINGLE_ACTION);
                $this->_getUserBattles();
                break;
            case 'returnbattlegroup':
                $this->returnBattle(SU_ViewBattleSystemLogs::GROUP_ACTION);
                $this->_getUserBattles();
                break;
            default:
                $this->_getUserBattles();

        }

        return $out;
    }

    private function _viewSystemLog()
    {
        assert($this->battleID > 0);
        assert(!is_null($this->DBID));

        $this->common->setDbID($this->DBID);

        $sql = "SELECT
                    b.id_battle,
                    b.battlestartdate,
                    b.battleenddate,
                    b.winner
                FROM
                    ds_battle as b
                WHERE
                    b.id_battle = ".$this->battleID;
        $battleData = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        if ($_REQUEST['system']) {
            $sql = "SELECT *
                    FROM ds_battlesystemreport
                    WHERE id_battle = ".$this->battleID."
                    ORDER BY id_battlesystemreport";
        }
        else {
            $sql = "SELECT *
                    FROM ds_battlereport
                    WHERE id_battle = ".$this->battleID."
                    ORDER BY id_battlereport";
        }
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        $out = "<h1>BATTLE ".($_REQUEST['system']?'SYSTEM':'USER')." LOG</h1>";

        $out .= "<div style=\"color:#cccccc\">";
        $out .= "<b>ID:</b> ".$battleData[0]['id_battle']."<br> ";
        $out .= "<b>START:</b> ".$battleData[0]['battlestartdate']."<br> ";
        $out .= "<b>END:</b> ".$battleData[0]['battleenddate']."<br> ";

        foreach($res as $k => $v)
        {
            $out .= nl2br($_REQUEST['system']?$v['log_record']:$v['report']);
        }

        $out .= "</div>";

        echo $out;
        return;
    }

    private function _getUserBattles()
    {
        $sql = "
            SELECT
                *
            FROM
                ds_world";
        $worldsList = $this->common->getMultyArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        $options = '';
        foreach($worldsList as $k=>$worldData)
        {
            $prefixName = $worldData["dbname"]."_";
            $prefixID = $worldData["dbId"]."_";

            if($this->DBID == $worldData["dbId"] && $this->worldID == $worldData['id_world'])
            {
                $selected = 'SELECTED';
            }else{
                $selected = '';
            }

            $options .= '<option value="'.$prefixID.$worldData['id_world'].'" '.$selected.'>'.$prefixName.$worldData['worldname'].'</option>';
        }

        $out = "<h1>VIEW BATTLES SYSTEM LOGS</h1>";
        $out .= "<form method=POST>";

        $out .= '<b>WORLD</b>: <select name="world_id">
                '.$options.'
                </select> ';
        $out .= '<b>USER:</b> <input type="text" name="username" value="'.$this->userName.'">';
        $out .= '<input type="hidden" name="action" value="getuserbattles"> <input type="submit" value="Search">';
        $out .= "</form><BR><BR>";

        //$out .= $this->_getBattlesList();

        echo $out;
        return;
    }

    private function _getBattlesList()
    {
        if(empty($this->userName))
        {
            return '<font color=red><h3>Enter username!!</h3></font>';
        }

        assert($this->worldID > 0);


        $sql = "
            SELECT                  
                b.id_battle,
                b.battlestartdate,
                b.battleenddate,
                bp.participantflag,
                bp.iswinner,
                bp.iscombowner,
                b.id_battlecomb
            FROM
                ds_battle as b
                INNER JOIN ds_battleparticipant AS bp ON (bp.id_battle = b.id_battle)
                INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = bp.id_userinstance)
                INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
                INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
            WHERE
                u.username = '".$this->userName."'
                AND
                w.id_world = ".$this->worldID."
            ORDER BY b.battlestartdate";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        if($this->flag == 'trueFlag')
        {
            $out = "<form action='".$_SERVER['PHP_SELF']."' method='get'>
                    <input type='hidden' name='action' value='returnbattlegroup' />
                    <input type='hidden' name='username' value='".$this->userName."' />
                    <input type='hidden' name='worldid' value='".$this->worldID."' />
                    <input type='hidden' name='databaseid' value='".$this->DBID."' />";
        }
        else $out = "";

        $out .= "<table border=1>
                <tr>
                    <th>ID</th>
                    <th>StartDate</th>
                    <th>EndDate</th>
                    <th>Type</th>
                    <th>IsWinner</th>
                    <th>IsCombOwner</th>
                    <th>Comb</th>
                    <th>Action</th>
                    <th>SystemLog</th>
                    <th>UserLog</th>";

        if ($this->flag == 'trueFlag')
        {
            $out .= "
                    <th>Return Button</th>";
        }

        $out .= "
                </tr>";

        foreach($res as $k=>$batleData)
        {
            $systemLogURL = $_SERVER['PHP_SELF']."?action=getsystemlog&system=1&battle_id=".$this->DBID."_".$batleData['id_battle'];
            $userLogURL = $_SERVER['PHP_SELF']."?action=getsystemlog&battle_id=".$this->DBID."_".$batleData['id_battle'];

            if($batleData['iswinner'] == 't')
            {
                $isWinner = '<font color=green>YES</font>';
            }else{
                $isWinner = '<font color=red>NO</font>';
            }

            if($batleData['iscombowner'] == 't')
            {
                $isCombOwner = '<font color=green>YES</font>';
            }else{
                $isCombOwner = '<font color=red>NO</font>';
            }

            $id = $batleData['id_battlecomb'];
            $id = (int)$id;

            $_SESSION['dbId'] = $this->DBID;

            $comb = DE_Comb::CreateFromID($id);
            $combFullName = $comb -> getFullName();

            $out .= "<tr>";
            if ($this->flag == 'trueFlag') $out .= "<td><input type='checkbox' name='group_action_".$k."' value='".$batleData['id_battle']."' /></td>";
            $out .= "<td>&nbsp;".$batleData['id_battle']."</td>";
            $out .= "<td>&nbsp;".$batleData['battlestartdate']."</td>";
            $out .= "<td>&nbsp;".$batleData['battleenddate']."</td>";
            $out .= "<td align=center>&nbsp;".$batleData['participantflag']."</td>";
            $out .= "<td align=center>&nbsp;".$isWinner."</td>";
            $out .= "<td align=center>&nbsp;".$isCombOwner."</td>";
            $out .= "<td align=center>&nbsp;".$combFullName."</td>";
            $out .= "<td align=center>&nbsp;<A href='".$systemLogURL."' target='_BLANK'>SystemLog</a>&nbsp;</td>";
            $out .= "<td align=center>&nbsp;<A href='".$userLogURL."' target='_BLANK'>UserLog</a>&nbsp;</td>";

            if ($this->flag == 'trueFlag')
            {
                $out .= "
                <td valign=middle>
                <input type='button' value='Return battle' onClick='conf$k()'>
                </td>";
            }
            $out .= "</tr>";
            $out .= "<script language='JavaScript'>
            <!--
            function conf$k(){
            if (confirm('Are you shure?')) {
            document.location.href='".$_SERVER['PHP_SELF']."?action=returnbattle&username=$this->userName&worldid=$this->worldID&battleid=".$batleData['id_battle']."&databaseid=$this->DBID'
        }
        }
        //-->
        </script>";
        }

        $out .= "</table>";

        if($this->flag == 'trueFlag') $out .= "<br /><input type='submit' onclick='return confirm(\"Are you sure?\")' value='Return selected battles' /></form>";


        echo $out;
        return;
    }

    private function _getParams($data)
    {
        if(key_exists('world_id', $data))
        {
            $tmp = explode('_', $data['world_id']);
            assert(count($tmp) == 2);

            $this->DBID = (int)$tmp[0];
            assert($this->DBID >= 0);

            $this->worldID = (int)$tmp[1];
            assert($this->worldID > 0);
        }

        if(key_exists('worldid', $data))
        {
            $this->worldID = (int)$data['worldid'];
            assert($this->worldID > 0);
        }

        if(key_exists('battleid', $data))
        {
            $this->returnBattleID = (int)$data['battleid'];
            assert($this->returnBattleID > 0);
        }

        if(key_exists('databaseid', $data))
        {
            $this->DBID = (int)$data['databaseid'];
            assert($this->DBID >= 0);
        }

        foreach ($data as $key => $value)
        {
            if(substr($key, 0, 13) == 'group_action_')
            {
                $this->returnBattlesID[] = intval($value);
            }
        }

        if(key_exists('battle_id', $data))
        {
            $tmp = explode('_', $data['battle_id']);
            assert(count($tmp) == 2);

            $this->DBID = (int)$tmp[0];
            assert($this->DBID >= 0);

            $this->battleID = (int)$tmp[1];
            assert($this->battleID > 0);
        }

        if(key_exists('username', $data))
        {
            $this->userName = $data['username'];
            //assert(!empty($this->userName));
        }

        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
        }
    }

    private function returnBattle($group_action = false)
    {
        if ($this->flag != "trueFlag")
        {
            echo "<font color=#FF0000><h2>You can't return battle!</h2></font>";
            return;
        }

        $group_action ? assert(count($this->returnBattlesID) > 0) : assert($this->returnBattleID >= 0);
        
        // Выбираем имя мира
        $sql = "SELECT
                    worldname
                FROM
                    ds_world
                WHERE
                    id_world = ".$this->worldID;

        $worldName = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        if($group_action)
        {
            foreach ($this->returnBattlesID as $battleId) {
                $this->returnBattleById($battleId);
            }
            actionLog("Return Battles", "Battle IDs (".implode(", ", $this->returnBattlesID)."), user $this->userName, world ".$worldName[0][worldname] );
        }
        else
        {
            $this->returnBattleById($this->returnBattleID);
            actionLog("Return Battle", "Battle ID $this->returnBattleID, user $this->userName, world ".$worldName[0][worldname] );
        }

        //Логируем действие
        echo "<font color=#FF0000><h2>ОК</h2></font>";
    }

    public function returnBattleById($battleId)
    {
        $battleId = (int) $battleId;
        
        // Логируем откатанный бой
        $sql = "SELECT *
                FROM ds_battlesystemreport
                WHERE id_battle = ".$battleId."
                ORDER BY id_battlesystemreport";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        
        SU_DebugTools::LogAll($res, 'ReturnButtle.log');

        $sql = "select id_userinstance, battlexp from ds_battleparticipant where id_battle=$battleId";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        foreach ($res as $row) {
            $sql = "update ds_battlexp set value=value-{$row['battlexp']}, absolute_value=absolute_value-{$row['battlexp']} where id_userinstance={$row['id_userinstance']}";
            $this->common->executeQuery($sql);
        }

        $sql = "select bp.id_userinstance, sum(uha.value * r.exchangerate * br.loses)/10 as prod_weight
            from ds_battleparticipant bp, ds_battleresult br, ds_unithasattribute uha, ds_resource r
            where bp.id_battle=$battleId
                and bp.id_battleparticipant=br.id_battleparticipant
                and br.id_unit=uha.id_unit
                and uha.id_attribute=r.id_attributename
            group by bp.id_userinstance";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        foreach ($res as $row) {
            $sql = "update ds_army_weight set army_weight_lost=army_weight_lost-{$row['prod_weight']} where id_userinstance={$row['id_userinstance']}";
            $this->common->executeQuery($sql);
        }

        $sql = "SELECT rollback_battle($battleId)";

        $sqlResult = $this->common->executeQuery($sql);

        assert($sqlResult == TRUE);

        assert(empty($this->common->errormsg));
    }
}



?>