<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2011/05/10 13:23:48 $';

/**
 * Настройки мира
 *
 */
class SU_WorldOption
{
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    static private $cacheTime = 172800;
    
    static private $result;
    
    // Трехбуквенный идентификатор мира
    static private $worldIdn = '';
    
    // Id мира
    static private $worldId;
    
    // Id базы
    static private $dbId;

    static private $langID;

    
    static public function Get(array $data = array())
    {
        self::_initParams($data);
        
        self::$result = false;
        self::$result = DS_Cache::Singleton()->get(self::GetCacheMark());
        if(self::$result)
        {
            echo self::$result;
            return;
        }

        if(GameDown(0))
        {
            echo '<?xml version="1.0" encoding="utf-8"?>'."\n".'<game_down/>';
            return;
        }
            
        self::$common = DS_Common::CreateForCurentDB(0);
            
        self::_getHeader();
            
        self::_getWorldId();
            
        self::_getWorldSettings();
            
        self::_getFooter();
            
        self::$result = SU_OutputHandler::encodeData(self::$result);
            
        DS_Cache::Singleton()->set(self::GetCacheMark(), self::$cacheTime, self::$result);
        echo self::$result;
    }
    
    static private function _initParams(array $data)
    {
        if(isset($data['world']))
        {
            self::$worldIdn = pg_escape_string($data['world']);
            assert(!empty(self::$worldIdn));
        }
        if (isset($data['LANG']))
            self::$langID = $data['LANG'];
        else 
            self::$langID = 3;
    }
    
    static public function GetCacheMark()
    {
        return 'WORLD_OPTIONS_'.self::$worldIdn;
    }
    
    static private function _getHeader()
    {
        self::$result = '<?xml version="1.0" encoding="utf-8"?>'."\n".'<CONFIG>';
    }
    
    static private function _getFooter()
    {
        self::$result .= '</CONFIG>';
    }
    
    static private function _getWorldSettings()
    {
        $worldSettings = self::_getAllSettings();
        
        foreach ($worldSettings as $group => $groupModif)
        {
            self::$result .= '
                <PAR desc="'.$group.'">';
            
            foreach ($groupModif as $modifName => $modifArr)
            {
                $desc = str_replace("%", "", $modifArr['descforuser']);
                $desc = str_replace("`", "", $desc);
                $desc = str_replace("&", "", $desc);
                $desc = str_replace("*", "", $desc);
                $desc = str_replace("$", "", $desc);
                $desc = str_replace("/", "", $desc);
                $desc = str_replace("(", "", $desc);
                $desc = str_replace(")", "", $desc);
                $desc = str_replace("\"", "", $desc);
                $desc = str_replace("<", "", $desc);
                $desc = str_replace(">", "", $desc);
                
                $val = $modifArr['value'];
                if ($val < 0.01 && $val > 0) {
                    $val = number_format($val, 5, '.', '');
                }
                if ($val > 1000) {
                    $val = number_format($val, 0, '.', '');
                }
                
                //colordesc="FF0000"
                self::$result .= '
                    <setting desc="'.$desc.'" val="'.$val.'"/>
    
            ';
            }
            
            self::$result .= '
                </PAR>';
        }
        // Для переноса строки можно использавать <split/>
    }
    
    static private function _getAllSettings()   
    {
        self::$common->setDbId(self::$dbId);
        $_SESSION['dbId'] = self::$dbId;
        
        $sql = "
                SELECT
                            mtpl.descforuser".self::$langID." as descforuser,
                            coalesce(mt.local_name".self::$langID.", 'Other') as typename,
                            gm.modifiername,
                            gm.value
                        FROM
                            ds_gamemodifiertemplate mtpl
                        LEFT JOIN ds_gamemodifiertype mt ON (mtpl.id_modifiertype = mt.id_modifiertype)
                        LEFT JOIN ds_gamemodifier gm ON (mtpl.modifiername = gm.modifiername)
                        WHERE   
                            mtpl.indesc = 't'
                            AND 
                            gm.id_world = ".self::$worldId."
                        ORDER BY mt.sort_order, gm.modifiername
                ";
        
        $modifierDesc = self::$common->getResultArray("WORLD_SETTINGS_WORLD".self::$worldId, $sql, "", "", "GLOBAL_CACHE");
        assert(empty(self::$common->errormsg));

        foreach ($modifierDesc as $modifierKey => $modifierData)
        {
            $resArr[$modifierData['typename']][$modifierData['modifiername']]['descforuser'] = $modifierData['descforuser'];
            $resArr[$modifierData['typename']][$modifierData['modifiername']]['value'] = $modifierData['value'];
        }
        
        return $resArr;
    }
    
    static private function _getWorldId()   
    {
        // Если во входящих параметрах нет idn мира, берм первый попавшийся
        if (empty(self::$worldIdn)) {
            $sql = "
            SELECT 
                w.world_identifier
            FROM 
                ds_worldcollection w
            LIMIT 1
            ";
            $res = self::$common->getResultArray("", $sql, "", "", "");
            
            self::$worldIdn = $res[0]['world_identifier'];
        }
        
        assert(!empty(self::$worldIdn));
        
        $sql = "
                SELECT 
                    w.dbname, 
                    w.worldname, 
                    w.id_world
                FROM 
                    ds_worldcollection w
                WHERE 
                    w.world_identifier = '".self::$worldIdn."'";
        
        $res = self::$common->getResultArray("GET_WORLD_DATA_BY_IDN_".self::$worldIdn, $sql, "", "", "GLOBAL_CACHE");
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);
        
        // Получаем Id базы по ее имени
        foreach($GLOBALS['dbWorldAccess'] as $db_Id => $db)
        {
            if($res[0]['dbname'] == $db['server'])
            {
                self::$dbId = $db_Id;
                break;
            }
        }
        
        if(GameDown(self::$dbId))
        {
            die("GameDown");
        }
        
        self::$worldId = $res[0]['id_world'];
    }
}

?>