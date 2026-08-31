<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class EditWorld
{
    private $dbId;
    
    private $worldId;
    
    private $worldData = array();
    
    private $action = 'INDEX';
    
    private $common;
    
    function __construct($data)
    {
        $this->_getParams($data);
        
        $this->common = DS_Common::CreateForCurentDB(0);
        
        switch ($this->action) {
            case 'EDIT':
                $this->_updateWorldInfo();
                $this->_showWorldInfo();
                break;
                
            case 'WORLD':
                $this->_showWorldInfo();
                break;  
        
            default:
                $this->_out();
                break;
        }
    }
    
    private function _getParams($data)
    {
        assert(is_array($data));
    
        if(key_exists('world', $data))
        {
            $arr = explode("_", $data['world']);
            assert(count($arr) == 2);
            
            $this->dbId = (int)$arr[0];
            assert($this->dbId >= 0);
            
            $this->worldId = (int)$arr[1];
            assert($this->worldId > 0);
            
            
            if(key_exists('EDIT', $data))
            {
                assert(key_exists('world_descr', $data));
                $this->worldData['descr'] = $data['world_descr'];
                //assert(!empty($this->worldData['descr']));
            
                assert(key_exists('world_type', $data));
                $this->worldData['type'] = (int)$data['world_type'];
                assert($this->worldData['type'] > 0);
        
                assert(key_exists('world_name', $data));
                $this->worldData['name'] = $data['world_name'];
                assert(!empty($this->worldData['name']));
                
                assert(key_exists('world_status', $data));
                $this->worldData['status'] = (int)$data['world_status'];
                assert($this->worldData['status'] > 0);
            
                $this->action = 'EDIT';
            } else {
                $this->action = 'WORLD';
            }
        }
    }
    
    private function _updateWorldInfo()
    {
        $this->common->setDbId($this->dbId);
        
        $this->common->startTrans();
        $sql = "
            UPDATE  
                ds_world 
            SET
                description = '".pg_escape_string($this->worldData['descr'])."',
                worldname = '".pg_escape_string($this->worldData['name'])."',
                world_status = ".$this->worldData['status']."
            WHERE
                id_world = ".$this->worldId."
            ";
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
        
        $sql = "    
            UPDATE
                ds_worldhastype
            SET
                id_worldtype = ".$this->worldData['type']."
            WHERE
                id_world = ".$this->worldId."
            ";
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
        
        // Обновляем коллкцию
        $sql = "
        UPDATE    
                ds_worldcollection 
            SET
                description = '".pg_escape_string($this->worldData['descr'])."',
                worldname = '".pg_escape_string($this->worldData['name'])."',
                world_status = ".$this->worldData['status']."
            WHERE
                id_world = ".$this->worldId."
                 AND
                dbname = '".$GLOBALS['dbWorldAccess'][$this->dbId]['server']."'
        ";
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
        
        
        $this->common->closeTrans('COMMIT');
        
        // Обновляем коллкцию на мастере 
        $this->common->setDbId(0);
        
        // Обновляем коллкцию
        $sql = "
        UPDATE    
                ds_worldcollection 
            SET
                description = '".pg_escape_string($this->worldData['descr'])."',
                worldname = '".pg_escape_string($this->worldData['name'])."',
                world_status = ".$this->worldData['status']."
            WHERE
                id_world = ".$this->worldId."
                AND
                dbname = '".$GLOBALS['dbWorldAccess'][$this->dbId]['server']."'
        ";  
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
    }
    
    private function _getWorldList()
    {
        $this->common->setDbId(0);
        
        $sql = "
            SELECT
                worldname,
                id_world,
                dbname
            FROM
                ds_worldcollection
            ";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        
        $worldList = array();
        
        foreach($res as $k => $world)
        {
            $worldList[$this->common->getDbIdByName($world['dbname']).'_'.$world['id_world']] = $world['worldname'];
        }
        
        return $worldList;
    }
    
    private function _out()
    {
        $worldLsit = $this->_getWorldList();
        
        echo '
            <form action="" method="POST">
                <b>Игровой мир:</b>&nbsp;<select name="world">
        ';
        
        foreach($worldLsit as $id => $worldName)
        {
            echo '<option value="'.$id.'">'.$worldName;
        }
        
        echo '
                </select>&nbsp;&nbsp;
                <input type="submit" value="редактировать">
            </form>';
    }
    
    private function _getWorldInfo()
    {
        $this->common->setDbId($this->dbId);
        
        $sql = "
            SELECT
                *
            FROM
                ds_world as w
                INNER JOIN ds_worldhastype AS wht ON (wht.id_world = w.id_world)
            WHERE
                w.id_world = ".$this->worldId."
            ";

        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        
        assert(count($res) == 1);
        
        return $res[0];
    }
    
    private function _getWorldTypeList()
    {
        $sql = "
            SELECT
                *
            FROM
                ds_worldtype as wt
                INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = wt.id_localizedtype)
            WHERE
                ltl.id_language = 3
            ";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        
        return $res;
    }
    
    private function _getWorldStatusList()
    {
        $sql = "
            SELECT
                *
            FROM
                ds_worldstatus
            ";
        
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        
        return $res;
    }
    
    private function _showWorldInfo()
    {
        $info = $this->_getWorldInfo();
        
        $worldTypes = $this->_getWorldTypeList();
        $worldStatuses = $this->_getWorldStatusList();
        
        echo '
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<A href="./"><b><<< назад</b></A>
            <form action="" method="POST">
                <input type="hidden" name="world" value="'.$this->dbId.'_'.$this->worldId.'">
                <input type="hidden" name="EDIT" value="1">
                <b>Название мира:</b>&nbsp;<input type="text" name="world_name" value="'.$info['worldname'].'"><br>
                <b>Описание:</b>&nbsp;<br><textarea cols=100 rows=5 name="world_descr">'.$info['description'].'</textarea><br>
                <b>Тип мира:</b>&nbsp;<select name="world_type">
            ';
        
        foreach($worldTypes as $k => $worldType)
        {
            echo '<option value="'.$worldType['id_worldtype'].'"';
            
            if($worldType['id_worldtype'] == $info['id_worldtype'])
            {
                echo ' selected="1" ';
            }
            
            echo '>'.$worldType['text'];
        }
        
        echo '
                </select><br>
                <b>Статус мира:</b>&nbsp;<select name="world_status">
            ';
        
        foreach($worldStatuses as $k => $worldStatus)
        {
            echo '<option value="'.$worldStatus['id_worldstatus'].'"';
            
            if($worldStatus['id_worldstatus'] == $info['world_status'])
            {
                echo ' selected="1" ';
            }
            
            echo '>'.$worldStatus['status_name'];
        }
        
        echo '
                </select><br><br>
                <input type="submit" value="изменить">
            </form>
            ';
        
    }
}

$w = new EditWorld($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>