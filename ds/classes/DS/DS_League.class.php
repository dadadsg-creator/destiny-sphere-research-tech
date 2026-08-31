.<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * —татический класс бибилотека лиг.
 */
class DS_League extends DS_Base
{

    /**
    * @return ¬озвращ€ет сведени€ по лиге   
    *
    */
    static function GetByID ( $ID )
    {
        settype ($ID, 'integer');
        assert ($ID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    *
                FROM 
                    ds_league           
                WHERE 
                    id_league = ".$ID;
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $ID), $sql, "", "", "GLOBAL_CACHE" );
        assert(empty($common->errormsg));

        if (count($res)>0)
        {
            return $res[0];
        }else {
            return false;
        }

    }//END OF FUNCTION
    
    
    static public function GetLeagueLife($userInstanceID)
    {
        assert(is_int($userInstanceID));
        
        $common = self::getCommon();

        // достаточно ли прошло времени
        $sql = "
                    SELECT 
                        id_league,
                        league_name
                    FROM
                        ds_league
                    WHERE 
                        min_life_time < 
                                (SELECT 
                                    extract('days' from (now() - creation_date)) AS days
                                FROM 
                                    ds_userinstance 
                                WHERE
                                    id_userinstance = $userInstanceID
                                )
                    ORDER BY id_league DESC
                    LIMIT 1";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));
    
        if (count($res) > 0) {
            return $res[0];
        } 
        return false;
    }
    
    static public function GetLeagueByLevel($level)
    {
        assert(is_int($level) && $level > 0);
        
        $common = self::getCommon();

        // Ћига, соответствующа€ данному уровню
        $sql = "
                    SELECT 
                        id_league,
                        league_name
                    FROM
                        ds_league
                    WHERE 
                        min_level <= ".$level."
                                
                    ORDER BY id_league DESC
                    LIMIT 1";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $level), $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0];
    }
    
    static public function GetLeagueArmyes($totalArmysPower)
    {
        assert(is_double($totalArmysPower) && $totalArmysPower >= 0);
        
        $common = self::getCommon();

        // Ћига, соответствующа€ данному весу армий
        $sql = "
                    SELECT 
                        id_league,
                        league_name
                    FROM
                        ds_league
                    WHERE 
                        min_armies_weight <= ".$totalArmysPower."
                    ORDER BY id_league DESC
                    LIMIT 1";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $totalArmysPower), $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0];
    }
    
    static public function GetLeagueOneArmye($armyStars)
    {
        assert($armyStars >= 0);
        
        $common = self::getCommon();

        // Ћига, соответствующа€ данному весу армий
        $sql = "
                    SELECT 
                        id_league,
                        league_name
                    FROM
                        ds_league
                    WHERE 
                        min_one_army_stars <= ".$armyStars."
                    ORDER BY id_league DESC
                    LIMIT 1";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $armyStars), $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0];
    }
    
    static public function GetMaxLeague()
    {
        $common = self::getCommon();

        // Ћига, соответствующа€ данному весу армий
        $sql = "
                    SELECT 
                        id_league
                    FROM
                        ds_league
                    ORDER BY id_league DESC
                    LIMIT 1";
        $res = $common->getResultArray(self::FormMark(__METHOD__), $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);

        return (int)$res[0];
    }
}//END OF CLASS


?>