<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/10/20 11:58:19 $';

/**
 * Гейт анализатора.
 * Получение списка сот в мире
 * Название не очень, так как возвращаются еще и домашние соты, а не только колонии
 * 
 */
class LG_ColoniesListExport extends LG_Base 
{
    static private $enc;
    /**
     * Исходная кодировка
     *
     * 
     */

    static public function Export($world)
    {
        if(isset($_SESSION['lang']))
        {
            $langID = $_SESSION['lang'];
        }else{
            $langID = 3;
        }


        switch($langID)
        {
            case 1:
                self::$enc = 'cp1250';
                break;
            case 2:
                self::$enc = 'cp1250';
                break;
            case 3:
                self::$enc = 'cp1251';
                break;
            case 5: // Литва
                self::$enc = 'cp1257';
                break;
            default:
                self::$enc = 'cp1251';

        }

        assert(ereg("[A-Z]{1,3}", $world));

        self::$world = $world;

        self::_getCommon();

        $combs = self::_getCombList();
        
        foreach($combs as $k => $comb)
        {
            $str = $k.";";
            $str .= $comb['username'].";";
            $str .= $comb['ringnumber'].";";
            $str .= $comb['complexnumber'].";";
            $str .= $comb['combnumber'].";";
            $str .= iconv(self::$enc, 'utf-8', $comb['combname']).";\n";
            
            echo $str;
        }
    }
    
    /**
     * Получение списка сот
     *
     * @return array
     */
    static private function _getCombList()
    {
        // Номер_по_порядку;ник;кольцо_соты;комплекс_соты;номер_соты;имя_соты;
        
        $sql = "
            SELECT
                u.username,
                r.ringnumber,
                cc.complexnumber,
                c.combnumber,
                cn.combname
            FROM
                ds_comb AS c
                INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
                INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                INNER JOIN ds_combname AS cn USING (id_comb)
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
                INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
            WHERE
                ui.id_world = ".self::$worldID."
                AND (u.npc is null or u.npc=false)
            ORDER BY u.username, r.ringnumber, cc.complexnumber, c.combnumber
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;
    }
}

?>