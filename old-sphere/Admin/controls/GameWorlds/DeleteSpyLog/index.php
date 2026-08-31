<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

ini_set('error_reporting', E_ALL);

class SpyLogDelete
{
    private $startDate;
    
    private $endDate;
    
    private $userName;
    
    private $objectType;
    
    private $dbId;
    
    private $worldId;
    
    private $action = 'index';
    
    public function __construct($data)
    {
        $this->_getParams($data);
        echo "<h1>Delete Log</h1>";
        
        switch ($this->action) {
            case 'index':
                $this->_outIndex(); 
                break;
                
            case 'delete_logs':
                $this->_deleteLogs();
                break;
        
            default:
                $this->_outIndex(); 
                break;
        }
    }
    
    private function _getParams($data)
    {
        assert(is_array($data));
        
        if(key_exists('world', $data))
        {
            $arr = explode('_', $data['world']);
            $this->dbId = (int)$arr[0];
            $this->worldId = (int)$arr[1];
            
            assert($this->dbId >= 0);
            assert($this->worldId > 0);
        }
        
        if(key_exists('username', $data))
        {
            $this->userName = $data['username'];
        }
        
        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
        }
    }
    
    private function _getWorldList()
    {
        $common = DS_Common::CreateForCurentDB(0);
        
        $sql = "
            SELECT
                wc.worldname,
                wc.dbname,
                wc.id_world
            FROM
                ds_worldcollection AS wc
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        foreach ($res as $k => $world)
        {
            $res[$k]['dbId'] = $common->getDBIDByName($world['dbname']);
        }
        
        return $res;
    }
    
    
    private function _outIndex()
    {
        $worlds = $this->_getWorldList();
        
        echo "
<form action='index.php' method='POST'>
<input type='hidden' name='action' value='delete_logs'>
<b><font color=white>User: </font></b><input type='text' name='username' value='".$this->userName."'>
<select name='world'>
";
        foreach ($worlds as $k => $world)
        {
            if($this->dbId == $world['dbId'] && $this->worldId == $world['id_world'])
            {
                $sel = 'selected';
            } else {
                $sel = '';
            }
            
            echo "<option $sel value=".$world['dbId'].'_'.$world['id_world'].">".$world['worldname'];
        }
        
        echo "
</select><br><br>
<input type='submit' class='submit' value='Стереть шпионские логи'>
</form>
        ";
    }
    
    private function _deleteLogs()
    {
        $common = DS_Common::CreateForCurentDB($this->dbId);
        $sql = "delete from ds_spy_log where log_date<now() - interval '1 week' and id_owner in (
            select ui.id_userinstance from ds_userinstance ui, ds_user u
            where u.username='".pg_escape_string($this->userName)."'
            and u.id_user=ui.id_user and ui.id_world=".$this->worldId."
        )";
        $common->executeQuery($sql);
        echo "<b><font color=white>Логи шпионажа юзера ".$this->userName." почищены</font></b>";
    }
}

$v = new SpyLogDelete($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>