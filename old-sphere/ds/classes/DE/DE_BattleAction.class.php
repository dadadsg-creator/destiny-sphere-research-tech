<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Действие во время боя. Типо отметка что в этом раунде случилось что-то.
 * Вообщем-то для логов военных надо.
 */

class DE_BattleAction extends DE_Base
{
    private $locolizedActions = array();

    private $langID;
    
    //private $paramsToLocalize = array('RELIGION', 'END_REASON', 'RESOURCE_NAME', 'UNIT_NAME', 'RACE_NAME');

    public function __construct($langID)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_integer($langID) && $langID>0);
        parent::__construct($langID);

        $data = DS_Battle::GetBattleActionsByLang($langID);
        assert(is_array($data) && count($data)>0);

        foreach($data as $k=>$v)
        {
            $this->locolizedActions[$v['battleactionname']] = $v['text'];
        }
    }

    /**
     * Enter description here...
     *
     * @return DE_BattleAction
     */
    public static function CreateForCurentLang()
    {
        $langID = (int)$_SESSION['lang'];
        assert(is_int($langID) && $langID>0);

        return self::CreateFromLangID($langID);
    }

    /**
     * Enter description here...
     *
     * @return DE_BattleAction
     */
    public static function CreateForWorldDefaultLang()
    {
        assert(defined('DEFAULT_LANGUAGE'));

        return self::CreateFromLangID(constant('DEFAULT_LANGUAGE'));
    }

    /**
     * Enter description here...
     *
     * @param int $langID
     * @return DE_BattleAction
     */
    static public function CreateFromLangID($langID)
    {
        assert(is_int($langID) && $langID>0);
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $langID);
    }

    /**
     * Enter description here...
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public function formAction($name, array $params, $serialize=true)
    {
        assert(!empty($name));
        //assert(array_key_exists($name, $this->locolizedActions));

        $multyLang = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_LOG_MULTY_LANG');

        if ($multyLang == 1 && $serialize) {
            $str = array('NAME' => $name, 'PARAMS' => $params);
        } else {
            if(count($params) == 0)
            {
                return $this->locolizedActions[$name];
            }

            $str = $this->locolizedActions[$name];
            
            foreach($params as $k=>$v)
            {
                if ($k == 'RELIGION') {
                    if ($v == 100) {
                        $v = '-';
                    } else {
                        $v = DE_Religion::CreateFromID($v)->getLocolizedName();
                    }
                } elseif ($k == 'RACE_NAME') {
                    $v = DE_Race::CreateFromID($v)->getLocalizedName();
                } elseif ($k == 'RESOURCE_NAME') {
                    $v = DE_Resource::CreateFromName($v)->getLocolizedName();
                } elseif ($k == 'UNIT_NAME') {
                    $v = DE_Unit::CreateFromName($v)->getLocalizedName();
                } elseif ($k == 'END_REASON') {
                    $v = DE_BattleEndReason::CreateFromID($v)->getLocolizedReason();
                }
                
                $str = str_replace("{".$k."}", $v, $str);
            }
        }
//SU_DebugTools::LogAll($str, 'Annita.log');        
        return $str;
    }

    protected function _invariant($mode = 'public')
    {
        parent::_invariant($mode);

        assert(is_array($this->locolizedActions) && count($this->locolizedActions));
    }

    public function __destruct()
    {
        parent::__destruct();
    }
}

?>