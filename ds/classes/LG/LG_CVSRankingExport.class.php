<?php $CVS_Version = '$Revision: 1.12 $ ; $Date: 2011/09/12 12:46:25 $';

/**
 * Гейт анализатора.
 * Рейтинг игроков в формате CSV
 *
 */
class LG_CVSRankingExport extends LG_Base
{
    static private $timeOut;
    
    /**
     * Експорт
     *
     * @param string $world
     */
    static public function Export($world)
    {
        assert(ereg("[A-Z]{1,3}", $world));
        
        self::$world = $world;
        
        self::$timeOut = (int)DE_GlobalSettings::Get('ANALIZATOR_CVS_RANKING_TIMEOUT', true);
        if(!self::$timeOut)
        {
            self::$timeOut = 120000;
        }

        self::_getCommon();
        
        $res = self::_getRanking();
        
        self::_printRankingIsCSV($res);
    }
    
    /**
     * Выборка рейтинга
     *
     * @return array
     */
    static private function _getRanking()
    {
        /*$usersNotInRanking = "('HerrDerRinge', 'Curunir', 'Hexenkoenig', 'Morgoth', 'Support', 'Support2', 'Support3',
        'Mannix', 'Moira', 'Zeira', 'IraSpaatz', 'Achmed83', 'BarFetstein', 'Rolf131', 'Hedemeyer', 'Kardan', 'McCloud', 'Artur92', 'Jamal2', 'Lucinda', 'Jonas', 'CapralButa', 'RonaldReg', 'Spaatz18', 'KogaS', 'Silas', 'Kufal', 'Clarissa86', 'Jerricho7', 'SgtHeller', 'hanako', 'steele4', 'tcherenkoff', 'ranFORD', 'kentlog', 'armin', 'Tamzarian', 'Pillan',
        'Killer88', 'Cereanec', 'bryant', 'Tigel', 'Kozyrev', 'Lassiter', 'NisiiziN', 'nurijev', 'Kibben80', 'nileshill', 'marcopolus', 'Bryce777', 'zlaya1990', 'DaleKoga', 'Joshma', 'Elissa44', 'Skotina', 'GectorLove', 'TAPAH88', 'harizmius', 'meskoz', 'Clearwater', 'reddick', 'doomerv1666', 'AlainMoore', 'STARwarrior', 'AndyHoffman', 'Semen7ich', 'DaniilLucker', 'TOnOJIb', 'TuMyP13',
        'Sixto', 'Samtakoy', 'Turiza', 'Annita')";*/
        
        $usersNotInRanking = "('Support')";
        
        self::$common->startTrans();
        
        self::$common->executeQuery('SET STATEMENT_TIMEOUT TO '.self::$timeOut);
        
        $sql = "
            SELECT 
                fr.*,
                ui.id_league,
                
                COALESCE
                (
                    (
                        SELECT
                            ab.inupgrade
                        FROM
                            ds_comb AS c
                            INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                            INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                            INNER JOIN ds_attachedbuilding AS ab ON (ab.id_comb = c.id_comb)
                        WHERE
                            r.ringnumber != 4
                            AND
                            c.id_userinstance = fr.id_userinstance
                            AND
                            ab.id_building = 5
                        limit 1
                    ),
                    'f'
                ) as gate_wreck,
                
                (
                    SELECT
                        ab.active
                    FROM
                        ds_comb AS c
                        INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                        INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                        INNER JOIN ds_attachedbuilding AS ab ON (ab.id_comb = c.id_comb)
                    WHERE
                        r.ringnumber != 4
                        AND
                        c.id_userinstance = fr.id_userinstance
                        AND
                        ab.id_building = 6
                    limit 1
                ) as central_gate,
                
                (
                    SELECT
                        upd.enddate > now()
                    FROM
                        ds_userprofidata AS upd
                    WHERE
                        upd.id_user = fr.id_user
                        AND
                        upd.active
                    limit 1
                ) as prem,
                
                bxp.absolute_value as battlexp,
                
                (
                    SELECT
                        count(*)
                    FROM
                        ds_powershild_current_status AS pou
                    WHERE
                        pou.id_userinstance = fr.id_userinstance
                        AND
                        pou.end_date > now()
                        AND
                        pou.id_powershildstatusname = 5
                ) as power_shield,
                
                (
                    SELECT
                        count(*)
                    FROM
                        ds_vocationmode AS vm
                    WHERE
                        vm.id_userinstance = fr.id_userinstance
                        AND
                        (
                            vm.enddate IS NULL
                            OR
                            vm.enddate > NOW()
                        )
                ) as vocation_mode,
                
                (
                    SELECT
                        count(*)
                    FROM
                        ds_userban AS ub
                    WHERE
                        ub.id_user = fr.id_user
                        AND
                        ub.enddate > NOW()
                ) as user_ban,
                
                (
                    SELECT
                        count(*)
                    FROM
                        ds_user_premium_ban AS upb
                    WHERE
                        upb.id_user = fr.id_user
                        AND
                        upb.end_date > NOW()
                ) as premium_ban,
            
                u.accountactive,
                
                (ui.newb_defend_enddate > NOW()) as newb_defend
                    
            FROM 
                ds_finalranking AS fr
                LEFT JOIN ds_battlexp AS bxp USING (id_userinstance)
                INNER JOIN ds_user AS u USING (id_user)
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
            WHERE
                fr.id_world = ".self::$worldID."
                AND u.username NOT IN ".$usersNotInRanking."
                AND (u.npc is null or u.npc=false)
            ORDER BY fr.thefinal DESC
            ";
        
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        self::$common->closeTrans('COMMIT');
        
        return $res;
    }
    
    /**
     * Вывод в нужном формате
     *
     * @param array $res
     */
    static private function _printRankingIsCSV($res)
    {
        foreach ($res as $k => $rank)
        {
            
            /*
            Номер_по_порядку;
            ник;
            альянс тэг;
            id_раса;
            обычный_рейтинг;
            боевой_рейтинг(опыт);
            состояние_ворот(0 - еще не открывались,1 - открываются первый раз, 2 - закрыты, 3 - открыты);
            прем (0 - нет, 1 - есть); !!!!
            статус (0 - защита новичка, 1 - щит, 2 - бан, 3 - отпуск, 99 - нет ничего)
            */
            
            $str = ($k+1).';';
            $str .= $rank['username'].';';
            $str .= $rank['alliancetag'].';';
            $str .= $rank['id_race'].';';
            $str .= $rank['thefinal'].';';
            $str .= (float)$rank['battlexp'].';';
            
            $status = 0;
            
            if($rank['gate_wreck'] == 't')
            {
                $status = 1;
            }
            
            if($rank['central_gate'] == 'f')
            {
                $status = 2;
            }
            
            if($rank['central_gate'] == 't')
            {
                $status = 3;
            }
            
            $str .= $status.';';
            
            if($rank['prem'] == 't')
            {
                $str .= "1;";
            } else {
                $str .= "0;";
            }

            if($rank['newb_defend'] == 't')
            {
                $status = '0';
            } elseif($rank['power_shield']) {
                $status = '1';
            } elseif($rank['user_ban'] || $rank['accountactive'] == 'f' || $rank['premium_ban']) {
                $status = '2';
            } elseif($rank['vocation_mode']) {
                $status = '3';
            } else {
                $status = '99';
            }
            
            $str .= $status.';'.$rank['id_league'].';';
                    
            $str .= "\n";
            echo $str;
        }
    }
}

?>