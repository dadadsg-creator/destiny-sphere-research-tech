<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Ranking extends DS_Base
{

    public static function getByID($id)
    {
        assert(is_int($id));

        $common = self::getCommon();

        $sql = "
            SELECT *
            FROM ds_ranking
            WHERE id_userinstance = ".$id;

        $res = $common->getResultArray("", $sql, "", "");
        assert(empty($common->errormsg));

        if(!$res){
            $sql = "
                INSERT INTO 
                ds_ranking
                (id_userinstance, id_level)
                VALUES
                (".$id.", 1)";
            $common->executeQuery($sql);
            assert(empty($common->errormsg));

            $return['science_ranking'] = 0;
            $return['artefact_ranking'] = 0;
            $return['building_ranking'] = 0;
            $return['battle_ranking'] = 0;
            $return['id_level'] = 1;

            return $return;
        }else{
            return $res[0];
        }
    }

    /**
     * Берем уровень, соответствующий даным очкам
     *
     * @param 
     */
    static function GetAccordanceLevel($idUserInstance, $scienceRanking, $artefactRanking, $buildingRanking, $battleRanking)
    {
        $common = self::getCommon();
        
        assert($scienceRanking >= 0);
        assert($artefactRanking >= 0);
        //assert($buildingRanking >= 0);
        assert($battleRanking >= 0);
        
        $battleRanking = DE_BattleXP::CreateFromID($idUserInstance)->getXPAbsolute();
        
        // Достаем все модифаеры
        $worldSettigs = DE_WorldSettings::CreateForCurentWorld();
        $sciensK = $worldSettigs->get('RANKING_SCIENCE_MULTIPLIER');
        $artK = $worldSettigs->get('RANKING_ARCHEOLOGY_MULTIPLIER');
        $buildK = $worldSettigs->get('RANKING_BUILDING_MULTIPLIER');
        $battleK = $worldSettigs->get('RANKING_BATTLE_MULTIPLIER');
        
        $ranking = $scienceRanking*$sciensK + $artefactRanking*$artK + $buildingRanking*$buildK + $battleRanking*$battleK;
    
        $sql = "
                        SELECT 
                            id_level 
                        FROM 
                            ds_ranking_level
                        WHERE 
                            score_from < $ranking
                            AND 
                            score_to > $ranking";       

        $res = $common->getResultArray("", $sql, "", "");
        assert(empty($common->errormsg));   
    
        return  (int)$res[0]['id_level'];
    }
    
    /**
     * Апдейтим рейтинги
     *
     * @param 
     */
    static function UpdateRanking($idUserInstance, $scienceRanking, $artefactRanking, $buildingRanking, $battleRanking, $level)
    {
        $common = self::getCommon();
        
        assert($scienceRanking >= 0);
        assert($artefactRanking >= 0);
        // assert($buildingRanking >= 0);
        // assert($battleRanking >= 0);
        assert($level >= 0);
        
        if ($level == 0) {
            $level = 1;
        }
        
        $sql = "UPDATE
                    ds_ranking
                SET     
                    science_ranking = ".$scienceRanking.",          
                    artefact_ranking = ".$artefactRanking.",        
                    building_ranking = ".$buildingRanking.",    
                    -- battle_ranking = ".$battleRanking.",
                    id_level = ".$level ."
                WHERE
                    id_userinstance = ".$idUserInstance;        

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));       
    }
    
    static function GetDeltaBuildRanking($idComb)
    {
        assert($idComb > 0);
        
        $common = self::getCommon();
        
        $sql = "
                        SELECT
                            COALESCE(sum(b.building_score), 0) as score
                        FROM  ds_comb c
                        LEFT JOIN ds_attachedbuilding ab ON (ab.id_comb = c.id_comb)  
                        LEFT JOIN ds_building b ON (b.id_building = ab.id_building)                      
                        WHERE
                            c.id_comb = $idComb
                    ";
        $res = $common->getResultArray("", $sql, "", "");
        assert(empty($common->errormsg));

        return  $res[0]['score'];
    }
    
}

?>